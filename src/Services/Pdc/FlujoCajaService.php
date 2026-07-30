<?php

declare(strict_types=1);

namespace App\Services\Pdc;

/**
 * Flujo de caja: la curva mensual de desembolsos de una obra.
 *
 * La empresa no está entregando flujo de caja de sus proyectos y le está costando en comité EPI. El
 * dato ya estaba: cada destino contratable tiene un valor (la suma de sus insumos) y, desde A4, unas
 * fechas derivadas del frente del cronograma al que está amarrado. Esto reparte lo uno sobre lo otro
 * y suma por mes.
 *
 * ------------------------------------------------------------------------------------------------
 * LA CURVA CUENTA EL PRESUPUESTO ENTERO, EN TRES ORÍGENES
 * ------------------------------------------------------------------------------------------------
 * Un flujo de caja que solo mira lo contratado no es el flujo de caja de la obra: la nómina y los
 * imprevistos también salen de caja, y todos los meses. Por eso cada peso del plan entra en la curva,
 * pero **por el camino que le corresponde y dicho con su nombre**:
 *
 * | Origen | Qué es | Cómo se reparte |
 * |---|---|---|
 * | `contratado` | Tiene frente amarrado y fechas propias | Lineal entre el inicio y el fin de SU frente |
 * | `permanente` | No se le compra a nadie (nómina, imprevistos, provisiones) o no se contrata (ferretería contra almacén) | Lineal sobre TODA la duración de la obra |
 * | `provisional` | Se va a contratar, pero nadie le ha amarrado un frente todavía | Lineal sobre toda la obra, **marcado aparte** |
 *
 * La diferencia entre `permanente` y `provisional` no es un matiz: el primero es un dato correcto —ese
 * gasto es continuo de verdad—, y el segundo es un **relleno que se va a mover** en cuanto alguien
 * amarre ese paquete a su frente. Mezclarlos daría una curva que se ve igual de firme en las dos
 * mitades, y cuando la mitad provisional se reacomode nadie entendería por qué cambió. Por eso la
 * pantalla y la exportación llevan el reparto provisional en su propia columna, y el total de la curva
 * es el 100 % del valor del plan.
 *
 * ------------------------------------------------------------------------------------------------
 * DOS DECISIONES QUE SE DECLARAN, NO SE ESCONDEN
 * ------------------------------------------------------------------------------------------------
 * 1. **El reparto es LINEAL** en los tres casos, a prorrata de los días. Es una decisión, no un
 *    descuido: «aproximado» significa exactamente esto, y una curva en S fingiría una precisión que no
 *    tenemos. `NOTA_METODO` viaja en la respuesta y dentro de la exportación.
 * 2. **No hay condiciones de pago.** Ni anticipos, ni cortes de obra, ni retenciones, ni plazos. Eso
 *    exige un modelo de pagos que hoy no existe en ninguna parte del sistema: es una fase propia, no
 *    un parámetro que se añada aquí.
 *
 * **Derivado, nunca almacenado.** Se computa al pedirlo. Guardarlo obligaría a invalidarlo cada vez
 * que alguien recalcula el plan o mueve un frente, y sería la primera cifra del módulo que se queda
 * vieja sin que nadie lo note. No hay migración para esto a propósito.
 *
 * La unidad es el **destino contratable** de `SubpaquetesService::destinos()`, no el paquete: si la
 * obra partió «Pisos» en tres lotes con fechas distintas, la curva reparte por lote.
 */
final class FlujoCajaService
{
    /**
     * La advertencia que acompaña a la curva en la pantalla Y en el archivo exportado. Vive aquí y no
     * en la vista porque el archivo viaja solo: quien lo abra en un comité no va a tener la pantalla
     * al lado.
     */
    public const NOTA_METODO = 'Reparto lineal a prorrata de los días. Lo contratado se reparte entre '
        . 'el inicio y el fin de su frente; la nómina, los imprevistos y las provisiones, sobre toda la '
        . 'duración de la obra; y lo que se va a contratar pero todavía no tiene frente amarrado va '
        . 'repartido sobre toda la obra y contado aparte, porque esa parte se moverá. NO considera '
        . 'condiciones de pago (anticipos, cortes de obra, retenciones ni plazos): es un flujo '
        . 'aproximado de salida de dinero, no un presupuesto de tesorería.';

