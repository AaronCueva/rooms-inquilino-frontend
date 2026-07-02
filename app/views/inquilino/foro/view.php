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

$cod = $foro['categoria_codigo'];
$cat_info = $iconos_cat[$cod] ?? ['icon' => 'fa-tag', 'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => $foro['categoria_nombre'] ?? 'General'];
$iniciales = strtoupper(substr($foro['nombres'] ?? 'U', 0, 1) . substr($foro['apellido_paterno'] ?? '', 0, 1));
$es_mio = ($foro['usuario_id'] == $usuario_id);

// Organizar comentarios en árbol por padre
$arbol = [];
$hijos_por_padre = [];
foreach ($comentarios as $c) {
    if (empty($c['comentario_padre_id'])) {
        $arbol[] = $c;
    } else {
        $hijos_por_padre[$c['comentario_padre_id']][] = $c;
    }
}

// Función auxiliar recursiva para renderizar comentarios con límite de indentación de 3 niveles (0, 1, 2)
if (!function_exists('renderizarComentario')) {
    function renderizarComentario($comentario, $hijos_por_padre, $nivel = 0, $usuario_id = null) {
        $id = $comentario['foro_comentario_id'];
        $autor = trim(($comentario['nombres'] ?? 'Estudiante') . ' ' . ($comentario['apellido_paterno'] ?? ''));
        $inicial = strtoupper(substr($comentario['nombres'] ?? 'E', 0, 1));
        $fecha = date('d/m/Y H:i', strtotime($comentario['fecha_envio']));
        $es_autor = ($comentario['usuario_id'] == $usuario_id);

        // Limitar indentación máxima a 2 niveles de sangría (Nieto)
        $indent_rem = min($nivel, 2) * 2.5;
        ?>
        <div class="comentario-item mb-3 transition-all" id="comentario-<?php echo $id; ?>" style="margin-left: <?php echo $indent_rem; ?>rem;">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; background: <?php echo ($nivel > 0) ? '#F8FAFC' : '#fff'; ?>; border-left: <?php echo ($nivel > 0) ? '3px solid #CBD5E1 !important' : 'none'; ?>;">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                <?php echo $inicial; ?>
                            </div>
                            <div>
                                <span class="fw-bold text-dark small"><?php echo htmlspecialchars($autor); ?></span>
                                <?php if ($es_autor): ?>
                                    <span class="badge bg-light text-primary border px-1 py-0 ms-1" style="font-size: 0.65rem;">Tú</span>
                                <?php endif; ?>
                                <?php if ($nivel > 0): ?>
                                    <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.65rem;"><i class="fas fa-reply me-1"></i>Respuesta</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small" style="font-size: 0.75rem;"><i class="far fa-clock me-1"></i><?php echo $fecha; ?></span>
                            <?php if ($es_autor): ?>
                                <form method="POST" action="/foros/eliminarComentario" onsubmit="return confirmarAccionSweet(event, '¿Eliminar este comentario?', 'No podrás recuperar tu aporte ni las respuestas asociadas.');" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="foro_id" value="<?php echo $comentario['foro_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-1" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="text-dark mb-2" style="font-size: 0.95rem; line-height: 1.5; word-break: break-word;">
                        <?php echo nl2br(htmlspecialchars($comentario['mensaje'])); ?>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-bold small" onclick="responderA('<?php echo $id; ?>', '<?php echo addslashes($autor); ?>')">
                            <i class="fas fa-reply me-1"></i> Responder
                        </button>
                    </div>
                </div>
            </div>

            <?php
            // Renderizar hijos de este comentario si existen
            if (!empty($hijos_por_padre[$id])) {
                foreach ($hijos_por_padre[$id] as $hijo) {
                    renderizarComentario($hijo, $hijos_por_padre, $nivel + 1, $usuario_id);
                }
            }
            ?>
        </div>
        <?php
    }
}
?>

<div class="page-content" style="background: #F8FAFC; min-height: calc(100vh - 70px); padding-bottom: 60px;">
    <!-- BOTÓN VOLVER -->
    <div class="mb-4">
        <a href="/foros" class="btn btn-white bg-white text-dark fw-bold border shadow-sm rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2 transition-all hover-shadow">
            <i class="fas fa-arrow-left text-primary"></i> Volver a la Comunidad
        </a>
    </div>

    <div class="row g-4">
        <!-- COLUMNA PRINCIPAL: HILO Y COMENTARIOS -->
        <div class="col-lg-8">
            <!-- TARJETA DEL HILO PRINCIPAL -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; overflow: hidden;">
                <div class="card-body p-4 p-md-5">
                    <!-- Cabecera de Publicación -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" 
                                 style="width: 52px; height: 52px; background: linear-gradient(135deg, <?php echo $cat_info['color']; ?>, #1E293B); font-size: 1.1rem;">
                                <?php echo $iniciales; ?>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2" style="font-size: 1.1rem;">
                                    <?php echo htmlspecialchars(($foro['nombres'] ?? 'Estudiante') . ' ' . ($foro['apellido_paterno'] ?? '')); ?>
                                    <?php if ($es_mio): ?>
                                        <span class="badge bg-light text-primary border px-2 py-0" style="font-size: 0.75rem;">Tú</span>
                                    <?php endif; ?>
                                </h6>
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
                            <span class="badge px-3 py-2 rounded-pill fw-bold" style="background: <?php echo $cat_info['bg']; ?>; color: <?php echo $cat_info['color']; ?>; font-size: 0.85rem;">
                                <i class="fas <?php echo $cat_info['icon']; ?> me-1"></i> <?php echo htmlspecialchars($cat_info['label']); ?>
                            </span>
                            <?php if ($es_mio): ?>
                                <form method="POST" action="/foros/eliminar" onsubmit="return confirmarAccionSweet(event, '¿Estás seguro de eliminar tu publicación?', 'Toda la conversación y comentarios serán archivados.');" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $foro['foro_id']; ?>">
                                    <button type="submit" class="btn btn-light text-danger border rounded-circle d-flex align-items-center justify-content-center" title="Eliminar publicación" style="width: 36px; height: 36px;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Título del Hilo -->
                    <h2 class="fw-extrabold text-dark mb-4" style="line-height: 1.3; font-size: 1.8rem;">
                        <?php echo htmlspecialchars($foro['titulo']); ?>
                    </h2>

                    <!-- Cuerpo del Mensaje -->
                    <div class="text-secondary mb-5" style="font-size: 1.08rem; line-height: 1.8; word-break: break-word;">
                        <?php echo nl2br(htmlspecialchars($foro['descripcion'] ?? '')); ?>
                    </div>

                    <!-- BARRA DE REACCIONES ASÍNCRONAS (AJAX) -->
                    <div class="p-3 rounded-4 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="fw-bold text-dark small">
                            <i class="fas fa-heart text-danger me-1"></i> ¿Qué te parece esta publicación?
                        </div>
                        <div class="d-flex gap-2 flex-wrap" id="contenedor-reacciones">
                            <!-- TRFO001: Me sirve 👍 -->
                            <button type="button" class="btn rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2 transition-all reaccion-btn <?php echo ($mi_reaccion === 'TRFO001') ? 'btn-primary shadow-sm text-white' : 'btn-white bg-white text-dark border'; ?>" 
                                    onclick="reaccionarAjax('<?php echo $foro['foro_id']; ?>', 'TRFO001', this)">
                                <span>👍</span> 
                                <span>Me sirve</span> 
                                <span class="badge <?php echo ($mi_reaccion === 'TRFO001') ? 'bg-white text-primary' : 'bg-light text-dark'; ?> rounded-pill ms-1 count-TRFO001"><?php echo (int)($reacciones['TRFO001'] ?? 0); ?></span>
                            </button>

                            <!-- TRFO002: Gracias ❤️ -->
                            <button type="button" class="btn rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2 transition-all reaccion-btn <?php echo ($mi_reaccion === 'TRFO002') ? 'btn-danger shadow-sm text-white' : 'btn-white bg-white text-dark border'; ?>" 
                                    onclick="reaccionarAjax('<?php echo $foro['foro_id']; ?>', 'TRFO002', this)">
                                <span>❤️</span> 
                                <span>Gracias</span> 
                                <span class="badge <?php echo ($mi_reaccion === 'TRFO002') ? 'bg-white text-danger' : 'bg-light text-dark'; ?> rounded-pill ms-1 count-TRFO002"><?php echo (int)($reacciones['TRFO002'] ?? 0); ?></span>
                            </button>

                            <!-- TRFO003: Top Recomendación 🔥 -->
                            <button type="button" class="btn rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2 transition-all reaccion-btn <?php echo ($mi_reaccion === 'TRFO003') ? 'btn-warning text-dark shadow-sm' : 'btn-white bg-white text-dark border'; ?>" 
                                    onclick="reaccionarAjax('<?php echo $foro['foro_id']; ?>', 'TRFO003', this)">
                                <span>🔥</span> 
                                <span>Top</span> 
                                <span class="badge <?php echo ($mi_reaccion === 'TRFO003') ? 'bg-dark text-warning' : 'bg-light text-dark'; ?> rounded-pill ms-1 count-TRFO003"><?php echo (int)($reacciones['TRFO003'] ?? 0); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE COMENTARIOS Y RESPUESTAS -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-comments text-primary"></i> 
                        Comentarios (<span id="total-comentarios-ui"><?php echo count($comentarios); ?></span>)
                    </h5>
                    <a href="#caja-responder" class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                        <i class="fas fa-plus me-1"></i> Escribir Comentario
                    </a>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($arbol)): ?>
                        <div class="text-center py-5 my-2">
                            <i class="far fa-comment-dots text-muted mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                            <h6 class="fw-bold text-dark">Aún no hay comentarios en esta conversación</h6>
                            <p class="text-muted small mb-0">¡Sé el primero en aportar un consejo o responder la duda!</p>
                        </div>
                    <?php else: ?>
                        <div class="lista-comentarios">
                            <?php foreach ($arbol as $comentario_padre): ?>
                                <?php renderizarComentario($comentario_padre, $hijos_por_padre, 0, $usuario_id); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CAJA DE RESPUESTA / NUEVO COMENTARIO -->
            <div class="card border-0 shadow-sm" id="caja-responder" style="border-radius: 16px; scroll-margin-top: 100px;">
                <div class="card-body p-4 p-md-5">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-pen text-primary"></i> Deja tu aporte o comentario
                    </h5>
                    
                    <div id="alerta-respuesta-padre" class="alert alert-info py-2 px-3 d-none align-items-center justify-content-between mb-3 rounded-3" style="font-size: 0.9rem;">
                        <div>
                            <i class="fas fa-reply me-2"></i> Respondiendo al comentario de: <strong id="nombre-autor-padre"></strong>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-dark p-0 fw-bold text-decoration-none" onclick="cancelarRespuesta()">
                            <i class="fas fa-times me-1"></i> Cancelar respuesta
                        </button>
                    </div>

                    <form method="POST" action="/foros/comentar">
                        <input type="hidden" name="foro_id" value="<?php echo $foro['foro_id']; ?>">
                        <input type="hidden" name="comentario_padre_id" id="input_comentario_padre_id" value="">
                        
                        <div class="mb-3">
                            <textarea name="mensaje" id="textarea_mensaje" class="form-control border shadow-sm p-3" rows="4" required placeholder="Escribe aquí tu consejo, respuesta o recomendación para la comunidad..." style="border-radius: 12px; font-size: 1rem;"></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" style="border-radius: 12px; background: linear-gradient(135deg, #3B82F6, #2563EB); border: none;">
                                <i class="fas fa-paper-plane"></i> Publicar Comentario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- COLUMNA LATERAL: INFORMACIÓN Y TIPS DE CONVIVENCIA -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; background: linear-gradient(135deg, #EFF6FF, #DBEAFE);">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-lightbulb me-2"></i>Normas de la Comunidad</h5>
                    <ul class="list-unstyled text-dark small mb-0 d-flex flex-column gap-2" style="line-height: 1.6;">
                        <li><i class="fas fa-check-circle text-primary me-2"></i> <strong>Se respetuoso:</strong> Evita lenguaje ofensivo o ataques personales hacia compañeros o propietarios.</li>
                        <li><i class="fas fa-check-circle text-primary me-2"></i> <strong>No compartas datos privados:</strong> Por tu seguridad, no publiques contraseñas, números de cuenta o direcciones exactas públicamente.</li>
                        <li><i class="fas fa-check-circle text-primary me-2"></i> <strong>Verifica antes de rentar:</strong> Las recomendaciones de zónas son referenciales. Realiza siempre un contrato formal.</li>
                    </ul>
                </div>
            </div>

            <!-- Botón flotante para explorar otras categorías -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-tags me-2 text-warning"></i>Ejes Universitarios</h6>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($categorias as $cat): 
                            $c_cod = $cat['codigo'];
                            $c_info = $iconos_cat[$c_cod] ?? ['icon' => 'fa-tag', 'label' => $cat['nombre'], 'color' => '#64748B'];
                        ?>
                            <a href="/foros?categoria=<?php echo urlencode($c_cod); ?>" class="btn btn-light bg-white text-start fw-semibold border d-flex align-items-center justify-content-between py-2 px-3 rounded-3 hover-shadow transition-all">
                                <span>
                                    <i class="fas <?php echo $c_info['icon']; ?> me-2" style="color: <?php echo $c_info['color']; ?>;"></i>
                                    <?php echo htmlspecialchars($c_info['label']); ?>
                                </span>
                                <i class="fas fa-chevron-right small text-muted"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Lógica AJAX para enviar reacciones dinámicamente con Fetch API
 */
function reaccionarAjax(foroId, tipoReaccion, botonClickeado) {
    // Deshabilitar momentáneamente para evitar doble clic
    botonClickeado.disabled = true;

    fetch('/foros/reaccionar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            foro_id: foroId,
            tipo: tipoReaccion
        })
    })
    .then(response => response.json())
    .then(data => {
        botonClickeado.disabled = false;
        if (data.success) {
            // Actualizar contadores
            if (data.conteos) {
                document.querySelectorAll('.count-TRFO001').forEach(el => el.textContent = data.conteos['TRFO001'] || 0);
                document.querySelectorAll('.count-TRFO002').forEach(el => el.textContent = data.conteos['TRFO002'] || 0);
                document.querySelectorAll('.count-TRFO003').forEach(el => el.textContent = data.conteos['TRFO003'] || 0);
            }

            // Reiniciar estilos de los botones
            const botones = document.querySelectorAll('.reaccion-btn');
            botones.forEach(btn => {
                btn.className = 'btn rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2 transition-all reaccion-btn btn-white bg-white text-dark border';
                const badge = btn.querySelector('.badge');
                if (badge) badge.className = 'badge bg-light text-dark rounded-pill ms-1 ' + badge.className.split(' ').pop();
            });

            // Resaltar el botón activo si no fue removido
            if (data.mi_reaccion) {
                if (data.mi_reaccion === 'TRFO001') {
                    botonClickeado.className = 'btn rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2 transition-all reaccion-btn btn-primary shadow-sm text-white';
                    botonClickeado.querySelector('.badge').className = 'badge bg-white text-primary rounded-pill ms-1 count-TRFO001';
                } else if (data.mi_reaccion === 'TRFO002') {
                    botonClickeado.className = 'btn rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2 transition-all reaccion-btn btn-danger shadow-sm text-white';
                    botonClickeado.querySelector('.badge').className = 'badge bg-white text-danger rounded-pill ms-1 count-TRFO002';
                } else if (data.mi_reaccion === 'TRFO003') {
                    botonClickeado.className = 'btn rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2 transition-all reaccion-btn btn-warning text-dark shadow-sm';
                    botonClickeado.querySelector('.badge').className = 'badge bg-dark text-warning rounded-pill ms-1 count-TRFO003';
                }
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo reaccionar',
                text: data.error || 'Ocurrió un problema al procesar tu reacción',
                confirmButtonColor: '#2563EB',
                customClass: {
                    popup: 'rounded-4 shadow-lg border-0',
                    confirmButton: 'btn btn-primary px-4 py-2 rounded-pill fw-bold'
                },
                buttonsStyling: false
            });
        }
    })
    .catch(error => {
        botonClickeado.disabled = false;
        console.error('Error AJAX Reacción:', error);
    });
}

/**
 * Lógica para responder a un comentario específico en el árbol (Padre -> Hijo -> Nieto)
 */
function responderA(comentarioId, autorNombre) {
    document.getElementById('input_comentario_padre_id').value = comentarioId;
    document.getElementById('nombre-autor-padre').textContent = autorNombre;
    
    const alerta = document.getElementById('alerta-respuesta-padre');
    alerta.classList.remove('d-none');
    alerta.classList.add('d-flex');

    const textarea = document.getElementById('textarea_mensaje');
    textarea.placeholder = `Escribe tu respuesta a ${autorNombre}...`;
    textarea.focus();

    // Scroll suave hacia la caja de respuesta
    document.getElementById('caja-responder').scrollIntoView({ behavior: 'smooth' });
}

function cancelarRespuesta() {
    document.getElementById('input_comentario_padre_id').value = '';
    const alerta = document.getElementById('alerta-respuesta-padre');
    alerta.classList.add('d-none');
    alerta.classList.remove('d-flex');

    const textarea = document.getElementById('textarea_mensaje');
    textarea.placeholder = 'Escribe aquí tu consejo, respuesta o recomendación para la comunidad...';
}
</script>
