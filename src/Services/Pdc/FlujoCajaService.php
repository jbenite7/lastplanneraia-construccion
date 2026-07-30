<?php

declare(strict_types=1);

namespace App\Services\Pdc;

/**
 * Flujo de caja: la curva mensual de desembolsos por contratación de una obra.
 *
 * La empresa no está entregando flujo de caja de sus proyectos y le está costando en comité EPI. El
 * dato ya estaba: cada destino contratable tiene un valor (la suma de sus insumos) y, desde A4, unas
 * fechas derivadas del frente del cronograma al que está amarrado. Esto reparte lo uno sobre lo otro
 * y suma por mes.
 *
 * ------------------------------------------------------------------------------------------------
 * TRES DECISIONES QUE SE DECLARAN, NO SE ESCONDEN
 * ------------------------------------------------------------------------------------------------
 * 1. **El reparto es LINEAL** entre el inicio y el fin del frente, a prorrata de los días de cada
 *    mes. Es una decisión, no un descuido: «aproximado» significa exactamente esto, y una curva en S
 *    fingiría una precisión que no tenemos. `NOTA_METODO` viaja en la respuesta y en la exportación
 *    para que el documento lo diga por sí mismo — esta curva va a llegar a un comité de dirección y
 *    alguien la va a tratar como presupuesto de tesorería.
 * 2. **No hay condiciones de pago.** Ni anticipos, ni cortes de obra, ni retenciones, ni plazos.
 *    Eso exige un modelo de pagos que hoy no existe en ninguna parte del sistema: es una fase
 *    propia, no un parámetro que se añada aquí.
 * 3. **Lo que queda fuera se cuenta y se valora.** Un destino sin frente, sin fechas utilizables o
 *    cuya modalidad no genera contratación no aporta a la curva, y la pantalla dice cuántos son y
 *    cuánto valen. Una curva que calla lo que no incluye es una curva que miente, y con 11 de 96
 *    paquetes con fecha lo que se calla es la mitad.
 *
 * **Derivado, nunca almacenado.** Se computa al pedirlo. Guardarlo obligaría a invalidarlo cada vez
 * que alguien recalcula el plan o mueve un frente, y sería la primera cifra del módulo que se queda
 * vieja sin que nadie lo note. No hay migración para esto a propósito.
 *
 * La unidad es el **destino contratable** de `SubpaquetesService::destinos()`, no el paquete: si la
 * obra partió «Pisos» en tres lotes con fechas distintas, la curva reparte por lote. Que la unidad
 * salga de un solo sitio es lo que impide que esta pantalla y el tablero de vencimientos cuenten
 * cosas distintas.
 */
final class FlujoCajaService
{
    /**
     * La advertencia que acompaña a la curva en la pantalla Y en el archivo exportado. Vive aquí y
     * no en la vista porque el archivo viaja solo: quien lo abra en un comité no va a tener la
     * pantalla al lado.
     */
    public const NOTA_METODO = 'Reparto lineal del valor de cada contratación entre el inicio y el fin '
        . 'de su frente en el cronograma. NO considera condiciones de pago (anticipos, cortes de obra, '
        . 'retenciones ni plazos). Es un flujo aproximado de salida de dinero por contratación, no un '
        . 'presupuesto de tesorería.';

    public function __construct(
        private readonly \Database $db,
        private readonly ?SubpaquetesService $subpaquetes = null,
    ) {
    }

