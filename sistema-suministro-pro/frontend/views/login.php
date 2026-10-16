<?php
/**
 * Pantalla de acceso del sistema (ingresar o solicitar ser voluntario).
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
<meta name="robots" content="noindex">
<title><?php echo esc_html( wp_get_document_title() ); ?></title>
<link rel="preload" as="image" href="<?php echo esc_url( $this->rel( $foto ) ); ?>">
<style><?php echo $this->css( 'login', 'componentes' ); ?></style>
</head>
<body class="uac-login-body">
<div class="uac-login">
    <aside class="uac-login-aside">
        <img src="<?php echo esc_url( $this->rel( $foto ) ); ?>" width="1086" height="1448" alt="Brigadistas combaten un incendio forestal en una ladera andina mientras llamas, caballos y ovejas se alejan del humo." decoding="async" fetchpriority="high">
    </aside>

    <main class="uac-login-main">
        <div class="uac-login-panel">
            <div class="uac-login-brand">
                <span class="uac-login-mark"><?php echo $this->icono( 'package', 20 ); ?></span>
                <span><strong>Sistema-Suministro-Pro</strong><small>Universidad Andina del Cusco</small></span>
            </div>

            <div data-modo="<?php echo esc_attr( $modo ); ?>" id="uac-login-modos">
            <div class="uac-login-tabs" role="tablist">
                <button type="button" role="tab" data-ir="ingresar" aria-selected="<?php echo 'ingresar' === $modo ? 'true' : 'false'; ?>">Ya tengo cuenta</button>
                <button type="button" role="tab" data-ir="solicitar" aria-selected="<?php echo 'solicitar' === $modo ? 'true' : 'false'; ?>">Quiero ser voluntario</button>
            </div>

            <?php if ( isset( $avisos[ $aviso ] ) ) : $a = $avisos[ $aviso ]; ?>
                <div class="uac-login-alert <?php echo esc_attr( $a[0] ); ?>" role="<?php echo 'is-error' === $a[0] ? 'alert' : 'status'; ?>">
                    <?php echo $this->icono( $a[1], 18 ); ?><span><?php echo esc_html( $a[2] ); ?></span>
                </div>
            <?php endif; ?>

            <div class="uac-login-pane" data-pane="ingresar">
            <h1>Ingresa al sistema</h1>
            <p class="uac-login-lead">Con tu cuenta de voluntario registras donaciones en el punto de acopio.</p>

            <form class="uac-login-form" method="post" action="<?php echo esc_url( $this->rel( wp_login_url() ) ); ?>">
                <div class="uac-login-field">
                    <label for="uac-log">Usuario o correo</label>
                    <input type="text" id="uac-log" name="log" autocomplete="username" autocapitalize="off" autocorrect="off" spellcheck="false" required <?php echo 'salida' === $aviso || '' === $aviso ? 'autofocus' : ''; ?>>
                </div>
                <div class="uac-login-field">
                    <label for="uac-pwd">Contraseña</label>
                    <div class="uac-login-pwd">
                        <input type="password" id="uac-pwd" name="pwd" autocomplete="current-password" required <?php echo 'error' === $aviso || 'vacio' === $aviso ? 'autofocus' : ''; ?>>
                        <button type="button" class="uac-login-eye" id="uac-login-eye" aria-label="Mostrar contraseña" aria-pressed="false" hidden><?php echo $this->icono( 'eye', 18 ); ?></button>
                    </div>
                </div>
                <label class="uac-login-check">
                    <input type="checkbox" name="rememberme" value="forever">
                    <span>Mantener la sesión en este equipo</span>
                </label>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $destino ); ?>">
                <input type="hidden" name="uac_volver" value="<?php echo esc_attr( $volver ); ?>">
                <input type="hidden" name="uac_login" value="1">
                <button type="submit" class="uac-login-submit">Ingresar</button>
            </form>

            <p class="uac-login-help">
                <a href="<?php echo esc_url( $this->rel( wp_lostpassword_url() ) ); ?>">¿Olvidaste tu contraseña?</a>
            </p>
            </div>

            <div class="uac-login-pane" data-pane="solicitar">
            <h1>Pide tu acceso</h1>
            <p class="uac-login-lead">Completa tus datos. El administrador revisará tu solicitud y, cuando la apruebe, podrás ingresar.</p>

            <form class="uac-login-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="uac_solicitar">
                <?php wp_nonce_field( 'uac_solicitar', '_uac_n' ); ?>
                <input type="hidden" name="uac_volver" value="<?php echo esc_attr( $volver ); ?>">
                <div class="uac-login-hp" aria-hidden="true"><label>No llenar<input type="text" name="uac_web" tabindex="-1" autocomplete="off"></label></div>
                <div class="uac-login-fila">
                    <div class="uac-login-field">
                        <label for="uac-s-nombres">Nombres</label>
                        <input type="text" id="uac-s-nombres" name="nombres" autocomplete="given-name" autocapitalize="words" spellcheck="false" required minlength="2" maxlength="50" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ][A-Za-zÁÉÍÓÚÜÑáéíóúüñ' .\-]*" data-nombre aria-describedby="uac-s-nombres-err">
                        <small class="uac-login-err" id="uac-s-nombres-err" role="alert" hidden></small>
                    </div>
                    <div class="uac-login-field">
                        <label for="uac-s-apellidos">Apellidos</label>
                        <input type="text" id="uac-s-apellidos" name="apellidos" autocomplete="family-name" autocapitalize="words" spellcheck="false" required minlength="2" maxlength="50" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ][A-Za-zÁÉÍÓÚÜÑáéíóúüñ' .\-]*" data-nombre aria-describedby="uac-s-apellidos-err">
                        <small class="uac-login-err" id="uac-s-apellidos-err" role="alert" hidden></small>
                    </div>
                </div>
                <div class="uac-login-field">
                    <label for="uac-s-correo">Correo</label>
                    <input type="email" id="uac-s-correo" name="correo" autocomplete="email" required>
                </div>
                <div class="uac-login-field">
                    <label for="uac-s-usuario">Usuario</label>
                    <input type="text" id="uac-s-usuario" name="usuario" autocomplete="username" autocapitalize="off" autocorrect="off" spellcheck="false" required minlength="4" pattern="[A-Za-z0-9_.@\-]{4,}" title="Mínimo 4 caracteres: letras, números, punto, guion o guion bajo. Sin espacios.">
                    <small>Mínimo 4 caracteres: letras, números, punto o guion. Sin espacios.</small>
                </div>
                <div class="uac-login-field">
                    <label for="uac-s-clave">Contraseña</label>
                    <input type="password" id="uac-s-clave" name="clave" autocomplete="new-password" required minlength="8">
                    <div class="uac-fuerza" aria-live="polite"><i></i><span id="uac-fuerza-txt">Mínimo 8 caracteres.</span></div>
                </div>
                <div class="uac-login-field">
                    <label for="uac-s-motivo">¿Dónde o cómo ayudarás? (opcional)</label>
                    <textarea id="uac-s-motivo" name="motivo" maxlength="300" rows="2"></textarea>
                </div>
                <button type="submit" class="uac-login-submit">Enviar solicitud</button>
            </form>
            </div>
            </div>

            <a class="uac-login-verlink" href="<?php echo esc_url( $this->rel( $this->url_vista( 'dashboard' ) ) ); ?>"><?php echo $this->icono( 'chart', 16 ); ?> Ver el avance de las donaciones sin ingresar</a>
        </div>

        <p class="uac-login-foot">Universidad Andina del Cusco · Sistema de Gestión de Suministros · Datos protegidos según la Ley N.° 29733</p>
    </main>
</div>
<?php wp_print_scripts( 'uac-login' ); ?>
</body>
</html>
