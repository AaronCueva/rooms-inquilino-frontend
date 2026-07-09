<?php
// Vista mis puntos Nido (W5.13). Layout main. Tokens pd-* + inline pn-.
$pn_u = $usuario ?? [];
$pn_saldo = $saldo ?? 0;
$pn_nivel = $nivel ?? ['nombre' => 'Novato', 'codigo' => 'NV1', 'proximo_nombre' => null, 'falta' => 0];
$pn_racha = $racha ?? 0;
$pn_mov = $movimientos ?? [];
$pn_pag = $pagina ?? 1;

// Barra de progreso al siguiente nivel
$pn_min_actual = 0;
if ($pn_nivel['codigo'] === 'NV2') $pn_min_actual = 1000;
elseif ($pn_nivel['codigo'] === 'NV3') $pn_min_actual = 3000;
$pn_proximo_min = $pn_nivel['proximo_nombre'] !== null ? ($pn_min_actual + $pn_nivel['falta']) : $pn_min_actual;
$pn_pct = 100;
if ($pn_nivel['proximo_nombre'] !== null && $pn_proximo_min > $pn_min_actual) {
    $pn_pct = max(0, min(100, round(($pn_saldo - $pn_min_actual) / ($pn_proximo_min - $pn_min_actual) * 100)));
}

