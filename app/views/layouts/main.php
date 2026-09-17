<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APP-ROOMS | Inquilinos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/app-design.css">
</head>
<body class="pd-body">
<?php
$current_uri = $_SERVER['REQUEST_URI'];
$rol_id = $_SESSION['rol_id'] ?? null;

// Menú dinámico por rol (admin/propietario — inquilino usa links fijos)
$menuModel = new \App\Models\MenuMaestro();
$menu_items = $menuModel->obtenerMenuPorRol($rol_id);

// Mensajes no leídos (W6)
$pdNoLeidos = 0;
try {
    $pdNoLeidos = (int)(new \App\Models\Chat())->contarNoLeidos($_SESSION['usuario_id']);
} catch (\Throwable $pdE) { $pdNoLeidos = 0; }

// Avatar
$foto_usuario = $_SESSION['url_foto'] ?? null;
$pd_nom = $_SESSION['nombres'] ?? '';
$pd_ape = $_SESSION['apellidos'] ?? '';
$pd_mb = function_exists('mb_substr');
$avatar_letras = strtoupper(
    ($pd_mb ? mb_substr($pd_nom, 0, 1) : substr($pd_nom, 0, 1)) .
    ($pd_mb ? mb_substr($pd_ape, 0, 1) : substr($pd_ape, 0, 1))
);
?>

<!-- Immersive overlays -->
<div class="pd-progress" id="pdProgress"></div>
<div class="pd-grain" aria-hidden="true"></div>
<div class="pd-cursor-glow" id="pdCursorGlow" aria-hidden="true"></div>

<!-- HEADER -->
<header class="pd-header" id="pdHeader">
    <div class="pd-header-inner">
        <a href="/dashboard" class="pd-brand">
            <span class="pd-mark"><i class="fas fa-house-user"></i></span>
            APP-<span style="color:var(--pd-primary)">ROOMS</span>
        </a>

        <div class="pd-header-right">
            <nav class="pd-nav">
                <a href="/dashboard" class="pd-nav-link <?php echo (strpos($current_uri, '/dashboard') !== false) ? 'is-active' : ''; ?>">Dashboard</a>
                <a href="/buscar" class="pd-nav-link <?php echo (strpos($current_uri, '/buscar') !== false) ? 'is-active' : ''; ?>">Buscar</a>
                <a href="/reservas" class="pd-nav-link <?php echo (strpos($current_uri, '/reservas') !== false) ? 'is-active' : ''; ?>">Mis reservas</a>
                <a href="/contratos" class="pd-nav-link <?php echo (strpos($current_uri, '/contratos') !== false || strpos($current_uri, '/contrato/') !== false) ? 'is-active' : ''; ?>">Mis contratos</a>
                <a href="/pagos" class="pd-nav-link <?php echo (strpos($current_uri, '/pagos') !== false || strpos($current_uri, '/pago/') !== false) ? 'is-active' : ''; ?>">Mis Pagos</a>
                <a href="/foros" class="pd-nav-link <?php echo (strpos($current_uri, '/foros') !== false) ? 'is-active' : ''; ?>">Comunidad</a>
                <a href="/blog" class="pd-nav-link <?php echo (strpos($current_uri, '/blog') !== false) ? 'is-active' : ''; ?>">Blog</a>
                <a href="/mensajes" class="pd-nav-link <?php echo (strpos($current_uri, '/mensajes') !== false) ? 'is-active' : ''; ?>" style="position:relative">Mensajes<?php if ($pdNoLeidos > 0): ?><span style="position:absolute;top:-6px;right:-12px;background:var(--pd-accent);color:#fff;font-size:10px;font-weight:700;min-width:17px;height:17px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;padding:0 4px"><?php echo $pdNoLeidos > 9 ? '9+' : $pdNoLeidos; ?></span><?php endif; ?></a>

                <?php foreach ($menu_items as $seccion): ?>
                    <?php if (empty($seccion['url'])): ?>
                        <?php foreach ($seccion['hijos'] as $hijo): ?>
                            <a href="<?php echo htmlspecialchars($hijo['url']); ?>" class="pd-nav-link <?php echo (strpos($current_uri, $hijo['url']) !== false) ? 'is-active' : ''; ?>"><?php echo htmlspecialchars($hijo['nombre']); ?></a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars($seccion['url']); ?>" class="pd-nav-link <?php echo (strpos($current_uri, $seccion['url']) !== false) ? 'is-active' : ''; ?>"><?php echo htmlspecialchars($seccion['nombre']); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div style="display:flex;align-items:center;gap:10px;">
                <!-- Avatar con dropdown -->
                <div class="dropdown">
                    <div class="pd-avatar-btn" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer">
                        <?php if (!empty($foto_usuario)): ?>
                            <img src="<?php echo htmlspecialchars($foto_usuario); ?>" alt="avatar">
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($avatar_letras); ?></span>
                        <?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end pd-dropdown">
                        <li class="pd-dropdown-head"><?php echo htmlspecialchars($pd_nom . ' ' . $pd_ape); ?></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/reservas"><i class="fas fa-calendar-check fa-sm fa-fw me-2"></i> Mis reservas</a></li>
                        <li><a class="dropdown-item" href="/contratos"><i class="fas fa-file-contract fa-sm fa-fw me-2"></i> Mis contratos</a></li>
                        <li><a class="dropdown-item" href="/perfil"><i class="fas fa-user fa-sm fa-fw me-2"></i> Mi perfil</a></li>
                        <li><a class="dropdown-item" href="/puntos"><i class="fas fa-star fa-sm fa-fw me-2"></i> Mis puntos</a></li>
                        <li><a class="dropdown-item" href="/referidos"><i class="fas fa-user-plus fa-sm fa-fw me-2"></i> Referidos</a></li>
                        <li><a class="dropdown-item" href="/perfil/verificar"><i class="fas fa-id-card fa-sm fa-fw me-2"></i> Verificación</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" style="color:#e11d48;font-weight:600;" href="/logout"><i class="fas fa-sign-out-alt fa-sm fa-fw me-2"></i> Cerrar sesión</a></li>
                    </ul>
                </div>

                <!-- Botón rápido de salida circular -->
                <a href="/logout" title="Cerrar sesión" style="width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff1f2;color:#e11d48;border:1px solid #fecdd3;text-decoration:none;transition:all .2s;"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </div>
