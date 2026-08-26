<?php

namespace App\Services;

use App\Core\Lps\LpsService;

/**
 * Bitacora de ediciones manuales del avance (`Ejecutado`) en Programa General.
 *
 * Existe para que `WeeklyRealProgressCarryoverService` deje de adivinar en su caso ambiguo: sin
 * evidencia de quien escribio un valor, una edicion real del residente y un residuo del defecto
 * corregido el 2026-08-25 producen exactamente el mismo dato.
 *
 * Se usa en dos tiempos alrededor de la logica que ya existe, en vez de reemplazarla:
 * `GeneralApiController::update()` ejecuta hasta dos UPDATE por peticion —el que guarda lo
 * tecleado y el de herencia que lo reemplaza— y solo comparando el estado final contra el inicial
 * se registra lo que de verdad quedo.
 */
class PgAvanceEdicionManualService
{
    private const TOLERANCIA = 0.001;

    private $db;
    private LpsService $lpsService;

    public function __construct($db = null, ?LpsService $lpsService = null)
    {
        $this->db = $db ?: \Database::getInstance();
        $this->lpsService = $lpsService ?: new LpsService();
    }

    /**
     * Lee el estado del que se parte, antes de que corra la logica del controlador.
     *
     * @return array{Ejecutado: ?float, programaAnteriorAsociar: ?string}
     */
    public function capturarAvancePrevio(int $projectId, int $semana, int $uniqueId): array
    {
        $fila = $this->db->queryWithProject(
            "SELECT Ejecutado, programaAnteriorAsociar FROM programa_consolidado
             WHERE Semana = ? AND unique_id = ? LIMIT 1",
            [$semana, $uniqueId],
            $projectId,
        )->fetch();

        return [
            'Ejecutado' => $this->lpsService->toFloat($fila['Ejecutado'] ?? null, null),
            'programaAnteriorAsociar' => $fila['programaAnteriorAsociar'] ?? null,
        ];
    }

    /**
     * Compara el estado final contra el capturado y firma si corresponde.
     *
     * `$herenciaAplicada` lo informa el controlador: cierto solo cuando el UPDATE de herencia
     * se ejecuto de verdad, no cuando la casilla estaba marcada nada mas. Cuando la herencia
     * corrio pero la asociacion no cambio, el reemplazo fue un efecto secundario —el residente
     * venia a corregir otra cosa— y no se firma.
     *
     * @param array{Ejecutado: ?float, programaAnteriorAsociar: ?string} $previo
     */
    public function registrarSiCambio(
        int $projectId,
        int $semana,
        int $uniqueId,
        array $previo,
        string $usuario,
        bool $herenciaAplicada,
    ): bool {
        $actual = $this->capturarAvancePrevio($projectId, $semana, $uniqueId);

        $antes = $previo['Ejecutado'];
        $despues = $actual['Ejecutado'];

        if ($antes === null && $despues === null) {
            return false;
        }
        if ($antes !== null && $despues !== null && abs($antes - $despues) <= self::TOLERANCIA) {
            return false;
        }

        if ($herenciaAplicada) {
            $asociacionCambio = trim((string) ($previo['programaAnteriorAsociar'] ?? ''))
                !== trim((string) ($actual['programaAnteriorAsociar'] ?? ''));
            if (!$asociacionCambio) {
                return false;
            }
        }

        try {
            $this->db->queryWithProject(
                "INSERT INTO pg_avance_edicion_manual
                    (project_id, Semana, unique_id, valor_anterior, valor_nuevo, usuario)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$projectId, $semana, $uniqueId, $antes, $despues, $usuario],
                $projectId,
            );
        } catch (\Throwable $e) {
            // Una edicion sin firma es justo lo que este servicio existe para evitar, asi que el
            // fallo no se traga: queda en el log para que se note y se pueda investigar.
            error_log(sprintf(
                '[PgAvanceEdicionManual] No se pudo registrar la edicion | proyecto=%d semana=%d actividad=%d usuario=%s | %s',
                $projectId, $semana, $uniqueId, $usuario, $e->getMessage(),
            ));
            return false;
        }

        return true;
    }
}