    /** Los tres caminos por los que un peso del plan entra en la curva. */
    public const ORIGENES = ['contratado', 'permanente', 'provisional'];

    public function __construct(
        private readonly \Database $db,
        private readonly ?SubpaquetesService $subpaquetes = null,
    ) {
    }

    /**
     * La curva del proyecto.
     *
     * @return array<string, mixed>
     */
    public function curva(int $projectId, ?int $versionId = null): array
    {
        $sub = $this->subpaquetes ?? new SubpaquetesService($this->db);
        $destinos = $sub->destinos($projectId, $versionId);
        $frentes = $this->frentesDeDestinos($projectId);
        $obra = $this->duracionObra($projectId);

        /** @var array<string, array<string, float>> $porMes  mes → origen → monto */
        $porMes = [];
        /** @var array<string, int> $destinosPorMes */
        $destinosPorMes = [];
        $porOrigen = [];
        foreach (self::ORIGENES as $o) {
            $porOrigen[$o] = ['destinos' => 0, 'valor' => 0.0];
        }
        $excluidos = ['destinos' => 0, 'valor' => 0.0, 'motivos' => []];
        $detalle = [];
        $valorTotal = 0.0;

        foreach ($destinos as $d) {
            $clave = $d['paqueteId'] . ':' . $d['subpaqueteId'];
            $valorTotal += $d['valor'];
            $conFrente = isset($frentes[$clave])
                && $frentes[$clave]['inicio'] !== null
                && $frentes[$clave]['fin'] !== null;

            // El orden importa: lo que NO se contrata es permanente aunque tenga un frente amarrado
            // por accidente, porque su gasto no depende de cuándo se construya ese frente.
            if (!$d['generaProceso']) {
                $origen = 'permanente';
                $desde = $obra['desde'] ?? null;
                $hasta = $obra['hasta'] ?? null;
            } elseif ($conFrente) {
                $origen = 'contratado';
                $desde = $frentes[$clave]['inicio'];
                $hasta = $frentes[$clave]['fin'];
            } else {
                $origen = 'provisional';
                $desde = $obra['desde'] ?? null;
                $hasta = $obra['hasta'] ?? null;
            }

            // Sin duración de obra no hay sobre qué repartir lo permanente ni lo provisional. Es el
            // único caso que sigue quedando fuera de la curva, y se declara igual que antes: inventar
            // un rango de fechas para que el total cuadre sería justo la mentira que este módulo evita.
            if ($desde === null || $hasta === null) {
                $motivo = $origen === 'contratado'
                    ? 'Su frente no tiene fechas utilizables en el cronograma'
                    : 'La obra no tiene fechas de inicio y fin con las que repartir';
                $excluidos['destinos']++;
                $excluidos['valor'] += $d['valor'];
                $excluidos['motivos'][$motivo] ??= ['destinos' => 0, 'valor' => 0.0];
                $excluidos['motivos'][$motivo]['destinos']++;
                $excluidos['motivos'][$motivo]['valor'] += $d['valor'];
                $detalle[] = $this->filaDetalle($d, false, $origen, $motivo, null, null, []);
                continue;
            }

            $reparto = self::repartirLineal($d['valor'], (string) $desde, (string) $hasta);
            $porOrigen[$origen]['destinos']++;
            $porOrigen[$origen]['valor'] += $d['valor'];
            foreach ($reparto as $mes => $monto) {
                $porMes[$mes] ??= array_fill_keys(self::ORIGENES, 0.0);
                $porMes[$mes][$origen] += $monto;
                $destinosPorMes[$mes] = ($destinosPorMes[$mes] ?? 0) + 1;
            }
            $detalle[] = $this->filaDetalle($d, true, $origen, null, $desde, $hasta, $reparto);
        }

        ksort($porMes);
        $meses = [];
        $acumulado = 0.0;
        foreach ($porMes as $mes => $porO) {
            $previsto = array_sum($porO);
            $acumulado += $previsto;
            $meses[] = [
                'mes' => $mes,
                'previsto' => round($previsto, 2),
                'acumulado' => round($acumulado, 2),
                'destinos' => $destinosPorMes[$mes],
                'contratado' => round($porO['contratado'], 2),
                'permanente' => round($porO['permanente'], 2),
                'provisional' => round($porO['provisional'], 2),
            ];
        }

        $enCurva = 0.0;
        $destinosEnCurva = 0;
        foreach ($porOrigen as $o) {
            $enCurva += $o['valor'];
            $destinosEnCurva += $o['destinos'];
        }

        return [
            'nota' => self::NOTA_METODO,
            'duracionObra' => $obra,
            'meses' => $meses,
            'total' => round($acumulado, 2),
            'porOrigen' => array_map(static fn (array $o): array => [
                'destinos' => $o['destinos'],
                'valor' => round($o['valor'], 2),
            ], $porOrigen),
            'incluidos' => ['destinos' => $destinosEnCurva, 'valor' => round($enCurva, 2)],
            'excluidos' => [
                'destinos' => $excluidos['destinos'],
                'valor' => round($excluidos['valor'], 2),
                'motivos' => array_map(static fn (array $m): array => [
                    'destinos' => $m['destinos'],
                    'valor' => round($m['valor'], 2),
                ], $excluidos['motivos']),
            ],
            // La cifra contra la que se comprueba que la curva no perdió nada por el camino. Con la
            // obra fechada, `total` y esto son el mismo número: la curva cuenta el plan entero.
            'valorTotalDelPlan' => round($valorTotal, 2),
            'detalle' => $detalle,
        ];
    }

