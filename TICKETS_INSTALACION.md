# 🚀 GUÍA DE INSTALACIÓN RÁPIDA - SISTEMA DE TICKETS

## ✅ PASO 1: Crear las tablas en la BD

1. Abre **phpMyAdmin**
2. Selecciona tu BD **iphix**
3. Ve a la pestaña **SQL**
4. Copia y pega TODO el contenido del archivo:
   - `database/tickets_schema.sql`
5. Haz clic en **Ejecutar**

Deberías ver 3 tablas nuevas:
- ✅ `tickets`
- ✅ `mensajes_ticket`
- ✅ `respuestas_bot`

---

## ✅ PASO 2: Verificar que los archivos están en su lugar

Asegúrate que estos archivos existen:

```
iphix/
├── includes/
│   └── tickets.php              ✅ (NUEVO)
│
├── pages/
│   └── soporte.php              ✅ (NUEVO)
│
├── admin/
│   └── tickets.php              ✅ (NUEVO)
│
├── api/
│   └── tickets.php              ✅ (NUEVO)
│
├── assets/
│   └── css/
│       └── tickets.css          ✅ (NUEVO)
│
└── database/
    └── tickets_schema.sql       ✅ (NUEVO)
```

---

## ✅ PASO 3: Actualizar header.php

Abre el archivo: `/includes/header.php`

Busca la línea donde se incluye `auth.php`:
```php
require_once __DIR__ . '/auth.php';
```

Justo **después** de esa línea, **AGREGA**:
```php
require_once __DIR__ . '/tickets.php';
```

Quedará algo así:
```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/tickets.php';  // ← NUEVA LÍNEA
```

---

## ✅ PASO 4: (OPCIONAL) Agregar enlace en la navegación

Si quieres que los usuarios vean un botón de "Soporte" en el menú:

Busca donde están los links de navegación (probablemente en `header.php`)

Agrega una línea como esta:
```html
<a href="/pages/soporte.php" class="nav-link">
    <i class="bi bi-ticket-detailed"></i> Centro de Soporte
</a>
```

---

## ✅ PASO 5: (OPCIONAL) Agregar enlace en panel admin

Para que los admins vean los tickets en el menú:

Busca el menú admin (en `admin/includes/header.php` o similar)

Agrega:
```html
<a href="/admin/tickets.php" class="admin-menu-link">
    <i class="bi bi-ticket2"></i> Tickets de Soporte
</a>
```

---

## 🧪 PROBAR QUE FUNCIONA

### 1️⃣ Prueba como USUARIO

1. Accede a http://localhost:8080/pages/soporte.php
2. Deberías ver el "Centro de Soporte"
3. Haz clic en "Nuevo ticket"
4. Crea un ticket de prueba
5. Deberías ver el bot haciendo preguntas

✅ Si ves esto, **¡el sistema para usuarios funciona!**

### 2️⃣ Prueba como ADMIN

1. Accede a http://localhost:8080/admin/tickets.php
2. Deberías ver tu ticket en la lista
3. Haz clic en "Ver"
4. Deberías ver los detalles, mensajes y opciones para responder

✅ Si ves esto, **¡el sistema admin funciona!**

---

## 🎯 ¿QUÉ HACE AHORA?

### Para los USUARIOS:
- ✅ Crear tickets con categoría y prioridad
- ✅ Ver historial de tickets
- ✅ Chatear con los admins
- ✅ El bot hace preguntas automáticas

### Para los ADMINS:
- ✅ Ver todos los tickets
- ✅ Filtrar por estado
- ✅ Asignar a un admin
- ✅ Responder a los usuarios
- ✅ Cambiar estado del ticket
- ✅ Ver estadísticas

---

## 🐛 ¿Algo no funciona?

### Error: "Undefined variable: usuarioActual"
**Solución:** Asegúrate que incluiste `tickets.php` en `header.php`

### Error: "SQLSTATE[42S02]"
**Solución:** Las tablas no existen. Ejecuta el SQL nuevamente

### No veo el Centro de Soporte
**Solución:** 
- Verifica que `/pages/soporte.php` existe
- Abre directamente: `http://localhost:8080/pages/soporte.php`

### Los admins no ven los tickets
**Solución:** 
- Verifica que eres admin: `SELECT * FROM usuarios WHERE id = 1 AND rol = 'admin'`
- Abre: `http://localhost:8080/admin/tickets.php`

---

## 📚 PRÓXIMOS PASOS

Una vez funcionando:

1. **Personaliza el bot** - Cambia las preguntas en `/includes/tickets.php`
2. **Agrega notificaciones por email** - Cuando haya respuestas
3. **Integra en tu menú** - Para que sea fácil acceder
4. **Entrena a tu equipo** - Sobre cómo usar el panel admin

---

## 💡 TIPS

- Cada ticket tiene un código único (ej: TKT-202604261234-ABC123)
- Los tickets críticos se resaltan con colores rojo
- El bot hace diferentes preguntas según la categoría
- Los mensajes del bot tienen un emoji 🤖
- Los admins pueden responder en cualquier momento

---

## 🎉 ¡LISTO!

Tu sistema de tickets profesional está operativo.

**Cualquier duda, revisa el archivo `TICKETS_README.md` para documentación completa.**

---

**Sistema creado con ❤️ para IPHIX**
