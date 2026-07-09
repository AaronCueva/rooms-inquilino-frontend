<?php
/** @var array $destacados */
/** @var array $universidades */
/** @var array $tipos */

$monedaLabels = ['TPM001' => 'S/', 'TPM002' => 'US$'];
$tipoLabels = ['TPA001' => 'Cuarto', 'TPA002' => 'Mini-depto', 'TPA003' => 'Depto completo'];
function precio_fmt($monto, $monedaCodigo, $monedaLabels) {
    $simbolo = $monedaLabels[$monedaCodigo] ?? '';
    return $simbolo . ' ' . number_format((float)$monto, 0, ',', '.');
}
// Pins del mapa: usamos destacados reales (hasta 5) con posiciones fijas variadas
$pinPositions = [[8,12],[26,64],[55,8],[70,52],[40,80]];
$pinPins = array_slice($destacados, 0, 5);
?>

<!-- ============ HERO ============ -->
<section class="pd-hero">
    <div class="pd-hero-grid"></div>
    <div class="pd-aurora a1"></div>
    <div class="pd-aurora a2"></div>

    <!-- Mapa interactivo con pines -->
    <div class="pd-map" aria-hidden="true">
        <div class="pd-road r1"></div>
        <div class="pd-road r2"></div>
        <div class="pd-campus" title="Tu universidad"><i class="fas fa-university"></i></div>
        <?php foreach ($pinPins as $i => $a): ?>
            <a href="/alojamiento/<?php echo htmlspecialchars($a['alojamiento_id']); ?>" class="pd-pin"
               style="left:<?php echo $pinPositions[$i][0]; ?>%;top:<?php echo $pinPositions[$i][1]; ?>%;animation-delay:<?php echo ($i * 0.18 + 0.5); ?>s">
                <span class="pd-bubble"><span class="pd-dot"></span><?php echo precio_fmt($a['precio_mensual'], $a['moneda_codigo'], $monedaLabels); ?></span>
                <span class="pd-stem"></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pd-hero-inner">
        <span class="pd-eyebrow">Alojamiento universitario verificado</span>
        <h1 class="pd-hero-title" style="margin-top:18px">
            <span class="pd-line"><span class="pd-line-i">Encuentra tu lugar</span></span>
            <span class="pd-line"><span class="pd-line-i">cerca del <span class="pd-hl">campus</span>.</span></span>
        </h1>
        <p class="pd-lead">Habitaciones y departamentos verificados a pasos de tu universidad. Sin comisiones ocultas, con contrato digital y comunidad real.</p>

        <div class="pd-hero-stats">
            <div class="pd-stat"><div class="pd-num" data-to="<?php echo max(count($destacados), 5); ?>">0</div><div class="pd-lbl">Alojamientos verificados</div></div>
            <div class="pd-stat"><div class="pd-num" data-to="48">0</div><div class="pd-lbl">Universidades aliadas</div></div>
            <div class="pd-stat"><div class="pd-num" data-to="1200">0</div><div class="pd-lbl">Estudiantes alojados</div></div>
        </div>

        <!-- Buscador -->
        <form class="pd-search" method="GET" action="/buscar">
            <div class="pd-field">
                <label>Universidad o ciudad</label>
                <input type="text" name="q" id="hero-universidad" placeholder="Ej. Universidad del Pacífico, Lima..." autocomplete="off">
            </div>
            <div class="pd-field">
                <label>Fecha de ingreso</label>
                <input type="date" name="fecha">
            </div>
            <div class="pd-field">
                <label>Presupuesto máx.</label>
                <input type="number" name="presupuesto" placeholder="S/ 800" min="100">
            </div>
            <button class="pd-btn pd-btn-accent" type="submit"><i class="fas fa-search"></i> Buscar</button>

            <div class="pd-type-row">
                <?php foreach ($tipos as $t): ?>
                    <label class="pd-chip">
                        <input type="checkbox" name="tipo[]" value="<?php echo htmlspecialchars($t['codigo']); ?>" onchange="this.closest('.pd-chip').classList.toggle('is-on', this.checked)">
                        <?php echo htmlspecialchars($t['nombre']); ?>
                    </label>
                <?php endforeach; ?>
                <label class="pd-chip"><input type="checkbox" name="amoblado" value="1" onchange="this.closest('.pd-chip').classList.toggle('is-on', this.checked)"> Amoblado</label>
                <label class="pd-chip"><input type="checkbox" name="mascotas" value="1" onchange="this.closest('.pd-chip').classList.toggle('is-on', this.checked)"> Mascotas</label>
            </div>
        </form>
    </div>
</section>

