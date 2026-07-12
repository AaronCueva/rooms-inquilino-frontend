<?php
// Vista verificación de identidad estudiantil (W5.4). Layout main. Tokens pd-* + inline pf-.
// app-design.css no está cargado por main.php → se enlaza aquí (válido en HTML5).
$pf_u = $usuario ?? [];
$pf_verificado = !empty($pf_u['verificado']);
$pf_correo = $pf_u['correo'] ?? '';
$pf_docs = $documentos ?? [];
$pf_inst = !empty($esInstitucional);
?>
<style>
  .pf-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .pf-head { margin-bottom: 28px; }
  .pf-head h1 { font-family: var(--pd-display); font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; margin: 6px 0 10px; }
  .pf-head .pf-sub { color: var(--pd-muted); font-size: 16px; max-width: 560px; }
  .pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
  @media (max-width: 900px) { .pf-grid { grid-template-columns: 1fr; } }
  .pf-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 26px 28px; box-shadow: var(--pd-sh-1); }
  .pf-card h2 { font-family: var(--pd-display); font-size: 20px; font-weight: 600; margin-bottom: 6px; }
  .pf-card .pf-card-sub { color: var(--pd-muted); font-size: 14px; margin-bottom: 18px; }
  .pf-status { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; padding: 10px 16px; border-radius: 999px; margin-bottom: 18px; }
  .pf-status-ok { background: var(--pd-lime); color: var(--pd-ink); }
  .pf-status-wait { background: var(--pd-accent-soft); color: var(--pd-accent); }
  .pf-alert { background: var(--pd-sky); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 14px 16px; color: var(--pd-primary-ink); font-size: 14px; margin-bottom: 18px; display: flex; gap: 10px; align-items: flex-start; }
  .pf-alert i { margin-top: 2px; }
  .pf-field { margin-bottom: 18px; }
  .pf-field label { font-family: var(--pd-mono); font-size: 11.5px; letter-spacing: .1em; text-transform: uppercase; color: var(--pd-muted); display: block; margin-bottom: 6px; }
  .pf-field input[type="text"] { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 12px 14px; font-family: var(--pd-body); font-size: 15px; color: var(--pd-ink); width: 100%; outline: none; }
  .pf-field input[type="file"] { width: 100%; font-family: var(--pd-body); font-size: 14px; }
  .pf-doc { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--pd-line); gap: 12px; }
  .pf-doc:last-child { border-bottom: none; }
  .pf-doc .pf-doc-name { font-weight: 600; font-size: 14px; color: var(--pd-ink); display: flex; align-items: center; gap: 8px; }
  .pf-doc .pf-doc-meta { font-size: 12px; color: var(--pd-muted); }
  .pf-empty { text-align: center; padding: 28px 16px; color: var(--pd-muted); font-size: 14px; }
  .pf-note { margin-top: 18px; padding: 14px 16px; background: var(--pd-paper); border: 1px dashed var(--pd-line); border-radius: var(--pd-r-sm); font-size: 13px; color: var(--pd-muted); display: flex; gap: 10px; align-items: flex-start; }
  .pf-note i { color: var(--pd-primary); margin-top: 2px; }
</style>

