# 🎫 SISTEMA DE TICKETS DE SOPORTE - DOCUMENTACIÓN

## 📋 Descripción

Sistema profesional y completo de tickets de soporte para tu plataforma IPHIX que incluye:

✅ **Para usuarios:**
- Crear tickets de soporte con categorías (compra, técnico, envío, factura, otro)
- Bot conversacional que hace preguntas automáticas
- Chat en tiempo real con los administradores
- Historial completo de tickets
- Estados claros de avance (abierto, en proceso, resuelto, cerrado)

✅ **Para administradores:**
- Panel completo de gestión de tickets
- Asignar tickets a diferentes admins
- Responder a los usuarios
- Cambiar estados de tickets
- Filtrar y buscar tickets
- Estadísticas de tickets
- Prioridades (baja, normal, alta, crítica)

---

## 🚀 INSTALACIÓN RÁPIDA

### 1️⃣ Crear las tablas en la BD

Ejecuta el SQL en tu phpmyadmin:

```bash
cd /ruta/a/proyecto/database
# Abre tickets_schema.sql en tu editor
# O copia el contenido en phpmyadmin
```

O desde MySQL:
```bash
mysql -u usuario -p nombre_bd < tickets_schema.sql
```

### 2️⃣ Incluir los archivos necesarios

Ya están creados en:
- `/includes/tickets.php` → Funciones del sistema (YA INCLUIDO)
- `/pages/soporte.php` → Página de usuario
- `/admin/tickets.php` → Panel admin
- `/api/tickets.php` → API para AJAX
- `/assets/css/tickets.css` → Estilos (opcional, está en inline en los archivos)

### 3️⃣ Actualizar header.php

Agrega esta línea en `/includes/header.php` después de requerir otros includes:

```php
<?php
// ... otros includes ...
require_once __DIR__ . '/tickets.php';
?>
```

### 4️⃣ (Opcional) Agregar link en menú

En tu navegación principal, agrega:
```html
<a href="/pages/soporte.php" class="nav-link">
    <i class="bi bi-ticket-detailed"></i> Soporte
</a>
```

---

## 📖 USO DEL SISTEMA

### 👥 PARA USUARIOS

#### Crear un Ticket
1. Accede a `/pages/soporte.php`
2. Haz clic en "Nuevo ticket"
3. Selecciona categoría y prioridad
4. Escribe asunto y descripción detallada
5. El bot automáticamente hará preguntas de diagnóstico
6. Responde las preguntas del bot
7. Los admins verán tu ticket en el panel

#### Responder a los admins
1. Ve a tu ticket
2. Verás los mensajes del bot y de los admins
3. Escribe tu respuesta en el formulario inferior
4. El bot hará más preguntas si es necesario

#### Estados de ticket
- 🔵 **Abierto**: Tu ticket fue creado
- 🟠 **En proceso**: Un admin lo está revisando
- 🟢 **Resuelto**: Se encontró solución
- ⚫ **Cerrado**: Ticket terminado

---

### 🛠️ PARA ADMINISTRADORES

#### Acceder al panel
1. Ve a `/admin/tickets.php`
2. Verás todos los tickets con estadísticas

#### Acciones disponibles

**Ver tickets:**
- Filtra por estado (Abierto, En proceso, Resuelto, Cerrado)
- Busca por código, asunto o email del cliente
- Haz clic en "Ver" para abrir detalles

**Asignar ticket:**
1. Abre el ticket
2. En "Acciones" → Selecciona tu nombre
3. Haz clic en "Asignar"
4. El ticket cambiará a "En proceso" automáticamente

**Responder:**
1. Escribe tu respuesta en el área inferior
2. Haz clic en "Enviar respuesta"
3. El cliente recibe notificación (si tiene notificaciones activas)

**Cambiar estado:**
1. En "Acciones" → Selecciona nuevo estado
2. Estados disponibles:
   - Abierto
   - En proceso
   - Resuelto
   - Cerrado

---

## 🤖 FUNCIONAMIENTO DEL BOT

El bot es **automático y inteligente**. Hace preguntas diferentes según el tipo de ticket:

### 📦 Categoría: COMPRA
1. ¿Cuál es tu número de pedido?
2. ¿Cuál es el problema específico?
3. ¿Ya intentaste resolver el problema?

### 🔧 Categoría: TÉCNICO
1. ¿Qué dispositivo tienes?
2. ¿Cuál es exactamente el problema?
3. ¿Qué pasos seguiste?
4. ¿Qué sistema operativo tienes?

### 🚚 Categoría: ENVÍO
1. ¿Cuál es tu número de pedido?
2. ¿Cuál es el problema con el envío?
3. ¿Ya contactaste con logística?

### 📄 Categoría: FACTURA
1. ¿Cuál es tu número de pedido?
2. ¿Qué problema tienes con tu factura?
3. ¿Necesitas rectificativa o duplicado?

### ❓ Categoría: OTRO
1. ¿Cuál es el tema?
2. ¿Puedes describirlo más?
3. ¿Hay algo más que debamos saber?

---

## 🔧 FUNCIONES DISPONIBLES

### En `/includes/tickets.php`

