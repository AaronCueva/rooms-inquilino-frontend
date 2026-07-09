<?php
// Vista dashboard real del inquilino (W5.10). Layout main. Tokens pd-* + inline db-.
// app-design.css no está cargado por main.php → se enlaza aquí (válido en HTML5).
$db_u = $usuario ?? [];
$db_uid = $db_u['usuario_id'] ?? null;
$db_verificado = !empty($db_u['verificado']);
$db_mb = function_exists('mb_substr');
$db_nombre = $db_u['nombres'] ?? 'estudiante';
$db_apellido = $db_u['apellido_paterno'] ?? '';
$db_correo = $db_u['correo'] ?? '';
$db_universidad = $db_u['universidad_nombre'] ?? ($db_u['universidad_id'] ?? '—');
$db_puntos = $saldo ?? 0;
$db_nivel = $nivel ?? ['nombre' => 'Novato', 'codigo' => 'NV1', 'proximo_nombre' => null, 'falta' => 0];
$db_racha = $racha ?? 0;
$db_mov = $ultMov ?? [];
$db_noLeidos = $noLeidos ?? 0;
$db_refAct = $refActivos ?? 0;
$db_refPend = $refPendientes ?? 0;
$db_blog = $blogRecientes ?? [];
$db_desc = $descuentos ?? [];
$db_ben = $beneficios ?? [];

