<?php
// Helper mb_substr con guard para extractos.
$pd_substr = function ($s, $start, $len) {
    return function_exists('mb_substr') ? mb_substr($s, $start, $len) : substr($s, $start, $len);
};
$pd_strlen = function ($s) {
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
};
?>
<style>
  .bl-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .bl-head { margin-bottom: 30px; }
  .bl-head h1 { font-family: var(--pd-display); font-size: clamp(30px, 4.4vw, 44px); font-weight: 700; letter-spacing: -.02em; line-height: 1.02; margin: 6px 0 10px; }
  .bl-head .bl-sub { color: var(--pd-muted); font-size: 16px; max-width: 560px; }
  .bl-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
  @media (max-width: 900px) { .bl-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 640px) { .bl-grid { grid-template-columns: 1fr; } }
  .bl-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 24px; box-shadow: var(--pd-sh-1); display: flex; flex-direction: column; transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s, border-color .2s; }
  .bl-card:hover { transform: translateY(-3px); box-shadow: var(--pd-sh-2); border-color: transparent; }
  .bl-card h3 { font-family: var(--pd-display); font-size: 20px; font-weight: 600; letter-spacing: -.01em; margin: 0 0 10px; line-height: 1.25; }
  .bl-card h3 a { color: var(--pd-ink); text-decoration: none; }
  .bl-card h3 a:hover { color: var(--pd-primary); }
  .bl-excerpt { color: var(--pd-muted); font-size: 14.5px; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; margin: 0 0 16px; flex: 1; }
  .bl-meta { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--pd-muted); padding-top: 14px; border-top: 1px solid var(--pd-line); flex-wrap: wrap; }
  .bl-meta .bl-author { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--pd-ink); }
  .bl-meta .bl-date { display: inline-flex; align-items: center; gap: 6px; }
  .bl-empty { text-align: center; padding: 64px 20px; background: var(--pd-surface); border: 1px dashed var(--pd-line); border-radius: var(--pd-r-lg); }
  .bl-empty i { font-size: 44px; color: var(--pd-muted); opacity: .5; }
  .bl-empty h3 { font-family: var(--pd-display); font-size: 22px; margin: 16px 0 8px; }
  .bl-empty p { color: var(--pd-muted); max-width: 440px; margin: 0 auto; }
  .bl-pag { display: flex; justify-content: center; gap: 8px; margin-top: 36px; }
  .bl-pag a, .bl-pag span { width: 40px; height: 40px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; background: var(--pd-surface); border: 1px solid var(--pd-line); color: var(--pd-ink); text-decoration: none; }
  .bl-pag a.is-on, .bl-pag span.is-on { background: var(--pd-ink); color: #fff; border-color: var(--pd-ink); }
  .bl-pag span.is-off { opacity: .4; cursor: default; }
</style>

<section class="pd-body bl-wrap">
    <!-- CABECERA -->
    <div class="bl-head">
        <span class="pd-eyebrow">Guía del universitario</span>
        <h1>Blog &amp; consejos para tu vida estudiantil</h1>
        <p class="bl-sub">Guías prácticas sobre alojamiento, roomies, contratos y vida universitaria. Escrito por y para estudiantes de Nido Universitario.</p>
    </div>

    <!-- GRID DE POSTS -->
    <?php if (empty($posts)): ?>
        <div class="bl-empty">
            <i class="fas fa-newspaper"></i>
            <h3>Aún no hay artículos publicados</h3>
            <p>Vuelve pronto: estamos preparando guías y consejos para ayudarte a encontrar tu lugar ideal.</p>
        </div>
    <?php else: ?>
        <div class="bl-grid">
            <?php foreach ($posts as $post):
                $bl_contenido = $post['contenido'] ?? '';
                $bl_cut = $pd_substr($bl_contenido, 0, 160);
                $bl_len = $pd_strlen($bl_contenido);
                $bl_autor = trim(($post['autor_nombres'] ?? '') . ' ' . ($post['autor_apellido'] ?? ''));
                if ($bl_autor === '') { $bl_autor = 'Equipo Nido'; }
                $bl_fecha = !empty($post['fecha_publicacion']) ? date('d M, Y', strtotime($post['fecha_publicacion'])) : '';
            ?>
                <article class="bl-card pd-reveal">
                    <h3><a href="/blog/ver?id=<?php echo htmlspecialchars($post['blog_id']); ?>"><?php echo htmlspecialchars($post['titulo']); ?></a></h3>
                    <p class="bl-excerpt"><?php echo htmlspecialchars($bl_cut); ?><?php if ($bl_len > 160) echo '…'; ?></p>
                    <div class="bl-meta">
                        <span class="bl-author"><i class="fas fa-user-edit"></i> <?php echo htmlspecialchars($bl_autor); ?></span>
                        <?php if ($bl_fecha !== ''): ?>
                            <span class="bl-date"><i class="far fa-calendar"></i> <?php echo htmlspecialchars($bl_fecha); ?></span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($totalPaginas > 1): ?>
            <nav class="bl-pag" aria-label="Paginación">
                <?php
                $bl_prev = max(1, $pagina - 1);
                $bl_next = min($totalPaginas, $pagina + 1);
                ?>
                <?php if ($pagina > 1): ?>
                    <a href="/blog?pagina=<?php echo $bl_prev; ?>" aria-label="Página anterior"><i class="fas fa-chevron-left"></i></a>
                <?php else: ?>
                    <span class="is-off" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <span class="is-on"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="/blog?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <a href="/blog?pagina=<?php echo $bl_next; ?>" aria-label="Página siguiente"><i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                    <span class="is-off" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
