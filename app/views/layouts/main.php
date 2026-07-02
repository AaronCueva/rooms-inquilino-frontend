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
    <?php
    $current_uri = $_SERVER['REQUEST_URI'];
    $rol_id = $_SESSION['rol_id'] ?? null;
    
    // Obtener menú dinámico
    $menuModel = new \App\Models\MenuMaestro();
    $menu_items = $menuModel->obtenerMenuPorRol($rol_id);
    
    $foto_usuario = $_SESSION['url_foto'] ?? null;
    $nombre_completo = $_SESSION['nombres'] . ' ' . $_SESSION['apellidos'];
    $avatar_letras = strtoupper(substr($_SESSION['nombres'], 0, 1) . substr($_SESSION['apellidos'], 0, 1));
    ?>

    <div class="app-shell active">
        <!-- BARRA NAVEGACIÓN SUPERIOR (ESTILO INQUILINO) -->
        <nav class="app-nav">
            <div class="nav-content">
                <div class="nav-brand">
                    <div class="logo-box"><i class="fas fa-building"></i></div>
                    <div class="wm">APP-<span>ROOMS</span></div>
                </div>
                
                <!-- Menú dinámico horizontal -->
                <div class="nav-links">
                    <?php foreach ($menu_items as $seccion): ?>
                        <?php if (empty($seccion['url'])): // Si es una sección padre, ignoramos para barra horizontal o lo mostramos como dropdown ?>
                            <?php foreach ($seccion['hijos'] as $hijo): ?>
                                <a class="nav-link <?php echo (strpos($current_uri, $hijo['url']) !== false) ? 'is-active' : ''; ?>" 
                                   href="<?php echo htmlspecialchars($hijo['url']); ?>">
                                    <?php echo htmlspecialchars($hijo['nombre']); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: // Enlace directo ?>
                            <a class="nav-link <?php echo (strpos($current_uri, $seccion['url']) !== false) ? 'is-active' : ''; ?>" 
                               href="<?php echo htmlspecialchars($seccion['url']); ?>">
                                <?php echo htmlspecialchars($seccion['nombre']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="nav-actions">
                    <!-- Icono de comunidad/foros para inquilinos -->
                    <a href="/foros" class="icon-btn text-decoration-none d-flex align-items-center justify-content-center" title="Comunidad y Foros Universitarios" style="color: inherit;">
                        <i class="fas fa-users"></i>
                    </a>
                    <button class="icon-btn"><i class="fas fa-bell"></i><span class="dot"></span></button>
                    <div class="user-menu dropdown">
                        <div class="nav-avatar" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                            <?php echo $avatar_letras; ?>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow animated--grow-in">
                            <li><a class="dropdown-item" href="/foros"><i class="fas fa-users fa-sm fa-fw me-2 text-primary"></i> Comunidad Universitaria</a></li>
                            <li><a class="dropdown-item" href="/perfil"><i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i> Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- CONTENIDO PRINCIPAL -->
        <?php echo $content; ?>
        
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
                    timer: 3500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-3 shadow-lg border-0'
                    }
                });
            <?php endif; ?>
        });

        /**
         * Función global para confirmar acciones destructivas o importantes con SweetAlert2
         */
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
                customClass: {
                    popup: 'rounded-4 shadow-lg border-0',
                    confirmButton: `btn ${btnColor === '#EF4444' ? 'btn-danger' : 'btn-primary'} px-4 py-2 rounded-pill fw-bold ms-2 shadow-sm`,
                    cancelButton: 'btn btn-light px-4 py-2 rounded-pill fw-bold text-dark border'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar indicador de carga en SweetAlert mientras se envía
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Por favor espera un momento',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        customClass: {
                            popup: 'rounded-4 shadow-lg border-0'
                        }
                    });
                    form.submit();
                }
            });
            return false;
        }
    </script>
</body>
</html>
