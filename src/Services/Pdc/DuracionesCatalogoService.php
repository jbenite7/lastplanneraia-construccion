<?php

namespace App\Services\Pdc;

/**
 * A4.1 · diferido nº 4 — las duraciones del catálogo legacy, editables desde el PDC v2.
 *
 * Hasta ahora, cambiar cuántos días dura «recibo de propuestas» exigía entrar a la base de datos.
 * Es un número que mueve las fechas de toda la obra, así que tenerlo solo al alcance de quien sabe
 * escribir SQL no era una salvaguarda: era un cuello de botella.
 *
 * `general_dias_procesos_contratacion` es de la EMPRESA, no de una obra: cambiar un número aquí
 * mueve las fechas de todas las obras cuyos paquetes apunten a esa fila. Por eso el permiso que lo
 * protege es `lps.paquetes_contratacion.reglas` —el de reglas globales— y no el de editar el plan,
 * y por eso `deProyecto()` solo ofrece las filas que la obra usa de verdad: desde la pantalla de una
 * obra no se edita a ciegas un catálogo entero.
 *
 * El módulo `/contratos` ya escribe en esta tabla por su cuenta
 * (`ContratosApiController::guardarDuracionesContratacion()`), con clave
 * `(paqueteContratacion, tipoPaquete)` y el permiso `lps.contratos.editar`. Aquí se escribe por
 * `id`, que es como el PDC v2 la referencia vía `general_paquetes_contratacion.duracion_ref`, y
 * aquel camino no se toca.
 */
class DuracionesCatalogoService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * Las filas del catálogo que los paquetes de ESTA obra usan, con cuántos las usan.
     *
     * El conteo no es decorativo: es lo que permite que la pantalla diga a cuántos paquetes va a
     * mover las fechas antes de que alguien toque un número.
     *
     * @return list<array{duracionRef:int,paqueteContratacion:string,tipoPaquete:string,dias:array<string,?int>,paquetesQueLaUsan:int}>
     */
    public function deProyecto(int $projectId): array
    {
        // Los nombres de columna se interpolan porque son nombres de columna, no valores. Salen de
        // `columnasLegacy()`, que se deriva de la constante `PlanFechasService::PASOS` y no de la
        // base: no hay entrada de usuario en este string.
        $cols = PasosContratacionService::columnasLegacy();
        $select = implode(', ', array_map(static fn (string $c): string => 'd.' . $c, $cols));
        $rows = $this->db->query(
            "SELECT d.id, d.paqueteContratacion, d.tipoPaquete, {$select}, COUNT(DISTINCT p.id) AS usos
             FROM pdc_paquete_frente f
             JOIN general_paquetes_contratacion p ON p.id = f.paquete_id
             JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
             WHERE f.project_id = ?
             GROUP BY d.id, d.paqueteContratacion, d.tipoPaquete, {$select}
             ORDER BY d.paqueteContratacion",
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $dias = [];
            foreach ($cols as $c) {
                $dias[$c] = $r[$c] === null ? null : (int) $r[$c];
            }
            $out[] = [
                'duracionRef' => (int) $r['id'],
                'paqueteContratacion' => (string) $r['paqueteContratacion'],
                'tipoPaquete' => (string) $r['tipoPaquete'],
                'dias' => $dias,
                'paquetesQueLaUsan' => (int) $r['usos'],
            ];
        }
        return $out;
    }

    /**
     * Cambia una o varias de las siete duraciones de una fila del catálogo.
     *
     * Los nombres de columna se validan contra `PasosContratacionService::columnasLegacy()` —la
     * misma lista blanca que A4.1— porque van interpolados en el SQL: son nombres de columna y no
     * pueden ir como parámetro. Sin ese filtro esto sería una inyección.
     *
     * @param array<string, mixed> $dias columna legacy → días
     * @return array{ok:bool,code?:string,mensaje?:string}
     */
    public function actualizar(int $duracionRef, array $dias, string $usuario): array
    {
        if ($dias === []) {
            return ['ok' => false, 'code' => 'SIN_CAMBIOS', 'mensaje' => 'No se recibió ninguna duración que cambiar.'];
        }

        // La existencia se comprueba ANTES del UPDATE y no por su `rowCount()`: este repo no activa
        // PDO::MYSQL_ATTR_FOUND_ROWS (ver Database.php), así que MySQL reporta filas MODIFICADAS y
        // guardar el mismo número dos veces daría 0 — indistinguible de «esa fila no existe».
        $existe = (int) $this->db->query(
            'SELECT COUNT(*) FROM general_dias_procesos_contratacion WHERE id = ?',
            [$duracionRef],
        )->fetchColumn();
        if ($existe === 0) {
            return ['ok' => false, 'code' => 'DURACION_NO_EXISTE', 'mensaje' => 'Esa fila del catálogo ya no existe.'];
        }

        $legales = PasosContratacionService::columnasLegacy();
        $sets = [];
        $args = [];
        foreach ($dias as $col => $valor) {
            if (!in_array((string) $col, $legales, true)) {
                return ['ok' => false, 'code' => 'COLUMNA_DESCONOCIDA', 'mensaje' => "«{$col}» no es una duración del catálogo."];
            }
            $n = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($n === false) {
                return [
                    'ok' => false, 'code' => 'DIAS_INVALIDOS',
                    'mensaje' => 'Los días tienen que ser un número entero de cero para arriba.',
                ];
            }
            $sets[] = "{$col} = ?";
            $args[] = $n;
        }
        // Todas las columnas se validan antes de escribir ninguna: una lista con un valor malo en
        // medio no puede dejar la fila a medio actualizar.
        $args[] = $duracionRef;
        $this->db->query(
            'UPDATE general_dias_procesos_contratacion SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $args,
        );

        return ['ok' => true];
    }
}