<section class="pd-body pf-wrap">
    <!-- CABECERA -->
    <div class="pf-head">
        <span class="pd-eyebrow">Verificación estudiantil</span>
        <h1>Verifica tu identidad</h1>
        <p class="pf-sub">Estudiantes verificados obtienen un badge en su perfil, mayor confianza en la comunidad y acceso a beneficios exclusivos de Nido.</p>
    </div>

    <div class="pf-grid">
        <!-- ESTADO + CORREO INSTITUCIONAL -->
        <div class="pf-card">
            <h2><i class="fas fa-shield-alt" style="color:var(--pd-primary)"></i> Estado actual</h2>
            <p class="pf-card-sub">Tu estado de verificación como estudiante universitario.</p>

            <?php
            $pf_estado = 'pendiente';
            if (!empty($pf_u['verificado'])) {
                $pf_estado = 'verificado';
            } elseif (!empty($pf_u['url_verificacion_estudiante'])) {
                $pf_estado = 'en_revision';
            }
            ?>
            <?php if ($pf_estado === 'verificado'): ?>
                <span class="pf-status pf-status-ok"><i class="fas fa-check-circle"></i> Estudiante verificado</span>
            <?php elseif ($pf_estado === 'en_revision'): ?>
                <span class="pf-status pf-status-wait"><i class="fas fa-hourglass-half"></i> Documento en revisión</span>
            <?php else: ?>
                <span class="pf-status pf-status-wait"><i class="fas fa-clock"></i> Verificación pendiente</span>
            <?php endif; ?>

            <div class="pf-ro" style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--pd-line)">
                <span style="font-size:13px; color:var(--pd-muted)">Correo registrado</span>
                <span style="font-weight:600; font-size:14px; color:var(--pd-ink)"><?php echo htmlspecialchars($pf_correo); ?></span>
            </div>

            <?php if ($pf_inst): ?>
                <div class="pf-alert">
                    <i class="fas fa-university"></i>
                    <span>Tu correo pertenece a un dominio institucional. Esto favorece la verificación automática de tu identidad estudiantil.</span>
                </div>
            <?php else: ?>
                <div class="pf-alert" style="background:var(--pd-accent-soft); color:var(--pd-accent)">
                    <i class="fas fa-info-circle"></i>
                    <span>Tu correo no pertenece a un dominio institucional (.edu). Te recomendamos subir tu carnet universitario o constancia de estudios para completar la verificación.</span>
                </div>
            <?php endif; ?>

            <div class="pf-note">
                <i class="fas fa-headset"></i>
                <span>La verificación la revisa el equipo Nido en hasta 48h hábiles. Recibirás la confirmación en tu correo.</span>
            </div>
        </div>

        <!-- SUBIDA DE DOCUMENTOS -->
        <div class="pf-card">
            <h2><i class="fas fa-file-upload" style="color:var(--pd-primary)"></i> Subir carnet / constancia</h2>
            <p class="pf-card-sub">Sube una foto clara de tu carnet universitario o constancia de estudios vigente.</p>

            <form method="POST" action="/perfil/verificar/subir" enctype="multipart/form-data">
                <div class="pf-field">
                    <label>Archivo (PDF, JPG, PNG)</label>
                    <input type="file" name="documento" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="pf-field">
                    <label>O pega una URL (stub)</label>
                    <input type="text" name="url" placeholder="https://… (opcional si no subes archivo)">
                </div>
                <div class="pf-field">
                    <label>Nombre descriptivo</label>
                    <input type="text" name="nombre" placeholder="Ej. Carnet universitario 2026">
                </div>
                <button type="submit" class="pd-btn pd-btn-primary"><i class="fas fa-cloud-upload-alt"></i> Subir documento</button>
            </form>

            <!-- LISTA DE DOCUMENTOS SUBIDOS -->
            <h2 style="margin-top:28px;"><i class="fas fa-folder-open" style="color:var(--pd-primary)"></i> Documentos subidos</h2>
            <?php if (empty($pf_docs)): ?>
                <div class="pf-empty">
                    <i class="fas fa-inbox" style="font-size:28px; opacity:.4; display:block; margin-bottom:8px;"></i>
                    Aún no has subido documentos de verificación.
                </div>
            <?php else: ?>
                <?php foreach ($pf_docs as $doc):
                    $pf_fecha = $doc['creado'] ?? null;
                    $pf_fecha_txt = $pf_fecha ? date('d/m/Y H:i', strtotime($pf_fecha)) : '';
                ?>
                    <div class="pf-doc">
                        <div class="pf-doc-name">
                            <i class="fas fa-file-alt" style="color:var(--pd-primary)"></i>
                            <?php echo htmlspecialchars($doc['nombre'] ?? 'documento'); ?>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="pf-doc-meta"><?php echo $pf_fecha_txt; ?></span>
                            <a href="<?php echo htmlspecialchars($doc['url'] ?? '#'); ?>" target="_blank" rel="noopener" class="pd-btn pd-btn-ghost" style="padding:6px 14px; font-size:13px;"><i class="fas fa-eye"></i> Ver</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
