<?php
// Mapeo de íconos y colores por categoría de catálogo
$iconos_cat = [
    'CATFR001' => ['icon' => 'fa-map-marked-alt', 'color' => '#4FCBDA', 'bg' => 'rgba(79, 203, 218, 0.15)', 'label' => '🗺️ Zonas'],
    'CATFR002' => ['icon' => 'fa-user-friends',  'color' => '#7BA0F6', 'bg' => 'rgba(123, 160, 246, 0.15)', 'label' => '🤝 Roomies'],
    'CATFR003' => ['icon' => 'fa-file-contract', 'color' => '#F0AE5C', 'bg' => 'rgba(240, 174, 92, 0.15)', 'label' => '⚖️ Contratos'],
    'CATFR004' => ['icon' => 'fa-home',          'color' => '#E86E6E', 'bg' => 'rgba(232, 110, 110, 0.15)', 'label' => '🏠 Arrendadores'],
    'CATFR005' => ['icon' => 'fa-store',         'color' => '#A07BF6', 'bg' => 'rgba(160, 123, 246, 0.15)', 'label' => '📦 Mercadillo'],
    'CATFR006' => ['icon' => 'fa-graduation-cap','color' => '#4DDA8A', 'bg' => 'rgba(77, 218, 138, 0.15)', 'label' => '💡 Vida Uni'],
    'CATFR007' => ['icon' => 'fa-bullhorn',      'color' => '#FF8040', 'bg' => 'rgba(255, 128, 64, 0.15)', 'label' => '📢 Soporte']
];
?>

