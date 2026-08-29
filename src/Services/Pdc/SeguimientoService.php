<?php

namespace App\Services\Pdc;

use App\Security\DataScope\MultiProjectScope;

/**
 * Seguimiento al plan de compras (PDC v2 / Fase B1): cuando ocurrio de verdad cada paso de
 * contratacion, y como se lee eso contra lo que estaba programado.
 *
 * Va aparte de PlanFechasService a proposito. Aquel calcula el plan —cuando deberian pasar las
 * cosas— y ya pasa de 1.600 lineas; este registra lo que paso. Son dos responsabilidades que se
 * tocan solo por la tabla, y mantenerlas separadas es lo que permite razonar sobre cualquiera de las
 * dos sin sostener la otra en la cabeza.
 */
class SeguimientoService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * Que paquetes del tablero se calcularon contra un cronograma que ya cambio.
     *
     * Se delega en `PlanFechasService::desfases()` en vez de repetir la comparacion aqui: si algun
     * dia cambia que cuenta como desfase, el tablero de vencimientos y la pantalla del plan no
     * pueden discrepar. Sin columna de estado que mantener al dia — se deduce comparando la fecha
     * guardada en el amarre contra la del cronograma en vivo, que es lo que decidio el diseño B2.
     *
     * Va como lista aparte y no como columna del resumen: es una propiedad del AMARRE, no del
     * avance, y mezclarla haria pensar que un paquete «esta desactualizado» en su seguimiento.
     *
     * @return list<int>
     */
    public function paquetesDesactualizados(int $projectId): array
    {
        // Sin `array_values()`: `desfases()` ya devuelve una lista con claves consecutivas, y
        // envolverla no hacía nada. Se veía necesario mientras el tipo de retorno de `desfases()`
        // estaba sin declarar; al declararlo, PHPStan señaló que la llamada no tenía efecto.
        return array_map(
            static fn (array $d): int => $d['paqueteId'],
            (new PlanFechasService($this->db))->desfases($projectId),
        );
    }

    /**
     * El corte de vencimiento de una fecha programada contra hoy.
     *
     * Estatica y pura a proposito: es la UNICA regla del modulo que dice si algo esta vencido, y la
     * consumen dos sitios —la pestaña de vencimientos y el semaforo del plan—. Si viviera en la SPA,
     * o duplicada en cada consumidor, el color y la lista podrian contradecirse sin que nada fallara.
     *
     * Los cortes son los que nombro el dueño del producto —vencido, 1, 2, 3 y 6 semanas— y ninguno
     * mas. `sin_fecha` no es un corte inventado: es el hueco de un paso que el plan aun no fecho, y
     * tiene nombre propio para que se pueda contar y enseñar en vez de desaparecer.
     *
     * @return array{estado: string, diasDesfase: ?int}
     */
    public static function clasificarVencimiento(?string $fechaFin, string $hoy): array
    {
        if ($fechaFin === null || $fechaFin === '') {
            return ['estado' => 'sin_fecha', 'diasDesfase' => null];
        }
        $fin = new \DateTimeImmutable($fechaFin);
        $ref = new \DateTimeImmutable($hoy);
        // Dias completos entre las dos fechas, con signo: negativo = ya paso.
        $dias = (int) $ref->diff($fin)->format('%r%a');
        if ($dias < 0) {
            return ['estado' => 'vencido', 'diasDesfase' => -$dias];
        }
        // Intervalos medio abiertos [inicio, fin), en el mismo orden en que se leen: hoy entra en la
        // primera semana, no en «vencido». Lo de hoy todavia se puede hacer hoy.
        $estado = match (true) {
            $dias < 7 => 'sem1',
            $dias < 14 => 'sem2',
            $dias < 21 => 'sem3',
            $dias < 42 => 'sem6',
            default => 'adelante',
        };
        return ['estado' => $estado, 'diasDesfase' => null];
    }

    /**
     * Proyeccion: cuando terminara cada paso si lo pendiente dura lo previsto.
     *
     * Es aritmetica pura, sin base de datos, para poder probarla con casos escritos a mano en vez de
     * con un plan sembrado. Lo PROGRAMADO no entra ni sale: es la linea base contra la que se mide el
     * atraso, y reescribirla dejaria al proyecto sin forma de decir cuanto se desvio de lo prometido.
     *
     * Un paso cumplido vale por si mismo: su proyectada ES su fecha real. Uno pendiente hereda el
     * cursor que dejo el anterior. Y si al llegar al primer pendiente el cursor esta en el pasado, se
     * adelanta a hoy: decir que algo que no ha ocurrido «terminara» hace tres semanas no es una
     * proyeccion, es ruido.
     *
     * @param list<array{dias: int, fechaFin: ?string, fechaReal: ?string}> $pasos en orden
     * @return list<array{proyectadoInicio: string, proyectadoFin: string, desfaseDias: ?int}>
     */
    public function proyectar(string $fechaArranque, array $pasos, string $hoy): array
    {
        $cursor = new \DateTimeImmutable($fechaArranque);
        $limite = new \DateTimeImmutable($hoy);
        $out = [];

        foreach ($pasos as $p) {
            if ($p['fechaReal'] !== null) {
                $real = new \DateTimeImmutable($p['fechaReal']);
                $inicio = $cursor;
                $cursor = $real;
                $out[] = [
                    'proyectadoInicio' => $inicio->format('Y-m-d'),
                    'proyectadoFin' => $real->format('Y-m-d'),
                    // Sin fecha programada no hay contra que medir: es el caso de un paso que
                    // conserva su avance mientras espera que el plan se recalcule tras un reamarre.
                    'desfaseDias' => $p['fechaFin'] === null
                        ? null
                        : (int) (new \DateTimeImmutable($p['fechaFin']))->diff($real)->format('%r%a'),
                ];
                continue;
            }

            if ($cursor < $limite) {
                $cursor = $limite;
            }
            $inicio = $cursor;
            $cursor = $cursor->modify(sprintf('+%d days', $p['dias']));
            $out[] = [
                'proyectadoInicio' => $inicio->format('Y-m-d'),
                'proyectadoFin' => $cursor->format('Y-m-d'),
                'desfaseDias' => null,
            ];
        }

        return $out;
    }

    /**
     * Los pasos de un paquete con las tres fechas: programada, real y proyectada.
     *
     * @return list<array<string, mixed>>
     */
    public function pasosDePaquete(int $projectId, int $paqueteId, int $subpaqueteId = 0): array
    {
        // `$subpaqueteId` no es opcional por comodidad: sin él, un paquete partido en tres devolvería
        // los pasos de sus tres lotes mezclados y ordenados por `orden`, es decir tres veces cada
        // paso del proceso, como si el paquete tuviera 21 pasos. `0` es el paquete sin partir.
        $arranque = $this->db->query(
            'SELECT fecha_arranque FROM pdc_plan_paquete
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?',
            [$projectId, $paqueteId, $subpaqueteId],
        )->fetchColumn();
        if ($arranque === false || $arranque === null) {
            // Sin cabecera con fechas no hay plan que seguir. Devolver vacio y no reventar: la
            // pantalla tiene que poder pedir el detalle de cualquier fila sin comprobar antes.
            return [];
        }

        $rows = $this->db->query(
            'SELECT paso_id, orden, paso, dias, fecha_inicio, fecha_fin, fecha_real,
                    registrado_por, registrado_at
             FROM pdc_plan_paso
             WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?
             ORDER BY orden',
            [$projectId, $paqueteId, $subpaqueteId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $paraProyectar = array_map(static fn (array $r): array => [
            'dias' => (int) $r['dias'],
            'fechaFin' => $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'],
            'fechaReal' => $r['fecha_real'] === null ? null : (string) $r['fecha_real'],
        ], $rows);
        $proyeccion = $this->proyectar((string) $arranque, $paraProyectar, (new \DateTimeImmutable('today'))->format('Y-m-d'));

        $out = [];
        foreach ($rows as $i => $r) {
            $out[] = [
                'pasoId' => $r['paso_id'] === null ? null : (int) $r['paso_id'],
                'orden' => (int) $r['orden'],
                'paso' => (string) $r['paso'],
                'dias' => (int) $r['dias'],
                'fechaInicio' => $r['fecha_inicio'] === null ? null : (string) $r['fecha_inicio'],
                'fechaFin' => $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'],
                'fechaReal' => $r['fecha_real'] === null ? null : (string) $r['fecha_real'],
                'proyectadoInicio' => $proyeccion[$i]['proyectadoInicio'],
                'proyectadoFin' => $proyeccion[$i]['proyectadoFin'],
                'desfaseDias' => $proyeccion[$i]['desfaseDias'],
                'registradoPor' => (string) $r['registrado_por'],
                'registradoAt' => $r['registrado_at'] === null ? null : (string) $r['registrado_at'],
            ];
        }

        return $out;
    }

    /**
     * Registra (o borra, con null) la fecha en que ocurrio de verdad un paso.
     *
     * No hay regla de orden entre pasos a proposito. En obra la orden de compra se firma a veces
     * antes de que alguien archive el acta del paso anterior, y bloquear el registro fuera de orden
     * no produce disciplina: produce fechas inventadas para desbloquear la pantalla.
     *
     * @return array{ok: bool, code?: string, mensaje?: string}
     */
    public function registrarPaso(
        int $projectId,
        int $paqueteId,
        int $pasoId,
        ?string $fechaReal,
        string $usuario,
        int $subpaqueteId = 0,
    ): array {
        if ($fechaReal !== null) {
            // Formato estricto: `strtotime` aceptaria '15/04/2026' y lo interpretaria al reves, y esa
            // fecha silenciosamente equivocada no la detecta nadie hasta que la proyeccion sale rara.
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $fechaReal);
            if ($d === false || $d->format('Y-m-d') !== $fechaReal) {
                return ['ok' => false, 'code' => 'FECHA_INVALIDA', 'mensaje' => 'La fecha debe venir como AAAA-MM-DD.'];
            }
        }

        // La CUATERNA (proyecto, paquete, lote, paso) se comprueba junta. Que el paso exista en el
        // catalogo no dice nada: lo que hay que garantizar es que ESE paso pertenece al plan de ESE
        // destino en ESTE proyecto. Sin esto, un paquete_id equivocado escribiria en el plan de otro.
        // El lote entra en la clave porque en un paquete partido los tres lotes tienen el MISMO
        // paso_id: sin el, registrar «propuestas recibidas» en el lote de porcelanato lo marcaria
        // tambien en el de ceramica, que no ha recibido nada.
        $existe = $this->db->query(
            'SELECT 1 FROM pdc_plan_paso
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ? AND paso_id = ?',
            [$projectId, $paqueteId, $subpaqueteId, $pasoId],
        )->fetchColumn();
        if ($existe === false) {
            return [
                'ok' => false,
                'code' => 'PASO_INVALIDO',
                'mensaje' => 'Ese paso no pertenece al plan de este paquete.',
            ];
        }

        // Borrar la fecha borra tambien su auditoria: dejar «lo registro Fulano» sobre una casilla
        // vacia solo genera preguntas sin respuesta.
        $this->db->query(
            'UPDATE pdc_plan_paso
                SET fecha_real = ?,
                    registrado_por = ?,
                    registrado_at = ?
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ? AND paso_id = ?',
            [
                $fechaReal,
                $fechaReal === null ? '' : $usuario,
                $fechaReal === null ? null : (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                $projectId, $paqueteId, $subpaqueteId, $pasoId,
            ],
        );

        return ['ok' => true];
    }

    /**
     * Una fila por paquete con plan: en que paso va, cuanto lleva, si esta atrasado.
     *
     * Todos los derivados se calculan aqui y ninguno se guarda. Un estado persistido se
     * desincroniza de la fecha que lo justifica en cuanto alguien corrija una sola de las dos.
     *
     * @return list<array<string, mixed>>
     */
    public function resumen(int $projectId): array
    {
        $cabeceras = $this->db->query(
            'SELECT pp.paquete_id, pp.subpaquete_id, pp.fecha_arranque, p.nombre, f.frente_nombre,
                    s.nombre AS lote_nombre, s.es_resto, s.responsable_user_id AS lote_responsable,
                    pp.responsable_user_id, u.nombre AS responsable_nombre,
                    u.activo AS responsable_activo, pm.user_id AS responsable_miembro
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             -- Por DESTINO (paquete + lote). Unir el amarre solo por paquete multiplicaba cada
             -- cabecera de un paquete partido por su número de lotes, y el tablero mostraba el mismo
             -- proceso tantas veces como lotes tuviera.
             LEFT JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
                                           AND f.subpaquete_id = pp.subpaquete_id
             LEFT JOIN pdc_subpaquete s ON s.project_id = pp.project_id AND s.id = pp.subpaquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             LEFT JOIN project_members pm ON pm.project_id = pp.project_id AND pm.user_id = pp.responsable_user_id
             WHERE pp.project_id = ? AND p.activo = 1 AND pp.fecha_arranque IS NOT NULL
             ORDER BY pp.fecha_arranque ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Una sola consulta para todos los pasos del proyecto: pedirlos paquete por paquete serian
        // cientos de viajes a la base para pintar una pantalla.
        // Indexado por DESTINO y no por paquete: si no, los pasos de todos los lotes de un paquete
        // partido se apilan en la misma lista y cada lote cuenta como si tuviera 21 pasos.
        $porPaquete = [];
        foreach ($this->db->query(
            'SELECT paquete_id, subpaquete_id, orden, paso, dias, fecha_fin, fecha_real
             FROM pdc_plan_paso WHERE project_id = ? ORDER BY paquete_id, subpaquete_id, orden',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $porPaquete[(int) $r['paquete_id'] . ':' . (int) $r['subpaquete_id']][] = $r;
        }

        $hoy = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $out = [];
        foreach ($cabeceras as $c) {
            $paqueteId = (int) $c['paquete_id'];
            $subpaqueteId = (int) $c['subpaquete_id'];
            $pasos = $porPaquete[$paqueteId . ':' . $subpaqueteId] ?? [];
            if ($pasos === []) {
                continue;
            }

            $proyeccion = $this->proyectar(
                (string) $c['fecha_arranque'],
                array_map(static fn (array $r): array => [
                    'dias' => (int) $r['dias'],
                    'fechaFin' => $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'],
                    'fechaReal' => $r['fecha_real'] === null ? null : (string) $r['fecha_real'],
                ], $pasos),
                $hoy,
            );

            $cumplidos = 0;
            $pasoActual = '';
            $atrasado = false;
            // El fin programado es el del ULTIMO paso, y punto. Arrastrar «la ultima fecha no nula»
            // parecia mas tolerante con los huecos, pero un paso conservado sin fechas programadas
            // —reamarre pendiente, o paso retirado del proceso— hacia que la columna anunciara como
            // fin del proceso la fecha de un paso intermedio: una fecha plausible, que nadie lee
            // como error. Sin fecha en el ultimo paso no hay fin que anunciar: null.
            $ultimo = $pasos[count($pasos) - 1];
            $finProgramado = $ultimo['fecha_fin'] === null ? null : (string) $ultimo['fecha_fin'];
            foreach ($pasos as $i => $r) {
                if ($r['fecha_real'] !== null) {
                    $cumplidos++;
                    if (($proyeccion[$i]['desfaseDias'] ?? 0) > 0) {
                        $atrasado = true;
                    }
                    continue;
                }
                if ($pasoActual === '') {
                    $pasoActual = (string) $r['paso'];
                }
                // Pendiente cuya fecha programada ya paso: nadie lo ha hecho y el plazo vencio.
                if ($r['fecha_fin'] !== null && (string) $r['fecha_fin'] < $hoy) {
                    $atrasado = true;
                }
            }

            $total = count($pasos);
            $esLote = $subpaqueteId !== 0;
            $out[] = [
                'paqueteId' => $paqueteId,
                'subpaqueteId' => $subpaqueteId,
                'esLote' => $esLote,
                'esResto' => $esLote && (int) $c['es_resto'] === 1,
                // Para un paquete partido, el tablero nombra el LOTE: es lo que de verdad se
                // contrata y lo que alguien tiene que ir a mover. El paquete queda como contexto.
                'nombre' => $esLote ? (string) $c['lote_nombre'] : (string) $c['nombre'],
                'paqueteNombre' => (string) $c['nombre'],
                'frenteNombre' => (string) ($c['frente_nombre'] ?? ''),
                'responsableUserId' => $c['responsable_user_id'] === null ? null : (int) $c['responsable_user_id'],
                'responsableNombre' => (string) ($c['responsable_nombre'] ?? ''),
                'responsableHuerfano' => $c['responsable_user_id'] !== null
                    && ($c['responsable_miembro'] === null || (int) $c['responsable_activo'] !== 1),
                'pasoActual' => $pasoActual,
                'cumplidos' => $cumplidos,
                'total' => $total,
                'estado' => $cumplidos === 0 ? 'sin_empezar' : ($cumplidos === $total ? 'terminado' : 'en_curso'),
                'atrasado' => $atrasado,
                'finProgramado' => $finProgramado,
                'finProyectado' => $proyeccion[$total - 1]['proyectadoFin'],
            ];
        }

        return $out;
    }

    /**
     * El look-ahead de contratacion: que pasos pendientes vencen y cuando.
     *
     * Una fila por PASO pendiente, no por paquete: un paquete con tres pasos abiertos aparece tres
     * veces, y agregarlo a una sola fila es justo lo que esconde los atrasos que se pidio ver.
     *
     * Los filtros se aplican AQUI y no en la SPA, para que los conteos por corte describan siempre
     * exactamente lo que hay en la tabla de al lado. Sin filtros, la suma de los conteos es el total
     * de pasos pendientes del proyecto — es la invariante que vigila el gate.
     *
     * @param array{pasoClave?: string, responsableUserId?: ?int, soloSinResponsable?: bool} $filtros
     * @return array<string, mixed>
     */
    public function vencimientos(int $projectId, array $filtros = [], ?string $hoy = null): array
    {
        $hoy ??= (new \DateTimeImmutable('today'))->format('Y-m-d');

        $rows = $this->db->query(
            'SELECT ps.paquete_id, ps.subpaquete_id, ps.paso_id, ps.orden, ps.paso, ps.fecha_fin,
                    COALESCE(g.clave, ps.paso) AS clave,
                    p.nombre AS paquete, f.frente_nombre,
                    s.nombre AS lote_nombre, s.es_resto,
                    pp.responsable_user_id, u.nombre AS responsable_nombre
             FROM pdc_plan_paso ps
             -- Las tres uniones van por DESTINO (paquete + lote). Con la unión solo por paquete, un
             -- paso de un paquete partido en tres salía tres veces en el tablero —una por cabecera—
             -- y los conteos de «qué se me vence» quedaban multiplicados sin que nada lo dijera.
             JOIN pdc_plan_paquete pp ON pp.project_id = ps.project_id AND pp.paquete_id = ps.paquete_id
                                     AND pp.subpaquete_id = ps.subpaquete_id
             JOIN general_paquetes_contratacion p ON p.id = ps.paquete_id
             LEFT JOIN general_pasos_contratacion g ON g.id = ps.paso_id
             LEFT JOIN pdc_paquete_frente f ON f.project_id = ps.project_id AND f.paquete_id = ps.paquete_id
                                           AND f.subpaquete_id = ps.subpaquete_id
             LEFT JOIN pdc_subpaquete s ON s.project_id = ps.project_id AND s.id = ps.subpaquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             WHERE ps.project_id = ? AND ps.fecha_real IS NULL AND p.activo = 1
             ORDER BY ps.fecha_fin IS NULL, ps.fecha_fin ASC, p.nombre ASC, ps.orden ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $pasoClave = (string) ($filtros['pasoClave'] ?? '');
        $responsable = $filtros['responsableUserId'] ?? null;
        $soloSinResponsable = ($filtros['soloSinResponsable'] ?? false) === true;

        $conteos = ['vencido' => 0, 'sem1' => 0, 'sem2' => 0, 'sem3' => 0, 'sem6' => 0, 'adelante' => 0, 'sin_fecha' => 0];
        $filas = [];
        $total = 0;
        $pasos = [];
        foreach ($rows as $r) {
            $clave = (string) $r['clave'];
            // El catalogo del desplegable se arma con TODO lo pendiente, antes de filtrar: si se
            // armara con lo filtrado, elegir un paso vaciaria la lista y no habria como volver.
            $pasos[$clave] = (string) $r['paso'];

            $responsableId = $r['responsable_user_id'] === null ? null : (int) $r['responsable_user_id'];
            if ($pasoClave !== '' && $clave !== $pasoClave) {
                continue;
            }
            if ($soloSinResponsable && $responsableId !== null) {
                continue;
            }
            if (!$soloSinResponsable && $responsable !== null && $responsableId !== (int) $responsable) {
                continue;
            }

            $fechaFin = $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'];
            $c = self::clasificarVencimiento($fechaFin, $hoy);
            $conteos[$c['estado']]++;
            $total++;
            if ($c['estado'] === 'adelante') {
                // Se cuenta y no se lista: Da Porto puede llegar a 96 paquetes por hasta 9 pasos, y
                // la cola lejana es la mitad del peso de la tabla sin ser el trabajo de esta semana.
                continue;
            }
            $subpaqueteId = (int) $r['subpaquete_id'];
            $filas[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'subpaqueteId' => $subpaqueteId,
                'esLote' => $subpaqueteId !== 0,
                // Lo que se contrata: el lote si el paquete está partido. El tablero de vencimientos
                // es donde de verdad se contrata, así que aquí manda el lote.
                'paquete' => $subpaqueteId !== 0 ? (string) $r['lote_nombre'] : (string) $r['paquete'],
                'paqueteNombre' => (string) $r['paquete'],
                'frenteNombre' => (string) ($r['frente_nombre'] ?? ''),
                'pasoId' => $r['paso_id'] === null ? null : (int) $r['paso_id'],
                'orden' => (int) $r['orden'],
                'paso' => (string) $r['paso'],
                'clave' => $clave,
                'fechaFin' => $fechaFin,
                'responsableUserId' => $responsableId,
                'responsableNombre' => (string) ($r['responsable_nombre'] ?? ''),
                'estado' => $c['estado'],
                'diasDesfase' => $c['diasDesfase'],
            ];
        }

        $catalogo = [];
        foreach ($pasos as $clave => $etiqueta) {
            $catalogo[] = ['clave' => (string) $clave, 'paso' => $etiqueta];
        }

        return [
            'hoy' => $hoy,
            'filas' => $filas,
            'conteos' => $conteos,
            'totalPendientes' => $total,
            'pasos' => $catalogo,
            'sinFechas' => $this->paquetesSinFechas($projectId),
        ];
    }

    /**
     * Detalle del drill-down de la Torre de Control: un renglón por paso pendiente.
     *
     * No selecciona proveedor a propósito (Decisión 3 del spec): ese dato no sale del módulo.
     * Para eso está la pantalla de contratación, que ya lo protege.
     *
     * @param MultiProjectScope $scope alcance BI autorizado
     * @return list<array{project_id:int,paquete:string,lote:?string,paso:string,fecha_fin:?string,estado:string,diasDesfase:int,responsable:?string}>
     */
    public function detalleDestinos(MultiProjectScope $scope, ?string $hoy = null): array
    {
        $hoy ??= (new \DateTimeImmutable('today'))->format('Y-m-d');
        $ids = $scope->projectIds();

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->queryForProjects(
            $scope,
            "SELECT ps.project_id, ps.paquete_id, s.nombre AS lote, ps.orden, ps.paso, ps.fecha_fin,
                    u.nombre AS responsable
             FROM pdc_plan_paso ps
             JOIN pdc_plan_paquete pp ON pp.project_id = ps.project_id AND pp.paquete_id = ps.paquete_id
                                     AND pp.subpaquete_id = ps.subpaquete_id
             LEFT JOIN pdc_subpaquete s ON s.project_id = ps.project_id AND s.id = ps.subpaquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             WHERE ps.project_id IN ({$ph}) AND ps.fecha_real IS NULL",
            $ids,
        )->fetchAll(\PDO::FETCH_ASSOC);
        $packages = $this->activePackageNames(array_map(
            static fn(array $row): int => (int) $row['paquete_id'],
            $rows,
        ));
        usort($rows, static function (array $left, array $right) use ($packages): int {
            $nullOrder = ($left['fecha_fin'] === null) <=> ($right['fecha_fin'] === null);
            if ($nullOrder !== 0) {
                return $nullOrder;
            }

            $dateOrder = strcmp((string) ($left['fecha_fin'] ?? ''), (string) ($right['fecha_fin'] ?? ''));
            if ($dateOrder !== 0) {
                return $dateOrder;
            }

            $packageOrder = strnatcasecmp(
                $packages[(int) $left['paquete_id']] ?? '',
                $packages[(int) $right['paquete_id']] ?? '',
            );
            if ($packageOrder !== 0) {
                return $packageOrder;
            }

            return (int) $left['orden'] <=> (int) $right['orden'];
        });

        $out = [];
        foreach ($rows as $r) {
            $packageId = (int) $r['paquete_id'];
            if (!isset($packages[$packageId])) {
                continue;
            }
            $fechaFin = $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'];
            $c = self::clasificarVencimiento($fechaFin, $hoy);
            $out[] = [
                'project_id'  => (int) $r['project_id'],
                'paquete'     => $packages[$packageId],
                'lote'        => $r['lote'] === null ? null : (string) $r['lote'],
                'paso'        => (string) $r['paso'],
                'fecha_fin'   => $fechaFin,
                'estado'      => (string) $c['estado'],
                'diasDesfase' => (int) $c['diasDesfase'],
                'responsable' => $r['responsable'] === null ? null : (string) $r['responsable'],
            ];
        }

        return $out;
    }

    /**
     * Agregado de vencimientos para VARIAS obras, para la Torre de Control (fase B3).
     *
     * Una sola consulta con IN (...), no N consultas: el número de obras autorizadas crece y el
     * panel de gerencia las pide todas de golpe.
     *
     * La clasificación NO se recalcula aquí: se delega en clasificarVencimiento(), la misma que
     * consumen la pestaña del módulo y el semáforo del plan. Dos definiciones de «vencido» en la
     * misma empresa es peor que no tener ninguna.
     *
     * @param MultiProjectScope $scope alcance BI autorizado
     * @return array{hoy:string,por_obra:array<int,array{project_id:int,conteos:array<string,int>,destinos:int,pasos:int}>,totales:array<string,int>,por_paso:array<string,array{pendientes:int,vencidos:int}>,por_responsable:array<int,array{nombre:string,pendientes:int,vencidos:int}>}
     */
    public function vencimientosAgregados(MultiProjectScope $scope, ?string $hoy = null): array
    {
        $hoy ??= (new \DateTimeImmutable('today'))->format('Y-m-d');

        $ids = $scope->projectIds();
        $vacio = ['vencido' => 0, 'sem1' => 0, 'sem2' => 0, 'sem3' => 0, 'sem6' => 0, 'adelante' => 0, 'sin_fecha' => 0];

        $ph = implode(',', array_fill(0, count($ids), '?'));
        // La unión va por DESTINO (paquete + lote), igual que vencimientos(): unir solo por
        // paquete hace que un paso de un paquete partido en tres se cuente tres veces.
        $rows = $this->db->queryForProjects(
            $scope,
            "SELECT ps.project_id, ps.paquete_id, ps.subpaquete_id, ps.fecha_fin, ps.paso,
                    pp.responsable_user_id, u.nombre AS responsable_nombre
             FROM pdc_plan_paso ps
             JOIN pdc_plan_paquete pp ON pp.project_id = ps.project_id AND pp.paquete_id = ps.paquete_id
                                     AND pp.subpaquete_id = ps.subpaquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             WHERE ps.project_id IN ({$ph}) AND ps.fecha_real IS NULL",
            $ids,
        )->fetchAll(\PDO::FETCH_ASSOC);
        $packages = $this->activePackageNames(array_map(
            static fn(array $row): int => (int) $row['paquete_id'],
            $rows,
        ));

        $porObra = [];
        $totales = $vacio;
        $destinos = [];
        $porPaso = [];
        $porResponsable = [];
        foreach ($rows as $r) {
            if (!isset($packages[(int) $r['paquete_id']])) {
                continue;
            }
            $pid = (int) $r['project_id'];
            if (!isset($porObra[$pid])) {
                $porObra[$pid] = ['project_id' => $pid, 'conteos' => $vacio, 'destinos' => 0, 'pasos' => 0];
                $destinos[$pid] = [];
            }

            $fechaFin = $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'];
            $estado = (string) self::clasificarVencimiento($fechaFin, $hoy)['estado'];
            $vencido = $estado === 'vencido';

            $porObra[$pid]['conteos'][$estado]++;
            $porObra[$pid]['pasos']++;
            $totales[$estado]++;
            $destinos[$pid][$r['paquete_id'] . ':' . $r['subpaquete_id']] = true;

            // Avance de contratación: por qué paso va cada compra.
            $paso = (string) $r['paso'];
            if (!isset($porPaso[$paso])) {
                $porPaso[$paso] = ['pendientes' => 0, 'vencidos' => 0];
            }
            $porPaso[$paso]['pendientes']++;
            if ($vencido) {
                $porPaso[$paso]['vencidos']++;
            }

            // Carga por responsable. El 0 agrupa lo que no tiene dueño: repartirlo o esconderlo
            // haría que «quién está sobrecargado» ignorara justo el trabajo que nadie ha reclamado.
            $rid = $r['responsable_user_id'] === null ? 0 : (int) $r['responsable_user_id'];
            if (!isset($porResponsable[$rid])) {
                $porResponsable[$rid] = [
                    'nombre' => $rid === 0
                        ? 'Sin responsable'
                        : (string) ($r['responsable_nombre'] ?? ('Usuario ' . $rid)),
                    'pendientes' => 0,
                    'vencidos' => 0,
                ];
            }
            $porResponsable[$rid]['pendientes']++;
            if ($vencido) {
                $porResponsable[$rid]['vencidos']++;
            }
        }

        foreach ($destinos as $pid => $claves) {
            $porObra[$pid]['destinos'] = count($claves);
        }

        // El más cargado primero: la pregunta de gerencia es a quién descargar.
        uasort($porResponsable, static fn(array $a, array $b): int => $b['pendientes'] <=> $a['pendientes']);

        return [
            'hoy' => $hoy,
            'por_obra' => $porObra,
            'totales' => $totales,
            'por_paso' => $porPaso,
            'por_responsable' => $porResponsable,
        ];
    }

    /** @param list<int> $packageIds @return array<int, string> */
    private function activePackageNames(array $packageIds): array
    {
        $packageIds = array_values(array_unique(array_filter($packageIds, static fn(int $id): bool => $id > 0)));
        if ($packageIds === []) {
            return [];
        }

        // Catálogo global de presentación: no contiene project_id ni concede autoridad. Las filas
        // operativas que originan estos IDs ya quedaron acotadas por queryForProjects().
        $ph = implode(',', array_fill(0, count($packageIds), '?'));
        $rows = $this->db->query(
            "SELECT id, nombre FROM general_paquetes_contratacion WHERE id IN ({$ph}) AND activo = 1",
            $packageIds,
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['id']] = (string) $row['nombre'];
        }
        return $out;
    }

    /**
     * Cobertura de presupuesto por obra para la ruta BI multiproyecto.
     *
     * Conserva el mismo numerador y denominador de PaquetesService::resumen(), pero resuelve todas
     * las obras autorizadas de una sola vez y mantiene la autoridad en MultiProjectScope.
     *
     * @return array<int, array{cobertura:float,coberturaValor:float}>
     */
    public function coberturaPorProyecto(MultiProjectScope $scope): array
    {
        $ids = $scope->projectIds();
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->queryForProjects(
            $scope,
            "SELECT v.project_id,
                    COUNT(*) AS total,
                    SUM(CASE WHEN a.paquete_id IS NOT NULL THEN 1 ELSE 0 END) AS asignados,
                    SUM(CASE WHEN a.omitido = 1 THEN 1 ELSE 0 END) AS omitidos,
                    COALESCE(SUM(v.valor_total), 0) AS valor_total,
                    COALESCE(SUM(CASE WHEN a.paquete_id IS NOT NULL OR a.omitido = 1 THEN v.valor_total ELSE 0 END), 0) AS valor_cubierto
             FROM pdc_insumo_vinculos v
             JOIN pdc_presupuesto_versiones pv
               ON pv.project_id = v.project_id AND pv.id = v.version_id AND pv.activa = 1
             LEFT JOIN pdc_insumo_paquete a
               ON a.project_id = v.project_id
              AND a.descripcion_norm = v.descripcion_norm
              AND a.unidad = v.unidad
             WHERE v.project_id IN ({$ph})
             GROUP BY v.project_id",
            $ids,
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($ids as $projectId) {
            $out[$projectId] = ['cobertura' => 0.0, 'coberturaValor' => 0.0];
        }
        foreach ($rows as $row) {
            $projectId = (int) $row['project_id'];
            $total = (int) $row['total'];
            $valorTotal = (float) $row['valor_total'];
            $out[$projectId] = [
                'cobertura' => $total === 0
                    ? 0.0
                    : round(((int) $row['asignados'] + (int) $row['omitidos']) * 100 / $total, 1),
                'coberturaValor' => $valorTotal <= 0
                    ? 0.0
                    : round((float) $row['valor_cubierto'] * 100 / $valorTotal, 1),
            ];
        }

        return $out;
    }

    /**
     * Paquetes cuyo frente cambió o desapareció, agrupados por obra para la ruta BI.
     *
     * Reproduce PlanFechasService::frentesDisponibles()/desfases() sobre el conjunto autorizado:
     * los encabezados sin unique_id se anclan a la hoja más temprana de su subárbol y el primer
     * encabezado que resuelve cada unique_id gana.
     *
     * @return array<int, int>
     */
    public function paquetesDesactualizadosPorProyecto(MultiProjectScope $scope): array
    {
        $ids = $scope->projectIds();
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $weeks = $this->db->queryForProjects(
            $scope,
            "SELECT project_id, MAX(Semana) AS Semana
             FROM semanas_activas
             WHERE project_id IN ({$ph})
             GROUP BY project_id",
            $ids,
        )->fetchAll(\PDO::FETCH_ASSOC);

        $weekByProject = [];
        foreach ($weeks as $row) {
            $weekByProject[(int) $row['project_id']] = (int) $row['Semana'];
        }

        $currentByProject = [];
        if ($weekByProject !== []) {
            $weekValues = array_values(array_unique(array_values($weekByProject)));
            sort($weekValues, SORT_NUMERIC);
            $weekPh = implode(',', array_fill(0, count($weekValues), '?'));
            $programRows = $this->db->queryForProjects(
                $scope,
                "SELECT project_id, Semana, unique_id, Fecha_Inicio, Titulo, Id
                 FROM programa_consolidado
                 WHERE project_id IN ({$ph})
                   AND Semana IN ({$weekPh})
                   AND Fecha_Inicio IS NOT NULL
                 ORDER BY project_id, Fecha_Inicio ASC, unique_id ASC",
                array_merge($ids, $weekValues),
            )->fetchAll(\PDO::FETCH_ASSOC);

            $rowsByProject = [];
            foreach ($programRows as $row) {
                $projectId = (int) $row['project_id'];
                if (!isset($weekByProject[$projectId]) || (int) $row['Semana'] !== $weekByProject[$projectId]) {
                    continue;
                }
                $rowsByProject[$projectId][] = $row;
            }

            foreach ($rowsByProject as $projectId => $rows) {
                $leaves = array_values(array_filter(
                    $rows,
                    static fn(array $row): bool => (int) $row['Titulo'] !== 1 && $row['unique_id'] !== null,
                ));
                foreach ($rows as $row) {
                    if ((int) $row['Titulo'] !== 1) {
                        continue;
                    }
                    $uniqueId = $row['unique_id'] === null ? null : (int) $row['unique_id'];
                    $fecha = (string) $row['Fecha_Inicio'];
                    if ($uniqueId === null) {
                        $prefix = (string) $row['Id'] . '.';
                        foreach ($leaves as $leaf) {
                            if (str_starts_with((string) $leaf['Id'], $prefix)) {
                                $uniqueId = (int) $leaf['unique_id'];
                                $fecha = (string) $leaf['Fecha_Inicio'];
                                break;
                            }
                        }
                    }
                    if ($uniqueId !== null && !isset($currentByProject[$projectId][$uniqueId])) {
                        $currentByProject[$projectId][$uniqueId] = $fecha;
                    }
                }
            }
        }

        $frontRows = $this->db->queryForProjects(
            $scope,
            "SELECT f.project_id, f.unique_id, f.fecha_ancla
             FROM pdc_paquete_frente f
             WHERE f.project_id IN ({$ph})",
            $ids,
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = array_fill_keys($ids, 0);
        foreach ($frontRows as $row) {
            $projectId = (int) $row['project_id'];
            $actual = $currentByProject[$projectId][(int) $row['unique_id']] ?? null;
            if ($actual === null || $actual !== (string) $row['fecha_ancla']) {
                $out[$projectId]++;
            }
        }

        return $out;
    }

    /**
     * Cuantos paquetes del proyecto NO puede ver el tablero, y por que.
     *
     * Un plan que calla lo que no sabe es peor que uno incompleto que lo declara: sin este numero, un
     * tablero vacio se lee igual que «no hay nada vencido».
     *
     * El denominador son solo los paquetes que generan proceso de contratacion. Nomina, imprevistos y
     * consumo directo no se le compran a nadie y nunca van a tener fecha; contarlos seria una alarma
     * que no se puede apagar haciendo las cosas bien.
     *
     * Falta `duracion_ref` NO entra aqui a proposito: `PlanFechasService::calcular()` ya le da fechas
     * a esos paquetes por la mediana de su tipo (`duracion_provisional = 1`), asi que aparecen en el
     * tablero como cualquier otro. Lo que deja a un paquete fuera es no tener plan.
     *
     * @return array{paquetes: int, sinFrente: int, sinCalcular: int}
     */
    private function paquetesSinFechas(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT (f.paquete_id IS NOT NULL) AS amarrado,
                    (pp.fecha_arranque IS NOT NULL) AS con_plan
             FROM (SELECT DISTINCT api.project_id, api.paquete_id FROM pdc_insumo_paquete api
                    WHERE api.project_id = ? AND api.paquete_id IS NOT NULL) a
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             LEFT JOIN (
                 SELECT pf.project_id, pf.paquete_id
                 FROM pdc_paquete_frente pf
                 WHERE pf.project_id = ?
             ) f ON f.project_id = a.project_id AND f.paquete_id = p.id
             LEFT JOIN (
                 SELECT plan.project_id, plan.paquete_id, plan.fecha_arranque
                 FROM pdc_plan_paquete plan
                 WHERE plan.project_id = ?
             ) pp ON pp.project_id = a.project_id AND pp.paquete_id = p.id
             WHERE p.activo = 1
               AND p.modalidad_contratacion IN (' . PlanFechasService::modalidadesConProcesoSql() . ')',
            [$projectId, $projectId, $projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $sinFrente = 0;
        $sinCalcular = 0;
        foreach ($rows as $r) {
            if ((int) $r['con_plan'] === 1) {
                continue;
            }
            // Amarrado sin recalcular es un caso distinto de sin amarrar: el primero se arregla con
            // un boton y el segundo exige decidir a que frente pertenece.
            if ((int) $r['amarrado'] === 1) {
                $sinCalcular++;
            } else {
                $sinFrente++;
            }
        }

        return [
            'paquetes' => $sinFrente + $sinCalcular,
            'sinFrente' => $sinFrente,
            'sinCalcular' => $sinCalcular,
        ];
    }
}
