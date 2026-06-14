<?php
$pageTitle   = 'Gestión de Tickets';
$currentPage = 'tickets';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tickets.php';


$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $action = $_POST['_action'] ?? '';

        if ($action === 'asignar') {
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $adminId  = (int)($_POST['admin_id'] ?? 0);
            if (asignarTicket($ticketId, $adminId)) {
                $success = 'Ticket asignado correctamente.';
            } else {
                $errors[] = 'Error al asignar el ticket.';
            }
        } elseif ($action === 'cambiar_estado') {
            $ticketId    = (int)($_POST['ticket_id'] ?? 0);
            $nuevoEstado = $_POST['nuevo_estado'] ?? '';
            if (cambiarEstadoTicket($ticketId, $nuevoEstado, usuarioActual()['id'])) {
                $success = 'Estado actualizado.';
            } else {
                $errors[] = 'Error al cambiar el estado.';
            }
        } elseif ($action === 'responder') {
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $mensaje  = trim($_POST['mensaje'] ?? '');
            if (!$mensaje) {
                $errors[] = 'El mensaje no puede estar vacío.';
            } else {
                agregarMensajeTicket($ticketId, usuarioActual()['id'], $mensaje, true);
                $success = 'Respuesta enviada.';
            }
        }
    }
}


$estado       = $_GET['estado'] ?? 'todos';
$filtro       = $_GET['q'] ?? '';
$pagina       = max(1, (int)($_GET['p'] ?? 1));

$resultado    = obtenerTicketsAdmin($estado === 'todos' ? null : $estado, $filtro, $pagina, 15);
$tickets      = $resultado['tickets'];
$totalPaginas = $resultado['paginas'];
$estadisticas = $resultado['por_estado'];

$admins      = dbQuery('SELECT id, nombre FROM usuarios WHERE rol = "admin" ORDER BY nombre');
$verTicketId = (int)($_GET['ver'] ?? 0);

if ($verTicketId) {
    $ticket = obtenerTicket($verTicketId);
    if (!$ticket) {
        $errors[] = 'Ticket no encontrado.';
    }
}


function labelPrioridad(string $p): string {
    return match($p) {
        'baja'    => 'Baja',
        'normal'  => 'Normal',
        'alta'    => 'Alta',
        'critica' => 'Crítica',
        default   => ucfirst($p),
    };
}
function labelEstado(string $e): string {
    return match($e) {
        'en_proceso' => 'En proceso',
        default      => ucfirst($e),
    };
}
?>

<style>
/* =============================================
   ADMIN TICKETS — tema dark coherente con IPHIX
   ============================================= */
.tk-wrap { display: grid; gap: 1.5rem; }