<div class="page-content" style="background: #F8FAFC; min-height: calc(100vh - 70px); padding-bottom: 50px;">
    <!-- BANNER CABECERA DE COMUNIDAD -->
    <div class="card border-0 mb-4 overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #1E293B 0%, #334155 100%); border-radius: 16px;">
        <div class="card-body p-4 p-md-5 position-relative">
            <!-- Decoración de fondo -->
            <div style="position: absolute; right: -20px; top: -20px; font-size: 10rem; opacity: 0.05; color: #fff; pointer-events: none;">
                <i class="fas fa-users"></i>
            </div>
            
            <div class="row align-items-center position-relative z-1">
                <div class="col-lg-7 mb-3 mb-lg-0">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge px-3 py-2 rounded-pill fw-bold" style="background: rgba(255,255,255,0.15); color: #7BA0F6; font-size: 0.85rem; backdrop-filter: blur(4px);">
                            <i class="fas fa-comments me-1"></i> Foro Estudiantil
                        </span>
                    </div>
                    <h1 class="text-white fw-extrabold mb-2" style="font-size: 2.2rem; letter-spacing: -0.5px;">
                        <?php echo htmlspecialchars($nombre_comunidad); ?>
                    </h1>
                    <p class="text-light mb-0" style="opacity: 0.85; font-size: 1.05rem; max-width: 600px;">
                        Conéctate con tu comunidad, encuentra roomies, compra/vende muebles y comparte consejos sobre la vida universitaria y alquileres.
                    </p>
                </div>

                <!-- SELECTOR DINÁMICO DE UNIVERSIDAD -->
                <div class="col-lg-5">
                    <div class="p-3 rounded-4 shadow-sm" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.15);">
                        <label class="form-label text-white small fw-bold mb-2 d-block">
                            <i class="fas fa-university me-1 text-warning"></i> Filtrar por Comunidad Académica:
                        </label>
                        <form method="GET" action="/foros" class="d-flex gap-2">
                            <?php if (!empty($filtros['categoria'])): ?>
                                <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($filtros['categoria']); ?>">
                            <?php endif; ?>
                            <?php if (!empty($filtros['busqueda'])): ?>
                                <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
                            <?php endif; ?>
                            
                            <select name="uni" class="form-select border-0 shadow-none fw-semibold" style="border-radius: 10px; background: #fff; color: #1E293B;" onchange="this.form.submit()">
                                <option value="ALL" <?php echo empty($universidad_id) ? 'selected' : ''; ?>>🌐 Todas las Universidades</option>
                                <hr>
                                <?php foreach ($universidades as $uni): ?>
                                    <option value="<?php echo $uni['universidad_id']; ?>" <?php echo ($universidad_id === $uni['universidad_id']) ? 'selected' : ''; ?>>
                                        🎓 <?php echo htmlspecialchars($uni['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BARRA DE ACCIÓN Y BÚSQUEDA -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <form method="GET" action="/foros" class="d-flex gap-2 flex-grow-1" style="max-width: 500px;">
            <?php if (!empty($universidad_id)): ?>
                <input type="hidden" name="uni" value="<?php echo htmlspecialchars($universidad_id); ?>">
            <?php endif; ?>
            <?php if (!empty($filtros['categoria'])): ?>
                <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($filtros['categoria']); ?>">
            <?php endif; ?>
            
            <div class="input-group shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" name="busqueda" class="form-control border-0 shadow-none ps-1" placeholder="Buscar por palabra clave en el foro..." value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
                <button type="submit" class="btn btn-dark px-3 fw-semibold">Buscar</button>
            </div>
            <?php if (!empty($filtros['busqueda']) || !empty($filtros['categoria'])): ?>
                <a href="/foros<?php echo !empty($universidad_id) ? '?uni='.$universidad_id : ''; ?>" class="btn btn-outline-secondary d-flex align-items-center shadow-sm" title="Limpiar filtros" style="border-radius: 12px;">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>

        <button type="button" class="btn btn-primary px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCrearForo" style="border-radius: 12px; background: linear-gradient(135deg, #3B82F6, #2563EB); border: none;">
            <i class="fas fa-plus-circle"></i> Crear Publicación
        </button>
    </div>

    <!-- PESTAÑAS DE CATEGORÍAS (PILLS) -->
    <div class="d-flex gap-2 overflow-auto pb-2 mb-4" style="scrollbar-width: thin; white-space: nowrap;">
        <a href="/foros<?php echo !empty($universidad_id) ? '?uni='.$universidad_id : ''; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda='.urlencode($filtros['busqueda']) : ''; ?>" 
           class="btn px-4 py-2 fw-bold rounded-pill transition-all <?php echo empty($filtros['categoria']) ? 'btn-dark shadow-sm' : 'bg-white text-dark border'; ?>" 
           style="font-size: 0.9rem;">
            🔥 Todo el Feed
        </a>

        <?php foreach ($categorias as $cat): 
            $cod = $cat['codigo'];
            $style_info = $iconos_cat[$cod] ?? ['icon' => 'fa-tag', 'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => $cat['nombre']];
            $is_active = ($filtros['categoria'] === $cod);
        ?>
            <a href="/foros?categoria=<?php echo urlencode($cod); ?><?php echo !empty($universidad_id) ? '&uni='.$universidad_id : ''; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda='.urlencode($filtros['busqueda']) : ''; ?>" 
               class="btn px-4 py-2 fw-semibold rounded-pill d-flex align-items-center gap-2 transition-all <?php echo $is_active ? 'shadow-sm text-white' : 'bg-white text-dark border'; ?>"
               style="font-size: 0.9rem; <?php echo $is_active ? 'background: '.$style_info['color'].'; border-color: '.$style_info['color'].';' : ''; ?>">
                <i class="fas <?php echo $style_info['icon']; ?>" style="color: <?php echo $is_active ? '#fff' : $style_info['color']; ?>;"></i>
                <?php echo htmlspecialchars($style_info['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- LISTA DE PUBLICACIONES (FEED) -->
    <?php if (empty($foros)): ?>
        <div class="card border-0 shadow-sm text-center py-5 my-3" style="border-radius: 16px;">
            <div class="card-body py-5">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; color: #94A3B8; font-size: 2.5rem;">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-dark">No hay publicaciones aún en esta comunidad o categoría</h4>
                <p class="text-muted max-w-md mx-auto mb-4" style="max-width: 450px;">
                    ¡Sé el primero en iniciar una conversación, consultar dudas o compartir consejos con tus compañeros!
                </p>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearForo">
                    <i class="fas fa-plus me-2"></i> Iniciar Hilo Ahora
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($foros as $foro): 
                $cod = $foro['categoria_codigo'];
                $cat_info = $iconos_cat[$cod] ?? ['icon' => 'fa-tag', 'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => $foro['categoria_nombre'] ?? 'General'];
                $iniciales = strtoupper(substr($foro['nombres'] ?? 'U', 0, 1) . substr($foro['apellido_paterno'] ?? '', 0, 1));
                $es_mio = ($foro['usuario_id'] == $usuario_actual['usuario_id']);
            ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm transition-all h-100" style="border-radius: 14px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.06)'" onmouseout="this.style.transform='none'; this.style.boxShadow='0 .125rem .25rem rgba(0,0,0,.075)'">
                        <div class="card-body p-4">
                            <!-- Cabecera de tarjeta: Autor + Categoría + Universidad -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" 
                                         style="width: 42px; height: 42px; background: linear-gradient(135deg, <?php echo $cat_info['color']; ?>, #1E293B); font-size: 0.9rem;">
                                        <?php echo $iniciales; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                            <?php echo htmlspecialchars($foro['nombres'] . ' ' . ($foro['apellido_paterno'] ?? '')); ?>
                                            <?php if ($es_mio): ?>
                                                <span class="badge bg-light text-primary border px-2 py-0" style="font-size: 0.7rem;">Tú</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small d-flex align-items-center gap-2">
                                            <span><i class="far fa-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($foro['fecha_creacion'])); ?></span>
                                            <?php if (!empty($foro['universidad_nombre'])): ?>
                                                <span>•</span>
                                                <span class="text-secondary fw-semibold"><i class="fas fa-university me-1"></i> <?php echo htmlspecialchars($foro['universidad_nombre']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge px-3 py-2 rounded-pill fw-bold" style="background: <?php echo $cat_info['bg']; ?>; color: <?php echo $cat_info['color']; ?>; font-size: 0.8rem;">
                                        <i class="fas <?php echo $cat_info['icon']; ?> me-1"></i> <?php echo htmlspecialchars($cat_info['label']); ?>
                                    </span>
                                    <?php if ($es_mio): ?>
                                        <form method="POST" action="/foros/eliminar" onsubmit="return confirmarAccionSweet(event, '¿Estás seguro de eliminar tu publicación?', 'Tu hilo dejará de estar visible en el foro estudiantil.');" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $foro['foro_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-light text-danger border-0 rounded-circle" title="Eliminar publicación" style="width: 32px; height: 32px;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Título y Descripción -->
                            <a href="/foros/ver?id=<?php echo $foro['foro_id']; ?>" class="text-decoration-none">
                                <h4 class="fw-bold text-dark mb-2 hover-primary transition-all" style="line-height: 1.3;">
                                    <?php echo htmlspecialchars($foro['titulo']); ?>
                                </h4>
                            </a>
                            <p class="text-secondary mb-3" style="font-size: 0.98rem; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo nl2br(htmlspecialchars(substr($foro['descripcion'] ?? '', 0, 300) . (strlen($foro['descripcion'] ?? '') > 300 ? '...' : ''))); ?>
                            </p>

                            <!-- Pie de tarjeta: Comentarios y Reacciones -->
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-2">
                                <div class="d-flex align-items-center gap-3">
                                    <a href="/foros/ver?id=<?php echo $foro['foro_id']; ?>" class="btn btn-sm bg-light text-dark fw-bold rounded-pill px-3 py-1 text-decoration-none d-flex align-items-center gap-2 border">
                                        <i class="far fa-comment-dots text-primary"></i> 
                                        <span><?php echo (int)$foro['total_comentarios']; ?></span>
                                        <span class="text-muted fw-normal d-none d-sm-inline">comentario<?php echo ((int)$foro['total_comentarios'] !== 1) ? 's' : ''; ?></span>
                                    </a>

                                    <div class="d-flex align-items-center gap-1 text-muted small fw-semibold bg-light px-3 py-1 rounded-pill border">
                                        <span title="Reacciones">👍 ❤️ 🔥</span>
                                        <span class="ms-1 text-dark fw-bold"><?php echo (int)$foro['total_reacciones']; ?></span>
                                    </div>
                                </div>

                                <a href="/foros/ver?id=<?php echo $foro['foro_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                    Participar <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación del foro" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): 
                        $url_pag = "/foros?pagina=" . $i;
                        if (!empty($universidad_id)) $url_pag .= "&uni=" . $universidad_id;
                        if (!empty($filtros['categoria'])) $url_pag .= "&categoria=" . urlencode($filtros['categoria']);
                        if (!empty($filtros['busqueda'])) $url_pag .= "&busqueda=" . urlencode($filtros['busqueda']);
                    ?>
                        <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>">
                            <a class="page-link rounded-circle mx-1 fw-bold shadow-sm d-flex align-items-center justify-content-center" href="<?php echo $url_pag; ?>" style="width: 40px; height: 40px; border: none; <?php echo ($pagina == $i) ? 'background: #2563EB; color: #fff;' : 'background: #fff; color: #334155;'; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- MODAL CREAR PUBLICACIÓN -->
<div class="modal fade" id="modalCrearForo" tabindex="-1" aria-labelledby="modalCrearForoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #1E293B, #334155);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 45px; height: 45px; font-size: 1.2rem;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalCrearForoLabel">Nueva Publicación en la Comunidad</h5>
                        <small class="text-light opacity-75">Comparte dudas, avisos o recomendaciones con tus compañeros</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="/foros/guardar">
                <div class="modal-body p-4 p-md-5 bg-light">
                    <div class="row g-3">
                        <!-- Categoría / Eje -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">Eje / Categoría <span class="text-danger">*</span></label>
                            <select name="categoria_codigo" class="form-select border-0 shadow-sm py-2 fw-semibold" required style="border-radius: 10px;">
                                <option value="">Selecciona una categoría...</option>
                                <?php foreach ($categorias as $cat): 
                                    $cod = $cat['codigo'];
                                    $cat_info = $iconos_cat[$cod] ?? ['label' => $cat['nombre']];
                                ?>
                                    <option value="<?php echo $cod; ?>"><?php echo htmlspecialchars($cat_info['label'] ?? $cat['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Comunidad Universitaria -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">Comunidad Académica <span class="text-danger">*</span></label>
                            <select name="universidad_id" class="form-select border-0 shadow-sm py-2 fw-semibold" style="border-radius: 10px;">
                                <option value="">🌐 Para Todas las Universidades</option>
                                <?php foreach ($universidades as $uni): ?>
                                    <option value="<?php echo $uni['universidad_id']; ?>" <?php echo ($universidad_id === $uni['universidad_id'] || (!empty($usuario_actual['universidad_id']) && $usuario_actual['universidad_id'] === $uni['universidad_id'])) ? 'selected' : ''; ?>>
                                        🎓 <?php echo htmlspecialchars($uni['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Título -->
                        <div class="col-12 mt-4">
                            <label class="form-label fw-bold text-dark small">Título de la publicación <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control border-0 shadow-sm py-2 fw-semibold" required maxlength="150" placeholder="Ej. ¿Alguien buscando roomie cerca a la puerta 3 de la universidad?" style="border-radius: 10px;">
                        </div>

                        <!-- Descripción -->
                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold text-dark small">Detalles o descripción <span class="text-muted fw-normal">(Opcional)</span></label>
                            <textarea name="descripcion" class="form-control border-0 shadow-sm py-3" rows="5" placeholder="Brinda más contexto, precios, horarios, zona exacta, etc..." style="border-radius: 10px;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-4 bg-white border-top justify-content-between">
                    <button type="button" class="btn btn-light fw-bold px-4 py-2" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-5 py-2 shadow-sm" style="border-radius: 10px; background: linear-gradient(135deg, #3B82F6, #2563EB); border: none;">
                        <i class="fas fa-paper-plane me-2"></i> Publicar Ahora
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
