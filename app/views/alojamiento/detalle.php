<?php
/** @var array $a */
/** @var array $resenas */
/** @var int $totalResenas */
/** @var array $distribucion */
/** @var int $totalAlojamientosProp */
/** @var array $monedaLabels */
/** @var array $tipoLabels */
/** @var array $generoLabels */
/** @var bool $logueado */
/** @var bool $hayMasResenas */

$mon = $monedaLabels[$a['moneda_codigo']] ?? '';
$precio = $mon . ' ' . number_format((float)$a['precio_mensual'], 0, ',', '.');
$garantia = $mon . ' ' . number_format((float)($a['garantia'] ?? 0), 0, ',', '.');
$fotos = $a['fotos'] ?? [];
$fotoPrincipal = !empty($fotos) ? $fotos[0]['url'] : null;
$servicios = $a['servicios'] ?? [];
$universidades = $a['universidades'] ?? [];
$politicas = $a['politicas'] ?? [];
$dirMasked = preg_replace('/\d+/', '•••', $a['direccion'] ?? '');
$propNombre = htmlspecialchars(($a['propietario_nombres'] ?? '') . ' ' . strtoupper(pd_initial($a['propietario_apellido'] ?? '')) . '.');
$miembroDesde = !empty($a['propietario_miembro_desde']) ? date('Y', strtotime($a['propietario_miembro_desde'])) : '';
$tieneCoords = !empty($a['latitud']) && !empty($a['longitud']);
$alojId = htmlspecialchars($a['alojamiento_id']);
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.pd-ficha { padding-top: 36px; }
.pd-breadcrumb { font-size: 13px; color: var(--pd-muted); margin-bottom: 18px; }
.pd-breadcrumb a:hover { color: var(--pd-ink); }
.pd-ficha-grid { display: grid; grid-template-columns: 1fr 340px; gap: 32px; align-items: start; }
.pd-ficha-main { min-width: 0; }

