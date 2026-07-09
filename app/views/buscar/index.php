<?php
/** @var array $alojamientos */
/** @var int $total */
/** @var int $pagina */
/** @var array $filtros */
/** @var string $vista */
/** @var array $tipos */
/** @var array $coords */
/** @var int $porPagina */
/** @var bool $hayMas */

$monedaLabels = ['TPM001' => 'S/', 'TPM002' => 'US$'];
$tipoLabels = ['TPA001' => 'Cuarto', 'TPA002' => 'Mini-depto', 'TPA003' => 'Depto completo'];

// Query string base (sin pagina) para "Ver más" y toggles
$qsParams = [];
if ($filtros['q'] !== '')                $qsParams['q'] = $filtros['q'];
foreach ($filtros['tipos'] as $t)        $qsParams['tipo'][] = $t;
if ($filtros['presupuesto'] !== '')      $qsParams['presupuesto'] = $filtros['presupuesto'];
if ($filtros['amoblado'])                $qsParams['amoblado'] = 1;
if ($filtros['mascotas'])                $qsParams['mascotas'] = 1;
if ($filtros['solo_verificados'])        $qsParams['solo_verificados'] = 1;
if ($filtros['fecha'] !== '')            $qsParams['fecha'] = $filtros['fecha'];
if ($filtros['orden'] !== 'recientes')   $qsParams['orden'] = $filtros['orden'];
$qsBase = http_build_query($qsParams);

$ordenLabels = [
    'recientes'   => 'Más recientes',
    'precio_asc'  => 'Precio: menor a mayor',
    'precio_desc' => 'Precio: mayor a menor',
    'calificados' => 'Mejor calificados',
    'cercanos'    => 'Más cercanos al campus'
];
$ordenTexto = $ordenLabels[$filtros['orden']] ?? 'Más recientes';

