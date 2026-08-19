<?php

declare(strict_types=1);

namespace App\Services;

/**
 * La línea base contractual de un proyecto: la fecha contra la que se mide toda desviación.
 *
 * Por qué existe este servicio y no se sigue deduciendo al vuelo: hasta el 2026-08-19 la fecha
 * contractual del cronograma se derivaba del primer corte del programa y luego se cruzaba con las
 * actividades de la semana consultada. Al reprogramar y cambiar actividades, esa intersección
 * quedaba vacía y la fecha DESAPARECÍA — justo cuando más falta hace. La línea base es el patrón,
 * no un derivado de lo vigente.
 *
 * Es la misma fuente que ya consume el PDC (`Pdc\FlujoCajaService`), a propósito: cronograma y
 * presupuesto tienen que medir contra el mismo dato.
 */
final class LineaBaseContractualService
{
    private \Database $db;

    public function __construct(?\Database $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
    }

    /** @return array{inicio: string, fin: string}|null */
    public function declaradaDe(int $projectId): ?array
    {
        $fila = $this->db->query(
            'SELECT fechaInicioLineaBase AS inicio, fechaFinLineaBase AS fin
               FROM general_proyectos_procesos WHERE Id = ?',
            [$projectId],
        )->fetch(\PDO::FETCH_ASSOC);

        if ($fila === false || empty($fila['inicio']) || empty($fila['fin'])) {
            return null;
        }

        return ['inicio' => (string) $fila['inicio'], 'fin' => (string) $fila['fin']];
    }

    /**
     * La línea base que se deduciría del PRIMER corte registrado del programa.
     *
     * Solo se usa para sembrar lo que nadie declaró. No es equivalente a la contractual: es «cuándo
     * empezamos a registrar», no «qué se prometió». Por eso nunca pisa una declarada.
     *
     * @return array{inicio: string, fin: string}|null
     */
    public function deducidaDelPrimerCorte(int $projectId): ?array
    {
        // `programa_consolidado` es TABLA GLOBAL, aislada por `project_id`. No pasa por
        // TableResolver a propósito: con tablas globales ese resolutor devuelve el mismo nombre, y
        // nombrarla directa evita SQL dinámico nuevo, que es lo que AGENTS.md prohíbe.
        $primera = $this->db->query(
            'SELECT MIN(Semana) FROM programa_consolidado WHERE project_id = ?',
            [$projectId],
        )->fetchColumn();

        if ($primera === false || $primera === null) {
            return null;
        }

        $fila = $this->db->query(
            'SELECT MIN(Fecha_Inicio) AS inicio, MAX(Fecha_Fin) AS fin
               FROM programa_consolidado
              WHERE project_id = ? AND Semana = ?
                AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL',
            [$projectId, $primera],
        )->fetch(\PDO::FETCH_ASSOC);

        if ($fila === false || empty($fila['inicio']) || empty($fila['fin'])) {
            return null;
        }

        return ['inicio' => (string) $fila['inicio'], 'fin' => (string) $fila['fin']];
    }

    /**
     * Escribe la línea base deducida SOLO si el proyecto no tiene una declarada.
     *
     * Write-once por diseño: si alguien la corrigió a mano, manda la suya. Sin esa regla, cada
     * consolidación de semana reescribiría el patrón contra el que se mide, que es exactamente el
     * defecto que este servicio viene a cerrar.
     */
    public function sembrarSiFalta(int $projectId): bool
    {
        if ($this->declaradaDe($projectId) !== null) {
            return false;
        }

        $deducida = $this->deducidaDelPrimerCorte($projectId);
        if ($deducida === null) {
            return false;
        }

        $this->db->query(
            'UPDATE general_proyectos_procesos
                SET fechaInicioLineaBase = ?, fechaFinLineaBase = ?
              WHERE Id = ?
                AND (fechaInicioLineaBase IS NULL OR fechaFinLineaBase IS NULL)',
            [$deducida['inicio'], $deducida['fin'], $projectId],
        );

        return true;
    }
}
