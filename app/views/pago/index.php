<?php
/**
 * Vista de mis Pagos y Pasarela de Pago Simulada
 * Variables disponibles: $pagos, $totalesPorMoneda, $cuotasPendientes, $proximaCuota
 *
 * Paleta alineada al design system (ultramarine --pd-primary / coral --pd-accent).
 * CSS scoped con prefijo `pg-` para no colisionar con el menú ni el resto de la app.
 */
use App\Models\Pago;
?>
<style>
  .pg-wrap { max-width: var(--pd-maxw, 1180px); margin: 0 auto; padding: 0 4px; }

  /* Header */
  .pg-header {
    background: linear-gradient(135deg, var(--pd-ink, #0F1115) 0%, var(--pd-primary-ink, #1A2FB0) 55%, var(--pd-primary, #2A44FF) 100%);
    border-radius: var(--pd-r-lg, 22px);
    padding: 36px 32px;
    color: #fff;
    margin-bottom: 28px;
    box-shadow: var(--pd-sh-2, 0 18px 50px -20px rgba(15,17,21,.30));
    position: relative;
    overflow: hidden;
  }
  .pg-header::after {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 260px; height: 260px;
    background: radial-gradient(circle, rgba(255,255,255,0.16) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
  }
  .pg-eyebrow {
    background: rgba(255,255,255,0.18);
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .5px;
    display: inline-block;
    margin-bottom: 12px;
  }
  .pg-header h1 { font-family: var(--pd-display, 'Space Grotesk', sans-serif); font-size: 30px; font-weight: 700; margin: 0 0 8px 0; line-height: 1.2; }
  .pg-header p  { font-size: 15px; opacity: .92; margin: 0; line-height: 1.6; max-width: 620px; }

  /* Stats */
  .pg-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 28px; }
  .pg-stat-card {
    background: var(--pd-surface, #fff);
    border: 1px solid var(--pd-line, #E4E0D6);
    border-radius: var(--pd-r-lg, 22px);
    padding: 20px 22px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: var(--pd-sh-1);
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .pg-stat-card:hover { transform: translateY(-2px); box-shadow: var(--pd-sh-2); }
  .pg-stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
  .pg-stat-icon.primary { background: var(--pd-sky, #E9F0FF); color: var(--pd-primary, #2A44FF); }
  .pg-stat-icon.success { background: #dcfce7; color: #16a34a; }
  .pg-stat-icon.warning { background: #fef9c3; color: #ca8a04; }
  .pg-stat-value { font-family: var(--pd-display); font-size: 22px; font-weight: 700; color: var(--pd-ink, #0F1115); line-height: 1.2; }
  .pg-stat-sub   { font-size: 13px; color: var(--pd-muted, #6B6F7A); margin-top: 2px; }
  .pg-stat-label { font-size: 12px; color: var(--pd-muted, #6B6F7A); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }

  /* Tabla de cuotas */
  .pg-table-card {
    background: var(--pd-surface, #fff);
    border: 1px solid var(--pd-line, #E4E0D6);
    border-radius: var(--pd-r-lg, 22px);
    overflow: hidden;
    box-shadow: var(--pd-sh-1);
  }
  .pg-table-head { padding: 20px 24px; border-bottom: 1px solid var(--pd-line, #E4E0D6); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .pg-table-head h3 { margin: 0; font-family: var(--pd-display); font-size: 17px; font-weight: 700; color: var(--pd-ink); }
  .pg-table-head .pg-count { font-size: 13px; color: var(--pd-muted); font-weight: 600; }

  .pg-cuota-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 24px; border-bottom: 1px solid var(--pd-line, #E4E0D6); transition: background .15s ease; }
  .pg-cuota-row:last-child { border-bottom: none; }
  .pg-cuota-row:hover { background: var(--pd-paper, #F6F4EE); }
  .pg-cuota-info { display: flex; align-items: center; gap: 14px; min-width: 0; }
  .pg-cuota-num { width: 42px; height: 42px; border-radius: 12px; background: var(--pd-paper, #F6F4EE); color: var(--pd-muted); font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .pg-cuota-num.pagado { background: #dcfce7; color: #16a34a; }
  .pg-cuota-num.vencido { background: #fee2e2; color: #b91c1c; }
  .pg-cuota-titulo { font-weight: 700; font-size: 15px; color: var(--pd-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .pg-cuota-meta  { font-size: 13px; color: var(--pd-muted); margin-top: 3px; display: flex; flex-wrap: wrap; gap: 6px 10px; align-items: center; }
  .pg-cuota-ref   { font-family: var(--pd-mono, monospace); background: var(--pd-paper, #F6F4EE); padding: 2px 7px; border-radius: 6px; font-size: 12px; }
  .pg-cuota-venc  { color: #b91c1c; font-weight: 700; }

  .pg-cuota-side { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
  .pg-cuota-monto { text-align: right; }
  .pg-cuota-monto .pg-monto-val { font-size: 17px; font-weight: 700; color: var(--pd-ink); font-family: var(--pd-display); }
  .pg-cuota-monto .pg-monto-sub { margin-top: 3px; }

  .pg-badge { padding: 4px 11px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; display: inline-flex; align-items: center; gap: 4px; }
  .pg-badge.pendiente  { background: #fef9c3; color: #854d0e; }
  .pg-badge.completado { background: #dcfce7; color: #166534; }
  .pg-badge.fallido    { background: #fee2e2; color: #991b1b; }
  .pg-badge.metodo     { background: var(--pd-sky, #E9F0FF); color: var(--pd-primary-ink, #1A2FB0); text-transform: none; }

  .pg-btn-pagar {
    background: linear-gradient(135deg, var(--pd-primary, #2A44FF) 0%, var(--pd-primary-ink, #1A2FB0) 100%);
    color: #fff; border: none; padding: 10px 18px; border-radius: 12px;
    font-weight: 700; font-size: 14px; cursor: pointer;
    box-shadow: 0 6px 16px -6px rgba(42,68,255,.55);
    transition: transform .2s ease, box-shadow .2s ease;
    display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;
  }
  .pg-btn-pagar:hover { transform: translateY(-1px); box-shadow: 0 10px 22px -8px rgba(42,68,255,.65); }
  .pg-btn-pagar:disabled { opacity: .6; cursor: not-allowed; transform: none; }

  .pg-empty { padding: 56px 24px; text-align: center; color: var(--pd-muted); }
  .pg-empty .pg-empty-ico { font-size: 46px; margin-bottom: 10px; }
  .pg-empty h4 { font-family: var(--pd-display); font-size: 17px; color: var(--pd-ink); margin: 0 0 8px 0; }
  .pg-empty p  { font-size: 14px; max-width: 420px; margin: 0 auto; }

  /* Modal */
  .pg-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(15,17,21,.62);
    backdrop-filter: blur(8px);
    z-index: 99999;
    display: flex; align-items: center; justify-content: center; padding: 18px;
    opacity: 0; pointer-events: none; transition: opacity .25s ease;
  }
  .pg-modal-overlay.active { opacity: 1; pointer-events: auto; }
  .pg-modal-card {
    background: var(--pd-surface, #fff);
    border-radius: 24px; width: 100%; max-width: 520px; max-height: 92vh; overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,.25);
    transform: scale(.95); transition: transform .25s cubic-bezier(.16,1,.3,1);
  }
  .pg-modal-overlay.active .pg-modal-card { transform: scale(1); }
  .pg-modal-header { background: var(--pd-paper, #F6F4EE); padding: 22px 26px; border-bottom: 1px solid var(--pd-line); display: flex; align-items: center; justify-content: space-between; }
  .pg-modal-title { font-family: var(--pd-display); font-size: 17px; font-weight: 700; color: var(--pd-ink); display: flex; align-items: center; gap: 10px; }
  .pg-modal-close { background: none; border: none; font-size: 24px; color: var(--pd-muted); cursor: pointer; line-height: 1; padding: 0 4px; border-radius: 8px; }
  .pg-modal-close:hover { color: var(--pd-ink); background: rgba(0,0,0,.05); }

  .pg-tabs { display: flex; background: var(--pd-paper, #F6F4EE); padding: 6px; border-radius: 14px; margin: 22px 26px 16px; gap: 4px; }
  .pg-tab-btn { flex: 1; border: none; background: transparent; padding: 10px 10px; border-radius: 10px; font-size: 13px; font-weight: 700; color: var(--pd-muted); cursor: pointer; transition: all .15s ease; }
  .pg-tab-btn.active { background: #fff; color: var(--pd-primary, #2A44FF); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
  .pg-tab-pane { display: none; padding: 0 26px 22px; }
  .pg-tab-pane.active { display: block; }

  .pg-field { margin-bottom: 14px; }
  .pg-label { display: block; font-size: 13px; font-weight: 700; color: var(--pd-ink); margin-bottom: 6px; }
  .pg-input { width: 100%; padding: 12px 14px; border: 1px solid var(--pd-line); border-radius: 12px; font-size: 14px; outline: none; transition: border-color .2s, box-shadow .2s; box-sizing: border-box; background: #fff; }
  .pg-input:focus { border-color: var(--pd-primary, #2A44FF); box-shadow: 0 0 0 3px rgba(42,68,255,.12); }
  .pg-qr-box { text-align: center; background: var(--pd-paper, #F6F4EE); border: 2px dashed var(--pd-line); border-radius: 16px; padding: 20px; margin-bottom: 14px; }
  .pg-bank-box { background: var(--pd-paper, #F6F4EE); border-radius: 14px; padding: 14px 16px; margin-bottom: 14px; border: 1px solid var(--pd-line); font-size: 13px; color: var(--pd-ink); }
  .pg-bank-box strong { color: var(--pd-ink); }

  .pg-modal-foot { padding: 18px 26px; background: var(--pd-paper, #F6F4EE); border-top: 1px solid var(--pd-line); display: flex; align-items: center; justify-content: space-between; gap: 12px; position: sticky; bottom: 0; }
  .pg-total-label { font-size: 12px; color: var(--pd-muted); }
  .pg-total-val { font-family: var(--pd-display); font-size: 22px; font-weight: 700; color: var(--pd-primary, #2A44FF); }

  /* Responsive */
  @media (max-width: 720px) {
    .pg-header { padding: 26px 20px; border-radius: 18px; }
    .pg-header h1 { font-size: 24px; }
    .pg-cuota-row { flex-direction: column; align-items: flex-start; gap: 12px; padding: 16px 18px; }
    .pg-cuota-side { width: 100%; justify-content: space-between; }
    .pg-cuota-titulo { white-space: normal; }
    .pg-modal-card { max-height: 100vh; border-radius: 18px; }
    .pg-modal-foot { flex-direction: column-reverse; align-items: stretch; }
    .pg-modal-foot .pg-btn-pagar { justify-content: center; }
  }
</style>

<div class="pg-wrap">
  <div class="pg-header">
    <div>
      <span class="pg-eyebrow">💳 MÓDULO DE PAGOS UNIVERSITARIOS</span>
      <h1>Mis Cuotas y Alquileres</h1>
      <p>Administra tus cuotas mensuales y realiza pagos al instante mediante nuestra pasarela digital segura.</p>
    </div>
  </div>

  <div class="pg-stat-grid">
    <div class="pg-stat-card">
      <div class="pg-stat-icon warning">🕒</div>
      <div>
        <div class="pg-stat-label">Próxima Cuota</div>
        <?php if ($proximaCuota): ?>
          <?php $simbProx = ($proximaCuota['moneda_codigo'] === 'TPM002') ? 'US$' : 'S/'; ?>
          <div class="pg-stat-value"><?= $simbProx ?> <?= number_format((float)$proximaCuota['monto'], 2) ?></div>
          <div class="pg-stat-sub">Vence <?= date('d/m/Y', strtotime($proximaCuota['fecha_vencimiento'])) ?></div>
        <?php else: ?>
          <div class="pg-stat-value">—</div>
          <div class="pg-stat-sub">Sin cuotas pendientes</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="pg-stat-card">
      <div class="pg-stat-icon success">✓</div>
      <div>
        <div class="pg-stat-label">Total Pagado</div>
        <?php if (!empty($totalesPorMoneda)): ?>
          <?php foreach ($totalesPorMoneda as $simb => $total): ?>
            <div class="pg-stat-value"><?= htmlspecialchars($simb) ?> <?= number_format((float)$total, 2) ?></div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="pg-stat-value">S/ 0.00</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="pg-stat-card">
      <div class="pg-stat-icon primary">📅</div>
      <div>
        <div class="pg-stat-label">Cuotas Pendientes</div>
        <div class="pg-stat-value"><?= (int)$cuotasPendientes ?></div>
        <div class="pg-stat-sub"><?= count($pagos) ?> cuotas en total</div>
      </div>
    </div>
  </div>

  <div class="pg-table-card">
    <div class="pg-table-head">
      <h3>Historial y Cronograma de Cuotas</h3>
      <span class="pg-count"><?= count($pagos) ?> cuotas registradas</span>
    </div>

    <?php if (empty($pagos)): ?>
      <div class="pg-empty">
        <div class="pg-empty-ico">📑</div>
        <h4>No tienes cuotas programadas aún</h4>
        <p>Al contar con un contrato activo de alojamiento universitario, tu cronograma mensual se mostrará automáticamente aquí.</p>
      </div>
    <?php else: ?>
      <?php foreach ($pagos as $p):
        $esPagado    = ($p['estado_codigo'] === Pago::EST_COMPLETADO);
        $esFallido   = ($p['estado_codigo'] === Pago::EST_FALLIDO);
        $simboloMoneda = ($p['moneda_codigo'] === 'TPM002') ? 'US$' : 'S/';
        $hoy = strtotime(date('Y-m-d'));
        $vencTs = strtotime($p['fecha_vencimiento']);
        $estaVencido = !$esPagado && $vencTs !== false && $vencTs < $hoy;
      ?>
        <div class="pg-cuota-row">
          <div class="pg-cuota-info">
            <div class="pg-cuota-num <?= $esPagado ? 'pagado' : ($estaVencido ? 'vencido' : '') ?>">
              <?= $esPagado ? '✓' : '#' . (int)$p['numero_cuota'] ?>
            </div>
            <div style="min-width:0">
              <div class="pg-cuota-titulo">
                Cuota Nº <?= (int)$p['numero_cuota'] ?> — <?= htmlspecialchars($p['alojamiento_titulo'] ?? 'Alojamiento') ?>
              </div>
              <div class="pg-cuota-meta">
                <span>Vence: <strong class="<?= $estaVencido ? 'pg-cuota-venc' : '' ?>"><?= date('d/m/Y', $vencTs) ?></strong></span>
                <?php if ($esPagado && !empty($p['fecha_pago'])): ?>
                  <span>| Pagado: <?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($p['referencia_externa'])): ?>
                  <span class="pg-cuota-ref"><?= htmlspecialchars($p['referencia_externa']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="pg-cuota-side">
            <div class="pg-cuota-monto">
              <div class="pg-monto-val"><?= $simboloMoneda ?> <?= number_format((float)$p['monto'], 2) ?></div>
              <div class="pg-monto-sub">
                <?php if ($esPagado): ?>
                  <span class="pg-badge completado">✓ Pagado</span>
                  <?php if (!empty($p['metodo_nombre'])): ?>
                    <span class="pg-badge metodo" style="margin-left:6px"><?= htmlspecialchars($p['metodo_nombre']) ?></span>
                  <?php endif; ?>
                <?php elseif ($esFallido): ?>
                  <span class="pg-badge fallido">Fallido</span>
                <?php else: ?>
                  <span class="pg-badge pendiente"><?= $estaVencido ? 'Vencida' : 'Pendiente' ?></span>
                <?php endif; ?>
              </div>
            </div>

            <?php if (!$esPagado): ?>
              <button type="button" class="pg-btn-pagar pg-btn-abrir"
                      data-pago-id="<?= htmlspecialchars($p['pago_id'], ENT_QUOTES) ?>"
                      data-num-cuota="<?= (int)$p['numero_cuota'] ?>"
                      data-monto="<?= htmlspecialchars($simboloMoneda . ' ' . number_format((float)$p['monto'], 2), ENT_QUOTES) ?>"
                      data-titulo="<?= htmlspecialchars($p['alojamiento_titulo'] ?? 'Alojamiento', ENT_QUOTES) ?>">
                💳 Pagar
              </button>
            <?php else: ?>
              <span class="pg-badge completado" style="padding:8px 14px">Comprobante listo</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- MODAL PASARELA DE PAGO SIMULADA -->
<div id="modalPasarelaPago" class="pg-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="pgModalTitulo">
  <div class="pg-modal-card">
    <div class="pg-modal-header">
      <div class="pg-modal-title">
        <span style="font-size:22px">🔒</span>
        <div>
          <span id="pgModalTitulo">Pasarela de Pago Segura</span>
          <div id="modalPagoSubtitulo" style="font-size:12px; color:var(--pd-muted); font-weight:600">Cuota de Alquiler</div>
        </div>
      </div>
      <button type="button" class="pg-modal-close" onclick="cerrarModalPago()" aria-label="Cerrar">&times;</button>
    </div>

    <div class="pg-tabs">
      <button type="button" class="pg-tab-btn active" onclick="cambiarPestañaPago('tarjeta', this)">💳 Tarjeta</button>
      <button type="button" class="pg-tab-btn" onclick="cambiarPestañaPago('yape', this)">📱 Yape/Plin</button>
      <button type="button" class="pg-tab-btn" onclick="cambiarPestañaPago('transf', this)">🏦 Transferencia</button>
    </div>

    <!-- Tarjeta -->
    <div id="tab-tarjeta" class="pg-tab-pane active">
      <div class="pg-field">
        <label class="pg-label">Número de Tarjeta</label>
        <input type="text" id="inputTarjeta" class="pg-input" placeholder="4557 8800 1234 5678" value="4557 8899 0011 2233" inputmode="numeric" autocomplete="cc-number">
      </div>
      <div style="display:flex; gap:12px">
        <div class="pg-field" style="flex:1">
          <label class="pg-label">Vencimiento</label>
          <input type="text" class="pg-input" placeholder="MM/AA" value="08/29" autocomplete="cc-exp">
        </div>
        <div class="pg-field" style="flex:1">
          <label class="pg-label">CVV</label>
          <input type="password" class="pg-input" placeholder="123" value="482" autocomplete="cc-csc">
        </div>
      </div>
      <div class="pg-field">
        <label class="pg-label">Titular de la Tarjeta</label>
        <input type="text" class="pg-input" placeholder="Nombre completo" value="ESTUDIANTE UNIVERSITARIO" autocomplete="cc-name">
      </div>
    </div>

    <!-- Yape / Plin -->
    <div id="tab-yape" class="pg-tab-pane">
      <div class="pg-qr-box">
        <div style="font-size:50px; margin-bottom:6px">📲</div>
        <div style="font-weight:700; color:var(--pd-ink); font-size:15px">Escanea con Yape o Plin</div>
        <div style="font-size:13px; color:var(--pd-muted)">Número oficial Rooms: <strong>+51 987 654 321</strong></div>
      </div>
      <div class="pg-field">
        <label class="pg-label">Código u Operación de Yape/Plin</label>
        <input type="text" id="inputReferenciaYape" class="pg-input" placeholder="Ej. 827361" value="YP-882319">
      </div>
    </div>

    <!-- Transferencia -->
    <div id="tab-transf" class="pg-tab-pane">
      <div class="pg-bank-box">
        <div><strong>Banco BCP:</strong> 193-98765432-0-12</div>
        <div style="margin-top:4px"><strong>CCI Interbancario:</strong> 00219300987654320121</div>
      </div>
      <div class="pg-field">
        <label class="pg-label">Nro. Operación o Comprobante</label>
        <input type="text" id="inputReferenciaTransf" class="pg-input" placeholder="Ej. TR-20260709" value="TR-998811">
      </div>
    </div>

    <div class="pg-modal-foot">
      <div>
        <div class="pg-total-label">Total a Pagar</div>
        <div id="modalPagoMontoTotal" class="pg-total-val">S/ 0.00</div>
      </div>
      <button type="button" id="btnProcesarPago" class="pg-btn-pagar" style="padding:14px 26px; font-size:15px">
        ⚡ Confirmar Pago Digital
      </button>
    </div>
  </div>
</div>

<script>
(function () {
  let pagoIdActual = null;
  let metodoPagoActual = 'MPG002'; // Tarjeta por defecto
  const modal = document.getElementById('modalPasarelaPago');
  const btnProcesar = document.getElementById('btnProcesarPago');
  let ultimoFoco = null;

  // Formato de número de tarjeta (grupos de 4)
  const inputTarjeta = document.getElementById('inputTarjeta');
  inputTarjeta.addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 16);
    this.value = v.replace(/(.{4})/g, '$1 ').trim();
  });

  function abrirModalPago(pagoId, numCuota, montoStr, alojamientoTitulo) {
    pagoIdActual = pagoId;
    document.getElementById('modalPagoSubtitulo').textContent = 'Cuota Nº ' + numCuota + ' — ' + alojamientoTitulo;
    document.getElementById('modalPagoMontoTotal').textContent = montoStr;
    ultimoFoco = document.activeElement;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => { const c = modal.querySelector('.pg-modal-close'); if (c) c.focus(); }, 50);
  }
  window.abrirModalPago = abrirModalPago;

  function cerrarModalPago() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
    if (ultimoFoco && typeof ultimoFoco.focus === 'function') ultimoFoco.focus();
  }
  window.cerrarModalPago = cerrarModalPago;

  modal.addEventListener('click', function (e) { if (e.target === modal) cerrarModalPago(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('active')) cerrarModalPago();
  });

  function cambiarPestañaPago(pestaña, btn) {
    document.querySelectorAll('.pg-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.pg-tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + pestaña).classList.add('active');
    if (pestaña === 'tarjeta') metodoPagoActual = 'MPG002';
    else if (pestaña === 'yape') metodoPagoActual = 'MPG003';
    else if (pestaña === 'transf') metodoPagoActual = 'MPG001';
  }
  window.cambiarPestañaPago = cambiarPestañaPago;

  // Botones "Pagar" — lectura segura vía data-attributes (sin XSS)
  document.querySelectorAll('.pg-btn-abrir').forEach(function (btn) {
    btn.addEventListener('click', function () {
      abrirModalPago(
        btn.dataset.pagoId,
        btn.dataset.numCuota,
        btn.dataset.monto,
        btn.dataset.titulo
      );
    });
  });

  btnProcesar.addEventListener('click', async function () {
    if (!pagoIdActual) return;
    const btn = this;
    btn.disabled = true;
    const txtOriginal = btn.innerHTML;
    btn.innerHTML = '⏳ Procesando en Banco...';

    let ref = 'PAY-CARD-' + Math.floor(100000 + Math.random() * 900000);
    if (metodoPagoActual === 'MPG003') {
      ref = document.getElementById('inputReferenciaYape').value.trim() || ref;
    } else if (metodoPagoActual === 'MPG001') {
      ref = document.getElementById('inputReferenciaTransf').value.trim() || ref;
    } else if (metodoPagoActual === 'MPG002') {
      const digits = (inputTarjeta.value || '').replace(/\D/g, '');
      if (digits.length < 13) {
        Swal.fire({ icon: 'warning', title: 'Tarjeta inválida', text: 'Ingresa un número de tarjeta válido (13-16 dígitos).', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        btn.disabled = false; btn.innerHTML = txtOriginal; return;
      }
    }

    const formData = new FormData();
    formData.append('metodo_pago', metodoPagoActual);
    formData.append('referencia', ref);

    try {
      const res = await fetch('/pago/' + encodeURIComponent(pagoIdActual) + '/simular', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (data.success) {
        btn.innerHTML = '✅ ¡Pago Aprobado!';
        btn.style.background = 'linear-gradient(135deg,#16a34a,#15803d)';
        Swal.fire({ icon: 'success', title: data.message || '¡Pago procesado!', text: data.comprobante ? ('Nro. Operación: ' + data.comprobante) : '', toast: true, position: 'top-end', showConfirmButton: false, timer: 1600, timerProgressBar: true });
        setTimeout(() => window.location.reload(), 1400);
      } else {
        Swal.fire({ icon: 'error', title: 'No se pudo procesar', text: data.message || 'Error al procesar el pago.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true });
        btn.disabled = false; btn.innerHTML = txtOriginal;
      }
    } catch (err) {
      console.error(err);
      Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar con la pasarela.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
      btn.disabled = false; btn.innerHTML = txtOriginal;
    }
  });
})();
</script>
