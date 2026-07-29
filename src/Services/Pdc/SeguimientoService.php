<?php

namespace App\Services\Pdc;

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
    public function pasosDePaquete(int $projectId, int $paqueteId): array
    {
        $arranque = $this->db->query(
            'SELECT fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
            [$projectId, $paqueteId],
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
             WHERE project_id = ? AND paquete_id = ?
             ORDER BY orden',
            [$projectId, $paqueteId],
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
    ): array {
        if ($fechaReal !== null) {
            // Formato estricto: `strtotime` aceptaria '15/04/2026' y lo interpretaria al reves, y esa
            // fecha silenciosamente equivocada no la detecta nadie hasta que la proyeccion sale rara.
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $fechaReal);
            if ($d === false || $d->format('Y-m-d') !== $fechaReal) {
                return ['ok' => false, 'code' => 'FECHA_INVALIDA', 'mensaje' => 'La fecha debe venir como AAAA-MM-DD.'];
            }
        }

        // La terna (proyecto, paquete, paso) se comprueba junta. Que el paso exista en el catalogo no
        // dice nada: lo que hay que garantizar es que ESE paso pertenece al plan de ESE paquete en
        // ESTE proyecto. Sin esto, un paquete_id equivocado escribiria en el plan de otro.
        $existe = $this->db->query(
            'SELECT 1 FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
            [$projectId, $paqueteId, $pasoId],
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
              WHERE project_id = ? AND paquete_id = ? AND paso_id = ?',
            [
                $fechaReal,
                $fechaReal === null ? '' : $usuario,
                $fechaReal === null ? null : (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                $projectId, $paqueteId, $pasoId,
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
            'SELECT pp.paquete_id, pp.fecha_arranque, p.nombre, f.frente_nombre,
                    pp.responsable_user_id, u.nombre AS responsable_nombre,
                    u.activo AS responsable_activo, pm.user_id AS responsable_miembro
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             LEFT JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             LEFT JOIN project_members pm ON pm.project_id = pp.project_id AND pm.user_id = pp.responsable_user_id
             WHERE pp.project_id = ? AND p.activo = 1 AND pp.fecha_arranque IS NOT NULL
             ORDER BY pp.fecha_arranque ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Una sola consulta para todos los pasos del proyecto: pedirlos paquete por paquete serian
        // cientos de viajes a la base para pintar una pantalla.
        $porPaquete = [];
        foreach ($this->db->query(
            'SELECT paquete_id, orden, paso, dias, fecha_fin, fecha_real
             FROM pdc_plan_paso WHERE project_id = ? ORDER BY paquete_id, orden',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $porPaquete[(int) $r['paquete_id']][] = $r;
        }

        $hoy = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $out = [];
        foreach ($cabeceras as $c) {
            $paqueteId = (int) $c['paquete_id'];
            $pasos = $porPaquete[$paqueteId] ?? [];
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
            $out[] = [
                'paqueteId' => $paqueteId,
                'nombre' => (string) $c['nombre'],
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
            'SELECT ps.paquete_id, ps.paso_id, ps.orden, ps.paso, ps.fecha_fin,
                    COALESCE(g.clave, ps.paso) AS clave,
                    p.nombre AS paquete, f.frente_nombre,
                    pp.responsable_user_id, u.nombre AS responsable_nombre
             FROM pdc_plan_paso ps
             JOIN pdc_plan_paquete pp ON pp.project_id = ps.project_id AND pp.paquete_id = ps.paquete_id
             JOIN general_paquetes_contratacion p ON p.id = ps.paquete_id
             LEFT JOIN general_pasos_contratacion g ON g.id = ps.paso_id
             LEFT JOIN pdc_paquete_frente f ON f.project_id = ps.project_id AND f.paquete_id = ps.paquete_id
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
            $filas[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'paquete' => (string) $r['paquete'],
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
             FROM (SELECT DISTINCT paquete_id FROM pdc_insumo_paquete
                    WHERE project_id = ? AND paquete_id IS NOT NULL) a
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             LEFT JOIN pdc_paquete_frente f ON f.project_id = ? AND f.paquete_id = p.id
             LEFT JOIN pdc_plan_paquete pp ON pp.project_id = ? AND pp.paquete_id = p.id
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
