<?php
/**
 * Vista Inbox + Conversación de chat
 * Se renderiza dentro del layout public.php (que inyecta $content en <main>).
 *
 * @var array  $chats           Lista de hilos: chat_id, otro_id, otro_nombre, otro_apellido, otro_foto, ultimo_contenido, ultimo_fecha, no_leidos
 * @var string|null $chatActivo UUID del chat abierto o null
 * @var array  $mensajes        Mensajes del chat activo: mensaje_id, contenido, fecha_envio, usuario_id, es_mio
 * @var array|null $otro        {usuario_id, nombres, apellido_paterno, url_foto} o null
 * @var string $busqueda
 * @var int    $totalNoLeidos
 * @var string $supabaseUrl
 * @var string $supabaseAnonKey
 * @var string $uid
 */

// --- Helper de fechas relativas ---
if (!function_exists('pd_fecha_hilo')) {
    function pd_fecha_hilo(?string $fecha): string {
        if ($fecha === null || $fecha === '') return '';
        $ts = strtotime($fecha);
        if ($ts === false) return '';
        $hoy = strtotime('today');
        $ayer = strtotime('yesterday');
        $dia = strtotime('today', $ts);
        if ($dia === $hoy)      return date('H:i', $ts);
        if ($dia === $ayer)     return 'Ayer';
        if ((time() - $ts) < 7 * 86400) return date('D', $ts); // día de la semana
        return date('d/m/Y', $ts);
    }
}

$hayChat   = ($chatActivo !== null && $otro !== null);
$realtime  = ($supabaseAnonKey !== '' && $chatActivo !== null);
$otroNomFull = $hayChat
    ? htmlspecialchars(($otro['nombres'] ?? '') . ' ' . ($otro['apellido_paterno'] ?? ''), ENT_QUOTES, 'UTF-8')
    : '';
$otroFoto = $hayChat ? ($otro['url_foto'] ?? '') : '';
?>

<style>
/* --- Layout 2 paneles --- */
.pd-chat-wrap {
    max-width: var(--pd-maxw);
    margin: 0 auto;
    height: calc(100vh - 220px);
    min-height: 520px;
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 0;
    background: var(--pd-surface);
    border: 1px solid var(--pd-line);
    border-radius: var(--pd-r-lg);
    overflow: hidden;
    box-shadow: var(--pd-sh-1);
    margin-top: 24px;
    margin-bottom: 32px;
}
.pd-chat-list, .pd-chat-conv { display: flex; flex-direction: column; min-height: 0; }
.pd-chat-list { border-right: 1px solid var(--pd-line); background: var(--pd-paper); }

