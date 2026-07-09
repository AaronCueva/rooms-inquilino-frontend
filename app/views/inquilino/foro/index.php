<?php
// Mapeo de íconos y colores por categoría de catálogo (paleta alineada al design system pd-*)
$iconos_cat = [
    'CATFR001' => ['icon' => 'fa-map-marked-alt', 'color' => '#2A44FF', 'bg' => '#E9F0FF', 'label' => 'Zonas'],
    'CATFR002' => ['icon' => 'fa-user-friends',  'color' => '#FF5A3C', 'bg' => '#FFE7DF', 'label' => 'Roomies'],
    'CATFR003' => ['icon' => 'fa-file-contract', 'color' => '#1A2FB0', 'bg' => '#E9F0FF', 'label' => 'Contratos'],
    'CATFR004' => ['icon' => 'fa-home',          'color' => '#FF5A3C', 'bg' => '#FFE7DF', 'label' => 'Arrendadores'],
    'CATFR005' => ['icon' => 'fa-store',         'color' => '#2A44FF', 'bg' => '#E9F0FF', 'label' => 'Mercadillo'],
    'CATFR006' => ['icon' => 'fa-graduation-cap','color' => '#16a34a', 'bg' => '#E8FB9E', 'label' => 'Vida Uni'],
    'CATFR007' => ['icon' => 'fa-bullhorn',      'color' => '#FF5A3C', 'bg' => '#FFE7DF', 'label' => 'Soporte']
];
$uid_actual = $usuario_actual['usuario_id'] ?? null;
?>
<style>
  .fr-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 36px 28px 72px; }
  .fr-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 28px; flex-wrap: wrap; margin-bottom: 26px; }
  .fr-head h1 { font-family: var(--pd-display); font-size: clamp(30px, 4.4vw, 44px); font-weight: 700; letter-spacing: -.02em; line-height: 1.02; margin: 6px 0 10px; }
  .fr-head .fr-sub { color: var(--pd-muted); font-size: 16px; max-width: 560px; }
  .fr-uni { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 14px 18px; box-shadow: var(--pd-sh-1); min-width: 280px; }
  .fr-uni label { font-family: var(--pd-mono); font-size: 11.5px; letter-spacing: .12em; text-transform: uppercase; color: var(--pd-muted); display: block; margin-bottom: 6px; }
  .fr-uni select { border: none; background: transparent; font-family: var(--pd-body); font-size: 16px; font-weight: 600; color: var(--pd-ink); width: 100%; outline: none; cursor: pointer; }
  .fr-toolbar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 22px; }
  .fr-search { flex: 1; min-width: 260px; display: flex; align-items: center; gap: 10px; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: 999px; padding: 10px 18px; box-shadow: var(--pd-sh-1); }
  .fr-search:focus-within { border-color: var(--pd-primary); }
  .fr-search input { border: none; background: transparent; outline: none; font-family: var(--pd-body); font-size: 15px; color: var(--pd-ink); flex: 1; }
  .fr-search i { color: var(--pd-muted); }
  .fr-cats { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 6px; margin-bottom: 28px; scrollbar-width: thin; }
  .fr-cats::-webkit-scrollbar { height: 6px; }
  .fr-cats a { white-space: nowrap; display: inline-flex; align-items: center; gap: 8px; font-family: var(--pd-body); font-weight: 600; font-size: 14px; padding: 9px 16px; border-radius: 999px; border: 1.5px solid var(--pd-line); background: var(--pd-surface); color: var(--pd-ink); text-decoration: none; transition: all .2s; }
  .fr-cats a:hover { border-color: var(--pd-ink); transform: translateY(-1px); }
  .fr-cats a.is-on { background: var(--pd-ink); color: #fff; border-color: var(--pd-ink); }
  .fr-cats a.is-on i { color: #fff !important; }
  .fr-feed { display: flex; flex-direction: column; gap: 16px; }
  .fr-post { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 22px 24px; box-shadow: var(--pd-sh-1); transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s, border-color .2s; }
  .fr-post:hover { transform: translateY(-3px); box-shadow: var(--pd-sh-2); border-color: transparent; }
  .fr-post-top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
  .fr-author { display: flex; align-items: center; gap: 12px; }
  .fr-avatar { width: 42px; height: 42px; border-radius: 999px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 14px; flex-shrink: 0; }
  .fr-author .fr-name { font-weight: 700; color: var(--pd-ink); display: flex; align-items: center; gap: 8px; }
  .fr-author .fr-meta { font-size: 13px; color: var(--pd-muted); display: flex; align-items: center; gap: 8px; margin-top: 2px; }
  .fr-badge { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 5px 11px; border-radius: 999px; }
  .fr-post h3 { font-family: var(--pd-display); font-size: 20px; font-weight: 600; letter-spacing: -.01em; margin: 4px 0 8px; }
  .fr-post h3 a { color: var(--pd-ink); text-decoration: none; }
  .fr-post h3 a:hover { color: var(--pd-primary); }
  .fr-desc { color: var(--pd-muted); font-size: 15px; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
  .fr-foot { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--pd-line); flex-wrap: wrap; }
  .fr-stats { display: flex; align-items: center; gap: 14px; color: var(--pd-muted); font-size: 13.5px; font-weight: 600; }
  .fr-stats span { display: inline-flex; align-items: center; gap: 6px; }
  .fr-del { background: transparent; border: 1px solid var(--pd-line); color: var(--pd-accent); width: 34px; height: 34px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; }
  .fr-del:hover { background: var(--pd-accent); color: #fff; border-color: var(--pd-accent); }
  .fr-empty { text-align: center; padding: 64px 20px; background: var(--pd-surface); border: 1px dashed var(--pd-line); border-radius: var(--pd-r-lg); }
  .fr-empty i { font-size: 44px; color: var(--pd-muted); opacity: .5; }
  .fr-empty h3 { font-family: var(--pd-display); font-size: 22px; margin: 16px 0 8px; }
  .fr-empty p { color: var(--pd-muted); max-width: 440px; margin: 0 auto 20px; }
  .fr-pag { display: flex; justify-content: center; gap: 8px; margin-top: 36px; }
  .fr-pag a { width: 40px; height: 40px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; background: var(--pd-surface); border: 1px solid var(--pd-line); color: var(--pd-ink); text-decoration: none; }
  .fr-pag a.is-on { background: var(--pd-ink); color: #fff; border-color: var(--pd-ink); }
  /* modal pd-* */
  .fr-modal .modal-content { border: none; border-radius: var(--pd-r-lg); box-shadow: var(--pd-sh-2); overflow: hidden; }
  .fr-modal .modal-header { background: var(--pd-ink); color: #fff; border: none; padding: 22px 26px; }
  .fr-modal .modal-header h5 { font-family: var(--pd-display); font-weight: 700; }
  .fr-modal .modal-body { background: var(--pd-paper); padding: 26px; }
  .fr-modal .fr-field label { font-family: var(--pd-mono); font-size: 11.5px; letter-spacing: .1em; text-transform: uppercase; color: var(--pd-muted); }
  .fr-modal .fr-field input, .fr-modal .fr-field select, .fr-modal .fr-field textarea { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 12px 14px; font-family: var(--pd-body); font-size: 15px; color: var(--pd-ink); width: 100%; outline: none; }
  .fr-modal .fr-field input:focus, .fr-modal .fr-field select:focus, .fr-modal .fr-field textarea:focus { border-color: var(--pd-primary); }
  .fr-modal .modal-footer { background: var(--pd-surface); border-top: 1px solid var(--pd-line); padding: 18px 26px; }
</style>

<section class="pd-body fr-wrap">
    <!-- CABECERA -->
    <div class="fr-head">
        <div>
            <span class="pd-eyebrow">Foro estudiantil</span>
            <h1><?php echo htmlspecialchars($nombre_comunidad); ?></h1>
            <p class="fr-sub">Conéctate con tu comunidad: encuentra roomies, compra y vende muebles, resuelve dudas sobre contratos y comparte la vida universitaria.</p>
        </div>
        <!-- SELECTOR DE UNIVERSIDAD -->
        <form method="GET" action="/foros" class="fr-uni">
            <label><i class="fas fa-graduation-cap"></i> Comunidad académica</label>
            <select name="uni" onchange="this.form.submit()">
                <option value="ALL" <?php echo empty($universidad_id) ? 'selected' : ''; ?>>🌐 Todas las universidades</option>
                <?php foreach ($universidades as $uni): ?>
                    <option value="<?php echo $uni['universidad_id']; ?>" <?php echo ($universidad_id === $uni['universidad_id']) ? 'selected' : ''; ?>>
                        🎓 <?php echo htmlspecialchars($uni['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($filtros['categoria'])): ?>
                <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($filtros['categoria']); ?>">
            <?php endif; ?>
            <?php if (!empty($filtros['busqueda'])): ?>
                <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- TOOLBAR: búsqueda + crear -->
    <div class="fr-toolbar">
        <form method="GET" action="/foros" class="fr-search" style="flex:1; min-width:260px;">
            <i class="fas fa-search"></i>
            <input type="text" name="busqueda" placeholder="Buscar por palabra clave en el foro…" value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
            <?php if (!empty($universidad_id)): ?><input type="hidden" name="uni" value="<?php echo htmlspecialchars($universidad_id); ?>"><?php endif; ?>
            <?php if (!empty($filtros['categoria'])): ?><input type="hidden" name="categoria" value="<?php echo htmlspecialchars($filtros['categoria']); ?>"><?php endif; ?>
        </form>
        <?php if (!empty($filtros['busqueda']) || !empty($filtros['categoria'])): ?>
            <a href="/foros<?php echo !empty($universidad_id) ? '?uni='.urlencode($universidad_id) : ''; ?>" class="pd-btn pd-btn-light" title="Limpiar filtros"><i class="fas fa-times"></i></a>
        <?php endif; ?>
        <button type="button" class="pd-btn pd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearForo">
            <i class="fas fa-plus"></i> Crear publicación
        </button>
    </div>

    <!-- CATEGORÍAS (pills) -->
    <div class="fr-cats">
        <a href="/foros<?php echo !empty($universidad_id) ? '?uni='.urlencode($universidad_id) : ''; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda='.urlencode($filtros['busqueda']) : ''; ?>"
           class="<?php echo empty($filtros['categoria']) ? 'is-on' : ''; ?>">
            <i class="fas fa-fire" style="color:var(--pd-accent)"></i> Todo el feed
        </a>
        <?php foreach ($categorias as $cat):
            $cod = $cat['codigo'];
            $si = $iconos_cat[$cod] ?? ['icon' => 'fa-tag', 'color' => '#6B6F7A', 'bg' => '#F1F5F9', 'label' => $cat['nombre']];
            $is_active = ($filtros['categoria'] === $cod);
        ?>
            <a href="/foros?categoria=<?php echo urlencode($cod); ?><?php echo !empty($universidad_id) ? '&uni='.urlencode($universidad_id) : ''; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda='.urlencode($filtros['busqueda']) : ''; ?>"
               class="<?php echo $is_active ? 'is-on' : ''; ?>">
                <i class="fas <?php echo $si['icon']; ?>" style="color:<?php echo $is_active ? '#fff' : $si['color']; ?>"></i>
                <?php echo htmlspecialchars($si['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- FEED -->
    <?php if (empty($foros)): ?>
        <div class="fr-empty">
            <i class="fas fa-comments"></i>
            <h3>No hay publicaciones aún</h3>
            <p>En esta comunidad o categoría todavía no hay hilos. Sé la primera persona en iniciar una conversación o compartir un consejo.</p>
            <button type="button" class="pd-btn pd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearForo">
                <i class="fas fa-plus"></i> Iniciar hilo ahora
            </button>
        </div>
    <?php else: ?>
        <div class="fr-feed">
            <?php foreach ($foros as $foro):
                $cod = $foro['categoria_codigo'];
                $ci = $iconos_cat[$cod] ?? ['icon' => 'fa-tag', 'color' => '#6B6F7A', 'bg' => '#F1F5F9', 'label' => $foro['categoria_nombre'] ?? 'General'];
                $iniciales = strtoupper(substr($foro['nombres'] ?? 'U', 0, 1) . substr($foro['apellido_paterno'] ?? '', 0, 1));
                $es_mio = ($foro['usuario_id'] == $uid_actual);
            ?>
                <article class="fr-post">
                    <div class="fr-post-top">
                        <div class="fr-author">
                            <div class="fr-avatar" style="background: linear-gradient(135deg, <?php echo $ci['color']; ?>, var(--pd-ink));"><?php echo $iniciales; ?></div>
                            <div>
                                <div class="fr-name">
                                    <?php echo htmlspecialchars($foro['nombres'] . ' ' . ($foro['apellido_paterno'] ?? '')); ?>
                                    <?php if ($es_mio): ?><span class="fr-badge" style="background:var(--pd-lime); color:var(--pd-ink)">Tú</span><?php endif; ?>
                                </div>
                                <div class="fr-meta">
                                    <span><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($foro['fecha_creacion'])); ?></span>
                                    <?php if (!empty($foro['universidad_nombre'])): ?>
                                        <span>·</span>
                                        <span><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($foro['universidad_nombre']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span class="fr-badge" style="background:<?php echo $ci['bg']; ?>; color:<?php echo $ci['color']; ?>">
                                <i class="fas <?php echo $ci['icon']; ?>"></i> <?php echo htmlspecialchars($ci['label']); ?>
                            </span>
                            <?php if ($es_mio): ?>
                                <form method="POST" action="/foros/eliminar" onsubmit="return confirmarAccionSweet(event, '¿Eliminar tu publicación?', 'El hilo dejará de estar visible en el foro estudiantil.');">
                                    <input type="hidden" name="id" value="<?php echo $foro['foro_id']; ?>">
                                    <button type="submit" class="fr-del" title="Eliminar publicación"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h3><a href="/foros/ver?id=<?php echo $foro['foro_id']; ?>"><?php echo htmlspecialchars($foro['titulo']); ?></a></h3>
                    <?php
                        $fr_desc = $foro['descripcion'] ?? '';
                        $fr_cut = function_exists('mb_substr') ? mb_substr($fr_desc, 0, 280) : substr($fr_desc, 0, 280);
                        $fr_len = function_exists('mb_strlen') ? mb_strlen($fr_desc) : strlen($fr_desc);
                    ?>
                    <p class="fr-desc"><?php echo htmlspecialchars($fr_cut); ?><?php if ($fr_len > 280) echo '…'; ?></p>

                    <div class="fr-foot">
                        <div class="fr-stats">
                            <span><i class="far fa-comment-dots"></i> <?php echo (int)$foro['total_comentarios']; ?></span>
                            <span><i class="fas fa-fire"></i> <?php echo (int)$foro['total_reacciones']; ?></span>
                        </div>
                        <a href="/foros/ver?id=<?php echo $foro['foro_id']; ?>" class="pd-btn pd-btn-ghost" style="padding:9px 18px; font-size:14px;">Participar <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($total_paginas > 1): ?>
            <nav class="fr-pag" aria-label="Paginación">
                <?php for ($i = 1; $i <= $total_paginas; $i++):
                    $url = "/foros?pagina=" . $i;
                    if (!empty($universidad_id)) $url .= "&uni=" . urlencode($universidad_id);
                    if (!empty($filtros['categoria'])) $url .= "&categoria=" . urlencode($filtros['categoria']);
                    if (!empty($filtros['busqueda'])) $url .= "&busqueda=" . urlencode($filtros['busqueda']);
                ?>
                    <a href="<?php echo $url; ?>" class="<?php echo ($pagina == $i) ? 'is-on' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- MODAL CREAR PUBLICACIÓN -->
<div class="modal fade fr-modal" id="modalCrearForo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="pd-eyebrow" style="color:var(--pd-lime)">Nueva publicación</span>
                    <h5 class="modal-title">Comparte con la comunidad</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="/foros/guardar">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6 fr-field">
                            <label class="d-block mb-2">Eje / categoría <span style="color:var(--pd-accent)">*</span></label>
                            <select name="categoria_codigo" required>
                                <option value="">Selecciona una categoría…</option>
                                <?php foreach ($categorias as $cat):
                                    $cod = $cat['codigo'];
                                    $ci = $iconos_cat[$cod] ?? ['label' => $cat['nombre']];
                                ?>
                                    <option value="<?php echo $cod; ?>"><?php echo htmlspecialchars($ci['label'] ?? $cat['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 fr-field">
                            <label class="d-block mb-2">Comunidad académica</label>
                            <select name="universidad_id">
                                <option value="">🌐 Todas las universidades</option>
                                <?php foreach ($universidades as $uni): ?>
                                    <option value="<?php echo $uni['universidad_id']; ?>" <?php echo ($universidad_id === $uni['universidad_id'] || (!empty($usuario_actual['universidad_id']) && $usuario_actual['universidad_id'] === $uni['universidad_id'])) ? 'selected' : ''; ?>>
                                        🎓 <?php echo htmlspecialchars($uni['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 fr-field mt-3">
                            <label class="d-block mb-2">Título <span style="color:var(--pd-accent)">*</span></label>
                            <input type="text" name="titulo" required maxlength="150" placeholder="Ej. ¿Alguien buscando roomie cerca a la puerta 3 de la universidad?">
                        </div>
                        <div class="col-12 fr-field mt-2">
                            <label class="d-block mb-2">Detalles <span style="color:var(--pd-muted); font-weight:400; text-transform:none; letter-spacing:0">(opcional)</span></label>
                            <textarea name="descripcion" rows="5" placeholder="Da más contexto: precios, horarios, zona exacta, etc."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="pd-btn pd-btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="pd-btn pd-btn-primary"><i class="fas fa-paper-plane"></i> Publicar ahora</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Confirmación destructiva con Swal2 (el layout public carga Swal2 pero no define esta función).
function confirmarAccionSweet(event, titulo, texto) {
    event.preventDefault();
    const form = event.target.closest('form') || event.target;
    Swal.fire({
        title: titulo || '¿Estás seguro?',
        text: texto || 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FF5A3C',
        cancelButtonColor: '#6B6F7A',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((r) => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
