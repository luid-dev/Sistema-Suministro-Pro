<?php
/**
 * Acceso de voluntarios: pantalla propia de ingreso y ajustes de wp-login.php.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Acceso {

    private function imprimir_login() {
        $aviso   = isset( $_GET['uac_acceso'] ) ? sanitize_key( wp_unslash( $_GET['uac_acceso'] ) ) : '';
        $destino = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), '' ) : '';
        $volver  = $this->ruta_actual();
        if ( '' === $destino ) {
            // Tras ingresar se abre la raíz, que muestra el Registro a quien puede registrar.
            $destino = $this->rel( home_url( '/' ) );
        }
        $avisos = array(
            'error'  => array( 'is-error', 'alert', 'Usuario o contraseña incorrectos. Revisa los datos e inténtalo de nuevo.' ),
            'vacio'  => array( 'is-error', 'alert', 'Escribe tu usuario y tu contraseña para ingresar.' ),
            'salida' => array( 'is-ok', 'check-circle', 'Cerraste sesión correctamente.' ),
            'solicitado' => array( 'is-ok', 'check-circle', '¡Solicitud enviada! El administrador revisará tu cuenta. Cuando la apruebe, ingresa con tu usuario y contraseña.' ),
            'pendiente'  => array( 'is-warn', 'clock', 'Tu cuenta aún está pendiente de aprobación por el administrador. Vuelve a intentarlo más tarde.' ),
            'reg_dup'    => array( 'is-error', 'alert', 'Ese usuario o correo ya está registrado. Ingresa con tu cuenta o prueba con otros datos.' ),
            'reg_datos'  => array( 'is-error', 'alert', 'Revisa los datos: nombres y apellidos (solo letras), correo válido, usuario (mín. 4 caracteres) y contraseña (mín. 8).' ),
            'bloqueo'    => array( 'is-error', 'alert', 'Demasiados intentos fallidos. Por seguridad, espera 15 minutos antes de volver a intentar.' ),
            'reg_limite' => array( 'is-error', 'alert', 'Demasiados intentos. Espera un momento e inténtalo de nuevo.' ),
            'reg_error'  => array( 'is-error', 'alert', 'No se pudo enviar la solicitud. Inténtalo de nuevo.' ),
        );
        $modo = ( 0 === strpos( $aviso, 'reg_' ) || 'solicitado' === $aviso ) ? 'solicitar' : 'ingresar';
        $foto = UAC_SSP_URL . 'frontend/img/login-incendio.webp';
        wp_add_inline_script(
            'uac-login',
            'window.uacLogin = ' . wp_json_encode( array(
                'iconoOjo'    => $this->icono( 'eye', 18 ),
                'iconoOjoOff' => $this->icono( 'eye-off', 18 ),
            ) ) . ';',
            'before'
        );
        include $this->ruta_vista( 'login' );
    }

    /**
     * wp-login.php sin acción (o con solo redirect_to) lleva a la pantalla de acceso del sistema.
     * Recuperar contraseña, cerrar sesión, reautenticación y peticiones POST siguen en WordPress.
     */
    public function login_hacia_app() {
        if ( ! apply_filters( 'uac_suministros_modo_app', true ) || ! apply_filters( 'uac_suministros_requiere_login', true ) ) {
            return;
        }
        if ( 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || is_user_logged_in() ) {
            return;
        }
        $accion = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'login';
        if ( 'login' !== $accion || array_diff( array_keys( $_GET ), array( 'redirect_to', 'action' ) ) ) {
            return;
        }
        $destino = $this->rel( $this->url_vista( 'registro' ) );
        if ( ! empty( $_GET['redirect_to'] ) ) {
            $destino = add_query_arg( 'redirect_to', rawurlencode( $this->rel( wp_unslash( $_GET['redirect_to'] ) ) ), $destino );
        }
        wp_safe_redirect( $destino );
        exit;
    }

    /**
     * Ruta a la que vuelve un intento fallido desde la pantalla del sistema.
     */
    private function volver_con_aviso( $aviso ) {
        $volver  = isset( $_POST['uac_volver'] ) ? wp_validate_redirect( wp_unslash( $_POST['uac_volver'] ), '/' ) : '/';
        $destino = isset( $_POST['redirect_to'] ) ? $this->rel( wp_validate_redirect( wp_unslash( $_POST['redirect_to'] ), '' ) ) : '';
        $args    = array( 'uac_acceso' => $aviso );
        if ( $destino && '/' !== $destino && $destino !== $volver ) {
            $args['redirect_to'] = rawurlencode( $destino );
        }
        wp_safe_redirect( add_query_arg( $args, $volver ) );
        exit;
    }

    /**
     * Campos vacíos: WordPress no dispara wp_login_failed, así que se atienden aquí.
     */
    public function login_campos_vacios( $user, $username, $password ) {
        if ( empty( $_POST['uac_login'] ) || ! is_wp_error( $user ) ) {
            return $user;
        }
        if ( array_intersect( $user->get_error_codes(), array( 'empty_username', 'empty_password' ) ) ) {
            $this->volver_con_aviso( 'vacio' );
        }
        return $user;
    }

    /**
     * Credenciales incorrectas: se vuelve a la pantalla del sistema (después de que otros
     * plugins de seguridad hayan registrado el intento en wp_login_failed).
     */
    public function login_fallido( $username, $error = null ) {
        if ( ! empty( $_POST['uac_login'] ) ) {
            $codigos   = is_wp_error( $error ) ? $error->get_error_codes() : array();
            $pendiente = in_array( 'uac_pendiente', $codigos, true );
            if ( in_array( 'uac_bloqueo', $codigos, true ) ) {
                $this->volver_con_aviso( 'bloqueo' );
            }
            $clave = 'uac_lf_' . md5( $this->ip_cliente() );
            set_transient( $clave, (int) get_transient( $clave ) + 1, 15 * MINUTE_IN_SECONDS );
            $this->volver_con_aviso( $pendiente ? 'pendiente' : 'error' );
        }
    }

    /**
     * Textos de wp-login.php en español (recuperar contraseña), aunque el sitio esté en inglés.
     */
    public function traducir_login( $traduccion, $texto, $dominio ) {
        if ( 'default' !== $dominio || ! isset( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] ) {
            return $traduccion;
        }
        static $mapa = array(
            'Username or Email Address' => 'Usuario o correo',
            'Password'                  => 'Contraseña',
            'Remember Me'               => 'Mantener la sesión',
            'Log In'                    => 'Ingresar',
            'Log in'                    => 'Ingresar',
            'Lost your password?'       => '¿Olvidaste tu contraseña?',
            'Get New Password'          => 'Obtener nueva contraseña',
            'Lost Password'             => 'Recuperar contraseña',
            'Show password'             => 'Mostrar contraseña',
            'Hide password'             => 'Ocultar contraseña',
            '&larr; Go to %s'           => '&larr; Volver a %s',
            'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.' => 'Escribe tu usuario o correo. Recibirás un mensaje con las instrucciones para crear una nueva contraseña.',
            'Check your email for the confirmation link, then visit the <a href="%s">login page</a>.' => 'Revisa tu correo y abre el enlace de confirmación. Luego vuelve a la <a href="%s">pantalla de acceso</a>.',
            'You are now logged out.'   => 'Cerraste sesión correctamente.',
        );
        return isset( $mapa[ $texto ] ) ? $mapa[ $texto ] : $traduccion;
    }

    public function login_url_cabecera() {
        return $this->rel( home_url( '/' ) );
    }

    public function login_texto_cabecera() {
        return 'Sistema-Suministro-Pro';
    }

    public function estilos_login() {
        wp_enqueue_style( 'uac-wp-login', UAC_SSP_URL . 'frontend/css/wp-login.css', array(), UAC_SSP_VERSION );
    }

    /**
     * Un Registrador que entra por wp-login.php llega al formulario, no al escritorio.
     */
    public function redireccion_tras_login( $redirect_to, $requested, $user ) {
        if ( $user instanceof WP_User && ! $user->has_cap( 'manage_options' ) && ( $user->has_cap( 'uac_registrar' ) || in_array( 'uac_registrador', (array) $user->roles, true ) ) ) {
            if ( empty( $requested ) || false !== strpos( $redirect_to, 'wp-admin' ) ) {
                return $this->url_vista( 'registro' );
            }
        }
        return $redirect_to;
    }
}
