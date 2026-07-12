<?php
// Vista: Mis contratos (W4). Layout main. Tokens pd-* + scope mc-.
$mc_estados = [
    'ESCO001' => ['label' => 'Activo',     'bg' => '#E8FB9E', 'color' => '#1A2FB0', 'icon' => 'fa-file-signature'],
    'ESCO002' => ['label' => 'Finalizado', 'bg' => '#F1F5F9', 'color' => '#6B6F7A', 'icon' => 'fa-flag-checkered'],
    'ESCO003' => ['label' => 'Cancelado',  'bg' => '#FDECEC', 'color' => '#B23B3B', 'icon' => 'fa-ban'],
];
$mc_mon = $monedaLabels;
$mc_total = (int)$total;
$mc_pagina = (int)$pagina;
$mc_totalPaginas = (int)$totalPaginas;
$mc_fmt = function ($n, $mon) { return $mon . ' ' . number_format((float)$n, 0, ',', '.'); };
$mc_fecha = function ($f) { if (!$f) return '—'; $ts = strtotime($f); return $ts !== false ? date('d/m/Y', $ts) : '—'; };
$mc_fechahora = function ($f) { if (!$f) return '—'; $ts = strtotime($f); return $ts !== false ? date('d/m/Y H:i', $ts) : '—'; };
?>
<style>
  .mc-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .mc-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 26px; flex-wrap: wrap; }
  .mc-head h1 { font-family: var(--pd-display); font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; margin: 6px 0 10px; }
  .mc-head .mc-sub { color: var(--pd-muted); font-size: 16px; }
  .mc-head .mc-count { font-family: var(--pd-mono); font-size: 13px; color: var(--pd-muted); }

  .mc-list { display: grid; grid-template-columns: 1fr; gap: 16px; }
  .mc-card { display: grid; grid-template-columns: 1fr auto; gap: 18px; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 20px 22px; box-shadow: var(--pd-sh-1); align-items: center; }
  @media (max-width: 640px) { .mc-card { grid-template-columns: 1fr; } }

  .mc-body { min-width: 0; }
  .mc-title { font-family: var(--pd-display); font-size: 17px; font-weight: 700; margin: 0 0 4px; }
  .mc-title a { color: var(--pd-ink); text-decoration: none; }
  .mc-title a:hover { color: var(--pd-primary); }
  .mc-meta { font-size: 13px; color: var(--pd-muted); display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 10px; }
  .mc-meta i { margin-right: 4px; }
  .mc-amount { font-family: var(--pd-display); font-size: 16px; font-weight: 700; }
  .mc-amount small { font-size: 12px; color: var(--pd-muted); font-weight: 500; }

  .mc-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
  .mc-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 5px 11px; border-radius: 999px; }
  .mc-badge-firm { background: #D7F5E0; color: #1B7A3D; }
  .mc-badge-pend { background: #FFF7E0; color: #B7791F; }

  .mc-side { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
  .mc-btn { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; padding: 9px 15px; border-radius: 999px; text-decoration: none; border: 1px solid var(--pd-line); }
  .mc-btn-primary { background: var(--pd-primary); color: #fff; border-color: var(--pd-primary); }
  .mc-btn-ghost { background: transparent; color: var(--pd-ink); }
  .mc-btn-ghost:hover { border-color: var(--pd-primary); color: var(--pd-primary); }

  .mc-empty { text-align: center; padding: 60px 20px; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); }
  .mc-empty i { font-size: 42px; color: var(--pd-muted); margin-bottom: 14px; }
  .mc-empty h2 { font-family: var(--pd-display); font-size: 20px; font-weight: 700; margin: 0 0 8px; }
  .mc-empty p { color: var(--pd-muted); font-size: 15px; margin: 0 0 18px; }
  .mc-empty a { display: inline-flex; align-items: center; gap: 8px; background: var(--pd-primary); color: #fff; font-weight: 700; font-size: 14px; padding: 11px 20px; border-radius: 999px; text-decoration: none; }

  .mc-pager { display: flex; justify-content: center; gap: 8px; margin-top: 28px; }
  .mc-pager a { padding: 8px 14px; border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); text-decoration: none; color: var(--pd-ink); font-weight: 600; font-size: 14px; }
  .mc-pager a.is-active { background: var(--pd-primary); color: #fff; border-color: var(--pd-primary); }
</style>

<section class="mc-wrap">
    <div class="mc-head">
        <div>
            <h1>Mis contratos</h1>
            <p class="mc-sub">Contratos digitales de tus alojamientos reservados.</p>
        </div>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div style="position:relative">
                <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--pd-muted);font-size:13px"></i>
                <input type="text" id="mc-search-input" placeholder="Buscar por alojamiento o estado..." style="padding:9px 14px 9px 34px;border:1px solid var(--pd-line);border-radius:999px;font-size:13.5px;outline:none;width:240px;background:var(--pd-surface);color:var(--pd-ink)" oninput="filtrarContratos(this.value)">
            </div>
            <div class="mc-count"><?php echo $mc_total; ?> contrato(s)</div>
        </div>
    </div>

    <?php if (empty($contratos)): ?>
        <div class="mc-empty">
            <i class="fas fa-file-contract"></i>
            <h2>Aún no tienes contratos</h2>
            <p>Cuando una reserva sea aprobada, el contrato aparecerá aquí.</p>
            <a href="/reservas"><i class="fas fa-calendar-check"></i> Ver mis reservas</a>
        </div>
    <?php else: ?>
        <div class="mc-list" id="mc-contrato-list">
            <?php foreach ($contratos as $mc_c):
                $mc_esFin = ($mc_c['estado_codigo'] === 'ESCO002' || $mc_c['estado_codigo'] === 'FINALIZADO' || $mc_c['estado_codigo'] === 'EC003' || strcasecmp($mc_c['estado_nombre'] ?? '', 'Finalizado') === 0);
                if ($mc_esFin) {
                    $mc_est = ['label' => 'Finalizado', 'bg' => '#E2E8F0', 'color' => '#475569', 'icon' => 'fa-flag-checkered'];
                } else {
                    $mc_est = $mc_estados[$mc_c['estado_codigo']] ?? ['label' => $mc_c['estado_nombre'] ?? $mc_c['estado_codigo'], 'bg' => '#F1F5F9', 'color' => '#6B6F7A', 'icon' => 'fa-file'];
                }
                $mc_mon_symbol = $mc_mon[$mc_c['moneda_codigo'] ?? ''] ?? 'S/';
                $mc_tienePdf = !empty($mc_c['multimedia_id']);
                $mc_firmado = !empty($mc_c['fecha_firma_inquilino']);
            ?>
                <div class="mc-card" data-texto="<?php echo htmlspecialchars(strtolower(($mc_c['alojamiento_titulo'] ?? '') . ' ' . $mc_est['label']), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mc-body">
                        <div class="mc-title">
                            <a href="/alojamiento/<?php echo urlencode($mc_c['alojamiento_id']); ?>"><?php echo htmlspecialchars($mc_c['alojamiento_titulo'] ?? '(sin título)', ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                        <div class="mc-meta">
                            <span><i class="fas fa-play"></i> Inicio: <?php echo $mc_fecha($mc_c['fecha_inicio']); ?></span>
                            <span><i class="fas fa-stop"></i> Fin: <?php echo $mc_fecha($mc_c['fecha_fin']); ?></span>
                            <span><i class="fas fa-file"></i> Creado: <?php echo $mc_fecha($mc_c['creado']); ?></span>
                        </div>
                        <div class="mc-amount"><?php echo $mc_fmt($mc_c['monto_renta'], $mc_mon_symbol); ?> <small>/ mes de renta</small></div>
                        <div class="mc-badges">
                            <span class="mc-badge" style="background:<?php echo $mc_est['bg']; ?>;color:<?php echo $mc_est['color']; ?>"><i class="fas <?php echo $mc_est['icon']; ?>"></i> <?php echo htmlspecialchars($mc_est['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($mc_firmado): ?>
                                <span class="mc-badge mc-badge-firm"><i class="fas fa-check"></i> Firmado <?php echo $mc_fecha($mc_c['fecha_firma_inquilino']); ?></span>
                            <?php else: ?>
                                <span class="mc-badge mc-badge-pend"><i class="fas fa-pen"></i> Pendiente de firma</span>
                            <?php endif; ?>
                            <?php if (!$mc_tienePdf): ?>
                                <span class="mc-badge" style="background:#F1F5F9;color:#6B6F7A"><i class="fas fa-hourglass-half"></i> PDF pendiente</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mc-side">
                        <a href="/contrato/<?php echo urlencode($mc_c['contrato_id']); ?>" class="mc-btn mc-btn-primary"><i class="fas fa-eye"></i> Ver</a>
                        <?php if ($mc_tienePdf): ?>
                            <a href="/contrato/<?php echo urlencode($mc_c['contrato_id']); ?>/pdf" class="mc-btn mc-btn-ghost" target="_blank"><i class="fas fa-download"></i> PDF</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($mc_totalPaginas > 1): ?>
            <div class="mc-pager">
                <?php for ($p = 1; $p <= $mc_totalPaginas; $p++): ?>
                    <a href="?pagina=<?php echo $p; ?>" class="<?php echo $p === $mc_pagina ? 'is-active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<script>
function filtrarContratos(q) {
    var query = (q || '').toLowerCase().trim();
    var cards = document.querySelectorAll('.mc-card');
    cards.forEach(function(c) {
        var txt = c.getAttribute('data-texto') || '';
        c.style.display = (txt.indexOf(query) !== -1) ? '' : 'none';
    });
}
</script>
