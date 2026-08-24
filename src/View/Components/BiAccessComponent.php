<?php

namespace App\View\Components;

use App\Security\BiPreviewAccessPolicy;
use App\Security\RbacService;
use App\Support\BiProjectScope;
use Database;

class BiAccessComponent
{
    private const ROUTES = [
        'control-tower' => '/bi/control-tower',
        'programa-general' => '/bi/programa-general',
        'intermedia' => '/bi/intermedia',
        'semanal' => '/bi/semanal',
        'pdc' => '/bi/pdc',
        'subcontratistas' => '/bi/contratistas',
        'profesionales' => '/bi/responsables',
        'indicadores' => '/bi/curva-s',
    ];

    /**
     * `BiViewController` identifica cada pantalla por reportKey ('overview', 'cip', ...);
     * ROUTES identifica cada módulo por el nombre que usan los enlaces ('control-tower',
     * 'profesionales', ...). Los dos vocabularios no coinciden 1:1 (cic → subcontratistas,
     * cip → profesionales, overview → control-tower), así que hace falta este mapa.
     */
    private const REPORT_KEY_TO_MODULE = [
        'overview' => 'control-tower',
        'programa-general' => 'programa-general',
        'intermedia' => 'intermedia',
        'semanal' => 'semanal',
        'pdc' => 'pdc',
        'cic' => 'subcontratistas',
        'cip' => 'profesionales',
        'curva-s' => 'indicadores',
    ];

    /**
     * A qué módulo aterriza cada rol al abrir la Torre desde el enlace de entrada.
     * Decisión de Felipe, 2026-08-24 (reemplaza D72 "sin conmutador" para R y A):
     * docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md.
     */
    public static function defaultModuleForRole(string $role): string
    {
        $role = strtoupper(trim($role));

        if ($role === 'D' || $role === 'R') {
            return 'intermedia';
        }

        if ($role === 'A') {
            return self::adminLastModule() ?? 'control-tower';
        }

        return 'control-tower';
    }

    /**
     * Último módulo que el Admin visitó en esta sesión, o null si no ha entrado
     * todavía. Lo escribe BiViewController::renderView() en cada visita. Ver Tarea 3.
     */
    private static function adminLastModule(): ?string
    {
        $reportKey = $_SESSION['bi_admin_last_module'] ?? null;
        if (!is_string($reportKey) || $reportKey === '') {
            return null;
        }

        return self::REPORT_KEY_TO_MODULE[$reportKey] ?? null;
    }

    /**
     * El módulo BI está oculto de la navegación mientras se termina de desarrollar
     * (spec del 2026-08-13: docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md).
     * Sus accesos —barra lateral, selector de proyectos, tarjeta del cajón contextual,
     * los cinco botones «BI …» y los boot-configs de JS— solo se pintan para quien
     * además puede abrir el módulo. Hoy eso incluye Admin, Director (desde el
     * 2026-08-20) y Residente (Tarea 3, 2026-08-24), estos dos últimos sujetos al
     * interruptor global `BiPreviewAccessPolicy::canOpen()`.
     *
     * Corregido el 2026-08-13, mismo día: la primera versión devolvía `false` para
     * todos, y dejaba al propio Admin teniendo que teclear la URL. Lo reportó el
     * usuario al no ver los accesos en producción entrando como administrador.
     *
     * El gate va PRIMERO y el alcance por proyecto después: un Admin sin acceso al
     * proyecto activo no debe ver un enlace que luego le rechazaría BiProjectScope.
     * Para revertir el ocultamiento, quitar la llamada a BiPreviewAccessPolicy de
     * ambos métodos; el resto del cuerpo es el original.
     */
    public static function canAccess(?string $role = null): bool
    {
        if (!BiPreviewAccessPolicy::canOpen($_SESSION ?? [])) {
            return false;
        }

        $db = Database::getInstance();
        if ($role !== null) {
            return (new RbacService($db))->can('lps.indicadores.ver', $role);
        }

        $scope = new BiProjectScope($db);
        $projectId = (int) ($_SESSION['project_id'] ?? 0);

        return $projectId > 0
            ? $scope->canAccessProject($projectId, $_SESSION)
            : $scope->hasAnyAccess($_SESSION);
    }

    public static function canAccessAny(): bool
    {
        if (!BiPreviewAccessPolicy::canOpen($_SESSION ?? [])) {
            return false;
        }

        return (new BiProjectScope(Database::getInstance()))->hasAnyAccess($_SESSION);
    }

    public static function globalUrl(string $module = 'control-tower'): string
    {
        return self::ROUTES[$module] ?? self::ROUTES['control-tower'];
    }

    public static function url(string $module = 'control-tower', array $context = []): string
    {
        $path = self::ROUTES[$module] ?? self::ROUTES['control-tower'];
        $params = [];

        $projectIds = BiProjectScope::normalizeProjectIds(
            $context['project_ids'] ?? ($_GET['project_ids'] ?? null),
        );
        if ($projectIds !== []) {
            $params['project_ids'] = $projectIds;
        } else {
            $projectId = $context['project_id'] ?? ($_SESSION['project_id'] ?? null);
            if ($projectId !== null && (int) $projectId > 0) {
                $params['project_id'] = (int) $projectId;
            }
        }

        $week = $context['semana'] ?? ($_GET['semana'] ?? ($_SESSION['semana'] ?? null));
        if ($week !== null && (int) $week > 0) {
            $params['semana'] = (int) $week;
        }

        foreach (['desde', 'hasta', 'sub', 'resp', 'etapa', 'theme'] as $filter) {
            $value = $context[$filter] ?? ($_GET[$filter] ?? '');
            if (is_scalar($value) && trim((string) $value) !== '') {
                $params[$filter] = trim((string) $value);
            }
        }

        return $path . ($params ? '?' . http_build_query($params) : '');
    }

    public static function renderLink(
        string $module = 'control-tower',
        string $label = 'Control Tower',
        string $class = 'aia-btn aia-btn--secondary',
        array $attributes = [],
    ): string {
        if (!self::canAccess()) {
            return '';
        }

        $attrs = array_merge([
            'href' => self::url($module),
            'class' => $class,
            'data-bi-access-link' => $module,
            'data-bi-base-url' => self::ROUTES[$module] ?? self::ROUTES['control-tower'],
            'aria-label' => $label,
        ], $attributes);

        $htmlAttrs = '';
        foreach ($attrs as $key => $value) {
            $htmlAttrs .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<a' . $htmlAttrs . '><i class="fas fa-chart-line" aria-hidden="true"></i> <span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></a>';
    }

    public static function renderBootConfig(string $module = 'control-tower'): string
    {
        if (!self::canAccess()) {
            return '';
        }

        $payload = [
            'enabled' => true,
            'module' => $module,
            'baseUrl' => self::ROUTES[$module] ?? self::ROUTES['control-tower'],
            'projectId' => (int) ($_SESSION['project_id'] ?? 0),
            'semana' => (int) ($_SESSION['semana'] ?? 0),
        ];

        return '<script>window.__BI_ACCESS__ = Object.assign({}, window.__BI_ACCESS__ || {}, '
            . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . ');</script>';
    }
}
