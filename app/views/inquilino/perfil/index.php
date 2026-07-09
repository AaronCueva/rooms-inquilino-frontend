<?php
// Vista edición de perfil (W5.6). Layout main. Tokens pd-* + inline pf-.
// app-design.css no está cargado por main.php → se enlaza aquí (válido en HTML5).
$pf_u = $usuario ?? [];
$pf_uid = $pf_u['usuario_id'] ?? null;
$pf_verificado = !empty($pf_u['verificado']);
$pf_puntos = (int)($pf_u['puntos_acumulados'] ?? 0);
$pf_mb = function_exists('mb_substr');
$pf_prim = ($pf_u['nombres'] ?? 'U') !== '' ? ($pf_u['nombres'] ?? 'U') : 'U';
$pf_apellido = $pf_u['apellido_paterno'] ?? '';
$pf_iniciales = strtoupper(
    $pf_mb ? mb_substr($pf_prim, 0, 1) : substr($pf_prim, 0, 1)
);
$pf_iniciales .= strtoupper($pf_mb ? mb_substr($pf_apellido, 0, 1) : substr($pf_apellido, 0, 1));
?>
<style>
  .pf-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .pf-head { margin-bottom: 28px; }
  .pf-head h1 { font-family: var(--pd-display); font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; margin: 6px 0 10px; }
  .pf-head .pf-sub { color: var(--pd-muted); font-size: 16px; max-width: 560px; }
  .pf-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; }
  @media (max-width: 900px) { .pf-grid { grid-template-columns: 1fr; } }
  .pf-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 26px 28px; box-shadow: var(--pd-sh-1); }
  .pf-card h2 { font-family: var(--pd-display); font-size: 20px; font-weight: 600; margin-bottom: 18px; }
  .pf-field { margin-bottom: 18px; }
  .pf-field label { font-family: var(--pd-mono); font-size: 11.5px; letter-spacing: .1em; text-transform: uppercase; color: var(--pd-muted); display: block; margin-bottom: 6px; }
  .pf-field input, .pf-field select, .pf-field textarea { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 12px 14px; font-family: var(--pd-body); font-size: 15px; color: var(--pd-ink); width: 100%; outline: none; transition: border-color .2s; }
  .pf-field input:focus, .pf-field select:focus, .pf-field textarea:focus { border-color: var(--pd-primary); }
  .pf-field textarea { resize: vertical; min-height: 90px; }
  .pf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media (max-width: 560px) { .pf-row { grid-template-columns: 1fr; } }
  .pf-actions { display: flex; gap: 12px; margin-top: 24px; }
  .pf-ro { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--pd-line); }
  .pf-ro:last-child { border-bottom: none; }
  .pf-ro .pf-ro-lbl { font-size: 13px; color: var(--pd-muted); }
  .pf-ro .pf-ro-val { font-family: var(--pd-body); font-weight: 600; font-size: 15px; color: var(--pd-ink); text-align: right; max-width: 60%; word-break: break-word; }
  .pf-avatar { width: 64px; height: 64px; border-radius: 999px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 22px; background: linear-gradient(135deg, var(--pd-primary), var(--pd-ink)); margin: 0 auto 14px; }
  .pf-ro-card { text-align: center; }
  .pf-verified { display: inline-flex; align-items: center; gap: 5px; background: var(--pd-lime); color: var(--pd-ink); font-weight: 600; font-size: 12px; padding: 5px 11px; border-radius: 999px; }
  .pf-pending { display: inline-flex; align-items: center; gap: 5px; background: var(--pd-accent-soft); color: var(--pd-accent); font-weight: 600; font-size: 12px; padding: 5px 11px; border-radius: 999px; }
</style>

