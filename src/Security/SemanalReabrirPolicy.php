<?php

declare(strict_types=1);

namespace App\Security;

use DateTimeImmutable;
use Throwable;

/**
 * Regla de autorización para reabrir una semana en Programación Semanal.
 *
 * Regla dada por el usuario el 2026-08-10, verbatim:
 *   "Reabren Admin y Director, siempre. El Residente solo hasta el fin del día de inicio
 *   de la semana. Cualquier otro rol, nunca."
 *
 * Función pura para poder probarla sin bootstrap HTTP. El rol que recibe ya debe venir
 * normalizado a un rol canónico (ver RbacService::normalizeRole()); no vuelve a normalizar
 * aquí para no duplicar esa responsabilidad.
 *
 * Si la fecha de inicio de semana no se puede resolver, deniega — misma decisión que unifica
 * la Task 4 para todo el candado, aplicada aquí primero: un candado que no sabe, cierra.
 */
final class SemanalReabrirPolicy
{
    private const ROLES_SIEMPRE_PERMITIDOS = ['A', 'D'];
    private const ROL_RESIDENTE = 'R';

    /**
     * @param string $role rol canónico ya normalizado (A, D, R, DCV, OT, G, S, SG, C, V)
     * @param string|null $fechaInicioSemana fecha de inicio de la semana (formato parseable por strtotime), o null si no se pudo resolver
     * @param DateTimeImmutable|null $ahora momento a evaluar; por defecto "now"
     */
    public static function allows(string $role, ?string $fechaInicioSemana, ?DateTimeImmutable $ahora = null): bool
    {
        if (in_array($role, self::ROLES_SIEMPRE_PERMITIDOS, true)) {
            return true;
        }

        if ($role !== self::ROL_RESIDENTE) {
            return false;
        }

        $finDeVentana = self::resolverFinDeVentana($fechaInicioSemana);
        if ($finDeVentana === null) {
            return false;
        }

        $ahora ??= new DateTimeImmutable('now');

        return $ahora <= $finDeVentana;
    }

    private static function resolverFinDeVentana(?string $fechaInicioSemana): ?DateTimeImmutable
    {
        if ($fechaInicioSemana === null || trim($fechaInicioSemana) === '') {
            return null;
        }

        try {
            $inicio = new DateTimeImmutable($fechaInicioSemana);
        } catch (Throwable $e) {
            return null;
        }

        return $inicio->setTime(23, 59, 59);
    }
}
