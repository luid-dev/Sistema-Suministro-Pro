<?php
/**
 * Aviso de acceso restringido de la gestión de voluntarios.
 *
 * Plantilla incluida desde el backend: usa las variables locales del método que la incluye.
 */

defined( 'ABSPATH' ) || exit;
?>
<style><?php echo $this->css( 'base', 'ocultar-tema', 'registro', 'voluntarios' ); ?></style>
<div class="uac-box"><div class="uac-denied" role="alert">
    <span class="uac-denied-icon"><?php echo $this->icono( 'lock', 22 ); ?></span>
    <h2>Solo para administradores</h2>
    <p>Los voluntarios los aprueba y gestiona el administrador del sistema.</p>
</div></div>
