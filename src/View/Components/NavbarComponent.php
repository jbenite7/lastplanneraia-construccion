<?php

namespace App\View\Components;

class NavbarComponent
{
    public static function render($activeSection = '')
    {
        // Detectar si el proyecto es Pre-Construcción
        $isPreConstruccion = ($_SESSION['area'] ?? 'Construccion') === 'Pre-Construccion';
        $canAccessBi = $activeSection === 'proyectos'
            ? BiAccessComponent::canAccessAny()
            : BiAccessComponent::canAccess();
        $biControlTowerUrl = $activeSection === 'proyectos'
            ? BiAccessComponent::globalUrl()
            : BiAccessComponent::url('control-tower');

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
                <a class="navbar-brand aia-brand" href="/proyectos" aria-label="Last Planner AIA">
                    <span class="aia-brand-mark" aria-hidden="true"></span>
                    <span class="aia-brand-name">Last Planner AIA</span>
                </a>

                <!-- Mobile Toggler -->
                <button class="navbar-toggler" type="button" id="drawerToggle"
                        aria-label="Abrir menú" aria-controls="aiaNavbar" aria-expanded="false">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar Content (Desktop: Row, Mobile: Drawer) -->
                <div class="collapse navbar-collapse navbar-collapse-drawer" id="aiaNavbar"
                     role="dialog" aria-modal="true" aria-labelledby="drawerTitle">

                    <!-- Mobile Drawer Header -->
                    <div class="drawer-header d-xl-none">
                        <h5 class="m-0" id="drawerTitle">Menú</h5>
                        <button type="button" class="close-drawer" id="drawerClose"
                                aria-label="Cerrar menú">&times;</button>
                    </div>

                    <!-- Main Navigation Links (Center/Left) - ONLY if not identifying as project selector -->
                    <?php if ($activeSection !== 'proyectos'): ?>
                    <ul class="navbar-nav mr-auto ml-lg-4 main-links">
                        <li class="nav-item dropdown">
                            <a id="informacionGeneral" class="nav-link dropdown-toggle <?php echo in_array($activeSection, ['general', 'listado_actividades', 'contratos', 'bi_control_tower'], true) ? 'active' : ''; ?>" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-info-circle nav-icon"></i>
                                <span class="nav-text-full">Información General</span>
                                <span class="nav-text-compact">I. General</span>
                            </a>
                            <ul class="dropdown-menu" id="informacionGeneralMenu" aria-labelledby="informacionGeneral">
                                <?php if ($canAccessBi): ?>
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($biControlTowerUrl, ENT_QUOTES, 'UTF-8'); ?>" data-bi-access-link="control-tower"><i class="fas fa-chart-line mr-2"></i>Control Tower</a></li>
                                <?php endif; ?>
                            </ul>
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
                                <span class="badge badge-danger badge-pill" id="notificationBadge" style="position: absolute; top: 0px; right: 0; display: none; font-size: 0.6rem;">0</span>
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

                                <button type="button" class="dropdown-item aia-theme-switch" aria-pressed="false">
                                    <i class="fas fa-moon mr-2 text-muted" aria-hidden="true"></i>
                                    <span class="aia-theme-switch-text">Modo oscuro</span>
                                </button>
                                <div class="dropdown-divider"></div>

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

                            <?php if ($canAccessBi): ?>
                            <a href="<?php echo htmlspecialchars($biControlTowerUrl, ENT_QUOTES, 'UTF-8'); ?>" class="island-btn" data-bi-access-link="control-tower">
                                <i class="fas fa-chart-line"></i> BI
                            </a>
                            <?php endif; ?>

                            <button type="button" class="island-btn aia-theme-switch" aria-pressed="false">
                                <i class="fas fa-moon" aria-hidden="true"></i>
                                <span class="aia-theme-switch-text">Oscuro</span>
                            </button>

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
        <div class="shell-nav-spacer" aria-hidden="true"></div>

        <script src="/public/js/modules/aia_ui/nav_drawer.js?v=<?= filemtime(__DIR__ . '/../../../public/js/modules/aia_ui/nav_drawer.js') ?>"></script>
        <script>
            // Simple JS for Drawer Toggling (Vanilla JS for standard compatibility)
            function bindThemeSwitchesWhenReady() {
                if (window.AiaDesignSystem && typeof window.AiaDesignSystem.bindThemeSwitches === 'function') {
                    window.AiaDesignSystem.bindThemeSwitches(document);
                    return;
                }
                document.addEventListener('aia-theme-ready', function() {
                    window.AiaDesignSystem.bindThemeSwitches(document);
                }, { once: true });
            }
            document.addEventListener('DOMContentLoaded', function() {
                if (window.AiaNavDrawer) window.AiaNavDrawer.init();
                bindThemeSwitchesWhenReady();
            });
        </script>
        <script src="/public/js/components/notifications.js?v=<?php echo time(); ?>"></script>
        <?php
        return ob_get_clean();
    }
}