```php
// Crear ticket
crearTicket($usuarioId, $asunto, $descripcion, $categoria, $prioridad)

// Obtener tickets del usuario
obtenerTicketsUsuario($usuarioId, $estado = null, $pagina = 1, $porPagina = 10)

// Obtener un ticket específico
obtenerTicket($ticketId)

// Agregar mensaje
agregarMensajeTicket($ticketId, $usuarioId, $mensaje, $esAdmin = false, $archivoUrl = null, $esBot = false)

// Cambiar estado
cambiarEstadoTicket($ticketId, $nuevoEstado, $adminId = null)

// Asignar a admin
asignarTicket($ticketId, $adminId)

// Para admins
obtenerTicketsAdmin($estado = null, $filtro = null, $pagina = 1, $porPagina = 15)

// Estadísticas
obtenerEstadisticasTickets()

// Bot
obtenerSiguientePreguntaBot($ticketId)
completarPreguntaBot($ticketId, $paso, $respuesta)
obtenerRespuestasBot($ticketId)
```

---

## 🗄️ ESTRUCTURA DE BD

### Tabla: tickets
```sql
- id (INT)
- codigo (VARCHAR) - Código único como TKT-202604261234-ABC123
- usuario_id (INT FK)
- asunto (VARCHAR)
- descripcion (TEXT)
- categoria (ENUM: compra, tecnico, envio, factura, otro)
- prioridad (ENUM: baja, normal, alta, critica)
- estado (ENUM: abierto, en_proceso, resuelto, reabierto, cerrado)
- asignado_a (INT FK - id del admin)
- cerrado_por (INT FK - id del admin que cerró)
- created_at, updated_at, resuelto_at (DATETIME)
```

### Tabla: mensajes_ticket
```sql
- id (INT)
- ticket_id (INT FK)
- usuario_id (INT FK)
- es_admin (TINYINT - 0=usuario, 1=admin)
- mensaje (TEXT)
- es_bot (TINYINT - 1 si el mensaje es del bot)
- leido (TINYINT)
- created_at (DATETIME)
```

### Tabla: respuestas_bot
```sql
- id (INT)
- ticket_id (INT FK)
- paso (INT - número de pregunta)
- pregunta (VARCHAR)
- respuesta_usuario (TEXT - la respuesta del usuario)
- completado (TINYINT - 1 si respondió)
- created_at (DATETIME)
```

---

## 🎨 PERSONALIZACIÓN

### Cambiar colores
En `/assets/css/tickets.css`, modifica las variables:

```css
:root {
    --color-primary: #0066cc;
    --color-primary-dark: #0052a3;
    /* ... */
}
```

### Agregar más categorías
En `/includes/tickets.php`, función `obtenerPreguntasBot()`:

```php
'nueva_categoria' => [
    "Primera pregunta?",
    "Segunda pregunta?",
    "Tercera pregunta?"
]
```

### Cambiar preguntas del bot
Edita las preguntas en `obtenerPreguntasBot()` según tus necesidades.

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Los tickets no se crean
- ✅ Verifica que las tablas estén creadas en la BD
- ✅ Asegúrate de que `require_once __DIR__ . '/tickets.php'` está en header.php

### El bot no hace preguntas
- ✅ La función `crearTicket()` debe llamar a `iniciarBotTicket()`
- ✅ Verifica que los mensajes se insertan en `mensajes_ticket`

### Los admins no ven los tickets
- ✅ Verifica que es admin: `SELECT * FROM usuarios WHERE id = ? AND rol = 'admin'`
- ✅ La página es `/admin/tickets.php`

### No se guardan respuestas
- ✅ Verifica CSRF token: `<?= campoCSRF() ?>`
- ✅ Verifica que el campo `ticket_id` se envía correctamente

---

## 📊 ESTADÍSTICAS

En el panel admin, verás automáticamente:
- ✅ Total de tickets por estado
- ✅ Tickets críticos resaltados
- ✅ Tickets sin asignar
- ✅ Últimas actualizaciones

---

## 🔐 SEGURIDAD

- ✅ Todas las consultas usan prepared statements (PDO)
- ✅ Validación de CSRF en todos los formularios
- ✅ Solo usuarios logueados pueden crear tickets
- ✅ Los usuarios solo ven sus propios tickets
- ✅ Los admins solo pueden ver el panel admin

---

## 📱 RESPONSIVE

El sistema es completamente responsive:
- ✅ Funciona en mobile
- ✅ Diseño adaptable
- ✅ Touch-friendly

---

## 🚀 PRÓXIMAS MEJORAS (Opcional)

Puedes agregar:
- Notificaciones por email cuando hay respuestas
- Archivos adjuntos en los mensajes
- Calificación del ticket después de cerrar
- Exportar tickets a PDF
- API pública para integraciones
- Webhooks para sincronizar con otros sistemas
- Chat en vivo (WebSocket)

---

## 📞 SOPORTE

Para más información o problemas, revisa:
1. El código en los archivos (está bien comentado)
2. Las funciones en `/includes/tickets.php`
3. El SQL en `/database/tickets_schema.sql`

---

**¡Sistema de tickets profesional listo para usar! 🎉**

Hecho con ❤️ para IPHIX
