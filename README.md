# iphix — Guía de Instalación

## 📋 Requisitos
- PHP 8.1+
- MySQL / MariaDB 10.6+
- Apache con mod_rewrite activo
- (Opcional) Composer para autoloading

---

## 🚀 Pasos de instalación

### 1. Subir los archivos
Sube todos los archivos a tu servidor web (p.ej. `/var/www/html/iphix/` o raíz del dominio).

### 2. Importar la base de datos
```bash
mysql -u root -p < database/iphix.sql
```
O desde phpMyAdmin: importa el archivo `database/iphix.sql`.

### 3. Configurar la conexión
Edita `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'iphix');
define('DB_USER', 'tu_usuario_bd');
define('DB_PASS', 'tu_contraseña_bd');
define('APP_URL', 'https://tudominio.com');
define('APP_DEV_MODE', false); // false en producción
```

### 4. Configurar Stripe
En `includes/config.php`:
```php
define('STRIPE_PUBLIC_KEY', 'pk_live_...');
define('STRIPE_SECRET_KEY', 'sk_live_...');
```

### 5. Generar contraseñas reales
Ejecuta este script PHP una sola vez para crear el admin:

```bash
php database/hash_passwords.php
```

O manualmente en tu base de datos:
```sql
UPDATE usuarios SET password = '$2y$12$HASH_GENERADO' WHERE email = 'admin@iphix.es';
```

### 6. Permisos de carpeta de imágenes
```bash
chmod 755 assets/img/productos/
chown www-data:www-data assets/img/productos/
```

### 7. Verificar .htaccess
Asegúrate de que `mod_rewrite` está activo en Apache:
```bash
a2enmod rewrite
systemctl restart apache2
```

---

## 🔑 Acceso inicial

Ejecuta `database/hash_passwords.php` para generar los usuarios de ejemplo con contraseñas cifradas.
Edita ese archivo para establecer los emails y contraseñas que necesites antes de ejecutarlo.

> ⚠️ **Cambia las contraseñas por defecto antes de usar en producción.**

---

## 📁 Estructura del proyecto

```
iphix/
├── index.php              # Homepage
├── .htaccess              # Config Apache
├── includes/
│   ├── config.php         # ⚙️ Configuración (DB, Stripe, etc.)
│   ├── db.php             # Conexión PDO + helpers
│   ├── auth.php           # Auth, sesiones, carrito
│   ├── header.php         # Cabecera HTML pública
│   └── footer.php         # Pie de página público
├── pages/
│   ├── productos.php      # Catálogo con filtros
│   ├── detalle.php        # Detalle de producto
│   ├── login.php          # Login + Registro
│   ├── carrito.php        # Carrito de compra
│   ├── checkout.php       # Proceso de pago (Stripe)
│   ├── perfil.php         # Perfil de usuario
│   ├── buscar.php         # Buscador
│   └── logout.php         # Cierre de sesión
├── admin/
│   ├── index.php          # Dashboard con KPIs y gráficas
│   ├── productos.php      # CRUD productos
│   ├── pedidos.php        # Gestión pedidos
│   ├── piezas.php         # Inventario piezas
│   ├── usuarios.php       # Gestión usuarios
│   ├── contacto.php       # Mensajes de contacto
│   ├── finanzas.php       # Ingresos/gastos
│   ├── includes/
│   │   ├── header.php     # Cabecera admin
│   │   └── footer.php     # Pie admin
│   └── assets/
│       ├── css/admin.css  # Estilos panel admin
│       └── js/admin.js    # JS panel admin
├── api/
│   ├── cart.php           # API carrito (AJAX)
│   ├── payment.php        # API Stripe + pedidos
│   └── search.php         # API búsqueda
├── assets/
│   ├── css/style.css      # Estilos tienda pública
│   ├── js/main.js         # JS tienda pública
│   └── img/productos/     # 📷 Imágenes de productos
└── database/
    ├── iphix.sql          # Schema + datos de ejemplo
    └── hash_passwords.php # Script para generar hashes
```

---

## 🛡️ Seguridad implementada

- **CSRF tokens** en todos los formularios
- **PDO con prepared statements** (prevención SQL Injection)
- **password_hash/verify** con bcrypt (cost=12)
- **Sesiones seguras** (httponly, samesite=strict)
- **XSS prevention** con htmlspecialchars()
- **Cabeceras HTTP** de seguridad en .htaccess
- **Validación** de tipos, emails y datos en servidor
- **Acceso admin** protegido por verificación de rol en cada página

---

## 💳 Integración Stripe

El pago usa **Stripe Elements** (v3) en el frontend.

**Para modo test:** usa la tarjeta `4242 4242 4242 4242` con cualquier fecha futura y CVC.

**Para producción:** reemplaza las claves en `includes/config.php` por las claves `live` de tu cuenta Stripe.

---

## 📞 Soporte

Proyecto desarrollado por **Pedro Lao** — IES Inca Garcilaso
