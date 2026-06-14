# iphix

Plataforma web para la gestión y venta de dispositivos electrónicos de segunda mano. Desarrollada como proyecto final del CFGM de Sistemas Microinformáticos y Redes.

## Tecnologías

- PHP 8.3 + PDO
- MariaDB / MySQL
- Apache con mod_rewrite y SSL
- Bootstrap 5 + Chart.js
- Stripe API (pagos)

## Requisitos del servidor

- Ubuntu Server 24.04 LTS (o similar)
- Apache 2.4 con mod_rewrite y mod_ssl
- PHP 8.3 con extensiones: pdo_mysql, mbstring, gd, curl, zip
- MariaDB 10.6 o superior

## Instalación

**1. Importar la base de datos**

```bash
mysql -u root -p < database/iphix.sql
mysql -u root -p nombre_bd < database/tickets_schema.sql
```

**2. Configurar la conexión**

Copia `includes/config.php` y edita los valores:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nombre_bd');
define('DB_USER', 'usuario');
define('DB_PASS', 'contraseña');
```

También añade tus claves de Stripe si vas a usar el módulo de pagos.

**3. Generar contraseñas de los usuarios de ejemplo**

```bash
php database/hash_passwords.php
```

Edita ese archivo antes de ejecutarlo para cambiar los emails y contraseñas por defecto.

**4. Permisos**

```bash
chown -R www-data:www-data /var/www/iphix
chmod -R 755 /var/www/iphix
chmod -R 775 /var/www/iphix/assets/img/productos
```

## Estructura

```
iphix/
├── index.php
├── includes/          # Configuración, BD, auth, cabecera y pie
├── pages/             # Tienda pública (productos, carrito, checkout...)
├── admin/             # Panel de administración
├── api/               # Endpoints AJAX (carrito, pagos, búsqueda)
├── assets/            # CSS, JS e imágenes
└── database/          # SQL del esquema y script de contraseñas
```

## Funcionalidades

**Tienda pública**
- Catálogo con filtros por categoría, precio y estado del dispositivo
- Buscador global
- Carrito de compra y checkout con Stripe
- Registro e inicio de sesión con historial de pedidos
- Sistema de soporte con tickets

**Panel de administración**
- Dashboard con gráficas de ventas (Chart.js)
- Gestión de productos, pedidos y usuarios
- Inventario de piezas con control de entradas y salidas
- Registro de ingresos y gastos
- Gestión de tickets de soporte

## Seguridad

- Contraseñas cifradas con bcrypt (password_hash, cost 12)
- Consultas preparadas con PDO
- Tokens CSRF en todos los formularios
- Sesiones con flags httponly y samesite strict
- HTTPS obligatorio (redirección desde HTTP)
- Firewall UFW (puertos 22, 80 y 443)
