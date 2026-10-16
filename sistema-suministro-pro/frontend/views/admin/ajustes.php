<?php
/**
 * Página de ajustes en el escritorio de WordPress (Ajustes > Sistema-Suministro-Pro).
 *
 * Plantilla incluida desde el backend: usa las variables locales del método que la incluye.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap uac-admin">
    <h1>Configuración – Sistema de Gestión de Sistema-Suministro-Pro</h1>
    <p class="uac-admin-lead">Sistema de gestión de suministros con base de datos propia, integrada en WordPress.</p>

    <?php
    global $wpdb;
    $avisos = array(
        'carreras'    => 'Carreras importadas: %1$d. Filas omitidas: %2$d.',
        'estudiantes' => 'Estudiantes importados: %1$d. Filas omitidas (código inválido o carrera inexistente): %2$d.',
    );
    if ( isset( $_GET['uac_imp'] ) && preg_match( '/^(carreras|estudiantes):(\d+):(\d+)$/', wp_unslash( $_GET['uac_imp'] ), $m ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( $avisos[ $m[1] ], (int) $m[2], (int) $m[3] ) ) . '</p></div>';
    } elseif ( isset( $_GET['uac_imp'] ) && 'error' === $_GET['uac_imp'] ) {
        echo '<div class="notice notice-error is-dismissible"><p>No se pudo leer el archivo CSV.</p></div>';
    }
    $conteos = array(
        'Carreras'    => 'carreras',
        'Estudiantes' => 'estudiantes',
        'Cortes'      => 'cortes',
        'Donaciones'  => 'donaciones',
        'Líneas aceptadas' => 'detalle',
        'Descartes'   => 'descartes',
    );
    ?>
    <form method="post" action="options.php" class="uac-admin-card">
        <h2>
            Base de datos
            <span class="uac-admin-status is-ok"><?php echo $this->icono( 'check', 12 ); ?> Integrada en WordPress</span>
        </h2>
        <p class="uac-admin-help">Los datos se guardan en tablas <code><?php echo esc_html( $wpdb->prefix ); ?>uac_*</code> de la base de datos de este WordPress. No se necesita ningún servicio externo.</p>
        <?php settings_fields( 'uac_suministros_group' ); ?>
        <table class="uac-admin-table" style="margin-bottom:16px">
            <thead><tr><?php foreach ( array_keys( $conteos ) as $titulo ) : ?><th><?php echo esc_html( $titulo ); ?></th><?php endforeach; ?></tr></thead>
            <tbody><tr><?php foreach ( $conteos as $tabla ) : ?><td><?php echo (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->t($tabla)}" ); ?></td><?php endforeach; ?></tr></tbody>
        </table>
        <table class="form-table">
            <tr>
                <th scope="row">Modo demostración</th>
                <td>
                    <label><input type="checkbox" name="<?php echo esc_attr( self::OPT_DEMO ); ?>" value="1" <?php checked( 1, (int) get_option( self::OPT_DEMO ) ); ?> /> Mostrar datos de ejemplo en el tablero y los reportes</label>
                    <p class="description">Las cifras se generan en WordPress y <strong>no se guardan</strong> en la base de datos. Desactívalo antes de la campaña real.</p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Guardar Configuración' ); ?>
    </form>

    <div class="uac-admin-card">
        <h2>Importar datos (CSV)</h2>
        <p class="uac-admin-help">Útil para cargar el padrón de estudiantes o el catálogo de carreras (por ejemplo, exportados desde una hoja de cálculo). Se aceptan separadores coma o punto y coma; los registros existentes se actualizan.</p>
        <?php foreach ( array(
            'carreras'    => array( 'Carreras', 'carrera_id, nombre, facultad' ),
            'estudiantes' => array( 'Estudiantes (padrón)', 'codigo, carrera_id' ),
        ) as $tipo => $def ) : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="margin-bottom:14px">
                <input type="hidden" name="action" value="uac_importar" />
                <input type="hidden" name="tipo" value="<?php echo esc_attr( $tipo ); ?>" />
                <?php wp_nonce_field( 'uac_importar' ); ?>
                <strong><?php echo esc_html( $def[0] ); ?></strong> — columnas: <code><?php echo esc_html( $def[1] ); ?></code><br />
                <input type="file" name="archivo" accept=".csv,text/csv" required />
                <?php submit_button( 'Importar', 'secondary', 'submit', false ); ?>
            </form>
        <?php endforeach; ?>
    </div>

    <div class="uac-admin-card">
        <h2>Shortcodes disponibles</h2>
        <p class="uac-admin-help">Pégalos en una página de WordPress para publicar cada vista.</p>
        <table class="uac-admin-table">
            <thead>
                <tr><th>Shortcode</th><th>Descripción</th><th>Acceso</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[uac_registro]</code></td>
                    <td>Formulario de punto de acopio con lector QR.</td>
                    <td>Administrador o Registrador (requiere iniciar sesión).</td>
                </tr>
                <tr>
                    <td><code>[uac_dashboard]</code></td>
                    <td>Tablero público en vivo con refresco automático cada 15 segundos.</td>
                    <td>Público (no requiere iniciar sesión).</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="uac-admin-card">
        <h2>Pantallas del sistema</h2>
        <p class="uac-admin-help">El sitio público funciona como aplicación: la raíz abre el sistema y las vistas de blog (entradas, archivos, búsquedas) vuelven al inicio.</p>
        <table class="uac-admin-table">
            <thead>
                <tr><th>Dirección</th><th>Qué muestra</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( home_url( '/' ) ); ?></code></a></td>
                    <td>Registro de donaciones para Administradores y Registradores con sesión iniciada; Tablero en vivo para el público.</td>
                </tr>
                <tr>
                    <td><a href="<?php echo esc_url( $this->url_vista( 'dashboard' ) ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( $this->url_vista( 'dashboard' ) ); ?></code></a></td>
                    <td>Tablero en vivo (siempre).</td>
                </tr>
                <tr>
                    <td><a href="<?php echo esc_url( $this->url_vista( 'registro' ) ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( $this->url_vista( 'registro' ) ); ?></code></a></td>
                    <td>Registro de donaciones (pide iniciar sesión).</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
