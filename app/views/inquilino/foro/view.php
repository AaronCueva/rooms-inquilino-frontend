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

$cod = $foro['categoria_codigo'];
$cat_info = $iconos_cat[$cod] ?? ['icon' => 'fa-tag', 'color' => '#6B6F7A', 'bg' => '#F1F5F9', 'label' => $foro['categoria_nombre'] ?? 'General'];
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
        <div class="fr-comment" id="comentario-<?php echo $id; ?>" style="margin-left: <?php echo $indent_rem; ?>rem;">
            <div class="fr-comment-card<?php echo ($nivel > 0) ? ' fr-comment-reply' : ''; ?>">
                <div class="fr-comment-top">
                    <div class="fr-author">
                        <div class="fr-avatar fr-avatar-sm" style="background: linear-gradient(135deg, var(--pd-primary), var(--pd-ink));"><?php echo htmlspecialchars($inicial); ?></div>
                        <div>
                            <div class="fr-name">
                                <?php echo htmlspecialchars($autor); ?>
                                <?php if ($es_autor): ?>
                                    <span class="fr-badge" style="background:var(--pd-lime); color:var(--pd-ink)">Tú</span>
                                <?php endif; ?>
                                <?php if ($nivel > 0): ?>
                                    <span class="fr-badge" style="background:var(--pd-paper); color:var(--pd-muted)"><i class="fas fa-reply"></i> Respuesta</span>
                                <?php endif; ?>
                            </div>
                            <div class="fr-meta">
                                <span><i class="far fa-clock"></i> <?php echo $fecha; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="fr-comment-actions">
                        <?php if ($es_autor): ?>
                            <form method="POST" action="/foros/eliminarComentario" onsubmit="return confirmarAccionSweet(event, '¿Eliminar este comentario?', 'No podrás recuperar tu aporte ni las respuestas asociadas.');" class="d-inline">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <input type="hidden" name="foro_id" value="<?php echo $comentario['foro_id']; ?>">
                                <button type="submit" class="fr-del" title="Eliminar comentario"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="fr-comment-body">
                    <?php echo nl2br(htmlspecialchars($comentario['mensaje'], ENT_QUOTES, 'UTF-8')); ?>
                </div>

                <div class="fr-comment-foot">
                    <button type="button" class="fr-reply-btn" onclick="responderA('<?php echo $id; ?>', '<?php echo addslashes($autor); ?>')">
                        <i class="fas fa-reply"></i> Responder
                    </button>
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

