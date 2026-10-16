<?php
/**
 * Aviso de acceso restringido del Registro de donaciones.
 *
 * Plantilla incluida desde el backend: usa las variables locales del método que la incluye.
 */

defined( 'ABSPATH' ) || exit;
?>
<style><?php echo $this->css( 'base', 'ocultar-tema', 'registro' ); ?></style>
<div class="uac-box">
    <div class="uac-denied" role="alert">
        <span class="uac-denied-icon"><?php echo $this->icono( 'lock', 22 ); ?></span>
        <h2>Acceso restringido</h2>
        <p>Debes iniciar sesión con una cuenta de <strong>Registrador</strong> o <strong>Administrador</strong> para registrar suministros en el punto de acopio.</p>
        <?php if ( ! $logueado ) : ?>
            <a class="uac-btn" href="<?php echo esc_url( wp_login_url( $permalink ) ); ?>">Iniciar sesión</a>
        <?php else : ?>
            <p style="margin-bottom:0;">Tu cuenta actual no tiene el rol necesario. Pide acceso al administrador del sistema.</p>
        <?php endif; ?>
    </div>
</div>