function pn_h($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function pn_fecha($f) {
    if (!$f) return '—';
    $ts = strtotime($f);
    return $ts ? date('d M Y, H:i', $ts) : pn_h($f);
}
?>
<style>
  .pn-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .pn-head { margin-bottom: 28px; }
  .pn-head h1 { font-family: var(--pd-display); font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; margin: 6px 0 10px; }
  .pn-head .pn-sub { color: var(--pd-muted); font-size: 16px; max-width: 560px; }
  .pn-grid { display: grid; grid-template-columns: 1fr 1.4fr; gap: 24px; }
  @media (max-width: 900px) { .pn-grid { grid-template-columns: 1fr; } }
  .pn-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 26px 28px; box-shadow: var(--pd-sh-1); }
  .pn-card h2 { font-family: var(--pd-display); font-size: 19px; font-weight: 600; margin-bottom: 18px; }
  .pn-saldo { font-family: var(--pd-display); font-size: 52px; font-weight: 700; line-height: 1; margin: 6px 0 14px; }
  .pn-chip { display: inline-flex; align-items: center; background: var(--pd-sky); color: var(--pd-primary-ink); font-weight: 600; font-size: 13px; padding: 5px 12px; border-radius: 999px; }
  .pn-meta { color: var(--pd-muted); font-size: 14px; margin-top: 10px; }
  .pn-prog { margin: 18px 0 4px; }
  .pn-prog-lbl { display: flex; justify-content: space-between; font-size: 12.5px; color: var(--pd-muted); margin-bottom: 6px; }
  .pn-bar { height: 10px; background: var(--pd-line); border-radius: 999px; overflow: hidden; }
  .pn-bar > span { display: block; height: 100%; background: linear-gradient(90deg, var(--pd-primary), var(--pd-accent)); border-radius: 999px; transition: width .4s; }
  .pn-field { margin-bottom: 14px; }
  .pn-field label { font-family: var(--pd-mono); font-size: 11.5px; letter-spacing: .1em; text-transform: uppercase; color: var(--pd-muted); display: block; margin-bottom: 6px; }
  .pn-field input, .pn-field textarea { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 11px 13px; font-family: var(--pd-body); font-size: 15px; color: var(--pd-ink); width: 100%; outline: none; }
  .pn-field input:focus, .pn-field textarea:focus { border-color: var(--pd-primary); }
  .pn-btn { font-family: var(--pd-body); font-weight: 600; font-size: 15px; padding: 12px 22px; border-radius: 999px; border: none; cursor: pointer; background: var(--pd-ink); color: #fff; transition: background .2s, transform .2s; }
  .pn-btn:hover { background: var(--pd-primary); transform: translateY(-2px); }
  .pn-tbl { width: 100%; border-collapse: collapse; }
  .pn-tbl th { font-family: var(--pd-mono); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--pd-muted); text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--pd-line); }
  .pn-tbl td { padding: 12px; border-bottom: 1px solid var(--pd-line); font-size: 14px; }
  .pn-pos { color: #1f8a4c; font-weight: 600; }
  .pn-neg { color: var(--pd-accent); font-weight: 600; }
  .pn-empty { color: var(--pd-muted); font-size: 14px; font-style: italic; padding: 18px 0; }
</style>

<section class="pd-body pn-wrap">
    <div class="pn-head">
        <span class="pd-eyebrow">Gamificación</span>
        <h1>Mis puntos Nido</h1>
        <p class="pn-sub">Acumula puntos por buenas acciones en la comunidad y canjéalos por beneficios exclusivos.</p>
    </div>

    <div class="pn-grid">
        <!-- IZQUIERDA: saldo + nivel + canje -->
        <div>
            <div class="pn-card" style="margin-bottom: 22px">
                <h2>Saldo actual</h2>
                <div class="pn-saldo"><?php echo (int)$pn_saldo; ?></div>
                <span class="pn-chip"><?php echo pn_h($pn_nivel['nombre']); ?></span>
                <div class="pn-meta">Racha de pago puntual: <strong><?php echo (int)$pn_racha; ?></strong> meses</div>

                <?php if ($pn_nivel['proximo_nombre'] !== null): ?>
                    <div class="pn-prog">
                        <div class="pn-prog-lbl">
                            <span><?php echo pn_h($pn_nivel['nombre']); ?></span>
                            <span><?php echo pn_h($pn_nivel['proximo_nombre']); ?> (faltan <?php echo (int)$pn_nivel['falta']; ?>)</span>
                        </div>
                        <div class="pn-bar"><span style="width: <?php echo $pn_pct; ?>%"></span></div>
                    </div>
                <?php else: ?>
                    <div class="pn-meta" style="margin-top:14px">¡Estás en el nivel tope! 🎉</div>
                <?php endif; ?>
            </div>

            <div class="pn-card">
                <h2>Canjear puntos</h2>
                <form method="POST" action="/puntos/canjear" id="pn-canje-form">
                    <div class="pn-field">
                        <label>Puntos a canjear</label>
                        <input type="number" name="puntos_a_canjear" id="pn-cant" min="1" max="<?php echo (int)$pn_saldo; ?>" value="" placeholder="Ej. 100" required>
                    </div>
                    <div class="pn-field">
                        <label>Descripción (opcional)</label>
                        <input type="text" name="descripcion" value="" placeholder="Motivo del canje" maxlength="120">
                    </div>
                    <button type="submit" class="pn-btn">Canjear</button>
                </form>
            </div>
        </div>

        <!-- DERECHA: historial -->
        <div class="pn-card">
            <h2>Historial de movimientos</h2>
            <?php if (!empty($pn_mov)): ?>
                <table class="pn-tbl">
                    <thead>
                        <tr><th>Fecha</th><th>Descripción</th><th>Tipo</th><th style="text-align:right">Puntos</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pn_mov as $m): $p = (int)($m['puntos'] ?? 0); ?>
                            <tr>
                                <td><?php echo pn_fecha($m['fecha_creacion'] ?? null); ?></td>
                                <td><?php echo pn_h($m['descripcion'] ?? '—'); ?></td>
                                <td><span class="pn-chip" style="font-size:11px"><?php echo pn_h($m['tipo_movimiento_codigo'] ?? ''); ?></span></td>
                                <td style="text-align:right" class="<?php echo $p >= 0 ? 'pn-pos' : 'pn-neg'; ?>"><?php echo ($p >= 0 ? '+' : '') . $p; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top:16px; display:flex; gap:10px; justify-content:center">
                    <?php if ($pn_pag > 1): ?><a class="db-link" href="/puntos?page=<?php echo $pn_pag - 1; ?>" style="color:var(--pd-primary); font-weight:600">← Anterior</a><?php endif; ?>
                    <span class="pn-meta">Página <?php echo (int)$pn_pag; ?></span>
                    <?php if (count($pn_mov) === 15): ?><a class="db-link" href="/puntos?page=<?php echo $pn_pag + 1; ?>" style="color:var(--pd-primary); font-weight:600">Siguiente →</a><?php endif; ?>
                </div>
            <?php else: ?>
                <div class="pn-empty">Aún no tienes movimientos de puntos. Empieza invitando amigos o participando en la comunidad.</div>
            <?php endif; ?>
        </div>
    </div>
</section>