function db_h($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function db_fecha($f) {
    if (!$f) return '—';
    $ts = strtotime($f);
    return $ts ? date('d M Y', $ts) : db_h($f);
}
?>
<style>
  .db-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .db-head { margin-bottom: 28px; }
  .db-head h1 { font-family: var(--pd-display); font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; margin: 6px 0 10px; }
  .db-head .db-sub { color: var(--pd-muted); font-size: 16px; max-width: 560px; }
  .db-badge-v { display: inline-flex; align-items: center; gap: 6px; background: var(--pd-lime); color: var(--pd-ink); font-weight: 600; font-size: 12px; padding: 5px 11px; border-radius: 999px; margin-left: 10px; vertical-align: middle; }
  .db-badge-p { display: inline-flex; align-items: center; gap: 6px; background: var(--pd-accent-soft); color: var(--pd-accent); font-weight: 600; font-size: 12px; padding: 5px 11px; border-radius: 999px; margin-left: 10px; vertical-align: middle; }
  .db-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
  @media (max-width: 1000px) { .db-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 640px) { .db-grid { grid-template-columns: 1fr; } }
  .db-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 24px 26px; box-shadow: var(--pd-sh-1); display: flex; flex-direction: column; gap: 10px; }
  .db-card h3 { font-family: var(--pd-display); font-size: 17px; font-weight: 600; display: flex; align-items: center; gap: 9px; }
  .db-card h3 i { color: var(--pd-primary); }
  .db-card .db-meta { color: var(--pd-muted); font-size: 13px; }
  .db-card .db-big { font-family: var(--pd-display); font-size: 30px; font-weight: 700; }
  .db-chip { display: inline-flex; align-items: center; background: var(--pd-sky); color: var(--pd-primary-ink); font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 999px; }
  .db-link { margin-top: auto; align-self: flex-start; font-family: var(--pd-body); font-weight: 600; font-size: 14px; color: var(--pd-primary); text-decoration: none; }
  .db-link:hover { text-decoration: underline; }
  .db-stub { color: var(--pd-muted); font-size: 13px; font-style: italic; }
  .db-btn-dis { margin-top: auto; align-self: flex-start; background: transparent; color: var(--pd-muted); border: 1.5px solid var(--pd-line); font-weight: 600; font-size: 13px; padding: 8px 16px; border-radius: 999px; cursor: not-allowed; }
  .db-list { display: flex; flex-direction: column; gap: 8px; }
  .db-list .db-li { font-size: 13.5px; padding: 7px 0; border-bottom: 1px solid var(--pd-line); }
  .db-list .db-li:last-child { border-bottom: none; }
  .db-mov { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; border-bottom: 1px solid var(--pd-line); }
  .db-mov:last-child { border-bottom: none; }
  .db-mov .db-mov-pos { color: #1f8a4c; font-weight: 600; }
  .db-mov .db-mov-neg { color: var(--pd-accent); font-weight: 600; }
  .db-noleidos { background: var(--pd-accent); color: #fff; border-radius: 999px; font-size: 11px; font-weight: 700; padding: 2px 7px; margin-left: 6px; }
</style>

<section class="pd-body db-wrap">
    <div class="db-head">
        <span class="pd-eyebrow">Dashboard</span>
        <h1>Bienvenida, <?php echo db_h($db_nombre); ?><?php if ($db_verificado): ?><span class="db-badge-v"><i class="fas fa-check-circle"></i> Verificado</span><?php else: ?><span class="db-badge-p"><i class="fas fa-clock"></i> Pendiente de verificación</span><?php endif; ?></h1>
        <p class="db-sub">Tu centro de control en Nido Universitario. Gestiona tu perfil, puntos, mensajes y más.</p>
    </div>

    <div class="db-grid">
        <!-- 1. Perfil y verificación -->
        <div class="db-card">
            <h3><i class="fas fa-id-card"></i> Perfil y verificación</h3>
            <div class="db-meta"><?php echo $db_verificado ? 'Cuenta verificada' : 'Verificación pendiente'; ?> · <?php echo db_h($db_correo); ?></div>
            <a class="db-link" href="/perfil">Editar perfil →</a>
            <a class="db-link" href="/perfil/verificacion">Verificación estudiantil →</a>
        </div>

        <!-- 2. Mis puntos Nido -->
        <div class="db-card">
            <h3><i class="fas fa-star"></i> Mis puntos Nido</h3>
            <div class="db-big"><?php echo (int)$db_puntos; ?></div>
            <div><span class="db-chip"><?php echo db_h($db_nivel['nombre']); ?></span> <span class="db-meta">· Racha <?php echo (int)$db_racha; ?> meses</span></div>
            <?php if (!empty($db_mov)): ?>
                <div class="db-list">
                    <?php foreach ($db_mov as $m): $p = (int)($m['puntos'] ?? 0); ?>
                        <div class="db-mov">
                            <span><?php echo db_h($m['descripcion'] ?? $m['tipo_movimiento_codigo']); ?></span>
                            <span class="<?php echo $p >= 0 ? 'db-mov-pos' : 'db-mov-neg'; ?>"><?php echo ($p >= 0 ? '+' : '') . $p; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="db-stub">Aún no tienes movimientos de puntos.</div>
            <?php endif; ?>
            <a class="db-link" href="/puntos">Ver historial completo →</a>
        </div>

        <!-- 3. Mis referidos (W8.2 aterrizó) -->
        <div class="db-card">
            <h3><i class="fas fa-user-plus"></i> Mis referidos</h3>
            <div class="db-meta"><strong><?php echo (int)$db_refAct; ?></strong> acreditados · <strong><?php echo (int)$db_refPend; ?></strong> pendientes</div>
            <div class="db-stub">Invita amigos y gana 200 puntos Nido por cada referido que complete su registro.</div>
            <a class="db-link" href="/referidos">Gestionar referidos →</a>
        </div>

        <!-- 4. Mensajes (W6) -->
        <div class="db-card">
            <h3><i class="fas fa-comments"></i> Mensajes<?php if ($db_noLeidos > 0): ?><span class="db-noleidos"><?php echo (int)$db_noLeidos; ?></span><?php endif; ?></h3>
            <div class="db-meta"><?php echo $db_noLeidos > 0 ? 'Tienes mensajes sin leer.' : 'Bandeja al día.'; ?></div>
            <a class="db-link" href="/mensajes">Abrir mensajes →</a>
        </div>

        <!-- 5. Blog / Guía (W8.1 aterrizó) -->
        <div class="db-card">
            <h3><i class="fas fa-book-open"></i> Blog / Guía del universitario</h3>
            <?php if (!empty($db_blog)): ?>
                <div class="db-list">
                    <?php foreach ($db_blog as $b): ?>
                        <div class="db-li"><a href="/blog/ver?id=<?php echo urlencode($b['blog_id'] ?? ''); ?>"><?php echo db_h($b['titulo'] ?? '—'); ?></a></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="db-stub">No hay publicaciones recientes.</div>
            <?php endif; ?>
            <a class="db-link" href="/blog">Ver todo el blog →</a>
        </div>

        <!-- 6. Descuentos y beneficios -->
        <div class="db-card">
            <h3><i class="fas fa-tags"></i> Descuentos y beneficios</h3>
            <?php if (!empty($db_desc)): ?>
                <div class="db-list">
                    <?php foreach ($db_desc as $d): ?>
                        <div class="db-li"><?php echo db_h($d['nombre'] ?? $d['titulo'] ?? 'Descuento'); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="db-stub">No hay descuentos activos ahora.</div>
            <?php endif; ?>
            <?php if (!empty($db_ben)): ?>
                <div class="db-meta" style="margin-top:6px"><strong>Beneficios:</strong> <?php echo db_h($db_ben[0]['nombre'] ?? $db_ben[0]['titulo'] ?? ''); ?><?php if (count($db_ben) > 1) echo ' y ' . (count($db_ben) - 1) . ' más'; ?></div>
            <?php endif; ?>
        </div>

        <!-- 7. Mis reservas (W3 stub) -->
        <div class="db-card">
            <h3><i class="fas fa-calendar-check"></i> Mis reservas</h3>
            <div class="db-stub">Próximamente — gestión de reservas de alojamiento.</div>
            <button class="db-btn-dis" disabled>Ver reservas</button>
        </div>

        <!-- 8. Mi alojamiento actual (W3 stub) -->
        <div class="db-card">
            <h3><i class="fas fa-home"></i> Mi alojamiento actual</h3>
            <div class="db-stub">Próximamente — detalle de tu alojamiento vigente.</div>
            <button class="db-btn-dis" disabled>Ver alojamiento</button>
        </div>

        <!-- 9. Favoritos (W7 stub) -->
        <div class="db-card">
            <h3><i class="fas fa-heart"></i> Favoritos</h3>
            <div class="db-stub">Próximamente — alojamientos guardados.</div>
            <button class="db-btn-dis" disabled>Ver favoritos</button>
        </div>

        <!-- 10. Mis reseñas (W7 stub) -->
        <div class="db-card">
            <h3><i class="fas fa-pen-alt"></i> Mis reseñas</h3>
            <div class="db-stub">Próximamente — reseñas que has publicado.</div>
            <button class="db-btn-dis" disabled>Ver reseñas</button>
        </div>

        <!-- 11. Documentos / Contrato (W4 stub) -->
        <div class="db-card">
            <h3><i class="fas fa-file-contract"></i> Documentos / Contrato</h3>
            <div class="db-stub">Próximamente — contratos y documentos de tu alojamiento.</div>
            <button class="db-btn-dis" disabled>Ver documentos</button>
        </div>
    </div>
</section>
