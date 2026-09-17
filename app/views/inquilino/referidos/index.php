<?php
// Vista: Programa de referidos (W8.2). Layout main (chrome pd-*).
// Tokens pd-* (definidos en app-design.css), prefijo fr- para scopes locales.
$fr_codigo = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
$fr_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$fr_host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fr_base   = $fr_scheme . '://' . $fr_host;
$fr_link   = $fr_base . '/register?ref=' . urlencode($codigo);
$fr_wa     = 'https://wa.me/?text=' . urlencode('Únete a Nido Universitario: ' . $fr_link);

$fr_estados = [
    'ESREF01' => ['label' => 'Pendiente',  'bg' => '#FFF7E0', 'color' => '#B7791F', 'icon' => 'fa-clock'],
    'ESREF02' => ['label' => 'Acreditado', 'bg' => '#E8FB9E', 'color' => '#1A2FB0', 'icon' => 'fa-check-circle'],
    'ESREF03' => ['label' => 'Cancelado',  'bg' => '#F1F5F9', 'color' => '#6B6F7A', 'icon' => 'fa-times-circle'],
];
?>
<style>
  .fr-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .fr-head { margin-bottom: 26px; }
  .fr-head h1 { font-family: var(--pd-display); font-size: clamp(30px, 4.4vw, 44px); font-weight: 700; letter-spacing: -.02em; line-height: 1.02; margin: 6px 0 10px; }
  .fr-head .fr-sub { color: var(--pd-muted); font-size: 16px; max-width: 620px; }

  .fr-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 22px; margin-bottom: 26px; }
  @media (max-width: 880px) { .fr-grid { grid-template-columns: 1fr; } }

  .fr-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 26px 28px; box-shadow: var(--pd-sh-1); }
  .fr-card h2 { font-family: var(--pd-display); font-size: 20px; font-weight: 700; margin: 0 0 6px; }
  .fr-card .fr-card-sub { color: var(--pd-muted); font-size: 14px; margin-bottom: 18px; }

  /* Codigo shareable */
  .fr-code-box { background: var(--pd-paper); border: 1.5px dashed var(--pd-line); border-radius: var(--pd-r-sm); padding: 22px 24px; text-align: center; margin-bottom: 18px; }
  .fr-code-label { font-family: var(--pd-mono); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--pd-muted); margin-bottom: 8px; }
  .fr-code-value { font-family: var(--pd-mono); font-size: clamp(26px, 4vw, 34px); font-weight: 700; letter-spacing: .04em; color: var(--pd-ink); }
  .fr-actions { display: flex; gap: 10px; flex-wrap: wrap; }
  .fr-actions .pd-btn { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; padding: 11px 18px; border-radius: 999px; text-decoration: none; cursor: pointer; border: none; }
  .fr-link-row { display: flex; align-items: center; gap: 10px; background: var(--pd-paper); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 10px 14px; margin-top: 16px; }
  .fr-link-row input { flex: 1; border: none; background: transparent; font-family: var(--pd-mono); font-size: 13px; color: var(--pd-ink); outline: none; min-width: 0; }
  .fr-link-row button { background: transparent; border: none; color: var(--pd-muted); cursor: pointer; padding: 4px; }
  .fr-link-row button:hover { color: var(--pd-primary); }
  .fr-explain { margin-top: 18px; padding: 14px 16px; background: var(--pd-sky); border-radius: var(--pd-r-sm); color: var(--pd-primary-ink); font-size: 14px; line-height: 1.55; }
  .fr-explain strong { color: var(--pd-primary); }

  /* Resumen */
  .fr-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .fr-stat { background: var(--pd-paper); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 18px 16px; text-align: center; }
  .fr-stat .fr-num { font-family: var(--pd-display); font-size: 30px; font-weight: 700; color: var(--pd-ink); line-height: 1; }
  .fr-stat .fr-lbl { font-size: 12.5px; color: var(--pd-muted); margin-top: 6px; }
  .fr-stat.fr-accent .fr-num { color: var(--pd-accent); }
  .fr-stat.fr-primary .fr-num { color: var(--pd-primary); }

  /* Lista referidos */
  .fr-list-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
  .fr-list-head h2 { font-family: var(--pd-display); font-size: 22px; font-weight: 700; margin: 0; }
  .fr-list-head .fr-count { font-size: 14px; color: var(--pd-muted); font-weight: 600; }
  .fr-table { width: 100%; border-collapse: collapse; }
  .fr-table th { font-family: var(--pd-mono); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--pd-muted); text-align: left; padding: 10px 14px; border-bottom: 1px solid var(--pd-line); }
  .fr-table td { padding: 14px; border-bottom: 1px solid var(--pd-line); font-size: 14.5px; color: var(--pd-ink); }
  .fr-table tr:last-child td { border-bottom: none; }
  .fr-table tr:hover td { background: var(--pd-paper); }
  .fr-name { font-weight: 600; }
  .fr-name .fr-mail { display: block; font-size: 12.5px; color: var(--pd-muted); font-weight: 400; }
  .fr-badge { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 5px 11px; border-radius: 999px; }
  .fr-empty { text-align: center; padding: 56px 20px; background: var(--pd-surface); border: 1px dashed var(--pd-line); border-radius: var(--pd-r-lg); }
  .fr-empty i { font-size: 42px; color: var(--pd-muted); opacity: .45; }
  .fr-empty h3 { font-family: var(--pd-display); font-size: 20px; margin: 14px 0 8px; }
  .fr-empty p { color: var(--pd-muted); max-width: 420px; margin: 0 auto; }
