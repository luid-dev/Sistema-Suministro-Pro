# Changelog

## 2.1.0
- **Arquitectura separada en `backend/` y `frontend/`:** el archivo único de ~5100 líneas se dividió en una clase principal y 12 módulos (*traits*) por responsabilidad. El CSS, el JavaScript y las plantillas HTML pasaron a archivos propios en `frontend/`.
- El JavaScript se registra con `wp_register_script` y recibe su configuración con `wp_add_inline_script` (rutas de la API, *nonce* e íconos; sin datos sensibles).
- Los estilos del escritorio y de `wp-login.php` se cargan con `wp_enqueue_style` solo en sus pantallas.
- Protección `index.php` en cada carpeta y guardas `ABSPATH` en todos los archivos PHP.
- Pantalla de acceso: la foto ya no cambia de tamaño al pasar a "Quiero ser voluntario", queda fija al hacer *scroll*, el diseño es más compacto y se adapta mejor a celular y tablet.
- El código de estudiante de ejemplo ahora es ficticio (`012345678A`).
- Sin cambios en la base de datos ni en la API REST.

## 2.0.0
- **Base de datos propia:** el sistema ya no depende de Google Sheets ni de Google Apps Script. Los datos se guardan en tablas `{prefijo}uac_*` de la base de datos de WordPress, creadas y actualizadas automáticamente.
- Reglas de negocio (RN-01 a RN-12), cortes, metas, tablero y reportes reimplementados en PHP.
- Importación de carreras y padrón de estudiantes por CSV desde *Ajustes > Sistema-Suministro-Pro*.
- Validación completa antes de guardar y escritura en transacción (si algo falla, no se guarda nada a medias).
- Renombrado a **Sistema-Suministro-Pro** y licencia GPL-2.0-or-later.

## 1.x
- Versión inicial conectada a Google Sheets mediante Apps Script.