/* Galería */
.pd-gal-principal { aspect-ratio: 16/10; border-radius: var(--pd-r-lg); overflow: hidden; background: linear-gradient(135deg, var(--pd-primary), #7B8CFF); position: relative; cursor: zoom-in; }
.pd-gal-principal img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pd-gal-principal .pd-ph { position: absolute; inset: 0; display: grid; place-items: center; color: #fff; font-size: 56px; opacity: .8; }
.pd-gal-thumbs { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 12px; }
.pd-gal-thumb { aspect-ratio: 1; border-radius: 10px; overflow: hidden; cursor: pointer; border: 2px solid transparent; transition: border-color .2s; background: var(--pd-line); }
.pd-gal-thumb img { width: 100%; height: 100%; object-fit: cover; }
.pd-gal-thumb.is-active { border-color: var(--pd-primary); }

/* Lightbox */
.pd-lightbox { position: fixed; inset: 0; background: rgba(15,17,21,.92); z-index: 1000; display: none; align-items: center; justify-content: center; }
.pd-lightbox.is-open { display: flex; }
.pd-lightbox img { max-width: 90vw; max-height: 86vh; border-radius: 12px; }
.pd-lightbox .pd-lb-close, .pd-lightbox .pd-lb-nav { position: absolute; background: rgba(255,255,255,.12); color: #fff; border: none; width: 48px; height: 48px; border-radius: 50%; font-size: 22px; cursor: pointer; display: grid; place-items: center; transition: background .2s; }
.pd-lightbox .pd-lb-close:hover, .pd-lightbox .pd-lb-nav:hover { background: rgba(255,255,255,.25); }
.pd-lightbox .pd-lb-close { top: 24px; right: 24px; }
.pd-lightbox .pd-lb-nav.prev { left: 24px; top: 50%; transform: translateY(-50%); }
.pd-lightbox .pd-lb-nav.next { right: 24px; top: 50%; transform: translateY(-50%); }

/* Info */
.pd-ficha-title { font-family: var(--pd-display); font-size: clamp(24px, 3.5vw, 34px); font-weight: 700; margin: 18px 0 8px; }
.pd-ficha-sub { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; color: var(--pd-muted); font-size: 14px; margin-bottom: 22px; }
.pd-ficha-sub .pd-price-big { color: var(--pd-ink); font-family: var(--pd-display); font-weight: 700; font-size: 20px; }
.pd-ficha-section { margin-top: 30px; }
.pd-ficha-section h3 { font-family: var(--pd-display); font-size: 18px; margin-bottom: 12px; }
.pd-ficha-desc { color: var(--pd-ink); line-height: 1.6; font-size: 15px; }
.pd-ficha-attrs { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.pd-attr { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: 10px; padding: 12px 14px; font-size: 14px; }
.pd-attr b { display: block; font-size: 12px; color: var(--pd-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px; }
.pd-chips-line { display: flex; flex-wrap: wrap; gap: 8px; }
.pd-chip-info { display: inline-flex; align-items: center; gap: 6px; background: var(--pd-sky); color: var(--pd-primary); padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; }
.pd-chip-info.off { background: var(--pd-line); color: var(--pd-muted); }
.pd-serv-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.pd-serv { display: flex; align-items: center; gap: 9px; font-size: 14px; }
.pd-serv i { color: var(--pd-primary); }
.pd-uni-list { list-style: none; padding: 0; margin: 0; }
.pd-uni-list li { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--pd-line); font-size: 14px; }
.pd-uni-list li:last-child { border-bottom: none; }
.pd-uni-list .km { color: var(--pd-muted); font-family: var(--pd-mono); }
#pdFichaMap { height: 320px; border-radius: var(--pd-r-md); border: 1px solid var(--pd-line); }

/* Propietario */
.pd-prop { display: flex; align-items: center; gap: 14px; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 18px; }
.pd-prop .pd-prop-av { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--pd-primary), #7B8CFF); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 18px; }
.pd-prop b { font-size: 15px; }
.pd-prop .pd-prop-meta { font-size: 13px; color: var(--pd-muted); margin-top: 2px; }

/* Reseñas */
.pd-resenas-summary { display: flex; gap: 28px; align-items: center; margin-bottom: 22px; flex-wrap: wrap; }
.pd-resenas-big { font-family: var(--pd-display); font-size: 44px; font-weight: 700; }
.pd-resenas-dist { flex: 1; min-width: 220px; }
.pd-res-bar { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--pd-muted); margin-bottom: 4px; }
.pd-res-bar .pd-res-track { flex: 1; height: 6px; background: var(--pd-line); border-radius: 999px; overflow: hidden; }
.pd-res-bar .pd-res-fill { height: 100%; background: var(--pd-accent); border-radius: 999px; }
.pd-resena { background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 18px; margin-bottom: 14px; }
.pd-resena-head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.pd-resena-head .pd-av { width: 40px; height: 40px; border-radius: 50%; background: var(--pd-ink); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 14px; }
.pd-resena-who b { font-size: 14px; display: block; }
.pd-resena-fecha { margin-left: auto; font-size: 12px; color: var(--pd-muted); }
.pd-resena p { font-size: 14.5px; line-height: 1.55; }
.pd-resena-resp { margin-top: 12px; padding: 10px 12px; background: var(--pd-paper); border-radius: 10px; font-size: 13.5px; }
.pd-resena-resp span { font-weight: 600; color: var(--pd-primary); }

/* Sidebar reserva */
.pd-sidebar { position: sticky; top: 84px; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 22px; box-shadow: var(--pd-sh-2); }
.pd-sidebar .pd-sb-price { font-family: var(--pd-display); font-size: 26px; font-weight: 700; }
.pd-sidebar .pd-sb-price small { font-size: 14px; color: var(--pd-muted); font-weight: 500; }
.pd-sb-field { margin-top: 16px; }
.pd-sb-field label { display: block; font-size: 12px; font-weight: 600; color: var(--pd-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
.pd-sb-field input, .pd-sb-field select { width: 100%; padding: 10px 12px; border: 1.5px solid var(--pd-line); border-radius: 10px; font-size: 14px; background: var(--pd-paper); }
.pd-sb-resumen { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--pd-line); font-size: 14px; }
.pd-sb-resumen .pd-sb-line { display: flex; justify-content: space-between; margin-bottom: 7px; color: var(--pd-muted); }
.pd-sb-resumen .pd-sb-total { display: flex; justify-content: space-between; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--pd-line); font-weight: 700; font-size: 16px; color: var(--pd-ink); }
.pd-sb-btns { margin-top: 18px; display: flex; flex-direction: column; gap: 9px; }
.pd-sb-btns .pd-btn { width: 100%; }
.pd-sb-stub { width: 100%; padding: 11px; border: 1.5px dashed var(--pd-line); border-radius: 999px; background: transparent; color: var(--pd-muted); font-weight: 600; font-size: 14px; cursor: not-allowed; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }

@media (max-width: 900px) { .pd-ficha-grid { grid-template-columns: 1fr; } .pd-sidebar { position: static; } .pd-gal-thumbs { grid-template-columns: repeat(4, 1fr); } .pd-ficha-attrs, .pd-serv-list { grid-template-columns: 1fr; } }
</style>

<section class="pd-section pd-ficha">
    <div class="pd-breadcrumb">
        <a href="/">Inicio</a> › <a href="/buscar">Buscar</a> › <span><?php echo htmlspecialchars($a['titulo']); ?></span>
    </div>

    <div class="pd-ficha-grid">
        <!-- MAIN -->
        <div class="pd-ficha-main">
            <!-- Galería -->
            <div class="pd-gal-principal" id="pdGalPrincipal">
                <?php if ($fotoPrincipal): ?>
                    <img src="<?php echo htmlspecialchars($fotoPrincipal); ?>" alt="<?php echo htmlspecialchars($a['titulo']); ?>">
                <?php else: ?>
                    <div class="pd-ph"><i class="fas fa-bed"></i></div>
                <?php endif; ?>
            </div>
            <?php if (count($fotos) > 1): ?>
                <div class="pd-gal-thumbs">
                    <?php foreach ($fotos as $i => $f): ?>
                        <div class="pd-gal-thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-idx="<?php echo $i; ?>">
                            <img src="<?php echo htmlspecialchars($f['url']); ?>" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h1 class="pd-ficha-title"><?php echo htmlspecialchars($a['titulo']); ?></h1>
            <div class="pd-ficha-sub">
                <span class="pd-price-big"><?php echo $precio; ?>/mes</span>
                <span><i class="fas fa-location-dot" style="color:var(--pd-accent)"></i> <?php echo htmlspecialchars($a['distrito'] ?? 'Distrito no disponible'); ?></span>
                <span><?php echo htmlspecialchars($tipoLabels[$a['tipo_codigo']] ?? 'Alojamiento'); ?></span>
                <?php if ($a['calificacion'] !== null): ?>
                    <span class="pd-stars"><i class="fas fa-star"></i> <?php echo number_format((float)$a['calificacion'], 1); ?> · <?php echo $totalResenas; ?> reseñas</span>
                <?php endif; ?>
            </div>

            <!-- Atributos -->
            <div class="pd-ficha-section">
                <h3>Características</h3>
                <div class="pd-ficha-attrs">
                    <div class="pd-attr"><b>Precio</b><?php echo $precio; ?> / mes</div>
                    <div class="pd-attr"><b>Garantía</b><?php echo $garantia; ?></div>
                    <div class="pd-attr"><b>Tamaño</b><?php echo htmlspecialchars($a['tamano_m2'] ?? '—'); ?> m²</div>
                    <div class="pd-attr"><b>Habitaciones</b><?php echo htmlspecialchars($a['numero_habitaciones'] ?? '—'); ?></div>
                    <div class="pd-attr"><b>Baños</b><?php echo htmlspecialchars($a['numero_banos'] ?? '—'); ?></div>
                    <div class="pd-attr"><b>Género</b><?php echo htmlspecialchars($generoLabels[$a['genero_exclusivo_codigo']] ?? 'Mixto'); ?></div>
                    <div class="pd-attr"><b>Disponible desde</b><?php echo !empty($a['fecha_disponible']) ? date('d/m/Y', strtotime($a['fecha_disponible'])) : '—'; ?></div>
                    <div class="pd-attr"><b>Duración mínima</b><?php echo htmlspecialchars($a['duracion_minima_meses'] ?? '—'); ?> meses</div>
                </div>
                <div class="pd-chips-line" style="margin-top:14px">
                    <span class="pd-chip-info<?php echo !empty($a['amoblado']) ? '' : ' off'; ?>"><i class="fas fa-couch"></i> Amoblado</span>
                    <span class="pd-chip-info<?php echo !empty($a['mascotas_permitidas']) ? '' : ' off'; ?>"><i class="fas fa-paw"></i> Mascotas</span>
                    <span class="pd-chip-info<?php echo !empty($a['fumadores_permitidas']) ? '' : ' off'; ?>"><i class="fas fa-smoking"></i> Fumadores</span>
                    <span class="pd-chip-info"><i class="fas fa-map-pin"></i> <?php echo $dirMasked ? htmlspecialchars($dirMasked) : 'Dirección tras reservar'; ?></span>
                </div>
            </div>

            <!-- Descripción -->
            <?php if (!empty($a['descripcion'])): ?>
                <div class="pd-ficha-section">
                    <h3>Descripción</h3>
                    <p class="pd-ficha-desc"><?php echo nl2br(htmlspecialchars($a['descripcion'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Servicios -->
            <?php if (!empty($servicios)): ?>
                <div class="pd-ficha-section">
                    <h3>Servicios e instalaciones</h3>
                    <div class="pd-serv-list">
                        <?php foreach ($servicios as $s): ?>
                            <div class="pd-serv"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($s['nombre']); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Mapa -->
            <?php if ($tieneCoords): ?>
                <div class="pd-ficha-section">
                    <h3>Ubicación (aproximada)</h3>
                    <div id="pdFichaMap"></div>
                    <p style="font-size:12.5px;color:var(--pd-muted);margin-top:8px"><i class="fas fa-info-circle"></i> Mostramos un área aproximada de 100m. La dirección exacta se comparte tras reservar.</p>
                    <?php if (!empty($universidades)): ?>
                        <ul class="pd-uni-list" style="margin-top:14px">
                            <?php foreach ($universidades as $u): ?>
                                <li><span><i class="fas fa-university" style="color:var(--pd-primary);margin-right:6px"></i><?php echo htmlspecialchars($u['nombre']); ?></span><span class="km"><?php echo $u['distancia_km'] !== null ? number_format((float)$u['distancia_km'], 1) . ' km' : '—'; ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Propietario -->
            <div class="pd-ficha-section">
                <h3>Anfitrión</h3>
                <div class="pd-prop">
                    <span class="pd-prop-av"><?php echo htmlspecialchars(strtoupper(pd_initial($a['propietario_nombres'] ?? 'A'))); ?></span>
                    <div style="flex:1">
                        <b><?php echo $propNombre; ?></b>
                        <?php if (!empty($a['propietario_verificado'])): ?> <span class="pd-verified"><i class="fas fa-check"></i> Verificado</span><?php endif; ?>
                        <div class="pd-prop-meta">
                            <?php if ($miembroDesde): ?>Miembro desde <?php echo $miembroDesde; ?> · <?php endif; ?>
                            <?php echo $totalAlojamientosProp; ?> alojamiento(s) publicado(s)
                            <?php if ($a['propietario_calificacion'] !== null): ?> · <i class="fas fa-star" style="color:var(--pd-accent)"></i> <?php echo number_format((float)$a['propietario_calificacion'], 1); ?><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reseñas -->
            <div class="pd-ficha-section">
                <h3>Reseñas (<?php echo $totalResenas; ?>)</h3>
                <?php if ($totalResenas > 0): ?>
                    <div class="pd-resenas-summary">
                        <div style="text-align:center">
                            <div class="pd-resenas-big"><?php echo number_format((float)$a['calificacion'], 1); ?></div>
                            <span class="pd-stars"><i class="fas fa-star"></i></span>
                        </div>
                        <div class="pd-resenas-dist">
                            <?php for ($star = 5; $star >= 1; $star--): $pct = $totalResenas > 0 ? ($distribucion[$star] / $totalResenas) * 100 : 0; ?>
                                <div class="pd-res-bar"><span><?php echo $star; ?>★</span><div class="pd-res-track"><div class="pd-res-fill" style="width:<?php echo $pct; ?>%"></div></div><span><?php echo $distribucion[$star]; ?></span></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div id="pdResenasLista">
                        <?php require __DIR__ . '/_resenas.php'; ?>
                    </div>
                    <?php if ($hayMasResenas): ?>
                        <div style="text-align:center;margin-top:16px"><button class="pd-btn pd-btn-ghost" id="pdVerMasResenas" style="padding:10px 20px;font-size:14px">Ver más reseñas</button></div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color:var(--pd-muted)">Aún no hay reseñas para este alojamiento.</p>
                <?php endif; ?>
            </div>

            <!-- Políticas -->
            <?php if (!empty($politicas)): ?>
                <div class="pd-ficha-section">
                    <h3>Políticas de la casa</h3>
                    <ul class="pd-uni-list">
                        <?php foreach ($politicas as $p): ?>
                            <li><span><i class="fas fa-circle-info" style="color:var(--pd-primary);margin-right:6px"></i><?php echo htmlspecialchars($p['nombre']); ?></span><span style="color:var(--pd-muted);font-size:13px;max-width:60%;text-align:right"><?php echo htmlspecialchars($p['descripcion'] ?? ''); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR RESERVA -->
        <aside class="pd-sidebar">
            <div class="pd-sb-price"><?php echo $precio; ?> <small>/ mes</small></div>
            <div class="pd-sb-field">
                <label>Fecha de ingreso</label>
                <input type="date" id="pdSbFecha" min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
            </div>
            <div class="pd-sb-field">
                <label>Duración (meses)</label>
                <select id="pdSbMeses">
                    <?php $minDur = (int)($a['duracion_minima_meses'] ?? 1); if ($minDur < 1) $minDur = 1; ?>
                    <?php for ($m = $minDur; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m === $minDur ? 'selected' : ''; ?>><?php echo $m; ?> mes(es)</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="pd-sb-resumen" id="pdSbResumen">
                <div class="pd-sb-line"><span>Alquiler (<?php echo $minDur; ?> mes)</span><span data-line="alq"><?php echo $precio; ?></span></div>
                <div class="pd-sb-line"><span>Garantía</span><span><?php echo $garantia; ?></span></div>
                <div class="pd-sb-line"><span>Cargo plataforma (5%)</span><span data-line="cargo">—</span></div>
                <div class="pd-sb-total"><span>Total estimado</span><span data-line="total">—</span></div>
            </div>
            <div class="pd-sb-btns">
                <?php if (!$logueado): ?>
                    <a href="/login" class="pd-btn pd-btn-primary" style="width:100%;padding:15px 22px;font-size:15.5px;font-weight:700;text-align:center;display:flex;align-items:center;justify-content:center;gap:10px;border-radius:999px;box-shadow:0 6px 18px rgba(99,102,241,0.3);letter-spacing:0.02em"><i class="fas fa-lock"></i> Inicia sesión para reservar</a>
                <?php else: ?>
                    <button class="pd-sb-stub" disabled><i class="fas fa-calendar-check"></i> Solicitar reserva · Próximamente</button>
                    <a href="/mensajes/abrir?alojamiento=<?php echo $alojId; ?>" class="pd-btn pd-btn-ghost" style="width:100%;padding:15px 22px;font-size:15.5px;font-weight:700;text-align:center;display:flex;align-items:center;justify-content:center;gap:10px;border-radius:999px;letter-spacing:0.02em"><i class="fas fa-comment"></i> Contactar anfitrión</a>
                    <button class="pd-sb-stub" disabled><i class="fas fa-heart"></i> Favorito · Próximamente</button>
                    <button class="pd-sb-stub" disabled><i class="fas fa-share"></i> Compartir · Próximamente</button>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<!-- Lightbox -->
<div class="pd-lightbox" id="pdLightbox">
    <button class="pd-lb-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>
    <button class="pd-lb-nav prev" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
    <img src="" alt="">
    <button class="pd-lb-nav next" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>
</div>

<?php if ($tieneCoords): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var lat = <?php echo (float)$a['latitud']; ?>, lng = <?php echo (float)$a['longitud']; ?>;
    var map = L.map('pdFichaMap').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
    L.circle([lat, lng], { radius: 100, color: '#2A44FF', fillColor: '#2A44FF', fillOpacity: 0.15, weight: 2 }).addTo(map);
})();
</script>
<?php endif; ?>

<script>
(function () {
    // --- Galería + Lightbox ---
    var fotos = <?php echo json_encode(array_map(fn($f) => $f['url'], $fotos)); ?>;
    var principal = document.getElementById('pdGalPrincipal');
    var thumbs = document.querySelectorAll('.pd-gal-thumb');
    var lb = document.getElementById('pdLightbox');
    var lbImg = lb.querySelector('img');
    var lbIdx = 0;

    function setPrincipal(i) {
        if (!fotos.length) return;
        principal.querySelector('img') && (principal.querySelector('img').src = fotos[i]);
        thumbs.forEach(function (t, j) { t.classList.toggle('is-active', j === i); });
    }
    thumbs.forEach(function (t, i) {
        t.addEventListener('click', function () { setPrincipal(i); });
    });
    function openLb(i) { lbIdx = i; lbImg.src = fotos[i]; lb.classList.add('is-open'); }
    function closeLb() { lb.classList.remove('is-open'); }
    if (principal) principal.addEventListener('click', function () { if (fotos.length) openLb(0); });
    lb.querySelector('.pd-lb-close').addEventListener('click', closeLb);
    lb.querySelector('.pd-lb-nav.prev').addEventListener('click', function () { lbIdx = (lbIdx - 1 + fotos.length) % fotos.length; lbImg.src = fotos[lbIdx]; });
    lb.querySelector('.pd-lb-nav.next').addEventListener('click', function () { lbIdx = (lbIdx + 1) % fotos.length; lbImg.src = fotos[lbIdx]; });
    lb.addEventListener('click', function (e) { if (e.target === lb) closeLb(); });
    document.addEventListener('keydown', function (e) {
        if (!lb.classList.contains('is-open')) return;
        if (e.key === 'Escape') closeLb();
        if (e.key === 'ArrowLeft') lb.querySelector('.pd-lb-nav.prev').click();
        if (e.key === 'ArrowRight') lb.querySelector('.pd-lb-nav.next').click();
    });

    // --- Resumen de costos ---
    var precioNum = <?php echo (float)$a['precio_mensual']; ?>;
    var garantiaNum = <?php echo (float)($a['garantia'] ?? 0); ?>;
    var mon = <?php echo json_encode($mon); ?>;
    var mesesSel = document.getElementById('pdSbMeses');
    var resumen = document.getElementById('pdSbResumen');
    function fmt(n) { return mon + ' ' + Number(n).toLocaleString('es-PE', { maximumFractionDigits: 0 }); }
    function updResumen() {
        var meses = parseInt(mesesSel.value, 10) || 1;
        var alq = precioNum * meses;
        var cargo = alq * 0.05;
        var total = alq + garantiaNum + cargo;
        resumen.querySelector('[data-line=alq]').textContent = fmt(alq);
        resumen.querySelector('[data-line=cargo]').textContent = fmt(cargo);
        resumen.querySelector('[data-line=total]').textContent = fmt(total);
        resumen.querySelector('.pd-sb-line span:first-child').textContent = 'Alquiler (' + meses + ' mes(es))';
    }
    mesesSel.addEventListener('change', updResumen); updResumen();

    // --- Ver más reseñas (AJAX) ---
    var verMasBtn = document.getElementById('pdVerMasResenas');
    if (verMasBtn) {
        var pagina = 1;
        var lista = document.getElementById('pdResenasLista');
        var alojId = <?php echo json_encode($a['alojamiento_id']); ?>;
        verMasBtn.addEventListener('click', function () {
            pagina++;
            verMasBtn.disabled = true; verMasBtn.textContent = 'Cargando…';
            fetch('/alojamiento/' + alojId + '/resenas?pagina=' + pagina)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var tmp = document.createElement('div'); tmp.innerHTML = data.html;
                    while (tmp.firstChild) lista.appendChild(tmp.firstChild);
                    if (!data.hayMas) { verMasBtn.style.display = 'none'; }
                    else { verMasBtn.disabled = false; verMasBtn.textContent = 'Ver más reseñas'; }
                })
                .catch(function () { verMasBtn.disabled = false; verMasBtn.textContent = 'Ver más reseñas'; });
        });
    }
})();
</script>