</style>

<section class="pd-body fr-wrap">
    <!-- CABECERA -->
    <div class="fr-head">
        <span class="pd-eyebrow">Programa de referidos</span>
        <h1>Invita y gana puntos Nido</h1>
        <p class="fr-sub">Comparte tu código. Cuando tu amigo se registre con él, ganas <?php echo (int)$puntosPorReferido; ?> puntos Nido.</p>
    </div>

    <!-- CÓDIGO + RESUMEN -->
    <div class="fr-grid">
        <!-- Tarjeta código shareable -->
        <div class="fr-card">
            <h2>Tu código de invitación</h2>
            <p class="fr-card-sub">Compártelo con tus amigos. Cuando se registren con este código, ganas puntos al instante.</p>

            <div class="fr-code-box">
                <div class="fr-code-label">Tu código</div>
                <div class="fr-code-value" id="frCodigo"><?php echo $fr_codigo; ?></div>
            </div>

            <div class="fr-actions">
                <button type="button" class="pd-btn pd-btn-primary" id="frCopyCodigo">
                    <i class="fas fa-copy"></i> Copiar código
                </button>
                <a href="<?php echo htmlspecialchars($fr_wa, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="pd-btn pd-btn-accent">
                    <i class="fab fa-whatsapp"></i> Compartir por WhatsApp
                </a>
            </div>

            <div class="fr-link-row">
                <i class="fas fa-link" style="color:var(--pd-muted)"></i>
                <input type="text" id="frLink" readonly value="<?php echo htmlspecialchars($fr_link, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="button" id="frCopyLink" title="Copiar enlace"><i class="fas fa-copy"></i></button>
            </div>

            <div class="fr-explain">
                <i class="fas fa-gift"></i>
                <strong>¿Cómo funciona?</strong> Tu amigo se registra en el enlace con tu código. Al completar el registro, se le acredita el bono a tu cuenta de puntos Nido automáticamente.
            </div>
        </div>

        <!-- Resumen -->
        <div class="fr-card">
            <h2>Resumen</h2>
            <p class="fr-card-sub">Tu actividad en el programa de referidos.</p>
            <div class="fr-stats">
                <div class="fr-stat">
                    <div class="fr-num"><?php echo (int)$totalReferidos; ?></div>
                    <div class="fr-lbl">Referidos totales</div>
                </div>
                <div class="fr-stat fr-primary">
                    <div class="fr-num"><?php echo (int)$totalAcreditados; ?></div>
                    <div class="fr-lbl">Acreditados</div>
                </div>
                <div class="fr-stat">
                    <div class="fr-num"><?php echo (int)$totalPendientes; ?></div>
                    <div class="fr-lbl">Pendientes</div>
                </div>
                <div class="fr-stat fr-accent">
                    <div class="fr-num"><?php echo (int)$puntosGanados; ?></div>
                    <div class="fr-lbl">Puntos ganados</div>
                </div>
            </div>
            <div style="margin-top:16px; padding:12px 14px; background:var(--pd-sky); border-radius:var(--pd-r-sm); font-size:13.5px; color:var(--pd-primary-ink);">
                <i class="fas fa-coins"></i> Puntos Nido actuales: <strong><?php echo (int)$puntosActuales; ?></strong>
            </div>
        </div>
    </div>

    <!-- LISTA DE REFERIDOS -->
    <div class="fr-card">
        <div class="fr-list-head">
            <h2>Mis referidos</h2>
            <span class="fr-count"><?php echo (int)$totalReferidos; ?> en total · <?php echo (int)$totalAcreditados; ?> acreditados</span>
        </div>

        <?php if (empty($referidos)): ?>
            <div class="fr-empty">
                <i class="fas fa-user-plus"></i>
                <h3>Aún no tienes referidos</h3>
                <p>Comparte tu código de invitación para que tus amigos se registren y empieces a ganar puntos Nido.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="fr-table">
                <thead>
                    <tr>
                        <th>Amigo referido</th>
                        <th>Código usado</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referidos as $ref):
                        $estCod = $ref['estado_codigo'] ?? '';
                        $est = $fr_estados[$estCod] ?? ['label' => $estCod, 'bg' => '#F1F5F9', 'color' => '#6B6F7A', 'icon' => 'fa-circle'];
                        $nombreCompleto = trim(($ref['referido_nombres'] ?? '') . ' ' . ($ref['referido_apellido'] ?? ''));
                        if ($nombreCompleto === '') { $nombreCompleto = 'Usuario'; }
                        $fechaTxt = !empty($ref['fecha_creacion']) ? date('d/m/Y', strtotime($ref['fecha_creacion'])) : '—';
                    ?>
                        <tr>
                            <td>
                                <span class="fr-name">
                                    <?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($ref['referido_correo'])): ?>
                                        <span class="fr-mail"><?php echo htmlspecialchars($ref['referido_correo'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td style="font-family:var(--pd-mono); font-size:13px; color:var(--pd-muted);">
                                <?php echo htmlspecialchars($ref['codigo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <span class="fr-badge" style="background:<?php echo $est['bg']; ?>; color:<?php echo $est['color']; ?>;">
                                    <i class="fas <?php echo $est['icon']; ?>"></i> <?php echo htmlspecialchars($est['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="color:var(--pd-muted);"><?php echo $fechaTxt; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    function toast(text, icon) {
        if (typeof Swal === 'undefined') { return; }
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon || 'success',
            title: text,
            showConfirmButton: false,
            timer: 2200,
            timerProgressBar: true,
            customClass: { popup: 'rounded-3 shadow-lg border-0' }
        });
    }
    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { toast('Copiado al portapapeles'); }).catch(function () { toast('No se pudo copiar', 'error'); });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); toast('Copiado al portapapeles'); } catch (e) { toast('No se pudo copiar', 'error'); }
            document.body.removeChild(ta);
        }
    }
    var btnCodigo = document.getElementById('frCopyCodigo');
    if (btnCodigo) {
        btnCodigo.addEventListener('click', function () {
            var el = document.getElementById('frCodigo');
            copyText(el ? el.textContent.trim() : '');
        });
    }
    var btnLink = document.getElementById('frCopyLink');
    if (btnLink) {
        btnLink.addEventListener('click', function () {
            var el = document.getElementById('frLink');
            copyText(el ? el.value : '');
        });
    }
})();
</script>
