/**
 * Tablero público en vivo: se actualiza cada 15 s desde /wp-json/uac/v1/resumen.
 * Configuración recibida del backend en window.uacTablero.
 */
document.addEventListener('DOMContentLoaded', function() {
    const URL_RESUMEN = window.uacTablero.urlResumen;
    const URL_REPORTE = window.uacTablero.urlReporte;
    const PUEDE_EQUIPO = !!window.uacTablero.puedeEquipo;
    const NONCE_REST = window.uacTablero.nonce;
    const MI_USUARIO = window.uacTablero.miUsuario;
    const ICO_TROFEO = window.uacTablero.iconoTrofeo;
    const divTime     = document.getElementById('uac-dash-time');
    const divCards    = document.getElementById('uac-dash-cards');
    const tbodyCortes = document.getElementById('uac-dash-cortes');
    const tbodyCarr   = document.getElementById('uac-dash-carreras');
    const syncBox     = divTime.parentElement;

    function esc(valor) {
        return String(valor == null ? '' : valor).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    }

    function estadoSync(estado, texto) {
        syncBox.className = 'uac-sync is-' + estado;
        divTime.textContent = texto;
    }

    // Si la primera carga falla, los marcadores "Cargando..." pasan a un estado de error legible.
    let cargadoAlgunaVez = false;
    function mostrarFalloInicial() {
        if (cargadoAlgunaVez) return;
        const msg = 'No se pudieron obtener los datos. Se reintentará automáticamente.';
        divCards.innerHTML = `<div class="uac-state is-error">${msg}</div>`;
        tbodyCortes.innerHTML = `<tr><td class="uac-state-cell" colspan="6">${msg}</td></tr>`;
        tbodyCarr.innerHTML = `<tr><td class="uac-state-cell" colspan="4">${msg}</td></tr>`;
    }

    // Carreras: filtro, búsqueda y "ver todas" (la lista completa es larga y casi toda en cero al inicio)
    const inpBuscar = document.getElementById('uac-car-buscar');
    const chkSolo   = document.getElementById('uac-car-solo');
    const btnMas    = document.getElementById('uac-car-mas');
    let rankingActual = [];
    let verTodas = false;
    const LIMITE_CARRERAS = 10;

    function pintarCarreras() {
        const q = (inpBuscar.value || '').trim().toLowerCase();
        let lista = rankingActual.map((c, i) => Object.assign({ pos: i + 1 }, c));
        if (chkSolo.checked) lista = lista.filter(c => Number(c.total_unidades_aceptadas) > 0);
        if (q) lista = lista.filter(c => (String(c.nombre) + ' ' + String(c.facultad)).toLowerCase().includes(q));
        const total = lista.length;
        const recortar = !verTodas && !q && total > LIMITE_CARRERAS;
        if (recortar) lista = lista.slice(0, LIMITE_CARRERAS);
        const maximo = Math.max(1, ...rankingActual.map(r => Number(r.total_unidades_aceptadas) || 0));
        tbodyCarr.innerHTML = lista.map(car => {
            const ancho = Math.round(((Number(car.total_unidades_aceptadas) || 0) / maximo) * 100);
            return `
                <tr>
                    <td data-label="#" class="uac-rank">${car.pos}</td>
                    <td data-label="Carrera profesional"><strong>${esc(car.nombre)}</strong></td>
                    <td data-label="Facultad" class="uac-muted">${esc(car.facultad)}</td>
                    <td data-label="Unidades aceptadas" class="uac-num">
                        <div class="uac-meter">
                            <div class="uac-meter-track">${barra('carrera:' + car.nombre, ancho, ' uac-meter-bar')}</div>
                            <b>${esc(car.total_unidades_aceptadas)}</b>
                        </div>
                    </td>
                </tr>`;
        }).join('') || `<tr><td class="uac-state-cell" colspan="4">${rankingActual.length ? 'Ninguna carrera coincide con el filtro.' : 'Todavía no hay aportes registrados.'}</td></tr>`;
        btnMas.hidden = !(recortar || (verTodas && !q && total > LIMITE_CARRERAS));
        btnMas.textContent = verTodas ? 'Ver solo las 10 primeras' : `Ver las ${total} carreras`;
        animarBarras();
    }
    inpBuscar.addEventListener('input', pintarCarreras);
    chkSolo.addEventListener('change', pintarCarreras);
    btnMas.addEventListener('click', () => { verTodas = !verTodas; pintarCarreras(); });

    const el = id => document.getElementById(id);
    const fmtDia = f => { const p = String(f).split('-'); return p[2] + '/' + p[1]; };
    const fmtHora = fh => { const m = String(fh).match(/(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2})/); return m ? m[3] + '/' + m[2] + ' ' + m[4] : ''; };

    function pintarExtra(data) {
        el('uac-dash-demo').hidden = !data.demo;

        // ¿Quién va ganando?
        const carreras = (data.ranking_carreras || []).filter(c => Number(c.total_unidades_aceptadas) > 0);
        const top = carreras.slice(0, 3);
        const fl = (data.ranking_facultades || [])[0];
        const podio = el('uac-dash-podio');
        podio.hidden = !top.length;
        if (top.length) {
            podio.innerHTML = `<h3>${ICO_TROFEO} ¿Quién va ganando?</h3>
                <div class="uac-podio-grid">${top.map((c, i) => `
                    <div class="uac-podio-card is-p${i + 1}">
                        <span class="uac-podio-pos">${i + 1}º lugar</span>
                        <strong>${esc(c.nombre)}</strong>
                        <span class="uac-podio-fac">${esc(c.facultad)}</span>
                        <b>${esc(c.total_unidades_aceptadas)} unidades</b>
                    </div>`).join('')}</div>
                ${fl && Number(fl.total_unidades_aceptadas) > 0 ? `<p class="uac-podio-lider">Facultad líder: <strong>${esc(fl.facultad)}</strong> con ${esc(fl.total_unidades_aceptadas)} unidades.</p>` : ''}`;
        }

        // Indicadores
        const k = data.kpis;
        const kp = el('uac-dash-kpis');
        kp.hidden = !k;
        if (k) {
            const totalCar = (data.ranking_carreras || []).length;
            const tiles = [
                ['Donaciones registradas', k.donaciones, 'entregas recibidas'],
                ['Unidades aceptadas', k.unidades_aceptadas, `${k.unidades_descartadas} descartadas`],
                ['Tasa de aceptación', Math.round(k.tasa_aceptacion * 100) + '%', 'cumplen vencimiento y empaque'],
                ['Carreras participantes', k.carreras_participantes, totalCar ? `de ${totalCar} carreras` : 'carreras'],
                ['Voluntarios activos', k.voluntarios_activos, 'en el punto de acopio'],
            ];
            kp.innerHTML = tiles.map(t => `<div class="uac-kpi"><span>${esc(t[0])}</span><strong>${esc(t[1])}</strong><small>${esc(t[2])}</small></div>`).join('');
        }

        // Actividad de 7 días y facultades
        const ex = el('uac-dash-extra');
        const act = data.actividad_diaria || [];
        const facs = data.ranking_facultades || [];
        ex.hidden = !(act.length || facs.length);
        const maxA = Math.max(1, ...act.map(a => Number(a.unidades) || 0));
        const maxF = Math.max(1, ...facs.map(f => Number(f.total_unidades_aceptadas) || 0));
        ex.innerHTML = (act.length ? `<div class="uac-panel"><h3>Actividad de los últimos 7 días</h3>
                <div class="uac-bars" role="img" aria-label="Unidades aceptadas por día: ${act.map(a => fmtDia(a.fecha) + ': ' + a.unidades).join(', ')}">
                    ${act.map(a => `<div class="uac-bar-col"><b>${esc(a.unidades)}</b><i style="height:${Math.round((Number(a.unidades) || 0) / maxA * 100)}%"></i><span>${esc(fmtDia(a.fecha))}</span></div>`).join('')}
                </div></div>` : '') +
            (facs.length ? `<div class="uac-panel"><h3>Aporte por facultad</h3>
                <ul class="uac-hbars">${facs.map(f => `<li><span>${esc(f.facultad)}</span><div><i style="width:${Math.round((Number(f.total_unidades_aceptadas) || 0) / maxF * 100)}%"></i></div><b>${esc(f.total_unidades_aceptadas)}</b></li>`).join('')}</ul></div>` : '');

        // Últimas donaciones (sin datos personales)
        const ul = data.ultimas_donaciones || [];
        const bu = el('uac-dash-ultimas');
        bu.hidden = !ul.length;
        if (ul.length) {
            bu.innerHTML = `<div class="uac-dash-section-head"><h3>Últimas donaciones</h3><p>Los aportes más recientes, sin datos personales.</p></div>
                <ul class="uac-feed">${ul.map(u => `<li><span class="uac-feed-hora">${esc(fmtHora(u.fecha_hora))}</span><span><strong>${esc(u.carrera)}</strong> aportó ${esc(u.unidades)} ${Number(u.unidades) === 1 ? 'unidad' : 'unidades'}</span></li>`).join('')}</ul>`;
        }
    }

    // Reportes del equipo: solo con sesión de voluntario o administrador
    async function pintarEquipo() {
        if (!PUEDE_EQUIPO) return;
        const bx = el('uac-dash-equipo');
        try {
            const res = await fetch(URL_REPORTE, { headers: { 'X-WP-Nonce': NONCE_REST } });
            const r = await res.json();
            bx.hidden = false;
            if (!r.ok) {
                bx.innerHTML = `<div class="uac-dash-section-head"><h3>Reportes del equipo</h3><p>Aún no disponibles.</p></div>`;
                return;
            }
            const vol = r.voluntarios || [];
            const des = r.descartes || [];
            const totD = des.reduce((t, d) => t + (Number(d.cantidad) || 0), 0);
            bx.innerHTML = `<div class="uac-dash-section-head"><h3>Reportes del equipo</h3><p>Solo visibles con sesión de voluntario o administrador.</p></div>
                <div class="uac-grid2">
                    <div class="uac-panel"><h3>Ranking de voluntarios</h3>
                        <ul class="uac-feed">${vol.map((v, i) => `<li${v.usuario === MI_USUARIO ? ' class="is-yo"' : ''}><span class="uac-feed-hora">${i + 1}º</span><span><strong>${esc(v.usuario)}</strong>${v.usuario === MI_USUARIO ? ' (tú)' : ''}: ${esc(v.donaciones)} donaciones · ${esc(v.unidades)} unidades</span></li>`).join('') || '<li>Aún sin registros.</li>'}</ul></div>
                    <div class="uac-panel"><h3>Motivos de descarte</h3>
                        <ul class="uac-hbars">${des.map(d => `<li><span>${esc(d.motivo)}</span><div><i style="width:${totD ? Math.round(d.cantidad / totD * 100) : 0}%"></i></div><b>${esc(d.cantidad)}</b></li>`).join('') || '<li>Sin descartes.</li>'}</ul>
                        <p class="uac-muted">Útil para recordar a los donantes las reglas de vencimiento y empaque.</p></div>
                </div>`;
        } catch (e) { bx.hidden = true; }
    }

    // Las barras se insertan con el ancho previo y luego pasan al nuevo,
    // para que la transición de ancho (0.3 s) se vea en cada refresco.
    const anchoPrevio = {};
    function barra(clave, pct, extra = '') {
        const desde = anchoPrevio[clave] != null ? anchoPrevio[clave] : 0;
        return `<div class="uac-progress-bar${pct >= 100 && !extra ? ' is-done' : ''}${extra}" data-clave="${esc(clave)}" data-ancho="${pct}" style="width:${desde}%;"></div>`;
    }
    function animarBarras() {
        const barras = document.querySelectorAll('#uac-app-dashboard [data-ancho]');
        void divCards.offsetWidth;
        barras.forEach(b => {
            b.style.width = b.dataset.ancho + '%';
            anchoPrevio[b.dataset.clave] = Number(b.dataset.ancho);
        });
    }

    async function actualizarTablero() {
        try {
            const res = await fetch(URL_RESUMEN);
            const data = await res.json();
            if (!data.ok) {
                estadoSync('error', 'No se pudo actualizar');
                mostrarFalloInicial();
                return;
            }

            cargadoAlgunaVez = true;
            if (data.desactualizado) {
                estadoSync('warn', 'Sin conexión con el servidor. Mostrando datos de: ' + (data.actualizado_en || ''));
            } else {
                estadoSync('ok', 'Última sincronización: ' + (data.actualizado_en || ''));
            }

            // 0. Avance global de la campaña
            const tiposG = data.totales_por_tipo || [];
            const accG = tiposG.reduce((t, x) => t + (Number(x.aceptado) || 0), 0);
            const metaG = tiposG.reduce((t, x) => t + (Number(x.meta_total) || 0), 0);
            const pctG = metaG > 0 ? Math.min(100, Math.round(accG / metaG * 100)) : 0;
            const boxG = document.getElementById('uac-dash-global');
            boxG.hidden = !metaG;
            boxG.innerHTML = `<div class="uac-global-txt"><strong>${pctG}%</strong><span>de la meta global de la campaña · ${esc(accG)} de ${esc(metaG)} unidades aceptadas</span></div>
                <div class="uac-progress-bg" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${pctG}" aria-label="Avance global de la campaña">${barra('global', pctG)}</div>`;

            // 1. Tarjetas por suministro
            divCards.innerHTML = (data.totales_por_tipo || []).map(item => {
                const pct = item.meta_total > 0 ? Math.min(100, Math.round((item.aceptado / item.meta_total) * 100)) : 0;
                return `
                    <div class="uac-card">
                        <div class="uac-card-top">
                            <h4>${esc(item.tipo)}</h4>
                            <span class="uac-card-pct">${pct}%</span>
                        </div>
                        <div class="uac-big-num">${esc(item.aceptado)} <span>/ ${esc(item.meta_total)} meta global</span></div>
                        <div class="uac-progress-bg" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${pct}" aria-label="Avance de ${esc(item.tipo)} hacia la meta global">
                            ${barra('tipo:' + item.tipo, pct)}
                        </div>
                    </div>
                `;
            }).join('') || '<div class="uac-state">Aún no hay suministros configurados.</div>';

            // 2. Tabla de Cortes (el título cuenta los cortes configurados)
            const numCortes = new Set((data.avance_por_corte || []).map(c => c.corte)).size;
            document.getElementById('uac-dash-titulo-cortes').textContent = 'Avance por fecha de entrega' + (numCortes ? ` (${numCortes} ${numCortes === 1 ? 'corte' : 'cortes'})` : '');
            let corteAnterior = null;
            tbodyCortes.innerHTML = (data.avance_por_corte || []).map(c => {
                const pctNum = Math.min(100, Math.round((c.cantidad_aceptada / (c.meta_cantidad || 1)) * 100));
                const inicioGrupo = corteAnterior !== null && corteAnterior !== c.corte;
                corteAnterior = c.corte;
                const sinFaltante = !(c.faltante > 0);
                return `
                    <tr${inicioGrupo ? ' class="uac-group-start"' : ''}>
                        <td data-label="Corte"><span class="uac-badge uac-badge-navy">Corte ${esc(c.corte)}</span></td>
                        <td data-label="Fecha de entrega">${esc(c.fecha_entrega)}</td>
                        <td data-label="Suministro"><strong>${esc(c.tipo)}</strong></td>
                        <td data-label="Aceptado / Meta" class="uac-num"><strong>${esc(c.cantidad_aceptada)}</strong> / ${esc(c.meta_cantidad)}</td>
                        <td data-label="Faltante" class="uac-num"><span class="uac-falt${sinFaltante ? ' is-zero' : ''}">${esc(c.faltante)}</span></td>
                        <td data-label="Avance">
                            <div class="uac-avance">
                                <div class="uac-progress-bg">${barra('corte:' + c.corte + ':' + c.tipo, pctNum)}</div>
                                <span class="uac-avance-pct">${pctNum}%</span>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('') || '<tr><td class="uac-state-cell" colspan="6">Aún no hay cortes configurados.</td></tr>';

            // 3. Tabla de Carreras
            rankingActual = data.ranking_carreras || [];
            pintarCarreras();
            pintarExtra(data);
            pintarEquipo();

            animarBarras();
        } catch (e) {
            console.error('Error refrescando tablero:', e);
            estadoSync('error', 'Sin conexión. Reintentando...');
            mostrarFalloInicial();
        }
    }

    actualizarTablero();
    setInterval(actualizarTablero, 15000);
});