    /**
     * Reparte un valor de forma lineal entre dos fechas, devolviendo cuánto cae en cada mes.
     *
     * A prorrata de DÍAS, contando los dos extremos: un frente del 1 de febrero al 30 de abril dura
     * 89 días y aporta a esos tres meses en proporción a los días que tiene en cada uno, y a ningún
     * otro mes.
     *
     * El último mes recibe el residuo en vez de su proporción exacta. Con divisiones de céntimos
     * repartidas en 20 meses, la suma de los redondeos se separa del total unos céntimos, y en una
     * cifra que va a un comité «la suma no da» desacredita la tabla entera aunque el error sea de
     * $3. Así la suma de los meses es EXACTAMENTE el valor repartido.
     *
     * @return array<string, float> mes `AAAA-MM` → monto
     */
    public static function repartirLineal(float $valor, string $inicio, string $fin): array
    {
        $desde = new \DateTimeImmutable($inicio);
        $hasta = new \DateTimeImmutable($fin);
        if ($hasta < $desde) {
            // Un frente con fin anterior al inicio es un dato malo del cronograma, no un caso a
            // modelar: se trata como un solo día para no inventar un reparto hacia atrás ni perder
            // el valor por el camino.
            $hasta = $desde;
        }

        $diasPorMes = [];
        $totalDias = 0;
        for ($d = $desde; $d <= $hasta; $d = $d->modify('+1 day')) {
            $mes = $d->format('Y-m');
            $diasPorMes[$mes] = ($diasPorMes[$mes] ?? 0) + 1;
            $totalDias++;
        }
        if ($totalDias === 0) {
            return [];
        }

        $out = [];
        $repartido = 0.0;
        $meses = array_keys($diasPorMes);
        $ultimo = $meses[count($meses) - 1];
        foreach ($diasPorMes as $mes => $dias) {
            if ($mes === $ultimo) {
                $out[$mes] = round($valor - $repartido, 2);
                continue;
            }
            $monto = round($valor * $dias / $totalDias, 2);
            $out[$mes] = $monto;
            $repartido += $monto;
        }
        return $out;
    }

