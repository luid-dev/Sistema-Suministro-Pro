<?php
/**
 * Formulario del punto de acopio con lector QR.
 *
 * Plantilla incluida desde el backend: usa las variables locales del método que la incluye.
 */

defined( 'ABSPATH' ) || exit;
?>
<style><?php echo $this->css( 'base', 'ocultar-tema', 'registro' ); ?></style>

<div class="uac-box" id="uac-app-registro">
    <header class="uac-head">
        <div class="uac-brand">
            <span class="uac-brand-mark"><?php echo $this->icono( 'package', 22 ); ?></span>
            <div>
                <h2 class="uac-title">Registro de donaciones</h2>
                <p class="uac-subtitle">Punto de acopio de la Universidad Andina del Cusco</p>
            </div>
        </div>
        <span class="uac-chip" title="Usuario que registra"><?php echo $this->icono( 'user', 14 ); ?> <?php echo esc_html( $usuario->user_login ); ?></span>
    </header>

    <div id="uac-corte-actual" class="uac-corte-actual" aria-live="polite"></div>

    <!-- 1. Identificación del Estudiante (QR o Manual) -->
    <section class="uac-section" aria-labelledby="uac-sec-1">
        <div class="uac-section-head">
            <span class="uac-step" aria-hidden="true">1</span>
            <div>
                <h3 class="uac-section-title" id="uac-sec-1">Identificación del estudiante</h3>
                <p class="uac-section-help">Escanea el QR del carné o digita el código manualmente.</p>
            </div>
        </div>

        <label class="uac-label" for="uac-codigo">Código de estudiante</label>
        <div class="uac-toolbar">
            <button type="button" class="uac-btn uac-btn-sec" id="uac-btn-qr"><?php echo $this->icono( 'camera', 16 ); ?><span>Escanear QR</span></button>
            <input type="text" id="uac-codigo" class="uac-input uac-code" maxlength="10" placeholder="Ej. 012345678A" autocomplete="off" autocapitalize="characters" spellcheck="false" />
            <button type="button" class="uac-btn" id="uac-btn-buscar"><?php echo $this->icono( 'search', 16 ); ?><span>Verificar código</span></button>
        </div>
        <p class="uac-hint">Formato: 9 dígitos seguidos de 1 letra.</p>

        <div id="uac-qr-reader" class="uac-qr" style="display:none;"></div>

        <div id="uac-info-estudiante" class="uac-info-box" style="display:none;">
            <div id="uac-est-status"></div>
            <div id="uac-wrap-carrera-nueva" class="uac-field" style="display:none;">
                <label for="uac-select-carrera" class="uac-label">Selecciona la carrera profesional (solo la primera vez)</label>
                <select id="uac-select-carrera" class="uac-input">
                    <option value="">-- Seleccione una carrera --</option>
                </select>
            </div>
        </div>
    </section>

    <!-- 2. Líneas de Suministros -->
    <section class="uac-section" aria-labelledby="uac-sec-2">
        <div class="uac-section-head">
            <span class="uac-step" aria-hidden="true">2</span>
            <div>
                <h3 class="uac-section-title" id="uac-sec-2">Inspección y registro de suministros</h3>
                <p class="uac-section-help">Cada línea se valida por separado. Se aceptan productos con vigencia de al menos 3 meses y empaque íntegro.</p>
            </div>
        </div>

        <div id="uac-lista-items"></div>

        <div class="uac-add-row">
            <button type="button" class="uac-btn uac-btn-sec" id="uac-btn-add-item"><?php echo $this->icono( 'plus', 16 ); ?><span>Agregar otro suministro</span></button>
        </div>
    </section>

    <div class="uac-section uac-submit">
        <p class="uac-submit-note"><?php echo $this->icono( 'clock', 16 ); ?><span>La fecha y hora de ingreso las asigna el servidor (hora de Lima). Los productos rechazados pasan al log de descartes sin detener el resto de la donación.</span></p>
        <button type="button" class="uac-btn uac-btn-lg" id="uac-btn-guardar"><?php echo $this->icono( 'save', 18 ); ?><span>Registrar donación en el sistema</span></button>
    </div>

    <div id="uac-resultado-envio" class="uac-result" aria-live="polite"></div>

    <div class="uac-toast-region" id="uac-toasts" aria-live="assertive"></div>
</div>