    /**
     * La curva del proyecto.
     *
     * @return array{
     *     nota: string,
     *     meses: list<array{mes: string, previsto: float, acumulado: float, destinos: int}>,
     *     total: float,
     *     incluidos: array{destinos: int, valor: float},
     *     excluidos: array{destinos: int, valor: float, motivos: array<string, array{destinos: int, valor: float}>},
     *     valorTotalDelPlan: float,
     *     detalle: list<array<string, mixed>>
     * }
     */
    public function curva(int $projectId, ?int $versionId = null): array
    {
        $sub = $this->subpaquetes ?? new SubpaquetesService($this->db);
        $destinos = $sub->destinos($projectId, $versionId);
        $frentes = $this->frentesDeDestinos($projectId);

        /** @var array<string, float> $porMes */
        $porMes = [];
        /** @var array<string, int> $destinosPorMes */
        $destinosPorMes = [];
        $incluidos = ['destinos' => 0, 'valor' => 0.0];
        $excluidos = ['destinos' => 0, 'valor' => 0.0, 'motivos' => []];
        $detalle = [];

        foreach ($destinos as $d) {
            $clave = $d['paqueteId'] . ':' . $d['subpaqueteId'];
            $motivo = null;
            if (!$d['generaProceso']) {
                // No se le compra a nadie (nómina, provisiones) o no se contrata (consumo directo):
                // no es que falte el dato, es que no hay desembolso por contratación que repartir.
                $motivo = 'Su modalidad no genera contratación (' . $d['modalidad'] . ')';
            } elseif (!isset($frentes[$clave])) {
                $motivo = 'Sin frente amarrado en el cronograma';
            } elseif ($frentes[$clave]['inicio'] === null || $frentes[$clave]['fin'] === null) {
                $motivo = 'Su frente no tiene fechas utilizables en el cronograma';
            }

            if ($motivo !== null) {
                $excluidos['destinos']++;
                $excluidos['valor'] += $d['valor'];
                $excluidos['motivos'][$motivo] ??= ['destinos' => 0, 'valor' => 0.0];
                $excluidos['motivos'][$motivo]['destinos']++;
                $excluidos['motivos'][$motivo]['valor'] += $d['valor'];
                $detalle[] = [
                    'paqueteId' => $d['paqueteId'],
                    'subpaqueteId' => $d['subpaqueteId'],
                    'nombre' => $d['nombre'],
                    'paqueteNombre' => $d['paqueteNombre'],
                    'valor' => $d['valor'],
                    'incluido' => false,
                    'motivoExclusion' => $motivo,
                    'meses' => [],
                ];
                continue;
            }

            $reparto = self::repartirLineal(
                $d['valor'],
                (string) $frentes[$clave]['inicio'],
                (string) $frentes[$clave]['fin'],
            );
            $incluidos['destinos']++;
            $incluidos['valor'] += $d['valor'];
            foreach ($reparto as $mes => $monto) {
                $porMes[$mes] = ($porMes[$mes] ?? 0.0) + $monto;
                $destinosPorMes[$mes] = ($destinosPorMes[$mes] ?? 0) + 1;
            }
            $detalle[] = [
                'paqueteId' => $d['paqueteId'],
                'subpaqueteId' => $d['subpaqueteId'],
                'nombre' => $d['nombre'],
                'paqueteNombre' => $d['paqueteNombre'],
                'valor' => $d['valor'],
                'incluido' => true,
                'motivoExclusion' => null,
                'frenteInicio' => $frentes[$clave]['inicio'],
                'frenteFin' => $frentes[$clave]['fin'],
                'meses' => $reparto,
            ];
        }

        ksort($porMes);
        $meses = [];
        $acumulado = 0.0;
        foreach ($porMes as $mes => $monto) {
            $acumulado += $monto;
            $meses[] = [
                'mes' => $mes,
                'previsto' => round($monto, 2),
                'acumulado' => round($acumulado, 2),
                'destinos' => $destinosPorMes[$mes],
            ];
        }

        return [
            'nota' => self::NOTA_METODO,
            'meses' => $meses,
            'total' => round($acumulado, 2),
            'incluidos' => ['destinos' => $incluidos['destinos'], 'valor' => round($incluidos['valor'], 2)],
            'excluidos' => [
                'destinos' => $excluidos['destinos'],
                'valor' => round($excluidos['valor'], 2),
                'motivos' => array_map(static fn (array $m): array => [
                    'destinos' => $m['destinos'],
                    'valor' => round($m['valor'], 2),
                ], $excluidos['motivos']),
            ],
            // Incluidos + excluidos. Es la cifra contra la que se comprueba que la curva no perdió
            // nada por el camino, y el punto 3 de la condición de hecho del spec.
            'valorTotalDelPlan' => round($incluidos['valor'] + $excluidos['valor'], 2),
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
     * $3. Así la suma de los meses es EXACTAMENTE el valor repartido, que es el punto 1 de la
     * condición de hecho.
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

        // Días por mes dentro del intervalo.
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
     * comité es una tabla de dos columnas y un encabezado, y un CSV con BOM lo abre Excel sin
     * preguntar nada. Un `.xlsx` añadiría código de formato para el mismo contenido.
     *
     * Lleva `;` como separador y BOM UTF-8 porque el Excel en español interpreta la coma como
     * decimal: con `,` la columna de pesos se parte en dos y sin BOM las tildes salen roídas.
     *
     * Las dos primeras filas son la advertencia del método. Van DENTRO del archivo a propósito: el
     * archivo se reenvía por correo y se abre sin la pantalla al lado, y sin ellas alguien lo lee
     * como un presupuesto de tesorería.
     */
    public function csv(int $projectId, ?int $versionId = null): string
    {
        $c = $this->curva($projectId, $versionId);
        $lineas = [];
        $lineas[] = ['Flujo de caja aproximado de contratación'];
        $lineas[] = [self::NOTA_METODO];
        $lineas[] = [];
        $lineas[] = ['Mes', 'Desembolso previsto', 'Acumulado', 'Contrataciones que aportan'];
        foreach ($c['meses'] as $m) {
            $lineas[] = [$m['mes'], self::numero($m['previsto']), self::numero($m['acumulado']), (string) $m['destinos']];
        }
        $lineas[] = [];
        $lineas[] = ['Total incluido en la curva', self::numero($c['incluidos']['valor']), '', (string) $c['incluidos']['destinos']];
        $lineas[] = ['Fuera de la curva', self::numero($c['excluidos']['valor']), '', (string) $c['excluidos']['destinos']];
        foreach ($c['excluidos']['motivos'] as $motivo => $m) {
            $lineas[] = ['   ' . $motivo, self::numero($m['valor']), '', (string) $m['destinos']];
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
