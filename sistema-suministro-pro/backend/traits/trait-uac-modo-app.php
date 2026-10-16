<?php
/**
 * Modo aplicación: páginas del sistema, enrutamiento de vistas y plantilla propia.
 *
 * @package Sistema_Suministro_Pro
 */

defined( 'ABSPATH' ) || exit;

trait UAC_SSP_Modo_App {

    /**
     * Definición de las páginas del sistema: opción => [shortcode, título, slug].
     */
    private function paginas_sistema() {
        return array(
            self::OPT_PAGE_DASH => array( 'uac_dashboard', 'Tablero de donaciones', 'tablero-de-donaciones-uac' ),
            self::OPT_PAGE_REG  => array( 'uac_registro', 'Registro de acopio', 'registro-de-acopio-uac' ),
            self::OPT_PAGE_CORTES => array( 'uac_cortes', 'Cortes y metas', 'cortes-y-metas-uac' ),
            self::OPT_PAGE_VOL    => array( 'uac_voluntarios', 'Voluntarios', 'voluntarios-uac' ),
        );
    }

    /**
     * En el front-end solo actúa si falta alguna página (una consulta de opciones ya cargadas).
     */
    public function asegurar_paginas() {
        foreach ( $this->paginas_sistema() as $opcion => $def ) {
            if ( ! get_option( $opcion ) ) {
                $this->vincular_pagina( $opcion, $def );
            }
        }
    }

    /**
     * En el escritorio comprueba además que las páginas guardadas sigan publicadas.
     */
    public function verificar_paginas() {
        foreach ( $this->paginas_sistema() as $opcion => $def ) {
            $id = (int) get_option( $opcion );
            if ( ! $id || 'publish' !== get_post_status( $id ) ) {
                $this->vincular_pagina( $opcion, $def );
            }
        }
    }

    /**
     * Reutiliza la página publicada que ya contiene el shortcode o crea una limpia.
     */
    private function vincular_pagina( $opcion, $def ) {
        list( $shortcode, $titulo, $slug ) = $def;
        $id = 0;

        $paginas = get_posts( array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ) );
        foreach ( $paginas as $pagina ) {
            if ( has_shortcode( $pagina->post_content, $shortcode ) ) {
                $id = (int) $pagina->ID;
                break;
            }
        }

        if ( ! $id ) {
            $nuevo = wp_insert_post( array(
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'post_title'     => $titulo,
                'post_name'      => $slug,
                'post_content'   => "<!-- wp:shortcode -->\n[" . $shortcode . "]\n<!-- /wp:shortcode -->",
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ) );
            $id = is_wp_error( $nuevo ) ? 0 : (int) $nuevo;
        }

