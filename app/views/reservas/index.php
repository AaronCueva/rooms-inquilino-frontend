<?php
// Vista: Mis reservas (W3.3). Layout main. Tokens pd-* + scope mr-.
$mr_estados = [
    'ESRE001' => ['label' => 'Pendiente',   'bg' => '#FFF7E0', 'color' => '#B7791F', 'icon' => 'fa-clock'],
    'ESRE002' => ['label' => 'Aprobada',    'bg' => '#E8FB9E', 'color' => '#1A2FB0', 'icon' => 'fa-check-circle'],
    'ESRE003' => ['label' => 'Rechazada',   'bg' => '#FDECEC', 'color' => '#B23B3B', 'icon' => 'fa-times-circle'],
    'ESRE004' => ['label' => 'Finalizada',  'bg' => '#F1F5F9', 'color' => '#6B6F7A', 'icon' => 'fa-flag-checkered'],
    'ESRE005' => ['label' => 'En revisión', 'bg' => '#E8F0FE', 'color' => '#1A56DB', 'icon' => 'fa-search'],
    'ESRE006' => ['label' => 'Formalizado', 'bg' => '#D7F5E0', 'color' => '#1B7A3D', 'icon' => 'fa-file-signature'],
    'ESRE007' => ['label' => 'Cancelada',   'bg' => '#F1F5F9', 'color' => '#6B6F7A', 'icon' => 'fa-ban'],
];
$mr_cancelables = $estadosCancelables;
$mr_moneda = $monedaLabels;
$mr_total = (int)$total;
$mr_pagina = (int)$pagina;
$mr_totalPaginas = (int)$totalPaginas;

