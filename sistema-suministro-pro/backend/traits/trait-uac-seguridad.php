<?php
/**
 * Seguridad: límite de intentos de ingreso, cabeceras HTTP e IP del cliente.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Seguridad {

    /** Seguridad (ISO 25010): tras 8 fallos en 15 minutos desde una IP se bloquea el ingreso temporalmente. */
    public function limitar_intentos( $user, $username, $password ) {
        if ( ! empty( $_POST['uac_login'] ) && (int) get_transient( 'uac_lf_' . md5( $this->ip_cliente() ) ) >= 8 ) {
            return new WP_Error( 'uac_bloqueo', 'Demasiados intentos fallidos.' );
        }
        return $user;
    }

    public function reiniciar_intentos() {
        delete_transient( 'uac_lf_' . md5( $this->ip_cliente() ) );
    }

    /** Seguridad: cabeceras que evitan incrustar el sistema en otros sitios y el sniffing de contenido. */
    private function cabeceras_seguridad() {
        if ( headers_sent() ) {
            return;
        }
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: camera=(self), microphone=(), geolocation=()' );
    }

    private function ip_cliente() {
        // Solo REMOTE_ADDR: las cabeceras de proxy (CF-Connecting-IP, X-Forwarded-For) las puede falsificar el cliente.
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
        return sanitize_text_field( wp_unslash( $ip ) );
    }
}
