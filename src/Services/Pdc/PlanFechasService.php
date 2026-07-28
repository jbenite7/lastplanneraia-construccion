<?php

namespace App\Services\Pdc;

/**
 * A4 · Convierte el amarre paquete↔cronograma en fechas.
 *
 * El cronograma no es el presupuesto a otra escala: tiene su propio árbol de frentes, con el
 * capítulo embebido en HTML dentro del campo `Actividad`. Los frentes (encabezados, `Titulo = 1`)
 * son los que hablan el idioma de los paquetes: ESTRUCTURA, MAMPOSTERÍA, RED ELÉCTRICA.
 */
class PlanFechasService
{
    /**
     * Similitud mínima de nombre para proponer un frente (Jaccard sobre palabras).
     * Un paquete con 2 palabras de ruido y 1 de coincidencia con un frente de una sola palabra
     * («TEST A4 ESTRUCTURA» vs. «ESTRUCTURA») da Jaccard = 1/3 ≈ 0,3333: el umbral tiene que
     * quedar por debajo de ese valor o ese caso —representativo de paquetes con prefijos que el
     * strip de tipo de negociación no cubre— nunca propondría nada.
     */
    private const SIMILITUD_MINIMA = 0.33;

    /**
     * Modalidades que generan proceso de contratación y por tanto entran al plan de fechas.
     * `no_contratable` (nómina, imprevistos) y `consumo_directo` (ferretería contra almacén) quedan
     * fuera siempre: no se le compran a nadie, o el gasto se controla contra almacén sin ningún
     * paso de contratación que programar. `sugerirFrentes()` ya las excluía de las propuestas; esta
     * constante es la misma regla aplicada también en la escritura (`amarrar()`) y en el cálculo y
     * la lectura del plan (`calcular()`/`plan()`), para que un amarre manual o un cambio posterior
     * de modalidad no metan un paquete no contratable al plan de fechas.
     */
    private const MODALIDADES_CON_PROCESO = ['contrato', 'orden_compra'];

    /**
     * Duración de respaldo (días) cuando ni el paquete tiene un desglose completo propio ni existe
     * ninguna mediana calculable para su tipo de negociación (ningún paquete activo de ese tipo
     * tiene un desglose completo en `general_dias_procesos_contratacion`). En DAPORTO el único tipo
     * que llega a necesitarlo es "consumibles": ningún paquete de ese tipo tiene `duracion_ref`, así
     * que su mediana nunca existe y este número se usa con datos reales. 90 días (~3 meses) es una
     * cifra conservadora heredada del ejercicio DAPORTO para no subestimar un proceso del que no hay
     * ninguna referencia; se documenta aquí para que quede claro que es un valor de negocio, no un
     * accidente de código, y para poder ajustarlo si aparece mejor evidencia.
     */
    private const DURACION_FALLBACK_DIAS = 90;

    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * Lista `'contrato','orden_compra'` lista para pegar en un `IN (...)` SQL. Son los dos únicos
     * valores fijos del enum `modalidad_contratacion` que generan proceso — no hay entrada de
     * usuario en el valor, así que interpolarlos directo en la consulta es seguro; se centraliza
     * aquí para que `calcular()` y `plan()` no dupliquen el literal ni puedan divergir de
     * `MODALIDADES_CON_PROCESO`.
     */
    private static function modalidadesConProcesoSql(): string
    {
        return "'" . implode("','", self::MODALIDADES_CON_PROCESO) . "'";
    }

