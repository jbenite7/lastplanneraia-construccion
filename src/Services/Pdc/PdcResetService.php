<?php

namespace App\Services\Pdc;

use Database;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Limpieza selectiva de los datos operativos del Plan de Compras de UN proyecto.
 *
 * Única fuente de verdad de la operación: la consumen el script de consola
 * `database/seeds/pdc_reset_proyecto.php` y la pantalla del panel admin
 * (`Admin\Controllers\PdcMaintenanceController`), para que no puedan divergir.
 *
 * Qué NO toca, nunca:
 *  - Los catálogos globales (`general_maestro_insumos`, `general_paquetes_contratacion`,
 *    `general_pasos_contratacion`, `general_rama_frente`, ...): no tienen `project_id`, los
 *    comparten todos los proyectos y el flujo los espera sembrados.
 *  - El cronograma LPS (`programa`, `programa_consolidado`, `semanas_activas`): son datos reales
 *    del proyecto, ajenos al PDC. (El sandbox e2e sí los borra, pero porque los resiembra.)
 */
final class PdcResetService
{
    /**
     * Etapas del flujo, de aguas ABAJO hacia ARRIBA. El orden importa dos veces: define qué
     * arrastra qué en `expandir()` y el orden de borrado en `limpiar()` (las versiones de
     * presupuesto van al final porque son las que tienen dependientes en cascada).
     *
     * @var array<string, array{label: string, tablas: list<string>}>
     */
    public const ETAPAS = [
        'plan' => [
            'label' => 'Plan y fechas',
            'tablas' => ['pdc_plan_paso', 'pdc_plan_paquete', 'pdc_proyecto_pasos'],
        ],
        'paquetes' => [
            'label' => 'Paquetes y amarres',
            'tablas' => [
                'pdc_insumo_paquete',
                'pdc_paquete_frente',
                'pdc_rama_frente',
                'pdc_correcciones_motor',
                'pdc_correcciones_frente',
            ],
        ],
        'vinculos' => [
            'label' => 'Vínculos al maestro',
            'tablas' => ['pdc_insumo_vinculos', 'pdc_insumo_actividades'],
        ],
        'presupuesto' => [
            'label' => 'Presupuesto cargado',
            'tablas' => ['pdc_presupuesto_versiones'],
        ],
    ];

    /**
     * Tablas que caen solas al borrar `pdc_presupuesto_versiones` (FK `fk_pdcpi_version`,
     * `fk_pdcpai_version`, `fk_piv_version`). Se cuentan y se respaldan, pero jamás se borran a
     * mano: dejar que la cascada haga su trabajo evita orden de borrado equivocado.
     *
     * @var list<string>
     */
    public const TABLAS_CASCADA = [
        'pdc_presupuesto_items',
        'pdc_presupuesto_apu_insumos',
        'pdc_insumo_vinculos',
    ];

    /**
     * Catálogos globales que se muestran en la interfaz para evidenciar que quedan intactos.
     *
     * @var list<string>
     */
    public const CATALOGOS_INTACTOS = [
        'general_maestro_insumos',
        'general_paquetes_contratacion',
        'general_pasos_contratacion',
    ];

    public function __construct(private Database $db)
    {
    }

    /**
     * Añade a la selección las etapas de aguas abajo: borrar el presupuesto invalida los vínculos,
     * que invalidan los paquetes, que invalidan el plan. Devuelve las claves en el orden canónico
     * de `ETAPAS` (plan primero), que es también el orden de borrado.
     *
     * @param  list<string> $etapas
     * @return list<string>
     */
    public function expandir(array $etapas): array
    {
        $claves = array_keys(self::ETAPAS);

        $masProfunda = -1;
        foreach ($etapas as $etapa) {
            $posicion = array_search($etapa, $claves, true);
            if ($posicion === false) {
                throw new InvalidArgumentException("Etapa desconocida: {$etapa}");
            }
            $masProfunda = max($masProfunda, $posicion);
        }

        if ($masProfunda < 0) {
            throw new InvalidArgumentException('No se seleccionó ninguna etapa.');
        }

        return array_slice($claves, 0, $masProfunda + 1);
    }

    /**
     * Tablas afectadas por una selección ya expandida, en orden de borrado.
     *
     * @param  list<string> $etapas
     * @return list<string>
     */
    public function tablasDe(array $etapas): array
    {
        $tablas = [];
        foreach ($this->expandir($etapas) as $etapa) {
            foreach (self::ETAPAS[$etapa]['tablas'] as $tabla) {
                $tablas[] = $tabla;
            }
        }

        return array_values(array_unique($tablas));
    }