<!-- ============ DESTACADOS ============ -->
<section class="pd-section">
    <div class="pd-section-head pd-reveal">
        <div>
            <span class="pd-eyebrow">Destacados</span>
            <h2 style="margin-top:10px">Los mejores cerca del campus.</h2>
            <p class="pd-sub">Ordenados por calificación de estudiantes reales que ya viven ahí.</p>
        </div>
        <a href="/buscar" class="pd-btn pd-btn-ghost" style="padding:11px 20px;font-size:14px">Ver todos <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <?php if (empty($destacados)): ?>
        <div class="pd-reveal" style="text-align:center;padding:60px 0;color:var(--pd-muted)">
            <i class="fas fa-bed" style="font-size:48px;opacity:.25"></i>
            <p style="margin-top:14px">Aún no hay alojamientos destacados. Vuelve pronto.</p>
        </div>
    <?php else: ?>
        <div class="pd-cards">
            <?php foreach ($destacados as $i => $a): ?>
                <a href="/alojamiento/<?php echo htmlspecialchars($a['alojamiento_id']); ?>" class="pd-card pd-reveal d<?php echo ($i % 4) + 1; ?>">
                    <div class="pd-thumb">
                        <?php if (!empty($a['foto_principal'])): ?>
                            <img src="<?php echo htmlspecialchars($a['foto_principal']); ?>" alt="<?php echo htmlspecialchars($a['titulo']); ?>">
                        <?php else: ?>
                            <div class="pd-ph"><i class="fas fa-bed"></i></div>
                        <?php endif; ?>
                        <span class="pd-price"><?php echo precio_fmt($a['precio_mensual'], $a['moneda_codigo'], $monedaLabels); ?>/mes</span>
                    </div>
                    <div class="pd-body">
                        <span class="pd-kind"><?php echo htmlspecialchars($tipoLabels[$a['tipo_codigo']] ?? 'Alojamiento'); ?></span>
                        <h3><?php echo htmlspecialchars($a['titulo']); ?></h3>
                        <span class="pd-loc"><i class="fas fa-location-dot" style="color:var(--pd-accent)"></i> <?php echo htmlspecialchars($a['distrito'] ?? 'Distrito no disponible'); ?></span>
                        <div class="pd-foot">
                            <?php if (!empty($a['propietario_verificado'])): ?>
                                <span class="pd-verified"><i class="fas fa-check"></i> Verificado</span>
                            <?php else: ?>
                                <span style="font-size:12.5px;color:var(--pd-muted)"><?php echo htmlspecialchars(trim(($a['propietario_nombres'] ?? '') . ' ' . ($a['propietario_apellido'] ?? ''))); ?></span>
                            <?php endif; ?>
                            <?php if ($a['calificacion'] !== null): ?>
                                <span class="pd-stars"><i class="fas fa-star"></i> <?php echo number_format((float)$a['calificacion'], 1); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============ CÓMO FUNCIONA ============ -->
<section class="pd-section" style="background:var(--pd-surface)">
    <div class="pd-section-head pd-reveal" style="justify-content:center;text-align:center">
        <div>
            <span class="pd-eyebrow">3 pasos</span>
            <h2 style="margin-top:10px">Así de simple mudarte.</h2>
        </div>
    </div>
    <div class="pd-steps">
        <div class="pd-step pd-reveal d1">
            <span class="pd-n">01 / Busca</span>
            <div class="pd-ic"><i class="fas fa-magnifying-glass"></i></div>
            <h3>Filtra y compara</h3>
            <p>Por universidad, precio, servicios y distancia. Sin registro para solo mirar.</p>
        </div>
        <div class="pd-step pd-reveal d2">
            <span class="pd-n">02 / Reserva</span>
            <div class="pd-ic"><i class="fas fa-calendar-check"></i></div>
            <h3>Solicita y firma</h3>
            <p>Escribe al propietario, envía tu solicitud y firma el contrato digital sin salir de casa.</p>
        </div>
        <div class="pd-step pd-reveal d3">
            <span class="pd-n">03 / Múdate</span>
            <div class="pd-ic"><i class="fas fa-house-chimney"></i></div>
            <h3>Paga y disfruta</h3>
            <p>Paga tu primer mes desde la plataforma y recibe las llaves de tu nuevo lugar.</p>
        </div>
    </div>
</section>

<!-- ============ UNIVERSIDADES (marquee) ============ -->
<?php if (!empty($universidades)): ?>
<section class="pd-section" style="padding-top:30px;padding-bottom:30px">
    <div class="pd-reveal" style="text-align:center;margin-bottom:30px">
        <span class="pd-eyebrow">Comunidad</span>
        <h2 style="margin-top:10px">Universidades aliadas</h2>
    </div>
    <div class="pd-marquee pd-reveal">
        <div class="pd-marquee-track">
            <?php
            $uniList = array_merge($universidades, $universidades); // duplicar para loop continuo
            foreach ($uniList as $uni): ?>
                <span class="pd-uni"><i class="fas fa-university"></i> <?php echo htmlspecialchars($uni['nombre']); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ TESTIMONIOS ============ -->