<section class="pd-body pf-wrap">
    <!-- CABECERA -->
    <div class="pf-head">
        <span class="pd-eyebrow">Mi perfil</span>
        <h1>Editar perfil</h1>
        <p class="pf-sub">Actualiza tu información personal y académica. Mantener tu perfil al día mejora tu verificación y tu experiencia en Nido Universitario.</p>
    </div>

    <div class="pf-grid">
        <!-- FORM EDITABLE -->
        <div class="pf-card">
            <h2><i class="fas fa-user-edit" style="color:var(--pd-primary)"></i> Información editable</h2>
            <form method="POST" action="/perfil">
                <div class="pf-row">
                    <div class="pf-field">
                        <label>Nombres</label>
                        <input type="text" name="nombres" value="<?php echo htmlspecialchars($pf_u['nombres'] ?? ''); ?>" required>
                    </div>
                    <div class="pf-field">
                        <label>Apellido paterno</label>
                        <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($pf_u['apellido_paterno'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="pf-row">
                    <div class="pf-field">
                        <label>Apellido materno</label>
                        <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($pf_u['apellido_materno'] ?? ''); ?>">
                    </div>
                    <div class="pf-field">
                        <label>Género</label>
                        <select name="genero_codigo">
                            <option value="">Selecciona…</option>
                            <?php foreach ($generos as $g): ?>
                                <option value="<?php echo htmlspecialchars($g['codigo']); ?>" <?php echo (($pf_u['genero_codigo'] ?? '') === $g['codigo']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($g['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="pf-row">
                    <div class="pf-field">
                        <label>Celular</label>
                        <input type="text" name="celular" value="<?php echo htmlspecialchars($pf_u['celular'] ?? ''); ?>" placeholder="Ej. 999 123 456">
                    </div>
                    <div class="pf-field">
                        <label>Teléfono fijo</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($pf_u['telefono'] ?? ''); ?>" placeholder="(opcional)">
                    </div>
                </div>
                <div class="pf-field">
                    <label>Universidad</label>
                    <select name="universidad_id">
                        <option value="">Selecciona tu universidad…</option>
                        <?php foreach ($universidades as $uni): ?>
                            <option value="<?php echo htmlspecialchars($uni['universidad_id']); ?>" <?php echo (($pf_u['universidad_id'] ?? '') === $uni['universidad_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($uni['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pf-row">
                    <div class="pf-field">
                        <label>Carrera</label>
                        <input type="text" name="carrera" value="<?php echo htmlspecialchars($pf_u['carrera'] ?? ''); ?>" placeholder="Ej. Ingeniería de Sistemas">
                    </div>
                    <div class="pf-field">
                        <label>Año de ingreso</label>
                        <input type="number" name="anio_ingreso" min="2000" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($pf_u['anio_ingreso'] ?? ''); ?>" placeholder="Ej. 2023">
                    </div>
                </div>
                <div class="pf-field">
                    <label>País de origen</label>
                    <input type="text" name="pais_origen" value="<?php echo htmlspecialchars($pf_u['pais_origen'] ?? ''); ?>" placeholder="Ej. Perú">
                </div>
                <div class="pf-field">
                    <label>URL de foto</label>
                    <input type="text" name="url_foto" value="<?php echo htmlspecialchars($pf_u['url_foto'] ?? ''); ?>" placeholder="https://… (foto de perfil)">
                </div>
                <div class="pf-field">
                    <label>Descripción / bio</label>
                    <textarea name="descripcion" placeholder="Cuéntale a la comunidad algo sobre ti…"><?php echo htmlspecialchars($pf_u['descripcion'] ?? ''); ?></textarea>
                </div>
                <div class="pf-actions">
                    <button type="submit" class="pd-btn pd-btn-primary"><i class="fas fa-save"></i> Guardar cambios</button>
                    <a href="/perfil/verificacion" class="pd-btn pd-btn-ghost"><i class="fas fa-shield-alt"></i> Verificación</a>
                </div>
            </form>
        </div>

        <!-- CARD SOLO LECTURA -->
        <div class="pf-card pf-ro-card">
            <h2>Datos de cuenta</h2>
            <div class="pf-avatar"><?php echo htmlspecialchars($pf_iniciales); ?></div>
            <?php if ($pf_verificado): ?>
                <span class="pf-verified"><i class="fas fa-check-circle"></i> Estudiante verificado</span>
            <?php else: ?>
                <span class="pf-pending"><i class="fas fa-clock"></i> Verificación pendiente</span>
            <?php endif; ?>

            <div style="text-align:left; margin-top:20px;">
                <div class="pf-ro">
                    <span class="pf-ro-lbl">Correo</span>
                    <span class="pf-ro-val"><?php echo htmlspecialchars($pf_u['correo'] ?? ''); ?></span>
                </div>
                <div class="pf-ro">
                    <span class="pf-ro-lbl">Tipo de documento</span>
                    <span class="pf-ro-val">
                        <?php
                        $pf_td = $pf_u['tipo_documento_codigo'] ?? '';
                        $pf_td_lbl = $pf_td;
                        foreach ($tiposDocumento as $td) {
                            if ($td['codigo'] === $pf_td) { $pf_td_lbl = $td['nombre']; break; }
                        }
                        echo htmlspecialchars($pf_td_lbl);
                        ?>
                    </span>
                </div>
                <div class="pf-ro">
                    <span class="pf-ro-lbl">N° de documento</span>
                    <span class="pf-ro-val"><?php echo htmlspecialchars($pf_u['numero_documento'] ?? ''); ?></span>
                </div>
                <div class="pf-ro">
                    <span class="pf-ro-lbl">Puntos Nido</span>
                    <span class="pf-ro-val"><?php echo $pf_puntos; ?> pts</span>
                </div>
                <div class="pf-ro">
                    <span class="pf-ro-lbl">Universidad</span>
                    <span class="pf-ro-val"><?php echo htmlspecialchars($pf_u['universidad_nombre'] ?? '—'); ?></span>
                </div>
                <div class="pf-ro">
                    <span class="pf-ro-lbl">Miembro desde</span>
                    <span class="pf-ro-val">
                        <?php
                        $pf_fecha = $pf_u['fecha_ingreso'] ?? ($pf_u['creado'] ?? null);
                        echo $pf_fecha ? date('d/m/Y', strtotime($pf_fecha)) : '—';
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>