    /**
     * De cuándo a cuándo va la obra, que es el rango sobre el que se reparte lo que no depende de un
     * frente concreto.
     *
     * Se toma del **cronograma** y no de la línea base del proyecto: el cronograma es lo que la obra
     * está ejecutando hoy y es la misma fuente de la que sale el resto de la curva, así que las dos
     * mitades hablan del mismo calendario. La línea base queda como respaldo para una obra cuyo
     * cronograma todavía no se ha consolidado; si tampoco la tiene, se devuelve `null` y quien no
     * tenga frente propio queda declarado fuera en vez de repartido sobre un rango inventado.
     *
     * @return array{desde: string, hasta: string, origen: string}|null
     */
    private function duracionObra(int $projectId): ?array
    {
        $r = $this->db->query(
            'SELECT MIN(pc.Fecha_Inicio) AS desde, MAX(pc.Fecha_Fin) AS hasta
               FROM programa_consolidado pc
              WHERE pc.project_id = ?
                AND pc.Semana = (SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?)',
            [$projectId, $projectId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($r !== false && $r['desde'] !== null && $r['hasta'] !== null) {
            return ['desde' => (string) $r['desde'], 'hasta' => (string) $r['hasta'], 'origen' => 'cronograma'];
        }

        $lb = $this->db->query(
            'SELECT fechaInicioLineaBase AS desde, fechaFinLineaBase AS hasta
               FROM general_proyectos_procesos WHERE Id = ?',
            [$projectId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($lb !== false && !empty($lb['desde']) && !empty($lb['hasta'])) {
            return ['desde' => (string) $lb['desde'], 'hasta' => (string) $lb['hasta'], 'origen' => 'linea_base'];
        }
        return null;
    }

    /**
     * @param array<string, mixed> $d
     * @param array<string, float> $reparto
     * @return array<string, mixed>
     */
    private function filaDetalle(
        array $d,
        bool $incluido,
        string $origen,
        ?string $motivo,
        ?string $desde,
        ?string $hasta,
        array $reparto,
    ): array {
        return [
            'paqueteId' => $d['paqueteId'],
            'subpaqueteId' => $d['subpaqueteId'],
            'nombre' => $d['nombre'],
            'paqueteNombre' => $d['paqueteNombre'],
            'valor' => $d['valor'],
            'incluido' => $incluido,
            'origen' => $origen,
            'motivoExclusion' => $motivo,
            'repartoDesde' => $desde,
            'repartoHasta' => $hasta,
            'meses' => $reparto,
        ];
    }

    /**
     * Fechas de inicio y fin del frente de cada destino amarrado, indexadas por «paquete:lote».
     *
     * El inicio se lee del cronograma en vivo y no del `fecha_ancla` guardado en el amarre: ese campo
     * es una copia congelada del momento en que se amarró, y si la obra se reprogramó, la curva
     * quedaría dibujada sobre fechas que ya no existen. La curva es un cálculo derivado que se pide
     * cuando se pide, así que lee el dato de hoy.
     *
     * El FIN no está en `pdc_paquete_frente` —que solo guarda el ancla— y hay que traerlo de
     * `programa_consolidado`. Se toma la última semana consolidada, la misma que usa todo el módulo
     * (`MAX(Semana)`, no hay bandera de activa).
     *
     * @return array<string, array{inicio: string|null, fin: string|null, frente: string}>
     */
    private function frentesDeDestinos(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT f.paquete_id, f.subpaquete_id, f.frente_nombre, pc.Fecha_Inicio, pc.Fecha_Fin
               FROM pdc_paquete_frente f
               LEFT JOIN programa_consolidado pc
                      ON pc.project_id = f.project_id AND pc.unique_id = f.unique_id
                     AND pc.Semana = (SELECT MAX(Semana) FROM semanas_activas WHERE project_id = f.project_id)
              WHERE f.project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['paquete_id'] . ':' . (int) $r['subpaquete_id']] = [
                'inicio' => $r['Fecha_Inicio'] === null ? null : (string) $r['Fecha_Inicio'],
                'fin' => $r['Fecha_Fin'] === null ? null : (string) $r['Fecha_Fin'],
                'frente' => (string) $r['frente_nombre'],
            ];
        }
        return $out;
    }

    /**
     * La curva como CSV, listo para abrir en Excel.
     *
     * CSV y no `.xlsx` aunque PhpSpreadsheet ya sea dependencia del proyecto: lo que viaja a un
     * comité es una tabla de pocas columnas, y un CSV con BOM lo abre Excel sin preguntar nada.
     *
     * Lleva `;` como separador y BOM UTF-8 porque el Excel en español interpreta la coma como
     * decimal: con `,` la columna de pesos se parte en dos y sin BOM las tildes salen roídas.
     *
     * Las columnas de los tres orígenes NO son un adorno: son lo que permite ver qué parte del mes es
     * un compromiso con fecha y qué parte es un reparto que se moverá. Y la advertencia del método va
     * en las primeras filas, dentro del archivo, porque el archivo se reenvía y se abre sin la
     * pantalla al lado.
     */
    public function csv(int $projectId, ?int $versionId = null): string
    {
        $c = $this->curva($projectId, $versionId);
        $lineas = [];
        $lineas[] = ['Flujo de caja aproximado de la obra'];
        $lineas[] = [self::NOTA_METODO];
        if ($c['duracionObra'] !== null) {
            $lineas[] = [sprintf(
                'Duración de obra usada para el reparto: %s a %s (según %s).',
                $c['duracionObra']['desde'],
                $c['duracionObra']['hasta'],
                $c['duracionObra']['origen'] === 'cronograma' ? 'el cronograma' : 'la línea base del proyecto',
            )];
        }
        $lineas[] = [];
        $lineas[] = [
            'Mes',
            'Desembolso previsto',
            'Acumulado',
            'Contratado con fecha',
            'Nómina y provisiones',
            'Provisional (sin frente todavía)',
            'Contrataciones que aportan',
        ];
        foreach ($c['meses'] as $m) {
            $lineas[] = [
                $m['mes'],
                self::numero($m['previsto']),
                self::numero($m['acumulado']),
                self::numero($m['contratado']),
                self::numero($m['permanente']),
                self::numero($m['provisional']),
                (string) $m['destinos'],
            ];
        }
        $lineas[] = [];
        $etiquetas = [
            'contratado' => 'Contratado con fecha propia',
            'permanente' => 'Nómina, imprevistos y provisiones (toda la obra)',
            'provisional' => 'Provisional: se contratará, sin frente todavía',
        ];
        foreach (self::ORIGENES as $o) {
            $lineas[] = [$etiquetas[$o], self::numero($c['porOrigen'][$o]['valor']), '', '', '', '', (string) $c['porOrigen'][$o]['destinos']];
        }
        $lineas[] = ['Total en la curva', self::numero($c['total']), '', '', '', '', (string) $c['incluidos']['destinos']];
        if ($c['excluidos']['destinos'] > 0) {
            $lineas[] = ['Fuera de la curva', self::numero($c['excluidos']['valor']), '', '', '', '', (string) $c['excluidos']['destinos']];
            foreach ($c['excluidos']['motivos'] as $motivo => $m) {
                $lineas[] = ['   ' . $motivo, self::numero($m['valor']), '', '', '', '', (string) $m['destinos']];
            }
        }
        $lineas[] = ['Valor total del plan', self::numero($c['valorTotalDelPlan'])];

        $out = "\xEF\xBB\xBF";
        foreach ($lineas as $l) {
            $out .= implode(';', array_map(static fn (string $v): string => '"' . str_replace('"', '""', $v) . '"', $l)) . "\r\n";
        }
        return $out;
    }

    /**
     * Número con coma decimal y sin separador de miles: es lo que el Excel en español lee como
     * número. Con punto decimal lo trata como texto y las sumas del comité dan cero.
     */
    private static function numero(float $v): string
    {
        return number_format($v, 2, ',', '');
    }
}