<section class="pd-section">
    <div class="pd-section-head pd-reveal" style="justify-content:center;text-align:center">
        <div>
            <span class="pd-eyebrow">Voces reales</span>
            <h2 style="margin-top:10px">Lo que dicen quienes ya viven ahí.</h2>
        </div>
    </div>
    <div class="pd-quotes">
        <?php
        $testimonios = [
            ['ini'=>'CR','nombre'=>'Camila R.', 'uni'=>'Universidad del Pacífico', 'texto'=>'Encontré un cuarto a 10 min del campus en dos días. El propietario respondió rápido y todo fue transparente.'],
            ['ini'=>'JM','nombre'=>'José M.', 'uni'=>'PUCP', 'texto'=>'Ver las reseñas de otros estudiantes antes de reservar me dio toda la confianza. Cero sorpresas, todo verificado.'],
            ['ini'=>'AL','nombre'=>'Ana L.', 'uni'=>'UPC', 'texto'=>'El contrato digital y el pago desde la plataforma me dieron seguridad. Recomendado 100% para los que migran solos.'],
        ];
        foreach ($testimonios as $i => $t): ?>
            <div class="pd-quote pd-reveal d<?php echo $i + 1; ?>">
                <span class="pd-mark-q">"</span>
                <span class="pd-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span>
                <p><?php echo htmlspecialchars($t['texto']); ?></p>
                <div class="pd-who">
                    <span class="pd-av"><?php echo htmlspecialchars($t['ini']); ?></span>
                    <div><b><?php echo htmlspecialchars($t['nombre']); ?></b><span><?php echo htmlspecialchars($t['uni']); ?></span></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ CTA PROPIETARIOS ============ -->
<section class="pd-section" style="padding-top:0">
    <div class="pd-cta pd-reveal">
        <span class="pd-eyebrow" style="color:#fff">Para propietarios</span>
        <h2 style="margin-top:14px">¿Tienes un lugar cerca de un campus?</h2>
        <p>Publica tu inmueble y conéctate con estudiantes verificados de todo LATAM. Gestiona reservas, pagos y contratos desde un solo lugar.</p>
        <a href="#" class="pd-btn pd-btn-light">Soy propietario <i class="fas fa-arrow-right"></i></a>
    </div>
</section>

<!-- Autocomplete universidades -->
<script>
(function () {
    var input = document.getElementById('hero-universidad');
    if (!input) return;
    var box = document.createElement('div');
    box.style.cssText = 'position:absolute;z-index:50;background:#fff;border:1px solid var(--pd-line);border-radius:12px;box-shadow:var(--pd-sh-2);max-height:240px;overflow:auto;display:none;min-width:280px';
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(box);
    var t;
    input.addEventListener('input', function () {
        clearTimeout(t);
        var q = input.value.trim();
        if (q.length < 2) { box.style.display = 'none'; return; }
        t = setTimeout(function () {
            fetch('/api/universidades?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    box.innerHTML = '';
                    if (!data.length) { box.style.display = 'none'; return; }
                    data.forEach(function (u) {
                        var item = document.createElement('div');
                        item.innerHTML = '<i class="fas fa-university" style="color:var(--pd-primary);margin-right:8px"></i> ' + u.nombre;
                        item.style.cssText = 'padding:11px 14px;cursor:pointer;font-size:14px;font-weight:500';
                        item.onmouseenter = function () { item.style.background = 'var(--pd-paper)'; };
                        item.onmouseleave = function () { item.style.background = ''; };
                        item.onmousedown = function () { input.value = u.nombre; box.style.display = 'none'; };
                        box.appendChild(item);
                    });
                    box.style.display = 'block';
                }).catch(function () { box.style.display = 'none'; });
        }, 250);
    });
    document.addEventListener('click', function (e) { if (e.target !== input) box.style.display = 'none'; });
})();

// Parallax hero (aurora + pines) al hacer scroll
(function () {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var hero = document.querySelector('.pd-hero'); if (!hero) return;
    var a1 = hero.querySelector('.pd-aurora.a1'), a2 = hero.querySelector('.pd-aurora.a2');
    var pins = hero.querySelectorAll('.pd-pin');
    function onScroll() {
        var y = window.scrollY; if (y > hero.offsetHeight) return;
        if (a1) a1.style.translate = (y * 0.12) + 'px ' + (y * -0.08) + 'px';
        if (a2) a2.style.translate = (y * -0.10) + 'px ' + (y * 0.06) + 'px';
        pins.forEach(function (p, i) { p.style.translate = '0px ' + (y * (0.04 + i * 0.012)) + 'px'; });
    }
    window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
})();

// Spotlight que sigue el cursor en las cards
(function () {
    document.querySelectorAll('.pd-card').forEach(function (c) {
        c.addEventListener('pointermove', function (e) { var r = c.getBoundingClientRect(); c.style.setProperty('--mx', (e.clientX - r.left) + 'px'); c.style.setProperty('--my', (e.clientY - r.top) + 'px'); }, { passive: true });
    });
})();
</script>
