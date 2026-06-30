<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APP-ROOMS | Inquilinos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <div class="auth-view active">
        <!-- Contenedor dinámico (auth/login o auth/register) -->
        <?php echo $content; ?>
        
        <div class="auth-visual">
            <div class="auth-visual-inner">
                <?php if (strpos($_SERVER['REQUEST_URI'], 'register') !== false): ?>
                <h2>Descubre tu próximo lugar ideal para vivir</h2>
                <p>Encuentra opciones seguras y verificadas cerca a tu universidad o lugar de trabajo.</p>
                <?php else: ?>
                <h2>Encuentra, reserva y alquila — todo en APP-ROOMS</h2>
                <p>Miles de opciones de alojamiento validadas especialmente para estudiantes y profesionales jóvenes.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php $flash = \App\Core\Controller::getFlash(); ?>
            <?php if ($flash): ?>
                Swal.fire({
                    icon: '<?php echo $flash['tipo']; ?>',
                    title: '<?php echo addslashes($flash['mensaje']); ?>',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
