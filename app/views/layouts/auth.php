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
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/app-design.css">
    <style>
      /* Remapeo de tokens viejos (style.css) → design system pd-* para auth.
         Las clases auth (.auth-panel, .field, .btn-primary, .step-dot, ...)
         quedan intactas pero renderizan con paleta/typografía pd-*. */
      :root {
        --font-display: 'Space Grotesk', sans-serif;
        --font-body: 'Inter', sans-serif;
        --font-mono: 'JetBrains Mono', monospace;
        --ink: var(--pd-ink);
        --ink-soft: var(--pd-muted);
        --ink-faint: var(--pd-muted);
        --navy: var(--pd-ink);
        --navy-soft: var(--pd-ink);
        --red: var(--pd-accent);
        --red-dark: var(--pd-accent);
        --red-wash: var(--pd-accent-soft);
        --blue: var(--pd-primary);
        --blue-wash: var(--pd-sky);
        --green: #16A34A;
        --green-wash: var(--pd-lime);
        --paper: var(--pd-paper);
        --surface: var(--pd-surface);
        --line: var(--pd-line);
        --line-soft: var(--pd-line);
      }
      body { background: var(--pd-paper); }
      /* Botón primario auth → pd-btn-primary (ink, hover ultramarine) */
      .btn-primary { background: var(--pd-ink); color: #fff; box-shadow: none; }
      .btn-primary:hover { background: var(--pd-primary); color: #fff; }
      /* Visual panel gradient → paleta pd-* */
      .auth-visual {
        background:
          radial-gradient(700px 600px at 20% 30%, rgba(42,68,255,.30), transparent 60%),
          radial-gradient(500px 500px at 90% 80%, rgba(255,90,60,.22), transparent 60%),
          linear-gradient(155deg, var(--pd-ink) 0%, #0B0F1C 100%);
      }
      .auth-visual-inner p { color: #9AA1BC; }
    </style>
</head>
<body class="pd-body">
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
