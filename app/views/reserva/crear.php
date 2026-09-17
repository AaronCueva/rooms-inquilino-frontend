<?php
// Vista: Solicitud de reserva (W3.2). Layout main. Tokens pd-* + scope rv-.
$rv_a = $a;
$rv_id = $rv_a['alojamiento_id'];
$rv_titulo = htmlspecialchars($rv_a['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
$rv_foto = '';
if (!empty($rv_a['fotos']) && is_array($rv_a['fotos'])) {
    $rv_foto = $rv_a['fotos'][0]['url'] ?? '';
}
$rv_precio = (float)$precio;
$rv_garantia = (float)$garantia;
$rv_mon = htmlspecialchars($mon, ENT_QUOTES, 'UTF-8');
$rv_minFecha = $minFecha;
$rv_minDur = (int)$minDur;
$rv_fechaPre = htmlspecialchars($fechaPre, ENT_QUOTES, 'UTF-8');
$rv_mesesPre = (int)$mesesPre;
$rv_msgMin = (int)$msgMinChars;
$rv_errores = $errores ?? [];
$rv_old = $old ?? [];
$rv_old_msg = htmlspecialchars($rv_old['mensaje_presentacion'] ?? '', ENT_QUOTES, 'UTF-8');
$rv_old_met = $rv_old['metodo_pago'] ?? '';
$rv_old_obs = htmlspecialchars($rv_old['observacion'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<style>
  .rv-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .rv-head { margin-bottom: 26px; }
  .rv-head h1 { font-family: var(--pd-display); font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; margin: 6px 0 10px; }
  .rv-head .rv-sub { color: var(--pd-muted); font-size: 16px; max-width: 560px; }
  .rv-back { display: inline-flex; align-items: center; gap: 8px; color: var(--pd-muted); text-decoration: none; font-size: 14px; font-weight: 600; }
  .rv-back:hover { color: var(--pd-primary); }

  .rv-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; }
  @media (max-width: 900px) { .rv-grid { grid-template-columns: 1fr; } }

  .rv-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 26px 28px; box-shadow: var(--pd-sh-1); }
  .rv-card h2 { font-family: var(--pd-display); font-size: 20px; font-weight: 600; margin: 0 0 18px; }
  .rv-field { margin-bottom: 18px; }
  .rv-field label { font-family: var(--pd-mono); font-size: 11.5px; letter-spacing: .1em; text-transform: uppercase; color: var(--pd-muted); display: block; margin-bottom: 6px; }
  .rv-field input, .rv-field select, .rv-field textarea { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 12px 14px; font-family: var(--pd-body); font-size: 15px; color: var(--pd-ink); width: 100%; outline: none; transition: border-color .2s; box-sizing: border-box; }
  .rv-field input:focus, .rv-field select:focus, .rv-field textarea:focus { border-color: var(--pd-primary); }
  .rv-field textarea { resize: vertical; min-height: 120px; }
  .rv-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media (max-width: 560px) { .rv-row { grid-template-columns: 1fr; } }
  .rv-hint { font-size: 12.5px; color: var(--pd-muted); margin-top: 6px; }
  .rv-counter { font-size: 12.5px; color: var(--pd-muted); margin-top: 6px; text-align: right; }
  .rv-counter.is-ok { color: #2f9e44; }
  .rv-counter.is-bad { color: var(--pd-accent); }

  .rv-errors { background: #FDECEC; border: 1px solid #E57373; border-radius: var(--pd-r-sm); padding: 14px 16px; margin-bottom: 20px; color: #B23B3B; font-size: 14px; }
  .rv-errors ul { margin: 6px 0 0 18px; padding: 0; }
  .rv-errors li { margin-top: 4px; }

  .rv-stub { margin-top: 10px; padding: 12px 14px; background: var(--pd-sky); border-radius: var(--pd-r-sm); color: var(--pd-primary-ink); font-size: 13px; line-height: 1.5; }
  .rv-stub i { color: var(--pd-primary); }

  .rv-actions { display: flex; gap: 12px; margin-top: 26px; }
  .rv-btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; font-weight: 700; font-size: 15px; padding: 14px 22px; border-radius: 999px; border: none; cursor: pointer; text-decoration: none; }
  .rv-btn-primary { background: var(--pd-primary); color: #fff; box-shadow: 0 6px 18px rgba(99,102,241,0.3); }
  .rv-btn-ghost { background: transparent; color: var(--pd-ink); border: 1px solid var(--pd-line); }

  /* Sidebar resumen */
  .rv-side { position: sticky; top: 84px; }
  .rv-side-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 0; box-shadow: var(--pd-sh-1); overflow: hidden; }
  .rv-side-img { width: 100%; height: 160px; object-fit: cover; background: var(--pd-paper); display: block; }
  .rv-side-body { padding: 18px 20px; }
  .rv-side-title { font-family: var(--pd-display); font-size: 17px; font-weight: 700; margin: 0 0 6px; }
  .rv-side-link { font-size: 13px; color: var(--pd-primary); text-decoration: none; font-weight: 600; }
  .rv-side-price { font-family: var(--pd-display); font-size: 24px; font-weight: 700; margin: 10px 0 14px; }
  .rv-side-price small { font-size: 13px; color: var(--pd-muted); font-weight: 500; }
  .rv-line { display: flex; justify-content: space-between; padding: 7px 0; font-size: 14px; color: var(--pd-ink); border-bottom: 1px solid var(--pd-line); }
  .rv-line:last-child { border-bottom: none; }
  .rv-total { display: flex; justify-content: space-between; padding: 12px 0 2px; font-size: 16px; font-weight: 700; color: var(--pd-ink); }
  .rv-total span:last-child { font-family: var(--pd-display); }
  @media (max-width: 900px) { .rv-side { position: static; } }

  /* Anfitrión */
  .rv-prop { display: flex; align-items: center; gap: 12px; padding: 12px 0; margin: 14px 0; border-top: 1px solid var(--pd-line); border-bottom: 1px solid var(--pd-line); }
  .rv-prop .rv-prop-av { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; background: linear-gradient(135deg, var(--pd-primary), #7B8CFF); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 16px; flex-shrink: 0; }
  .rv-prop b { font-size: 14px; display: block; }
  .rv-prop .rv-prop-meta { font-size: 12px; color: var(--pd-muted); margin-top: 2px; }
  .rv-prop .rv-verified { display: inline-flex; align-items: center; gap: 4px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; margin-left: 6px; }
</style>

<section class="rv-wrap">
    <div class="rv-head">
        <a href="/alojamiento/<?php echo urlencode($rv_id); ?>" class="rv-back"><i class="fas fa-arrow-left"></i> Volver al alojamiento</a>
        <h1>Solicitar reserva</h1>
        <p class="rv-sub">Completa los datos de tu solicitud. El propietario tendrá 48h para responder.</p>
    </div>

    <?php if ($rv_errores): ?>
        <div class="rv-errors">
            <b><i class="fas fa-exclamation-circle"></i> Revisa lo siguiente:</b>
            <ul>
                <?php foreach ($rv_errores as $rv_e): ?>
                    <li><?php echo htmlspecialchars($rv_e, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="rv-grid">
        <!-- FORMULARIO -->
        <form class="rv-card" method="post" action="/reserva/crear" id="rvForm">
            <input type="hidden" name="alojamiento_id" value="<?php echo htmlspecialchars($rv_id, ENT_QUOTES, 'UTF-8'); ?>">
            <h2>Detalles de la reserva</h2>

            <div class="rv-row">
                <div class="rv-field">
                    <label>Fecha de ingreso</label>
                    <input type="date" name="fecha_ingreso" id="rvFecha" min="<?php echo $rv_minFecha; ?>" value="<?php echo $rv_fechaPre; ?>" required>
                    <div class="rv-hint">Mínimo 3 días desde hoy.</div>
                </div>
                <div class="rv-field">
                    <label>Duración (meses)</label>
                    <select name="duracion_meses" id="rvMeses">
                        <?php for ($m = $rv_minDur; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m === $rv_mesesPre ? 'selected' : ''; ?>><?php echo $m; ?> mes(es)</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="rv-field">
                <label>Mensaje de presentación <span style="text-transform:none;color:var(--pd-accent)">(obligatorio · mín. <?php echo $rv_msgMin; ?> caracteres)</span></label>
                <textarea name="mensaje_presentacion" id="rvMsg" placeholder="Preséntate al propietario: quién eres, qué estudias, por qué te interesa el alojamiento…"><?php echo $rv_old_msg; ?></textarea>
                <div class="rv-counter" id="rvCounter">0 / <?php echo $rv_msgMin; ?> mín.</div>
            </div>

            <div class="rv-field">
                <label>Método de pago</label>
                <select name="metodo_pago" id="rvMetodo">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($metodosPago as $rv_mp): ?>
                        <?php $rv_cod = $rv_mp['codigo']; ?>
                        <option value="<?php echo htmlspecialchars($rv_cod, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $rv_cod === $rv_old_met ? 'selected' : ''; ?>><?php echo htmlspecialchars($rv_mp['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="rv-stub"><i class="fas fa-info-circle"></i> El pago se realiza tras la aprobación del propietario. El método se registra como referencia (stub v1 — sin cobro real).</div>
            </div>

            <div class="rv-field">
                <label>Observación (opcional)</label>
                <textarea name="observacion" placeholder="Algo que el propietario deba saber (horario de llegada, acompañantes, etc.)"><?php echo $rv_old_obs; ?></textarea>
            </div>

            <div class="rv-actions">
                <button type="submit" class="rv-btn rv-btn-primary"><i class="fas fa-paper-plane"></i> Solicitar reserva</button>
                <a href="/alojamiento/<?php echo urlencode($rv_id); ?>" class="rv-btn rv-btn-ghost">Cancelar</a>
            </div>
        </form>

        <!-- RESUMEN -->
        <aside class="rv-side">
            <div class="rv-side-card">
                <?php if ($rv_foto): ?>
                    <img class="rv-side-img" src="<?php echo htmlspecialchars($rv_foto, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <?php else: ?>
                    <div class="rv-side-img" style="display:flex;align-items:center;justify-content:center;color:var(--pd-muted)"><i class="fas fa-image" style="font-size:30px"></i></div>
                <?php endif; ?>
                <div class="rv-side-body">
                    <div class="rv-side-title"><?php echo $rv_titulo; ?></div>
                    <a class="rv-side-link" href="/alojamiento/<?php echo urlencode($rv_id); ?>">Ver ficha completa</a>

                    <?php
                        $rv_propFoto = trim($rv_a['propietario_foto'] ?? '');
                        $rv_propNom  = htmlspecialchars(($rv_a['propietario_nombres'] ?? '') . ' ' . strtoupper(pd_initial($rv_a['propietario_apellido'] ?? '')) . '.', ENT_QUOTES, 'UTF-8');
                        $rv_propVerif = !empty($rv_a['propietario_verificado']) && in_array((string)$rv_a['propietario_verificado'], ['1','true','t','TRUE','T'], true);
                    ?>
                    <div class="rv-prop">
                        <?php if ($rv_propFoto !== ''): ?>
                            <img class="rv-prop-av" src="<?php echo htmlspecialchars($rv_propFoto, ENT_QUOTES, 'UTF-8'); ?>" alt="" onerror="this.style.display='none';var s=document.createElement('span');s.className='rv-prop-av';s.textContent='<?php echo htmlspecialchars(strtoupper(pd_initial($rv_a['propietario_nombres'] ?? 'A')), ENT_QUOTES, 'UTF-8'); ?>';this.parentNode.insertBefore(s,this);">
                        <?php else: ?>
                            <span class="rv-prop-av"><?php echo htmlspecialchars(strtoupper(pd_initial($rv_a['propietario_nombres'] ?? 'A'))); ?></span>
                        <?php endif; ?>
                        <div style="flex:1;min-width:0">
                            <b><?php echo $rv_propNom; ?></b><?php if ($rv_propVerif): ?> <span class="rv-verified"><i class="fas fa-check"></i> Verificado</span><?php endif; ?>
                            <div class="rv-prop-meta">Anfitrión</div>
                        </div>
                    </div>

                    <div class="rv-side-price"><?php echo $rv_mon . ' ' . number_format($rv_precio, 0, ',', '.'); ?> <small>/ mes</small></div>

                    <div id="rvResumen">
                        <div class="rv-line"><span>Alquiler (<?php echo $rv_mesesPre; ?> mes(es))</span><span data-line="alq"><?php echo $rv_mon . ' ' . number_format($rv_precio * $rv_mesesPre, 0, ',', '.'); ?></span></div>
                        <div class="rv-line"><span>Garantía</span><span><?php echo $rv_mon . ' ' . number_format($rv_garantia, 0, ',', '.'); ?></span></div>
                        <div class="rv-line"><span>Cargo plataforma (5%)</span><span data-line="cargo">—</span></div>
                        <div class="rv-total"><span>Total estimado</span><span data-line="total">—</span></div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>

<script>
(function () {
    var precio = <?php echo json_encode($rv_precio); ?>;
    var garantia = <?php echo json_encode($rv_garantia); ?>;
    var mon = <?php echo json_encode($rv_mon); ?>;
    var msgMin = <?php echo json_encode($rv_msgMin); ?>;
    var mesesSel = document.getElementById('rvMeses');
    var resumen = document.getElementById('rvResumen');
    var msg = document.getElementById('rvMsg');
    var counter = document.getElementById('rvCounter');

    function fmt(n) { return mon + ' ' + Number(n).toLocaleString('es-PE', { maximumFractionDigits: 0 }); }
    function updResumen() {
        var meses = parseInt(mesesSel.value, 10) || 1;
        var alq = precio * meses;
        var cargo = alq * 0.05;
        var total = alq + garantia + cargo;
        resumen.querySelector('[data-line=alq]').textContent = fmt(alq);
        resumen.querySelector('[data-line=cargo]').textContent = fmt(cargo);
        resumen.querySelector('[data-line=total]').textContent = fmt(total);
        resumen.querySelector('.rv-line span:first-child').textContent = 'Alquiler (' + meses + ' mes(es))';
    }
    mesesSel.addEventListener('change', updResumen); updResumen();

    function updCounter() {
        var n = (msg.value || '').trim().length;
        counter.textContent = n + ' / ' + msgMin + ' mín.';
        counter.classList.toggle('is-ok', n >= msgMin);
        counter.classList.toggle('is-bad', n > 0 && n < msgMin);
    }
    msg.addEventListener('input', updCounter); updCounter();
})();
</script>
