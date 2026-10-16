<?php
/**
 * Panel de cortes (fechas de entrega) y metas por suministro.
 *
 * Plantilla incluida desde el backend: usa las variables locales del método que la incluye.
 */

defined( 'ABSPATH' ) || exit;
?>
<style><?php echo $this->css( 'base', 'ocultar-tema', 'registro', 'cortes' ); ?></style>

<div class="uac-box uac-cortes" id="uac-app-cortes">
    <header class="uac-head">
        <div class="uac-brand">
            <span class="uac-brand-mark"><?php echo $this->icono( 'calendar', 22 ); ?></span>
            <div>
                <h2 class="uac-title">Cortes y metas</h2>
                <p class="uac-subtitle">Fechas de entrega de la campaña y meta de cada suministro</p>
            </div>
        </div>
    </header>

    <div id="uac-cortes-estado" class="uac-cortes-estado" aria-live="polite"></div>

    <section class="uac-section" aria-labelledby="uac-cortes-titulo">
        <div class="uac-section-head">
            <div>
                <h3 class="uac-section-title" id="uac-cortes-titulo">Cortes creados</h3>
                <p class="uac-section-help">Cada donación se asigna sola al primer corte cuya fecha de entrega sea igual o posterior al día en que se registra (RN-10). Pasada la última fecha, el registro de donaciones se cierra.</p>
            </div>
        </div>
        <div id="uac-cortes-lista" class="uac-cortes-lista">
            <div class="uac-cortes-vacio">Cargando cortes...</div>
        </div>
        <div id="uac-cortes-total" class="uac-cortes-total"></div>
    </section>

    <section class="uac-section" aria-labelledby="uac-nuevo-titulo">
        <div class="uac-section-head">
            <div>
                <h3 class="uac-section-title" id="uac-nuevo-titulo">Crear corte</h3>
                <p class="uac-section-help">Revisa la fecha y las metas antes de crearlo: un corte creado no se puede editar. Solo se puede quitar mientras no tenga donaciones.</p>
            </div>
        </div>
        <form id="uac-form-corte" class="uac-corte-form" novalidate>
            <div class="uac-corte-grid" id="uac-nuevo-campos">
                <div class="uac-field">
                    <label class="uac-label" for="uac-nuevo-fecha">Fecha de entrega</label>
                    <input type="date" class="uac-input" id="uac-nuevo-fecha" required>
                </div>
            </div>
            <p class="uac-corte-preview" id="uac-nuevo-preview"></p>
            <div class="uac-corte-acciones">
                <button type="submit" class="uac-btn" id="uac-btn-crear-corte"><?php echo $this->icono( 'plus', 16 ); ?><span>Crear corte</span></button>
            </div>
        </form>
    </section>

    <div class="uac-toast-region" id="uac-toasts" aria-live="assertive"></div>
</div>
