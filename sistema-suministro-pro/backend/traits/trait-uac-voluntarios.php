<?php
/**
 * Voluntarios: solicitud pública de acceso y aprobación por el administrador.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Voluntarios {

    /** Rol sin permisos para quien solicitó acceso y espera la aprobación. */
    public function crear_rol_pendiente() {
        if ( ! get_role( self::ROL_PENDIENTE ) ) {
            add_role( self::ROL_PENDIENTE, 'Pendiente de aprobación', array() );
        }
        if ( ! get_role( 'uac_registrador' ) ) {
            add_role( 'uac_registrador', 'Registrador (Punto de Acopio)', array( 'read' => true, 'uac_registrar' => true ) );
        }
    }

    private function es_pendiente( $user ) {
        return $user instanceof WP_User && in_array( self::ROL_PENDIENTE, (array) $user->roles, true );
    }

    private function total_pendientes() {
        return count( get_users( array( 'role' => self::ROL_PENDIENTE, 'fields' => 'ID' ) ) );
    }

    /** Una cuenta pendiente no inicia sesión aunque la contraseña sea correcta. */
    public function bloquear_pendientes( $user, $username, $password ) {
        if ( $this->es_pendiente( $user ) ) {
            return new WP_Error( 'uac_pendiente', 'Tu cuenta está pendiente de aprobación.' );
        }
        return $user;
    }

    /**
     * Lee y valida "nombres" y "apellidos" del POST. Devuelve array( nombres, apellidos ) o null si no son válidos.
     * Quita espacios sobrantes; solo acepta letras (con tildes y ñ), espacios, apóstrofo, punto y guion.
     */
    private function leer_nombre_apellido() {
        $limpio = function ( $campo ) {
            $v = sanitize_text_field( wp_unslash( isset( $_POST[ $campo ] ) ? $_POST[ $campo ] : '' ) );
            return trim( preg_replace( '/\s+/u', ' ', $v ) );
        };
        $nombres   = $limpio( 'nombres' );
        $apellidos = $limpio( 'apellidos' );
        foreach ( array( $nombres, $apellidos ) as $parte ) {
            if ( mb_strlen( $parte ) < 2 || mb_strlen( $parte ) > 50 || ! preg_match( "/^\p{L}[\p{L} '.\-]*$/u", $parte ) ) {
                return null;
            }
        }
        return array( $nombres, $apellidos );
    }

    /** POST público desde la pantalla de acceso: crea la cuenta en estado pendiente. */
    public function procesar_solicitud() {
        if ( empty( $_POST['_uac_n'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_uac_n'] ) ), 'uac_solicitar' ) ) {
            $this->volver_con_aviso( 'reg_error' );
        }
        if ( ! empty( $_POST['uac_web'] ) ) { // trampa para bots
            $this->volver_con_aviso( 'solicitado' );
        }

        $clave_ip = 'uac_sol_' . md5( $this->ip_cliente() );
        $intentos = (int) get_transient( $clave_ip );
        if ( $intentos >= 5 ) {
            $this->volver_con_aviso( 'reg_limite' );
        }
        set_transient( $clave_ip, $intentos + 1, HOUR_IN_SECONDS );

        $partes  = $this->leer_nombre_apellido();
        $correo  = sanitize_email( wp_unslash( isset( $_POST['correo'] ) ? $_POST['correo'] : '' ) );
        $usuario = sanitize_user( wp_unslash( isset( $_POST['usuario'] ) ? $_POST['usuario'] : '' ), true );
        $clave   = isset( $_POST['clave'] ) ? (string) wp_unslash( $_POST['clave'] ) : '';
        $motivo  = sanitize_textarea_field( wp_unslash( isset( $_POST['motivo'] ) ? $_POST['motivo'] : '' ) );

        if ( ! $partes || ! is_email( $correo ) || strlen( $usuario ) < 4 || strlen( $clave ) < 8 ) {
            $this->volver_con_aviso( 'reg_datos' );
        }
        list( $nombres, $apellidos ) = $partes;
        $nombre = $nombres . ' ' . $apellidos;
        if ( username_exists( $usuario ) || email_exists( $correo ) ) {
            $this->volver_con_aviso( 'reg_dup' );
        }

        $id = wp_insert_user( array(
            'user_login'   => $usuario,
            'user_pass'    => $clave,
            'user_email'   => $correo,
            'display_name' => $nombre,
            'first_name'   => $nombres,
            'last_name'    => $apellidos,
            'role'         => self::ROL_PENDIENTE,
        ) );
        if ( is_wp_error( $id ) ) {
            $this->volver_con_aviso( 'reg_error' );
        }
        update_user_meta( $id, 'uac_motivo', mb_substr( $motivo, 0, 300 ) );
        update_user_meta( $id, 'uac_solicitado', time() );

        @wp_mail( get_option( 'admin_email' ), 'Nueva solicitud de voluntario · Sistema-Suministro-Pro', "$nombre ($usuario) pidió acceso al sistema.\nApruébala en: " . $this->url_vista( 'voluntarios' ) );

        $this->volver_con_aviso( 'solicitado' );
    }

    /** POST del administrador: aprobar, rechazar, agregar o quitar voluntarios. */
    public function procesar_gestion() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Solo el administrador puede gestionar voluntarios.', 403 );
        }
        check_admin_referer( 'uac_vol', '_uac_n' );

        $op      = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
        $uid     = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
        $destino = $this->rel( $this->url_vista( 'voluntarios' ) );
        $msg     = 'error';
        $objetivo = $uid ? get_userdata( $uid ) : false;

        if ( 'aprobar' === $op && $this->es_pendiente( $objetivo ) ) {
            $objetivo->set_role( 'uac_registrador' );
            @wp_mail( $objetivo->user_email, 'Tu acceso a Sistema-Suministro-Pro fue aprobado', "Hola {$objetivo->display_name}, ya puedes ingresar como voluntario con tu usuario \"{$objetivo->user_login}\" en: " . home_url( '/' ) );
            $msg = 'aprobado';
        } elseif ( 'rechazar' === $op && $this->es_pendiente( $objetivo ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user( $uid );
            $msg = 'rechazado';
        } elseif ( 'quitar' === $op && $objetivo && in_array( 'uac_registrador', (array) $objetivo->roles, true ) && ! $objetivo->has_cap( 'manage_options' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user( $uid );
            $msg = 'quitado';
        } elseif ( 'agregar' === $op ) {
            $partes  = $this->leer_nombre_apellido();
            $correo  = sanitize_email( wp_unslash( isset( $_POST['correo'] ) ? $_POST['correo'] : '' ) );
            $usuario = sanitize_user( wp_unslash( isset( $_POST['usuario'] ) ? $_POST['usuario'] : '' ), true );
            $clave   = isset( $_POST['clave'] ) ? (string) wp_unslash( $_POST['clave'] ) : '';
            if ( ! $partes || ! is_email( $correo ) || strlen( $usuario ) < 4 || strlen( $clave ) < 8 ) {
                $msg = 'datos';
            } elseif ( username_exists( $usuario ) || email_exists( $correo ) ) {
                $msg = 'duplicado';
            } else {
                $nuevo = wp_insert_user( array(
                    'user_login' => $usuario, 'user_pass' => $clave, 'user_email' => $correo,
                    'display_name' => $partes[0] . ' ' . $partes[1], 'first_name' => $partes[0], 'last_name' => $partes[1],
                    'role' => 'uac_registrador',
                ) );
                $msg = is_wp_error( $nuevo ) ? 'error' : 'agregado';
            }
        }

        wp_safe_redirect( add_query_arg( 'uac_msg', $msg, $destino ) );
        exit;
    }

    /** Vista del administrador: solicitudes, alta manual y voluntarios activos. */
    public function shortcode_voluntarios() {
        ob_start();
        if ( ! current_user_can( 'manage_options' ) ) {
            include $this->ruta_vista( 'voluntarios/sin-permiso' );
            return ob_get_clean();
        }

        $avisos = array(
            'aprobado'  => array( 'ok', 'Voluntario aprobado. Ya puede ingresar y registrar donaciones.' ),
            'rechazado' => array( 'ok', 'Solicitud rechazada y eliminada.' ),
            'agregado'  => array( 'ok', 'Voluntario agregado. Compártele su usuario y contraseña.' ),
            'quitado'   => array( 'ok', 'Se quitó el acceso del voluntario.' ),
            'datos'     => array( 'bad', 'Revisa los datos: nombre, correo válido, usuario (mín. 4) y contraseña (mín. 8).' ),
            'duplicado' => array( 'bad', 'Ese usuario o correo ya existe.' ),
            'error'     => array( 'bad', 'No se pudo completar la acción. Inténtalo de nuevo.' ),
        );
        $msg        = isset( $_GET['uac_msg'] ) ? sanitize_key( wp_unslash( $_GET['uac_msg'] ) ) : '';
        $pendientes = get_users( array( 'role' => self::ROL_PENDIENTE, 'orderby' => 'registered', 'order' => 'ASC' ) );
        $activos    = get_users( array( 'role' => 'uac_registrador', 'orderby' => 'display_name', 'order' => 'ASC' ) );
        $post_url   = admin_url( 'admin-post.php' );
        $campos     = function ( $op, $uid = 0 ) {
            echo '<input type="hidden" name="action" value="uac_voluntario"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
            if ( $uid ) {
                echo '<input type="hidden" name="uid" value="' . (int) $uid . '">';
            }
            wp_nonce_field( 'uac_vol', '_uac_n' );
        };
        include $this->ruta_vista( 'voluntarios/panel' );
        return ob_get_clean();
    }
}
