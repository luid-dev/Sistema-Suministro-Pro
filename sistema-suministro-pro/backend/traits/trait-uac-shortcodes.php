<?php
/**
 * Shortcodes de las vistas: [uac_registro], [uac_dashboard] y [uac_cortes].
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Shortcodes {

    /**
     * SHORTCODE 1: [uac_registro]
     */
    public function shortcode_registro() {
        if ( ! $this->tiene_permiso_registro() ) {
            $logueado  = is_user_logged_in();
            $permalink = get_permalink() ? get_permalink() : home_url( '/' );
            ob_start();
            include $this->ruta_vista( 'registro/sin-permiso' );
            return ob_get_clean();
        }

        // Ruta relativa: usa el mismo protocolo (http/https) con el que se abrió la página.
        $rest_url = wp_make_link_relative( esc_url_raw( rest_url( 'uac/v1' ) ) );
        $nonce    = wp_create_nonce( 'wp_rest' );
        $usuario  = wp_get_current_user();

        wp_enqueue_script( 'uac-registro' );
        wp_add_inline_script(
            'uac-registro',
            'window.uacRegistro = ' . wp_json_encode( array(
                'apiBase' => $rest_url,
                'nonce'   => $nonce,
                'iconos'  => $this->iconos(),
            ) ) . ';',
            'before'
        );

        ob_start();
        include $this->ruta_vista( 'registro/formulario' );
        return ob_get_clean();
    }

    /**
     * SHORTCODE 2: [uac_dashboard]
     */
    public function shortcode_dashboard() {
        // Ruta relativa: usa el mismo protocolo (http/https) con el que se abrió la página.
        $rest_url = wp_make_link_relative( esc_url_raw( rest_url( 'uac/v1/resumen' ) ) );

        wp_enqueue_script( 'uac-tablero' );
        wp_add_inline_script(
            'uac-tablero',
            'window.uacTablero = ' . wp_json_encode( array(
                'urlResumen'  => $rest_url,
                'urlReporte'  => wp_make_link_relative( esc_url_raw( rest_url( 'uac/v1/reporte' ) ) ),
                'puedeEquipo' => $this->tiene_permiso_registro(),
                'nonce'       => wp_create_nonce( 'wp_rest' ),
                'miUsuario'   => is_user_logged_in() ? wp_get_current_user()->user_login : '',
                'iconoTrofeo' => $this->icono( 'trophy', 20 ),
            ) ) . ';',
            'before'
        );

        ob_start();
        include $this->ruta_vista( 'tablero/tablero' );
        return ob_get_clean();
    }

    /**
     * SHORTCODE 3: [uac_cortes] — Fechas de entrega (cortes) y metas por suministro.
     */
    public function shortcode_cortes() {
        if ( ! current_user_can( 'manage_options' ) ) {
            ob_start();
            include $this->ruta_vista( 'cortes/sin-permiso' );
            return ob_get_clean();
        }

        $rest_url = wp_make_link_relative( esc_url_raw( rest_url( 'uac/v1' ) ) );
        $nonce    = wp_create_nonce( 'wp_rest' );

        wp_enqueue_script( 'uac-cortes' );
        wp_add_inline_script(
            'uac-cortes',
            'window.uacCortes = ' . wp_json_encode( array(
                'apiBase' => $rest_url,
                'nonce'   => $nonce,
                'iconos'  => $this->iconos(),
            ) ) . ';',
            'before'
        );

        ob_start();
        include $this->ruta_vista( 'cortes/panel' );
        return ob_get_clean();
    }
}
