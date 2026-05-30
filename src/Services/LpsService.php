<?php

namespace App\Services;

use Database;
use Throwable;
use PDO;

class LpsService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Calcula en vivo el Índice de Restricciones Habilitadas (ITR) de una actividad.
     * Retorna un arreglo con el porcentaje (0.00 a 1.00), el conteo de liberadas y el total aplicable.
     */
    public function calculateLiveITR(array $row): array
    {
        $campos = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'];
        $liberadas = 0;
        $aplicables = 0;

        foreach ($campos as $campo) {
            if (!isset($row[$campo])) {
                continue;
            }

            $val = trim((string) $row[$campo]);
            $upper = strtoupper($val);

            // Excluir N/A del cálculo
            if ($upper === 'N/A' || $upper === 'NA') {
                continue;
            }

            $aplicables++;
            $floatVal = (float) $val;
            if ($floatVal >= 0.999) {
                $liberadas++;
            }
        }

        $porcentaje = $aplicables > 0 ? round($liberadas / $aplicables, 4) : 1.0;

        return [
            'porcentaje' => $porcentaje,
            'liberadas' => $liberadas,
            'aplicables' => $aplicables,
        ];
    }

    /**
     * Registra de forma atómica una alerta de crisis en la tabla de escalamientos
     * y marca la actividad en consolidado/semanal como en crisis.
     */
    public function registerCrisisAlert(
        string $dbPrefix,
        int $proyectoId,
        int $semana,
        int $consecutivo,
        string $modulo,
        string $trigger,
    ): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // 1. Validar si ya existe una alerta activa para esta actividad en esta semana
            $stmt = $this->db->prepare(
                "SELECT id FROM `{$dbPrefix}_lps_escalamientos` 
                 WHERE proyecto_id = ? AND semana = ? AND consecutivo_en_programa = ? AND estado = 'Activo' LIMIT 1",
            );
            $stmt->execute([$proyectoId, $semana, $consecutivo]);
            $exists = $stmt->fetch();

            if (!$exists) {
                // Insertar nueva alerta
                $stmtInsert = $this->db->prepare(
                    "INSERT INTO `{$dbPrefix}_lps_escalamientos` 
                     (proyecto_id, semana, consecutivo_en_programa, modulo, trigger_origen, nivel_actual, estado) 
                     VALUES (?, ?, ?, ?, ?, 1, 'Activo')",
                );
                $stmtInsert->execute([$proyectoId, $semana, $consecutivo, $modulo, $trigger]);
            }

            // 2. Marcar en programa_consolidado
            $stmtConsolidado = $this->db->prepare(
                "UPDATE `{$dbPrefix}_programa_consolidado` 
                 SET alerta_crisis = 1 
                 WHERE Consecutivo_en_Programa = ? AND Semana = ?",
            );
            $stmtConsolidado->execute([$consecutivo, $semana]);

            // 3. Marcar en programacion_semanal
            $stmtSemanal = $this->db->prepare(
                "UPDATE `{$dbPrefix}_programacion_semanal` 
                 SET alerta_crisis = 1 
                 WHERE Consecutivo_En_Programa = ? AND Semana = ?",
            );
            $stmtSemanal->execute([$consecutivo, $semana]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al registrar alerta de crisis: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cron/Acción nocturna para escalar el nivel jerárquico de las alertas activas
     * que superen los 7 días sin mitigación/cierre.
     */
    public function escalarAlertasActivas(string $dbPrefix, int $proyectoId): int
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            return 0;
        }

        try {
            // Buscar alertas activas cuyo último cambio supere los 7 días
            $query = "SELECT id, nivel_actual, trigger_origen FROM `{$dbPrefix}_lps_escalamientos` 
                      WHERE proyecto_id = ? AND estado = 'Activo' AND nivel_actual < 5 
                        AND DATEDIFF(CURRENT_TIMESTAMP, COALESCE(fecha_ultimo_escalamiento, fecha_detonacion)) >= 7";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$proyectoId]);
            $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($alertas)) {
                return 0;
            }

            $escaladas = 0;
            $updateStmt = $this->db->prepare(
                "UPDATE `{$dbPrefix}_lps_escalamientos` 
                 SET nivel_actual = nivel_actual + 1, fecha_ultimo_escalamiento = CURRENT_TIMESTAMP 
                 WHERE id = ?",
            );

            foreach ($alertas as $alerta) {
                $updateStmt->execute([$alerta['id']]);
                $escaladas++;

                // Inyectar comentario de sistema informando del auto-escalamiento
                $nuevoNivel = $alerta['nivel_actual'] + 1;
                $rolesAIA = [1 => 'Residente', 2 => 'Director', 3 => 'Coordinador de Integración', 4 => 'Gerente de Construcción', 5 => 'Gerente General'];
                $rolNombre = $rolesAIA[$nuevoNivel] ?? 'Desconocido';

                $systemComment = "🚨 **Auto-Escalamiento del Sistema**: La inactividad de 7 días ha elevado esta alerta crítica al nivel {$nuevoNivel} ({$rolNombre}).";
                $this->addActivityComment($dbPrefix, $proyectoId, 0, 0, 0, $systemComment, null, $alerta['id']);
            }

            return $escaladas;
        } catch (Throwable $e) {
            error_log("Error al escalar alertas nocturnas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Registra un comentario o respuesta en la bitácora unificada de drawers.
     */
    public function addActivityComment(
        string $dbPrefix,
        int $proyectoId,
        int $consecutivo,
        int $semana,
        int $usuarioId,
        string $comentario,
        ?int $parentId = null,
        ?int $escalamientoId = null,
        ?array $menciones = null,
    ): int {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            return 0;
        }

        try {
            $mencionesJson = $menciones ? json_encode($menciones) : null;

            $query = "INSERT INTO `{$dbPrefix}_lps_drawer_comentarios` 
                      (proyecto_id, consecutivo_en_programa, semana, usuario_id, comentario, parent_id, escalamiento_id, menciones) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $proyectoId,
                $consecutivo,
                $semana,
                $usuarioId,
                $comentario,
                $parentId,
                $escalamientoId,
                $mencionesJson,
            ]);

            return (int) $this->db->lastInsertId();
        } catch (Throwable $e) {
            error_log("Error al agregar comentario en drawer: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene los comentarios estructurados en hilos jerárquicos (Slack-style)
     * para una actividad o alerta específica.
     */
    public function getActivityComments(string $dbPrefix, int $consecutivo, int $semana, ?int $escalamientoId = null): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            return [];
        }

        try {
            $params = [$consecutivo, $semana];
            $query = "SELECT c.*, u.nombre as autor_nombre, u.cargo as autor_cargo 
                      FROM `{$dbPrefix}_lps_drawer_comentarios` c
                      LEFT JOIN `general_usuarios` u ON c.usuario_id = u.Id 
                      WHERE c.consecutivo_en_programa = ? AND c.semana = ?";

            if ($escalamientoId !== null) {
                $query .= " AND (c.escalamiento_id = ? OR c.escalamiento_id IS NULL)";
                $params[] = $escalamientoId;
            }

            $query .= " ORDER BY c.created_at ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Estructurar en árbol (Slack style)
            $comentarios = [];
            $respuestas = [];

            foreach ($rows as $row) {
                $row['menciones'] = $row['menciones'] ? json_decode($row['menciones'], true) : null;
                if ($row['parent_id'] === null) {
                    $row['respuestas'] = [];
                    $comentarios[$row['id']] = $row;
                } else {
                    $respuestas[] = $row;
                }
            }

            foreach ($respuestas as $resp) {
                $pId = $resp['parent_id'];
                if (isset($comentarios[$pId])) {
                    $comentarios[$pId]['respuestas'][] = $resp;
                }
            }

            return array_values($comentarios);
        } catch (Throwable $e) {
            error_log("Error al obtener comentarios de bitácora: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cierra formalmente una alerta de crisis mitigada, inyectando justificación obligatoria.
     * Limpia las banderas de crisis en consolidado y semanal.
     */
    public function closeCrisisAlert(
        string $dbPrefix,
        int $alertaId,
        int $usuarioCierreId,
        string $justificacion,
    ): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            return false;
        }

        if (mb_strlen(trim($justificacion)) < 100) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // 1. Obtener detalles de la alerta
            $stmt = $this->db->prepare(
                "SELECT consecutivo_en_programa, semana, proyecto_id FROM `{$dbPrefix}_lps_escalamientos` 
                 WHERE id = ? AND estado = 'Activo'",
            );
            $stmt->execute([$alertaId]);
            $alerta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$alerta) {
                $this->db->rollBack();
                return false;
            }

            $consecutivo = $alerta['consecutivo_en_programa'];
            $semana = $alerta['semana'];

            // 2. Cerrar alerta en tabla de escalamientos
            $stmtClose = $this->db->prepare(
                "UPDATE `{$dbPrefix}_lps_escalamientos` 
                 SET estado = 'Cerrado', fecha_cierre = CURRENT_TIMESTAMP, 
                     usuario_cierre_id = ?, justificacion_cierre = ? 
                 WHERE id = ?",
            );
            $stmtClose->execute([$usuarioCierreId, trim($justificacion), $alertaId]);

            // 3. Remover bandera alerta_crisis en consolidado
            $stmtConsolidado = $this->db->prepare(
                "UPDATE `{$dbPrefix}_programa_consolidado` 
                 SET alerta_crisis = 0 
                 WHERE Consecutivo_en_Programa = ? AND Semana = ?",
            );
            $stmtConsolidado->execute([$consecutivo, $semana]);

            // 4. Remover bandera alerta_crisis en programacion_semanal
            $stmtSemanal = $this->db->prepare(
                "UPDATE `{$dbPrefix}_programacion_semanal` 
                 SET alerta_crisis = 0 
                 WHERE Consecutivo_En_Programa = ? AND Semana = ?",
            );
            $stmtSemanal->execute([$consecutivo, $semana]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al cerrar alerta de crisis: " . $e->getMessage());
            return false;
        }
    }

    public function getActiveCrisisByProject(string $dbPrefix, int $proyectoId): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            return [];
        }
        try {
            $q = "SELECT e.*, c.Actividad as actividad_nombre, c.Sub_Contratista as subcontratista,
                         c.Observaciones as restriccion_desc
                  FROM `{$dbPrefix}_lps_escalamientos` e
                  LEFT JOIN `{$dbPrefix}_programa_consolidado` c 
                    ON e.consecutivo_en_programa = c.Consecutivo_en_Programa AND e.semana = c.Semana
                  WHERE e.proyecto_id = ? AND e.estado = 'Activo'
                  ORDER BY e.nivel_actual DESC, e.fecha_detonacion ASC";
            $stmt = $this->db->prepare($q);
            $stmt->execute([$proyectoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Error crisis activas: " . $e->getMessage());
            return [];
        }
    }
}
