<?php
// Vista: Detalle de contrato (W4). Layout main. Tokens pd-* + scope cv-.
$cv_c = $c;
$cv_id = $cv_c['contrato_id'];
$cv_est = $estadosMap[$cv_c['estado_codigo']] ?? ['label' => $cv_c['estado_codigo'], 'bg' => '#F1F5F9', 'color' => '#6B6F7A'];
$cv_mon = $monedaLabels[$cv_c['moneda_codigo'] ?? ''] ?? 'S/';
$cv_fmt = function ($n) use ($cv_mon) { return $cv_mon . ' ' . number_format((float)$n, 0, ',', '.'); };
$cv_fecha = function ($f) { if (!$f) return '—'; $ts = strtotime($f); return $ts !== false ? date('d/m/Y', $ts) : '—'; };
$cv_fechahora = function ($f) { if (!$f) return '—'; $ts = strtotime($f); return $ts !== false ? date('d/m/Y H:i', $ts) : '—'; };
$cv_prop = trim(($cv_c['propietario_nombres'] ?? '') . ' ' . ($cv_c['propietario_apellido'] ?? ''));
$cv_inq = trim(($cv_c['inquilino_nombres'] ?? '') . ' ' . ($cv_c['inquilino_apellido'] ?? ''));
?>
<style>
  .cv-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .cv-back { display: inline-flex; align-items: center; gap: 8px; color: var(--pd-muted); text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 14px; }
  .cv-back:hover { color: var(--pd-primary); }
  .cv-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
  .cv-head h1 { font-family: var(--pd-display); font-size: clamp(26px, 3.6vw, 36px); font-weight: 700; letter-spacing: -.02em; margin: 0; }
  .cv-nro { font-family: var(--pd-mono); font-size: 12px; color: var(--pd-muted); margin-top: 6px; }
  .cv-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; padding: 6px 13px; border-radius: 999px; }

  .cv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
  @media (max-width: 760px) { .cv-grid { grid-template-columns: 1fr; } }
  .cv-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 22px 24px; box-shadow: var(--pd-sh-1); }
  .cv-card h2 { font-family: var(--pd-display); font-size: 16px; font-weight: 700; margin: 0 0 14px; color: var(--pd-ink); }
  .cv-card h2 i { color: var(--pd-primary); margin-right: 7px; }
  .cv-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--pd-line); font-size: 14px; }
  .cv-row:last-child { border-bottom: none; }
  .cv-row .cv-k { color: var(--pd-muted); }
  .cv-row .cv-v { font-weight: 600; color: var(--pd-ink); text-align: right; max-width: 60%; word-break: break-word; }

  .cv-full { grid-column: 1 / -1; }
  .cv-doc { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 22px 24px; box-shadow: var(--pd-sh-1); margin-bottom: 20px; }
  .cv-doc h2 { font-family: var(--pd-display); font-size: 16px; font-weight: 700; margin: 0 0 14px; }
  .cv-doc h2 i { color: var(--pd-primary); margin-right: 7px; }
  .cv-iframe { width: 100%; height: 600px; border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); background: var(--pd-paper); }
  .cv-doc-actions { margin-top: 12px; }
  .cv-btn { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; padding: 10px 18px; border-radius: 999px; text-decoration: none; border: 1px solid var(--pd-line); }
  .cv-btn-primary { background: var(--pd-primary); color: #fff; border-color: var(--pd-primary); }
  .cv-btn-ghost { background: transparent; color: var(--pd-ink); }
  .cv-btn-ghost:hover { border-color: var(--pd-primary); color: var(--pd-primary); }
  .cv-no-doc { padding: 28px; text-align: center; background: var(--pd-paper); border: 1px dashed var(--pd-line); border-radius: var(--pd-r-sm); color: var(--pd-muted); }
  .cv-no-doc i { font-size: 30px; margin-bottom: 10px; display: block; }

  .cv-legal { background: var(--pd-sky); border-radius: var(--pd-r-sm); padding: 14px 16px; font-size: 13px; line-height: 1.6; color: var(--pd-primary-ink); margin-bottom: 20px; }
  .cv-legal strong { color: var(--pd-primary); }

  .cv-firma { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 22px 24px; box-shadow: var(--pd-sh-1); }
  .cv-firma h2 { font-family: var(--pd-display); font-size: 16px; font-weight: 700; margin: 0 0 14px; }
  .cv-firma h2 i { color: var(--pd-primary); margin-right: 7px; }
  .cv-firma-done { display: flex; align-items: center; gap: 12px; background: #D7F5E0; color: #1B7A3D; padding: 14px 18px; border-radius: var(--pd-r-sm); font-weight: 600; }
  .cv-firma-done i { font-size: 20px; }
  .cv-firma-form { font-size: 14px; color: var(--pd-muted); line-height: 1.6; margin-bottom: 16px; }
  .cv-firma-form button { display: inline-flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; padding: 13px 22px; border-radius: 999px; border: none; cursor: pointer; background: var(--pd-primary); color: #fff; box-shadow: 0 6px 18px rgba(99,102,241,0.3); transition: all 0.2s ease; }
  .cv-firma-form button:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
  .cv-firma-form button:disabled { background: var(--pd-line); color: var(--pd-muted); box-shadow: none; cursor: not-allowed; }

  /* Modal Especializado de Firma Electrónica */
  .cv-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(6px);
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s ease;
      padding: 20px;
  }
  .cv-modal-overlay.is-open {
      opacity: 1;
      pointer-events: auto;
  }
  .cv-modal-box {
      background: var(--pd-surface);
      border: 1px solid var(--pd-line);
      border-radius: 24px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
      max-width: 500px;
      width: 100%;
      padding: 32px;
      transform: translateY(20px) scale(0.96);
      transition: transform 0.25s ease;
  }
  .cv-modal-overlay.is-open .cv-modal-box {
      transform: translateY(0) scale(1);
  }
  .cv-modal-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #EEF2FF;
      color: var(--pd-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      margin-bottom: 20px;
  }
  .cv-modal-title {
      font-family: var(--pd-display);
      font-size: 22px;
      font-weight: 700;
      color: var(--pd-ink);
      margin: 0 0 8px;
  }
  .cv-modal-sub {
      font-size: 14.5px;
      color: var(--pd-muted);
      line-height: 1.6;
      margin-bottom: 20px;
  }
  .cv-modal-legal-box {
      background: var(--pd-paper);
      border: 1px solid var(--pd-line);
      border-radius: 14px;
      padding: 16px;
      font-size: 13.5px;
      line-height: 1.6;
      color: var(--pd-ink);
      margin-bottom: 26px;
      display: flex;
      flex-direction: column;
      gap: 12px;
  }
  .cv-modal-legal-item {
      display: flex;
      align-items: flex-start;
      gap: 10px;
  }
  .cv-modal-legal-item i {
      color: var(--pd-primary);
      font-size: 16px;
      margin-top: 3px;
      flex-shrink: 0;
  }
  .cv-modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      flex-wrap: wrap;
  }
  .cv-modal-btn-cancel {
      padding: 12px 22px;
      border-radius: 999px;
      border: 1px solid var(--pd-line);
      background: transparent;
      color: var(--pd-ink);
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
  }
  .cv-modal-btn-cancel:hover {
      background: var(--pd-paper);
  }
  .cv-modal-btn-confirm {
      padding: 12px 26px;
      border-radius: 999px;
      border: none;
      background: var(--pd-primary);
      color: #fff;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 6px 18px rgba(99, 102, 241, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
  }
  .cv-modal-btn-confirm:hover {
      filter: brightness(1.08);
      transform: translateY(-1px);
  }
</style>

<section class="cv-wrap">
    <a href="/contratos" class="cv-back"><i class="fas fa-arrow-left"></i> Mis contratos</a>

    <div class="cv-head">
        <div>
            <h1>Contrato de arrendamiento</h1>
            <div class="cv-nro">N.° <?php echo htmlspecialchars(substr($cv_id, 0, 8), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <span class="cv-badge" style="background:<?php echo $cv_est['bg']; ?>;color:<?php echo $cv_est['color']; ?>"><?php echo htmlspecialchars($cv_est['label'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <!-- PARTES -->
    <div class="cv-grid">
        <div class="cv-card">
            <h2><i class="fas fa-user-tie"></i> Arrendador (propietario)</h2>
            <div class="cv-row"><span class="cv-k">Nombre</span><span class="cv-v"><?php echo htmlspecialchars($cv_prop ?: '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="cv-row"><span class="cv-k">Correo</span><span class="cv-v"><?php echo htmlspecialchars($cv_c['propietario_correo'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="cv-row"><span class="cv-k">Celular</span><span class="cv-v"><?php echo htmlspecialchars($cv_c['propietario_celular'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
        <div class="cv-card">
            <h2><i class="fas fa-user-graduate"></i> Arrendatario (inquilino)</h2>
            <div class="cv-row"><span class="cv-k">Nombre</span><span class="cv-v"><?php echo htmlspecialchars($cv_inq ?: '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="cv-row"><span class="cv-k">Correo</span><span class="cv-v"><?php echo htmlspecialchars($cv_c['inquilino_correo'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="cv-row"><span class="cv-k">Celular</span><span class="cv-v"><?php echo htmlspecialchars($cv_c['inquilino_celular'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
    </div>

    <!-- INMUEBLE + CONDICIONES -->
    <div class="cv-grid">
        <div class="cv-card">
            <h2><i class="fas fa-home"></i> Inmueble</h2>
            <div class="cv-row"><span class="cv-k">Alojamiento</span><span class="cv-v"><?php echo htmlspecialchars($cv_c['alojamiento_titulo'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="cv-row"><span class="cv-k">Distrito</span><span class="cv-v"><?php echo htmlspecialchars($cv_c['distrito'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="cv-row"><span class="cv-k">Dirección</span><span class="cv-v"><?php echo htmlspecialchars($cv_c['direccion'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
        <div class="cv-card">
            <h2><i class="fas fa-coins"></i> Condiciones económicas</h2>
            <div class="cv-row"><span class="cv-k">Renta mensual</span><span class="cv-v"><?php echo $cv_fmt($cv_c['monto_renta']); ?></span></div>
            <div class="cv-row"><span class="cv-k">Garantía</span><span class="cv-v"><?php echo $cv_fmt($cv_c['monto_garantia']); ?></span></div>
            <div class="cv-row"><span class="cv-k">Cargo plataforma</span><span class="cv-v"><?php echo $cv_fmt($cv_c['cargo_plataforma']); ?></span></div>
            <div class="cv-row"><span class="cv-k">Duración</span><span class="cv-v"><?php echo $cv_fecha($cv_c['fecha_inicio']); ?> → <?php echo $cv_fecha($cv_c['fecha_fin']); ?></span></div>
            <div class="cv-row"><span class="cv-k">Pago mensual (día)</span><span class="cv-v"><?php echo htmlspecialchars($cv_c['fecha_pago_mensual'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
    </div>

    <!-- DOCUMENTO PDF -->
    <div class="cv-doc">
        <h2><i class="fas fa-file-pdf"></i> Documento del contrato</h2>
        <?php if ($tienePdf && !empty($cv_c['pdf_url'])): ?>
            <iframe class="cv-iframe" src="<?php echo htmlspecialchars($cv_c['pdf_url'], ENT_QUOTES, 'UTF-8'); ?>" title="Contrato PDF"></iframe>
            <div class="cv-doc-action">
                <a class="cv-btn cv-btn-ghost" href="/contrato/<?php echo urlencode($cv_id); ?>/pdf" target="_blank"><i class="fas fa-download"></i> Descargar PDF</a>
            </div>
        <?php else: ?>
            <div class="cv-no-doc">
                <i class="fas fa-hourglass-half"></i>
                El propietario aún no carga el documento del contrato.<br>
                Vuelve a revisar en breve.
            </div>
        <?php endif; ?>
    </div>

    <!-- DISCLAIMER LEGAL -->
    <div class="cv-legal">
        <strong>Disclaimers y jurisdicción.</strong>
        Este contrato digital se rige por las leyes de la República del Perú. La firma electrónica
        tiene la misma validez legal que la manuscrita según la Ley N.° 27269. Las partes declaran
        conocer el inmueble y aceptar las condiciones económicas y de duración aquí descritas. La
        garantía será devuelta al arrendatario al término del contrato, salvo daños comprobados.
        Cualquier disputa será resuelta ante los juzgados competentes del domicilio del inmueble.
    </div>

    <!-- FIRMA -->
    <div class="cv-firma">
        <h2><i class="fas fa-signature"></i> Firma del inquilino</h2>
        <?php if ($firmado): ?>
            <div class="cv-firma-done">
                <i class="fas fa-check-circle"></i>
                <div>Contrato firmado electrónicamente el <?php echo $cv_fechahora($cv_c['fecha_firma_inquilino']); ?>.</div>
            </div>
        <?php else: ?>
            <form id="formFirmaContrato" method="post" action="/contrato/<?php echo urlencode($cv_id); ?>/firmar">
                <p class="cv-firma-form">
                    Al firmar, confirmas haber leído el documento del contrato y aceptas todas sus condiciones.
                    La firma electrónica queda registrada con fecha y hora.
                    <?php if (!$tienePdf): ?><br><strong style="color:var(--pd-accent)">No puedes firmar hasta que el propietario cargue el documento.</strong><?php endif; ?>
                </p>
                <button type="button" <?php echo $tienePdf ? '' : 'disabled'; ?> onclick="abrirModalFirmaContrato()"><i class="fas fa-signature"></i> Firmar contrato</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<!-- MODAL ESPECIALIZADO DE FIRMA ELECTRÓNICA -->
<div id="cvModalFirma" class="cv-modal-overlay" onclick="cerrarModalFirmaSiOverlay(event)">
    <div class="cv-modal-box">
        <div class="cv-modal-icon">
            <i class="fas fa-file-signature"></i>
        </div>
        <h3 class="cv-modal-title">Firma Electrónica con Validez Legal</h3>
        <p class="cv-modal-sub">
            Estás a punto de formalizar electrónicamente el contrato de arrendamiento <strong>N.° <?php echo htmlspecialchars(substr($cv_id, 0, 8), ENT_QUOTES, 'UTF-8'); ?></strong>.
        </p>
        <div class="cv-modal-legal-box">
            <div class="cv-modal-legal-item">
                <i class="fas fa-check-circle"></i>
                <div>Declaro haber leído en su totalidad el documento PDF adjunto y acepto sus cláusulas y condiciones económicas.</div>
            </div>
            <div class="cv-modal-legal-item">
                <i class="fas fa-balance-scale"></i>
                <div>De conformidad con la <strong>Ley N.° 27269</strong> de Firmas y Certificados Digitales, esta confirmación genera efectos jurídicos vinculantes y registra tu marca de tiempo oficial.</div>
            </div>
        </div>
        <div class="cv-modal-actions">
            <button type="button" class="cv-modal-btn-cancel" onclick="cerrarModalFirmaContrato()">Cancelar</button>
            <button type="button" class="cv-modal-btn-confirm" onclick="confirmarYFirmarContrato(this)">
                <i class="fas fa-signature"></i> Firmar Electrónicamente
            </button>
        </div>
    </div>
</div>

<script>
function abrirModalFirmaContrato() {
    var modal = document.getElementById('cvModalFirma');
    if (modal) {
        modal.classList.add('is-open');
    }
}
function cerrarModalFirmaContrato() {
    var modal = document.getElementById('cvModalFirma');
    if (modal) {
        modal.classList.remove('is-open');
    }
}
function cerrarModalFirmaSiOverlay(e) {
    if (e.target && e.target.id === 'cvModalFirma') {
        cerrarModalFirmaContrato();
    }
}
function confirmarYFirmarContrato(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando firma legal...';
    var form = document.getElementById('formFirmaContrato');
    if (form) {
        form.submit();
    }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalFirmaContrato();
});
</script>
