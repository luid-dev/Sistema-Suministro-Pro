<?php
/**
 * Tablero público en vivo.
 *
 * Plantilla incluida desde el backend: usa las variables locales del método que la incluye.
 */

defined( 'ABSPATH' ) || exit;
?>
<style><?php echo $this->css( 'base', 'ocultar-tema', 'tablero' ); ?></style>

<div class="uac-dash" id="uac-app-dashboard">
    <header class="uac-head uac-dash-header">
        <div class="uac-brand">
            <span class="uac-brand-mark"><?php echo $this->icono( 'chart', 22 ); ?></span>
            <div>
                <h2 class="uac-title">Tablero en vivo: Campaña de suministros</h2>
                <p class="uac-subtitle">Emergencia por incendios forestales en Cusco</p>
            </div>
        </div>
        <div class="uac-sync-wrap">
            <div class="uac-sync is-loading">
                <svg width="8" height="8" viewBox="0 0 8 8" aria-hidden="true" focusable="false"><circle cx="4" cy="4" r="4" fill="currentColor"/></svg>
                <span id="uac-dash-time">Cargando...</span>
            </div>
            <span class="uac-sync-note">Se actualiza cada 15 s</span>
        </div>
    </header>

    <div class="uac-demo-banner" id="uac-dash-demo" hidden><?php echo $this->icono( 'info', 16 ); ?><span><strong>Datos de demostración.</strong> Las cifras son de ejemplo y no provienen de los registros reales de la campaña.</span></div>

    <!-- Resumen global: respuesta inmediata a "¿cómo vamos?" -->
    <section class="uac-global" id="uac-dash-global" aria-live="polite" hidden></section>

    <section class="uac-podio" id="uac-dash-podio" aria-live="polite" hidden></section>
    <section class="uac-kpis" id="uac-dash-kpis" hidden></section>

    <!-- Contadores Globales por los 4 Suministros -->
    <div class="uac-cards" id="uac-dash-cards">
        <div class="uac-state">Cargando suministros...</div>
    </div>

    <div class="uac-grid2" id="uac-dash-extra" hidden></div>

    <!-- Avance por las 3 Fechas de Entrega (Cortes) -->
    <section class="uac-dash-section">
        <div class="uac-dash-section-head">
            <h3 id="uac-dash-titulo-cortes">Avance por fecha de entrega</h3>
            <p>Cantidad aceptada frente a la meta de cada suministro en cada fecha de entrega.</p>
        </div>
        <div class="uac-table-wrap">
            <table class="uac-table uac-table-cortes">
                <thead>
                    <tr>
                        <th>Corte</th>
                        <th>Fecha de entrega</th>
                        <th>Suministro</th>
                        <th class="uac-num">Aceptado / Meta</th>
                        <th class="uac-num">Faltante</th>
                        <th style="width:200px;">Avance</th>
                    </tr>
                </thead>
                <tbody id="uac-dash-cortes">
                    <tr><td class="uac-state-cell" colspan="6">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Ranking por Carrera Profesional (RN-12: Sin códigos personales) -->
    <section class="uac-dash-section">
        <div class="uac-dash-section-head">
            <h3>Aporte total por carrera profesional</h3>
            <p>Unidades aceptadas por escuela profesional.</p>
        </div>
        <div class="uac-filtros">
            <label class="uac-filtro-busca"><span class="screen-reader-text">Buscar carrera o facultad</span>
                <input type="search" id="uac-car-buscar" placeholder="Buscar carrera o facultad" autocomplete="off"></label>
            <label class="uac-filtro-check"><input type="checkbox" id="uac-car-solo"> Solo con aportes</label>
        </div>
        <div class="uac-table-wrap">
            <table class="uac-table uac-table-carreras">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Carrera profesional</th>
                        <th>Facultad</th>
                        <th class="uac-num">Unidades aceptadas</th>
                    </tr>
                </thead>
                <tbody id="uac-dash-carreras">
                    <tr><td class="uac-state-cell" colspan="4">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="uac-ver-mas" id="uac-car-mas" hidden></button>
    </section>

    <section class="uac-dash-section" id="uac-dash-ultimas" hidden></section>
    <section class="uac-dash-section" id="uac-dash-equipo" hidden></section>

    <p class="uac-privacy"><?php echo $this->icono( 'shield', 14 ); ?><span>Este tablero muestra únicamente cifras agregadas. No se publican códigos de estudiantes ni datos personales (Ley N.° 29733).</span></p>
</div>