    /**
     * Separa el nombre del frente del capítulo que el cronograma embebe en un `<small>`.
     * Entrada: `<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>`
     */
    public static function limpiarActividad(string $html): array
    {
        $capitulo = '';
        if (preg_match('/\[Cap[íi]tulo:\s*([^\]]+)\]/u', $html, $m) === 1) {
            $capitulo = trim($m[1]);
        }
        $sinSmall = preg_replace('/<small>.*?<\/small>/su', '', $html);
        $nombre = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $sinSmall)));
        return ['nombre' => rtrim($nombre, ' ,'), 'capitulo' => $capitulo];
    }

    /** Frentes de obra de la semana activa, del más temprano al más tardío. */
    public function frentesDisponibles(int $projectId): array
    {
        return $this->semanaYFrentes($projectId)['frentes'];
    }

    /**
     * La semana activa y sus frentes, de una sola pasada.
     *
     * `amarrar()` necesita las dos cosas: los frentes para validar el destino y la semana para
     * guardarla en `semana_origen`. Calcularlas por separado significaba pedir dos veces el mismo
     * `MAX(Semana)` en un mismo amarre y, en teoría, leer dos semanas distintas si el consolidado
     * cambiaba en medio (inalcanzable en la práctica: sin semanas no hay frentes y `amarrar()` sale
     * antes con FRENTE_INVALIDO). Devolverlas juntas quita el segundo viaje y la incoherencia.
     *
     * @return array{semana: ?int, frentes: list<array{uniqueId: int, nombre: string, capitulo: string, fechaInicio: string}>}
     */
    private function semanaYFrentes(int $projectId): array
    {
        $semana = $this->db->query(
            'SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?',
            [$projectId],
        )->fetchColumn();
        if ($semana === false || $semana === null) {
            return ['semana' => null, 'frentes' => []];
        }
        $rows = $this->db->query(
            'SELECT unique_id, Actividad, Fecha_Inicio FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND Titulo = 1 AND unique_id IS NOT NULL
               AND Fecha_Inicio IS NOT NULL
             ORDER BY Fecha_Inicio ASC, unique_id ASC',
            [$projectId, (int) $semana],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'semana' => (int) $semana,
            'frentes' => array_map(static function (array $r): array {
                $l = self::limpiarActividad((string) $r['Actividad']);
                return [
                    'uniqueId' => (int) $r['unique_id'],
                    'nombre' => $l['nombre'],
                    'capitulo' => $l['capitulo'],
                    'fechaInicio' => (string) $r['Fecha_Inicio'],
                ];
            }, $rows),
        ];
    }

    /** Palabras normalizadas de un nombre, sin el prefijo de tipo de negociación (no dice el oficio). */
    private static function tokens(string $s): array
    {
        $limpio = preg_replace('/^(Sum \+ Inst|Suministro|M\. de O)\s*/u', '', $s);
        return array_values(array_filter(explode(' ', MaestroInsumosService::normalizar((string) $limpio))));
    }

    /**
     * Mejor frente para un conjunto de palabras (Jaccard sobre `tok`). Entre empates gana el que
     * arranca antes: es el que fija la fecha límite del contrato. Null si nada llega al umbral.
     */
    private function mejorFrente(array $tp, array $frentesTok): ?array
    {
        // Dedup ANTES de contar: `array_intersect` conserva los duplicados del primer array, y una
        // palabra vacía repetida (p. ej. «DE») no puede valer como dos aciertos frente a un frente
        // de una sola palabra — el denominador ya deduplica, así que el numerador también debe.
        $tpUniq = array_unique($tp);
        $mejor = null;
        $mejorPunt = 0.0;
        foreach ($frentesTok as $f) {
            $comunes = count(array_intersect($tpUniq, $f['tok']));
            if ($comunes === 0) {
                continue;
            }
            $punt = $comunes / max(1, count(array_unique(array_merge($tpUniq, $f['tok']))));
            if ($punt > $mejorPunt || ($punt === $mejorPunt && $mejor !== null && $f['fechaInicio'] < $mejor['fechaInicio'])) {
                $mejor = $f;
                $mejorPunt = $punt;
            }
        }
        if ($mejor === null || $mejorPunt < self::SIMILITUD_MINIMA) {
            return null;
        }
        return ['frente' => $mejor, 'punt' => $mejorPunt];
    }

    /** Id de la versión activa del proyecto (o de la indicada), o null si no existe. */
    private function versionActivaId(int $projectId, ?int $versionId): ?int
    {
        $sql = $versionId === null
            ? 'SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1'
            : 'SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?';
        $params = $versionId === null ? [$projectId] : [$projectId, $versionId];
        $row = $this->db->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : (int) $row['id'];
    }

    /**
     * Subcapítulos donde cada paquete (de la lista dada) tiene insumos, en esta versión del
     * presupuesto. Recorre `pdc_insumo_paquete` → `pdc_presupuesto_apu_insumos` → sube por
     * `codigo_padre` hasta el primer `subcapitulo`: el mismo recorrido de
     * `PaquetesService::actividadDominantePorInsumo()`, duplicado aquí (sin llamar ni tocar ese
     * archivo) porque este servicio no depende de `PaquetesService`.
     *
     * @param list<int> $idsPaquetes
     * @return array<int, list<string>> paqueteId => nombres de subcapítulo (sin duplicados)
     */
    private function subcapitulosDePaquete(int $projectId, int $versionId, array $idsPaquetes): array
    {
        if ($idsPaquetes === []) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($idsPaquetes), '?'));
        $asignaciones = $this->db->query(
            "SELECT paquete_id, descripcion_norm, unidad FROM pdc_insumo_paquete
             WHERE project_id = ? AND paquete_id IN ({$marcadores})",
            array_merge([$projectId], $idsPaquetes),
        )->fetchAll(\PDO::FETCH_ASSOC);
        if ($asignaciones === []) {
            return [];
        }

        // Un solo SELECT de los ítems y de los insumos del APU: la rama se arma en memoria en vez
        // de una consulta por insumo (mismo criterio que actividadDominantePorInsumo()).
        $items = $this->db->query(
            'SELECT id, codigo, codigo_padre, tipo_fila, descripcion FROM pdc_presupuesto_items
             WHERE project_id = ? AND version_id = ?',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $porId = [];
        $porCodigo = [];
        foreach ($items as $it) {
            $porId[(int) $it['id']] = $it;
            $porCodigo[(string) $it['codigo']] = $it;
        }

        // Un insumo puede vivir en varios ítems del APU (40 de 394 en DAPORTO): se agrupa por
        // (descripción, unidad, item_id) y se ordena por SUM(valor_total) DESC, igual que
        // `PaquetesService::actividadDominantePorInsumo()`, para quedarse con el de mayor valor y no
        // con el primero que devuelva MySQL (orden físico, no semántico).
        $apu = $this->db->query(
            'SELECT descripcion, unidad, item_id, SUM(valor_total) AS valor
             FROM pdc_presupuesto_apu_insumos
             WHERE project_id = ? AND version_id = ?
             GROUP BY descripcion, unidad, item_id
             ORDER BY valor DESC',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        // La clave (descripción normalizada + unidad) es la misma que usa `pdc_insumo_paquete`,
        // que ya la guarda normalizada; aquí se normaliza la del APU para poder cruzarlas.
        $itemPorClave = [];
        foreach ($apu as $a) {
            $clave = MaestroInsumosService::normalizar((string) $a['descripcion'])
                . '@@' . mb_strtoupper(trim((string) $a['unidad']));
            if (!isset($itemPorClave[$clave])) {
                $itemPorClave[$clave] = (int) $a['item_id']; // el ORDER BY garantiza que es el de mayor valor
            }
        }

        $subcapPorPaquete = [];
        foreach ($asignaciones as $asig) {
            $clave = (string) $asig['descripcion_norm'] . '@@' . mb_strtoupper(trim((string) $asig['unidad']));
            $itemId = $itemPorClave[$clave] ?? null;
            if ($itemId === null) {
                continue; // el insumo asignado no aparece en esta versión del presupuesto
            }
            $actual = $porId[$itemId] ?? null;
            $sub = null;
            $guard = 0;
            while ($actual !== null && $guard++ < 12) {
                if ($actual['tipo_fila'] === 'subcapitulo') {
                    $sub = (string) $actual['descripcion'];
                    break;
                }
                if ($actual['tipo_fila'] === 'capitulo') {
                    break; // se llegó al capítulo sin pasar por un subcapítulo
                }
                $padre = $actual['codigo_padre'];
                $actual = $padre !== null ? ($porCodigo[(string) $padre] ?? null) : null;
            }
            if ($sub !== null) {
                $subcapPorPaquete[(int) $asig['paquete_id']][$sub] = true;
            }
        }
        return array_map('array_keys', $subcapPorPaquete);
    }

    /**
     * Propone un frente para cada paquete activo **del proyecto**: los que tienen insumos
     * asignados en `pdc_insumo_paquete` para ese `project_id` (no el catálogo global completo), y
     * cuya `modalidad_contratacion` genera proceso de contratación (`contrato`, `orden_compra`) —
     * `no_contratable` y `consumo_directo` no entran nunca al plan de fechas.
     *
     * Señal 1 — el nombre: «Sum + Inst ESTRUCTURA» contra el frente «ESTRUCTURA». Se descarta el
     * prefijo de tipo de negociación, que no dice nada del oficio.
     * Señal 2 — la rama: los subcapítulos donde el paquete tiene insumos, contra el nombre del
     * frente. Entre varios candidatos gana el que arranca antes: es el que marca la fecha límite.
     */
    public function sugerirFrentes(int $projectId, ?int $versionId = null): array
    {
        $frentes = $this->frentesDisponibles($projectId);
        if ($frentes === []) {
            return [];
        }
        $paquetes = $this->db->query(
            'SELECT DISTINCT p.id, p.nombre
             FROM general_paquetes_contratacion p
             JOIN pdc_insumo_paquete ip ON ip.paquete_id = p.id
             WHERE p.activo = 1 AND ip.project_id = ?
               AND p.modalidad_contratacion IN (\'contrato\', \'orden_compra\')',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $frentesTok = [];
        foreach ($frentes as $f) {
            $frentesTok[] = $f + ['tok' => self::tokens($f['nombre'])];
        }

        $out = [];
        foreach ($paquetes as $p) {
            $tp = self::tokens((string) $p['nombre']);
            if ($tp === []) {
                continue;
            }
            $m = $this->mejorFrente($tp, $frentesTok);
            if ($m === null) {
                continue;
            }
            $mejor = $m['frente'];
            $out[(int) $p['id']] = [
                'uniqueId' => $mejor['uniqueId'],
                'nombre' => $mejor['nombre'],
                'fechaInicio' => $mejor['fechaInicio'],
                'origen' => 'similitud',
                'confianza' => $m['punt'] >= 0.7 ? 'alta' : 'media',
                'evidencia' => sprintf(
                    'El nombre del paquete coincide con el frente «%s» del cronograma (arranca %s).',
                    $mejor['nombre'],
                    $mejor['fechaInicio'],
                ),
            ];
        }

        // Señal 2: la rama, solo para los que el nombre no resolvió.
        $sinMatch = array_values(array_diff(
            array_map(static fn (array $p): int => (int) $p['id'], $paquetes),
            array_keys($out),
        ));
        $vid = $this->versionActivaId($projectId, $versionId);
        if ($sinMatch !== [] && $vid !== null) {
            $subcapPorPaquete = $this->subcapitulosDePaquete($projectId, $vid, $sinMatch);
            foreach ($sinMatch as $paqueteId) {
                $subs = $subcapPorPaquete[$paqueteId] ?? [];
                if ($subs === []) {
                    continue; // sin insumos vinculados en esta versión: la señal no aplica
                }
                $mejorGlobal = null;
                $mejorSub = null;
                foreach ($subs as $sub) {
                    $tp = self::tokens($sub);
                    if ($tp === []) {
                        continue;
                    }
                    $m = $this->mejorFrente($tp, $frentesTok);
                    if ($m === null) {
                        continue;
                    }
                    // Entre varios subcapítulos con frente candidato, gana el que arranca antes.
                    if ($mejorGlobal === null || $m['frente']['fechaInicio'] < $mejorGlobal['frente']['fechaInicio']) {
                        $mejorGlobal = $m;
                        $mejorSub = $sub;
                    }
                }
                if ($mejorGlobal === null) {
                    continue;
                }
                $out[$paqueteId] = [
                    'uniqueId' => $mejorGlobal['frente']['uniqueId'],
                    'nombre' => $mejorGlobal['frente']['nombre'],
                    'fechaInicio' => $mejorGlobal['frente']['fechaInicio'],
                    'origen' => 'rama',
                    'confianza' => 'media',
                    'evidencia' => sprintf(
                        'Sus insumos están en el subcapítulo «%s», que en el cronograma arranca el %s.',
                        $mejorSub,
                        $mejorGlobal['frente']['fechaInicio'],
                    ),
                ];
            }
        }

        return $out;
    }

    /**
     * Amarra un paquete a un frente del cronograma.
     *
     * Guarda la fecha que el frente tenía en este momento: es lo que permite detectar más adelante
     * que la obra se reprogramó y el plan quedó viejo. La procedencia funciona como en los insumos:
     * aceptar la propuesta conserva la capa que la produjo (acierto), elegir a mano es «humano».
     */
    public function amarrar(int $projectId, int $paqueteId, int $uniqueId, string $usuario, array $procedencia = []): array
    {
        // Una sola lectura del cronograma para las dos cosas que hacen falta aquí: el frente destino
        // y la semana activa que se guarda en `semana_origen` (ver semanaYFrentes()).
        ['semana' => $semana, 'frentes' => $frentes] = $this->semanaYFrentes($projectId);
        $frente = null;
        foreach ($frentes as $f) {
            if ($f['uniqueId'] === $uniqueId) {
                $frente = $f;
                break;
            }
        }
        if ($frente === null) {
            // Sin semanas activas la lista viene vacía y se sale por aquí: más abajo `$semana` ya no
            // puede ser null.
            return ['ok' => false, 'code' => 'FRENTE_INVALIDO'];
        }
        $paquete = $this->db->query(
            'SELECT id, modalidad_contratacion FROM general_paquetes_contratacion WHERE id = ? AND activo = 1',
            [$paqueteId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($paquete === false) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }
        // `no_contratable` y `consumo_directo` no generan proceso de contratación: amarrarlos a un
        // frente metería siete pasos y una fecha a un paquete que, por diseño, no se le compra a
        // nadie. `sugerirFrentes()` ya no los propone, pero un amarre manual pasaba por aquí sin
        // que nada lo detuviera.
        if (!in_array($paquete['modalidad_contratacion'], self::MODALIDADES_CON_PROCESO, true)) {
            return ['ok' => false, 'code' => 'MODALIDAD_NO_CONTRATABLE'];
        }

        $origen = in_array($procedencia['origen'] ?? '', ['similitud', 'rama'], true) ? $procedencia['origen'] : 'humano';
        $delMotor = $origen !== 'humano';

        $this->db->beginTransaction();
        try {
            // Importante 1 del review final: si el paquete ya estaba amarrado a OTRO frente, el plan
            // calculado (cabecera + pasos) se restó hacia atrás desde la fecha de ESE frente anterior.
            // Guardar solo el amarre nuevo y dejar la fila vieja de `pdc_plan_paquete` intacta produce
            // una fila que dice «Frente: X» junto a un arranque calculado contra Y — y ni `plan()` ni
            // `desfases()` lo detectan, porque el amarre ya quedó al día con el cronograma.
            //
            // Importante 2 del review final de A4: comparar solo `unique_id` no basta. Reamarrar al
            // MISMO frente después de que el cronograma lo movió no cambia el unique_id, pero sí la
            // fecha ancla — y con solo esa comparación el plan calculado se queda con la fecha vieja
            // sin que nada lo avise (`desfases()` deja de reportarlo porque el amarre ya coincide con
            // el cronograma). Por eso se compara contra lo que el PLAN CALCULADO tiene guardado
            // (`pdc_plan_paquete`, no `pdc_paquete_frente`): unique_id distinto O fecha_ancla distinta
            // invalidan. Se detecta ANTES del upsert de abajo, porque después ya no queda registro de
            // contra qué estaba calculado el plan, y va DENTRO de la transacción para no competir con
            // un `calcular()` concurrente que reescriba `pdc_plan_paquete` entre la lectura y el DELETE.
            $planCalculado = $this->db->query(
                'SELECT unique_id, fecha_ancla FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
                [$projectId, $paqueteId],
            )->fetch(\PDO::FETCH_ASSOC);
            $reamarreInvalida = $planCalculado !== false
                && ((int) $planCalculado['unique_id'] !== $uniqueId
                    || (string) $planCalculado['fecha_ancla'] !== $frente['fechaInicio']);

            $this->db->query(
                'INSERT INTO pdc_paquete_frente
                    (project_id, paquete_id, unique_id, frente_nombre, fecha_ancla, semana_origen,
                     origen, confianza, evidencia, confirmado_humano, asignado_por, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE unique_id = VALUES(unique_id), frente_nombre = VALUES(frente_nombre),
                    fecha_ancla = VALUES(fecha_ancla), semana_origen = VALUES(semana_origen),
                    origen = VALUES(origen), confianza = VALUES(confianza), evidencia = VALUES(evidencia),
                    confirmado_humano = VALUES(confirmado_humano), asignado_por = VALUES(asignado_por),
                    updated_at = NOW()',
                [
                    $projectId, $paqueteId, $uniqueId, $frente['nombre'], $frente['fechaInicio'], $semana,
                    $origen,
                    $delMotor && in_array($procedencia['confianza'] ?? '', ['alta', 'media', 'baja'], true) ? $procedencia['confianza'] : null,
                    $delMotor ? mb_substr((string) ($procedencia['evidencia'] ?? ''), 0, 500) : '',
                    (!$delMotor || ($procedencia['confirmado'] ?? false) === true) ? 1 : 0,
                    $usuario,
                ],
            );

            if ($reamarreInvalida) {
                // El plan viejo quedó calculado contra un frente que ya no es el amarrado: se
                // invalida entero (pasos primero, por la FK conceptual con la cabecera). El paquete
                // cae a "amarrado, pendiente de calcular" — bloque que la SPA ya distingue de "sin
                // frente" — hasta el próximo «Recalcular», que es un acto explícito de quien lo vea.
                $this->db->query('DELETE FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ?', [$projectId, $paqueteId]);
                $this->db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', [$projectId, $paqueteId]);
            }

            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true];
    }

    /** Amarres vigentes del proyecto, indexados por paquete. */
    public function amarres(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT paquete_id, unique_id, frente_nombre, fecha_ancla, origen, confianza, confirmado_humano
             FROM pdc_paquete_frente WHERE project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['paquete_id']] = [
                'uniqueId' => (int) $r['unique_id'],
                'frenteNombre' => (string) $r['frente_nombre'],
                'fechaAncla' => (string) $r['fecha_ancla'],
                'origen' => (string) $r['origen'],
                'confianza' => $r['confianza'],
                'confirmadoHumano' => (int) $r['confirmado_humano'] === 1,
            ];
        }
        return $out;
    }

    /**
     * Los siete pasos del proceso de contratación, en orden, con la columna del catálogo legacy que
     * guarda su duración. El último termina en la fecha en que el paquete se necesita en obra.
     */
    public const PASOS = [
        ['paso' => 'Elaboración de pliegos', 'col' => 'diasElaboracionPliegos'],
        ['paso' => 'Entrega de pliegos', 'col' => 'diasEntregaPliegos'],
        ['paso' => 'Recibo de propuestas', 'col' => 'diasReciboPropuestas'],
        ['paso' => 'Cuadros comparativos', 'col' => 'diasCuadrosComparativos'],
        ['paso' => 'Legalización', 'col' => 'diasLegalizacionContrato'],
        ['paso' => 'Fabricación', 'col' => 'diasFabricacion'],
        ['paso' => 'Insumos en obra', 'col' => 'diasInsumosObra'],
    ];

    /**
     * Calcula el plan de todos los paquetes amarrados: resta hacia atrás desde la fecha del frente.
     *
     * En días calendario, porque así están escritos los números del catálogo: quien puso «25 días de
     * cuadros comparativos» pensaba en semanas de calendario, no en jornadas laborales.
     *
     * Convención de fronteras entre pasos (contrato con B1 · Seguimiento — no cambiar sin migrar
     * los datos ya guardados; la misma nota está en la migración 20260728_pdc_v2_plan_fechas.sql):
     * el intervalo de cada paso es MEDIO ABIERTO, `[fecha_inicio, fecha_fin)`. El cursor de abajo
     * avanza `+dias` y esa fecha es a la vez el fin de un paso y el inicio del siguiente:
     * `fecha_fin` es la frontera en la que se entrega el testigo, no el último día trabajado. De ahí
     * salen las tres propiedades que el consumidor puede dar por ciertas:
     *   1. `dias` = fecha_fin − fecha_inicio, sin sumar ni restar uno.
     *   2. la suma de los siete `dias` es exactamente `fecha_arranque` → `fecha_ancla`.
     *   3. la `fecha_fin` del último paso ES la `fecha_ancla` (el día que se necesita en obra).
     * Al comparar avance real contra programado, un paso va a tiempo si se cerró ANTES de su
     * `fecha_fin`. Leerla como «último día del paso» —o contar `fin − inicio + 1`— cuenta dos veces
     * cada frontera e infla el proceso en siete días.
     */
    public function calcular(int $projectId, string $usuario): array
    {
        $amarres = $this->amarres($projectId);
        if ($amarres === []) {
            return ['ok' => true, 'calculados' => 0, 'sinDuracion' => 0];
        }
        $medianas = $this->medianasPorTipo();
        $calculados = 0;
        $sinDuracion = 0;

        foreach ($amarres as $paqueteId => $a) {
            $paq = $this->db->query(
                "SELECT p.id, p.tipo_negociacion, p.duracion_ref, d.diasElaboracionPliegos, d.diasEntregaPliegos,
                        d.diasReciboPropuestas, d.diasCuadrosComparativos, d.diasLegalizacionContrato,
                        d.diasFabricacion, d.diasInsumosObra
                 FROM general_paquetes_contratacion p
                 LEFT JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
                 WHERE p.id = ? AND p.activo = 1
                   AND p.modalidad_contratacion IN (" . self::modalidadesConProcesoSql() . ')',
                [$paqueteId],
            )->fetch(\PDO::FETCH_ASSOC);
            if ($paq === false) {
                // Paquete inactivo, o cuya modalidad ya no genera proceso de contratación (cambió
                // después de amarrarlo): no se calcula plan para él. Su cabecera vieja, si existe,
                // queda huérfana en pdc_plan_paquete — plan() la filtra por su cuenta.
                continue;
            }

            // «Sin duración» se decide por las columnas del desglose, no por `duracion_ref`: así
            // se cubren de una sola vez los tres casos silenciosos (duracion_ref NULL, apuntando a
            // una fila borrada, o a una fila con algún `dias*` NULL) porque los tres producen el
            // mismo resultado en el LEFT JOIN — al menos una columna NULL — sin necesidad de
            // distinguirlos.
            $desgloseCompleto = true;
            foreach (self::PASOS as $p) {
                if ($paq[$p['col']] === null) {
                    $desgloseCompleto = false;
                    break;
                }
            }
            $provisional = !$desgloseCompleto;

            if ($provisional) {
                $sinDuracion++;
                $total = $medianas[$paq['tipo_negociacion']] ?? self::DURACION_FALLBACK_DIAS;
                // Sin desglose real, la mediana se reparte con los pesos medidos sobre el catálogo
                // (`PESOS_REPARTO`), no con una intuición del reparto típico.
                $dias = self::repartirMediana($total);
            } else {
                $dias = [];
                foreach (self::PASOS as $p) {
                    $dias[] = (int) $paq[$p['col']];
                }
            }
            $total = array_sum($dias);

            $ancla = new \DateTimeImmutable($a['fechaAncla']);
            $cursor = $ancla->modify(sprintf('-%d days', $total));
            $arranque = $cursor->format('Y-m-d');

            // La cabecera y sus siete pasos son una sola unidad: si algo falla a mitad de camino,
            // un rollback evita que quede una cabecera con `dias_totales = N` y menos de siete pasos
            // (o pasos de un cálculo anterior mezclados con uno nuevo) sin que nadie lo note.
            $this->db->beginTransaction();
            try {
                $this->db->query(
                    'INSERT INTO pdc_plan_paquete
                        (project_id, paquete_id, unique_id, fecha_ancla, fecha_arranque, dias_totales,
                         duracion_ref, duracion_provisional, responsable, calculado_por, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE unique_id = VALUES(unique_id), fecha_ancla = VALUES(fecha_ancla),
                        fecha_arranque = VALUES(fecha_arranque), dias_totales = VALUES(dias_totales),
                        duracion_ref = VALUES(duracion_ref), duracion_provisional = VALUES(duracion_provisional),
                        calculado_por = VALUES(calculado_por), updated_at = NOW()',
                    [
                        // `responsable` se inserta como '' solo para la fila nueva (no hay ninguna
                        // previa que preservar en un INSERT). En un reemarre, el `ON DUPLICATE KEY
                        // UPDATE` de arriba NO lista `responsable` entre las columnas a actualizar,
                        // así que MySQL conserva el valor existente por su cuenta: no hace falta
                        // leerlo antes ni pasarlo de vuelta. No añadir `responsable` a esa cláusula
                        // sin querer perder esta garantía.
                        $projectId, $paqueteId, $a['uniqueId'], $a['fechaAncla'], $arranque, $total,
                        $paq['duracion_ref'], $provisional ? 1 : 0, '', $usuario,
                    ],
                );

                // Upsert, no DELETE + INSERT: B1 (Seguimiento) va a colgar la fecha REAL de cada
                // paso de estas mismas filas, y borrarlas en cada recálculo se llevaría por delante
                // el avance ya registrado sin ningún aviso. La clave única
                // (project_id, paquete_id, orden) hace que cada paso caiga siempre en su misma fila.
                //
                // El ON DUPLICATE KEY UPDATE lista SOLO las cuatro columnas programadas: lo que no
                // se lista, MySQL lo conserva. Es la misma garantía que protege `responsable` en
                // pdc_plan_paquete, y es lo que hace que las columnas que añada B1 sobrevivan sin
                // volver a tocar este servicio. No añadir aquí ninguna columna de seguimiento.
                foreach (self::PASOS as $i => $p) {
                    $ini = $cursor;
                    $cursor = $cursor->modify(sprintf('+%d days', $dias[$i]));
                    $this->db->query(
                        'INSERT INTO pdc_plan_paso (project_id, paquete_id, orden, paso, dias, fecha_inicio, fecha_fin)
                         VALUES (?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE paso = VALUES(paso), dias = VALUES(dias),
                            fecha_inicio = VALUES(fecha_inicio), fecha_fin = VALUES(fecha_fin)',
                        [$projectId, $paqueteId, $i, $p['paso'], $dias[$i], $ini->format('Y-m-d'), $cursor->format('Y-m-d')],
                    );
                }

                // Sobrantes: si el proceso se acortara (PASOS pasa de 7 a 5), las filas de los
                // órdenes que ya no existen quedarían huérfanas y `plan()` las seguiría devolviendo.
                // Los órdenes son el índice del foreach, contiguos desde 0, así que todo lo que esté
                // en el último válido o por encima sobra. Se borra DESPUÉS del upsert para no dejar
                // ni un instante al paquete sin sus pasos dentro de la transacción.
                $this->db->query(
                    'DELETE FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND orden >= ?',
                    [$projectId, $paqueteId, count(self::PASOS)],
                );
                $this->db->commit();
            } catch (\Throwable $t) {
                $this->db->rollBack();
                throw $t;
            }
            $calculados++;
        }
        return ['ok' => true, 'calculados' => $calculados, 'sinDuracion' => $sinDuracion];
    }

    /**
     * Mediana del proceso completo por tipo de negociación, entre los paquetes que sí tienen un
     * desglose COMPLETO de duración (las siete columnas `dias*`, todas no nulas).
     *
     * Un `dias*` NULL propaga NULL a la suma SQL (`col1 + ... + NULL = NULL`); `(int) null` en PHP
     * vale 0, así que sin este filtro un cero fantasma se colaba en la muestra y bajaba la mediana
     * de todo el tipo. El `IS NOT NULL` de cada columna se construye desde `self::PASOS` para no
     * duplicar la lista de columnas ni poder desalinearse con `calcular()`.
     */
    private function medianasPorTipo(): array
    {
        $suma = implode(' + ', array_map(static fn (array $p): string => 'd.' . $p['col'], self::PASOS));
        $completo = implode(' AND ', array_map(static fn (array $p): string => 'd.' . $p['col'] . ' IS NOT NULL', self::PASOS));
        $rows = $this->db->query(
            "SELECT p.tipo_negociacion t, ({$suma}) tot
             FROM general_paquetes_contratacion p
             JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
             WHERE p.activo = 1 AND {$completo}
             ORDER BY tot",
        )->fetchAll(\PDO::FETCH_ASSOC);
        $porTipo = [];
        foreach ($rows as $r) {
            $porTipo[(string) $r['t']][] = (int) $r['tot'];
        }
        $out = [];
        foreach ($porTipo as $t => $v) {
            $n = count($v);
            $out[$t] = (int) round($n % 2 === 1 ? $v[intdiv($n, 2)] : ($v[$n / 2 - 1] + $v[$n / 2]) / 2);
        }
        return $out;
    }

    /**
     * Peso de cada paso dentro del proceso completo, en el orden de `self::PASOS`.
     *
     * GENERADO — no editar a mano. Se produce con:
     *
     *     docker compose exec -T app php scripts/pdc/derivar-pesos-reparto.php
     *
     * Última generación: 2026-07-28, sobre las 205 filas de `general_dias_procesos_contratacion`
     * con desglose completo (las siete columnas `dias*` no nulas) y total mayor que cero.
     * Método: media de las proporciones fila a fila (cada fila del catálogo pesa igual, sin que los
     * procesos largos dominen la mezcla), que es la misma medida con la que se detectó la
     * desviación de los valores anteriores.
     *
     * Por qué una constante congelada y no un cálculo en vivo: el catálogo es legacy y se edita
     * fuera de este módulo. Un peso derivado en cada `calcular()` se movería solo —sin diff, sin
     * commit, sin nadie que lo revise— y con él las fechas intermedias que ya se le comunicaron a
     * un proveedor. Congelado, el valor viaja en el diff y se cambia a propósito. Su único riesgo
     * —quedarse viejo en silencio— lo cubre el centinela de `tests/test_pdc_v2_plan_fechas.php`,
     * que recalcula desde el catálogo vivo y falla si algún peso se aleja más de 0,01.
     *
     * Los valores anteriores (`0.08, 0.09, 0.08, 0.24, 0.20, 0.16, 0.15`) estaban escritos a mano y
     * su comentario decía «el peso típico del catálogo» sin que nadie lo hubiera medido: el desvío
     * mayor era Fabricación, que recibía 0,16 cuando el catálogo dice 0,249 (−36 %).
     */
    public const PESOS_REPARTO = [0.087872, 0.121115, 0.054079, 0.189065, 0.178996, 0.248792, 0.120081];

    /**
     * Deriva los pesos desde el catálogo vivo: la media de las proporciones fila a fila entre las
     * filas con desglose completo. No la usa `calcular()` —que trabaja con `PESOS_REPARTO`— sino el
     * script generador y el centinela del test; vive aquí para que el método de derivación sea uno
     * solo y no dos copias que puedan divergir.
     *
     * Se mide sobre la tabla de duraciones y no sobre los paquetes que la referencian: la forma del
     * proceso la define el catálogo, y cruzarlo con `general_paquetes_contratacion` haría que los
     * pesos dependieran además de qué paquetes están activos, que es otra pieza móvil.
     *
     * @return list<float> siete pesos que suman 1
     */
    public function pesosDelCatalogo(): array
    {
        $suma = implode(' + ', array_map(static fn (array $p): string => $p['col'], self::PASOS));
        $completo = implode(' AND ', array_map(static fn (array $p): string => $p['col'] . ' IS NOT NULL', self::PASOS));
        $promedios = implode(', ', array_map(
            static fn (array $p): string => 'AVG(' . $p['col'] . ' / t)',
            self::PASOS,
        ));
        $fila = $this->db->query(
            "SELECT {$promedios}
             FROM (SELECT *, ({$suma}) t FROM general_dias_procesos_contratacion WHERE {$completo}) x
             WHERE t > 0",
        )->fetch(\PDO::FETCH_NUM);
        if ($fila === false || $fila[0] === null) {
            return self::PESOS_REPARTO; // catálogo vacío: no hay nada que derivar
        }
        $pesos = array_map('floatval', array_values($fila));
        // Las siete medias suman 1 por construcción (cada fila aporta proporciones que suman 1),
        // pero se normaliza igual para que el error de coma flotante no se cuele en el reparto.
        $t = array_sum($pesos);
        return $t > 0 ? array_map(static fn (float $w): float => $w / $t, $pesos) : self::PESOS_REPARTO;
    }

    /**
     * Reparte una duración total entre los siete pasos según `PESOS_REPARTO`.
     *
     * El residuo de redondeo se asigna por resto mayor (los pasos cuya parte fraccionaria quedó más
     * cerca del día siguiente reciben el día suelto), no cargándoselo entero al último paso: así la
     * suma sigue siendo exactamente `$total` —la fecha de arranque y el plazo total no se mueven—
     * pero ningún paso se desvía más de un día de su parte proporcional. Con el reparto anterior,
     * «Insumos en obra» absorbía todo el residuo y podía quedar hasta tres días fuera de su peso.
     *
     * Pura y pública a propósito: es una regla del dominio, sin estado ni base de datos, y tanto los
     * tests como cualquier consumidor futuro deben poder reproducir el reparto sin recalcular.
     *
     * @return list<int>
     */
    public static function repartirMediana(int $total): array
    {
        $n = count(self::PASOS);
        if ($total <= 0) {
            return array_fill(0, $n, 0);
        }
        $sumaPesos = array_sum(self::PESOS_REPARTO);
        $dias = [];
        $restos = [];
        $acum = 0;
        foreach (self::PESOS_REPARTO as $i => $w) {
            $exacto = $total * $w / $sumaPesos;
            $piso = (int) floor($exacto);
            $dias[$i] = $piso;
            $restos[$i] = $exacto - $piso;
            $acum += $piso;
        }
        // Los días que dejó el redondeo hacia abajo van a los restos mayores; entre restos iguales
        // gana el paso más temprano, para que el reparto sea determinista.
        $orden = array_keys($restos);
        usort($orden, static fn (int $a, int $b): int => $restos[$b] <=> $restos[$a] ?: $a <=> $b);
        for ($k = 0; $k < $total - $acum; $k++) {
            $dias[$orden[$k % $n]]++;
        }
        return $dias;
    }

    /**
     * El plan del proyecto, con los vencidos primero.
     *
     * `calcular()` salta los paquetes inactivos, sin modalidad contratable o sin amarre, pero no
     * borra la cabecera vieja que hubieran dejado en `pdc_plan_paquete` — por eso el filtro va aquí:
     * el `JOIN` (antes `LEFT JOIN`) a `pdc_paquete_frente` exige un amarre vigente, y las mismas
     * condiciones de `p.activo`/`modalidad_contratacion` que usa `calcular()` evitan devolver un
     * paquete retirado del catálogo o cuya modalidad cambió a algo que ya no se contrata.
     */
    public function plan(int $projectId): array
    {
        $rows = $this->db->query(
            "SELECT pp.paquete_id, pp.unique_id, pp.fecha_ancla, pp.fecha_arranque, pp.dias_totales,
                    pp.duracion_provisional, pp.responsable, p.nombre, p.tipo_negociacion,
                    p.modalidad_contratacion, f.frente_nombre
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
             WHERE pp.project_id = ? AND p.activo = 1
               AND p.modalidad_contratacion IN (" . self::modalidadesConProcesoSql() . ')
             ORDER BY pp.fecha_arranque ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $pasos = [];
        foreach ($this->db->query(
            'SELECT paquete_id, orden, paso, dias, fecha_inicio, fecha_fin FROM pdc_plan_paso
             WHERE project_id = ? ORDER BY paquete_id, orden',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $pasos[(int) $p['paquete_id']][] = [
                'orden' => (int) $p['orden'], 'paso' => (string) $p['paso'], 'dias' => (int) $p['dias'],
                'fechaInicio' => (string) $p['fecha_inicio'], 'fechaFin' => (string) $p['fecha_fin'],
            ];
        }

        $hoy = new \DateTimeImmutable('today');
        $out = [];
        foreach ($rows as $r) {
            $arranque = new \DateTimeImmutable((string) $r['fecha_arranque']);
            $retraso = $arranque < $hoy ? (int) $hoy->diff($arranque)->days : 0;
            $out[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'nombre' => (string) $r['nombre'],
                'tipoNegociacion' => (string) $r['tipo_negociacion'],
                'modalidad' => (string) $r['modalidad_contratacion'],
                'frenteNombre' => (string) ($r['frente_nombre'] ?? ''),
                'uniqueId' => (int) $r['unique_id'],
                'fechaAncla' => (string) $r['fecha_ancla'],
                'fechaArranque' => (string) $r['fecha_arranque'],
                'diasTotales' => (int) $r['dias_totales'],
                'duracionProvisional' => (int) $r['duracion_provisional'] === 1,
                'responsable' => (string) $r['responsable'],
                'diasRetraso' => $retraso,
                'pasos' => $pasos[(int) $r['paquete_id']] ?? [],
            ];
        }
        // Los vencidos primero, del más atrasado al menos; luego el resto por fecha de arranque.
        usort($out, static function (array $a, array $b): int {
            if ($a['diasRetraso'] !== $b['diasRetraso']) {
                return $b['diasRetraso'] <=> $a['diasRetraso'];
            }
            return strcmp($a['fechaArranque'], $b['fechaArranque']);
        });
        return $out;
    }

    /**
     * Amarres cuya fecha ancla ya no coincide con la del cronograma: el frente se movió y el plan
     * quedó viejo. No se recalcula solo — una fecha que ya se comunicó a un proveedor no debe
     * cambiar en silencio; aplicar el desfase es un acto explícito de quien lea esta lista.
     *
     * Convención de signo de `diasMovidos`: positivo cuando el frente se ATRASÓ (la fecha actual es
     * posterior a la guardada — el caso más común y el que retrasa el paquete) y negativo cuando se
     * ADELANTÓ (la fecha actual es anterior). Nunca aparece en cero: si las fechas coinciden, esa
     * fila no se reporta.
     *
     * Un amarre cuyo `unique_id` ya no aparece entre `frentesDisponibles()` de la semana activa (se
     * borró del cronograma, dejó de ser encabezado, o la reprogramación lo movió sin dejar rastro
     * con ese id) es un caso real y distinto de «se movió»: no hay ninguna fecha nueva que comparar.
     * Se decide reportarlo igual —en vez de callarlo, que es lo que hacía el ejemplo original del
     * brief con su `continue`— porque un amarre huérfano es, si acaso, un desfase más grave que uno
     * con fecha nueva: el paquete quedó apuntando a un frente que ya no existe y nadie se entera si
     * la lista lo omite. Se marca con `fechaActual` y `diasMovidos` en null (no un string vacío ni
     * un 0) para que el consumidor pueda distinguir «no sé a qué fecha quedó» de «se movió 0 días»
     * sin ambigüedad.
     */
    public function desfases(int $projectId): array
    {
        $actual = [];
        foreach ($this->frentesDisponibles($projectId) as $f) {
            $actual[$f['uniqueId']] = $f;
        }
        $rows = $this->db->query(
            'SELECT f.paquete_id, f.unique_id, f.frente_nombre, f.fecha_ancla, p.nombre
             FROM pdc_paquete_frente f
             JOIN general_paquetes_contratacion p ON p.id = f.paquete_id
             WHERE f.project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $f = $actual[(int) $r['unique_id']] ?? null;
            if ($f === null) {
                $out[] = [
                    'paqueteId' => (int) $r['paquete_id'],
                    'nombre' => (string) $r['nombre'],
                    'frenteNombre' => (string) $r['frente_nombre'],
                    'fechaGuardada' => (string) $r['fecha_ancla'],
                    'fechaActual' => null,
                    'diasMovidos' => null,
                ];
                continue;
            }
            if ($f['fechaInicio'] === (string) $r['fecha_ancla']) {
                continue; // el frente sigue en la misma fecha: no hay nada que avisar
            }
            $guardada = new \DateTimeImmutable((string) $r['fecha_ancla']);
            $ahora = new \DateTimeImmutable($f['fechaInicio']);
            $dias = (int) $guardada->diff($ahora)->days;
            $out[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'nombre' => (string) $r['nombre'],
                'frenteNombre' => (string) $r['frente_nombre'],
                'fechaGuardada' => (string) $r['fecha_ancla'],
                'fechaActual' => $f['fechaInicio'],
                'diasMovidos' => $ahora > $guardada ? $dias : -$dias,
            ];
        }
        return $out;
    }
}