/* Flash messages */
.tk-alert {
    padding: 0.85rem 1.1rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    animation: tkSlide 0.3s ease-out;
}
.tk-alert-error   { background: rgba(255,77,109,0.1); border: 1px solid rgba(255,77,109,0.25); color: #ff4d6d; }
.tk-alert-success { background: rgba(0,255,157,0.1); border: 1px solid rgba(0,255,157,0.25); color: #00ff9d; }
@keyframes tkSlide {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Stats */
.tk-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem; }
.tk-stat {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    padding: 1.25rem 1rem;
    text-align: center;
    transition: border-color 0.25s, transform 0.25s;
}
.tk-stat:hover { border-color: rgba(0,212,255,0.25); transform: translateY(-3px); }
.tk-stat-num { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: 0.4rem; }
.tk-stat-label { font-size: 0.78rem; color: #8892a4; text-transform: uppercase; letter-spacing: 0.06em; }
.tk-stat-abierto .tk-stat-num    { color: #00d4ff; }
.tk-stat-en_proceso .tk-stat-num { color: #ffbe0b; }
.tk-stat-resuelto .tk-stat-num   { color: #00ff9d; }
.tk-stat-reabierto .tk-stat-num  { color: #ff4d6d; }
.tk-stat-cerrado .tk-stat-num    { color: #a78bfa; }

/* Filtros */
.tk-filters {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    padding: 1rem;
}
.tk-filters-form { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
.tk-select, .tk-input {
    padding: 0.55rem 0.85rem;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    font-size: 0.88rem;
    color: #e8ecf4;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
}
.tk-select option { background: #0e1220; color: #e8ecf4; }
.tk-input { flex: 1; min-width: 200px; }
.tk-input::placeholder { color: #4a5568; }
.tk-select:focus, .tk-input:focus { border-color: rgba(0,212,255,0.4); box-shadow: 0 0 0 3px rgba(0,212,255,0.08); }
.tk-btn-search {
    padding: 0.55rem 1.1rem;
    background: rgba(0,212,255,0.1);
    color: #00d4ff;
    border: 1px solid rgba(0,212,255,0.3);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.85rem;
    font-family: inherit;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s;
    text-decoration: none;
}
.tk-btn-search:hover { background: rgba(0,212,255,0.18); color: #00d4ff; border-color: #00d4ff; }
.tk-btn-reset { background: rgba(255,255,255,0.04); color: #8892a4; border-color: rgba(255,255,255,0.1); }
.tk-btn-reset:hover { background: rgba(255,255,255,0.08); color: #e8ecf4; border-color: rgba(255,255,255,0.2); }

/* Tabla */
.tk-table-wrap {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    overflow: hidden;
}
.tk-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.tk-table thead { background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.07); }
.tk-table th {
    padding: 0.85rem 1rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 600;
    color: #8892a4;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    white-space: nowrap;
}
.tk-table td {
    padding: 0.9rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    color: #e8ecf4;
    vertical-align: middle;
}
.tk-table tbody tr:hover td { background: rgba(0,212,255,0.03); }
.tk-table tbody tr:last-child td { border-bottom: none; }

/* Celda código */
.tk-code {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    font-weight: 700;
    color: #00d4ff;
    letter-spacing: 0.03em;
    white-space: nowrap;
}

/* Celda cliente */
.tk-cliente-name  { font-weight: 600; color: #e8ecf4; font-size: 0.88rem; }
.tk-cliente-email { font-size: 0.78rem; color: #8892a4; margin-top: 2px; }

/* Celda asunto */
.tk-asunto {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #c5cede;
    font-size: 0.88rem;
}

/* Badges */
.tk-badge {
    display: inline-block;
    padding: 0.22rem 0.7rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    white-space: nowrap;
}
/* Estado */
.tk-badge-abierto    { background: rgba(0,212,255,0.12); color: #00d4ff; border: 1px solid rgba(0,212,255,0.25); }
.tk-badge-en_proceso { background: rgba(255,190,11,0.12); color: #ffbe0b; border: 1px solid rgba(255,190,11,0.25); }
.tk-badge-resuelto   { background: rgba(0,255,157,0.12); color: #00ff9d; border: 1px solid rgba(0,255,157,0.25); }
.tk-badge-reabierto  { background: rgba(255,77,109,0.12); color: #ff4d6d; border: 1px solid rgba(255,77,109,0.25); }
.tk-badge-cerrado    { background: rgba(138,92,246,0.12); color: #a78bfa; border: 1px solid rgba(138,92,246,0.25); }
/* Prioridad */
.tk-badge-baja    { background: rgba(0,255,157,0.08); color: #00ff9d; border: 1px solid rgba(0,255,157,0.2); }
.tk-badge-normal  { background: rgba(0,212,255,0.08); color: #00d4ff; border: 1px solid rgba(0,212,255,0.2); }
.tk-badge-alta    { background: rgba(255,190,11,0.1); color: #ffbe0b; border: 1px solid rgba(255,190,11,0.22); }
.tk-badge-critica { background: rgba(255,77,109,0.1); color: #ff4d6d; border: 1px solid rgba(255,77,109,0.25); }

/* Sin asignar */
.tk-unassigned { color: #4a5568; font-style: italic; font-size: 0.85rem; }

/* Fecha */
.tk-date { font-size: 0.8rem; color: #8892a4; white-space: nowrap; }

/* Botón ver */
.tk-btn-ver {
    padding: 0.35rem 0.75rem;
    background: rgba(0,212,255,0.1);
    color: #00d4ff;
    border: 1px solid rgba(0,212,255,0.25);
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    text-decoration: none;
    transition: all 0.2s;
}
.tk-btn-ver:hover { background: rgba(0,212,255,0.2); border-color: #00d4ff; color: #00d4ff; }

/* Empty state */
.tk-empty { text-align: center; padding: 3rem 1rem; color: #4a5568; }
.tk-empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4; }
.tk-empty-text { font-size: 0.95rem; }

/* Paginación */
.tk-pagination-wrap {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid rgba(255,255,255,0.05);
    flex-wrap: wrap;
    gap: 0.75rem;
}
.tk-pagination-info { font-size: 0.82rem; color: #8892a4; }
.tk-pagination { display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap; }
.tk-pagination a, .tk-pagination span {
    padding: 0.35rem 0.7rem;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 6px;
    text-decoration: none;
    color: #8892a4;
    font-size: 0.82rem;
    transition: all 0.2s;
    min-width: 32px;
    text-align: center;
    display: inline-block;
}
.tk-pagination a:hover { background: rgba(0,212,255,0.1); color: #00d4ff; border-color: rgba(0,212,255,0.3); }
.tk-pagination .active { background: rgba(0,212,255,0.15); color: #00d4ff; border-color: rgba(0,212,255,0.4); font-weight: 700; }

/* ---- VISTA DETALLE ---- */
.tk-back {
    padding: 0.5rem 1rem;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    color: #8892a4;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.88rem;
    font-weight: 500;
    transition: all 0.2s;
    margin-bottom: 1.5rem;
}
.tk-back:hover { background: rgba(255,255,255,0.08); color: #e8ecf4; border-color: rgba(255,255,255,0.15); }

.tk-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.tk-card {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
}
.tk-card-header {
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    font-weight: 700;
    font-size: 0.88rem;
    color: #8892a4;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.tk-card-body { padding: 1.25rem; }

.tk-info-row { margin-bottom: 1rem; }
.tk-info-row:last-child { margin-bottom: 0; }
.tk-info-label {
    font-size: 0.72rem;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    font-weight: 600;
    margin-bottom: 0.3rem;
}
.tk-info-value { font-size: 0.92rem; color: #e8ecf4; font-weight: 500; }
.tk-info-sub   { font-size: 0.8rem; color: #8892a4; margin-top: 2px; }

/* Formularios en panel acciones */
.tk-form-group { margin-bottom: 1rem; }
.tk-form-group:last-child { margin-bottom: 0; }
.tk-form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #8892a4;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.tk-form-select, .tk-form-textarea {
    width: 100%;
    padding: 0.65rem 0.85rem;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    font-size: 0.88rem;
    color: #e8ecf4;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
}
.tk-form-select option { background: #0e1220; color: #e8ecf4; }
.tk-form-textarea { resize: vertical; min-height: 80px; }
.tk-form-select:focus, .tk-form-textarea:focus {
    border-color: rgba(0,212,255,0.4);
    box-shadow: 0 0 0 3px rgba(0,212,255,0.08);
}
.tk-form-textarea::placeholder { color: #4a5568; }

.tk-btn-submit {
    width: 100%;
    padding: 0.7rem;
    background: rgba(0,212,255,0.1);
    color: #00d4ff;
    border: 1px solid rgba(0,212,255,0.3);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 0.88rem;
    font-family: 'Syne', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}
.tk-btn-submit:hover {
    background: rgba(0,212,255,0.18);
    border-color: #00d4ff;
    transform: translateY(-1px);
}

.tk-divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.06);
    margin: 1.1rem 0;
}

/* Chat */
.tk-chat-wrap {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.tk-chat-messages {
    height: 460px;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    scroll-behavior: smooth;
}
.tk-chat-messages::-webkit-scrollbar { width: 5px; }
.tk-chat-messages::-webkit-scrollbar-track { background: transparent; }
.tk-chat-messages::-webkit-scrollbar-thumb { background: rgba(0,212,255,0.2); border-radius: 3px; }

.tk-msg {
    padding: 0.8rem 1rem;
    border-radius: 10px;
    max-width: 80%;
    animation: tkMsgIn 0.25s ease-out;
}
@keyframes tkMsgIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mensaje del usuario (cliente) — alineado izquierda */
.tk-msg-user {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.07);
    border-left: 3px solid rgba(0,212,255,0.4);
    align-self: flex-start;
    margin-right: auto;
}
/* Mensaje del admin — alineado derecha */
.tk-msg-admin {
    background: rgba(138,92,246,0.1);
    border: 1px solid rgba(138,92,246,0.2);
    border-right: 3px solid rgba(138,92,246,0.5);
    align-self: flex-end;
    margin-left: auto;
}
/* Mensaje del bot */
.tk-msg-bot {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-left: 3px solid rgba(255,255,255,0.12);
    align-self: flex-start;
    margin-right: auto;
    font-style: italic;
}

.tk-msg-meta {
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.tk-msg-user .tk-msg-meta  { color: #00d4ff; }
.tk-msg-admin .tk-msg-meta { color: #a78bfa; }
.tk-msg-bot .tk-msg-meta   { color: #4a5568; }

.tk-msg-text {
    font-size: 0.88rem;
    line-height: 1.6;
    word-wrap: break-word;
    color: #e8ecf4;
}
.tk-msg-bot .tk-msg-text { color: #8892a4; }

.tk-msg-time {
    font-size: 0.72rem;
    color: #4a5568;
    margin-top: 0.3rem;
}

.tk-chat-input {
    padding: 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.02);
}

.tk-empty-chat {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4a5568;
    font-size: 0.9rem;
    flex-direction: column;
    gap: 0.5rem;
}
.tk-empty-chat i { font-size: 1.8rem; opacity: 0.4; }

/* Page heading */
.tk-heading { font-family: 'Syne', sans-serif; font-size: clamp(1.4rem, 4vw, 1.9rem); font-weight: 800; color: #e8ecf4; margin-bottom: 0.3rem; }
.tk-subheading { color: #8892a4; font-size: 0.9rem; margin: 0 0 1.5rem; }

/* Responsive */
@media (max-width: 768px) {
    .tk-filters-form { flex-direction: column; }
    .tk-input { min-width: 100%; }
    .tk-table { font-size: 0.78rem; }
    .tk-table th, .tk-table td { padding: 0.65rem 0.5rem; }
    .tk-asunto { max-width: 100px; }
    .tk-detail-grid { grid-template-columns: 1fr; }
    .tk-msg { max-width: 92%; }
    .tk-chat-messages { height: 380px; }
}

@media (max-width: 480px) {
    .tk-stats { grid-template-columns: repeat(2, 1fr); }
    .tk-btn-search { width: 100%; justify-content: center; }
    .tk-pagination-wrap { flex-direction: column; align-items: flex-start; }
    .tk-chat-messages { height: 300px; }
}
</style>

<div class="tk-wrap">

<!-- Flash messages -->
<?php foreach ($errors as $e): ?>
<div class="tk-alert tk-alert-error">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span><?= htmlspecialchars($e) ?></span>
</div>
<?php endforeach; ?>

<?php if ($success): ?>
<div class="tk-alert tk-alert-success">
    <i class="bi bi-check-circle-fill"></i>
    <span><?= htmlspecialchars($success) ?></span>
</div>
<?php endif; ?>


<!-- ======================== VISTA DETALLE ======================== -->
<?php if ($verTicketId && !empty($ticket)): ?>

    <a href="/admin/tickets.php" class="tk-back">
        <i class="bi bi-arrow-left"></i> Volver a tickets
    </a>

    <div class="tk-detail-grid">

        <!-- Información del ticket -->
        <div class="tk-card">
            <div class="tk-card-header">
                <i class="bi bi-info-circle"></i> Información del ticket
            </div>
            <div class="tk-card-body">
                <div class="tk-info-row">
                    <div class="tk-info-label">Código</div>
                    <div class="tk-info-value" style="font-family: monospace; color: #00d4ff;"><?= htmlspecialchars($ticket['codigo']) ?></div>
                </div>
                <div class="tk-info-row">
                    <div class="tk-info-label">Cliente</div>
                    <div class="tk-info-value"><?= htmlspecialchars($ticket['usuario_nombre']) ?></div>
                    <div class="tk-info-sub"><?= htmlspecialchars($ticket['email']) ?></div>
                </div>
                <div class="tk-info-row">
                    <div class="tk-info-label">Asunto</div>
                    <div class="tk-info-value"><?= htmlspecialchars($ticket['asunto']) ?></div>
                </div>
                <div class="tk-info-row">
                    <div class="tk-info-label">Estado</div>
                    <div class="tk-info-value">
                        <span class="tk-badge tk-badge-<?= htmlspecialchars($ticket['estado']) ?>">
                            <?= labelEstado($ticket['estado']) ?>
                        </span>
                    </div>
                </div>
                <div class="tk-info-row">
                    <div class="tk-info-label">Prioridad</div>
                    <div class="tk-info-value">
                        <span class="tk-badge tk-badge-<?= htmlspecialchars($ticket['prioridad']) ?>">
                            <?= labelPrioridad($ticket['prioridad']) ?>
                        </span>
                    </div>
                </div>
                <div class="tk-info-row">
                    <div class="tk-info-label">Categoría</div>
                    <div class="tk-info-value"><?= ucfirst(htmlspecialchars($ticket['categoria'])) ?></div>
                </div>
                <div class="tk-info-row">
                    <div class="tk-info-label">Asignado a</div>
                    <div class="tk-info-value">
                        <?= $ticket['admin_nombre'] ? htmlspecialchars($ticket['admin_nombre']) : '<span class="tk-unassigned">Sin asignar</span>' ?>
                    </div>
                </div>
                <div class="tk-info-row">
                    <div class="tk-info-label">Creado</div>
                    <div class="tk-info-value"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></div>
                </div>
                <div class="tk-info-row">
                    <div class="tk-info-label">Última actualización</div>
                    <div class="tk-info-value"><?= date('d/m/Y H:i', strtotime($ticket['updated_at'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="tk-card">
            <div class="tk-card-header">
                <i class="bi bi-lightning-charge"></i> Acciones
            </div>
            <div class="tk-card-body">
                <form method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="_action" value="asignar">
                    <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                    <div class="tk-form-group">
                        <label class="tk-form-label">Asignar a administrador</label>
                        <select name="admin_id" class="tk-form-select" required>
                            <option value="">— Seleccionar admin —</option>
                            <?php foreach ($admins as $a): ?>
                            <option value="<?= (int)$a['id'] ?>" <?= (isset($ticket['asignado_a']) && $ticket['asignado_a'] == $a['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="tk-btn-submit">
                        <i class="bi bi-person-check"></i> Asignar ticket
                    </button>
                </form>

                <hr class="tk-divider">

                <form method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="_action" value="cambiar_estado">
                    <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                    <div class="tk-form-group">
                        <label class="tk-form-label">Cambiar estado</label>
                        <select name="nuevo_estado" class="tk-form-select" required>
                            <option value="abierto"    <?= $ticket['estado'] === 'abierto'    ? 'selected' : '' ?>>Abierto</option>
                            <option value="en_proceso" <?= $ticket['estado'] === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                            <option value="resuelto"   <?= $ticket['estado'] === 'resuelto'   ? 'selected' : '' ?>>Resuelto</option>
                            <option value="cerrado"    <?= $ticket['estado'] === 'cerrado'    ? 'selected' : '' ?>>Cerrado</option>
                        </select>
                    </div>
                    <button type="submit" class="tk-btn-submit" style="background: rgba(0,255,157,0.08); color: #00ff9d; border-color: rgba(0,255,157,0.3);">
                        <i class="bi bi-check2-circle"></i> Actualizar estado
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Conversación -->
    <div class="tk-chat-wrap">
        <div class="tk-card-header" style="padding: 0.9rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.06);">
            <i class="bi bi-chat-dots"></i> Conversación
        </div>

        <div class="tk-chat-messages" id="chatMessages">
            <?php if (empty($ticket['mensajes'])): ?>
                <div class="tk-empty-chat">
                    <i class="bi bi-chat-left"></i>
                    <span>Sin mensajes aún</span>
                </div>
            <?php else: ?>
                <?php foreach ($ticket['mensajes'] as $m): ?>
                    <?php
                        if ($m['es_bot']) {
                            $cls = 'tk-msg-bot';
                        } elseif ($m['es_admin']) {
                            $cls = 'tk-msg-admin';
                        } else {
                            $cls = 'tk-msg-user';
                        }
                        $metaLabel = $m['es_bot']
                            ? '🤖 Asistente'
                            : ($m['es_admin']
                                ? htmlspecialchars($m['usuario_nombre']) . ' (Admin)'
                                : htmlspecialchars($m['usuario_nombre']));
                    ?>
                    <div class="tk-msg <?= $cls ?>">
                        <div class="tk-msg-meta"><?= $metaLabel ?></div>
                        <div class="tk-msg-text"><?= nl2br(htmlspecialchars($m['mensaje'])) ?></div>
                        <div class="tk-msg-time"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tk-chat-input">
            <form method="POST">
                <?= campoCSRF() ?>
                <input type="hidden" name="_action" value="responder">
                <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                <div class="tk-form-group" style="margin-bottom: 0.75rem;">
                    <textarea name="mensaje" class="tk-form-textarea" placeholder="Escribe tu respuesta al cliente..." required style="min-height: 70px;"></textarea>
                </div>
                <button type="submit" class="tk-btn-submit">
                    <i class="bi bi-send"></i> Enviar respuesta
                </button>
            </form>
        </div>
    </div>


<!-- ======================== LISTA DE TICKETS ======================== -->
<?php else: ?>

    <div>
        <h1 class="tk-heading"><i class="bi bi-ticket-detailed"></i> Gestión de Tickets</h1>
        <p class="tk-subheading">Total: <strong style="color:#e8ecf4;"><?= (int)($resultado['total'] ?? 0) ?></strong> tickets en el sistema</p>
    </div>

    <!-- Estadísticas -->
    <div class="tk-stats">
        <?php
        $estadoIconos = [
            'abierto'    => 'envelope-open',
            'en_proceso' => 'hourglass-split',
            'resuelto'   => 'check-circle',
            'reabierto'  => 'arrow-counterclockwise',
            'cerrado'    => 'lock',
        ];
        foreach ($estadisticas as $est => $count):
        ?>
        <div class="tk-stat tk-stat-<?= $est ?>">
            <div class="tk-stat-num"><?= (int)$count ?></div>
            <div class="tk-stat-label">
                <i class="bi bi-<?= $estadoIconos[$est] ?? 'circle' ?>"></i>
                <?= labelEstado($est) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="tk-filters">
        <form method="GET" class="tk-filters-form">
            <select name="estado" class="tk-select">
                <option value="todos"      <?= $estado === 'todos'      ? 'selected' : '' ?>>Todos los estados</option>
                <option value="abierto"    <?= $estado === 'abierto'    ? 'selected' : '' ?>>Abierto</option>
                <option value="en_proceso" <?= $estado === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                <option value="resuelto"   <?= $estado === 'resuelto'   ? 'selected' : '' ?>>Resuelto</option>
                <option value="reabierto"  <?= $estado === 'reabierto'  ? 'selected' : '' ?>>Reabierto</option>
                <option value="cerrado"    <?= $estado === 'cerrado'    ? 'selected' : '' ?>>Cerrado</option>
            </select>
            <input type="text" name="q" class="tk-input" value="<?= htmlspecialchars($filtro) ?>" placeholder="Buscar por código, asunto, email...">
            <button type="submit" class="tk-btn-search">
                <i class="bi bi-search"></i> Buscar
            </button>
            <a href="/admin/tickets.php" class="tk-btn-search tk-btn-reset">
                <i class="bi bi-x"></i> Limpiar
            </a>
        </form>
    </div>

    <!-- Tabla -->
    <div class="tk-table-wrap">
        <table class="tk-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Asunto</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Asignado a</th>
                    <th>Fecha</th>
                    <th style="text-align:center;">Ver</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="8">
                        <div class="tk-empty">
                            <div class="tk-empty-icon"><i class="bi bi-inbox"></i></div>
                            <div class="tk-empty-text">No se encontraron tickets</div>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><span class="tk-code"><?= htmlspecialchars($t['codigo']) ?></span></td>
                        <td>
                            <div class="tk-cliente-name"><?= htmlspecialchars($t['nombre']) ?></div>
                            <div class="tk-cliente-email"><?= htmlspecialchars($t['email']) ?></div>
                        </td>
                        <td><span class="tk-asunto" title="<?= htmlspecialchars($t['asunto']) ?>"><?= htmlspecialchars($t['asunto']) ?></span></td>
                        <td>
                            <span class="tk-badge tk-badge-<?= htmlspecialchars($t['estado']) ?>">
                                <?= labelEstado($t['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="tk-badge tk-badge-<?= htmlspecialchars($t['prioridad']) ?>">
                                <?= labelPrioridad($t['prioridad']) ?>
                            </span>
                        </td>
                        <td>
                            <?= !empty($t['admin_nombre'])
                                ? htmlspecialchars($t['admin_nombre'])
                                : '<span class="tk-unassigned">Sin asignar</span>' ?>
                        </td>
                        <td class="tk-date"><?= date('d/m H:i', strtotime($t['created_at'])) ?></td>
                        <td style="text-align:center;">
                            <a href="/admin/tickets.php?ver=<?= (int)$t['id'] ?>" class="tk-btn-ver">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPaginas > 1): ?>
        <div class="tk-pagination-wrap">
            <span class="tk-pagination-info">Página <?= $pagina ?> de <?= $totalPaginas ?></span>
            <div class="tk-pagination">
                <?php if ($pagina > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pagina - 1])) ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php for ($pp = max(1, $pagina - 2); $pp <= min($totalPaginas, $pagina + 2); $pp++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pp])) ?>"
                   class="<?= $pp === $pagina ? 'active' : '' ?>"><?= $pp ?></a>
                <?php endfor; ?>

                <?php if ($pagina < $totalPaginas): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pagina + 1])) ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

<?php endif; ?>
</div>

<script>
/* Auto-scroll al último mensaje del chat */
const chat = document.getElementById('chatMessages');
if (chat) chat.scrollTop = chat.scrollHeight;
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>