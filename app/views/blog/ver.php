<?php
$bl_autor = trim(($post['autor_nombres'] ?? '') . ' ' . ($post['autor_apellido'] ?? ''));
if ($bl_autor === '') { $bl_autor = 'Equipo Nido'; }
$bl_fecha = !empty($post['fecha_publicacion']) ? date('d \d\e F \d\e Y', strtotime($post['fecha_publicacion'])) : '';
$meses_es = ['January' => 'enero', 'February' => 'febrero', 'March' => 'marzo', 'April' => 'abril', 'May' => 'mayo', 'June' => 'junio', 'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre', 'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'];
$bl_fecha = strtr($bl_fecha, $meses_es);
?>
<style>
  .blv-wrap { max-width: 820px; margin: 0 auto; padding: 36px 28px 72px; }
  .blv-back { display: inline-flex; align-items: center; gap: 8px; font-family: var(--pd-body); font-weight: 600; font-size: 14px; color: var(--pd-muted); text-decoration: none; margin-bottom: 18px; transition: color .2s; }
  .blv-back:hover { color: var(--pd-ink); }
  .blv-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-lg); padding: 40px 44px; box-shadow: var(--pd-sh-1); }
  @media (max-width: 640px) { .blv-card { padding: 28px 22px; } }
  .blv-card h1 { font-family: var(--pd-display); font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; line-height: 1.1; margin: 8px 0 18px; }
  .blv-meta { display: flex; align-items: center; gap: 14px; font-size: 14px; color: var(--pd-muted); margin-bottom: 28px; padding-bottom: 22px; border-bottom: 1px solid var(--pd-line); flex-wrap: wrap; }
  .blv-meta .blv-author { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; color: var(--pd-ink); }
  .blv-meta .blv-date { display: inline-flex; align-items: center; gap: 8px; }
  .blv-content { font-family: var(--pd-body); font-size: 16px; line-height: 1.75; color: var(--pd-ink); }
  .blv-content p { margin: 0 0 16px; }
  .blv-foot { margin-top: 36px; text-align: center; }
</style>

<section class="pd-body blv-wrap">
    <a href="/blog" class="blv-back"><i class="fas fa-arrow-left"></i> Volver a la guía</a>

    <article class="blv-card pd-reveal">
        <span class="pd-eyebrow">Guía del universitario</span>
        <h1><?php echo htmlspecialchars($post['titulo']); ?></h1>
        <div class="blv-meta">
            <span class="blv-author"><i class="fas fa-user-edit"></i> <?php echo htmlspecialchars($bl_autor); ?></span>
            <?php if ($bl_fecha !== ''): ?>
                <span class="blv-date"><i class="far fa-calendar"></i> <?php echo htmlspecialchars($bl_fecha); ?></span>
            <?php endif; ?>
        </div>
        <div class="blv-content">
            <?php echo nl2br(htmlspecialchars($post['contenido'])); ?>
        </div>
    </article>

    <div class="blv-foot">
        <a href="/blog" class="pd-btn pd-btn-ghost"><i class="fas fa-arrow-left"></i> Ver todos los artículos</a>
    </div>
</section>