    /**
     * Radiografía del PDC de un proyecto: filas por etapa y por tabla, las de cascada aparte, y
     * el conteo de los catálogos globales para poder comprobar después que no se movieron.
     *
     * @return array{
     *     etapas: array<string, array{label: string, total: int, tablas: array<string, int>}>,
     *     cascada: array<string, int>,
     *     catalogos: array<string, int>,
     *     total: int
     * }
     */
    public function contar(int $projectId): array
    {
        $etapas = [];
        $total = 0;

        foreach (self::ETAPAS as $clave => $definicion) {
            $tablas = [];
            $subtotal = 0;
            foreach ($definicion['tablas'] as $tabla) {
                $filas = $this->contarTabla($tabla, $projectId);
                $tablas[$tabla] = $filas;
                $subtotal += $filas;
            }
            $etapas[$clave] = [
                'label' => $definicion['label'],
                'total' => $subtotal,
                'tablas' => $tablas,
            ];
            $total += $subtotal;
        }

        $cascada = [];
        foreach (self::TABLAS_CASCADA as $tabla) {
            $cascada[$tabla] = $this->contarTabla($tabla, $projectId);
        }

        $catalogos = [];
        foreach (self::CATALOGOS_INTACTOS as $tabla) {
            $catalogos[$tabla] = (int) $this->db->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
        }

        return [
            'etapas' => $etapas,
            'cascada' => $cascada,
            'catalogos' => $catalogos,
            'total' => $total,
        ];
    }

    /**
     * Vuelca a un `.sql` las filas que están a punto de borrarse, incluidas las que caerán en
     * cascada. En PHP puro con `quote()` —igual que `Admin\Models\Project::exportToSql()`— porque
     * el contenedor `app` no tiene cliente de MySQL instalado.
     *
     * @param  list<string> $etapas
     * @return string ruta absoluta del archivo generado
     */
    public function respaldar(int $projectId, array $etapas, ?string $backupDir = null): string
    {
        $dir = $backupDir ?? dirname(__DIR__, 3) . '/storage/backups';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("No se pudo crear el directorio de respaldos: {$dir}");
        }
        if (!is_writable($dir)) {
            throw new RuntimeException("El directorio de respaldos no admite escritura: {$dir}");
        }

        $tablas = array_values(array_unique([...$this->tablasDe($etapas), ...self::TABLAS_CASCADA]));

        $sql = "-- Respaldo del Plan de Compras\n"
            . "-- project_id: {$projectId}\n"
            . '-- Etapas: ' . implode(', ', $this->expandir($etapas)) . "\n"
            . '-- Generado: ' . date('Y-m-d H:i:s') . "\n\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tablas as $tabla) {
            $filas = $this->db->query(
                "SELECT * FROM `{$tabla}` WHERE project_id = ?",
                [$projectId],
            )->fetchAll(\PDO::FETCH_ASSOC);
            $sql .= $this->volcarInserts($tabla, $filas);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $ruta = sprintf('%s/pdc-limpieza-%d-%s.sql', $dir, $projectId, date('Ymd-His'));
        if (file_put_contents($ruta, $sql) === false || filesize($ruta) === 0) {
            throw new RuntimeException("El respaldo no se pudo escribir en {$ruta}.");
        }

        return $ruta;
    }

    /**
     * Borra, en una sola transacción, las filas de las etapas seleccionadas (y las de aguas abajo).
     * Devuelve los conteos posteriores para que quien llame pueda verificar sin volver a consultar.
     *
     * @param  list<string> $etapas
     * @return array{etapas: list<string>, tablas: list<string>, borradas: array<string, int>, conteos: array<string, mixed>}
     */
    public function limpiar(int $projectId, array $etapas): array
    {
        $expandidas = $this->expandir($etapas);
        $tablas = $this->tablasDe($expandidas);

        $this->db->beginTransaction();
        try {
            $borradas = [];
            foreach ($tablas as $tabla) {
                $stmt = $this->db->query("DELETE FROM `{$tabla}` WHERE project_id = ?", [$projectId]);
                $borradas[$tabla] = $stmt->rowCount();
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'etapas' => $expandidas,
            'tablas' => $tablas,
            'borradas' => $borradas,
            'conteos' => $this->contar($projectId),
        ];
    }

    private function contarTabla(string $tabla, int $projectId): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM `{$tabla}` WHERE project_id = ?",
            [$projectId],
        )->fetchColumn();
    }

    /**
     * @param list<array<string, mixed>> $filas
     */
    private function volcarInserts(string $tabla, array $filas): string
    {
        if ($filas === []) {
            return "-- {$tabla}: sin filas para este proyecto.\n\n";
        }

        $columnas = array_keys($filas[0]);
        $entrecomilladas = array_map(static fn ($columna) => "`{$columna}`", $columnas);
        $salida = "-- Datos de `{$tabla}`\n";

        foreach ($filas as $fila) {
            $valores = array_map(
                fn ($columna) => $fila[$columna] === null ? 'NULL' : $this->db->quote((string) $fila[$columna]),
                $columnas,
            );
            $salida .= "INSERT INTO `{$tabla}` (" . implode(', ', $entrecomilladas) . ') VALUES ('
                . implode(', ', $valores) . ");\n";
        }

        return $salida . "\n";
    }
}
