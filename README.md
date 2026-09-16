# ESG — Entorno Seguro y Gestión

Sistema de gestión de tickets identificado por PC, desarrollado con PHP 8+, PDO, MySQL, Bootstrap 5, JavaScript vanilla, SweetAlert2 y FontAwesome.

## Requisitos
- PHP 8.0 o superior.
- MySQL 5.7+ o MariaDB compatible.
- Apache 2.4+ o Nginx con PHP-FPM.
- Extensiones PHP: PDO, pdo_mysql, fileinfo y GD.
- Permiso de escritura en `uploads/`.
- JavaScript habilitado en el navegador.

## Instalación
1. Suba la carpeta `esg-app` al servidor web.
2. Dé permisos de escritura a `uploads/` (por ejemplo, `chmod 755 uploads`; adapte permisos al usuario del servidor).
3. Cree una base de datos MySQL o permita que el instalador la cree.
4. Abra `https://tudominio.com/esg-app/instalar.php`.
5. Introduzca host, usuario, contraseña y nombre de la base de datos.
6. Pulse **Instalar ESG**. El instalador crea las tablas y registra la PC instaladora como único SuperAdmin.
7. Elimine `instalar.php` inmediatamente después de finalizar.
8. La PC SuperAdmin debe acceder a `admin.php`. Las demás PCs acceden a `index.php`.

## Identificación
No se utilizan correos ni contraseñas. El identificador principal es `gethostname()`. El nombre de usuario intenta obtenerse mediante `get_current_user()`; en entornos donde no sea útil, se utiliza un identificador alternativo basado en el nombre de PC.

**Importante:** `get_current_user()` en PHP normalmente identifica al propietario del script/proceso y no necesariamente al usuario interactivo de Windows. La aplicación no realiza detección de hardware.

## Roles
- **SuperAdmin:** único; corresponde a la PC registrada durante la instalación. Solo usa `admin.php`.
- **Usuario:** se registra automáticamente al primer acceso y solo utiliza `index.php`.

Las rutas verifican el rol y redirigen automáticamente si se intenta abrir el panel equivocado. La API vuelve a comprobar permisos en cada acción.

## Tickets
Los usuarios crean tickets con título, descripción mínima de 10 caracteres y foto opcional. Las fotos admitidas son JPG/JPEG/PNG y el límite es 5 MB.

El SuperAdmin puede aprobar, rechazar con motivo, resolver y eliminar tickets. También puede cambiar manualmente el estado de cada PC: buena, lenta o fallando.

## Base de datos
`database.sql` contiene las tablas `usuarios`, `tickets` y `configuracion`. El instalador ejecuta este archivo automáticamente.

## Verificación
Abra `verificar_db.php` para comprobar conexión, tablas, usuarios y existencia del SuperAdmin.

## Apache / uploads
`uploads/.htaccess` contiene `Require all denied` para evitar acceso HTTP directo a los archivos subidos. En Nginx, configure también una regla equivalente que deniegue acceso directo a `/uploads/`.

## Solución de problemas
### No conecta MySQL
Compruebe host, usuario, contraseña, nombre de BD y que `pdo_mysql` esté instalado.

### No permite subir fotos
Compruebe permisos de `uploads/` y que PHP tenga habilitadas las subidas (`file_uploads`) y un `upload_max_filesize` de al menos 5M.

### Todos aparecen como la misma PC
`gethostname()` identifica el equipo desde el que ejecuta PHP. Si varios usuarios acceden mediante el mismo servidor/proxy, PHP puede ver el hostname del servidor y no el nombre del equipo cliente. Esta limitación es inherente a la identificación por `gethostname()`.

### El instalador dice que ya existe SuperAdmin
La instalación se bloquea deliberadamente. No reinstale encima de una instalación existente sin una copia de seguridad de la base de datos.

### Nginx
Nginx no interpreta `.htaccess`; añada una regla de denegación para `/uploads/` en la configuración del servidor y recargue Nginx.

## Seguridad
- PDO y consultas preparadas.
- Validación de rol en cada endpoint.
- Token CSRF para acciones POST.
- Validación MIME y tamaño de imágenes.
- Archivos de subida sin acceso HTTP directo bajo Apache.
- No se detecta hardware ni se almacenan contraseñas.

## Estructura
```text
esg-app/
├── assets/css/estilo.css
├── assets/js/app.js
├── includes/config.php
├── includes/auth.php
├── uploads/.htaccess
├── uploads/.gitkeep
├── index.php
├── admin.php
├── logout.php
├── api.php
├── instalar.php
├── verificar_db.php
├── database.sql
└── README.md
```
# esg
# esg
# esg
# esg
