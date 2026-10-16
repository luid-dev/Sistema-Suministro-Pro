/**
 * Cortes y metas (solo administrador): lista, crea y quita fechas de entrega con sus metas.
 * Configuración recibida del backend en window.uacCortes.
 */
document.addEventListener('DOMContentLoaded', function () {
    const API_BASE = window.uacCortes.apiBase;
    const NONCE    = window.uacCortes.nonce;
    const ICONOS   = window.uacCortes.iconos;
    const SUMINISTROS_POR_DEFECTO = ['Bebida rehidratante', 'Chocolate', 'Galletas soda', 'Atún'];

    const divEstado = document.getElementById('uac-cortes-estado');
    const lista     = document.getElementById('uac-cortes-lista');
    const divTotal  = document.getElementById('uac-cortes-total');
    const form      = document.getElementById('uac-form-corte');
    const campos    = document.getElementById('uac-nuevo-campos');
    const inpFecha  = document.getElementById('uac-nuevo-fecha');
    const preview   = document.getElementById('uac-nuevo-preview');
    const btnCrear  = document.getElementById('uac-btn-crear-corte');
    const toasts    = document.getElementById('uac-toasts');

    let suministros = SUMINISTROS_POR_DEFECTO.slice();
    let cortes = [];
    let corteActivo = null;
    let hoy = '';

    function ico(nombre, size = 16, extra = '') {
        return `<svg class="uac-ico ${extra}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${ICONOS[nombre] || ''}</svg>`;
    }
    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    }
    function avisar(mensaje, tipo = 'error') {
        const t = document.createElement('div');
        t.className = 'uac-toast uac-toast-' + tipo;
        t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
        t.innerHTML = ico(tipo === 'error' ? 'alert' : (tipo === 'ok' ? 'check-circle' : 'info'), 18) + '<span>' + esc(mensaje) + '</span>';
        t.addEventListener('click', () => t.remove());
        toasts.innerHTML = '';
        toasts.appendChild(t);
        setTimeout(() => t.remove(), 6000);
    }
    function parseISO(str) {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(str || ''));
        return m ? new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])) : null;
    }
    function fechaLarga(str) {
        const d = parseISO(str);
        return d ? d.toLocaleDateString('es-PE', { day: 'numeric', month: 'long', year: 'numeric' }) : str;
    }
    function diaSiguiente(str) {
        const d = parseISO(str);
        if (!d) return '';
        d.setDate(d.getDate() + 1);
        return d.toLocaleDateString('es-PE', { day: 'numeric', month: 'long' });
    }
    // Texto del rango de días que cubre un corte, según el corte anterior
    function rango(fecha, lista) {
        const anteriores = lista.map(c => c.fecha_entrega).filter(f => f < fecha).sort();
        const previo = anteriores[anteriores.length - 1];
        return previo
            ? 'Recibe donaciones del ' + diaSiguiente(previo) + ' al ' + fechaLarga(fecha)
            : 'Recibe donaciones hasta el ' + fechaLarga(fecha);
    }

    function pintarEstado(error) {
        if (error) {
            divEstado.innerHTML = `<div class="uac-estado is-error" role="alert">${ico('x-circle', 18)}<div><strong>No se pudieron cargar los cortes.</strong> ${esc(error)}</div></div>`;
            return;
        }
        const activo = cortes.find(c => c.corte === corteActivo);
        if (activo) {
            divEstado.innerHTML = `<div class="uac-estado is-ok">${ico('check-circle', 18)}<div>Hoy las donaciones se asignan al <strong>Corte ${esc(activo.corte)}</strong>, con entrega el ${esc(fechaLarga(activo.fecha_entrega))}.</div></div>`;
        } else if (!cortes.length) {
            divEstado.innerHTML = `<div class="uac-estado is-warn">${ico('alert', 18)}<div><strong>Aún no hay cortes.</strong> Crea al menos una fecha de entrega para habilitar el registro de donaciones.</div></div>`;
        } else {
            divEstado.innerHTML = `<div class="uac-estado is-error">${ico('alert', 18)}<div><strong>No hay un corte abierto para hoy.</strong> Todas las fechas de entrega ya pasaron: crea una nueva para seguir registrando donaciones.</div></div>`;
        }
    }

    function pintarLista() {
        if (!cortes.length) {
            lista.innerHTML = '<div class="uac-cortes-vacio">Todavía no hay cortes. Crea el primero en el formulario de abajo.</div>';
            divTotal.innerHTML = '';
            return;
        }
        lista.innerHTML = cortes.map(c => {
            const n = Number(c.donaciones) || 0;
            const enCurso = c.corte === corteActivo;
            return `
                <article class="uac-corte-card${enCurso ? ' is-activo' : ''}">
                    <div class="uac-corte-head">
                        <span class="uac-badge uac-badge-navy">Corte ${esc(c.corte)}</span>
                        ${enCurso ? '<span class="uac-badge uac-badge-ok">En curso</span>' : (c.fecha_entrega < hoy ? '<span class="uac-badge uac-badge-neutral">Cerrado</span>' : '')}
                        <span class="uac-corte-rango">${esc(rango(c.fecha_entrega, cortes))}</span>
                        <span class="uac-corte-don">${n === 1 ? '1 donación' : n + ' donaciones'}</span>
                        ${n === 0 ? `<button type="button" class="uac-btn-icon uac-corte-quitar" data-corte="${esc(c.corte)}" aria-label="Quitar Corte ${esc(c.corte)}" title="Quitar corte (no tiene donaciones)">${ico('trash', 16)}</button>` : ''}
                    </div>
                    <dl class="uac-corte-metas">
                        <div><dt>Fecha de entrega</dt><dd>${esc(fechaLarga(c.fecha_entrega))}</dd></div>
                        ${suministros.map(t => `<div><dt>Meta: ${esc(t)}</dt><dd>${esc((c.metas && c.metas[t]) || 0)}</dd></div>`).join('')}
                    </dl>
                </article>`;
        }).join('');
        divTotal.innerHTML = '<span>Meta global de la campaña:</span>' + suministros.map(t =>
            `<span>${esc(t)} <b>${cortes.reduce((s, c) => s + ((c.metas && c.metas[t]) || 0), 0)}</b></span>`).join('');
    }

    function pintarFormulario() {
        campos.querySelectorAll('.uac-field-meta').forEach(el => el.remove());
        suministros.forEach((t, i) => {
            const div = document.createElement('div');
            div.className = 'uac-field uac-field-meta';
            div.innerHTML = `<label class="uac-label" for="uac-nuevo-m${i}">Meta: ${esc(t)}</label>
                <input type="number" class="uac-input uac-nuevo-meta" id="uac-nuevo-m${i}" data-tipo="${esc(t)}" min="0" step="1" inputmode="numeric" placeholder="0">`;
            campos.appendChild(div);
        });
        if (hoy) inpFecha.min = hoy;
        campos.querySelectorAll('input').forEach(inp => inp.addEventListener('input', () => {
            inp.removeAttribute('aria-invalid');
            actualizarPreview();
        }));
        actualizarPreview();
    }

    function actualizarPreview() {
        const f = inpFecha.value;
        preview.textContent = parseISO(f) ? 'Este corte ' + rango(f, cortes).charAt(0).toLowerCase() + rango(f, cortes).slice(1) + '.' : '';
    }

    // Quitar un corte sin donaciones
    lista.addEventListener('click', async (e) => {
        const btn = e.target.closest('.uac-corte-quitar');
        if (!btn) return;
        const numero = Number(btn.dataset.corte);
        const corte = cortes.find(c => c.corte === numero);
        if (!corte) return;
        if (!window.confirm('¿Quitar el Corte ' + numero + ' (entrega el ' + fechaLarga(corte.fecha_entrega) + ')?\n\nNo tiene donaciones, así que no se pierde ningún registro.')) {
            return;
        }
        btn.disabled = true;
        btn.innerHTML = ico('loader', 16, 'uac-spin');
        try {
            const res = await fetch(`${API_BASE}/cortes/${numero}`, { method: 'DELETE', headers: { 'X-WP-Nonce': NONCE } });
            const data = await res.json();
            if (!data.ok) {
                avisar(data.error || 'No se pudo quitar el corte.');
                btn.disabled = false;
                btn.innerHTML = ico('trash', 16);
            } else {
                pintar(data);
                actualizarPreview();
                avisar('Corte ' + numero + ' quitado.', 'ok');
            }
        } catch (err) {
            avisar('Error de red al quitar el corte.');
            btn.disabled = false;
            btn.innerHTML = ico('trash', 16);
        }
    });

    function pintar(data) {
        suministros = (data.suministros && data.suministros.length) ? data.suministros : SUMINISTROS_POR_DEFECTO.slice();
        cortes = (data.cortes || []).slice().sort((a, b) => a.fecha_entrega.localeCompare(b.fecha_entrega));
        corteActivo = data.corte_activo || null;
        hoy = data.hoy || '';
        pintarLista();
        pintarEstado();
    }

    async function cargar() {
        try {
            const res = await fetch(`${API_BASE}/cortes`, { headers: { 'X-WP-Nonce': NONCE } });
            const data = await res.json();
            if (!data.ok) {
                lista.innerHTML = '<div class="uac-cortes-vacio">No disponible.</div>';
                pintarEstado(data.error || 'Respuesta no válida.');
                return;
            }
            pintar(data);
            pintarFormulario();
        } catch (e) {
            lista.innerHTML = '<div class="uac-cortes-vacio">No disponible.</div>';
            pintarEstado('Error de conexión con la API.');
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fecha = inpFecha.value;
        if (!parseISO(fecha)) {
            inpFecha.setAttribute('aria-invalid', 'true');
            inpFecha.focus();
            avisar('Elige la fecha de entrega del corte.');
            return;
        }
        if (hoy && fecha < hoy) {
            inpFecha.setAttribute('aria-invalid', 'true');
            inpFecha.focus();
            avisar('La fecha de entrega no puede ser anterior a hoy.');
            return;
        }
        const repetido = cortes.find(c => c.fecha_entrega === fecha);
        if (repetido) {
            inpFecha.setAttribute('aria-invalid', 'true');
            inpFecha.focus();
            avisar('Ya existe un corte con esa fecha (Corte ' + repetido.corte + ').');
            return;
        }
        const metas = {};
        let invalida = null;
        campos.querySelectorAll('.uac-nuevo-meta').forEach(inp => {
            const v = inp.value.trim() === '' ? 0 : Number(inp.value);
            if (!Number.isInteger(v) || v < 0) { invalida = invalida || inp; }
            metas[inp.dataset.tipo] = Number.isInteger(v) && v > 0 ? v : 0;
        });
        if (invalida) {
            invalida.setAttribute('aria-invalid', 'true');
            invalida.focus();
            avisar('Las metas deben ser números enteros mayores o iguales a 0.');
            return;
        }
        if (!Object.values(metas).some(v => v > 0)) {
            avisar('Indica la meta de al menos un suministro.');
            campos.querySelector('.uac-nuevo-meta').focus();
            return;
        }
        const resumen = suministros.map(t => t + ': ' + metas[t]).join('\n');
        if (!window.confirm('¿Crear este corte?\n\nFecha de entrega: ' + fechaLarga(fecha) + '\n' + resumen + '\n\nDespués no se podrá editar. Solo podrás quitarlo mientras no tenga donaciones.')) {
            return;
        }

        btnCrear.disabled = true;
        btnCrear.innerHTML = ico('loader', 16, 'uac-spin') + '<span>Creando corte...</span>';
        try {
            const res = await fetch(`${API_BASE}/cortes`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
                body: JSON.stringify({ fecha_entrega: fecha, metas: metas })
            });
            const data = await res.json();
            if (!data.ok) {
                avisar(data.error || 'No se pudo crear el corte.');
            } else {
                pintar(data);
                form.reset();
                actualizarPreview();
                const creado = cortes.find(c => c.fecha_entrega === fecha);
                avisar((creado ? 'Corte ' + creado.corte : 'Corte') + ' creado.', 'ok');
            }
        } catch (err) {
            avisar('Error de red al crear el corte.');
        } finally {
            btnCrear.disabled = false;
            btnCrear.innerHTML = ico('plus', 16) + '<span>Crear corte</span>';
        }
    });

    cargar();
});
