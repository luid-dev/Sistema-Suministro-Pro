<?php
/**
 * Plugin Name:       Sistema-Suministro-Pro
 * Description:       Sistema de Gestión de Suministros de la Universidad Andina del Cusco (Lector QR, base de datos propia en WordPress y Tablero en vivo).
 * Version:           2.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Sistema-Suministro-Pro (código abierto)
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sistema-suministro-pro
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

define( 'UAC_SSP_VERSION', '2.1.0' );
define( 'UAC_SSP_FILE', __FILE__ );
define( 'UAC_SSP_DIR', plugin_dir_path( __FILE__ ) );
define( 'UAC_SSP_URL', plugin_dir_url( __FILE__ ) );

// Backend: cada módulo es un trait; la clase principal los reúne.
$uac_ssp_modulos = array(
    'base-datos', 'api-donaciones', 'resumen', 'cortes', 'admin', 'modo-app',
    'acceso', 'voluntarios', 'seguridad', 'demo', 'interfaz', 'shortcodes',
);
foreach ( $uac_ssp_modulos as $uac_ssp_modulo ) {
    require_once UAC_SSP_DIR . 'backend/traits/trait-uac-' . $uac_ssp_modulo . '.php';
}
unset( $uac_ssp_modulos, $uac_ssp_modulo );
require_once UAC_SSP_DIR . 'backend/class-uac-suministros-plugin.php';

new UAC_Suministros_Plugin();
