# Sistema-Suministro-Pro

**Sistema de código abierto para organizar campañas de acopio de suministros: registro con lector QR, control de metas por fecha de entrega, tablero público en vivo y reportes.**

![Licencia](https://img.shields.io/badge/licencia-GPL--2.0--or--later-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb3)
![Base de datos](https://img.shields.io/badge/base%20de%20datos-MySQL%20%2F%20MariaDB-orange)
![Versión](https://img.shields.io/badge/versi%C3%B3n-2.1.0-green)

> Una campaña solidaria funciona cuando todos pueden **ver** cómo avanza, **participar** fácilmente y **confiar** en que cada donación quedó bien registrada. Este sistema existe para eso.

---

## Tabla de contenido

1. [¿Qué es y para qué sirve?](#qué-es-y-para-qué-sirve)
2. [Funciones principales](#funciones-principales)
3. [Cómo está hecho el sistema](#cómo-está-hecho-el-sistema)
4. [Tecnologías, lenguajes y librerías](#tecnologías-lenguajes-y-librerías)
5. [Estructura del repositorio](#estructura-del-repositorio)
6. [Instalación paso a paso](#instalación-paso-a-paso)
7. [Probar el sistema (accesos de demostración)](#probar-el-sistema-accesos-de-demostración)
8. [Credenciales y configuración](#credenciales-y-configuración)
9. [Accesos y roles](#accesos-y-roles)
10. [Cómo se usa](#cómo-se-usa)
11. [Reglas de negocio](#reglas-de-negocio)
12. [Base de datos](#base-de-datos)
13. [API REST](#api-rest)
14. [Seguridad y privacidad](#seguridad-y-privacidad)
15. [Personalización](#personalización)
16. [Solución de problemas](#solución-de-problemas)
17. [Cómo contribuir](#cómo-contribuir)
18. [Licencia y créditos](#licencia-y-créditos)

---

## ¿Qué es y para qué sirve?

**Sistema-Suministro-Pro** es un plugin de WordPress que convierte un sitio en una plataforma completa para gestionar una **campaña de acopio de suministros** (por ejemplo: bebidas rehidratantes, chocolate, galletas y atún para una emergencia).

Nació para la campaña de la **Universidad Andina del Cusco** durante una emergencia por incendios forestales, donde las carreras de la universidad compiten sanamente por aportar más. Hoy es **código abierto**: cualquier institución, colegio, ONG, municipalidad o grupo de voluntarios puede instalarlo y usarlo con **su propia base de datos**, sin depender de servicios de terceros.

| Problema | Solución |
|---|---|
| Registrar donaciones en papel es lento y se pierde | Registro digital con lector QR del carné |
| Nadie sabe cuánto falta para la meta | Tablero público con avance por suministro y por fecha de entrega |
| Productos vencidos o dañados se mezclan con los buenos | Validación automática: vigencia mínima de 3 meses y empaque íntegro |
| Falta de transparencia | Cualquiera ve las cifras agregadas, sin datos personales |
| Poca motivación para participar | Ranking de carreras y facultades, podio y actividad diaria |

---

## Funciones principales

- **Lector QR / código de estudiante**: escanea o digita el código (9 dígitos + 1 letra) y obtiene su carrera al instante.
- **Registro de donaciones** con varias líneas de producto; cada línea se evalúa por separado (aceptada o a *Descartes* con su motivo).
- **Cortes y metas editables**: el administrador crea fechas de entrega y define cuántas unidades se necesitan de cada suministro.
- **Tablero público en vivo** (se refresca cada 15 s): avance global, avance por corte, ranking de carreras y facultades, actividad de 7 días y últimas donaciones.
- **Competencia motivadora**: podio "¿Quién va ganando?", facultad líder y búsqueda de carreras.
- **Gestión de voluntarios**: las personas solicitan acceso y el administrador aprueba o rechaza; también puede crearlos directamente.
- **Reportes del equipo**: ranking de voluntarios y motivos de descarte.
- **Importación CSV** del catálogo de carreras y del padrón de estudiantes.
- **Modo demostración**: cifras de ejemplo para presentar el sistema sin tocar datos reales.
- **Seguridad integrada**: bloqueo por intentos fallidos, protección contra bots, cabeceras de seguridad y datos personales nunca expuestos.
- **Accesible y adaptable**: botones grandes, uso con teclado, lectores de pantalla y diseño para celular, tablet y escritorio.

---

## Cómo está hecho el sistema

El sistema es un **plugin de WordPress** dividido en dos capas claramente separadas:

```
          Navegador (frontend)                         Servidor (backend)
 ┌──────────────────────────────────┐        ┌──────────────────────────────────────┐
 │ Vistas HTML  (frontend/views)    │        │ WordPress 6+  ·  PHP 8               │
 │ Estilos CSS  (frontend/css)      │  JSON  │ API REST  /wp-json/uac/v1/...        │
 │ JavaScript   (frontend/js)       │ ─────► │ Reglas de negocio RN-01 … RN-12      │
 │ Lector QR    (html5-qrcode)      │ ◄───── │ Roles, sesiones y nonces de WP       │
 └──────────────────────────────────┘        │ $wpdb  ──►  MySQL / MariaDB          │
                                             │            tablas {prefijo}uac_*     │
                                             └──────────────────────────────────────┘
```

**Backend (`backend/`)**: está escrito en **PHP 8**. La clase principal `UAC_Suministros_Plugin` registra los *hooks* de WordPress y reúne **12 módulos** (*traits*), cada uno con una sola responsabilidad: base de datos, API de donaciones, resumen del tablero, cortes, administración, modo aplicación, acceso, voluntarios, seguridad, modo demostración, interfaz y *shortcodes*. Toda la lógica de negocio y la validación se ejecutan en el servidor. Cada donación se guarda dentro de una **transacción SQL**, de modo que nunca queda nada a medias.

**Frontend (`frontend/`)**: son **plantillas PHP/HTML5**, **CSS3** puro y **JavaScript** sin frameworks. El JavaScript llama a la API REST con `fetch` y pinta los resultados. Recibe del backend solo lo necesario mediante `wp_add_inline_script` (la ruta de la API, un *nonce* de WordPress e íconos). **No recibe ninguna clave ni secreto.**

**Base de datos**: es la misma base **MySQL/MariaDB** de WordPress. El plugin crea sus 7 tablas con `dbDelta` al activarse y las consulta siempre con sentencias preparadas (`$wpdb->prepare`). No usa Google Sheets, Apps Script ni ningún servicio externo.

**Flujo de una donación:** el voluntario escanea el QR, el JavaScript consulta `GET /estudiante/{código}` y luego envía `POST /donaciones`. El backend valida cada línea (RN-01 a RN-10) y guarda en una transacción. Después limpia la caché del resumen, y el tablero público refleja el cambio en su siguiente actualización (cada 15 s).

---

## Tecnologías, lenguajes y librerías

| Capa | Lenguaje / tecnología | Detalle |
|---|---|---|
| **Plataforma** | [WordPress](https://wordpress.org) 6.0+ | Funciona como plugin. Usa sus usuarios, roles, sesiones, *nonces* y la API REST |
| **Backend** | **PHP 8.0+** | Programación orientada a objetos: una clase principal y 12 *traits* por módulo |
| **Base de datos** | **SQL: MySQL 5.7+ / MariaDB 10.3+** | Acceso con `$wpdb` (consultas preparadas), esquema con `dbDelta`, transacciones InnoDB |
| **API** | **WordPress REST API** | Rutas `/wp-json/uac/v1/...` con respuestas **JSON** |
| **Frontend: estructura** | **HTML5** | Plantillas en `frontend/views/` |
| **Frontend: estilos** | **CSS3** | Variables CSS, Grid, Flexbox, *container queries* y *media queries* (diseño adaptable) |
| **Frontend: lógica** | **JavaScript (ES2017+, vanilla)** | Sin frameworks ni proceso de *build*. Usa `fetch`, `async/await` y DOM |
| **Lector QR** | [html5-qrcode](https://github.com/mebjas/html5-qrcode) 2.3.8 (licencia Apache-2.0) | Se carga desde el CDN unpkg con la versión fija |
| **Íconos** | SVG en línea | Trazo estilo [Lucide](https://lucide.dev) (licencia ISC), sin peticiones externas |
| **Tipografía** | Fuentes del sistema | `Inter` si está instalada; si no, la del sistema operativo. No se descargan fuentes |
| **Entorno de desarrollo** | [LocalWP](https://localwp.com) | nginx + PHP 8.2 + MySQL en local |
| **Licencia** | GPL-2.0-or-later | La misma de WordPress |

No necesita Node.js, Composer ni un proceso de compilación: **se copia la carpeta del plugin y se activa**.

---

## Estructura del repositorio

```
Sistema-Suministro-Pro/
├── README.md
├── LICENSE                         # GPL v2 o posterior
├── CHANGELOG.md
├── SECURITY.md                     # Cómo reportar vulnerabilidades
├── .gitignore  .gitattributes  .editorconfig
└── sistema-suministro-pro/         # ← el plugin de WordPress (esta carpeta va en wp-content/plugins/)
    ├── sistema-suministro-pro.php  # Punto de entrada: cabecera del plugin, constantes y carga de módulos
    │
    ├── backend/                    # PHP: lógica de negocio, API REST y base de datos
    │   ├── class-uac-suministros-plugin.php   # Clase principal: hooks, roles y permisos
    │   └── traits/
    │       ├── trait-uac-base-datos.php       # Tablas uac_*, dbDelta, utilidades de fecha y caché
    │       ├── trait-uac-api-donaciones.php   # GET /estudiante, POST /donaciones (RN-01…RN-10)
    │       ├── trait-uac-resumen.php          # GET /resumen del tablero (RN-11, RN-12)
    │       ├── trait-uac-cortes.php           # Cortes y metas (/cortes, solo administrador)
    │       ├── trait-uac-admin.php            # Ajustes en el escritorio e importación CSV
    │       ├── trait-uac-modo-app.php         # Páginas del sistema, rutas y plantilla propia
    │       ├── trait-uac-acceso.php           # Pantalla de acceso y ajustes de wp-login.php
    │       ├── trait-uac-voluntarios.php      # Solicitudes de acceso y gestión de voluntarios
    │       ├── trait-uac-seguridad.php        # Límite de intentos, cabeceras HTTP, IP del cliente
    │       ├── trait-uac-demo.php             # Modo demostración y GET /reporte
    │       ├── trait-uac-interfaz.php         # Registro de scripts, carga de CSS/vistas, íconos SVG
    │       └── trait-uac-shortcodes.php       # [uac_registro], [uac_dashboard], [uac_cortes]
    │
    └── frontend/                   # Lo que ve y ejecuta el navegador
        ├── views/                  # Plantillas HTML (PHP solo para imprimir datos escapados)
        │   ├── app-shell.php       # Barra superior, navegación y pie
        │   ├── login.php           # Ingresar / Quiero ser voluntario
        │   ├── admin/ajustes.php
        │   └── registro/  cortes/  tablero/  voluntarios/
        ├── css/                    # Una hoja por vista o componente
        │   ├── base.css  app-shell.css  componentes.css  responsive.css
        │   ├── login.css  registro.css  cortes.css  tablero.css  voluntarios.css
        │   └── admin.css  wp-login.css  ocultar-tema.css
        ├── js/                     # JavaScript sin dependencias
        │   └── login.js  registro.js  cortes.js  tablero.js
        └── img/
            └── login-incendio.webp
```

Cada carpeta incluye un `index.php` vacío ("Silence is golden"). Además, todos los archivos PHP terminan con `exit` si se abren directamente (`defined( 'ABSPATH' ) || exit;`).

---

## Instalación paso a paso

### Requisitos
- WordPress 6.0 o superior
- PHP 8.0 o superior
- MySQL 5.7+ / MariaDB 10.3+ (el mismo que ya usa WordPress)
- HTTPS recomendado (la cámara del lector QR lo exige en producción)

### ¿No tienes WordPress? Guía desde cero con LocalWP (recomendado para probar)

[LocalWP](https://localwp.com) es gratis, funciona en Windows, macOS y Linux, y ya trae todo lo necesario (servidor web, PHP, MySQL y WP-CLI). No tienes que instalar nada por separado.

1. Descarga e instala **LocalWP** desde <https://localwp.com>.
2. Abre Local → **+ (Create a new site)** → escribe un nombre (por ejemplo `suministros`) → **Continue**.
3. Elige **Preferred** (o en *Custom*: PHP 8.0 o superior y MySQL 8) → **Continue**.
4. Crea el usuario administrador de WordPress (usuario, contraseña y correo). **Guárdalos: con ellos entrarás.** Luego pulsa **Add site**.
5. En la ficha del sitio, junto a **SSL**, pulsa **Trust**. Así el sitio abre con `https://`, que la cámara del lector QR necesita.
6. Pulsa **Open site folder** y copia la carpeta `sistema-suministro-pro/` de este repositorio dentro de `app/public/wp-content/plugins/`.
7. Pulsa **WP Admin**, entra con tu usuario y ve a **Plugins → Sistema-Suministro-Pro → Activar**.
8. Ve a **Ajustes → Enlaces permanentes**, elige **Nombre de la entrada** y pulsa **Guardar cambios** (así funcionan direcciones como `/registro-de-acopio-uac/`).
9. Pulsa **Open site**. ¡Listo! Ya ves el tablero.

> **Consejo:** Para crear los usuarios de demostración, en Local haz clic derecho en el sitio → **Open site shell**. Esa terminal ya trae WP-CLI y puedes pegar ahí los comandos de [Probar el sistema](#probar-el-sistema-accesos-de-demostración).

### ¿Ya tienes WordPress (hosting o servidor propio)?

### Opción A: subir el ZIP
1. Comprime la carpeta `sistema-suministro-pro/` (o descarga el ZIP desde **Releases**).
2. En WordPress ve a **Plugins → Añadir nuevo → Subir plugin** y elige el ZIP.
3. Pulsa **Activar**.

### Opción B: copiar la carpeta
```bash
git clone https://github.com/TU-USUARIO/Sistema-Suministro-Pro.git
cp -R Sistema-Suministro-Pro/sistema-suministro-pro /ruta/a/wordpress/wp-content/plugins/
```
Luego activa el plugin desde **Plugins** en el escritorio de WordPress.

### Qué ocurre al activarlo
- Se crean automáticamente las **7 tablas** en *tu* base de datos.
- Se carga un **catálogo inicial de carreras** (editable o reemplazable por CSV).
- Se crean las **páginas del sistema** (tablero, registro, cortes y voluntarios).
- Se crea el rol **Registrador (Punto de Acopio)**.
- El sitio público pasa a comportarse como la aplicación (la portada muestra el tablero o el registro).

### Ajuste obligatorio después de activar
Ve a **Ajustes → Enlaces permanentes**, elige **Nombre de la entrada** y guarda. Las direcciones del sistema (`/registro-de-acopio-uac/`, `/tablero-de-donaciones-uac/`, …) dependen de esto.

### Primeros pasos recomendados
1. Entra a **Ajustes → Sistema-Suministro-Pro**.
2. Importa tus **carreras** (`carrera_id, nombre, facultad`) y luego tu **padrón** (`codigo, carrera_id`) en CSV.
3. Crea el **primer corte** en la pantalla *Cortes y metas*.
4. **Desactiva el modo demostración** cuando empiece la campaña real.

---

## Probar el sistema (accesos de demostración)

Para probar todas las pantallas, crea estos **usuarios de demostración** en tu instalación de prueba. Son los mismos para todos, así puedes seguir la guía tal cual:

| Rol | Usuario | Contraseña | Qué puedes probar |
|---|---|---|---|
| **Administrador** | `admin_demo` | `AdminDemo2026!` | Todo: cortes y metas, aprobar voluntarios, ajustes, importación CSV y modo demostración |
| **Voluntario (Registrador)** | `voluntario_demo` | `VoluntarioDemo2026!` | Registrar donaciones con QR o código, y ver los reportes del equipo |
| **Visitante** | *(sin cuenta)* | — | Ver el tablero público |

**Crearlos con WP-CLI** (en la carpeta de tu WordPress, después de activar el plugin):

```bash
wp user create admin_demo admin_demo@example.com --role=administrator --user_pass='AdminDemo2026!'
wp user create voluntario_demo voluntario_demo@example.com --role=uac_registrador --user_pass='VoluntarioDemo2026!'
wp option update uac_suministros_modo_demo 1   # opcional: tablero con datos de ejemplo
```

**Sin WP-CLI:** entra con el administrador que creaste al instalar WordPress. Ve a **Usuarios → Añadir nuevo** y crea `admin_demo` (perfil *Administrador*) y `voluntario_demo` (perfil *Registrador (Punto de Acopio)*) con esas contraseñas.

**Recorrido sugerido:**
1. Entra a `/registro-de-acopio-uac/` como **admin_demo** y crea un corte en **Cortes y metas** con una fecha futura.
2. Cierra sesión y entra como **voluntario_demo**. Registra una donación con el código `012345678A`.
3. Abre `/tablero-de-donaciones-uac/` sin sesión y mira cómo se actualiza el avance.
4. Prueba también *Quiero ser voluntario* y aprueba la solicitud desde **Voluntarios** como admin_demo.

> **Importante:** Estos accesos son **solo para pruebas locales**. Son públicos porque están en este README: **nunca los crees en un sitio en producción**, o bórralos antes de la campaña real (`wp user delete admin_demo voluntario_demo --reassign=1`).

---

## Credenciales y configuración

> **Importante:** **Este repositorio no contiene ninguna contraseña real, clave de API, token ni URL de Apps Script** (solo los accesos de demostración de la sección anterior, que cada quien crea en su propia instalación de prueba). Es público, y cualquier credencial que se suba a GitHub debe considerarse comprometida, aunque luego se borre. Cada instalación crea sus propias credenciales.

El sistema **no necesita claves de API**: no se conecta a Google, a hojas de cálculo ni a otros servicios. Solo usa estas credenciales, y todas se configuran **fuera del repositorio**:

| Credencial | Dónde se configura | Quién la define |
|---|---|---|
| **Base de datos** (`DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`) | `wp-config.php` de tu WordPress (no forma parte de este repositorio) | Tu hosting o tu entorno local |
| **Claves y *salts* de WordPress** (`AUTH_KEY`, `SECURE_AUTH_KEY`, …) | `wp-config.php`. Genéralas en <https://api.wordpress.org/secret-key/1.1/salt/> | Tú, al instalar WordPress |
| **Usuario administrador** | Se crea al instalar WordPress | Tú |
| **Usuarios voluntarios (Registradores)** | Vista **Voluntarios** del sistema, o solicitud *Quiero ser voluntario* | El administrador los aprueba o crea |
| **Correo de avisos** | *Ajustes → Generales → Correo electrónico de administración* | Tú |

Ejemplo de la sección de base de datos de `wp-config.php` (solo valores de ejemplo, **cámbialos**):

```php
define( 'DB_NAME',     'nombre_de_tu_base' );
define( 'DB_USER',     'usuario_de_tu_base' );
define( 'DB_PASSWORD', 'una-contraseña-segura' );
define( 'DB_HOST',     'localhost' );
```

**Buenas prácticas:**
- Usa contraseñas de al menos 12 caracteres, distintas para la base de datos y para cada usuario.
- Comparte las credenciales de los voluntarios por un canal privado, nunca en el repositorio, *issues* ni capturas.
- El `.gitignore` ya excluye `wp-config.php`, `.env`, volcados `.sql`, registros y copias de seguridad.

---

## Accesos y roles

| Rol | Cómo se obtiene | Qué puede hacer |
|---|---|---|
| **Visitante** | No requiere cuenta | Ver el tablero público (solo cifras agregadas) |
| **Voluntario pendiente** | Se registra con "Quiero ser voluntario" | Nada hasta que lo aprueben |
| **Registrador (Punto de Acopio)** | Lo aprueba o lo crea el administrador | Registrar donaciones, consultar estudiantes, ver reportes del equipo |
| **Administrador** | Es el administrador de WordPress | Todo lo anterior + cortes y metas, gestión de voluntarios, ajustes e importación CSV |

### Direcciones del sistema

| Dirección | Qué muestra |
|---|---|
| `/` | Registro (con sesión de registrador o administrador) o tablero (público) |
| `/tablero-de-donaciones-uac/` | Tablero en vivo |
| `/registro-de-acopio-uac/` | Registro de donaciones (pide iniciar sesión) |
| `/cortes-y-metas-uac/` | Crear o quitar cortes (solo administrador) |
| `/voluntarios-uac/` | Aprobar, rechazar, agregar o quitar voluntarios (solo administrador) |
| `/wp-admin/options-general.php?page=sistema-suministro-pro-config` | Ajustes, estado de la base de datos e importación CSV |
| `/wp-login.php` | Redirige al acceso del sistema |

### Shortcodes
`[uac_dashboard]`, `[uac_registro]`, `[uac_cortes]` y `[uac_voluntarios]`: pégalos en cualquier página si prefieres armar tu propia estructura.

### Cómo dar acceso a un voluntario
1. **Autoservicio:** la persona entra a la pantalla de acceso → *Quiero ser voluntario* → completa el formulario. El administrador recibe un correo y lo aprueba en **Voluntarios**.
2. **Directo:** el administrador usa *Agregar voluntario* en la vista **Voluntarios** (usuario, correo y contraseña de al menos 8 caracteres).

---

## Cómo se usa

### Para el voluntario (punto de acopio)
1. Inicia sesión.
2. Escanea el QR del carné o escribe el código del estudiante.
3. Si es la primera vez, elige su carrera (queda guardada).
4. Agrega una línea por producto: tipo, cantidad, fecha de vencimiento y si el empaque está bien.
5. Guarda: el sistema muestra qué se **aceptó** y qué se **descartó** (y por qué), con el número de donación.

### Para el administrador
1. Crea los **cortes** (fecha de entrega + meta por suministro). Un corte creado no se edita; solo se quita si no tiene donaciones.
2. Aprueba voluntarios.
3. Consulta el tablero y los reportes del equipo.

### Para el público
Entra al tablero, mira el avance, la carrera líder y cuánto falta para la meta.

---

## Reglas de negocio

| Regla | Descripción |
|---|---|
| **RN-01** | El código de estudiante tiene exactamente 9 dígitos y 1 letra (ej. `012345678A`) |
| **RN-03** | Si el estudiante no está en el padrón, se registra con la carrera elegida |
| **RN-04** | Solo se aceptan los suministros oficiales: Bebida rehidratante, Chocolate, Galletas soda, Atún |
| **RN-05** | La cantidad debe ser un entero mayor o igual a 1 |
| **RN-06** | Vigencia mínima: **3 meses** desde el día del ingreso (con ajuste de fin de mes) |
| **RN-08** | Si el empaque tiene fallas físicas, la línea va a *Descartes* con su motivo |
| **RN-09** | Toda donación debe tener al menos un producto |
| **RN-10** | Cada donación se asigna al primer corte con fecha de entrega igual o posterior a hoy; sin corte abierto no se registra |
| **RN-11/12** | El tablero solo suma líneas aceptadas y nunca expone códigos ni nombres |

Todo se valida **antes** de guardar y se escribe en una **transacción**: si algo falla, no queda nada a medias.

---

## Base de datos

El sistema usa **la base de datos de tu propio WordPress** (lenguaje **SQL**, motor **MySQL o MariaDB**). No hay servicios externos, claves de terceros ni hojas de cálculo. Quien instale el plugin trabaja solo con sus datos.

| Tabla | Contenido |
|---|---|
| `{prefijo}uac_carreras` | Catálogo de carreras y facultades |
| `{prefijo}uac_estudiantes` | Padrón: código → carrera |
| `{prefijo}uac_cortes` | Fechas de entrega |
| `{prefijo}uac_metas` | Meta por corte y suministro |
| `{prefijo}uac_donaciones` | Cabecera de cada donación (quién, cuándo, corte, voluntario) |
| `{prefijo}uac_detalle` | Líneas aceptadas |
| `{prefijo}uac_descartes` | Líneas rechazadas con motivo |

Las tablas se crean y actualizan solas (`dbDelta`). **Recomendación:** haz copias de seguridad periódicas de la base de datos de WordPress.

### Importar datos desde una hoja de cálculo
Exporta cada hoja como **CSV** (UTF-8; coma o punto y coma) e impórtala en *Ajustes → Sistema-Suministro-Pro*:

```csv
# carreras.csv
carrera_id,nombre,facultad
MEDH,Medicina Humana,Facultad de Ciencias de la Salud

# estudiantes.csv
codigo,carrera_id
012345678A,MEDH
```
Importa **primero las carreras** y después el padrón. Los registros existentes se actualizan.

---

## API REST

Base: `/wp-json/uac/v1`

| Método | Ruta | Acceso | Descripción |
|---|---|---|---|
| GET | `/resumen` | Público | Cifras agregadas del tablero (caché de 10 s) |
| GET | `/estudiante/{codigo}` | Registrador/Admin | Carrera del estudiante o catálogo si es nuevo |
| POST | `/donaciones` | Registrador/Admin | Registra una donación con sus líneas |
| GET | `/reporte` | Registrador/Admin | Ranking de voluntarios y motivos de descarte |
| GET / POST | `/cortes` | Administrador | Lista / crea cortes |
| DELETE | `/cortes/{n}` | Administrador | Quita un corte sin donaciones |

Las rutas protegidas exigen una sesión de WordPress y el *nonce* `X-WP-Nonce`. Ejemplo de donación:

```json
POST /wp-json/uac/v1/donaciones
{
  "codigo": "012345678A",
  "carrera_id": "ICIV",
  "items": [
    { "tipo": "Atún", "cantidad": 2, "fecha_vencimiento": "2030-01-01", "empaque_ok": true }
  ]
}
```

---

## Seguridad y privacidad

- **Sin secretos en el código:** no hay claves de API, tokens ni URLs privadas. La configuración que llega al navegador solo incluye rutas públicas y *nonces* de WordPress.
- Bloqueo temporal tras **8 intentos fallidos** de ingreso en 15 minutos por IP.
- Solicitudes de acceso con *honeypot* y límite de **5 por hora por IP**.
- *Nonces* de WordPress en todos los formularios y en la API autenticada; permisos verificados en cada ruta (`permission_callback`).
- Consultas preparadas (`$wpdb->prepare`) en toda la base de datos y salida escapada (`esc_html`, `esc_attr`, `esc_url`).
- Cabeceras `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` y `Permissions-Policy`.
- Los archivos PHP no se pueden ejecutar abriéndolos directamente.
- El tablero público **no muestra** códigos de estudiante ni nombres de voluntarios, pensado para cumplir con la protección de datos personales (p. ej. Ley N.° 29733 de Perú; adáptalo a la normativa de tu país).
- Usa **HTTPS** en producción y mantén WordPress y PHP actualizados.

¿Encontraste una vulnerabilidad? Lee [`SECURITY.md`](SECURITY.md) y repórtala de forma privada.

---

## Personalización

- **Suministros:** se definen en el método `suministros()` de `backend/traits/trait-uac-base-datos.php`.
- **Estilos:** edita las hojas de `frontend/css/`; cada vista tiene la suya.
- **Textos y plantillas:** están en `frontend/views/`.
- **Zona horaria:** por defecto `America/Lima`; cámbiala con el filtro `uac_suministros_zona_horaria`.
- **Imagen de acceso:** reemplaza `frontend/img/login-incendio.webp` por una de tu organización (*usa imágenes cuyos derechos tengas*).
- **Modo aplicación:** si no quieres que el plugin tome la portada del sitio: `add_filter( 'uac_suministros_modo_app', '__return_false' );`
- **Ocultar el tema en páginas con shortcodes:** `add_filter( 'uac_suministros_ocultar_tema', '__return_true' );`
- **Catálogo de carreras:** impórtalo por CSV. También sirve para departamentos, grados, barrios o equipos.

---

## Solución de problemas

| Problema | Solución |
|---|---|
| Las direcciones como `/registro-de-acopio-uac/` dan error 404 | **Ajustes → Enlaces permanentes** → *Nombre de la entrada* → Guardar |
| El botón **Escanear QR** no abre la cámara | El sitio debe abrirse con `https://` (en LocalWP: **SSL → Trust**) y el navegador debe tener permiso de cámara. También puedes escribir el código a mano |
| No se puede registrar: "no hay corte abierto" | Como administrador, crea un corte en **Cortes y metas** con una fecha de entrega futura |
| La imagen de la pantalla de acceso no se ve | Tu servidor no reconoce el formato WebP. En Apache, el `.htaccess` incluido lo corrige. En nginx, agrega `image/webp webp;` a los tipos MIME |
| Un voluntario no puede entrar | Su solicitud debe estar **aprobada** en la vista **Voluntarios** |
| "Demasiados intentos fallidos" | Es la protección contra ataques: espera 15 minutos |
| No llegan los correos de aviso | WordPress necesita poder enviar correo. En LocalWP revisa **Mailpit**; en un hosting, usa un plugin SMTP |
| El tablero muestra cifras que no son reales | Desactiva el **Modo demostración** en *Ajustes → Sistema-Suministro-Pro* |

---

## Cómo contribuir

1. Haz un *fork* del repositorio.
2. Crea una rama: `git checkout -b mejora/mi-aporte`.
3. Haz tus cambios y pruébalos en un WordPress local (por ejemplo con [LocalWP](https://localwp.com), `wp-env` o Docker).
4. Verifica la sintaxis:
   ```bash
   find sistema-suministro-pro -name "*.php" -exec php -l {} \;
   for f in sistema-suministro-pro/frontend/js/*.js; do node --check "$f"; done
   ```
5. Respeta la separación: lógica y datos en `backend/`; HTML, CSS y JS en `frontend/`.
6. Haz *commit* y envía tu *pull request* explicando qué problema resuelve.

**Ideas bienvenidas:** pruebas automatizadas (PHPUnit), exportación a Excel/PDF, traducciones (`i18n`), más tipos de suministro configurables desde el panel, notificaciones y copias de seguridad automáticas.

---

## Licencia y créditos

**Autor:** Luidman Zanes ([@Luidman132](https://github.com/Luidman132)).

Distribuido bajo **GNU GPL v2 o posterior** (ver [`LICENSE`](LICENSE)), la misma licencia de WordPress.

- Lector QR: [html5-qrcode](https://github.com/mebjas/html5-qrcode) de Minhaz (Apache-2.0).
- Estilo de íconos: [Lucide](https://lucide.dev) (ISC).

Hecho para que ayudar sea más fácil. El proyecto nació en la campaña de acopio de la Universidad Andina del Cusco.

 Si te sirvió, **dale una estrella al repositorio**: ayuda a que más personas lo encuentren.
