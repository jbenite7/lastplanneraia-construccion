<?php

declare(strict_types=1);

namespace App\Services\Shell;

use App\Security\RbacManager;

/**
 * Manifiesto único de navegación del shell React (spec T01 §8.2/§10).
 *
 * Puro a propósito: recibe el rol ya normalizado — `SessionApiController` es quien llama
 * `RbacService::normalizeRole()`, porque instanciar `RbacService` toca `Database::getInstance()`
 * en el constructor y eso rompería el nivel `puro` de sus pruebas — el área activa del
 * proyecto y la entrada BI ya autorizada por `BiAccessComponent` (T01 sigue respetándolo
 * hasta T03). No conoce sesión, base de datos ni HTTP.
 *
 * Reemplaza `ocultasPorRol` (React) para el shell nuevo. La tabla histórica equivalente en
 * `views/partials/shell_sidebar.php` (`$shellHiddenByRole`) sigue viva a propósito: el spec
 * (§10.2) la retira "al último corte", no en esta tarea — tocar el shell PHP legado está
 * fuera de alcance de T01-Tarea 3 ("Legado: corrige la causa con el cambio mínimo").
 */
final class ShellNavigationService
{
    /**
     * Transcripción de la visibilidad legacy de maestroPermisos, la misma que hoy vive
     * duplicada en `NavegacionLateral.tsx` (`ocultasPorRol`, que esta tarea retira) y en
     * `shell_sidebar.php` (`$shellHiddenByRole`). Única fuente para el bootstrap React.
     */
    private const OCULTOS_POR_ROL = [
        'G' => ['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
        'S' => ['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
        'SG' => ['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
        'C' => ['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
        'V' => ['actualizar-cronograma', 'control-cambios'],
        'OT' => ['actualizar-cronograma'],
        'DCV' => ['actualizar-cronograma'],
    ];

    /**
     * @param string $role Rol ya normalizado por `RbacService::normalizeRole()`.
     * @param string|null $area Área activa del proyecto (`Construccion`/`Pre-Construccion`); `null`
     *   se trata como `Construccion` (mismo default que `shell_sidebar.php`).
     * @param array{visible:bool,href:string|null} $bi Entrada de Control Tower ya autorizada.
     *
     * @return list<array{id:string,label:string,items:list<array{id:string,label:string,href:?string,icon:?string,action:bool}>}>
     */
    public static function build(string $role, ?string $area, array $bi): array
    {
        $hidden = self::hiddenIdsFor($role, $area);

        $groups = [
            [
                'id' => 'informacion',
                'items' => self::compactItems([
                    self::biItem($bi),
                    self::actionItem('semanas-proyecto', 'Semanas del Proyecto', 'calendar'),
                    self::linkItem('profesionales', 'Profesionales', '/profesionales', 'user', $hidden),
                    self::linkItem('subcontratistas', 'Subcontratistas', '/subcontratistas', 'contract', $hidden),
                    self::linkItem('indicadores', 'Indicadores LPS', '/indicadores', 'overview', $hidden),
                    self::linkItem('control-cambios', 'Control de Cambios', '/control-cambios', 'integration', $hidden),
                ]),
            ],
            [
                'id' => 'obra',
                'items' => self::compactItems([
                    self::linkItem('programa-general', 'Programa General', '/programa-general', 'program', $hidden),
                    self::linkItem('programacion-intermedia', 'Programación Intermedia', '/programacion-intermedia', 'tasks', $hidden),
                    self::linkItem('programacion-semanal', 'Programación Semanal', '/programacion-semanal', 'calendar', $hidden),
                    self::linkItem('actualizar-cronograma', 'Actualizar Cronograma', '/programa-general-actualizar', 'sync', $hidden),
                ]),
            ],
            [
                'id' => 'compras',
                'items' => self::compactItems([
                    self::linkItem('plan-compras', 'Plan de Compras', '/plan-compras', 'clipboard', $hidden),
                ]),
            ],
        ];

        foreach ($groups as $index => $group) {
            $groups[$index]['label'] = self::labelFor($group['id']);
        }

        return array_values(array_filter($groups, static fn (array $group): bool => $group['items'] !== []));
    }

    /**
     * Único punto donde se descartan los ítems `null` (denegados) de una lista candidata.
     * `array_filter()` preserva las claves originales del array de entrada — sin el
     * `array_values()` de aquí, un grupo con 2+ ítems donde se filtra uno que no sea el
     * último produce un array con claves no contiguas (p. ej. `{0:..., 3:...}`), que
     * `json_encode()` serializa como objeto JSON en vez de array. El esquema Zod del
     * frontend (`items: z.array(...)`) rechazaría ese payload. Con un solo ítem candidato el
     * bug es invisible (la única clave sobreviviente ya es `0`), que es justo por qué se
     * coló sin detectarse en el grupo "compras" hasta la revisión de esta tarea — de ahí que
     * todo grupo pase por este único helper en vez de repetir el patrón inline.
     *
     * @param list<array<string,mixed>|null> $candidatos
     * @return list<array<string,mixed>>
     */
    private static function compactItems(array $candidatos): array
    {
        return array_values(array_filter($candidatos));
    }

    /** @return list<string> */
    private static function hiddenIdsFor(string $role, ?string $area): array
    {
        $hidden = self::OCULTOS_POR_ROL[$role] ?? [];

        // Defensa adicional además de la tabla histórica: un rol externo
        // (`RbacManager::hasCapability(..., 'isExternal')`) nunca ve los módulos
        // administrativos aunque la tabla de arriba cambie por error algún día.
        if (RbacManager::hasCapability($role, 'isExternal')) {
            $hidden = array_merge($hidden, ['profesionales', 'subcontratistas', 'plan-compras']);
        }

        if ($area === 'Pre-Construccion') {
            $hidden[] = 'plan-compras';
        }

        return array_values(array_unique($hidden));
    }

    /** @param list<string> $hidden */
    private static function linkItem(string $id, string $label, string $href, string $icon, array $hidden): ?array
    {
        if (in_array($id, $hidden, true)) {
            return null;
        }

        return ['id' => $id, 'label' => $label, 'href' => $href, 'icon' => $icon, 'action' => false];
    }

    private static function actionItem(string $id, string $label, string $icon): array
    {
        return ['id' => $id, 'label' => $label, 'href' => null, 'icon' => $icon, 'action' => true];
    }

    /** @param array{visible:bool,href:string|null} $bi */
    private static function biItem(array $bi): ?array
    {
        if (!$bi['visible'] || $bi['href'] === null) {
            return null;
        }

        return ['id' => 'control-tower', 'label' => 'Control Tower - Informes', 'href' => $bi['href'], 'icon' => 'chart', 'action' => false];
    }

    private static function labelFor(string $groupId): string
    {
        return match ($groupId) {
            'informacion' => 'Información',
            'obra' => 'Obra',
            'compras' => 'Compras',
            default => $groupId,
        };
    }
}