/* --- Panel izquierdo: header --- */
.pd-chat-list-head {
    padding: 18px 18px 12px;
    border-bottom: 1px solid var(--pd-line);
    background: var(--pd-surface);
}
.pd-chat-list-head h2 {
    font-family: var(--pd-display);
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.pd-chat-badge {
    background: var(--pd-accent);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    border-radius: 999px;
    padding: 2px 9px;
    line-height: 1.4;
}
.pd-chat-search {
    display: flex;
    gap: 8px;
    align-items: center;
    background: var(--pd-paper);
    border: 1px solid var(--pd-line);
    border-radius: var(--pd-r-sm);
    padding: 6px 10px;
}
.pd-chat-search input {
    border: none;
    background: transparent;
    outline: none;
    flex: 1;
    font-size: 14px;
    color: var(--pd-ink);
    min-width: 0;
}
.pd-chat-search button {
    border: none;
    background: transparent;
    color: var(--pd-muted);
    cursor: pointer;
    padding: 2px 4px;
}
.pd-chat-search button:hover { color: var(--pd-primary); }

/* --- Lista de hilos --- */
.pd-chat-hilos { flex: 1; overflow-y: auto; }
.pd-hilo {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--pd-line);
    text-decoration: none;
    color: var(--pd-ink);
    transition: background .15s;
    align-items: center;
}
.pd-hilo:hover { background: var(--pd-sky); }
.pd-hilo.is-active {
    background: var(--pd-sky);
    box-shadow: inset 3px 0 0 var(--pd-primary);
}
.pd-avatar {
    width: 44px; height: 44px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    background: var(--pd-line);
}
.pd-avatar-fallback {
    width: 44px; height: 44px;
    border-radius: 50%;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    font-family: var(--pd-display);
    font-weight: 700;
    font-size: 18px;
    color: #fff;
    background: linear-gradient(135deg, var(--pd-primary), #7B8CFF);
}
.pd-hilo-body { flex: 1; min-width: 0; }
.pd-hilo-top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 8px;
}
.pd-hilo-nombre {
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pd-hilo-fecha { font-size: 11px; color: var(--pd-muted); flex-shrink: 0; }
.pd-hilo-msg {
    font-size: 13px;
    color: var(--pd-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}
.pd-hilo-unread {
    background: var(--pd-accent);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    border-radius: 999px;
    padding: 1px 7px;
    flex-shrink: 0;
}
.pd-chat-empty {
    padding: 40px 22px;
    text-align: center;
    color: var(--pd-muted);
    font-size: 14px;
    line-height: 1.6;
}
.pd-chat-empty i { font-size: 38px; color: var(--pd-line); display: block; margin-bottom: 14px; }

/* --- Panel derecho: conversación --- */
.pd-chat-conv { background: var(--pd-surface); }
.pd-conv-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--pd-muted);
    gap: 14px;
    padding: 40px;
    text-align: center;
}
.pd-conv-empty i { font-size: 56px; color: var(--pd-line); }
.pd-conv-empty h3 { font-family: var(--pd-display); font-size: 18px; color: var(--pd-ink); margin: 0; }
.pd-conv-empty p { font-size: 14px; max-width: 320px; margin: 0; }

.pd-conv-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--pd-line);
    background: var(--pd-surface);
}
.pd-conv-head .pd-conv-nom { font-weight: 600; font-size: 16px; }
.pd-conv-head .pd-conv-sub { font-size: 12px; color: var(--pd-muted); }
.pd-conv-back {
    display: none;
    border: none;
    background: transparent;
    color: var(--pd-ink);
    font-size: 20px;
    cursor: pointer;
    padding: 4px 8px;
}

