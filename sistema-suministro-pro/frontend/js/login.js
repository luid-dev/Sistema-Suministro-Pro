/**
 * Pantalla de acceso: pestañas Ingresar / Solicitar, validación de nombres, medidor de contraseña y botón "ver contraseña".
 * Configuración recibida del backend en window.uacLogin.
 */
(function () {
    var cont = document.getElementById('uac-login-modos');
    if (cont) {
        var ir = function (modo) {
            cont.setAttribute('data-modo', modo);
            cont.querySelectorAll('[data-ir]').forEach(function (b) { b.setAttribute('aria-selected', b.getAttribute('data-ir') === modo ? 'true' : 'false'); });
        };
        cont.querySelectorAll('[data-ir]').forEach(function (b) { b.addEventListener('click', function () { ir(b.getAttribute('data-ir')); }); });
        if (location.hash === '#solicitar') ir('solicitar');
    }
    // Nombres y apellidos: se limpian los espacios al salir del campo y el error aparece junto al campo (no antes de tocarlo).
    document.querySelectorAll('input[data-nombre]').forEach(function (inp) {
        var err = document.getElementById(inp.id + '-err');
        var mostrar = function (msg) {
            err.textContent = msg;
            err.hidden = !msg;
            if (msg) { inp.setAttribute('aria-invalid', 'true'); } else { inp.removeAttribute('aria-invalid'); }
        };
        var revisar = function () {
            var v = inp.value;
            if (!v) { return 'Este campo es obligatorio.'; }
            if (v.length < 2) { return 'Escribe al menos 2 letras.'; }
            if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ][A-Za-zÁÉÍÓÚÜÑáéíóúüñ' .\-]*$/.test(v)) { return 'Usa solo letras, espacios, apóstrofo o guion.'; }
            return '';
        };
        inp.addEventListener('blur', function () {
            inp.value = inp.value.replace(/\s+/g, ' ').trim();
            mostrar(inp.value || inp.dataset.tocado ? revisar() : '');
            inp.dataset.tocado = '1';
        });
        inp.addEventListener('input', function () {
            if (inp.hasAttribute('aria-invalid')) { mostrar(revisar()); }
        });
        inp.addEventListener('invalid', function (e) {
            e.preventDefault();
            mostrar(revisar() || 'Revisa este campo.');
        });
    });
    var clave = document.getElementById('uac-s-clave');
    if (clave) {
        var barra = clave.parentNode.querySelector('.uac-fuerza');
        var txt = document.getElementById('uac-fuerza-txt');
        clave.addEventListener('input', function () {
            var v = clave.value, n = 0;
            if (v.length >= 8) n++;
            if (v.length >= 12) n++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) n++;
            if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) n++;
            var niv = !v ? [0, '#CBD5E1', 'Mínimo 8 caracteres.'] : v.length < 8 ? [20, '#B91C1C', 'Muy corta: usa al menos 8 caracteres.'] : n <= 1 ? [45, '#B45309', 'Aceptable. Añade mayúsculas, números o símbolos.'] : n <= 3 ? [75, '#1E3A8A', 'Buena contraseña.'] : [100, '#15803D', 'Muy segura.'];
            barra.style.setProperty('--f', niv[0] + '%');
            barra.style.setProperty('--c', niv[1]);
            txt.textContent = niv[2];
        });
    }
    var btn = document.getElementById('uac-login-eye');
    var pwd = document.getElementById('uac-pwd');
    if (!btn || !pwd) return;
    var ojo = window.uacLogin.iconoOjo;
    var ojoOff = window.uacLogin.iconoOjoOff;
    btn.hidden = false;
    btn.addEventListener('click', function () {
        var mostrar = pwd.type === 'password';
        pwd.type = mostrar ? 'text' : 'password';
        btn.innerHTML = mostrar ? ojoOff : ojo;
        btn.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
        btn.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        pwd.focus();
    });
})();
