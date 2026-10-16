<?php
/**
 * Clase principal: reúne los módulos (traits) y registra los hooks de WordPress.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

class UAC_Suministros_Plugin {

    use UAC_SSP_Base_Datos;
    use UAC_SSP_Api_Donaciones;
    use UAC_SSP_Resumen;
    use UAC_SSP_Cortes;
    use UAC_SSP_Admin;
    use UAC_SSP_Modo_App;
    use UAC_SSP_Acceso;
    use UAC_SSP_Voluntarios;
    use UAC_SSP_Seguridad;
    use UAC_SSP_Demo;
    use UAC_SSP_Interfaz;
    use UAC_SSP_Shortcodes;

    // Páginas del sistema (se detectan o se crean automáticamente)
    const OPT_PAGE_DASH   = 'uac_suministros_page_dashboard';
    const OPT_PAGE_REG    = 'uac_suministros_page_registro';
    const OPT_PAGE_CORTES = 'uac_suministros_page_cortes';
    const OPT_PAGE_VOL    = 'uac_suministros_page_voluntarios';

    // Versión del esquema de la base de datos
    const DB_VERSION     = '2.0.0';
    const OPT_DB_VERSION = 'uac_suministros_db_version';

    // Rol de quien pidió acceso y espera aprobación
    const ROL_PENDIENTE = 'uac_pendiente';

    // Opción del modo demostración
    const OPT_DEMO = 'uac_suministros_modo_demo';

    /** Vista de la aplicación a renderizar en esta petición: '', 'dashboard' o 'registro'. */
    private $vista_app = '';

    /** true cuando la petición es una vista de blog que debe volver al inicio del sistema. */
    private $redirigir_a_inicio = false;

    /** true cuando una vista del sistema se pide sin sesión: se muestra la pantalla de acceso. */
    private $mostrar_login = false;

    public function __construct() {
        register_activation_hook( UAC_SSP_FILE, array( $this, 'activar_plugin' ) );
        add_action( 'admin_menu', array( $this, 'registrar_menu_admin' ) );
        add_action( 'admin_init', array( $this, 'registrar_ajustes' ) );
        add_action( 'rest_api_init', array( $this, 'registrar_rutas_rest' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'estilos_admin' ) );

        // Archivos del frontend (frontend/js): se registran aquí y cada vista encola los suyos.
        add_action( 'init', array( $this, 'registrar_assets' ) );

        add_shortcode( 'uac_registro', array( $this, 'shortcode_registro' ) );
        add_shortcode( 'uac_dashboard', array( $this, 'shortcode_dashboard' ) );
        add_shortcode( 'uac_cortes', array( $this, 'shortcode_cortes' ) );
        add_shortcode( 'uac_voluntarios', array( $this, 'shortcode_voluntarios' ) );

        // Solicitud de acceso (pública) y gestión de voluntarios (administrador)
        add_action( 'init', array( $this, 'crear_rol_pendiente' ) );
        add_action( 'admin_post_nopriv_uac_solicitar', array( $this, 'procesar_solicitud' ) );
        add_action( 'admin_post_uac_solicitar', array( $this, 'procesar_solicitud' ) );
        add_action( 'admin_post_uac_voluntario', array( $this, 'procesar_gestion' ) );
        add_filter( 'authenticate', array( $this, 'bloquear_pendientes' ), 30, 3 );
        add_filter( 'authenticate', array( $this, 'limitar_intentos' ), 99, 3 );
        add_action( 'wp_login', array( $this, 'reiniciar_intentos' ), 10, 0 );

        // Cortes y metas editables desde el sistema (rutas adicionales, solo administrador)
        add_action( 'rest_api_init', array( $this, 'registrar_rutas_cortes' ) );

        // Base de datos propia (se crea/actualiza sola) e importación CSV
        add_action( 'init', array( $this, 'verificar_base_datos' ), 1 );
        add_action( 'admin_post_uac_importar', array( $this, 'procesar_importacion' ) );

        // Modo aplicación: el sitio público se comporta como la plataforma, no como un blog.
        add_action( 'init', array( $this, 'asegurar_paginas' ) );
        add_action( 'admin_init', array( $this, 'verificar_paginas' ) );
        add_action( 'wp', array( $this, 'resolver_vista_app' ) );
        add_filter( 'show_admin_bar', array( $this, 'filtrar_admin_bar' ) );
        add_action( 'template_redirect', array( $this, 'renderizar_app' ), 5 );
        add_filter( 'pre_get_document_title', array( $this, 'titulo_documento' ) );

        // Acceso de voluntarios con la identidad del sistema
        add_action( 'login_enqueue_scripts', array( $this, 'estilos_login' ) );
        add_filter( 'login_headerurl', array( $this, 'login_url_cabecera' ) );
        add_filter( 'login_headertext', array( $this, 'login_texto_cabecera' ) );
        add_filter( 'login_redirect', array( $this, 'redireccion_tras_login' ), 10, 3 );
        add_action( 'login_init', array( $this, 'login_hacia_app' ) );
        add_filter( 'authenticate', array( $this, 'login_campos_vacios' ), 101, 3 );
        add_action( 'wp_login_failed', array( $this, 'login_fallido' ), 10, 2 );
        add_filter( 'gettext', array( $this, 'traducir_login' ), 10, 3 );
    }

    /**
     * Crea el rol "Registrador" al activar el plugin
     */
    public function activar_plugin() {
        add_role(
            'uac_registrador',
            'Registrador (Punto de Acopio)',
            array(
                'read'         => true,
                'uac_registrar' => true,
            )
        );

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'uac_registrar' );
        }

        $this->crear_tablas();
    }

    /**
     * Verifica si el usuario actual es Registrador o Administrador
     */
    public function tiene_permiso_registro() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        $user = wp_get_current_user();
        return current_user_can( 'manage_options' ) || current_user_can( 'uac_registrar' ) || in_array( 'uac_registrador', (array) $user->roles, true );
    }
}
