<?php

namespace App\View\Components;

class NavbarComponent
{
    public static function render($activeSection = '')
    {
        // Detectar si el proyecto es Pre-Construcción
        $isPreConstruccion = ($_SESSION['area'] ?? 'Construccion') === 'Pre-Construccion';
        
        $usuarioRaw = trim($_SESSION['nombreUsuario'] ?? 'Usuario');

        // Abreviatura Inteligente (Ej: "Juan Felipe Benitez Ramos" -> "Juan F. Benitez R.")
        $usuario = $usuarioRaw;
        $nameParts = explode(' ', $usuarioRaw);
        if (count($nameParts) >= 3) {
            $firstName = $nameParts[0];
            $secondName = mb_substr($nameParts[1], 0, 1) . '.';
            $lastName = $nameParts[2];
            $secondLastName = isset($nameParts[3]) ? mb_substr($nameParts[3], 0, 1) . '.' : '';
            $usuario = trim("$firstName $secondName $lastName $secondLastName");
        }
        // Base URL helper could be improved, using simple relative for now or absolute if cleaner

        ob_start();
        ?>
        <!-- Unified Navbar Component -->
        <link rel="stylesheet" href="/public/css/navbar.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="/public/css/dark-mode.css?v=<?php echo time(); ?>">
        
        <nav class="navbar navbar-expand-xl navbar-dark navbar-aia fixed-top">
            <!-- Drawer Overlay -->
            <div class="drawer-overlay" id="drawerOverlay"></div>

            <div class="container-fluid"> <!-- Use container-fluid for full width single row -->
                
                <!-- Brand / Logo -->
                <a class="navbar-brand" href="/proyectos">
                    <!-- Icon Placeholder (No Image loaded yet as per request) -->
                    <i class="fas fa-drafting-compass mr-2"></i> 
                    Last Planner AIA
                    <?php if ($isPreConstruccion): ?>
                    <span class="badge badge-warning ml-2" style="font-size: 0.55rem; vertical-align: middle;">
                        <i class="fas fa-hard-hat mr-1"></i>Pre-Construcción
                    </span>
                    <?php endif; ?>
                </a>

                <!-- Mobile Toggler -->
                <button class="navbar-toggler" type="button" id="drawerToggle">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar Content (Desktop: Row, Mobile: Drawer) -->
                <div class="collapse navbar-collapse navbar-collapse-drawer" id="aiaNavbar">
                    
                    <!-- Mobile Drawer Header -->
                    <div class="drawer-header d-xl-none">
                        <h5 class="m-0">Menú</h5>
                        <button type="button" class="close-drawer" id="drawerClose">&times;</button>
                    </div>

                    <!-- Main Navigation Links (Center/Left) - ONLY if not identifying as project selector -->
                    <?php if ($activeSection !== 'proyectos'): ?>
                    <ul class="navbar-nav mr-auto ml-lg-4 main-links">
                        <li class="nav-item">
                            <a id="informacionGeneral" class="nav-link <?php echo $activeSection == 'general' ? 'active' : ''; ?>" href="#">
                                <i class="fas fa-info-circle nav-icon"></i>
                                <span class="nav-text-full">Información General</span>
                                <span class="nav-text-compact">I. General</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeSection == 'integracion' ? 'active' : ''; ?>" href="#">
                                <i class="fas fa-network-wired nav-icon"></i>
                                <span class="nav-text-full">Integración</span>
                                <span class="nav-text-compact">Integración</span>
                            </a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link <?php echo $activeSection == 'semanas' ? 'active' : ''; ?>" href="#">
                                <i class="far fa-calendar-alt nav-icon"></i>
                                <span class="nav-text-full">Semanas del Proyecto</span>
                                <span class="nav-text-compact">Semanas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeSection == 'programa_general' ? 'active' : ''; ?>" href="/programa-general">
                                <i class="fas fa-project-diagram nav-icon"></i>
                                <span class="nav-text-full">Programa General</span>
                                <span class="nav-text-compact">P. General</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="programacion_intermedia" class="nav-link <?php echo $activeSection == 'programacion_intermedia' ? 'active' : ''; ?>" href="/programacion-intermedia">
                                <i class="fas fa-clipboard-list nav-icon"></i>
                                <span class="nav-text-full">Liberación de Restricciones</span>
                                <span class="nav-text-compact">Restricciones</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="programacion_semanal" class="nav-link <?php echo $activeSection == 'programacion_semanal' ? 'active' : ''; ?>" href="/programacion-semanal">
                                <i class="fas fa-tasks nav-icon"></i>
                                <span class="nav-text-full">Programación Semanal</span>
                                <span class="nav-text-compact">P. Semanal</span>
                            </a>
                        </li>
                        <?php if (!$isPreConstruccion): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeSection == 'listado_actividades' ? 'active' : ''; ?>" href="/listado-actividades">
                                <i class="fas fa-list-ul nav-icon"></i>
                                <span class="nav-text-full">Listado de Actividades</span>
                                <span class="nav-text-compact">Listado</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeSection == 'pdc' ? 'active' : ''; ?>" href="/pdc">
                                <i class="fas fa-clipboard-check nav-icon"></i>
                                <span class="nav-text-full">PDC</span>
                                <span class="nav-text-compact">PDC</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeSection == 'contratos' ? 'active' : ''; ?>" href="/contratos">
                                <i class="fas fa-file-contract nav-icon"></i>
                                <span class="nav-text-full">Contratos</span>
                                <span class="nav-text-compact">Contratos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeSection == 'cic' ? 'active' : ''; ?>" href="/programacion-semanal/cic">
                                <i class="fas fa-chart-bar nav-icon"></i>
                                <span class="nav-text-full">CIC</span>
                                <span class="nav-text-compact">CIC</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <?php else: ?>
                    <!-- Empty Spacer for Center if needed or just auto margin -->
                    <ul class="navbar-nav mr-auto main-links"></ul>
                    <?php endif; ?>

                    <!-- User Profile & Actions (Right - Desktop Only) -->
                    <ul class="navbar-nav ml-auto align-items-center d-none d-xl-flex">
                        
                        <!-- Notificaciones (Campana) Desktop -->
                        <li class="nav-item dropdown mr-2">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="position: relative;">
                                <i class="fas fa-bell"></i>
                                <span class="badge badge-danger badge-pill" id="notificationBadge" style="position: absolute; top: 0px; right: -5px; display: none; font-size: 0.6rem;">0</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow-sm border-0" aria-labelledby="notificationDropdown" style="width: 320px; max-height: 400px; overflow-y: auto; padding: 0;">
                                <h6 class="dropdown-header bg-light border-bottom py-2 font-weight-bold">Centro de Notificaciones</h6>
                                <div id="notificationList">
                                    <a class="dropdown-item text-muted text-center py-3" href="#"><i class="fas fa-spinner fa-spin mr-2"></i> Cargando...</a>
                                </div>
                            </div>
                        </li>

                         <li class="nav-item dropdown">
                            <!-- Desktop Trigger -->
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <div class="user-avatar-sm">
                                    <i class="fas fa-user"></i>
                                </div>
                                <?php echo htmlspecialchars($usuario); ?>
                            </a>
                            
                            <!-- Dropdown Menu -->
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                                <?php if ($activeSection !== 'proyectos'): ?>
                                <a class="dropdown-item" href="/proyectos">
                                    <i class="fas fa-exchange-alt mr-2 text-muted"></i> Cambiar Proyecto
                                </a>
                                <div class="dropdown-divider"></div>
                                <?php endif; ?>
                                
                                <a class="dropdown-item text-danger" href="/logout">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                                </a>
                            </div>
                        </li>
                    </ul>

                    <!-- Mobile User Island (Thumb Zone - Mobile Only) -->
                    <div class="user-island d-xl-none">
                        <div class="user-island-header">
                            <div class="user-avatar-lg">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($usuario); ?></div>
                                <div class="user-role">
                                    <?php
                                        echo isset($_SESSION['rol']) ? htmlspecialchars($_SESSION['rol']) : 'Usuario';
        ?>
                                </div>
                            </div>
                        </div>
                        <div class="user-island-actions">
                            <?php if ($activeSection !== 'proyectos'): ?>
                            <a href="/proyectos" class="island-btn">
                                <i class="fas fa-exchange-alt"></i> Proyecto
                            </a>
                            <?php else: ?>
                            <a href="#" class="island-btn" style="opacity: 0.5; pointer-events: none;">
                                <i class="fas fa-exchange-alt"></i> Proyecto
                            </a>
                            <?php endif; ?>
                            
                            <!-- Notificaciones Dropdown Mobile -->
                            <div class="dropdown" style="display: flex;">
                                <a href="#" class="island-btn w-100 island-notification-btn" id="notificationDropdownMobile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-bell"></i> Avisos
                                    <span class="badge badge-danger badge-pill" id="notificationBadgeMobile" style="display: none;">0</span>
                                </a>
                                <div class="dropdown-menu shadow-sm border-0" aria-labelledby="notificationDropdownMobile" style="width: 260px; max-height: 300px; overflow-y: auto; padding: 0; bottom: 100%; top: auto !important; margin-bottom: 5px; left: 0;">
                                    <h6 class="dropdown-header bg-light border-bottom py-2 font-weight-bold">Notificaciones</h6>
                                    <div id="notificationListMobile">
                                        <a class="dropdown-item text-muted text-center py-3" href="#"><i class="fas fa-spinner fa-spin mr-2"></i> Cargando...</a>
                                    </div>
                                </div>
                            </div>

                            <a href="/logout" class="island-btn btn-danger-soft" style="grid-column: span 2;">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Spacing for fixed navbar -->
        <div style="height: 70px;"></div>

        <script>
            // Simple JS for Drawer Toggling (Vanilla JS for standard compatibility)
            document.addEventListener('DOMContentLoaded', function() {
                var toggle = document.getElementById('drawerToggle');
                var drawer = document.getElementById('aiaNavbar');
                var close = document.getElementById('drawerClose');
                var overlay = document.getElementById('drawerOverlay');

                function openDrawer() {
                    drawer.classList.add('show');
                    overlay.classList.add('show');
                    document.body.style.overflow = 'hidden'; // Prevent background scrolling
                }

                function closeDrawer() {
                    drawer.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }

                if(toggle) toggle.addEventListener('click', openDrawer);
                if(close) close.addEventListener('click', closeDrawer);
                if(overlay) overlay.addEventListener('click', closeDrawer);

                // --- Dark Mode Logic ---
                const darkModeToggle = document.getElementById('darkModeToggle');
                const body = document.body;
                const icon = darkModeToggle ? darkModeToggle.querySelector('i') : null;
                const text = document.getElementById('darkModeText');

                function updateUI(isDark) {
                    if(icon) {
                        if (isDark) {
                            icon.classList.remove('fa-moon');
                            icon.classList.add('fa-sun');
                            if(text) text.textContent = 'Modo Claro';
                        } else {
                            icon.classList.remove('fa-sun');
                            icon.classList.add('fa-moon');
                            if(text) text.textContent = 'Modo Oscuro';
                        }
                    }
                }

                // 1. Check LocalStorage
                const currentTheme = localStorage.getItem('aia-theme');
                if (currentTheme === 'dark') {
                    body.classList.add('dark-mode');
                    updateUI(true);
                } else {
                    updateUI(false);
                }

                // 2. Toggle Handler
                if (darkModeToggle) {
                    darkModeToggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation(); // Keep dropdown open to see effect
                        
                        body.classList.toggle('dark-mode');
                        const isDark = body.classList.contains('dark-mode');
                        
                        // Save preference
                        localStorage.setItem('aia-theme', isDark ? 'dark' : 'light');
                        
                        // Update UI
                        updateUI(isDark);
                    });
                }
            });
        </script>
        <script src="/public/js/components/notifications.js?v=<?php echo time(); ?>"></script>
        <?php
        return ob_get_clean();
    }
}
