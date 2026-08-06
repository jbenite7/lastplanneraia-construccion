<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Regla de negocio N-1: sin Responsable AIA asignado no se gestionan restricciones.
 *
 * El cliente ya la aplica en `public/js/modules/programacion_intermedia/hot.js`
 * (`hasAssignedValue(rowData.Responsable_AIA, PI_CREATE_PROF)` → `hasResponsable`
 * → `readOnly` + candado). Esta clase es la MISMA regla del lado servidor, para
 * que ninguna vía (lote compartido o POST directo) escriba restricciones donde
 * la UI muestra candado.
 *
 * «Sin asignar» significa exactamente lo mismo que en el cliente: cadena vacía,
 * solo espacios, o el placeholder «➕ Crear Profesional...».
 */
final class ResponsableAiaPolicy
{
    /** Placeholder del dropdown de profesionales; no es un responsable real. */
    public const CREATE_PLACEHOLDER = '➕ Crear Profesional...';

    public const MENSAJE_FALTA_RESPONSABLE =
        'Falta el Responsable AIA: no se pueden gestionar restricciones de una actividad sin responsable asignado. Asigne primero el Responsable AIA.';

    /**
     * ¿El valor cuenta como Responsable AIA asignado?
     */
    public static function hasAssigned($value): bool
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' && $normalized !== self::CREATE_PLACEHOLDER;
    }

    /**
     * Mensaje de error para un lote, nombrando qué falta y en cuántas actividades.
     *
     * @param array<int,string> $consecutivos consecutivos sin responsable
     */
    public static function mensajeLote(array $consecutivos): string
    {
        $total = count($consecutivos);
        $muestra = array_slice($consecutivos, 0, 10);
        $listado = implode(', ', $muestra);
        if ($total > count($muestra)) {
            $listado .= ', …';
        }

        return self::MENSAJE_FALTA_RESPONSABLE
            . " Actividades sin Responsable AIA ({$total}): {$listado}.";
    }
}