$mr_fmt = function ($n, $mon) {
    return $mon . ' ' . number_format((float)$n, 0, ',', '.');
};
$mr_fecha = function ($f) {
    if (!$f) return '—';
    $ts = strtotime($f);
    return $ts !== false ? date('d/m/Y', $ts) : '—';
};
?>
<style>
  .mr-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .mr-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 26px; flex-wrap: wrap; }
  .mr-head h1 { font-family: var(--pd-display); font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; margin: 6px 0 10px; }
  .mr-head .mr-sub { color: var(--pd-muted); font-size: 16px; }
  .mr-head .mr-count { font-family: var(--pd-mono); font-size: 13px; color: var(--pd-muted); }

  .mr-list { display: grid; grid-template-columns: 1fr; gap: 16px; }
  .mr-card { display: grid; grid-template-columns: 160px 1fr auto; gap: 18px; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 18px; box-shadow: var(--pd-sh-1); align-items: center; }
  @media (max-width: 720px) { .mr-card { grid-template-columns: 1fr; } .mr-card .mr-thumb { height: 140px; } }

  .mr-thumb { width: 100%; height: 110px; object-fit: cover; border-radius: var(--pd-r-sm); background: var(--pd-paper); }
  .mr-thumb-ph { width: 100%; height: 110px; border-radius: var(--pd-r-sm); background: var(--pd-paper); display: flex; align-items: center; justify-content: center; color: var(--pd-muted); }

  .mr-body { min-width: 0; }
  .mr-title { font-family: var(--pd-display); font-size: 17px; font-weight: 700; margin: 0 0 4px; }
  .mr-title a { color: var(--pd-ink); text-decoration: none; }
  .mr-title a:hover { color: var(--pd-primary); }
  .mr-meta { font-size: 13px; color: var(--pd-muted); display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 8px; }
  .mr-meta i { margin-right: 4px; }
  .mr-amount { font-family: var(--pd-display); font-size: 16px; font-weight: 700; color: var(--pd-ink); }

  .mr-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 5px 11px; border-radius: 999px; }
  .mr-side { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
  .mr-cancel { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--pd-line); background: transparent; color: var(--pd-ink); cursor: pointer; }
  .mr-cancel:hover { border-color: var(--pd-accent); color: var(--pd-accent); }

  .mr-empty { text-align: center; padding: 60px 20px; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); }
  .mr-empty i { font-size: 42px; color: var(--pd-muted); margin-bottom: 14px; }
  .mr-empty h2 { font-family: var(--pd-display); font-size: 20px; font-weight: 700; margin: 0 0 8px; }
  .mr-empty p { color: var(--pd-muted); font-size: 15px; margin: 0 0 18px; }
  .mr-empty a { display: inline-flex; align-items: center; gap: 8px; background: var(--pd-primary); color: #fff; font-weight: 700; font-size: 14px; padding: 11px 20px; border-radius: 999px; text-decoration: none; }

  .mr-pager { display: flex; justify-content: center; gap: 8px; margin-top: 28px; }
  .mr-pager a { padding: 8px 14px; border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); text-decoration: none; color: var(--pd-ink); font-weight: 600; font-size: 14px; }
  .mr-pager a.is-active { background: var(--pd-primary); color: #fff; border-color: var(--pd-primary); }
  .mr-pager a.is-disabled { color: var(--pd-muted); pointer-events: none; opacity: .5; }
</style>

<section class="mr-wrap">
    <div class="mr-head">
        <div>
            <h1>Mis reservas</h1>
            <p class="mr-sub">Sigue el estado de tus solicitudes de alojamiento.</p>
        </div>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div style="position:relative">
                <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--pd-muted);font-size:13px"></i>
                <input type="text" id="mr-search-input" placeholder="Buscar por alojamiento o estado..." style="padding:9px 14px 9px 34px;border:1px solid var(--pd-line);border-radius:999px;font-size:13.5px;outline:none;width:240px;background:var(--pd-surface);color:var(--pd-ink)" oninput="filtrarReservas(this.value)">
            </div>
            <div class="mr-count"><?php echo $mr_total; ?> reserva(s)</div>
        </div>
    </div>

    <?php if (empty($reservas)): ?>
        <div class="mr-empty">
            <i class="fas fa-calendar-times"></i>
            <h2>Aún no tienes reservas</h2>
            <p>Cuando solicites un alojamiento, aparecerá aquí con su estado.</p>
            <a href="/buscar"><i class="fas fa-search"></i> Buscar alojamiento</a>
        </div>
    <?php else: ?>
        <div class="mr-list" id="mr-res-list">
            <?php foreach ($reservas as $mr_r):
                $mr_esFinalizado = (($mr_r['contrato_estado_codigo'] ?? '') === 'FINALIZADO' || ($mr_r['contrato_estado_codigo'] ?? '') === 'EC003' || strcasecmp($mr_r['contrato_estado_nombre'] ?? '', 'Finalizado') === 0);
                if ($mr_esFinalizado) {
                    $mr_est = ['label' => 'Finalizado', 'bg' => '#E2E8F0', 'color' => '#475569', 'icon' => 'fa-flag-checkered'];
                } else {
                    $mr_est = $mr_estados[$mr_r['estado_codigo']] ?? ['label' => $mr_r['estado_nombre'] ?? $mr_r['estado_codigo'], 'bg' => '#F1F5F9', 'color' => '#6B6F7A', 'icon' => 'fa-circle'];
                }
                $mr_mon = $mr_moneda[$mr_r['moneda_codigo'] ?? ''] ?? 'S/';
                $mr_canCancel = (!$mr_esFinalizado && in_array($mr_r['estado_codigo'], $mr_cancelables, true));
            ?>
                <div class="mr-card" data-texto="<?php echo htmlspecialchars(strtolower(($mr_r['alojamiento_titulo'] ?? '') . ' ' . $mr_est['label']), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (!empty($mr_r['foto_principal'])): ?>
                        <img class="mr-thumb" src="<?php echo htmlspecialchars($mr_r['foto_principal'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                    <?php else: ?>
                        <div class="mr-thumb-ph"><i class="fas fa-image" style="font-size:24px"></i></div>
                    <?php endif; ?>

                    <div class="mr-body">
                        <div class="mr-title">
                            <a href="/alojamiento/<?php echo urlencode($mr_r['alojamiento_id']); ?>"><?php echo htmlspecialchars($mr_r['alojamiento_titulo'] ?? '(sin título)', ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                        <div class="mr-meta">
                            <span><i class="fas fa-calendar"></i> Ingreso: <?php echo $mr_fecha($mr_r['fecha_ingreso']); ?></span>
                            <span><i class="fas fa-moon"></i> <?php echo (int)$mr_r['duracion_meses']; ?> mes(es)</span>
                            <span><i class="fas fa-paper-plane"></i> Solicitada: <?php echo $mr_fecha($mr_r['fecha_solicitud']); ?></span>
                        </div>
                        <div class="mr-amount"><?php echo $mr_fmt($mr_r['monto_total'], $mr_mon); ?> <span style="font-size:12px;color:var(--pd-muted);font-weight:500">total</span></div>
                    </div>

                    <div class="mr-side">
                        <span class="mr-badge" style="background:<?php echo $mr_est['bg']; ?>;color:<?php echo $mr_est['color']; ?>">
                            <i class="fas <?php echo $mr_est['icon']; ?>"></i> <?php echo htmlspecialchars($mr_est['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <?php if (!empty($mr_r['contrato_id']) && (in_array($mr_r['estado_codigo'], ['ESRE002', 'ESRE006'], true) || $mr_esFinalizado)): ?>
                            <a href="/contrato/<?php echo urlencode($mr_r['contrato_id']); ?>" class="mr-cancel" style="border-color:var(--pd-primary);color:var(--pd-primary)"><i class="fas fa-file-contract"></i> <?php echo $mr_esFinalizado ? 'Ver contrato finalizado' : 'Ver contrato'; ?></a>
                        <?php endif; ?>
                        <?php if ($mr_canCancel): ?>
                            <form method="post" action="/reserva/cancelar" onsubmit="return confirm('¿Cancelar esta reserva? Se calculará el reembolso según la política.')">
                                <input type="hidden" name="reserva_id" value="<?php echo htmlspecialchars($mr_r['reserva_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="mr-cancel"><i class="fas fa-times"></i> Cancelar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($mr_totalPaginas > 1): ?>
            <div class="mr-pager">
                <?php for ($p = 1; $p <= $mr_totalPaginas; $p++): ?>
                    <a href="?pagina=<?php echo $p; ?>" class="<?php echo $p === $mr_pagina ? 'is-active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<script>
function filtrarReservas(q) {
    var query = (q || '').toLowerCase().trim();
    var cards = document.querySelectorAll('.mr-card');
    cards.forEach(function(c) {
        var txt = c.getAttribute('data-texto') || '';
        c.style.display = (txt.indexOf(query) !== -1) ? '' : 'none';
    });
}
</script>
