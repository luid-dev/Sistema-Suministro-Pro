<?php
/**
 * Interfaz: registro de scripts, carga de CSS y vistas del frontend e íconos SVG.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Interfaz {

    /**
     * Registra los scripts del frontend. Cada vista encola solo el que necesita
     * y le pasa su configuración con wp_add_inline_script (sin datos sensibles).
     */
    public function registrar_assets() {
        $js = UAC_SSP_URL . 'frontend/js/';
        wp_register_script( 'html5-qrcode', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', array(), '2.3.8', true );
        wp_register_script( 'uac-login', $js . 'login.js', array(), UAC_SSP_VERSION, true );
        wp_register_script( 'uac-registro', $js . 'registro.js', array( 'html5-qrcode' ), UAC_SSP_VERSION, true );
        wp_register_script( 'uac-cortes', $js . 'cortes.js', array(), UAC_SSP_VERSION, true );
        wp_register_script( 'uac-tablero', $js . 'tablero.js', array(), UAC_SSP_VERSION, true );
    }

    /**
     * Devuelve el contenido de una o varias hojas de frontend/css para imprimirlas en línea.
     * Las vistas del sistema descartan los estilos del tema, por eso su CSS va incrustado.
     */
    private function css( ...$nombres ) {
        static $cache = array();
        $salida = '';
        foreach ( $nombres as $nombre ) {
            // Modo "pantalla dedicada" (opcional): oculta cabecera, pie y título del tema en las
            // páginas con shortcodes. Son las únicas reglas fuera de .uac-box / .uac-dash, por eso
            // viene desactivado. Se activa con: add_filter( 'uac_suministros_ocultar_tema', '__return_true' );
            if ( 'ocultar-tema' === $nombre && ! apply_filters( 'uac_suministros_ocultar_tema', false ) ) {
                continue;
            }
            if ( ! isset( $cache[ $nombre ] ) ) {
                $ruta              = UAC_SSP_DIR . 'frontend/css/' . sanitize_file_name( $nombre ) . '.css';
                $cache[ $nombre ] = is_readable( $ruta ) ? rtrim( (string) file_get_contents( $ruta ), "\n" ) : '';
            }
            $salida .= $cache[ $nombre ];
        }
        return $salida;
    }

    /**
     * Ruta absoluta de una plantilla de frontend/views.
     */
    private function ruta_vista( $nombre ) {
        return UAC_SSP_DIR . 'frontend/views/' . $nombre . '.php';
    }

    /**
     * Contenido interior de cada ícono (viewBox 24x24).
     */
    private function iconos() {
        return array(
            'camera'       => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
            'stop'         => '<rect x="5" y="5" width="14" height="14" rx="2"/>',
            'search'       => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
            'save'         => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
            'check'        => '<path d="M20 6 9 17l-5-5"/>',
            'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
            'x-circle'     => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
            'alert'        => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
            'info'         => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
            'package'      => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
            'ban'          => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
            'trash'        => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
            'plus'         => '<path d="M5 12h14"/><path d="M12 5v14"/>',
            'loader'       => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/>',
            'user'         => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'lock'         => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'chart'        => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
            'shield'       => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
            'calendar'     => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
            'clock'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'eye'          => '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
            'eye-off'      => '<path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c4.97 0 8.5 3.6 9.94 6.65a1 1 0 0 1 0 .7 10.75 10.75 0 0 1-1.44 2.49"/><path d="M14.08 14.16a3 3 0 0 1-4.24-4.24"/><path d="M17.48 17.5A10.75 10.75 0 0 1 2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 4.45-5.14"/><path d="m2 2 20 20"/>',
            'settings'     => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
            'logout'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
            'login'        => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/>',
            'trophy'       => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
            'users'        => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user-plus'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>',
        );
    }

    /**
     * Devuelve un ícono SVG inline.
     */
    private function icono( $nombre, $size = 16 ) {
        $iconos   = $this->iconos();
        $interior = isset( $iconos[ $nombre ] ) ? $iconos[ $nombre ] : '';
        return sprintf(
            '<svg class="uac-ico" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
            (int) $size,
            $interior
        );
    }
}
