<?php

namespace App\View\Components;

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

    public static function canAccess(?string $role = null): bool
    {
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
        string $class = 'btn-pdc-modern',
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
