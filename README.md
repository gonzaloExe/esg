# ESG — Entorno Seguro y Gestión

Versión corregida: 1.1.0.

## Punto importante

Un navegador no permite que PHP obtenga el `gethostname()` de Windows del equipo cliente. Esta versión identifica cada PC por su **IP cliente** (`REMOTE_ADDR`).

La PC que ejecuta `instalar.php` queda como SuperAdmin y su IP se guarda en `includes/config.local.php`.

## Instalación

1. Subí `esg-app` a `/var/www/html/esg`.
2. Asegurá PHP 8+, MySQL y PDO MySQL:
   ```bash
   sudo apt update
   sudo apt install php php-mysql php-fileinfo -y
   ```
3. Dale permisos de escritura:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/esg/uploads
   sudo chmod -R 775 /var/www/html/esg/uploads
   sudo chown www-data:www-data /var/www/html/esg/includes
   sudo chmod 775 /var/www/html/esg/includes
   ```
4. Abrí:
   `http://IP_DEL_SERVIDOR/esg/instalar.php`
5. Indicá host, base, usuario y contraseña MySQL.
6. Ejecutá la instalación.
7. El instalador crea `includes/config.local.php`, crea las tablas y registra la IP instaladora como SuperAdmin.
8. Eliminá `instalar.php`:
   ```bash
   sudo rm /var/www/html/esg/instalar.php
   ```
9. SuperAdmin:
   `http://IP_DEL_SERVIDOR/esg/admin.php`
10. Usuarios:
   `http://IP_DEL_SERVIDOR/esg/index.php`

## Actualización con Git

En la PC:
```bash
git add .
git commit -m "Actualizar ESG"
git push
```

En el servidor:
```bash
cd /var/www/html/esg
sudo git config --global --add safe.directory /var/www/html/esg
sudo git pull
```

No subas `includes/config.local.php`; contiene la contraseña de MySQL.

## Instalación existente

Si ya tenés la base de datos de una versión anterior que registraba el hostname del servidor como SuperAdmin, la base debe migrarse para que el SuperAdmin use la IP de su PC.

Ejemplo:
```sql
UPDATE usuarios
SET pc_identificador='10.24.96.50', rol='superadmin', activo=TRUE
WHERE rol='superadmin'
LIMIT 1;
```

Y `includes/config.local.php` debe contener:
```php
<?php
return [
  'host' => 'localhost',
  'name' => 'esg',
  'user' => 'esg_user',
  'pass' => 'TU_CONTRASEÑA',
  'admin_ip' => '10.24.96.50'
];
```

La IP debe ser la IP de la **PC administradora**, no la del servidor.

## Funciones

Usuario: crea tickets, adjunta JPG/JPEG/PNG de hasta 5 MB, consulta sus tickets y ve el estado de su PC.

SuperAdmin: ve estadísticas, todos los tickets, aprueba, rechaza con motivo, resuelve, elimina, activa/desactiva usuarios y asigna estado de PC.

Estados de ticket: 🟡 Pendiente, 🟢 Aprobado, 🔴 Rechazado, 🔵 Resuelto.

Estados de PC: 🟢 Buena, 🟡 Lenta, 🔴 Fallando.

## Seguridad

PDO/prepared statements, control de rol en cada endpoint, validación de imágenes, nombres aleatorios de archivos, `.htaccess` en uploads y credenciales locales fuera de Git.

## Problemas comunes

`Access denied for user`: verificá usuario/contraseña con:
```bash
mysql -u esg_user -p esg
```

`dubious ownership`:
```bash
git config --global --add safe.directory /var/www/html/esg
```

Todas las PCs entran como SuperAdmin: verificá que `admin_ip` sea la IP de la PC administradora y que el único registro `superadmin` tenga esa misma IP.

IP dinámica: reservá la IP del SuperAdmin en DHCP para que no cambie.
