<?php
/**
 * Escritorio de WordPress: página de ajustes, modo demostración e importación CSV.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Admin {

    /**
     * Menú en Ajustes > Sistema-Suministro-Pro
     */
    public function registrar_menu_admin() {
        add_options_page(
            'Configuración Sistema-Suministro-Pro',
            'Sistema-Suministro-Pro',
            'manage_options',
            'sistema-suministro-pro-config',
            array( $this, 'renderizar_pagina_admin' )
        );
    }

    public function registrar_ajustes() {
        register_setting( 'uac_suministros_group', self::OPT_DEMO, array( 'sanitize_callback' => 'absint' ) );
    }

    /**
     * Estilos de la página de ajustes: solo se cargan en esa pantalla del escritorio.
     */
    public function estilos_admin( $hook ) {
        if ( 'settings_page_sistema-suministro-pro-config' === $hook ) {
            wp_enqueue_style( 'uac-admin', UAC_SSP_URL . 'frontend/css/admin.css', array(), UAC_SSP_VERSION );
        }
    }

    public function renderizar_pagina_admin() {
        include $this->ruta_vista( 'admin/ajustes' );
    }

    /**
     * Importación CSV (solo administrador): carreras y padrón de estudiantes.
     */
    public function procesar_importacion() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No autorizado.', 403 );
        }
        check_admin_referer( 'uac_importar' );
        global $wpdb;

        $volver = admin_url( 'options-general.php?page=sistema-suministro-pro-config' );
        $tipo   = isset( $_POST['tipo'] ) && 'estudiantes' === $_POST['tipo'] ? 'estudiantes' : 'carreras';
        $tmp    = isset( $_FILES['archivo']['tmp_name'] ) ? $_FILES['archivo']['tmp_name'] : '';
        $fh     = ( $tmp && is_uploaded_file( $tmp ) ) ? fopen( $tmp, 'r' ) : false;
        if ( ! $fh ) {
            wp_safe_redirect( add_query_arg( 'uac_imp', 'error', $volver ) );
            exit;
        }

        $primera = fgets( $fh );
        rewind( $fh );
        $sep = substr_count( (string) $primera, ';' ) > substr_count( (string) $primera, ',' ) ? ';' : ',';

        $mapa = $this->mapa_carreras();
        $hoy  = $this->fecha_hoy();
        $ok   = 0;
        $skip = 0;
        $n    = 0;

        while ( ( $fila = fgetcsv( $fh, 0, $sep ) ) !== false ) {
            $n++;
            if ( 1 === $n && isset( $fila[0] ) ) {
                $fila[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $fila[0] );
            }
            if ( null === $fila[0] || '' === trim( implode( '', $fila ) ) ) {
                continue;
            }
            $a = isset( $fila[0] ) ? trim( $fila[0] ) : '';
            $b = isset( $fila[1] ) ? trim( $fila[1] ) : '';
            if ( 1 === $n && in_array( strtolower( $a ), array( 'carrera_id', 'codigo', 'código', 'id' ), true ) ) {
                continue; // encabezado
            }

            if ( 'carreras' === $tipo ) {
                $id = strtoupper( $a );
                if ( '' === $id || '' === $b || strlen( $id ) > 20 ) {
                    $skip++;
                    continue;
                }
                $r = $wpdb->query( $wpdb->prepare(
                    "INSERT INTO {$this->t('carreras')} (carrera_id, nombre, facultad) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), facultad = VALUES(facultad)",
                    $id, mb_substr( $b, 0, 150 ), mb_substr( isset( $fila[2] ) ? trim( $fila[2] ) : '', 0, 200 )
                ) );
            } else {
                $codigo = strtoupper( $a );
                $cid    = strtoupper( $b );
                if ( ! $this->validar_codigo( $codigo ) || ! isset( $mapa[ $cid ] ) ) {
                    $skip++;
                    continue;
                }
                $r = $wpdb->query( $wpdb->prepare(
                    "INSERT INTO {$this->t('estudiantes')} (codigo, carrera_id, fecha_alta) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE carrera_id = VALUES(carrera_id)",
                    $codigo, $cid, $hoy
                ) );
            }
            false === $r ? $skip++ : $ok++;
        }
        fclose( $fh );

        $this->limpiar_cache();
        wp_safe_redirect( add_query_arg( 'uac_imp', $tipo . ':' . $ok . ':' . $skip, $volver ) );
        exit;
    }
}