$tituloBusca = $filtros['q'] !== '' ? 'Alojamientos para «' . htmlspecialchars($filtros['q']) . '»' : 'Todos los alojamientos';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.pd-buscar { padding-top: 40px; }
.pd-buscar-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 28px; flex-wrap: wrap; }
.pd-buscar-head h1 { font-family: var(--pd-display); font-size: clamp(26px, 4vw, 36px); font-weight: 700; margin-top: 8px; }
.pd-buscar-toggle { display: inline-flex; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: 999px; padding: 4px; gap: 4px; }
.pd-buscar-toggle button { border: none; background: transparent; padding: 8px 16px; border-radius: 999px; font-weight: 600; font-size: 14px; color: var(--pd-muted); cursor: pointer; transition: all .2s; }
.pd-buscar-toggle button.is-active { background: var(--pd-ink); color: #fff; }
.pd-buscar-layout { display: grid; grid-template-columns: 280px 1fr; gap: 28px; align-items: start; }
.pd-buscar-filtros { position: sticky; top: 84px; background: var(--pd-surface); border: 1px solid var(--pd-line); border-radius: var(--pd-r-md); padding: 22px 20px; box-shadow: var(--pd-sh-1); }
.pd-buscar-filtros h3 { font-family: var(--pd-display); font-size: 16px; margin-bottom: 16px; }
.pd-buscar-filtros .pd-fg { margin-bottom: 16px; }
.pd-buscar-filtros label.pd-fl { display: block; font-size: 12px; font-weight: 600; color: var(--pd-muted); letter-spacing: .04em; text-transform: uppercase; margin-bottom: 6px; }
.pd-buscar-filtros input[type=text], .pd-buscar-filtros input[type=number], .pd-buscar-filtros input[type=date], .pd-buscar-filtros select { width: 100%; padding: 10px 12px; border: 1.5px solid var(--pd-line); border-radius: 10px; background: var(--pd-paper); font-family: var(--pd-body); font-size: 14px; color: var(--pd-ink); }
.pd-buscar-filtros input:focus, .pd-buscar-filtros select:focus { outline: none; border-color: var(--pd-primary); background: #fff; }
.pd-buscar-filtros .pd-chips-tipo { display: flex; flex-wrap: wrap; gap: 7px; }
.pd-buscar-filtros .pd-fbtns { display: flex; gap: 8px; margin-top: 18px; }
.pd-buscar-filtros .pd-fbtns .pd-btn { flex: 1; padding: 10px 14px; font-size: 13px; }
.pd-buscar-resultados { min-height: 400px; }
.pd-buscar-grilla { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
.pd-buscar-mapa { border-radius: var(--pd-r-md); overflow: hidden; border: 1px solid var(--pd-line); box-shadow: var(--pd-sh-1); }
#pdLeafletMap { height: 560px; width: 100%; }
.pd-buscar-mas { text-align: center; margin-top: 32px; }
.pd-buscar-mas .pd-btn { min-width: 220px; }
.pd-buscar-vacio { text-align: center; padding: 80px 20px; color: var(--pd-muted); }
.pd-buscar-vacio i { font-size: 52px; opacity: .25; }
.pd-buscar-vacio h3 { font-family: var(--pd-display); font-size: 22px; color: var(--pd-ink); margin: 16px 0 8px; }
@media (max-width: 900px) {
  .pd-buscar-layout { grid-template-columns: 1fr; }
  .pd-buscar-filtros { position: static; }
  .pd-buscar-grilla { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 560px) { .pd-buscar-grilla { grid-template-columns: 1fr; } }
</style>

<section class="pd-section pd-buscar">
    <div class="pd-buscar-head">
        <div>
            <span class="pd-eyebrow">Resultados</span>
            <h1><?php echo $tituloBusca; ?></h1>
            <p class="pd-sub"><?php echo $total; ?> resultado(s) · ordenados por <?php echo htmlspecialchars($ordenTexto); ?></p>
        </div>
        <div class="pd-buscar-toggle" id="pdBuscarToggle">
            <button type="button" data-vista="grilla" class="<?php echo $vista === 'grilla' ? 'is-active' : ''; ?>"><i class="fas fa-th"></i> Grilla</button>
            <button type="button" data-vista="mapa" class="<?php echo $vista === 'mapa' ? 'is-active' : ''; ?>"><i class="fas fa-map"></i> Mapa</button>
        </div>
    </div>

    <div class="pd-buscar-layout">
        <!-- FILTROS -->
        <aside class="pd-buscar-filtros">
            <h3>Filtros</h3>
            <form method="GET" action="/buscar">
                <div class="pd-fg">
                    <label class="pd-fl">Universidad o ciudad</label>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filtros['q']); ?>" placeholder="Ej. Pacífico, Lima...">
                </div>
                <div class="pd-fg">
                    <label class="pd-fl">Tipo</label>
                    <div class="pd-chips-tipo">
                        <?php foreach ($tipos as $t): $on = in_array($t['codigo'], $filtros['tipos'], true); ?>
                            <label class="pd-chip<?php echo $on ? ' is-on' : ''; ?>">
                                <input type="checkbox" name="tipo[]" value="<?php echo htmlspecialchars($t['codigo']); ?>" <?php echo $on ? 'checked' : ''; ?> onchange="this.closest('.pd-chip').classList.toggle('is-on', this.checked)">
                                <?php echo htmlspecialchars($tipoLabels[$t['codigo']] ?? $t['nombre']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="pd-fg">
                    <label class="pd-fl">Presupuesto máx. (S/)</label>
                    <input type="number" name="presupuesto" value="<?php echo htmlspecialchars($filtros['presupuesto']); ?>" placeholder="800" min="0">
                </div>
                <div class="pd-fg">
                    <label class="pd-fl">Disponible desde</label>
                    <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtros['fecha']); ?>">
                </div>
                <div class="pd-fg">
                    <label class="pd-fl">Extras</label>
                    <div class="pd-chips-tipo">
                        <label class="pd-chip<?php echo $filtros['amoblado'] ? ' is-on' : ''; ?>"><input type="checkbox" name="amoblado" value="1" <?php echo $filtros['amoblado'] ? 'checked' : ''; ?> onchange="this.closest('.pd-chip').classList.toggle('is-on', this.checked)"> Amoblado</label>
                        <label class="pd-chip<?php echo $filtros['mascotas'] ? ' is-on' : ''; ?>"><input type="checkbox" name="mascotas" value="1" <?php echo $filtros['mascotas'] ? 'checked' : ''; ?> onchange="this.closest('.pd-chip').classList.toggle('is-on', this.checked)"> Mascotas</label>
                        <label class="pd-chip<?php echo $filtros['solo_verificados'] ? ' is-on' : ''; ?>"><input type="checkbox" name="solo_verificados" value="1" <?php echo $filtros['solo_verificados'] ? 'checked' : ''; ?> onchange="this.closest('.pd-chip').classList.toggle('is-on', this.checked)"> Verificados</label>
                    </div>
                </div>
                <div class="pd-fg">
                    <label class="pd-fl">Ordenar por</label>
                    <select name="orden" onchange="this.form.submit()">
                        <?php foreach ($ordenLabels as $cod => $lbl): ?>
                            <option value="<?php echo $cod; ?>" <?php echo $filtros['orden'] === $cod ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pd-fbtns">
                    <button type="submit" class="pd-btn pd-btn-primary">Aplicar</button>
                    <a href="/buscar" class="pd-btn pd-btn-ghost">Limpiar</a>
                </div>
            </form>
        </aside>

        <!-- RESULTADOS -->
        <div class="pd-buscar-resultados">
            <?php if (empty($alojamientos)): ?>
                <div class="pd-buscar-vacio">
                    <i class="fas fa-magnifying-glass"></i>
                    <h3>No encontramos alojamientos con esos filtros</h3>
                    <p>Prueba quitando algún filtro o ampliando la zona/presupuesto.</p>
                    <p style="margin-top:18px"><a href="/buscar" class="pd-btn pd-btn-ghost" style="padding:10px 18px;font-size:14px">Ver todos los alojamientos</a></p>
                </div>
            <?php else: ?>
                <div class="pd-buscar-grilla" id="pdBuscarGrilla" style="display:<?php echo $vista === 'grilla' ? 'grid' : 'none'; ?>">
                    <?php $reveal = true; require __DIR__ . '/_cards.php'; ?>
                </div>

                <div class="pd-buscar-mapa" id="pdBuscarMapaWrap" style="display:<?php echo $vista === 'mapa' ? 'block' : 'none'; ?>">
                    <div id="pdLeafletMap"></div>
                </div>

                <div class="pd-buscar-mas" id="pdBuscarMas" style="display:<?php echo $vista === 'grilla' ? ($hayMas ? 'block' : 'none') : 'none'; ?>">
                    <button type="button" class="pd-btn pd-btn-accent" id="pdVerMasBtn">Ver más resultados</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var coords = <?php echo json_encode($coords); ?>;
    var qsBase = <?php echo json_encode($qsBase); ?>;
    var pagina = <?php echo (int)$pagina; ?>;
    var porPagina = <?php echo (int)$porPagina; ?>;
    var total = <?php echo (int)$total; ?>;
    var monedaLabels = { TPM001: 'S/', TPM002: 'US$' };
    function precioFmt(m, c) { return (monedaLabels[c] || '') + ' ' + Number(m).toLocaleString('es-PE'); }

    // --- Toggle grilla / mapa ---
    var grilla = document.getElementById('pdBuscarGrilla');
    var mapaWrap = document.getElementById('pdBuscarMapaWrap');
    var masWrap = document.getElementById('pdBuscarMas');
    var leafletMap = null, layerGroup = null;

    function initMap() {
        if (leafletMap) return;
        var center = coords.length ? [coords[0].latitud, coords[0].longitud] : [-12.0464, -77.0428];
        leafletMap = L.map('pdLeafletMap').setView(center, 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(leafletMap);
        layerGroup = L.layerGroup().addTo(leafletMap);
        coords.forEach(function (c) {
            if (c.latitud == null || c.longitud == null) return;
            var marker = L.marker([c.latitud, c.longitud]);
            marker.bindPopup('<strong>' + (c.titulo || '') + '</strong><br>' + precioFmt(c.precio_mensual, c.moneda_codigo) + '/mes<br><a href="/alojamiento/' + c.alojamiento_id + '">Ver detalle</a>');
            layerGroup.addLayer(marker);
        });
        if (coords.length > 1) {
            var group = L.featureGroup(layerGroup.getLayers());
            leafletMap.fitBounds(group.getBounds().pad(0.2));
        }
    }

    document.getElementById('pdBuscarToggle').addEventListener('click', function (e) {
        var btn = e.target.closest('button'); if (!btn) return;
        var v = btn.getAttribute('data-vista');
        this.querySelectorAll('button').forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        if (v === 'mapa') {
            grilla.style.display = 'none'; mapaWrap.style.display = 'block'; masWrap.style.display = 'none';
            initMap(); setTimeout(function () { leafletMap && leafletMap.invalidateSize(); }, 100);
        } else {
            mapaWrap.style.display = 'none'; grilla.style.display = 'grid';
            masWrap.style.display = (pagina * porPagina < total) ? 'block' : 'none';
        }
    });

    // --- Ver más (AJAX append) ---
    var verMasBtn = document.getElementById('pdVerMasBtn');
    if (verMasBtn) {
        verMasBtn.addEventListener('click', function () {
            pagina++;
            verMasBtn.disabled = true; verMasBtn.textContent = 'Cargando…';
            fetch('/buscar?' + qsBase + '&pagina=' + pagina + '&ajax=1')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var tmp = document.createElement('div'); tmp.innerHTML = data.html;
                    while (tmp.firstChild) grilla.appendChild(tmp.firstChild);
                    if (!data.hayMas) { masWrap.style.display = 'none'; }
                    else { verMasBtn.disabled = false; verMasBtn.textContent = 'Ver más resultados'; }
                })
                .catch(function () { verMasBtn.disabled = false; verMasBtn.textContent = 'Ver más resultados'; });
        });
    }

    // Autocomplete de universidades en el input q
    var qInput = document.querySelector('.pd-buscar-filtros input[name=q]');
    if (qInput) {
        var box = document.createElement('div');
        box.style.cssText = 'position:absolute;z-index:50;background:#fff;border:1px solid var(--pd-line);border-radius:12px;box-shadow:var(--pd-sh-2);max-height:240px;overflow:auto;display:none;min-width:240px';
        qInput.parentNode.style.position = 'relative';
        qInput.parentNode.appendChild(box);
        var t;
        qInput.addEventListener('input', function () {
            clearTimeout(t); var q = qInput.value.trim();
            if (q.length < 2) { box.style.display = 'none'; return; }
            t = setTimeout(function () {
                fetch('/api/universidades?q=' + encodeURIComponent(q)).then(function (r) { return r.json(); }).then(function (data) {
                    box.innerHTML = '';
                    if (!data.length) { box.style.display = 'none'; return; }
                    data.forEach(function (u) {
                        var item = document.createElement('div');
                        item.textContent = u.nombre;
                        item.style.cssText = 'padding:10px 12px;cursor:pointer;font-size:13.5px;font-weight:500';
                        item.onmouseenter = function () { item.style.background = 'var(--pd-paper)'; };
                        item.onmouseleave = function () { item.style.background = ''; };
                        item.onmousedown = function () { qInput.value = u.nombre; box.style.display = 'none'; };
                        box.appendChild(item);
                    });
                    box.style.display = 'block';
                }).catch(function () { box.style.display = 'none'; });
            }, 250);
        });
        document.addEventListener('click', function (e) { if (e.target !== qInput) box.style.display = 'none'; });
    }
})();
</script>