/* --- Mensajes (bubbles) --- */
.pd-msgs {
    flex: 1;
    overflow-y: auto;
    padding: 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: var(--pd-paper);
}
.pd-msg { display: flex; flex-direction: column; max-width: 72%; }
.pd-msg.is-mio { align-self: flex-end; align-items: flex-end; }
.pd-msg.is-otro { align-self: flex-start; align-items: flex-start; }
.pd-bubble {
    padding: 10px 14px;
    border-radius: var(--pd-r-md);
    font-size: 14.5px;
    line-height: 1.45;
    word-wrap: break-word;
    overflow-wrap: anywhere;
}
.pd-msg.is-mio .pd-bubble {
    background: var(--pd-primary);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.pd-msg.is-otro .pd-bubble {
    background: var(--pd-surface);
    color: var(--pd-ink);
    border: 1px solid var(--pd-line);
    border-bottom-left-radius: 4px;
}
.pd-msg-hora { font-size: 10.5px; color: var(--pd-muted); margin-top: 4px; padding: 0 4px; }

/* --- Plantillas rápidas --- */
.pd-quick {
    display: flex;
    gap: 8px;
    padding: 10px 18px;
    border-bottom: 1px solid var(--pd-line);
    overflow-x: auto;
    background: var(--pd-surface);
}
.pd-quick-chip {
    flex-shrink: 0;
    border: 1px solid var(--pd-line);
    background: var(--pd-paper);
    color: var(--pd-ink);
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 13px;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
.pd-quick-chip:hover { border-color: var(--pd-primary); background: var(--pd-sky); }

/* --- Form de envío --- */
.pd-conv-form {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    padding: 12px 18px;
    border-top: 1px solid var(--pd-line);
    background: var(--pd-surface);
}
.pd-conv-form textarea {
    flex: 1;
    resize: none;
    border: 1px solid var(--pd-line);
    border-radius: var(--pd-r-md);
    padding: 10px 14px;
    font-family: var(--pd-body);
    font-size: 14.5px;
    color: var(--pd-ink);
    outline: none;
    max-height: 120px;
    min-height: 44px;
    background: var(--pd-paper);
}
.pd-conv-form textarea:focus { border-color: var(--pd-primary); background: var(--pd-surface); }
.pd-send-btn {
    border: none;
    background: var(--pd-primary);
    color: #fff;
    width: 44px; height: 44px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 17px;
    display: grid;
    place-items: center;
    transition: background .15s, transform .15s;
    flex-shrink: 0;
}
.pd-send-btn:hover { background: var(--pd-primary-ink); transform: translateY(-1px); }
.pd-send-btn:disabled { opacity: .5; cursor: not-allowed; }

.pd-rt-aviso {
    font-size: 11px;
    color: var(--pd-muted);
    padding: 4px 18px;
    background: var(--pd-accent-soft);
    text-align: center;
}

/* --- Responsive --- */
@media (max-width: 767.98px) {
    .pd-chat-wrap { grid-template-columns: 1fr; height: calc(100vh - 160px); margin-top: 16px; }
    .pd-chat-list { border-right: none; }
    .pd-chat-conv { display: none; }
    .pd-chat-wrap.has-active .pd-chat-list { display: none; }
    .pd-chat-wrap.has-active .pd-chat-conv { display: flex; }
    .pd-conv-back { display: inline-block; }
}
</style>

<section class="pd-chat-wrap<?= $hayChat ? ' has-active' : '' ?>" id="pdChatWrap">

    <!-- ================= PANEL IZQUIERDO: lista de hilos ================= -->
    <aside class="pd-chat-list">
        <div class="pd-chat-list-head">
            <h2>
                <i class="fas fa-comments" style="color:var(--pd-primary)"></i>
                Mensajes
                <?php if (!empty($totalNoLeidos) && $totalNoLeidos > 0): ?>
                    <span class="pd-chat-badge"><?= (int)$totalNoLeidos ?></span>
                <?php endif; ?>
            </h2>
            <form class="pd-chat-search" method="get" action="/mensajes" autocomplete="off">
                <input type="text" name="q" value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar conversaciones...">
                <button type="submit" aria-label="Buscar"><i class="fas fa-search"></i></button>
                <?php if ($chatActivo !== null): ?>
                    <input type="hidden" name="chat" value="<?= htmlspecialchars($chatActivo, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            </form>
        </div>

        <div class="pd-chat-hilos">
            <?php if (empty($chats)): ?>
                <div class="pd-chat-empty">
                    <i class="far fa-comment-dots"></i>
                    Aún no tienes conversaciones.<br>
                    Contacta a un anfitrión desde la ficha de un alojamiento.
                    <div style="margin-top:14px">
                        <a href="/buscar" class="pd-btn pd-btn-primary" style="padding:8px 16px;font-size:13px">Ir a buscar</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($chats as $c):
                    $cid = htmlspecialchars($c['chat_id'], ENT_QUOTES, 'UTF-8');
                    $activo = ($chatActivo !== null && $c['chat_id'] === $chatActivo);
                    $nombreFull = htmlspecialchars(($c['otro_nombre'] ?? '') . ' ' . ($c['otro_apellido'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $ultimo = $c['ultimo_contenido'] !== null
                        ? htmlspecialchars($c['ultimo_contenido'], ENT_QUOTES, 'UTF-8')
                        : 'Sin mensajes aún';
                    $fechaRel = pd_fecha_hilo($c['ultimo_fecha'] ?? null);
                    $noLeidos = (int)($c['no_leidos'] ?? 0);
                ?>
                    <a href="/mensajes?chat=<?= $cid ?>" class="pd-hilo<?= $activo ? ' is-active' : '' ?>">
                        <?php if (!empty($c['otro_foto'])): ?>
                            <img src="<?= htmlspecialchars($c['otro_foto'], ENT_QUOTES, 'UTF-8') ?>" class="pd-avatar" alt="">
                        <?php else: ?>
                            <div class="pd-avatar-fallback"><?= htmlspecialchars(pd_initial($c['otro_nombre'] ?? '?'), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <div class="pd-hilo-body">
                            <div class="pd-hilo-top">
                                <span class="pd-hilo-nombre"><?= $nombreFull ?></span>
                                <?php if ($fechaRel !== ''): ?>
                                    <span class="pd-hilo-fecha"><?= htmlspecialchars($fechaRel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="pd-hilo-msg">
                                <span><?= $ultimo ?></span>
                                <?php if ($noLeidos > 0): ?>
                                    <span class="pd-hilo-unread"><?= $noLeidos ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ================= PANEL DERECHO: conversación ================= -->
    <section class="pd-chat-conv">
        <?php if (!$hayChat): ?>
            <div class="pd-conv-empty">
                <i class="far fa-comment"></i>
                <h3>Selecciona una conversación</h3>
                <p>Elige un hilo de la izquierda para ver los mensajes y chatear con el anfitrión.</p>
            </div>
        <?php else: ?>
            <!-- Header de la conversación -->
            <div class="pd-conv-head">
                <button class="pd-conv-back" onclick="document.getElementById('pdChatWrap').classList.remove('has-active')" aria-label="Volver">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <?php if (!empty($otroFoto)): ?>
                    <img src="<?= htmlspecialchars($otroFoto, ENT_QUOTES, 'UTF-8') ?>" class="pd-avatar" alt="">
                <?php else: ?>
                    <div class="pd-avatar-fallback"><?= htmlspecialchars(pd_initial($otro['nombres'] ?? '?'), ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div>
                    <div class="pd-conv-nom"><?= $otroNomFull ?></div>
                    <div class="pd-conv-sub">Conversación privada</div>
                </div>
            </div>

            <?php if (!$realtime): ?>
                <div class="pd-rt-aviso">
                    <i class="fas fa-info-circle"></i>
                    Realtime deshabilitado (falta anon key) — usa recargar para ver nuevos.
                </div>
            <?php endif; ?>

            <!-- Plantillas rápidas -->
            <div class="pd-quick">
                <button type="button" class="pd-quick-chip" data-text="Hola, ¿sigue disponible?" onclick="var t=document.getElementById('msgInput');t.value=this.dataset.text;t.focus();">Hola, ¿sigue disponible?</button>
                <button type="button" class="pd-quick-chip" data-text="¿Puedo visitar el lugar?" onclick="var t=document.getElementById('msgInput');t.value=this.dataset.text;t.focus();">¿Puedo visitar el lugar?</button>
                <button type="button" class="pd-quick-chip" data-text="¿Qué servicios incluye?" onclick="var t=document.getElementById('msgInput');t.value=this.dataset.text;t.focus();">¿Qué servicios incluye?</button>
            </div>

            <!-- Mensajes -->
            <div class="pd-msgs" id="pdMsgs">
                <?php foreach ($mensajes as $m):
                    $esMio = !empty($m['es_mio']);
                    $contenido = htmlspecialchars($m['contenido'] ?? '', ENT_QUOTES, 'UTF-8');
                    $hora = !empty($m['fecha_envio']) ? date('H:i', strtotime($m['fecha_envio'])) : '';
                ?>
                    <div class="pd-msg <?= $esMio ? 'is-mio' : 'is-otro' ?>" data-id="<?= htmlspecialchars($m['mensaje_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="pd-bubble"><?= $contenido ?></div>
                        <?php if ($hora !== ''): ?>
                            <div class="pd-msg-hora"><?= $hora ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Form de envío -->
            <form class="pd-conv-form" id="pdConvForm" onsubmit="return false;">
                <input type="hidden" name="chat_id" value="<?= htmlspecialchars($chatActivo, ENT_QUOTES, 'UTF-8') ?>">
                <textarea name="contenido" id="msgInput" maxlength="2000" placeholder="Escribe un mensaje..." rows="1"></textarea>
                <button type="button" class="pd-send-btn" id="pdSendBtn" aria-label="Enviar">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        <?php endif; ?>
    </section>

</section>

<?php if ($realtime): ?>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<?php endif; ?>

<?php if ($hayChat): ?>
<script>
(function () {
    var chatId = <?= json_encode($chatActivo) ?>;
    var uid    = <?= json_encode($uid) ?>;
    var realtime = <?= json_encode($realtime) ?>;
    var msgsEl   = document.getElementById('pdMsgs');
    var textarea = document.getElementById('msgInput');
    var sendBtn  = document.getElementById('pdSendBtn');

    // --- Utilidades ---
    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function scrollAbajo() {
        if (msgsEl) msgsEl.scrollTop = msgsEl.scrollHeight;
    }
    function ultimoId() {
        var els = msgsEl ? msgsEl.querySelectorAll('.pd-msg[data-id]') : null;
        if (!els || !els.length) return null;
        return els[els.length - 1].getAttribute('data-id');
    }

    function appendMensaje(m) {
        if (!msgsEl) return;
        // Evitar duplicados
        if (m.mensaje_id && msgsEl.querySelector('.pd-msg[data-id="' + m.mensaje_id + '"]')) return;
        var esMio = (m.usuario_id === uid) || (m.es_mio === true);
        var wrap = document.createElement('div');
        wrap.className = 'pd-msg ' + (esMio ? 'is-mio' : 'is-otro');
        if (m.mensaje_id) wrap.setAttribute('data-id', m.mensaje_id);
        var bub = document.createElement('div');
        bub.className = 'pd-bubble';
        bub.textContent = m.contenido == null ? '' : String(m.contenido);
        wrap.appendChild(bub);
        if (m.fecha_envio) {
            var h = new Date(m.fecha_envio);
            var hh = String(h.getHours()).padStart(2, '0');
            var mm = String(h.getMinutes()).padStart(2, '0');
            var horaEl = document.createElement('div');
            horaEl.className = 'pd-msg-hora';
            horaEl.textContent = hh + ':' + mm;
            wrap.appendChild(horaEl);
        }
        msgsEl.appendChild(wrap);
        scrollAbajo();
    }

    // --- Envío ---
    function enviar() {
        var contenido = textarea.value.trim();
        if (contenido === '') return;
        sendBtn.disabled = true;
        fetch('/mensajes/enviar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'chat_id=' + encodeURIComponent(chatId) + '&contenido=' + encodeURIComponent(contenido)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            sendBtn.disabled = false;
            if (data && data.ok && data.mensaje) {
                appendMensaje(data.mensaje);
                textarea.value = '';
                textarea.style.height = 'auto';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo enviar',
                    text: (data && data.error) ? data.error : 'Intenta de nuevo.',
                    toast: true, position: 'top-end',
                    showConfirmButton: false, timer: 3000
                });
            }
        })
        .catch(function () {
            sendBtn.disabled = false;
            Swal.fire({
                icon: 'error', title: 'Error de red',
                text: 'Verifica tu conexión.', toast: true, position: 'top-end',
                showConfirmButton: false, timer: 3000
            });
        });
    }
    sendBtn.addEventListener('click', enviar);
    textarea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviar(); }
    });
    // Auto-resize del textarea
    textarea.addEventListener('input', function () {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    });

    // --- Polling continuo (garantiza recepción instantánea sin F5) ---
    var pollTimer = null;
    function iniciarPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(function () {
            var ult = ultimoId();
            fetch('/mensajes/nuevo?chat=' + encodeURIComponent(chatId) + '&ultimo=' + encodeURIComponent(ult || ''))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok && Array.isArray(data.mensajes)) {
                        data.mensajes.forEach(appendMensaje);
                    }
                })
                .catch(function () {});
        }, 2500);
    }

    // --- Realtime (Supabase opcional) + Polling continuo ---
    scrollAbajo();
    iniciarPolling(); // Arrancar SIEMPRE el polling para no perder ningún mensaje

    if (realtime && typeof supabase !== 'undefined') {
        try {
            var sb = supabase.createClient(
                <?= json_encode($supabaseUrl) ?>,
                <?= json_encode($supabaseAnonKey) ?>
            );
            sb.channel('chat:' + chatId)
              .on('postgres_changes',
                  { event: 'INSERT', schema: 'public', table: 'mensaje', filter: 'chat_id=eq.' + chatId },
                  function (payload) { appendMensaje(payload.new); })
              .subscribe();
        } catch (e) { /* SDK no disponible */ }
    }
})();
</script>
<?php endif;