        if ( $id ) {
            update_option( $opcion, $id );
        }
    }

    /**
     * URL pública de una vista del sistema (con respaldo a la raíz).
     */
    private function url_vista( $vista ) {
        $opciones = array( 'registro' => self::OPT_PAGE_REG, 'cortes' => self::OPT_PAGE_CORTES, 'voluntarios' => self::OPT_PAGE_VOL );
        $id       = (int) get_option( isset( $opciones[ $vista ] ) ? $opciones[ $vista ] : self::OPT_PAGE_DASH );
        $url = $id ? get_permalink( $id ) : '';
        return $url ? $url : home_url( '/' );
    }

    /**
     * Enlaces relativos (/ruta): respetan el protocolo con el que se abrió el sitio
     * (LocalWP puede servir http aunque WordPress esté configurado en https).
     */
    private function rel( $url ) {
        $relativa = wp_make_link_relative( $url );
        return '' === $relativa ? '/' : $relativa;
    }

    /**
     * Ruta de la petición actual, sin los parámetros de aviso del acceso.
     */
    private function ruta_actual() {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        $uri = remove_query_arg( array( 'uac_acceso', 'redirect_to', 'reauth' ), $uri );
        return wp_validate_redirect( $uri, '/' );
    }

    /**
     * Decide qué ve cada petición pública:
     *  - Raíz del sitio: Registro si la cuenta puede registrar; si no, Tablero en vivo.
     *  - Páginas del sistema: su vista, dentro de la plantilla de la aplicación.
     *  - Portada de blog, entradas, archivos, búsquedas, 404 y otras páginas: vuelven a la raíz.
     * Se desactiva con: add_filter( 'uac_suministros_modo_app', '__return_false' );
     */
    public function resolver_vista_app() {
        if ( is_admin() || wp_doing_ajax() || ! apply_filters( 'uac_suministros_modo_app', true ) ) {
            return;
        }
        if ( is_feed() || is_robots() || is_favicon() || is_embed() || is_trackback() || is_preview() || is_customize_preview() || get_query_var( 'sitemap' ) ) {
            return;
        }

        $id_dash = (int) get_option( self::OPT_PAGE_DASH );
        $id_reg  = (int) get_option( self::OPT_PAGE_REG );
        $id_cort = (int) get_option( self::OPT_PAGE_CORTES );
        $id_vol  = (int) get_option( self::OPT_PAGE_VOL );
        $post    = get_queried_object();

        if ( is_front_page() || is_home() ) {
            $vista = $this->tiene_permiso_registro() ? 'registro' : 'dashboard';
            $this->vista_app = apply_filters( 'uac_suministros_vista_inicio', $vista ) === 'registro' ? 'registro' : 'dashboard';
        } elseif ( $id_dash && is_page( $id_dash ) ) {
            $this->vista_app = 'dashboard';
        } elseif ( $id_reg && is_page( $id_reg ) ) {
            $this->vista_app = 'registro';
        } elseif ( $id_cort && is_page( $id_cort ) ) {
            $this->vista_app = 'cortes';
        } elseif ( $id_vol && is_page( $id_vol ) ) {
            $this->vista_app = 'voluntarios';
        } elseif ( is_page() && $post instanceof WP_Post && has_shortcode( $post->post_content, 'uac_registro' ) ) {
            $this->vista_app = 'registro';
        } elseif ( is_page() && $post instanceof WP_Post && has_shortcode( $post->post_content, 'uac_dashboard' ) ) {
            $this->vista_app = 'dashboard';
        } elseif ( is_privacy_policy() ) {
            return;
        } elseif ( is_singular() || is_archive() || is_search() || is_404() ) {
            $this->redirigir_a_inicio = true;
        }

        // El tablero (trazabilidad de donaciones) es público; registro y cortes exigen sesión.
        if ( $this->vista_app && 'dashboard' !== $this->vista_app && ! is_user_logged_in() && apply_filters( 'uac_suministros_requiere_login', true ) ) {
            $this->mostrar_login = true;
        }
    }

    /**
     * La barra de administración de WordPress no aparece dentro de la aplicación.
     */
    public function filtrar_admin_bar( $mostrar ) {
        return $this->vista_app ? false : $mostrar;
    }

    public function titulo_documento( $titulo ) {
        if ( $this->mostrar_login ) {
            return 'Ingresar · Sistema-Suministro-Pro';
        }
        if ( 'registro' === $this->vista_app ) {
            return 'Registro de donaciones · Sistema-Suministro-Pro';
        }
        if ( 'dashboard' === $this->vista_app ) {
            return 'Tablero en vivo · Sistema-Suministro-Pro';
        }
        if ( 'cortes' === $this->vista_app ) {
            return 'Cortes y metas · Sistema-Suministro-Pro';
        }
        if ( 'voluntarios' === $this->vista_app ) {
            return 'Voluntarios · Sistema-Suministro-Pro';
        }
        return $titulo;
    }

    /**
     * Quita los estilos del tema y del editor de bloques en las vistas de la aplicación.
     * Los scripts se conservan (html5-qrcode se imprime en el pie).
     */
    public function limpiar_estilos_tema() {
        foreach ( (array) wp_styles()->queue as $handle ) {
            wp_dequeue_style( $handle );
        }
    }

    /**
     * Renderiza la plataforma con su propia plantilla (sin cabecera, pie ni menús del tema).
     */
    public function renderizar_app() {
        // WordPress fuerza https en el acceso cuando la URL del sitio es https; si el sistema
        // se abriera por http, la sesión (cookie segura) no sería visible. Se unifica en https.
        if ( ( $this->vista_app || $this->redirigir_a_inicio ) && ! is_ssl()
            && 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME )
            && apply_filters( 'uac_suministros_forzar_https', true ) ) {
            $host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
            $uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
            wp_safe_redirect( 'https://' . $host . $uri, 302 );
            exit;
        }

        if ( $this->redirigir_a_inicio ) {
            wp_safe_redirect( $this->rel( home_url( '/' ) ), 302 );
            exit;
        }
        if ( ! $this->vista_app ) {
            return;
        }
        if ( $this->mostrar_login ) {
            status_header( 200 );
            nocache_headers();
            $this->cabeceras_seguridad();
            $this->imprimir_login();
            exit;
        }

        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'wp_head', 'wp_print_font_faces', 50 );
        remove_action( 'wp_head', 'wp_print_font_faces_from_style_variations', 50 );
        remove_action( 'wp_head', 'feed_links', 2 );
        remove_action( 'wp_head', 'feed_links_extra', 3 );
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', '_wp_render_title_tag', 1 );
        add_action( 'wp_enqueue_scripts', array( $this, 'limpiar_estilos_tema' ), 999 );
        add_action( 'wp_footer', array( $this, 'limpiar_estilos_tema' ), 15 );

        // El shortcode se ejecuta antes de wp_head para que sus scripts queden en cola.
        $vista     = $this->vista_app;
        if ( 'registro' === $vista ) {
            $contenido = $this->shortcode_registro();
        } elseif ( 'cortes' === $vista ) {
            $contenido = $this->shortcode_cortes();
        } elseif ( 'voluntarios' === $vista ) {
            $contenido = $this->shortcode_voluntarios();
        } else {
            $contenido = $this->shortcode_dashboard();
        }

        status_header( 200 );
        nocache_headers();
        $this->cabeceras_seguridad();
        $this->imprimir_shell( $vista, $contenido );
        exit;
    }

    /**
     * Plantilla de la aplicación: barra superior, navegación entre vistas y pie institucional.
     */
    private function imprimir_shell( $vista, $contenido ) {
        $logueado    = is_user_logged_in();
        $usuario     = wp_get_current_user();
        $es_admin    = current_user_can( 'manage_options' );
        $puede       = $this->tiene_permiso_registro();
        $url_dash    = $this->rel( $this->url_vista( 'dashboard' ) );
        $url_reg     = $this->rel( $this->url_vista( 'registro' ) );
        $url_inicio  = $this->rel( home_url( '/' ) );
        $cortos      = array( 'registro' => 'Registro', 'dashboard' => 'Tablero', 'cortes' => 'Cortes', 'voluntarios' => 'Voluntarios' );
        $tabs        = array(
            'registro'  => array( $url_reg, $puede ? 'package' : 'lock', 'Registro de donaciones' ),
            'dashboard' => array( $url_dash, 'chart', 'Tablero en vivo' ),
        );
        if ( $es_admin ) {
            $tabs['cortes'] = array( $this->rel( $this->url_vista( 'cortes' ) ), 'calendar', 'Cortes y metas' );
            $tabs['voluntarios'] = array( $this->rel( $this->url_vista( 'voluntarios' ) ), 'users', 'Voluntarios', $this->total_pendientes() );
        }
        include $this->ruta_vista( 'app-shell' );
    }
}