</header>

<!-- CONTENIDO -->
<main>
    <?php echo $content; ?>
</main>

<!-- FOOTER -->
<footer class="pd-footer">
    <div class="pd-footer-inner">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="pd-brand mb-3">
                    <span class="pd-mark"><i class="fas fa-house-user"></i></span>
                    APP-<span style="color:var(--pd-primary)">ROOMS</span>
                </div>
                <p style="font-size:14px;max-width:300px">Alojamiento universitario verificado cerca de tu campus. Hecho para estudiantes de LATAM.</p>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <h6>Plataforma</h6>
                <ul>
                    <li><a href="/dashboard">Dashboard</a></li>
                    <li><a href="/buscar">Buscar alojamiento</a></li>
                    <li><a href="/foros">Comunidad</a></li>
                    <li><a href="/blog">Blog</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <h6>Mi cuenta</h6>
                <ul>
                    <li><a href="/perfil">Mi perfil</a></li>
                    <li><a href="/reservas">Mis reservas</a></li>
                    <li><a href="/contratos">Mis contratos</a></li>
                    <li><a href="/puntos">Mis puntos</a></li>
                    <li><a href="/referidos">Referidos</a></li>
                    <li><a href="/mensajes">Mensajes</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <h6>Contacto</h6>
                <ul>
                    <li><i class="fas fa-envelope me-2"></i> hola@app-rooms.com</li>
                    <li><i class="fab fa-whatsapp me-2"></i> +51 999 999 999</li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <h6>Síguenos</h6>
                <ul class="d-flex gap-3">
                    <li><a href="#"><i class="fab fa-instagram fa-lg"></i></a></li>
                    <li><a href="#"><i class="fab fa-facebook fa-lg"></i></a></li>
                    <li><a href="#"><i class="fab fa-tiktok fa-lg"></i></a></li>
                </ul>
            </div>
        </div>
        <div class="pd-footer-bottom">
            <span>© <?php echo date('Y'); ?> APP-ROOMS · Nido Universitario</span>
            <span>Hecho con cariño para estudiantes universitarios de LATAM</span>
        </div>
    </div>
