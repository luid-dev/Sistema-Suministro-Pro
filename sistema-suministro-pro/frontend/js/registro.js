/**
 * Registro de donaciones: lector QR, consulta del estudiante, líneas de producto y envío a la API.
 * Configuración recibida del backend en window.uacRegistro.
 */
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE = window.uacRegistro.apiBase;
    const NONCE    = window.uacRegistro.nonce;
    const ICONOS   = window.uacRegistro.iconos;

    const CRITERIOS_FISICOS = {
        'Bebida rehidratante': 'Unidad: botella | Criterio físico: sellada, sin fugas ni hinchazón.',
        'Chocolate': 'Unidad: barra o tableta | Criterio físico: envoltura cerrada, sin humedad.',
        'Galletas soda': 'Unidad: paquete | Criterio físico: paquete sin roturas.',
        'Atún': 'Unidad: lata | Criterio físico: sin abolladuras, óxido ni abombamiento.'
    };

    let qrScanner = null;
    let scannerActivo = false;
    let estudianteVerificado = false;
    let estudianteRegistradoEnPadron = false;
    let contadorLineas = 0;

    const inputCodigo   = document.getElementById('uac-codigo');
    const btnBuscar     = document.getElementById('uac-btn-buscar');
    const btnQr         = document.getElementById('uac-btn-qr');
    const infoBox       = document.getElementById('uac-info-estudiante');
    const estStatus     = document.getElementById('uac-est-status');
    const wrapCarrera   = document.getElementById('uac-wrap-carrera-nueva');
    const selectCarrera = document.getElementById('uac-select-carrera');
    const listaItems    = document.getElementById('uac-lista-items');
    const btnAddItem    = document.getElementById('uac-btn-add-item');
    const btnGuardar    = document.getElementById('uac-btn-guardar');
    const resEnvio      = document.getElementById('uac-resultado-envio');
    const toastRegion   = document.getElementById('uac-toasts');

    // ---------- Utilidades de interfaz ----------
    function ico(nombre, size = 16, extra = '') {
        return `<svg class="uac-ico ${extra}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${ICONOS[nombre] || ''}</svg>`;
    }

    function esc(valor) {
        return String(valor == null ? '' : valor).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    }

    function setBtn(btn, icono, texto, extra = '') {
        btn.innerHTML = ico(icono, btn === btnGuardar ? 18 : 16, extra) + '<span>' + texto + '</span>';
    }

    let toastTimer = null;
    function avisar(mensaje, tipo = 'error') {
        const t = document.createElement('div');
        t.className = 'uac-toast uac-toast-' + tipo;
        t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
        t.innerHTML = ico(tipo === 'error' ? 'alert' : 'info', 18) + '<span>' + esc(mensaje) + '</span>';
        t.addEventListener('click', () => t.remove());
        toastRegion.innerHTML = '';
        toastRegion.appendChild(t);
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.remove(), 7000);
    }

    function marcarCodigoInvalido(invalido) {
        if (invalido) {
            inputCodigo.setAttribute('aria-invalid', 'true');
            inputCodigo.focus();
        } else {
            inputCodigo.removeAttribute('aria-invalid');
        }
    }
    inputCodigo.addEventListener('input', () => marcarCodigoInvalido(false));
    inputCodigo.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            verificarEstudiante();
        }
    });

    function mostrarEstudiante(estado, html) {
        infoBox.className = 'uac-info-box is-' + estado;
        estStatus.innerHTML = html;
    }

    // Criterio físico como nota técnica (conserva el texto original de CRITERIOS_FISICOS)
    function renderCriterio(tipo) {
        const texto = CRITERIOS_FISICOS[tipo] || '';
        return texto.split(' | ').map(parte => {
            const i = parte.indexOf(':');
            if (i === -1) return `<span>${esc(parte)}</span>`;
            return `<span><b>${esc(parte.slice(0, i))}:</b> ${esc(parte.slice(i + 1).trim())}</span>`;
        }).join('');
    }

    const TXT_QR_ON  = 'Escanear QR';
    const TXT_QR_OFF = 'Detener cámara';
    const TXT_GUARDAR = 'Registrar donación en el sistema';

    // Calcula fecha mínima de vencimiento (+3 meses exactos con ajuste de fin de mes - RN-06)
    function obtenerFechaMinimaRN06() {
        const hoy = new Date();
        const anio = hoy.getFullYear();
        const mes = hoy.getMonth();
        const dia = hoy.getDate();
        const mesDestino = mes + 3;
        const ultimoDia = new Date(anio, mesDestino + 1, 0).getDate();
        const diaAjustado = Math.min(dia, ultimoDia);
        return new Date(anio, mesDestino, diaAjustado, 0, 0, 0, 0);
    }

    function formatearISO(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    // Crear fila de suministro
    function agregarFilaProducto() {
        const uid = ++contadorLineas;
        const card = document.createElement('div');
        card.className = 'uac-item-card';
        const minDateStr = formatearISO(obtenerFechaMinimaRN06());

        card.innerHTML = `
            <div class="uac-item-grid">
                <span class="uac-item-num" aria-hidden="true"></span>
                <div class="uac-field uac-f-tipo">
                    <label class="uac-label" for="uac-tipo-${uid}">Suministro</label>
                    <select class="uac-input uac-item-tipo" id="uac-tipo-${uid}">
                        <option value="Bebida rehidratante">Bebida rehidratante</option>
                        <option value="Chocolate">Chocolate</option>
                        <option value="Galletas soda">Galletas soda</option>
                        <option value="Atún">Atún</option>
                    </select>
                </div>
                <div class="uac-field uac-f-cant">
                    <label class="uac-label" for="uac-cant-${uid}">Cantidad</label>
                    <input type="number" class="uac-input uac-item-cant" id="uac-cant-${uid}" min="1" step="1" value="1" inputmode="numeric" />
                </div>
                <div class="uac-field uac-f-venc">
                    <label class="uac-label" for="uac-venc-${uid}">Fecha de vencimiento</label>
                    <input type="date" class="uac-input uac-item-venc" id="uac-venc-${uid}" value="${minDateStr}" />
                    <div class="uac-aviso-fecha"></div>
                </div>
                <button type="button" class="uac-btn-icon uac-btn-del" title="Quitar línea" aria-label="Quitar línea">${ico('trash', 16)}</button>
            </div>
            <div class="uac-item-foot">
                <div class="uac-criterio">${renderCriterio('Bebida rehidratante')}</div>
                <div class="uac-inspect">
                    <label class="uac-check">
                        <input type="checkbox" class="uac-item-empaque" checked />
                        <span><strong>Inspección física conforme</strong><em>empaque íntegro</em></span>
                    </label>
                </div>
                <input type="text" class="uac-input uac-item-motivo-fisico" placeholder="Motivo de falla física (ej. lata abollada)" aria-label="Motivo de falla física" style="display:none;" />
            </div>
        `;

        const selTipo = card.querySelector('.uac-item-tipo');
        const divCrit = card.querySelector('.uac-criterio');
        const inpVenc = card.querySelector('.uac-item-venc');
        const divAviso = card.querySelector('.uac-aviso-fecha');
        const chkEmpaque = card.querySelector('.uac-item-empaque');
        const inpMotivo = card.querySelector('.uac-item-motivo-fisico');
        const btnDel = card.querySelector('.uac-btn-del');

        selTipo.addEventListener('change', () => {
            divCrit.innerHTML = renderCriterio(selTipo.value);
        });

        function validarVencimientoEnVivo() {
            if (!inpVenc.value) {
                divAviso.innerHTML = `<span class="uac-badge uac-badge-amber">${ico('info', 14)}Seleccione fecha</span>`;
                return;
            }
            const partes = inpVenc.value.split('-');
            const fVenc = new Date(Number(partes[0]), Number(partes[1]) - 1, Number(partes[2]), 0, 0, 0, 0);
            const fMin = obtenerFechaMinimaRN06();
            const hoy = new Date();
            const mesesAprox = ((fVenc.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24 * 30.44)).toFixed(1);

            if (fVenc.getTime() >= fMin.getTime()) {
                divAviso.innerHTML = `<span class="uac-badge uac-badge-ok">${ico('check', 14)}Cumple RN-06 (~${mesesAprox} meses)</span>`;
            } else {
                divAviso.innerHTML = `<span class="uac-badge uac-badge-warn">${ico('alert', 14)}Menos de 3 meses (~${mesesAprox} m): irá a Descartes</span>`;
            }
        }

        inpVenc.addEventListener('change', validarVencimientoEnVivo);
        validarVencimientoEnVivo();

        chkEmpaque.addEventListener('change', () => {
            inpMotivo.style.display = chkEmpaque.checked ? 'none' : 'block';
            card.classList.toggle('is-reject', !chkEmpaque.checked);
            if (!chkEmpaque.checked) inpMotivo.focus();
        });

        btnDel.addEventListener('click', () => {
            if (listaItems.children.length > 1) {
                card.remove();
            } else {
                avisar('RN-09: La donación debe tener al menos una línea de producto.');
            }
        });

        listaItems.appendChild(card);
    }

    // Inicializar con 1 fila
    agregarFilaProducto();
    btnAddItem.addEventListener('click', agregarFilaProducto);

    // Buscar estudiante en el padrón
    async function verificarEstudiante() {
        const codigo = inputCodigo.value.trim().toUpperCase();
        inputCodigo.value = codigo;

        if (!/^[0-9]{9}[A-Z]$/.test(codigo)) {
            marcarCodigoInvalido(true);
            avisar('RN-01: El código de estudiante debe tener 9 dígitos y 1 letra (ejemplo: 012345678A).');
            return;
        }
        marcarCodigoInvalido(false);

        infoBox.style.display = 'block';
        mostrarEstudiante('loading', `<div class="uac-inline">${ico('loader', 16, 'uac-spin')}<span>Consultando padrón...</span></div>`);
        wrapCarrera.style.display = 'none';

        try {
            const res = await fetch(`${API_BASE}/estudiante/${codigo}`, {
                headers: { 'X-WP-Nonce': NONCE }
            });
            const data = await res.json();

            if (!data.ok) {
                mostrarEstudiante('error', `<div class="uac-status">${ico('x-circle', 20)}<div><strong>Error: ${esc(data.error || 'No se pudo verificar')}</strong></div></div>`);
                estudianteVerificado = false;
                return;
            }

            estudianteVerificado = true;
            if (data.registrado) {
                estudianteRegistradoEnPadron = true;
                mostrarEstudiante('ok', `
                    <div class="uac-status">${ico('check-circle', 20)}
                        <div>
                            <strong>Estudiante registrado</strong>
                            <dl class="uac-kv">
                                <div><dt>Código</dt><dd>${esc(data.codigo)}</dd></div>
                                <div><dt>Carrera</dt><dd>${esc(data.carrera_nombre)}</dd></div>
                                <div><dt>Facultad</dt><dd>${esc(data.facultad)}</dd></div>
                            </dl>
                        </div>
                    </div>`);
                wrapCarrera.style.display = 'none';
            } else {
                estudianteRegistradoEnPadron = false;
                mostrarEstudiante('info', `
                    <div class="uac-status">${ico('info', 20)}
                        <div>
                            <strong>Código nuevo: ${esc(data.codigo)}</strong>
                            <p>No figura en el padrón aún. Selecciona su carrera para registrarlo automáticamente.</p>
                        </div>
                    </div>`);
                selectCarrera.innerHTML = '<option value="">-- Seleccione la carrera profesional --</option>';
                (data.carreras_disponibles || []).forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.carrera_id;
                    opt.textContent = `${c.nombre} (${c.facultad})`;
                    selectCarrera.appendChild(opt);
                });
                wrapCarrera.style.display = 'block';
            }
        } catch (err) {
            mostrarEstudiante('error', `<div class="uac-status">${ico('x-circle', 20)}<div><strong>Error de conexión con la API.</strong></div></div>`);
        }
    }

    btnBuscar.addEventListener('click', verificarEstudiante);

    // Lector QR con html5-qrcode
    btnQr.addEventListener('click', () => {
        const qrDiv = document.getElementById('uac-qr-reader');
        if (scannerActivo && qrScanner) {
            qrScanner.stop().then(() => {
                qrDiv.style.display = 'none';
                scannerActivo = false;
                setBtn(btnQr, 'camera', TXT_QR_ON);
            });
            return;
        }

        qrDiv.style.display = 'block';
        setBtn(btnQr, 'stop', TXT_QR_OFF);
        qrScanner = new Html5Qrcode('uac-qr-reader');
        scannerActivo = true;

        qrScanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            (decodedText) => {
                const limpio = decodedText.trim().toUpperCase();
                inputCodigo.value = limpio;
                qrScanner.stop().then(() => {
                    qrDiv.style.display = 'none';
                    scannerActivo = false;
                    setBtn(btnQr, 'camera', TXT_QR_ON);
                    verificarEstudiante();
                });
            },
            () => {}
        ).catch(err => {
            avisar('No se pudo acceder a la cámara: ' + err);
            qrDiv.style.display = 'none';
            scannerActivo = false;
            setBtn(btnQr, 'camera', TXT_QR_ON);
        });
    });

    // Corte en curso (RN-10): informa a qué fecha de entrega van las donaciones de hoy
    (async function mostrarCorteActual() {
        const caja = document.getElementById('uac-corte-actual');
        try {
            const res = await fetch(`${API_BASE}/resumen`);
            const data = await res.json();
            if (!data.ok || !('corte_activo' in data)) return;
            if (data.corte_activo) {
                const d = /^(\d{4})-(\d{2})-(\d{2})$/.exec(data.corte_activo_fecha || '');
                const fecha = d ? new Date(+d[1], +d[2] - 1, +d[3]).toLocaleDateString('es-PE', { day: 'numeric', month: 'long' }) : (data.corte_activo_fecha || '');
                caja.className = 'uac-corte-actual is-ok';
                caja.innerHTML = ico('calendar', 16) + `<span>Las donaciones de hoy se asignan al <strong>Corte ${esc(data.corte_activo)}</strong> (entrega el ${esc(fecha)}).</span>`;
            } else {
                caja.className = 'uac-corte-actual is-error';
                caja.setAttribute('role', 'alert');
                caja.innerHTML = ico('alert', 16) + '<span><strong>No hay un corte abierto para hoy.</strong> El registro de donaciones está cerrado hasta que el administrador agregue una fecha de entrega en "Cortes y metas".</span>';
            }
        } catch (e) { /* sin aviso si el resumen no responde */ }
    })();

    // Guardar donación
    btnGuardar.addEventListener('click', async () => {
        const codigo = inputCodigo.value.trim().toUpperCase();
        if (!/^[0-9]{9}[A-Z]$/.test(codigo)) {
            marcarCodigoInvalido(true);
            avisar('RN-01: Primero ingresa y verifica un código de estudiante válido (9 dígitos + 1 letra).');
            return;
        }

        if (!estudianteVerificado) {
            await verificarEstudiante();
            if (!estudianteVerificado) return;
        }

        let carreraId = '';
        if (!estudianteRegistradoEnPadron) {
            carreraId = selectCarrera.value;
            if (!carreraId) {
                selectCarrera.focus();
                avisar('RN-03: Por favor selecciona la carrera profesional del estudiante.');
                return;
            }
        }

        const cards = listaItems.querySelectorAll('.uac-item-card');
        const items = [];

        for (let i = 0; i < cards.length; i++) {
            const c = cards[i];
            const tipo = c.querySelector('.uac-item-tipo').value;
            const cantidad = parseInt(c.querySelector('.uac-item-cant').value, 10);
            const fecha_vencimiento = c.querySelector('.uac-item-venc').value;
            const empaque_ok = c.querySelector('.uac-item-empaque').checked;
            const motivo_rechazo_fisico = c.querySelector('.uac-item-motivo-fisico').value.trim();

            if (!cantidad || cantidad < 1 || !fecha_vencimiento) {
                avisar('Verifica que todas las líneas tengan cantidad >= 1 y fecha de vencimiento.');
                return;
            }

            items.push({ tipo, cantidad, fecha_vencimiento, empaque_ok, motivo_rechazo_fisico });
        }

        btnGuardar.disabled = true;
        setBtn(btnGuardar, 'loader', 'Procesando donación...', 'uac-spin');
        resEnvio.innerHTML = '';

        try {
            const res = await fetch(`${API_BASE}/donaciones`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': NONCE
                },
                body: JSON.stringify({ codigo, carrera_id: carreraId, items })
            });

            const data = await res.json();
            if (!data.ok) {
                resEnvio.innerHTML = `<div class="uac-error-box" role="alert">${ico('x-circle', 20)}<div><strong>Error al registrar:</strong> ${esc(data.error || 'Error desconocido')}</div></div>`;
            } else {
                const aceptados  = data.aceptados || [];
                const rechazados = data.rechazados || [];
                const sumar = (lista) => lista.reduce((s, x) => s + (Number(x.cantidad) || 0), 0);

                const htmlAceptados = aceptados.map(a => `
                    <li>
                        <div class="uac-line-main"><strong>${esc(a.tipo)}</strong><span class="uac-line-qty">${esc(a.cantidad)} und.</span></div>
                        <div class="uac-line-meta">
                            <span>Vence: ${esc(a.fecha_vencimiento)}</span>
                            <span>Etiquetar como <code>${esc(a.lote_id)}</code></span>
                            <span>${esc(a.detalle_id)}</span>
                        </div>
                    </li>`).join('') || '<li class="uac-empty">Ninguno</li>';

                const htmlRechazados = rechazados.map(r => `
                    <li>
                        <div class="uac-line-main"><strong>${esc(r.tipo)}</strong><span class="uac-line-qty">${esc(r.cantidad)} und.</span></div>
                        <div class="uac-line-meta">
                            <span>${esc(r.motivo)}</span>
                            <span>${esc(r.descarte_id)}</span>
                        </div>
                    </li>`).join('') || '<li class="uac-empty">Ninguno</li>';

                resEnvio.innerHTML = `
                    <article class="uac-receipt">
                        <header class="uac-receipt-head">
                            ${ico('check-circle', 24)}
                            <div>
                                <h4 class="uac-receipt-title">Donación registrada</h4>
                                <p class="uac-receipt-sub">Acta de recepción</p>
                            </div>
                            <div class="uac-receipt-id">
                                <span class="uac-badge uac-badge-navy">${esc(data.donacion_id)}</span>
                                <span class="uac-badge uac-badge-neutral">Corte ${esc(data.corte)}</span>
                            </div>
                        </header>
                        <div class="uac-receipt-meta">
                            <dl class="uac-kv">
                                <div><dt>Estudiante</dt><dd>${esc(data.codigo)}</dd></div>
                                <div><dt>Carrera</dt><dd>${esc(data.carrera_nombre)}</dd></div>
                                <div><dt>Fecha y hora de ingreso</dt><dd>${esc(data.fecha_ingreso)}</dd></div>
                            </dl>
                        </div>
                        <div class="uac-receipt-cols">
                            <section class="uac-panel uac-panel-ok">
                                <div class="uac-panel-head">${ico('package', 16)}<h5>Inventario aceptado (ingreso a lote)</h5><span class="uac-badge uac-badge-ok">${sumar(aceptados)} und.</span></div>
                                <p class="uac-panel-sub">Etiquetar cada línea con su lote. Suman al contador oficial.</p>
                                <ul class="uac-lines">${htmlAceptados}</ul>
                            </section>
                            <section class="uac-panel uac-panel-bad">
                                <div class="uac-panel-head">${ico('ban', 16)}<h5>Registro de mermas / log de descartes</h5><span class="uac-badge uac-badge-warn">${sumar(rechazados)} und.</span></div>
                                <p class="uac-panel-sub">Rechazos por RN-06 o inspección física (RN-08). No suman al contador.</p>
                                <ul class="uac-lines">${htmlRechazados}</ul>
                            </section>
                        </div>
                    </article>
                `;

                // Reiniciar para siguiente estudiante
                listaItems.innerHTML = '';
                agregarFilaProducto();
                estudianteRegistradoEnPadron = true;
            }
            resEnvio.scrollIntoView({ block: 'nearest' });
        } catch (e) {
            resEnvio.innerHTML = `<div class="uac-error-box" role="alert">${ico('x-circle', 20)}<div>Error de red al enviar la donación.</div></div>`;
        } finally {
            btnGuardar.disabled = false;
            setBtn(btnGuardar, 'save', TXT_GUARDAR);
        }
    });
});
