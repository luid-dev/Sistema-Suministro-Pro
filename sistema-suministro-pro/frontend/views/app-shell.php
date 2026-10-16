<?php
/**
 * Plantilla de la aplicación: barra superior, navegación, contenido y pie.
 *
 * Plantilla incluida desde el backend: usa las variables locales del método que la incluye.
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0F172A">
<title><?php echo esc_html( wp_get_document_title() ); ?></title>
<?php wp_head(); ?>
<style><?php echo $this->css( 'app-shell', 'componentes' ); ?></style>
</head>
<body class="uac-app-body uac-vista-<?php echo esc_attr( $vista ); ?>">
<?php wp_body_open(); ?>
<a class="uac-skip" href="#uac-main">Saltar al contenido</a>
<div class="uac-shell">
    <header class="uac-appbar">
        <div class="uac-appbar-inner">
            <a class="uac-appbar-brand" href="<?php echo esc_url( $url_inicio ); ?>">
                <span class="uac-appbar-mark"><?php echo $this->icono( 'package', 18 ); ?></span>
                <span class="uac-appbar-name">
                    <strong>Sistema-Suministro-Pro</strong>
                    <small>Universidad Andina del Cusco</small>
                </span>
            </a>

            <nav class="uac-appnav" aria-label="Secciones del sistema">
                <?php foreach ( $tabs as $clave => $tab ) : ?>
                    <a href="<?php echo esc_url( $tab[0] ); ?>"<?php echo $clave === $vista ? ' class="is-active" aria-current="page"' : ''; ?>>
                        <?php echo $this->icono( $tab[1], 16 ); ?><span class="uac-nl"><?php echo esc_html( $tab[2] ); ?></span><span class="uac-ns" aria-hidden="true"><?php echo esc_html( isset( $cortos[ $clave ] ) ? $cortos[ $clave ] : $tab[2] ); ?></span><?php echo ! empty( $tab[3] ) ? '<b class="uac-nav-badge" title="Solicitudes pendientes">' . (int) $tab[3] . '</b>' : ''; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="uac-appbar-user">
                <?php if ( $logueado ) : ?>
                    <span class="uac-appbar-who" title="Sesión iniciada: <?php echo esc_attr( $usuario->user_login ); ?>"><?php echo $this->icono( 'user', 14 ); ?><span><?php echo esc_html( $usuario->user_login ); ?></span></span>
                    <?php if ( $es_admin ) : ?>
                        <a class="uac-appbar-link" href="<?php echo esc_url( $this->rel( admin_url( 'options-general.php?page=sistema-suministro-pro-config' ) ) ); ?>" aria-label="Ajustes del sistema" title="Ajustes"><?php echo $this->icono( 'settings', 14 ); ?><span>Ajustes</span></a>
                    <?php endif; ?>
                    <a class="uac-appbar-link" href="<?php echo esc_url( $this->rel( wp_logout_url( add_query_arg( 'uac_acceso', 'salida', $url_inicio ) ) ) ); ?>" aria-label="Cerrar sesión" title="Cerrar sesión"><?php echo $this->icono( 'logout', 14 ); ?><span>Salir</span></a>
                <?php else : ?>
                    <a class="uac-appbar-sec" href="<?php echo esc_url( $url_reg . '#solicitar' ); ?>"><?php echo $this->icono( 'user-plus', 14 ); ?><span>Quiero ser voluntario</span></a>
                    <a class="uac-appbar-login" href="<?php echo esc_url( $url_reg ); ?>"><?php echo $this->icono( 'login', 14 ); ?><span>Ingresar</span></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if ( ! $logueado && 'dashboard' === $vista ) : ?>
    <section class="uac-cta" aria-label="Participa como voluntario">
        <div class="uac-cta-in">
            <div><strong>¿Quieres ayudar en el punto de acopio?</strong><span>Cualquiera puede ver el avance. Para registrar donaciones, ingresa o pide acceso como voluntario.</span></div>
            <div class="uac-cta-btns">
                <a class="is-primary" href="<?php echo esc_url( $url_reg ); ?>"><?php echo $this->icono( 'login', 16 ); ?> Ingresar</a>
                <a class="is-ghost" href="<?php echo esc_url( $url_reg . '#solicitar' ); ?>"><?php echo $this->icono( 'user-plus', 16 ); ?> Solicitar acceso</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <main class="uac-app-main" id="uac-main" tabindex="-1">
        <?php echo $contenido; // Salida de los shortcodes del propio plugin (escapada en su origen). ?>
    </main>

    <footer class="uac-appfoot">
        <div class="uac-appfoot-inner">
            <span>Sistema de Gestión de Suministros · Universidad Andina del Cusco</span>
            <span>Campaña de emergencia por incendios forestales · Cusco</span>
        </div>
    </footer>
</div>
<style><?php echo $this->css( 'responsive' ); ?></style>
<?php wp_footer(); ?>
</body>
</html>
