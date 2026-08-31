<?php

declare(strict_types=1);

namespace App\Services\Shell;

use App\Contracts\Shell\WeekAdministrationRepository;
use App\Services\LineaBaseContractualService;
use App\Services\ProgramaConsolidadoNormalizationService;
use App\Services\WeeklyRealProgressCarryoverService;
use Database;
use TableResolver;

/**
 * Implementación real de `WeekAdministrationRepository`: cada método traslada, sin reescribir su
 * lógica, un bloque de SQL que hoy vive en `src/Legacy/nueva_semana.php`,
 * `src/Legacy/eliminar_semana.php` y `src/Legacy/modificar_sem_estado.php`. La razón de moverlo
 * en vez de reinterpretarlo: sin poder correr DML de verificación en este plan (T01, Tarea 5), el
 * riesgo más bajo es reubicar consultas ya probadas en producción, no reescribirlas.
 *
 * Nunca se ejercita con datos reales dentro de esta tarea — la evidencia de Tarea 5 son los tests
 * puros de `WeekAdministrationService` contra el fake. Un escenario de persistencia real necesita
 * autorización separada (restricción del plan T01, "no DDL/DML como evidencia").
 */
final class DatabaseWeekAdministrationRepository implements WeekAdministrationRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function contarSemanasActivas(int $projectId): int
    {
        $t = TableResolver::resolve($projectId, 'semanas_activas');
        $stmt = $this->db->queryWithProject("SELECT COUNT(*) AS conteo FROM {$t} WHERE project_id = ?", [$projectId], $projectId);

        return (int) ($stmt->fetch()['conteo'] ?? 0);
    }

    public function semanaConfirmada(int $projectId, int $semana): bool
    {
        $t = TableResolver::resolve($projectId, 'semanas_activas');
        $stmt = $this->db->queryWithProject(
            "SELECT Semanal_Confirmada FROM {$t} WHERE project_id = ? AND Semana = ?",
            [$projectId, $semana],
            $projectId,
        );

        return (int) ($stmt->fetch()['Semanal_Confirmada'] ?? 0) === 1;
    }

    public function pendientesCic(int $projectId, int $semanaReferencia): string|int
    {
        $t = TableResolver::resolve($projectId, 'cic');

        $stmt = $this->db->queryWithProject(
            "SELECT COUNT(*) AS conteo FROM {$t}
             WHERE project_id = ? AND Semana <= ? AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'",
            [$projectId, $semanaReferencia],
            $projectId,
        );
        if ((int) ($stmt->fetch()['conteo'] ?? 0) === 0) {
            return 0;
        }

        $query2 = "SELECT COUNT(*) AS conteo, GROUP_CONCAT(tabla.subcontratista SEPARATOR ', ') AS faltaCalificar
            FROM (
                SELECT c.Id, c.Semana, stats.semanasEnProyecto, c.subcontratista,
                       c.Calidad, c.GSA, c.SST, c.ADM
                FROM {$t} c
                INNER JOIN (
                    SELECT subcontratista, COUNT(*) AS semanasEnProyecto, MAX(Semana) AS maxSemana
                    FROM {$t}
                    WHERE project_id = ? AND Semana <= ?
                      AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'
                    GROUP BY subcontratista
                ) stats
                  ON stats.subcontratista = c.subcontratista AND stats.maxSemana = c.Semana
                WHERE c.project_id = ?
                  AND c.tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'
            ) AS tabla
            WHERE MOD(tabla.semanasEnProyecto, 8) = 0
              AND (tabla.Calidad = 'NR' OR tabla.GSA = 'NR' OR tabla.SST = 'NR' OR tabla.ADM = 'NR')";

        try {
            $stmt2 = $this->db->queryWithProject($query2, [$projectId, $semanaReferencia, $projectId], $projectId);
            $data = $stmt2->fetch();
            $conteoFaltaCalificar = (int) ($data['conteo'] ?? 0);

            if ($conteoFaltaCalificar > 1) {
                return ' de los Subcontratistas ' . ($data['faltaCalificar'] ?? '');
            }
            if ($conteoFaltaCalificar === 1) {
                return ' del Subcontratista ' . ($data['faltaCalificar'] ?? '');
            }

            return 0;
        } catch (\Throwable $e) {
            error_log('Error CIC (WeekAdministrationRepository): ' . $e->getMessage());

            return 0;
        }
    }

    public function programaMaestroTieneActividades(int $projectId): bool
    {
        $t = TableResolver::resolve($projectId, 'programa');
        $stmt = $this->db->queryWithProject("SELECT COUNT(*) AS c FROM {$t} WHERE project_id = ?", [$projectId], $projectId);

        return (int) ($stmt->fetch()['c'] ?? 0) > 0;
    }

    public function insertarSemanaActiva(int $projectId, int $semana, string $fechaInicio, string $fechaFin): void
    {
        $t = TableResolver::resolve($projectId, 'semanas_activas');
        $fechaCreacion = date('Y-m-d');
        $nextId = (int) $this->db
            ->queryWithProject("SELECT COALESCE(MAX(Id), 0) + 1 FROM {$t} WHERE project_id = ?", [$projectId], $projectId)
            ->fetchColumn();

        $this->db->query(
            "INSERT INTO {$t} (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaCreacionSemana) VALUES (?, ?, ?, ?, ?, ?)",
            [$projectId, $nextId, $semana, $fechaInicio, $fechaFin, $fechaCreacion],
        );
    }

    public function eliminarSemanaActivaRecienCreada(int $projectId, int $semana): void
    {
        $t = TableResolver::resolve($projectId, 'semanas_activas');
        $this->db->queryWithProject("DELETE FROM {$t} WHERE project_id = ? AND Semana = ?", [$projectId, $semana], $projectId);
    }

    public function copiarProgramaMaestroASemana(int $projectId, int $semana, bool $preConstruccion): void
    {
        $tPrograma = TableResolver::resolve($projectId, 'programa');
        $tConsolidado = TableResolver::resolve($projectId, 'programa_consolidado');
        $baseId = $this->maxRowIdConsolidado($projectId, $tConsolidado);

        $sql = $preConstruccion
            ? "INSERT INTO {$tConsolidado}(project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana)
                SELECT ?, ? + ROW_NUMBER() OVER (ORDER BY unique_id, Id), ? + ROW_NUMBER() OVER (ORDER BY unique_id, Id), ?, unique_id, unique_id, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, 0, IFNULL(Ejecutado, 0), Estado, IFNULL(Semanas_Inicio, 0), IFNULL(Estado_Restricciones, '0'), IFNULL(restriccion_pc_1, '0%'), IFNULL(restriccion_pc_2, '0%'), IFNULL(restriccion_pc_3, '0%'), IFNULL(restriccion_pc_4, '0%'), Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, IFNULL(Ejecutado, 0) FROM {$tPrograma} WHERE project_id = ?"
            : "INSERT INTO {$tConsolidado}(project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana)
                SELECT ?, ? + ROW_NUMBER() OVER (ORDER BY unique_id, Id), ? + ROW_NUMBER() OVER (ORDER BY unique_id, Id), ?, unique_id, unique_id, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, 0, IFNULL(Ejecutado, 0), Estado, IFNULL(Semanas_Inicio, 0), IFNULL(Estado_Restricciones, '0'), IFNULL(D_y_E, '0'), IFNULL(Materiales, '0'), IFNULL(MdeO, '0'), IFNULL(Equipos, '0'), IFNULL(Predecesora, '0'), IFNULL(Pdto_Cons, '0'), IFNULL(Modelo, '0'), Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, IFNULL(Ejecutado, 0) FROM {$tPrograma} WHERE project_id = ?";

        $this->db->query($sql, [$projectId, $baseId, $baseId, $semana, $projectId]);
    }

    public function maxSemanaConsolidada(int $projectId): int
    {
        $t = TableResolver::resolve($projectId, 'programa_consolidado');
        $stmt = $this->db->queryWithProject("SELECT MAX(Semana) AS max_semana FROM {$t} WHERE project_id = ?", [$projectId], $projectId);

        return (int) ($stmt->fetch()['max_semana'] ?? 0);
    }

    public function eliminarSemanasConsolidadasSuperioresA(int $projectId, int $semana): void
    {
        $t = TableResolver::resolve($projectId, 'programa_consolidado');
        $this->db->queryWithProject("DELETE FROM {$t} WHERE project_id = ? AND Semana > ?", [$projectId, $semana], $projectId);
        error_log("[WeekAdministrationRepository] Limpieza de huérfanos: eliminadas semanas > {$semana} en programa_consolidado (proyecto {$projectId})");
    }

    public function fusionarSemanaRecreada(int $projectId, int $semanaAnterior, int $semanaNueva, bool $preConstruccion): void
    {
        $t = TableResolver::resolve($projectId, 'programa_consolidado');
        $tSa = TableResolver::resolve($projectId, 'semanas_activas');

        $campos = $preConstruccion
            ? 'Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Activa, Ejecutado_Siguiente_Semana, codigo_actividad, medir_productividad, cantidad_ppto, unidad'
            : 'Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Activa, Ejecutado_Siguiente_Semana, codigo_actividad, medir_productividad, cantidad_ppto, unidad';
        $ifnullCheck = $preConstruccion
            ? ['restriccion_pc_1', 'restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4', 'Ejecutado', 'Estado_Restricciones']
            : ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo', 'Ejecutado', 'Estado_Restricciones'];

        $setClause = '';
        foreach (explode(',', $campos) as $campoRaw) {
            $campo = trim($campoRaw);
            $setClause .= in_array($campo, $ifnullCheck, true)
                ? "dest.$campo = IFNULL(src.$campo, 0), "
                : "dest.$campo = src.$campo, ";
        }
        $setClause = rtrim($setClause, ', ');

        $sql = "UPDATE {$t} AS dest
                INNER JOIN (SELECT * FROM {$t} WHERE project_id = ? AND Semana = ? AND Titulo = 0) AS src
                ON src.project_id = dest.project_id
                AND REPLACE(REPLACE(dest.programaAnteriorAsociar, '<b>', ''), '</b>', '') = REPLACE(REPLACE(src.Actividad, '<b>', ''), '</b>', '')
                SET $setClause
                WHERE dest.project_id = ? AND dest.Semana = ?";
        $this->db->query($sql, [$projectId, $semanaAnterior, $projectId, $semanaNueva]);

        $sqlReprog = "UPDATE {$tSa} SET reprogramacion=1, diferenciaEstructuraCron=(
                SELECT COUNT(*) FROM {$t} WHERE project_id = ? AND Semana=? AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Titulo != 1 AND (Ejecutado IS NULL OR Estado_Restricciones IS NULL OR programaAnteriorAsociar IS NOT NULL)
            ) WHERE project_id = ? AND Semana=?";
        $this->db->queryWithProject($sqlReprog, [$projectId, $semanaNueva, $projectId, $semanaNueva], $projectId);
    }

    public function copiarSemanaConsolidadaHaciaAdelante(int $projectId, int $semanaOrigen, int $semanaDestino, bool $preConstruccion): void
    {
        $t = TableResolver::resolve($projectId, 'programa_consolidado');

        $cols = $preConstruccion
            ? 'unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, unidad, cantidad_ppto, codigo_actividad, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana, programaAnteriorAsociar'
            : 'unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, unidad, cantidad_ppto, codigo_actividad, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana, programaAnteriorAsociar';
        $ifnullCols = $preConstruccion
            ? ['restriccion_pc_1', 'restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4', 'Ejecutado', 'Estado_Restricciones']
            : ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo', 'Ejecutado', 'Estado_Restricciones'];

        $selectCols = implode(', ', array_map(
            static fn (string $c) => in_array($c, $ifnullCols, true) ? "IFNULL($c, 0)" : $c,
            array_map('trim', explode(',', $cols)),
        ));

        $baseId = $this->maxRowIdConsolidado($projectId, $t);
        $sql = "INSERT INTO {$t}(project_id, row_id, Consecutivo, Semana, $cols)
                SELECT ?, ? + ROW_NUMBER() OVER (ORDER BY COALESCE(row_id, Consecutivo), unique_id, Id), ? + ROW_NUMBER() OVER (ORDER BY COALESCE(row_id, Consecutivo), unique_id, Id), ?, $selectCols FROM {$t} WHERE project_id = ? AND Semana = ?";
        $this->db->query($sql, [$projectId, $baseId, $baseId, $semanaDestino, $projectId, $semanaOrigen]);
    }

    public function normalizarCapitulos(int $projectId, int $semana): void
    {
        (new ProgramaConsolidadoNormalizationService($this->db))->normalizeChapters($this->resolvePrefix($projectId), $semana);
    }

    public function resetearCapitulosSemana(int $projectId, int $semana, bool $preConstruccion): void
    {
        $t = TableResolver::resolve($projectId, 'programa_consolidado');
        $sql = $preConstruccion
            ? "UPDATE {$t} SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, restriccion_pc_1='0%', restriccion_pc_2='0%', restriccion_pc_3='0%', restriccion_pc_4='0%', Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0 WHERE project_id = ? AND Semana = ? AND Titulo=1"
            : "UPDATE {$t} SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, D_y_E='0', Materiales='0', MdeO='0', Equipos='0', Predecesora='0', Pdto_Cons='0', Modelo='0', Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0 WHERE project_id = ? AND Semana = ? AND Titulo=1";
        $this->db->queryWithProject($sql, [$projectId, $semana], $projectId);
    }

    public function resetearFilasOperativasNulas(int $projectId, int $semana, bool $preConstruccion): void
    {
        $t = TableResolver::resolve($projectId, 'programa_consolidado');
        $sql = $preConstruccion
            ? "UPDATE {$t} SET Ejecutado = 0, Estado_Restricciones = '0', restriccion_pc_1 = '0%', restriccion_pc_2 = '0%', restriccion_pc_3 = '0%', restriccion_pc_4 = '0%' WHERE project_id = ? AND Ejecutado IS NULL AND Semana = ? AND Titulo=0"
            : "UPDATE {$t} SET Ejecutado = 0, Estado_Restricciones = '0', D_y_E = '0', Materiales = '0', MdeO = '0', Equipos = '0', Predecesora = '0', Pdto_Cons = '0', Modelo = '0' WHERE project_id = ? AND Ejecutado IS NULL AND Semana = ? AND Titulo=0";
        $this->db->queryWithProject($sql, [$projectId, $semana], $projectId);
    }

    public function sincronizarArrastre(int $projectId, int $semanaOrigen, int $semanaDestino): void
    {
        (new WeeklyRealProgressCarryoverService($this->db))->syncWeek($this->resolvePrefix($projectId), $semanaOrigen, $semanaDestino);
    }

    public function sembrarLineaBaseSiFalta(int $projectId): void
    {
        (new LineaBaseContractualService($this->db))->sembrarSiFalta($projectId);
    }

    public function finalizarEstadoSemana(int $projectId, int $semana, string $fechaInicio, string $fechaFin): void
    {
        require_once dirname(__DIR__, 2) . '/Legacy/estado_programa_general.php';

        $t = TableResolver::resolve($projectId, 'programa_consolidado');
        $tSa = TableResolver::resolve($projectId, 'semanas_activas');

        $stmt = $this->db->queryWithProject(
            "SELECT unique_id, unique_id AS Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin FROM {$t} WHERE project_id = ? AND Semana = ?",
            [$projectId, $semana],
            $projectId,
        );

        foreach ($stmt->fetchAll() as $fila) {
            // @phpstan-ignore-next-line función definida en estado_programa_general.php
            $estado = pg_calculate_status(
                $fila['Titulo'] ?? 0,
                $fila['Ejecutado'] ?? 0,
                $fila['Fecha_Inicio'] ?? null,
                $fila['Fecha_Fin'] ?? null,
                $fechaInicio,
                $fechaFin,
            );

            $this->db->queryWithProject(
                'UPDATE ' . $t . ' SET Estado = ? WHERE project_id = ? AND unique_id = ? AND Semana = ?',
                [$estado, $projectId, $fila['unique_id'], $semana],
                $projectId,
            );
        }

        $this->db->queryWithProject("UPDATE {$t} SET Ruta_Critica = 0 WHERE project_id = ? AND Titulo = 1 AND Semana = ?", [$projectId, $semana], $projectId);
        $this->normalizarCapitulos($projectId, $semana);
        $this->db->queryWithProject(
            "UPDATE {$t} SET Semanas_Inicio = 0 WHERE project_id = ? AND Fecha_Inicio IS NULL AND Fecha_Fin IS NULL AND Titulo = 1 AND Semana = ?",
            [$projectId, $semana],
            $projectId,
        );

        // `modificar_sem_estado.php` releía `Fecha_Fin_Sem` de `semanas_activas` solo cuando el
        // llamador no la traía; aquí siempre la tenemos (viene del comando), así que ese fallback
        // deja de ser necesario — se preserva la columna sin usarla para no romper otros lectores.
        unset($tSa);
    }

    public function semanaMaxima(int $projectId): int
    {
        $t = TableResolver::resolve($projectId, 'semanas_activas');
        $stmt = $this->db->queryWithProject("SELECT MAX(Semana) AS maxSemana FROM {$t} WHERE project_id = ?", [$projectId], $projectId);

        return (int) ($stmt->fetch()['maxSemana'] ?? 0);
    }

    public function eliminarCascada(int $projectId, int $semana): void
    {
        foreach (['semanas_activas', 'programa_consolidado', 'programacion_semanal', 'cic'] as $tipo) {
            $t = TableResolver::resolve($projectId, $tipo);
            $this->db->query("DELETE FROM {$t} WHERE project_id = ? AND Semana >= ?", [$projectId, $semana]);
        }
    }

    public function registrarActividad(string $accion, string $detalle): void
    {
        $this->db->logActivity('Sistema', $accion, $detalle);
    }

    public function semanasActivas(int $projectId): array
    {
        $t = TableResolver::resolve($projectId, 'semanas_activas');
        $stmt = $this->db->queryWithProject(
            'SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM ' . $t . ' WHERE project_id = ? ORDER BY Semana ASC',
            [$projectId],
            $projectId,
        );

        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[] = [
                'number' => (int) $fila['Semana'],
                'startsOn' => (string) $fila['Fecha_Inicio_Sem'],
                'endsOn' => (string) $fila['Fecha_Fin_Sem'],
            ];
        }

        return $resultado;
    }

    private function maxRowIdConsolidado(int $projectId, string $tablaResuelta): int
    {
        return (int) $this->db
            ->queryWithProject("SELECT COALESCE(MAX(row_id), MAX(Consecutivo), 0) FROM {$tablaResuelta} WHERE project_id = ?", [$projectId], $projectId)
            ->fetchColumn();
    }

    private function resolvePrefix(int $projectId): string
    {
        $stmt = $this->db->query(
            'SELECT Base_de_Datos FROM general_proyectos_procesos WHERE Id = ? AND Activo = 1',
            [$projectId],
        );
        $fila = $stmt->fetch();
        if (!$fila) {
            throw new \InvalidArgumentException("Proyecto no encontrado o inactivo: {$projectId}");
        }

        return (string) $fila['Base_de_Datos'];
    }
}