<style>
  .fr-wrap { max-width: var(--pd-maxw); margin: 0 auto; padding: 28px 24px 72px; }
  .fr-back { display: inline-flex; align-items: center; gap: 10px; font-family: var(--pd-body); font-weight: 600; font-size: 14px; color: var(--pd-ink); background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: 999px; padding: 10px 18px; text-decoration: none; box-shadow: var(--pd-sh-1); transition: all .2s; margin-bottom: 24px; }
  .fr-back:hover { border-color: var(--pd-ink); transform: translateY(-1px); }
  .fr-back i { color: var(--pd-primary); }
  .fr-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
  @media (min-width: 992px) { .fr-grid { grid-template-columns: 1fr 340px; } }
  .fr-col { display: flex; flex-direction: column; gap: 20px; }

  /* Tarjeta base pd-* */
  .fr-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); box-shadow: var(--pd-sh-1); }
  .fr-card-pad { padding: 26px 28px; }

  /* Hilo principal */
  .fr-thread-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
  .fr-author { display: flex; align-items: center; gap: 12px; }
  .fr-avatar { width: 44px; height: 44px; border-radius: 999px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 14px; flex-shrink: 0; }
  .fr-avatar-lg { width: 52px; height: 52px; font-size: 16px; }
  .fr-avatar-sm { width: 34px; height: 34px; font-size: 12px; }
  .fr-author .fr-name { font-weight: 700; color: var(--pd-ink); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .fr-author .fr-meta { font-size: 13px; color: var(--pd-muted); display: flex; align-items: center; gap: 8px; margin-top: 3px; flex-wrap: wrap; }
  .fr-badge { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 999px; }
  .fr-top-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .fr-del { background: transparent; border: 1px solid var(--pd-line); color: var(--pd-accent); width: 34px; height: 34px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; }
  .fr-del:hover { background: var(--pd-accent); color: #fff; border-color: var(--pd-accent); }

  .fr-thread-title { font-family: var(--pd-display); font-size: clamp(24px, 3.2vw, 32px); font-weight: 700; letter-spacing: -.02em; line-height: 1.2; color: var(--pd-ink); margin: 6px 0 16px; }
  .fr-thread-body { color: var(--pd-muted); font-size: 16px; line-height: 1.75; word-break: break-word; }

  /* Reacciones */
  .fr-reactions { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; margin-top: 24px; padding: 16px 18px; background: var(--pd-paper); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); }
  .fr-reactions-label { font-family: var(--pd-body); font-weight: 700; font-size: 14px; color: var(--pd-ink); display: inline-flex; align-items: center; gap: 8px; }
  .fr-reactions-label i { color: var(--pd-accent); }
  .fr-reactions-list { display: flex; gap: 10px; flex-wrap: wrap; }
  .fr-rxn { display: inline-flex; align-items: center; gap: 8px; font-family: var(--pd-body); font-weight: 600; font-size: 14px; padding: 8px 14px; border-radius: 999px; border: 1.5px solid var(--pd-line); background: var(--pd-surface); color: var(--pd-ink); cursor: pointer; transition: all .2s; }
  .fr-rxn:hover { border-color: var(--pd-ink); transform: translateY(-1px); }
  .fr-rxn.is-on { background: var(--pd-ink); color: #fff; border-color: var(--pd-ink); }
  .fr-rxn.is-on .fr-rxn-count { background: rgba(255,255,255,.22); color: #fff; }
  .fr-rxn.is-on.rxn-001 { background: var(--pd-primary); border-color: var(--pd-primary); }
  .fr-rxn.is-on.rxn-002 { background: var(--pd-accent); border-color: var(--pd-accent); }
  .fr-rxn.is-on.rxn-003 { background: #F59E0B; border-color: #F59E0B; }
  .fr-rxn-count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; background: var(--pd-paper); color: var(--pd-ink); font-size: 12px; font-weight: 700; }

  /* Comentarios */
  .fr-comments-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 20px 28px; border-bottom: 1px solid var(--pd-line); flex-wrap: wrap; }
  .fr-comments-head h3 { font-family: var(--pd-display); font-size: 18px; font-weight: 700; color: var(--pd-ink); margin: 0; display: inline-flex; align-items: center; gap: 10px; }
  .fr-comments-head h3 i { color: var(--pd-primary); }
  .fr-comments-body { padding: 24px 28px; }
  .fr-comment { margin-bottom: 14px; }
  .fr-comment-card { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 16px 18px; box-shadow: var(--pd-sh-1); }
  .fr-comment-reply { background: var(--pd-paper); border-left: 3px solid var(--pd-primary); }
  .fr-comment-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
  .fr-comment-actions { display: flex; align-items: center; gap: 8px; }
  .fr-comment-body { color: var(--pd-ink); font-size: 15px; line-height: 1.6; word-break: break-word; }
  .fr-comment-foot { margin-top: 10px; text-align: right; }
  .fr-reply-btn { background: transparent; border: none; color: var(--pd-primary); font-family: var(--pd-body); font-weight: 700; font-size: 13px; cursor: pointer; padding: 0; display: inline-flex; align-items: center; gap: 6px; }
  .fr-reply-btn:hover { text-decoration: underline; }
  .fr-empty { text-align: center; padding: 48px 20px; }
  .fr-empty i { font-size: 40px; color: var(--pd-muted); opacity: .45; }
  .fr-empty h4 { font-family: var(--pd-display); font-size: 18px; font-weight: 700; color: var(--pd-ink); margin: 14px 0 6px; }
  .fr-empty p { color: var(--pd-muted); font-size: 14px; margin: 0; }

  /* Caja de respuesta */
  .fr-reply-box { scroll-margin-top: 100px; }
  .fr-reply-box h3 { font-family: var(--pd-display); font-size: 18px; font-weight: 700; color: var(--pd-ink); margin: 0 0 14px; display: inline-flex; align-items: center; gap: 10px; }
  .fr-reply-box h3 i { color: var(--pd-primary); }
  .fr-reply-alert { display: none; align-items: center; justify-content: space-between; gap: 12px; background: var(--pd-paper); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 10px 14px; margin-bottom: 14px; font-size: 14px; color: var(--pd-ink); }
  .fr-reply-alert.is-on { display: flex; }
  .fr-reply-alert button { background: transparent; border: none; color: var(--pd-ink); font-weight: 700; cursor: pointer; }
  .fr-textarea { width: 100%; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 14px 16px; font-family: var(--pd-body); font-size: 15px; color: var(--pd-ink); resize: vertical; outline: none; }
  .fr-textarea:focus { border-color: var(--pd-primary); }
  .fr-form-actions { display: flex; justify-content: flex-end; margin-top: 14px; }

  /* Lateral */
  .fr-side { display: flex; flex-direction: column; gap: 20px; }
  .fr-rules { background: linear-gradient(135deg, #E9F0FF, #F4F7FF); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 22px 24px; box-shadow: var(--pd-sh-1); }
  .fr-rules h3 { font-family: var(--pd-display); font-size: 16px; font-weight: 700; color: var(--pd-primary); margin: 0 0 14px; display: inline-flex; align-items: center; gap: 8px; }
  .fr-rules ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
  .fr-rules li { font-size: 13.5px; color: var(--pd-ink); line-height: 1.55; display: flex; gap: 10px; }
  .fr-rules li i { color: var(--pd-primary); margin-top: 2px; }
  .fr-rules li strong { color: var(--pd-ink); }

  .fr-axes h3 { font-family: var(--pd-display); font-size: 15px; font-weight: 700; color: var(--pd-ink); margin: 0 0 14px; display: inline-flex; align-items: center; gap: 8px; }
  .fr-axes h3 i { color: var(--pd-accent); }
  .fr-axes-list { display: flex; flex-direction: column; gap: 8px; }
  .fr-axis { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-family: var(--pd-body); font-weight: 600; font-size: 14px; color: var(--pd-ink); background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-sm); padding: 10px 14px; text-decoration: none; transition: all .2s; }
  .fr-axis:hover { border-color: var(--pd-ink); transform: translateY(-1px); }
  .fr-axis .fr-axis-l { display: inline-flex; align-items: center; gap: 10px; }
  .fr-axis .fr-axis-r i { color: var(--pd-muted); font-size: 12px; }
</style>

<section class="pd-body fr-wrap">
    <!-- BOTÓN VOLVER -->
    <a href="/foros" class="fr-back"><i class="fas fa-arrow-left"></i> Volver a la comunidad</a>

    <div class="fr-grid">
        <!-- COLUMNA PRINCIPAL: HILO Y COMENTARIOS -->
        <div class="fr-col">
            <!-- TARJETA DEL HILO PRINCIPAL -->
            <article class="fr-card fr-card-pad">
                <div class="fr-thread-top">
                    <div class="fr-author">
                        <div class="fr-avatar fr-avatar-lg" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($cat_info['color']); ?>, var(--pd-ink));"><?php echo htmlspecialchars($iniciales); ?></div>
                        <div>
                            <div class="fr-name">
                                <?php echo htmlspecialchars(($foro['nombres'] ?? 'Estudiante') . ' ' . ($foro['apellido_paterno'] ?? '')); ?>
                                <?php if ($es_mio): ?>
                                    <span class="fr-badge" style="background:var(--pd-lime); color:var(--pd-ink)">Tú</span>
                                <?php endif; ?>
                            </div>
                            <div class="fr-meta">
                                <span><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($foro['fecha_creacion'])); ?></span>
                                <?php if (!empty($foro['universidad_nombre'])): ?>
                                    <span>·</span>
                                    <span><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($foro['universidad_nombre'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="fr-top-actions">
                        <span class="fr-badge" style="background:<?php echo htmlspecialchars($cat_info['bg']); ?>; color:<?php echo htmlspecialchars($cat_info['color']); ?>">
                            <i class="fas <?php echo htmlspecialchars($cat_info['icon']); ?>"></i> <?php echo htmlspecialchars($cat_info['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <?php if ($es_mio): ?>
                            <form method="POST" action="/foros/eliminar" onsubmit="return confirmarAccionSweet(event, '¿Estás seguro de eliminar tu publicación?', 'Toda la conversación y comentarios serán archivados.');" class="d-inline">
                                <input type="hidden" name="id" value="<?php echo $foro['foro_id']; ?>">
                                <button type="submit" class="fr-del" title="Eliminar publicación"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <h2 class="fr-thread-title"><?php echo htmlspecialchars($foro['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="fr-thread-body"><?php echo nl2br(htmlspecialchars($foro['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>

                <!-- BARRA DE REACCIONES ASÍNCRONAS (AJAX) -->
                <div class="fr-reactions">
                    <div class="fr-reactions-label"><i class="fas fa-heart"></i> ¿Qué te parece esta publicación?</div>
                    <div class="fr-reactions-list" id="contenedor-reacciones">
                        <!-- TRFO001: Me sirve 👍 -->
                        <button type="button" class="fr-rxn rxn-001 reaccion-btn <?php echo ($mi_reaccion === 'TRFO001') ? 'is-on' : ''; ?>" onclick="reaccionarAjax('<?php echo $foro['foro_id']; ?>', 'TRFO001', this)">
                            <span>👍</span> <span>Me sirve</span>
                            <span class="fr-rxn-count count-TRFO001"><?php echo (int)($reacciones['TRFO001'] ?? 0); ?></span>
                        </button>

                        <!-- TRFO002: Gracias ❤️ -->
                        <button type="button" class="fr-rxn rxn-002 reaccion-btn <?php echo ($mi_reaccion === 'TRFO002') ? 'is-on' : ''; ?>" onclick="reaccionarAjax('<?php echo $foro['foro_id']; ?>', 'TRFO002', this)">
                            <span>❤️</span> <span>Gracias</span>
                            <span class="fr-rxn-count count-TRFO002"><?php echo (int)($reacciones['TRFO002'] ?? 0); ?></span>
                        </button>

                        <!-- TRFO003: Top Recomendación 🔥 -->
                        <button type="button" class="fr-rxn rxn-003 reaccion-btn <?php echo ($mi_reaccion === 'TRFO003') ? 'is-on' : ''; ?>" onclick="reaccionarAjax('<?php echo $foro['foro_id']; ?>', 'TRFO003', this)">
                            <span>🔥</span> <span>Top</span>
                            <span class="fr-rxn-count count-TRFO003"><?php echo (int)($reacciones['TRFO003'] ?? 0); ?></span>
                        </button>
                    </div>
                </div>
            </article>

            <!-- SECCIÓN DE COMENTARIOS Y RESPUESTAS -->
            <section class="fr-card">
                <div class="fr-comments-head">
                    <h3><i class="fas fa-comments"></i> Comentarios (<span id="total-comentarios-ui"><?php echo count($comentarios); ?></span>)</h3>
                    <a href="#caja-responder" class="pd-btn pd-btn-ghost" style="padding:8px 16px; font-size:13px;"><i class="fas fa-plus"></i> Escribir comentario</a>
                </div>
                <div class="fr-comments-body">
                    <?php if (empty($arbol)): ?>
                        <div class="fr-empty">
                            <i class="far fa-comment-dots"></i>
                            <h4>Aún no hay comentarios</h4>
                            <p>¡Sé el primero en aportar un consejo o responder la duda!</p>
                        </div>
                    <?php else: ?>
                        <div class="lista-comentarios">
                            <?php foreach ($arbol as $comentario_padre): ?>
                                <?php renderizarComentario($comentario_padre, $hijos_por_padre, 0, $usuario_id); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- CAJA DE RESPUESTA / NUEVO COMENTARIO -->
            <section class="fr-card fr-card-pad fr-reply-box" id="caja-responder">
                <h3><i class="fas fa-pen"></i> Deja tu aporte o comentario</h3>

                <div id="alerta-respuesta-padre" class="fr-reply-alert">
                    <div><i class="fas fa-reply"></i> Respondiendo al comentario de: <strong id="nombre-autor-padre"></strong></div>
                    <button type="button" onclick="cancelarRespuesta()"><i class="fas fa-times"></i> Cancelar</button>
                </div>

                <form method="POST" action="/foros/comentar">
                    <input type="hidden" name="foro_id" value="<?php echo $foro['foro_id']; ?>">
                    <input type="hidden" name="comentario_padre_id" id="input_comentario_padre_id" value="">

                    <textarea name="mensaje" id="textarea_mensaje" class="fr-textarea" rows="4" required placeholder="Escribe aquí tu consejo, respuesta o recomendación para la comunidad..."></textarea>

                    <div class="fr-form-actions">
                        <button type="submit" class="pd-btn pd-btn-primary"><i class="fas fa-paper-plane"></i> Publicar comentario</button>
                    </div>
                </form>
            </section>
        </div>

        <!-- COLUMNA LATERAL: INFORMACIÓN Y TIPS DE CONVIVENCIA -->
        <aside class="fr-side">
            <div class="fr-rules">
                <h3><i class="fas fa-lightbulb"></i> Normas de la comunidad</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> <span><strong>Se respetuoso:</strong> Evita lenguaje ofensivo o ataques personales hacia compañeros o propietarios.</span></li>
                    <li><i class="fas fa-check-circle"></i> <span><strong>No compartas datos privados:</strong> Por tu seguridad, no publiques contraseñas, números de cuenta o direcciones exactas públicamente.</span></li>
                    <li><i class="fas fa-check-circle"></i> <span><strong>Verifica antes de rentar:</strong> Las recomendaciones de zonas son referenciales. Realiza siempre un contrato formal.</span></li>
                </ul>
            </div>

            <div class="fr-card fr-card-pad fr-axes">
                <h3><i class="fas fa-tags"></i> Ejes universitarios</h3>
                <div class="fr-axes-list">
                    <?php foreach ($categorias as $cat):
                        $c_cod = $cat['codigo'];
                        $c_info = $iconos_cat[$c_cod] ?? ['icon' => 'fa-tag', 'label' => $cat['nombre'], 'color' => '#6B6F7A'];
                    ?>
                        <a href="/foros?categoria=<?php echo urlencode($c_cod); ?>" class="fr-axis">
                            <span class="fr-axis-l"><i class="fas <?php echo htmlspecialchars($c_info['icon']); ?>" style="color:<?php echo htmlspecialchars($c_info['color']); ?>"></i> <?php echo htmlspecialchars($c_info['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="fr-axis-r"><i class="fas fa-chevron-right"></i></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</section>

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

/**
 * Lógica AJAX para enviar reacciones dinámicamente con Fetch API.
 * La ruta real del router es /foros/reaccionar (mapea a ForoController::reaccionarAjax).
 */
function reaccionarAjax(foroId, tipoReaccion, botonClickeado) {
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

            // Reiniciar estilos: quitar is-on de todos los botones de reacción
            document.querySelectorAll('.reaccion-btn').forEach(btn => btn.classList.remove('is-on'));

            // Resaltar el botón activo si no fue removido
            if (data.mi_reaccion) {
                botonClickeado.classList.add('is-on');
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo reaccionar',
                text: data.error || 'Ocurrió un problema al procesar tu reacción',
                confirmButtonColor: '#2A44FF',
                customClass: {
                    popup: 'rounded-4 shadow-lg border-0',
                    confirmButton: 'pd-btn pd-btn-primary'
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
    alerta.classList.add('is-on');

    const textarea = document.getElementById('textarea_mensaje');
    textarea.placeholder = `Escribe tu respuesta a ${autorNombre}...`;
    textarea.focus();

    // Scroll suave hacia la caja de respuesta
    document.getElementById('caja-responder').scrollIntoView({ behavior: 'smooth' });
}

function cancelarRespuesta() {
    document.getElementById('input_comentario_padre_id').value = '';
    const alerta = document.getElementById('alerta-respuesta-padre');
    alerta.classList.remove('is-on');

    const textarea = document.getElementById('textarea_mensaje');
    textarea.placeholder = 'Escribe aquí tu consejo, respuesta o recomendación para la comunidad...';
}
</script>
