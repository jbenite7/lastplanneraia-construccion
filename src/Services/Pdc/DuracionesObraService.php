<?php

namespace App\Services\Pdc;

/**
 * Las correcciones de duración que UNA OBRA hace sobre el catálogo de la empresa.
 *
 * `general_dias_procesos_contratacion` es de la empresa y lo comparten todas las obras: cambiar un
 * número allí mueve las fechas de todas. Esta tabla guarda la excepción de una obra, y la lectura
 * del plan la antepone al catálogo. Cero filas para una obra = manda el catálogo, igual que antes
 * de que esto existiera.
 *
 * Aquí el nombre de columna viaja como VALOR parametrizado —es un dato de la tabla, no un nombre de
 * columna del SQL—, así que la lista blanca no lo salva de una inyección: lo salva de escribir
 * basura. Se valida igual contra `PasosContratacionService::columnasLegacy()` porque es el mismo
 * vocabulario que `$selectCols` sí interpola al leer en `PlanFechasService`: una fila con una
 * columna que no esté en esa lista sería un dato que la lectura nunca podría usar.
 */
class DuracionesObraService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * Las correcciones de una obra, listas para consultar por paquete.
     *
     * Se carga UNA VEZ POR OBRA y no dentro del bucle de paquetes: la consulta de `proyectar()`
     * corre por paquete, así que meterlo ahí convertiría un plan de cien paquetes en doscientas
     * consultas.
     *
     * @return array<int, array<string,int>> duracionRef => [columna => días]
     */
    public function deProyecto(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT duracion_ref, columna, dias FROM pdc_proyecto_duraciones WHERE project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['duracion_ref']][(string) $r['columna']] = (int) $r['dias'];
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $dias columna legacy => días
     * @return array{ok:bool, code?:string, mensaje?:string}
     */
    public function guardar(int $projectId, int $duracionRef, array $dias, ?int $usuario): array
    {
        $legales = PasosContratacionService::columnasLegacy();
        // Se valida TODO antes de escribir nada: una corrección a medias dejaría la obra con unos
        // pasos movidos y otros no, y sin forma de saber cuáles.
        foreach ($dias as $col => $v) {
            if (!in_array($col, $legales, true)) {
                return ['ok' => false, 'code' => 'COLUMNA_INVALIDA', 'mensaje' => "«{$col}» no es un paso del proceso."];
            }
            $n = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($n === false) {
                return ['ok' => false, 'code' => 'DIAS_INVALIDOS', 'mensaje' => 'Los días deben ser un entero de cero o más.'];
            }
        }
        foreach ($dias as $col => $v) {
            $this->db->query(
                'INSERT INTO pdc_proyecto_duraciones (project_id, duracion_ref, columna, dias, actualizado_por)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE dias = VALUES(dias), actualizado_por = VALUES(actualizado_por)',
                [$projectId, $duracionRef, (string) $col, (int) $v, $usuario],
            );
        }
        return ['ok' => true];
    }

    /**
     * @param list<string> $columnas
     * @return array{ok:bool, code?:string, mensaje?:string}
     */
    public function borrar(int $projectId, int $duracionRef, array $columnas): array
    {
        $legales = PasosContratacionService::columnasLegacy();
        foreach ($columnas as $col) {
            if (!in_array($col, $legales, true)) {
                return ['ok' => false, 'code' => 'COLUMNA_INVALIDA', 'mensaje' => "«{$col}» no es un paso del proceso."];
            }
        }
        if ($columnas === []) {
            return ['ok' => true];
        }
        $marcas = implode(', ', array_fill(0, count($columnas), '?'));
        $this->db->query(
            "DELETE FROM pdc_proyecto_duraciones
             WHERE project_id = ? AND duracion_ref = ? AND columna IN ({$marcas})",
            array_merge([$projectId, $duracionRef], $columnas),
        );
        return ['ok' => true];
    }
}
