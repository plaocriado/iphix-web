<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tickets.php';

requiereLogin();

$usuario   = usuarioActual();
$pageTitle = 'Centro de Soporte';

$errors  = [];
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_ticket'])) {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $asunto      = trim(strip_tags($_POST['asunto'] ?? ''));
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoria   = $_POST['categoria'] ?? 'otro';
        $prioridad   = $_POST['prioridad'] ?? 'normal';

        if (!$asunto || !$descripcion) {
            $errors[] = 'El asunto y la descripción son obligatorios.';
        } elseif (strlen($descripcion) < 20) {
            $errors[] = 'La descripción debe tener al menos 20 caracteres.';
        } else {
            $ticketId = crearTicket($usuario['id'], $asunto, $descripcion, $categoria, $prioridad);
            if ($ticketId) {
                $success         = 'Ticket creado correctamente.';
                $_GET['ver']     = $ticketId;
            } else {
                $errors[] = 'Error al crear el ticket.';
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mensaje'])) {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $mensaje  = trim($_POST['mensaje'] ?? '');

        if (!$ticketId || !$mensaje) {
            $errors[] = 'El mensaje no puede estar vacío.';
        } else {
            $row = dbQueryOne("SELECT id, usuario_id FROM tickets WHERE id = ?", [$ticketId]);
            if (!$row || $row['usuario_id'] !== $usuario['id']) {
                $errors[] = 'Acceso denegado.';
            } else {
                agregarMensajeTicket($ticketId, $usuario['id'], $mensaje);
                $success = 'Mensaje enviado.';

                
                $preguntaActual = obtenerSiguientePreguntaBot($ticketId);
                if ($preguntaActual) {
                    completarPreguntaBot($ticketId, $preguntaActual['paso'], $mensaje);
                    $siguientePregunta = dbQueryOne(
                        "SELECT * FROM respuestas_bot WHERE ticket_id = ? AND paso = ? LIMIT 1",
                        [$ticketId, $preguntaActual['paso'] + 1]
                    );
                    $botMsg = $siguientePregunta
                        ? $siguientePregunta['pregunta'] . "\n\n_(Responde en el siguiente mensaje)_"
                        : "✅ Gracias por tus respuestas. Un administrador revisará tu ticket pronto y te contactará.";
                    agregarMensajeTicket($ticketId, $usuario['id'], $botMsg, false, null, true);
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';


$pagina      = max(1, (int)($_GET['p'] ?? 1));
$verTicketId = (int)($_GET['ver'] ?? 0);

if ($verTicketId) {
    $ticket = obtenerTicket($verTicketId);
    if (!$ticket || $ticket['usuario_id'] !== $usuario['id']) {
        $ticket  = null;
        $errors[] = 'Ticket no encontrado.';
    } else {
        dbExecute(
            "UPDATE mensajes_ticket SET leido = 1 WHERE ticket_id = ? AND es_admin = 1",
            [$verTicketId]
        );
    }
} else {
    $resultado    = obtenerTicketsUsuario($usuario['id'], null, $pagina, 10);
    $tickets      = $resultado['tickets'];
    $totalPaginas = $resultado['paginas'];
}


function labelEstadoU(string $e): string {
    return match($e) {
        'en_proceso' => 'En proceso',
        'reabierto'  => 'Reabierto',
        default      => ucfirst($e),
    };
}
function labelPrioridadU(string $p): string {
    return match($p) {
        'critica' => 'Crítica',
        default   => ucfirst($p),
    };
}
?>

<style>
/* =============================================
   SOPORTE USUARIO — dark theme IPHIX
   ============================================= */
.sp-wrap {
    max-width: 960px;
    margin: 0 auto;
    padding: 2rem 1rem 4rem;
}

/* Encabezado */
.sp-heading {
    font-family: 'Syne', sans-serif;
    font-size: clamp(1.6rem, 4vw, 2.2rem);
    font-weight: 800;
    color: #e8ecf4;
    margin: 0 0 0.3rem;
}
.sp-subheading { color: #8892a4; font-size: 0.95rem; margin: 0 0 2rem; }

/* Alertas */
.sp-alert {
    padding: 0.85rem 1.1rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    animation: spSlide 0.3s ease-out;
}
@keyframes spSlide {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.sp-alert-error   { background: rgba(255,77,109,0.1); border: 1px solid rgba(255,77,109,0.25); color: #ff4d6d; }
.sp-alert-success { background: rgba(0,255,157,0.1); border: 1px solid rgba(0,255,157,0.25); color: #00ff9d; }
.sp-alert-info    { background: rgba(0,212,255,0.1); border: 1px solid rgba(0,212,255,0.25); color: #00d4ff; }

/* Badges de estado */
.sp-badge {
    display: inline-block;
    padding: 0.22rem 0.72rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    white-space: nowrap;
}
.sp-badge-abierto    { background: rgba(0,212,255,0.12); color: #00d4ff; border: 1px solid rgba(0,212,255,0.25); }
.sp-badge-en_proceso { background: rgba(255,190,11,0.12); color: #ffbe0b; border: 1px solid rgba(255,190,11,0.25); }
.sp-badge-resuelto   { background: rgba(0,255,157,0.12); color: #00ff9d; border: 1px solid rgba(0,255,157,0.25); }
.sp-badge-reabierto  { background: rgba(255,77,109,0.12); color: #ff4d6d; border: 1px solid rgba(255,77,109,0.25); }
.sp-badge-cerrado    { background: rgba(138,92,246,0.12); color: #a78bfa; border: 1px solid rgba(138,92,246,0.25); }

/* Badges de prioridad */
.sp-prio {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 5px;
    font-size: 0.72rem;
    font-weight: 700;
}
.sp-prio-baja    { background: rgba(0,255,157,0.08); color: #00ff9d; border: 1px solid rgba(0,255,157,0.2); }
.sp-prio-normal  { background: rgba(0,212,255,0.08); color: #00d4ff; border: 1px solid rgba(0,212,255,0.2); }
.sp-prio-alta    { background: rgba(255,190,11,0.1); color: #ffbe0b; border: 1px solid rgba(255,190,11,0.22); }
.sp-prio-critica { background: rgba(255,77,109,0.1); color: #ff4d6d; border: 1px solid rgba(255,77,109,0.25); animation: spPulse 2s infinite; }
@keyframes spPulse { 0%,100%{opacity:1} 50%{opacity:0.6} }

/* Cards genéricas */
.sp-card {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
}
.sp-card-body { padding: 1.25rem; }
.sp-card-header {
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    font-size: 0.78rem;
    font-weight: 700;
    color: #8892a4;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Botones */
.sp-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1.1rem;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid transparent;
    white-space: nowrap;
}
.sp-btn-primary {
    background: rgba(0,212,255,0.1);
    color: #00d4ff;
    border-color: rgba(0,212,255,0.3);
}
.sp-btn-primary:hover { background: rgba(0,212,255,0.18); border-color: #00d4ff; color: #00d4ff; }
.sp-btn-ghost {
    background: rgba(255,255,255,0.04);
    color: #8892a4;
    border-color: rgba(255,255,255,0.08);
}
.sp-btn-ghost:hover { background: rgba(255,255,255,0.08); color: #e8ecf4; border-color: rgba(255,255,255,0.15); }
.sp-btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
.sp-btn-full { width: 100%; justify-content: center; }

/* --- VISTA DETALLE --- */
.sp-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.sp-info-row { margin-bottom: 0.9rem; }
.sp-info-row:last-child { margin-bottom: 0; }
.sp-info-label {
    font-size: 0.72rem;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    font-weight: 600;
    margin-bottom: 0.28rem;
}
.sp-info-value { font-size: 0.92rem; color: #e8ecf4; font-weight: 500; }

/* Chat */
.sp-chat {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
}
.sp-chat-messages {
    height: 440px;
    overflow-y: auto;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    scroll-behavior: smooth;
}
.sp-chat-messages::-webkit-scrollbar { width: 5px; }
.sp-chat-messages::-webkit-scrollbar-track { background: transparent; }
.sp-chat-messages::-webkit-scrollbar-thumb { background: rgba(0,212,255,0.2); border-radius: 3px; }

.sp-msg {
    padding: 0.8rem 1rem;
    border-radius: 10px;
    max-width: 80%;
    word-wrap: break-word;
    animation: spMsgIn 0.25s ease-out;
}
@keyframes spMsgIn {
    from { opacity: 0; transform: translateY(5px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mensaje del usuario — burbuja derecha, cyan */
.sp-msg-user {
    align-self: flex-end;
    margin-left: auto;
    background: rgba(0,212,255,0.1);
    border: 1px solid rgba(0,212,255,0.2);
    border-right: 3px solid rgba(0,212,255,0.5);
}
/* Mensaje del admin — burbuja izquierda, púrpura */
.sp-msg-admin {
    align-self: flex-start;
    margin-right: auto;
    background: rgba(138,92,246,0.1);
    border: 1px solid rgba(138,92,246,0.2);
    border-left: 3px solid rgba(138,92,246,0.5);
}
/* Mensaje del bot */
.sp-msg-bot {
    align-self: flex-start;
    margin-right: auto;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-left: 3px solid rgba(255,255,255,0.12);
    font-style: italic;
}

.sp-msg-meta { font-size: 0.74rem; font-weight: 700; margin-bottom: 0.35rem; }
.sp-msg-user .sp-msg-meta  { color: #00d4ff; text-align: right; }
.sp-msg-admin .sp-msg-meta { color: #a78bfa; }
.sp-msg-bot .sp-msg-meta   { color: #4a5568; }

.sp-msg-text {
    font-size: 0.88rem;
    line-height: 1.6;
    color: #e8ecf4;
    white-space: pre-wrap;
    word-break: break-word;
}
.sp-msg-bot .sp-msg-text { color: #8892a4; }

.sp-msg-time { font-size: 0.71rem; color: #4a5568; margin-top: 0.3rem; }
.sp-msg-user .sp-msg-time { text-align: right; }

.sp-chat-empty {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 0.5rem;
    color: #4a5568;
    font-size: 0.9rem;
}
.sp-chat-empty i { font-size: 1.8rem; opacity: 0.35; }

/* Formulario reply */
.sp-reply {
    padding: 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.015);
}
.sp-reply-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #8892a4;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 0.6rem;
    display: block;
}
.sp-reply textarea {
    width: 100%;
    padding: 0.7rem 0.9rem;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.88rem;
    color: #e8ecf4;
    resize: vertical;
    min-height: 80px;
    outline: none;
    transition: border-color 0.2s;
    margin-bottom: 0.75rem;
}
.sp-reply textarea::placeholder { color: #4a5568; }
.sp-reply textarea:focus { border-color: rgba(0,212,255,0.4); box-shadow: 0 0 0 3px rgba(0,212,255,0.08); }

/* --- VISTA LISTA --- */
.sp-list-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.sp-count { color: #8892a4; font-size: 0.9rem; }
.sp-count strong { color: #e8ecf4; }

.sp-table-wrap {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
}
.sp-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.sp-table thead {
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.sp-table th {
    padding: 0.85rem 1rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 600;
    color: #8892a4;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    white-space: nowrap;
}
.sp-table td {
    padding: 0.9rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    color: #e8ecf4;
    vertical-align: middle;
}
.sp-table tbody tr:hover td { background: rgba(0,212,255,0.03); }
.sp-table tbody tr:last-child td { border-bottom: none; }
.sp-table-code {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    font-weight: 700;
    color: #00d4ff;
    letter-spacing: 0.03em;
}
.sp-table-subject { font-weight: 600; color: #e8ecf4; }
.sp-table-date    { font-size: 0.82rem; color: #8892a4; white-space: nowrap; }
.sp-table-unread {
    display: inline-block;
    width: 8px; height: 8px;
    background: #00d4ff;
    border-radius: 50%;
    margin-left: 6px;
    vertical-align: middle;
    animation: spPulse 2s infinite;
}

.sp-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: #4a5568;
}
.sp-empty i { font-size: 2.2rem; display: block; margin-bottom: 0.75rem; opacity: 0.35; }
.sp-empty p { font-size: 0.92rem; margin: 0; }

/* Paginación */
.sp-pagination {
    display: flex;
    justify-content: center;
    gap: 0.4rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.sp-pagination a {
    padding: 0.35rem 0.75rem;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 6px;
    text-decoration: none;
    color: #8892a4;
    font-size: 0.82rem;
    transition: all 0.2s;
    min-width: 34px;
    text-align: center;
    display: inline-block;
}
.sp-pagination a:hover { background: rgba(0,212,255,0.1); color: #00d4ff; border-color: rgba(0,212,255,0.3); }
.sp-pagination a.active { background: rgba(0,212,255,0.15); color: #00d4ff; border-color: rgba(0,212,255,0.4); font-weight: 700; }

/* --- MODAL NUEVO TICKET --- */
.sp-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.72);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    backdrop-filter: blur(4px);
}
.sp-modal-overlay.active { display: flex; animation: spFade 0.25s ease-out; }
@keyframes spFade { from{opacity:0} to{opacity:1} }

.sp-modal {
    background: #0e1220;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.7);
    width: 100%;
    max-width: 560px;
    max-height: 92vh;
    overflow-y: auto;
    animation: spSlideUp 0.3s ease-out;
}
.sp-modal::-webkit-scrollbar { width: 5px; }
.sp-modal::-webkit-scrollbar-thumb { background: rgba(0,212,255,0.2); border-radius: 3px; }
@keyframes spSlideUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }

.sp-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    position: sticky;
    top: 0;
    background: #0e1220;
    z-index: 1;
}
.sp-modal-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #e8ecf4;
}
.sp-modal-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #8892a4;
    cursor: pointer;
    line-height: 1;
    transition: color 0.2s;
    padding: 0.2rem;
}
.sp-modal-close:hover { color: #ff4d6d; }

.sp-modal-body { padding: 1.5rem; }

.sp-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.1rem 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.07);
}

/* Formulario dentro del modal */
.sp-fgroup { margin-bottom: 1.1rem; }
.sp-fgroup:last-child { margin-bottom: 0; }
.sp-flabel {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #8892a4;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.sp-finput,
.sp-fselect,
.sp-ftextarea {
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
.sp-finput::placeholder, .sp-ftextarea::placeholder { color: #4a5568; }
.sp-fselect option { background: #0e1220; color: #e8ecf4; }
.sp-ftextarea { resize: vertical; min-height: 120px; }
.sp-finput:focus, .sp-fselect:focus, .sp-ftextarea:focus {
    border-color: rgba(0,212,255,0.4);
    box-shadow: 0 0 0 3px rgba(0,212,255,0.08);
}
.sp-fhint { font-size: 0.75rem; color: #4a5568; margin-top: 0.3rem; }

/* Responsive */
@media (max-width: 768px) {
    .sp-detail-grid { grid-template-columns: 1fr; }
    .sp-msg { max-width: 92%; }
    .sp-chat-messages { height: 360px; }
    .sp-list-top { flex-direction: column; align-items: stretch; }
    .sp-btn-primary { justify-content: center; }
    .sp-table th, .sp-table td { padding: 0.7rem 0.65rem; }
}
@media (max-width: 480px) {
    .sp-wrap { padding: 1.25rem 0.75rem 3rem; }
    .sp-chat-messages { height: 280px; }
    .sp-modal { border-radius: 12px; }
    .sp-table { font-size: 0.8rem; }
}
</style>

<div class="sp-wrap">

    <!-- Encabezado -->
    <h1 class="sp-heading"><i class="bi bi-headset"></i> Centro de Soporte</h1>
    <p class="sp-subheading">Gestiona tus tickets y comunícate con nuestro equipo</p>

    <!-- Alertas -->
    <?php foreach ($errors as $err): ?>
    <div class="sp-alert sp-alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= htmlspecialchars($err) ?></span>
    </div>
    <?php endforeach; ?>
    <?php if ($success): ?>
    <div class="sp-alert sp-alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>


    <!-- ============ VISTA DETALLE ============ -->
    <?php if ($verTicketId && !empty($ticket)): ?>

        <div style="margin-bottom:1.5rem;">
            <a href="/pages/soporte.php" class="sp-btn sp-btn-ghost">
                <i class="bi bi-arrow-left"></i> Volver a mis tickets
            </a>
        </div>

        <!-- Info del ticket en dos columnas -->
        <div class="sp-detail-grid">
            <div class="sp-card">
                <div class="sp-card-header"><i class="bi bi-ticket-detailed"></i> Ticket</div>
                <div class="sp-card-body">
                    <div class="sp-info-row">
                        <div class="sp-info-label">Código</div>
                        <div class="sp-info-value" style="font-family:monospace;color:#00d4ff;font-size:1rem;"><?= htmlspecialchars($ticket['codigo']) ?></div>
                    </div>
                    <div class="sp-info-row">
                        <div class="sp-info-label">Estado</div>
                        <div><span class="sp-badge sp-badge-<?= htmlspecialchars($ticket['estado']) ?>"><?= labelEstadoU($ticket['estado']) ?></span></div>
                    </div>
                    <div class="sp-info-row">
                        <div class="sp-info-label">Prioridad</div>
                        <div><span class="sp-prio sp-prio-<?= htmlspecialchars($ticket['prioridad']) ?>"><?= labelPrioridadU($ticket['prioridad']) ?></span></div>
                    </div>
                    <div class="sp-info-row">
                        <div class="sp-info-label">Categoría</div>
                        <div class="sp-info-value"><?= ucfirst(htmlspecialchars($ticket['categoria'])) ?></div>
                    </div>
                </div>
            </div>

            <div class="sp-card">
                <div class="sp-card-header"><i class="bi bi-info-circle"></i> Detalles</div>
                <div class="sp-card-body">
                    <div class="sp-info-row">
                        <div class="sp-info-label">Asunto</div>
                        <div class="sp-info-value"><?= htmlspecialchars($ticket['asunto']) ?></div>
                    </div>
                    <div class="sp-info-row">
                        <div class="sp-info-label">Creado</div>
                        <div class="sp-info-value"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></div>
                    </div>
                    <div class="sp-info-row">
                        <div class="sp-info-label">Asignado a</div>
                        <div class="sp-info-value">
                            <?= !empty($ticket['admin_nombre'])
                                ? htmlspecialchars($ticket['admin_nombre'])
                                : '<span style="color:#4a5568;font-style:italic;">Pendiente de asignar</span>' ?>
                        </div>
                    </div>
                    <div class="sp-info-row">
                        <div class="sp-info-label">Última actualización</div>
                        <div class="sp-info-value"><?= date('d/m/Y H:i', strtotime($ticket['updated_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversación -->
        <div class="sp-chat">
            <div class="sp-card-header" style="padding:0.85rem 1.25rem;border-bottom:1px solid rgba(255,255,255,0.06);">
                <i class="bi bi-chat-dots"></i> Conversación
            </div>

            <div class="sp-chat-messages" id="spChatMessages">
                <?php if (empty($ticket['mensajes'])): ?>
                    <div class="sp-chat-empty">
                        <i class="bi bi-chat-left"></i>
                        <span>Sin mensajes aún</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($ticket['mensajes'] as $m): ?>
                        <?php
                            if ($m['es_bot']) {
                                $cls       = 'sp-msg-bot';
                                $metaLabel = '🤖 Asistente';
                            } elseif ($m['es_admin']) {
                                $cls       = 'sp-msg-admin';
                                $metaLabel = htmlspecialchars($m['usuario_nombre']) . ' (Admin)';
                            } else {
                                $cls       = 'sp-msg-user';
                                $metaLabel = 'Tú';
                            }
                        ?>
                        <div class="sp-msg <?= $cls ?>">
                            <div class="sp-msg-meta"><?= $metaLabel ?></div>
                            <div class="sp-msg-text"><?= nl2br(htmlspecialchars($m['mensaje'])) ?></div>
                            <div class="sp-msg-time"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($ticket['estado'] !== 'cerrado'): ?>
            <div class="sp-reply">
                <form method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="enviar_mensaje" value="1">
                    <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                    <label class="sp-reply-label">Tu mensaje</label>
                    <textarea name="mensaje" placeholder="Escribe tu respuesta..." required></textarea>
                    <button type="submit" class="sp-btn sp-btn-primary">
                        <i class="bi bi-send"></i> Enviar
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="sp-reply">
                <div class="sp-alert sp-alert-info" style="margin:0;">
                    <i class="bi bi-lock"></i>
                    <span>Este ticket está cerrado. Si necesitas más ayuda, abre un nuevo ticket.</span>
                </div>
            </div>
            <?php endif; ?>
        </div>


    <!-- ============ VISTA LISTA ============ -->
    <?php else: ?>

        <div class="sp-list-top">
            <p class="sp-count">
                Tienes <strong><?= count($tickets ?? []) ?></strong> ticket<?= count($tickets ?? []) !== 1 ? 's' : '' ?>
            </p>
            <button class="sp-btn sp-btn-primary" onclick="spOpenModal('spModalNuevoTicket')">
                <i class="bi bi-plus-lg"></i> Nuevo ticket
            </button>
        </div>

        <div class="sp-table-wrap">
            <table class="sp-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th style="text-align:center;">Ver</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="sp-empty">
                                <i class="bi bi-inbox"></i>
                                <p>No tienes tickets aún.</p>
                                <p style="margin-top:0.4rem;font-size:0.82rem;">Haz clic en "Nuevo ticket" para crear uno.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><span class="sp-table-code"><?= htmlspecialchars($t['codigo']) ?></span></td>
                            <td>
                                <span class="sp-table-subject"><?= htmlspecialchars($t['asunto']) ?></span>
                                <?php if (!empty($t['mensajes_no_leidos'])): ?>
                                    <span class="sp-table-unread" title="<?= (int)$t['mensajes_no_leidos'] ?> mensajes nuevos"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="sp-badge sp-badge-<?= htmlspecialchars($t['estado']) ?>">
                                    <?= labelEstadoU($t['estado']) ?>
                                </span>
                            </td>
                            <td class="sp-table-date"><?= date('d/m/Y', strtotime($t['created_at'])) ?></td>
                            <td style="text-align:center;">
                                <a href="/pages/soporte.php?ver=<?= (int)$t['id'] ?>" class="sp-btn sp-btn-primary sp-btn-sm">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($totalPaginas ?? 1) > 1): ?>
        <div class="sp-pagination">
            <?php if ($pagina > 1): ?>
            <a href="/pages/soporte.php?p=1">«</a>
            <a href="/pages/soporte.php?p=<?= $pagina - 1 ?>">‹</a>
            <?php endif; ?>

            <?php for ($p = max(1, $pagina - 2); $p <= min($totalPaginas, $pagina + 2); $p++): ?>
            <a href="/pages/soporte.php?p=<?= $p ?>" class="<?= $p === $pagina ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if ($pagina < $totalPaginas): ?>
            <a href="/pages/soporte.php?p=<?= $pagina + 1 ?>">›</a>
            <a href="/pages/soporte.php?p=<?= $totalPaginas ?>">»</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>


<!-- ============ MODAL: NUEVO TICKET ============ -->
<div class="sp-modal-overlay" id="spModalNuevoTicket">
    <div class="sp-modal">
        <div class="sp-modal-header">
            <div class="sp-modal-title"><i class="bi bi-plus-square"></i> Nuevo ticket de soporte</div>
            <button class="sp-modal-close" onclick="spCloseModal('spModalNuevoTicket')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="sp-modal-body">
            <form method="POST" id="spFormTicket">
                <?= campoCSRF() ?>
                <input type="hidden" name="crear_ticket" value="1">

                <div class="sp-fgroup">
                    <label class="sp-flabel">Categoría *</label>
                    <select name="categoria" class="sp-fselect" required>
                        <option value="compra">📦 Problema con compra</option>
                        <option value="tecnico">🔧 Problema técnico</option>
                        <option value="envio">🚚 Problema de envío</option>
                        <option value="factura">📄 Factura</option>
                        <option value="otro">❓ Otro</option>
                    </select>
                </div>

                <div class="sp-fgroup">
                    <label class="sp-flabel">Prioridad</label>
                    <select name="prioridad" class="sp-fselect">
                        <option value="baja">Baja</option>
                        <option value="normal" selected>Normal</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>

                <div class="sp-fgroup">
                    <label class="sp-flabel">Asunto *</label>
                    <input type="text" name="asunto" class="sp-finput" placeholder="Resumen breve del problema" required>
                </div>

                <div class="sp-fgroup">
                    <label class="sp-flabel">Descripción *</label>
                    <textarea name="descripcion" class="sp-ftextarea" placeholder="Describe tu problema con el mayor detalle posible..." required></textarea>
                    <div class="sp-fhint">Mínimo 20 caracteres</div>
                </div>
            </form>
        </div>
        <div class="sp-modal-footer">
            <button type="button" class="sp-btn sp-btn-ghost" onclick="spCloseModal('spModalNuevoTicket')">Cancelar</button>
            <button type="submit" form="spFormTicket" class="sp-btn sp-btn-primary">
                <i class="bi bi-send"></i> Crear ticket
            </button>
        </div>
    </div>
</div>

<script>
function spOpenModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function spCloseModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
document.querySelectorAll('.sp-modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) spCloseModal(m.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.sp-modal-overlay.active').forEach(m => spCloseModal(m.id));
});

/* Auto-scroll al final del chat */
const chat = document.getElementById('spChatMessages');
if (chat) chat.scrollTop = chat.scrollHeight;

<?php if (!empty($success) && str_contains($success, 'Ticket creado')): ?>
/* Abrir el ticket recién creado sin mostrar el modal */
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>