</footer>

<!-- Floating chat -->
<a href="https://wa.me/51999999999" class="pd-chat" target="_blank" rel="noopener" title="Chat de soporte"><i class="fab fa-whatsapp"></i></a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Header shadow on scroll
    (function () {
        var h = document.getElementById('pdHeader');
        function onScroll() { h.classList.toggle('is-scrolled', window.scrollY > 8); }
        window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
    })();

    // Scroll reveal
    (function () {
        var els = document.querySelectorAll('.pd-reveal');
        if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('is-in'); }); return; }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
        }, { threshold: .14, rootMargin: '0px 0px -8% 0px' });
        els.forEach(function (e) { io.observe(e); });
    })();

    // Count-up stats
    (function () {
        var nums = document.querySelectorAll('.pd-num[data-to]');
        if (!('IntersectionObserver' in window)) return;
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                var el = en.target, to = +el.getAttribute('data-to'), from = 0, dur = 1100, t0 = performance.now();
                function tick(now) {
                    var p = Math.min(1, (now - t0) / dur);
                    el.textContent = Math.round((to - from) * (1 - Math.pow(1 - p, 3)) + from);
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick); io.unobserve(el);
            });
        }, { threshold: .5 });
        nums.forEach(function (n) { io.observe(n); });
    })();

    // Flash messages
    document.addEventListener('DOMContentLoaded', function () {
        <?php $flash = \App\Core\Controller::getFlash(); ?>
        <?php if ($flash): ?>
            Swal.fire({
                icon: '<?php echo $flash['tipo']; ?>',
                title: '<?php echo addslashes($flash['mensaje']); ?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                customClass: { popup: 'rounded-3 shadow-lg border-0' }
            });
        <?php endif; ?>
    });

    // Scroll progress bar
    (function () {
        var bar = document.getElementById('pdProgress'); if (!bar) return;
        function upd() { var h = document.documentElement, max = h.scrollHeight - h.clientHeight; bar.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%'; }
        window.addEventListener('scroll', upd, { passive: true }); upd();
    })();

    // Cursor glow
    (function () {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var g = document.getElementById('pdCursorGlow'); if (!g) return;
        var mx = innerWidth / 2, my = innerHeight / 2, cx = mx, cy = my;
        window.addEventListener('pointermove', function (e) { mx = e.clientX; my = e.clientY; }, { passive: true });
        (function loop() { cx += (mx - cx) * .12; cy += (my - cy) * .12; g.style.transform = 'translate(' + cx + 'px,' + cy + 'px)'; requestAnimationFrame(loop); })();
    })();

    // Magnetic buttons
    (function () {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        document.querySelectorAll('.pd-btn-primary, .pd-btn-accent, .pd-btn-light').forEach(function (b) {
            b.addEventListener('pointermove', function (e) { var r = b.getBoundingClientRect(); b.style.translate = ((e.clientX - r.left - r.width / 2) * 0.25) + 'px ' + ((e.clientY - r.top - r.height / 2) * 0.35) + 'px'; });
            b.addEventListener('pointerleave', function () { b.style.translate = '0px 0px'; });
        });
    })();

    // Confirmación destructiva con SweetAlert2
    function confirmarAccionSweet(event, titulo, texto, icono = 'warning', textConfirm = '<i class="fas fa-check me-1"></i> Sí, continuar', btnColor = '#EF4444') {
        event.preventDefault();
        const form = event.target.closest('form') || event.target;
        Swal.fire({
            title: titulo || '¿Estás seguro?',
            text: texto || 'Esta acción no se puede deshacer.',
            icon: icono,
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#64748B',
            confirmButtonText: textConfirm,
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Procesando...', text: 'Por favor espera un momento', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => { Swal.showLoading(); } });
                form.submit();
            }
        });
        return false;
    }
</script>
</body>
</html>
