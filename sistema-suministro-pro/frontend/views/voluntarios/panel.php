<?php
/**
 * Gestión de voluntarios: solicitudes, alta manual y voluntarios activos.
 *
 * Plantilla incluida desde el backend: usa las variables locales del método que la incluye.
 */

defined( 'ABSPATH' ) || exit;
?>
<style><?php echo $this->css( 'base', 'ocultar-tema', 'registro', 'voluntarios' ); ?></style>
<div class="uac-box uac-vol">
    <header class="uac-head">
        <div class="uac-brand">
            <span class="uac-brand-mark"><?php echo $this->icono( 'users', 22 ); ?></span>
            <div>
                <h2 class="uac-title">Voluntarios</h2>
                <p class="uac-subtitle">Aprueba solicitudes o agrega voluntarios del punto de acopio</p>
            </div>
        </div>
    </header>

    <?php if ( isset( $avisos[ $msg ] ) ) : ?>
        <div class="uac-vol-aviso is-<?php echo esc_attr( $avisos[ $msg ][0] ); ?>" role="status"><?php echo esc_html( $avisos[ $msg ][1] ); ?></div>
    <?php endif; ?>

    <section class="uac-vol-sec">
        <h3>Solicitudes pendientes <span class="uac-vol-count<?php echo $pendientes ? ' is-hot' : ''; ?>"><?php echo count( $pendientes ); ?></span></h3>
        <?php if ( ! $pendientes ) : ?>
            <p class="uac-vol-vacio">No hay solicitudes por revisar. Cuando alguien pida acceso desde la pantalla de inicio, aparecerá aquí.</p>
        <?php else : ?>
            <ul class="uac-vol-lista">
            <?php foreach ( $pendientes as $u ) : $mot = get_user_meta( $u->ID, 'uac_motivo', true ); $ts = (int) get_user_meta( $u->ID, 'uac_solicitado', true ); ?>
                <li class="uac-vol-item">
                    <div class="uac-vol-dato">
                        <strong><?php echo esc_html( $u->display_name ); ?></strong>
                        <span><?php echo esc_html( $u->user_login ); ?> · <?php echo esc_html( $u->user_email ); ?><?php echo $ts ? ' · ' . esc_html( date_i18n( 'd/m/Y H:i', $ts ) ) : ''; ?></span>
                        <?php if ( $mot ) : ?><em>“<?php echo esc_html( $mot ); ?>”</em><?php endif; ?>
                    </div>
                    <div class="uac-vol-acc">
                        <form method="post" action="<?php echo esc_url( $post_url ); ?>"><?php $campos( 'aprobar', $u->ID ); ?>
                            <button type="submit" class="uac-vol-btn is-ok"><?php echo $this->icono( 'check-circle', 16 ); ?> Aprobar</button></form>
                        <form method="post" action="<?php echo esc_url( $post_url ); ?>" onsubmit="return confirm('¿Rechazar y eliminar esta solicitud?');"><?php $campos( 'rechazar', $u->ID ); ?>
                            <button type="submit" class="uac-vol-btn"><?php echo $this->icono( 'ban', 16 ); ?> Rechazar</button></form>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="uac-vol-sec">
        <h3>Agregar voluntario</h3>
        <p class="uac-vol-ayuda">Crea la cuenta directamente, sin esperar una solicitud. Comparte la contraseña con la persona por un medio seguro.</p>
        <form method="post" action="<?php echo esc_url( $post_url ); ?>" class="uac-vol-form"><?php $campos( 'agregar' ); ?>
            <label>Nombres<input type="text" name="nombres" required minlength="2" maxlength="50" autocomplete="off"></label>
            <label>Apellidos<input type="text" name="apellidos" required minlength="2" maxlength="50" autocomplete="off"></label>
            <label>Correo<input type="email" name="correo" required autocomplete="off"></label>
            <label>Usuario<input type="text" name="usuario" required minlength="4" autocapitalize="off" autocomplete="off"></label>
            <label>Contraseña inicial<input type="text" name="clave" required minlength="8" value="<?php echo esc_attr( wp_generate_password( 10, false ) ); ?>" autocomplete="off"></label>
            <button type="submit" class="uac-vol-btn is-main"><?php echo $this->icono( 'user-plus', 16 ); ?> Agregar voluntario</button>
        </form>
    </section>

    <section class="uac-vol-sec">
        <h3>Voluntarios activos <span class="uac-vol-count"><?php echo count( $activos ); ?></span></h3>
        <?php if ( ! $activos ) : ?>
            <p class="uac-vol-vacio">Todavía no hay voluntarios activos.</p>
        <?php else : ?>
            <ul class="uac-vol-lista">
            <?php foreach ( $activos as $u ) : ?>
                <li class="uac-vol-item">
                    <div class="uac-vol-dato">
                        <strong><?php echo esc_html( $u->display_name ); ?></strong>
                        <span><?php echo esc_html( $u->user_login ); ?> · <?php echo esc_html( $u->user_email ); ?></span>
                    </div>
                    <div class="uac-vol-acc">
                        <form method="post" action="<?php echo esc_url( $post_url ); ?>" onsubmit="return confirm('¿Quitar el acceso a este voluntario?');"><?php $campos( 'quitar', $u->ID ); ?>
                            <button type="submit" class="uac-vol-btn"><?php echo $this->icono( 'trash', 16 ); ?> Quitar acceso</button></form>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
