<?php

namespace App\Services\Pdc;

/**
 * A4 · Convierte el amarre paquete↔cronograma en fechas.
 *
 * El cronograma no es el presupuesto a otra escala: tiene su propio árbol de frentes, con el
 * capítulo embebido en HTML dentro del campo `Actividad`. Los frentes (encabezados, `Titulo = 1`)
 * son los que hablan el idioma de los paquetes: ESTRUCTURA, MAMPOSTERÍA, RED ELÉCTRICA.
 *
 * @phpstan-type Frente array{uniqueId: int, nombre: string, capitulo: string, fechaInicio: string}
 * @phpstan-type FrenteTok array{
 *     uniqueId: int,
 *     nombre: string,
 *     capitulo: string,
 *     fechaInicio: string,
 *     tok: list<string>
 * }
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

    private readonly PasosContratacionService $pasos;

    /**
     * El servicio de pasos es opcional en la firma para no romper a los llamadores existentes
     * (el controlador y los tests construyen `new PlanFechasService($db)` a secas), pero nunca es
     * null dentro de la clase.
     */
    public function __construct(private readonly \Database $db, ?PasosContratacionService $pasos = null)
    {
        $this->pasos = $pasos ?? new PasosContratacionService($db);
    }

    /**
     * Lista `'contrato','orden_compra'` lista para pegar en un `IN (...)` SQL. Son los dos únicos
     * valores fijos del enum `modalidad_contratacion` que generan proceso — no hay entrada de
     * usuario en el valor, así que interpolarlos directo en la consulta es seguro; se centraliza
     * aquí para que `calcular()` y `plan()` no dupliquen el literal ni puedan divergir de
     * `MODALIDADES_CON_PROCESO`.
     *
     * Pública desde B2: el tablero de vencimientos necesita el mismo denominador que el plan para
     * contar los paquetes sin fecha. Duplicar la lista allí haría que nómina o imprevistos contaran
     * como «paquetes que el tablero no está mirando», una alarma que nadie podría apagar.
     */
    public static function modalidadesConProcesoSql(): string
    {
        return "'" . implode("','", self::MODALIDADES_CON_PROCESO) . "'";
    }

    /**
     * Separa el nombre del frente del capítulo que el cronograma embebe en un `<small>`.
     * Entrada: `<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>`
      *
      * @return array{nombre: string, capitulo: string} `capitulo` es '' cuando el `<small>` no lo trae
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

    /**
     * Frentes de obra de la semana activa, del más temprano al más tardío.
     *
     * @return list<array{uniqueId: int, nombre: string, capitulo: string, fechaInicio: string}>
     *         vacía si el proyecto no tiene semana activa
     */
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
     * @return array{semana: ?int, frentes: list<array{uniqueId: int, nombre: string, capitulo: string, fechaInicio: string, esFrente: bool}>}
     */
    private function semanaYFrentes(int $projectId, bool $soloEncabezados = true): array
    {
        $semana = $this->db->query(
            'SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?',
            [$projectId],
        )->fetchColumn();
        if ($semana === false || $semana === null) {
            return ['semana' => null, 'frentes' => []];
        }
        // `Titulo = 1` son los encabezados —los 31 frentes de obra—; con `$soloEncabezados = false`
        // entran también las 242 hojas. Ver anclasDisponibles() para por qué hacen falta.
        $filtroTitulo = $soloEncabezados ? ' AND Titulo = 1' : '';
        $rows = $this->db->query(
            "SELECT unique_id, Actividad, Fecha_Inicio, Titulo FROM programa_consolidado
             WHERE project_id = ? AND Semana = ?{$filtroTitulo} AND unique_id IS NOT NULL
               AND Fecha_Inicio IS NOT NULL
             ORDER BY Fecha_Inicio ASC, unique_id ASC",
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
                    'esFrente' => (int) $r['Titulo'] === 1,
                ];
            }, $rows),
        ];
    }

    /**
     * Todos los nodos del cronograma a los que se puede amarrar un paquete: los 31 encabezados y
     * también las 242 hojas.
     *
     * Las hojas hacen falta por una razón de obra, no de comodidad: hay ramas del presupuesto que no
     * tienen frente propio y cuyo hito real es una actividad concreta. El subcapítulo CUBIERTA ancla
     * en la hoja «LOSA AÉREA CUBIERTA» (2027-07-27); colgarlo del frente ESTRUCTURA (2026-08-18)
     * adelantaría la contratación 11 meses y 9 días, porque pondría la cubierta casi un año antes de
     * que exista la losa sobre la que va.
     *
     * Ojo con la asimetría, que es deliberada: `sugerirFrentes()` consume `frentesDisponibles()`
     * (solo encabezados) y nunca compara nombres contra esta lista larga. Está medido que hacerlo
     * produce disparates —«IMPERMEABILIZACION LOSA CUBIERTA» casa con «LOSA DE CIMENTACIÓN SÓTANO 3»
     * porque comparten «LOSA»—. Una hoja solo llega a proponerse cuando una correspondencia curada
     * por una persona la nombra.
     *
     * @return list<array{uniqueId: int, nombre: string, capitulo: string, fechaInicio: string, esFrente: bool}>
     */
    public function anclasDisponibles(int $projectId): array
    {
        return $this->semanaYFrentes($projectId, false)['frentes'];
    }

    /**
     * Palabras que no dicen nada del oficio y que, contadas como aciertos, fabrican parecidos falsos.
     * Medido en Da Porto: sin este filtro «CIMENTACIÓN PROFUNDA EN CONCRETO» y «CARPINTERIA EN
     * MADERA» comparten «EN», y «LABORATORIO DE MATERIALES» y «MOVIMIENTO DE TIERRA» comparten «DE».
     * Ninguna propuesta viva depende hoy de ellas —las 28 por similitud se sostienen todas en una
     * palabra con significado y el umbral de 0,33 filtra el resto—, así que quitarlas no cambia
     * ninguna propuesta existente: es prevención para el día que alguien toque el umbral.
     *
     * @var list<string>
     */
    private const PALABRAS_VACIAS = [
        'DE', 'DEL', 'LA', 'EL', 'LOS', 'LAS', 'Y', 'E', 'EN', 'A', 'AL',
        'CON', 'PARA', 'POR', 'SIN', 'SOBRE', 'U', 'O',
    ];

    /**
     * Palabras normalizadas de un nombre, sin el prefijo de tipo de negociación (no dice el oficio)
     * ni las palabras vacías (ver PALABRAS_VACIAS).
     *
     * @return list<string>
     */
    private static function tokens(string $s): array
    {
        $limpio = preg_replace('/^(Sum \+ Inst|Suministro|M\. de O)\s*/u', '', $s);
        $bruto = array_filter(explode(' ', MaestroInsumosService::normalizar((string) $limpio)));
        return array_values(array_filter(
            $bruto,
            static fn (string $w): bool => !in_array($w, self::PALABRAS_VACIAS, true),
        ));
    }

    /**
     * Mejor frente para un conjunto de palabras (Jaccard sobre `tok`). Entre empates gana el que
     * arranca antes: es el que fija la fecha límite del contrato. Null si nada llega al umbral.
      *
      * @param list<string>   $tp
      * @param list<FrenteTok> $frentesTok
      *
      * @return array{frente: FrenteTok, punt: float}|null null si nada alcanza SIMILITUD_MINIMA
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
            $guard = 0;
            // Se recogen TODAS las ramas del camino —grupos y subcapítulos—, de la más específica a
            // la más general, en vez de parar en el primer subcapítulo. El nivel `grupo` no es un
            // detalle: REVOQUES es el grupo 01.05.06 y ancla en «REVOQUE TRADICIONAL», mientras su
            // subcapítulo padre (MAMPOSTERIA Y REVOQUE) ancla un mes antes; IMPERMEABILIZACION
            // FILTROS (grupo 01.06.02) ancla en ESTRUCTURA, casi un año antes que su subcapítulo
            // hermano. Quedarse con el subcapítulo se saltaría los dos casos y daría fechas falsas.
            // El orden importa: quien consume esto prefiere la rama más específica que tenga
            // correspondencia (ver correspondenciaDeRamas()).
            while ($actual !== null && $guard++ < 12) {
                if ($actual['tipo_fila'] === 'capitulo') {
                    break; // el capítulo (COSTO DIRECTO / INDIRECTO) no dice nada del oficio
                }
                if (in_array($actual['tipo_fila'], ['grupo', 'subcapitulo'], true)) {
                    $subcapPorPaquete[(int) $asig['paquete_id']][(string) $actual['descripcion']] = true;
                }
                $padre = $actual['codigo_padre'];
                $actual = $padre !== null ? ($porCodigo[(string) $padre] ?? null) : null;
            }
        }
        return array_map('array_keys', $subcapPorPaquete);
    }

    /**
     * Correspondencias rama del presupuesto → nombre del nodo del cronograma, ya resueltas para este
     * proyecto: el catálogo global más las excepciones de la obra, que ganan.
     *
     * Se guarda el NOMBRE del nodo, no su `unique_id`, porque el unique_id pertenece a una obra
     * concreta mientras que el nombre («ESTRUCTURA», «MAMPOSTERÍA») se repite entre obras. Ese es el
     * detalle que permite que el catálogo sea global y que el próximo proyecto arranque con el
     * trabajo hecho.
     *
     * @return array<string, array{ancla: string, confirmado: bool, nota: string, alcance: string}>
     *         indexado por rama normalizada
     */
    private function correspondenciasEfectivas(int $projectId): array
    {
        $out = [];
        foreach ($this->db->query(
            'SELECT rama_norm, ancla_nombre, confirmado_humano, nota FROM general_rama_frente',
        )->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $out[MaestroInsumosService::normalizar((string) $r['rama_norm'])] = [
                'ancla' => (string) $r['ancla_nombre'],
                'confirmado' => (int) $r['confirmado_humano'] === 1,
                'nota' => (string) $r['nota'],
                'alcance' => 'global',
            ];
        }
        foreach ($this->db->query(
            'SELECT rama_norm, ancla_nombre, confirmado_humano, nota FROM pdc_rama_frente WHERE project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $out[MaestroInsumosService::normalizar((string) $r['rama_norm'])] = [
                'ancla' => (string) $r['ancla_nombre'],
                'confirmado' => (int) $r['confirmado_humano'] === 1,
                'nota' => (string) $r['nota'],
                'alcance' => 'proyecto',
            ];
        }
        return $out;
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
      *
      * @return array<int, array{
      *     uniqueId: int,
      *     nombre: string,
      *     fechaInicio: string,
      *     origen: 'similitud'|'rama',
      *     confianza: 'alta'|'media',
      *     evidencia: string
      * }> indexado por id de paquete
      */
    public function sugerirFrentes(int $projectId, ?int $versionId = null): array
    {
        return $this->analizarFrentes($projectId, $versionId)['sugerencias'];
    }

    /**
     * Igual que `sugerirFrentes()` pero devolviendo también, para cada paquete que se queda sin
     * propuesta, el motivo en palabras. Una fila muda no le dice a nadie qué hacer; «su rama X
     * todavía no tiene nodo asignado» sí, y además nombra exactamente lo que hay que resolver.
     *
     * @return array{sugerencias: array<int, array<string, mixed>>, motivos: array<int, array{texto: string, rama: string|null}>}
     */
    public function sugerenciasYMotivos(int $projectId, ?int $versionId = null): array
    {
        return $this->analizarFrentes($projectId, $versionId);
    }

    /**
     * @return array{sugerencias: array<int, array<string, mixed>>, motivos: array<int, array{texto: string, rama: string|null}>}
     */
    private function analizarFrentes(int $projectId, ?int $versionId = null): array
    {
        $frentes = $this->frentesDisponibles($projectId);
        if ($frentes === []) {
            return ['sugerencias' => [], 'motivos' => []];
        }
        // Las anclas incluyen las hojas: una correspondencia curada puede apuntar a una (CUBIERTA →
        // «LOSA AÉREA CUBIERTA»). Se indexan por nombre normalizado porque eso es lo que guarda el
        // catálogo global, que no puede conocer los unique_id de esta obra.
        $anclaPorNombre = [];
        foreach ($this->anclasDisponibles($projectId) as $a) {
            $k = MaestroInsumosService::normalizar($a['nombre']);
            // Ante nombres repetidos gana el que arranca antes: es el que marca la fecha límite.
            if (!isset($anclaPorNombre[$k]) || $a['fechaInicio'] < $anclaPorNombre[$k]['fechaInicio']) {
                $anclaPorNombre[$k] = $a;
            }
        }
        $correspondencias = $this->correspondenciasEfectivas($projectId);
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
        $motivos = [];
        $ids = array_map(static fn (array $p): int => (int) $p['id'], $paquetes);
        $vid = $this->versionActivaId($projectId, $versionId);
        $ramasPorPaquete = $vid !== null ? $this->subcapitulosDePaquete($projectId, $vid, $ids) : [];

        foreach ($paquetes as $p) {
            $paqueteId = (int) $p['id'];
            $tp = self::tokens((string) $p['nombre']);
            $ramas = $ramasPorPaquete[$paqueteId] ?? [];

            // ---- Señal 1: la rama, vía correspondencia. Es la que sabe que CIELOS RASOS pertenece
            // a ACABADOS aunque no compartan ninguna palabra.
            $candidatos = $this->anclasDeRamas($ramas, $correspondencias, $anclaPorNombre, $frentes);

            if ($candidatos !== []) {
                // El subcapítulo acota, el nombre desempata dentro del grupo: "M. de O MAMPOSTERÍA" y
                // "Sum + Inst REVOQUE SECO" comparten rama y una persona los amarró a frentes
                // distintos, y lo que los separa es el nombre.
                $elegido = null;
                $puntNombre = 0.0;
                foreach ($candidatos as $c) {
                    $punt = $tp === [] ? 0.0 : $this->parecido($tp, self::tokens($c['ancla']['nombre']));
                    if ($punt > $puntNombre) {
                        $puntNombre = $punt;
                        $elegido = $c;
                    }
                }
                // Sin desempate por nombre gana el que arranca antes: el contrato tiene que estar
                // listo para el primer uso, no para el más caro (mismo criterio que A3.3).
                if ($elegido === null) {
                    usort($candidatos, static fn (array $a, array $b): int => $a['ancla']['fechaInicio'] <=> $b['ancla']['fechaInicio']);
                    $elegido = $candidatos[0];
                }

                // ¿El nombre del paquete apunta a un frente que NO está entre los candidatos? Se
                // respeta la rama —es la señal fuerte— pero baja a media y se dice el desacuerdo en
                // vez de esconderlo.
                $porNombre = $tp === [] ? null : $this->mejorFrente($tp, $frentesTok);
                $nombresCandidatos = array_map(static fn (array $c): int => $c['ancla']['uniqueId'], $candidatos);
                $discrepa = $porNombre !== null && !in_array($porNombre['frente']['uniqueId'], $nombresCandidatos, true);

                $confianza = $elegido['confirmado'] && !$discrepa ? 'alta' : 'media';
                $evidencia = sprintf(
                    'Sus insumos están en «%s», que según %s corresponde a «%s» del cronograma (arranca %s).',
                    $elegido['rama'],
                    $elegido['confirmado'] ? 'una correspondencia confirmada' : 'una correspondencia aún sin confirmar',
                    $elegido['ancla']['nombre'],
                    $elegido['ancla']['fechaInicio'],
                );
                if ($discrepa) {
                    $evidencia .= sprintf(
                        ' Ojo: por su nombre parecería «%s»; se respeta la rama, revísalo.',
                        $porNombre['frente']['nombre'],
                    );
                }
                $out[$paqueteId] = [
                    'uniqueId' => $elegido['ancla']['uniqueId'],
                    'nombre' => $elegido['ancla']['nombre'],
                    'fechaInicio' => $elegido['ancla']['fechaInicio'],
                    'origen' => 'correspondencia',
                    'confianza' => $confianza,
                    'evidencia' => mb_substr($evidencia, 0, 500),
                ];
                continue;
            }

            // ---- Señal 2 (respaldo): el nombre contra los encabezados, la capa de A4. Solo actúa
            // cuando la rama no resolvió, y sigue con el umbral de 0,33 intacto.
            if ($tp !== []) {
                $m = $this->mejorFrente($tp, $frentesTok);
                if ($m !== null) {
                    $mejor = $m['frente'];
                    $out[$paqueteId] = [
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
                    continue;
                }
            }

            // ---- Sin propuesta: se dice por qué, nombrando la rama que falta por resolver.
            $motivos[$paqueteId] = $ramas === []
                ? ['texto' => 'No tiene insumos localizados en el presupuesto de esta versión.', 'rama' => null]
                : [
                    'texto' => sprintf('Su rama «%s» todavía no tiene nodo del cronograma asignado.', $ramas[0]),
                    'rama' => $ramas[0],
                ];
        }

        return ['sugerencias' => $out, 'motivos' => $motivos];
    }

    /**
     * Las correspondencias que este proyecto usa, y las ramas que todavía no tienen ninguna.
     *
     * Se devuelven juntas a propósito: el panel no sirve para admirar lo resuelto sino para cerrar
     * lo que falta, y las ramas pendientes son exactamente las que dejan paquetes sin propuesta.
     * Solo se listan las ramas que el proyecto usa de verdad; el catálogo entero sería ruido.
     *
     * @return array{
     *     correspondencias: list<array{rama: string, ancla: string, confirmado: bool, alcance: string, nota: string}>,
     *     pendientes: list<string>, confirmadas: int, sinConfirmar: int
     * }
     */
    public function correspondencias(int $projectId, ?int $versionId = null): array
    {
        $efectivas = $this->correspondenciasEfectivas($projectId);
        $anclas = [];
        foreach ($this->anclasDisponibles($projectId) as $a) {
            $anclas[MaestroInsumosService::normalizar($a['nombre'])] = true;
        }

        $vid = $this->versionActivaId($projectId, $versionId);
        $ramasUsadas = [];
        if ($vid !== null) {
            $ids = $this->db->query(
                'SELECT DISTINCT p.id FROM general_paquetes_contratacion p
                 JOIN pdc_insumo_paquete ip ON ip.paquete_id = p.id
                 WHERE p.activo = 1 AND ip.project_id = ?
                   AND p.modalidad_contratacion IN (\'contrato\', \'orden_compra\')',
                [$projectId],
            )->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($this->subcapitulosDePaquete($projectId, $vid, array_map('intval', $ids)) as $ramas) {
                foreach ($ramas as $r) {
                    $ramasUsadas[MaestroInsumosService::normalizar($r)] = $r;
                }
            }
        }

        $out = [];
        $pendientes = [];
        $confirmadas = 0;
        $sinConfirmar = 0;
        foreach ($ramasUsadas as $norm => $original) {
            if (isset($efectivas[$norm])) {
                $e = $efectivas[$norm];
                $e['confirmado'] ? $confirmadas++ : $sinConfirmar++;
                $out[] = [
                    'rama' => $original,
                    'ancla' => $e['ancla'],
                    'confirmado' => $e['confirmado'],
                    'alcance' => $e['alcance'],
                    'nota' => $e['nota'],
                ];
                continue;
            }
            // Coincidencia exacta con un nodo del cronograma: se resuelve sola, no es un pendiente.
            if (isset($anclas[$norm])) {
                $confirmadas++;
                $out[] = [
                    'rama' => $original,
                    'ancla' => $original,
                    'confirmado' => true,
                    'alcance' => 'automatica',
                    'nota' => 'La rama y el nodo del cronograma se llaman igual.',
                ];
                continue;
            }
            $pendientes[] = $original;
        }
        usort($out, static fn (array $a, array $b): int => [$a['confirmado'], $a['rama']] <=> [$b['confirmado'], $b['rama']]);

        // Una rama sin correspondencia propia NO es un pendiente si el paquete igual recibió
        // propuesta por otra de sus ramas —lo normal: un grupo fino cuyo subcapítulo padre ya está
        // resuelto—. Listarlas todas daba 66 «pendientes» cuando los paquetes realmente huérfanos
        // son 4, y un panel que grita 66 cosas por hacer no ayuda a cerrar ninguna. Pendiente es
        // solo lo que hoy deja a un paquete sin fecha.
        $bloquean = [];
        foreach ($this->analizarFrentes($projectId, $versionId)['motivos'] as $m) {
            if ($m['rama'] !== null) {
                $bloquean[MaestroInsumosService::normalizar($m['rama'])] = $m['rama'];
            }
        }
        $pendientes = array_values($bloquean);
        sort($pendientes);

        return [
            'correspondencias' => $out,
            'pendientes' => $pendientes,
            'confirmadas' => $confirmadas,
            'sinConfirmar' => $sinConfirmar,
        ];
    }

    /**
     * Guarda una correspondencia. `alcance = 'proyecto'` escribe la excepción de esta obra; cualquier
     * otro valor toca el catálogo global, conocimiento de la empresa, que por eso pide otro permiso.
     *
     * Guardar una correspondencia NO amarra ningún paquete: solo cambia lo que el motor propone de
     * aquí en adelante. Es deliberado — el amarre sigue siendo un acto humano y esto no puede
     * convertirse en una escritura masiva encubierta.
     *
     * @return array{ok: true}|array{ok: false, code: 'DATOS_INVALIDOS'|'ANCLA_INVALIDA'}
     */
    public function guardarCorrespondencia(
        int $projectId,
        string $rama,
        string $ancla,
        string $alcance,
        string $usuario,
    ): array {
        $ramaNorm = MaestroInsumosService::normalizar($rama);
        if ($ramaNorm === '' || trim($ancla) === '') {
            return ['ok' => false, 'code' => 'DATOS_INVALIDOS'];
        }
        // El ancla tiene que existir en el cronograma de esta obra. Una correspondencia hacia un
        // nombre inventado no falla al guardarse: falla meses después, dejando paquetes sin fecha.
        $existe = false;
        foreach ($this->anclasDisponibles($projectId) as $a) {
            if (MaestroInsumosService::normalizar($a['nombre']) === MaestroInsumosService::normalizar($ancla)) {
                $existe = true;
                break;
            }
        }
        if (!$existe) {
            return ['ok' => false, 'code' => 'ANCLA_INVALIDA'];
        }

        if ($alcance === 'proyecto') {
            $this->db->query(
                'INSERT INTO pdc_rama_frente (project_id, rama_norm, ancla_nombre, confirmado_humano, asignado_por)
                 VALUES (?, ?, ?, 1, ?)
                 ON DUPLICATE KEY UPDATE ancla_nombre = VALUES(ancla_nombre), confirmado_humano = 1,
                    asignado_por = VALUES(asignado_por)',
                [$projectId, $ramaNorm, trim($ancla), $usuario],
            );
        } else {
            $this->db->query(
                'INSERT INTO general_rama_frente (rama_norm, ancla_nombre, confirmado_humano, creado_por, actualizado_por)
                 VALUES (?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE ancla_nombre = VALUES(ancla_nombre), confirmado_humano = 1,
                    actualizado_por = VALUES(actualizado_por)',
                [$ramaNorm, trim($ancla), $usuario, $usuario],
            );
        }
        return ['ok' => true];
    }

    /**
     * Parecido Jaccard entre dos conjuntos de palabras. 0 si no comparten ninguna.
     *
     * @param list<string> $a
     * @param list<string> $b
     */
    private function parecido(array $a, array $b): float
    {
        $a = array_unique($a);
        $b = array_unique($b);
        if ($a === [] || $b === []) {
            return 0.0;
        }
        $comunes = count(array_intersect($a, $b));
        return $comunes === 0 ? 0.0 : $comunes / count(array_unique(array_merge($a, $b)));
    }

    /**
     * Anclas candidatas para las ramas de un paquete, de la rama más específica a la más general.
     *
     * Dos fuentes, ambas con confirmación humana detrás:
     *  1. La tabla de correspondencias (curada en obra).
     *  2. La coincidencia exacta de nombre entre la rama y un encabezado del cronograma —ESTRUCTURA
     *     con ESTRUCTURA—. No se siembran en la tabla a propósito: guardar lo que se deduce solo es
     *     confundir memoria con conocimiento.
     *
     * @param list<string>                                                                    $ramas
     * @param array<string, array{ancla: string, confirmado: bool, nota: string, alcance: string}> $correspondencias
     * @param array<string, array{uniqueId: int, nombre: string, fechaInicio: string, esFrente: bool, capitulo: string}> $anclaPorNombre
     * @param list<array{uniqueId: int, nombre: string, capitulo: string, fechaInicio: string}>  $frentes
     *
     * @return list<array{rama: string, ancla: array<string, mixed>, confirmado: bool}>
     */
    private function anclasDeRamas(array $ramas, array $correspondencias, array $anclaPorNombre, array $frentes): array
    {
        $out = [];
        $vistas = [];
        foreach ($ramas as $rama) {
            $k = MaestroInsumosService::normalizar($rama);
            $ancla = null;
            $confirmado = false;
            if (isset($correspondencias[$k])) {
                $destino = MaestroInsumosService::normalizar($correspondencias[$k]['ancla']);
                $ancla = $anclaPorNombre[$destino] ?? null;
                $confirmado = $correspondencias[$k]['confirmado'];
            }
            if ($ancla === null) {
                // Coincidencia exacta con un encabezado: evidencia tan buena como la curada.
                foreach ($frentes as $f) {
                    if (MaestroInsumosService::normalizar($f['nombre']) === $k) {
                        $ancla = $f + ['esFrente' => true];
                        $confirmado = true;
                        break;
                    }
                }
            }
            if ($ancla === null || isset($vistas[$ancla['uniqueId']])) {
                continue;
            }
            $vistas[$ancla['uniqueId']] = true;
            $out[] = ['rama' => $rama, 'ancla' => $ancla, 'confirmado' => $confirmado];
        }
        return $out;
    }

    /**
     * Amarra un paquete a un frente del cronograma.
     *
     * Guarda la fecha que el frente tenía en este momento: es lo que permite detectar más adelante
     * que la obra se reprogramó y el plan quedó viejo. La procedencia funciona como en los insumos:
     * aceptar la propuesta conserva la capa que la produjo (acierto), elegir a mano es «humano».
      *
      * @param array<string, mixed> $procedencia origen, confianza y confirmado de la decisión; como en
      *                                          los insumos, aceptar la propuesta conserva su capa
      *
      * @param int $subpaqueteId destino contratable dentro del paquete: `0` es el paquete sin partir,
      *                          y un id de `pdc_subpaquete` es uno de sus lotes. Cada lote tiene su
      *                          propio frente («eso lo contrato en dos meses; eso lo necesito ya»),
      *                          así que el amarre es por destino y no por paquete. El valor por
      *                          defecto deja intacto a todo el que ya llamaba a este método.
      *
      * @return array{ok: true}|array{
      *     ok: false,
      *     code: 'FRENTE_INVALIDO'|'PAQUETE_INVALIDO'|'MODALIDAD_NO_CONTRATABLE'
      * }
      */
    public function amarrar(
        int $projectId,
        int $paqueteId,
        int $uniqueId,
        string $usuario,
        array $procedencia = [],
        int $subpaqueteId = SubpaquetesService::SIN_PARTIR,
    ): array {
        // Una sola lectura del cronograma para las dos cosas que hacen falta aquí: el frente destino
        // y la semana activa que se guarda en `semana_origen` (ver semanaYFrentes()).
        // `false` = también las hojas. Un paquete puede amarrarse a una actividad concreta y no solo
        // a un encabezado: el subcapítulo CUBIERTA ancla en «LOSA AÉREA CUBIERTA», y exigir un
        // encabezado obligaría a colgarlo de ESTRUCTURA, 11 meses antes de que exista la losa.
        ['semana' => $semana, 'frentes' => $frentes] = $this->semanaYFrentes($projectId, false);
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

        $origen = in_array($procedencia['origen'] ?? '', ['similitud', 'rama', 'correspondencia'], true)
            ? $procedencia['origen']
            : 'humano';
        $delMotor = $origen !== 'humano';

        // Corregir al motor es información, no ruido: sin este registro nunca se puede medir si
        // acierta. Se escribe solo cuando había una propuesta y la persona eligió otra cosa —aceptar
        // tal cual no es una corrección—. Mismo criterio que `pdc_correcciones_motor` con los
        // insumos, en su tabla propia porque aquélla está atada a (descripcion_norm, unidad).
        $sugerido = filter_var($procedencia['sugeridoUniqueId'] ?? null, FILTER_VALIDATE_INT);
        if ($sugerido !== false && $sugerido !== $uniqueId) {
            $this->db->query(
                'INSERT INTO pdc_correcciones_frente
                    (project_id, paquete_id, unique_id_sugerido, unique_id_elegido, capa_sugerida, confianza_sugerida, usuario)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $projectId, $paqueteId, $sugerido, $uniqueId,
                    mb_substr((string) ($procedencia['origenSugerido'] ?? $procedencia['origen'] ?? ''), 0, 20),
                    in_array($procedencia['confianza'] ?? '', ['alta', 'media', 'baja'], true) ? $procedencia['confianza'] : null,
                    $usuario,
                ],
            );
        }

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
                'SELECT unique_id, fecha_ancla FROM pdc_plan_paquete
                  WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?',
                [$projectId, $paqueteId, $subpaqueteId],
            )->fetch(\PDO::FETCH_ASSOC);
            $reamarreInvalida = $planCalculado !== false
                && ((int) $planCalculado['unique_id'] !== $uniqueId
                    || (string) $planCalculado['fecha_ancla'] !== $frente['fechaInicio']);

            $this->db->query(
                'INSERT INTO pdc_paquete_frente
                    (project_id, paquete_id, subpaquete_id, unique_id, frente_nombre, fecha_ancla,
                     semana_origen, origen, confianza, evidencia, confirmado_humano, asignado_por,
                     updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE unique_id = VALUES(unique_id), frente_nombre = VALUES(frente_nombre),
                    fecha_ancla = VALUES(fecha_ancla), semana_origen = VALUES(semana_origen),
                    origen = VALUES(origen), confianza = VALUES(confianza), evidencia = VALUES(evidencia),
                    confirmado_humano = VALUES(confirmado_humano), asignado_por = VALUES(asignado_por),
                    updated_at = NOW()',
                [
                    $projectId, $paqueteId, $subpaqueteId, $uniqueId, $frente['nombre'],
                    $frente['fechaInicio'], $semana,
                    $origen,
                    $delMotor && in_array($procedencia['confianza'] ?? '', ['alta', 'media', 'baja'], true) ? $procedencia['confianza'] : null,
                    $delMotor ? mb_substr((string) ($procedencia['evidencia'] ?? ''), 0, 500) : '',
                    (!$delMotor || ($procedencia['confirmado'] ?? false) === true) ? 1 : 0,
                    $usuario,
                ],
            );

            if ($reamarreInvalida) {
                // El plan viejo quedó calculado contra un frente que ya no es el amarrado: se
                // invalida entero. El paquete cae a "amarrado, pendiente de calcular" — bloque que
                // la SPA ya distingue de "sin frente" — hasta el próximo «Recalcular», que es un
                // acto explícito de quien lo vea.
                //
                // Se invalidan las FECHAS, no la fila: hasta la revisión de UX de julio de 2026
                // esto era un `DELETE FROM pdc_plan_paquete` que se llevaba también al responsable,
                // en silencio. Corregir un frente mal elegido no puede costar volver a repartir el
                // trabajo (ver limpiarPlanCalculado()).
                $this->limpiarPlanCalculado($projectId, $paqueteId, $subpaqueteId);
            }

            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true];
    }

    /**
     * Deshace el amarre de un paquete: vuelve a «sin frente» y pierde sus fechas, pero NO su
     * responsable.
     *
     * Las fechas se borran a propósito. Se calculan hacia atrás desde la fecha de la actividad del
     * cronograma; sin frente no hay desde dónde calcularlas, y conservarlas dejaría en pantalla unas
     * fechas huérfanas indistinguibles de las vigentes — justo las que la gente ya comunicó a un
     * proveedor. El responsable, en cambio, es una decisión humana que no depende de ninguna fecha:
     * quien iba a comprar ese paquete lo sigue haciendo, y volver a repartir el trabajo desde cero
     * por haber corregido un frente mal elegido sería un castigo absurdo.
     *
     * La cabecera se conserva vacía SOLO si guarda un responsable: si no hay nada que conservar se
     * borra entera, para que una fila sin fechas signifique siempre «este paquete tiene dueño y
     * todavía no tiene plan» y no se acumulen cabeceras huecas.
     *
     * Desamarrar algo que no estaba amarrado es un no-op, no un error: el usuario quería que ese
     * paquete quedara sin frente, y ya lo está.
     *
     * No recibe `$usuario` a diferencia de `amarrar()`: la fila que registraría quién lo hizo
     * (`pdc_paquete_frente.asignado_por`) es justamente la que se borra, así que un parámetro con
     * ese nombre prometería una auditoría que no existe. Cuando B1 añada un registro de cambios,
     * ahí sí tendrá dónde escribirse.
     *
     * @return array{ok: bool, code?: string}
     */
    public function desamarrar(
        int $projectId,
        int $paqueteId,
        int $subpaqueteId = SubpaquetesService::SIN_PARTIR,
    ): array {
        $this->db->beginTransaction();
        try {
            $this->limpiarPlanCalculado($projectId, $paqueteId, $subpaqueteId);
            $this->db->query(
                'DELETE FROM pdc_paquete_frente
                  WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?',
                [$projectId, $paqueteId, $subpaqueteId],
            );
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true];
    }

    /**
     * Borra el plan calculado de un paquete (pasos y fechas) conservando a su responsable.
     *
     * Lo usan los dos caminos que invalidan un plan: desamarrar del todo, y reamarrar a un frente
     * distinto. En ambos las fechas dejan de valer, pero quién compra el paquete no cambia.
     *
     * La cabecera se queda vacía solo si guarda un responsable; si no, se borra entera. Así una
     * fila sin fechas significa siempre lo mismo —«tiene dueño, todavía no tiene plan»— y no se
     * acumulan cabeceras huecas. `calculado_por` no se toca: registra quién calculó, y esto no es
     * calcular.
     *
     * Asume una transacción abierta por quien llama.
     */
    private function limpiarPlanCalculado(
        int $projectId,
        int $paqueteId,
        int $subpaqueteId = SubpaquetesService::SIN_PARTIR,
    ): void {
        // Todo va acotado al DESTINO y no al paquete: en un paquete partido, invalidar el plan de un
        // lote no puede llevarse el de sus hermanos, que están amarrados a otros frentes y cuyas
        // fechas siguen siendo buenas. Con `subpaquete_id = 0` esto es palabra por palabra lo que
        // hacía antes, porque un paquete sin partir tiene exactamente una fila por tabla.
        // Las filas SIN avance se borran: son solo fechas calculadas contra un frente que ya no vale.
        $this->db->query(
            'DELETE FROM pdc_plan_paso
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ? AND fecha_real IS NULL',
            [$projectId, $paqueteId, $subpaqueteId],
        );
        // Las que SI llevan avance se conservan y se les vacian las fechas programadas. Una propuesta
        // ya recibida no deja de haberse recibido porque la obra se reprograme, y borrar la fila se
        // llevaria por delante trabajo que ocurrio de verdad — la deuda que A4 dejo anotada aqui.
        // Quedan con fecha_real y sin programadas, que es exactamente lo que significan: «esto se
        // hizo, pero el plan todavia no se ha recalculado». El siguiente calcular() las repone.
        $this->db->query(
            'UPDATE pdc_plan_paso SET fecha_inicio = NULL, fecha_fin = NULL
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?',
            [$projectId, $paqueteId, $subpaqueteId],
        );
        $this->db->query(
            'UPDATE pdc_plan_paquete
                SET unique_id = NULL, fecha_ancla = NULL, fecha_arranque = NULL,
                    dias_totales = NULL, duracion_ref = NULL, duracion_provisional = 0,
                    updated_at = NOW()
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?',
            [$projectId, $paqueteId, $subpaqueteId],
        );
        // La cabecera se retira solo si no queda nada que sostener: ni responsable ni avance. No hay
        // clave foránea entre `pdc_plan_paso` y `pdc_plan_paquete`, así que borrarla con pasos vivos
        // no falla — deja el avance escrito y fuera del alcance de las dos pantallas, porque el
        // resumen une por cabecera y el detalle necesita su `fecha_arranque`.
        $this->db->query(
            'DELETE FROM pdc_plan_paquete
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?
                AND responsable_user_id IS NULL
                AND NOT EXISTS (
                    SELECT 1 FROM pdc_plan_paso s
                     WHERE s.project_id = ? AND s.paquete_id = ? AND s.subpaquete_id = ?
                       AND s.fecha_real IS NOT NULL
                )',
            [$projectId, $paqueteId, $subpaqueteId, $projectId, $paqueteId, $subpaqueteId],
        );
    }

    /**
     * Amarres vigentes del proyecto, indexados por paquete.
     *
     * Acotado a los paquetes SIN PARTIR (`subpaquete_id = 0`), y por eso sigue pudiendo indexarse por
     * id de paquete sin perder filas: un paquete partido ya no es una unidad contratable —lo son sus
     * lotes— y su amarre pasó al lote «Resto». Quien necesite todas las unidades, incluidos los
     * lotes, tiene `destinosAmarrados()`; esta lista es la que la pantalla usa para saber qué
     * paquetes están amarrados como un todo.
     *
     * @return array<int, array{
     *     uniqueId: int,
     *     frenteNombre: string,
     *     fechaAncla: string,
     *     origen: string,
     *     confianza: mixed,
     *     confirmadoHumano: bool
     * }> indexado por id de paquete
     */
    public function amarres(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT paquete_id, unique_id, frente_nombre, fecha_ancla, origen, confianza, confirmado_humano
             FROM pdc_paquete_frente WHERE project_id = ? AND subpaquete_id = ?',
            [$projectId, SubpaquetesService::SIN_PARTIR],
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
     * Modalidad de cada lote de la obra, indexada por `subpaquete_id`.
     *
     * Se lee de una vez y no lote por lote dentro del bucle de `calcular()`: son 130 destinos en el
     * peor caso conocido y una consulta por destino convertiría un recálculo en 130 idas a la base.
     *
     * @return array<int, string>
     */
    private function modalidadPorLote(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT id, modalidad_contratacion FROM pdc_subpaquete WHERE project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id']] = (string) $r['modalidad_contratacion'];
        }
        return $out;
    }

    /**
     * Todos los destinos contratables amarrados del proyecto: paquetes sin partir Y lotes.
     *
     * Es lo que recorre `calcular()`. Devuelve una LISTA y no un mapa indexado por paquete porque la
     * clave ya no es el paquete: un paquete partido aporta tantas entradas como lotes tenga, y
     * indexar por paquete perdería todas menos una — el error silencioso más fácil de cometer al
     * añadir este nivel.
     *
     * @return list<array{
     *     paqueteId: int,
     *     subpaqueteId: int,
     *     uniqueId: int,
     *     frenteNombre: string,
     *     fechaAncla: string
     * }>
     */
    public function destinosAmarrados(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT paquete_id, subpaquete_id, unique_id, frente_nombre, fecha_ancla
               FROM pdc_paquete_frente WHERE project_id = ?
              ORDER BY paquete_id, subpaquete_id',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(static fn (array $r): array => [
            'paqueteId' => (int) $r['paquete_id'],
            'subpaqueteId' => (int) $r['subpaquete_id'],
            'uniqueId' => (int) $r['unique_id'],
            'frenteNombre' => (string) $r['frente_nombre'],
            'fechaAncla' => (string) $r['fecha_ancla'],
        ], $rows);
    }

    /**
     * El proceso de contratación POR DEFECTO de la empresa, en orden, con la columna del catálogo
     * legacy que guarda la duración de cada paso y la clave que lo identifica en
     * `general_pasos_contratacion`. El último termina en la fecha en que el paquete se necesita en obra.
     *
     * Desde A4.1 esto es *el proceso por defecto*, no *el proceso*: una obra puede definir el suyo en
     * `pdc_proyecto_pasos` y entonces manda el suyo (ver `PasosContratacionService::deProyecto()`).
     * Esta constante se conserva como respaldo en código a propósito: es lo que garantiza que una obra
     * sin configurar —Da Porto— dé exactamente las mismas fechas aunque el catálogo de la base
     * estuviera vacío o a medio sembrar.
     */
    public const PASOS = [
        ['paso' => 'Elaboración de pliegos', 'col' => 'diasElaboracionPliegos', 'clave' => 'elaboracion_pliegos'],
        ['paso' => 'Entrega de pliegos', 'col' => 'diasEntregaPliegos', 'clave' => 'entrega_pliegos'],
        ['paso' => 'Recibo de propuestas', 'col' => 'diasReciboPropuestas', 'clave' => 'recibo_propuestas'],
        ['paso' => 'Cuadros comparativos', 'col' => 'diasCuadrosComparativos', 'clave' => 'cuadros_comparativos'],
        ['paso' => 'Legalización', 'col' => 'diasLegalizacionContrato', 'clave' => 'legalizacion'],
        ['paso' => 'Fabricación', 'col' => 'diasFabricacion', 'clave' => 'fabricacion'],
        ['paso' => 'Insumos en obra', 'col' => 'diasInsumosObra', 'clave' => 'insumos_obra'],
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
      *
      * @return array{ok: true, calculados: int, sinDuracion: int}
      */
    /**
     * Qué fechas produciría este paquete anclado a `$fechaAncla`. NO escribe nada.
     *
     * Se separó de `calcular()` en B2 porque simular la reprogramación necesita exactamente este
     * cálculo sin la escritura. La aritmética no cambió al extraerla: el contrato de fronteras
     * medio abiertas `[fecha_inicio, fecha_fin)` que documenta `calcular()` sigue siendo el mismo,
     * y quien recorre los pasos con el cursor sigue siendo `calcular()`. Aquí solo se decide
     * cuántos días dura cada paso y en qué fecha arranca el conjunto.
     *
     * @param list<array{pasoId:?int,clave:string,nombre:string,colLegacy:?string,diasFijos:?int,peso:?float}> $pasos
     * @param array<string, int> $medianas
     * @return array{arranque:string,total:int,dias:list<int>,provisional:bool,duracionRef:?int}|null
     *         null si el paquete está inactivo o su modalidad ya no genera proceso de contratación
     */
    private function proyectar(
        int $paqueteId,
        string $fechaAncla,
        array $pasos,
        array $medianas,
        string $selectCols,
        ?string $modalidadDestino = null,
    ): ?array {
        // Cuando el destino es un LOTE, la modalidad que decide si hay proceso es la suya y no la del
        // paquete: la obra puede descubrir que la cerámica se compra por orden de compra aunque
        // «Pisos» sea un contrato. Por eso el filtro de modalidad se mueve del SQL a PHP en ese caso.
        // Con `$modalidadDestino === null` —un paquete sin partir— la consulta es exactamente la de
        // antes, filtro incluido, y no hay forma de que el resultado cambie.
        if ($modalidadDestino !== null && !in_array($modalidadDestino, self::MODALIDADES_CON_PROCESO, true)) {
            return null;
        }
        $filtroModalidad = $modalidadDestino === null
            ? ' AND p.modalidad_contratacion IN (' . self::modalidadesConProcesoSql() . ')'
            : '';
        $paq = $this->db->query(
            "SELECT p.id, p.tipo_negociacion, p.duracion_ref{$selectCols}
             FROM general_paquetes_contratacion p
             LEFT JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
             WHERE p.id = ? AND p.activo = 1{$filtroModalidad}",
            [$paqueteId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($paq === false) {
            return null;
        }

        // «Sin duración» se decide por las columnas del desglose que ESTA obra usa, no por
        // `duracion_ref`: así se cubren de una sola vez los tres casos silenciosos (duracion_ref
        // NULL, apuntando a una fila borrada, o a una fila con algún `dias*` NULL) porque los tres
        // producen el mismo resultado en el LEFT JOIN — al menos una columna NULL.
        // Un paso de días fijos siempre aporta su número y nunca vuelve provisional a un paquete.
        $desgloseCompleto = true;
        foreach ($pasos as $p) {
            if ($p['colLegacy'] !== null && ($paq[$p['colLegacy']] ?? null) === null) {
                $desgloseCompleto = false;
                break;
            }
        }
        $provisional = !$desgloseCompleto;

        if ($provisional) {
            // Los pasos de días fijos se respetan y el RESTO de la mediana se reparte entre los
            // que tienen peso, re-normalizados sobre los activos. La mediana es la duración del
            // proceso COMPLETO para ese tipo —ya incluye el tiempo administrativo real de esas
            // obras—, así que aquí es el sobre entero y no una base a la que sumar. En un paquete
            // CON desglose sí se suma: allí cada número es una medición de su propio paso.
            $mediana = $medianas[$paq['tipo_negociacion']] ?? self::DURACION_FALLBACK_DIAS;
            $fijos = 0;
            $pesos = [];
            foreach ($pasos as $p) {
                $esFijo = $p['colLegacy'] === null;
                $fijos += $esFijo ? (int) ($p['diasFijos'] ?? 0) : 0;
                $pesos[] = $esFijo ? 0.0 : ($p['peso'] ?? 0.0);
            }
            // Si los días fijos ya suman más que la mediana, el resto se topa en cero: el total
            // pasa a ser la suma de los fijos. Nunca un total negativo ni pasos en negativo.
            $reparto = self::repartirMediana(max(0, $mediana - $fijos), $pesos);
            $dias = [];
            foreach ($pasos as $i => $p) {
                $dias[] = $p['colLegacy'] === null ? (int) ($p['diasFijos'] ?? 0) : $reparto[$i];
            }
        } else {
            $dias = [];
            foreach ($pasos as $p) {
                $dias[] = $p['colLegacy'] === null
                    ? (int) ($p['diasFijos'] ?? 0)
                    : (int) $paq[$p['colLegacy']];
            }
        }
        $total = array_sum($dias);

        return [
            'arranque' => (new \DateTimeImmutable($fechaAncla))->modify(sprintf('-%d days', $total))->format('Y-m-d'),
            'total' => $total,
            'dias' => $dias,
            'provisional' => $provisional,
            'duracionRef' => $paq['duracion_ref'] === null ? null : (int) $paq['duracion_ref'],
        ];
    }

    /**
     * @return array{ok: true, calculados: int, sinDuracion: int} `calculados` cuenta DESTINOS
     *         contratables, no paquetes: un paquete partido en tres suma tres.
     */
    public function calcular(int $projectId, string $usuario): array
    {
        // Destinos contratables, no paquetes: un paquete partido aporta una entrada por lote y sus
        // fechas se calculan por separado, porque cada lote se contrata con su propio proveedor y en
        // su propio momento. Un proyecto sin ningún paquete partido produce exactamente la misma
        // lista que producía `amarres()`, en el mismo orden de recorrido.
        $destinos = $this->destinosAmarrados($projectId);
        if ($destinos === []) {
            return ['ok' => true, 'calculados' => 0, 'sinDuracion' => 0];
        }
        $modalidadPorLote = $this->modalidadPorLote($projectId);
        $medianas = $this->medianasPorTipo();
        $pasos = $this->pasos->deProyecto($projectId);
        // Las columnas legacy que ESTA obra necesita, no las siete siempre. `columnasLegacy()` es la
        // lista blanca: `colLegacy` viene de la base y aquí se interpola como nombre de columna.
        $cols = [];
        foreach ($pasos as $p) {
            if ($p['colLegacy'] !== null && in_array($p['colLegacy'], PasosContratacionService::columnasLegacy(), true)) {
                $cols[$p['colLegacy']] = true;
            }
        }
        $selectCols = $cols === []
            ? ''
            : ', ' . implode(', ', array_map(static fn (string $c): string => 'd.' . $c, array_keys($cols)));

        self::exigirIdentidad($pasos);

        $calculados = 0;
        $sinDuracion = 0;

        foreach ($destinos as $a) {
            $paqueteId = $a['paqueteId'];
            $subpaqueteId = $a['subpaqueteId'];
            $pr = $this->proyectar(
                $paqueteId,
                $a['fechaAncla'],
                $pasos,
                $medianas,
                $selectCols,
                $modalidadPorLote[$subpaqueteId] ?? null,
            );
            if ($pr === null) {
                // Paquete inactivo, o cuya modalidad ya no genera proceso de contratación (cambió
                // después de amarrarlo): no se calcula plan para él. Su cabecera vieja, si existe,
                // queda huérfana en pdc_plan_paquete — plan() la filtra por su cuenta.
                continue;
            }
            $provisional = $pr['provisional'];
            $dias = $pr['dias'];
            $total = $pr['total'];
            $arranque = $pr['arranque'];
            $cursor = new \DateTimeImmutable($arranque);
            if ($provisional) {
                $sinDuracion++;
            }

            // La cabecera y sus siete pasos son una sola unidad: si algo falla a mitad de camino,
            // un rollback evita que quede una cabecera con `dias_totales = N` y menos de siete pasos
            // (o pasos de un cálculo anterior mezclados con uno nuevo) sin que nadie lo note.
            $this->db->beginTransaction();
            try {
                $this->db->query(
                    'INSERT INTO pdc_plan_paquete
                        (project_id, paquete_id, subpaquete_id, unique_id, fecha_ancla, fecha_arranque,
                         dias_totales, duracion_ref, duracion_provisional, calculado_por, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE unique_id = VALUES(unique_id), fecha_ancla = VALUES(fecha_ancla),
                        fecha_arranque = VALUES(fecha_arranque), dias_totales = VALUES(dias_totales),
                        duracion_ref = VALUES(duracion_ref), duracion_provisional = VALUES(duracion_provisional),
                        calculado_por = VALUES(calculado_por), updated_at = NOW()',
                    [
                        // Las tres columnas del responsable (responsable_user_id, _asignado_por,
                        // _asignado_at) NO aparecen aquí ni en el ON DUPLICATE KEY UPDATE: lo que no
                        // se lista, MySQL lo conserva. Por eso recalcular el plan no borra a quién se
                        // le asignó cada paquete, y por eso B1 podrá añadir sus columnas sin volver a
                        // tocar este INSERT. No añadirlas sin querer perder esa garantía.
                        $projectId, $paqueteId, $subpaqueteId, $a['uniqueId'], $a['fechaAncla'],
                        $arranque, $total,
                        $pr['duracionRef'], $provisional ? 1 : 0, $usuario,
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
                // Upsert por (project_id, paquete_id, paso_id) desde A4.1: la fila sigue al PASO, no a
                // la posición. Por eso reordenar mueve `orden` dentro de la fila del paso en lugar de
                // sobrescribir la del vecino — que es lo que protegerá el avance real de B1.
                $idsVigentes = [];
                foreach ($pasos as $i => $p) {
                    $ini = $cursor;
                    $cursor = $cursor->modify(sprintf('+%d days', $dias[$i]));
                    if ($p['pasoId'] !== null) {
                        $idsVigentes[] = (int) $p['pasoId'];
                    }
                    $this->db->query(
                        'INSERT INTO pdc_plan_paso (project_id, paquete_id, subpaquete_id, orden, paso_id, paso, dias, fecha_inicio, fecha_fin)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE orden = VALUES(orden), paso = VALUES(paso), dias = VALUES(dias),
                            fecha_inicio = VALUES(fecha_inicio), fecha_fin = VALUES(fecha_fin)',
                        [$projectId, $paqueteId, $subpaqueteId, $i, $p['pasoId'], $p['nombre'], $dias[$i],
                            $ini->format('Y-m-d'), $cursor->format('Y-m-d')],
                    );
                }

                // Sobrantes: filas de pasos que la obra ya no usa. Se filtran por identidad y no por
                // `orden >= N`, que es lo único que funciona cuando la lista se reordena o crece por
                // encima de siete. Se borra DESPUÉS del upsert para no dejar ni un instante al
                // paquete sin sus pasos dentro de la transacción.
                //
                // El `paso_id IS NULL` NO es decorativo: en SQL, `NULL NOT IN (...)` vale NULL —ni
                // verdadero ni falso—, así que sin él una fila sin identidad sobreviviría para siempre
                // a todos los recálculos, invisible. Es además lo que limpia las filas que dejó
                // cualquier cálculo hecho con el esquema anterior.
                //
                // Sin `$idsVigentes` no queda ninguna condición de identidad y el DELETE arrasaba el
                // paquete entero, incluidas las filas recién escritas. Solo puede pasar con pasos sin
                // identidad —`exigirIdentidad()` aborta antes en el camino normal—, y para ese caso la
                // identidad es la posición: sobra lo que tenga `paso_id` o un `orden` por encima de
                // los que se acaban de escribir.
                if ($idsVigentes !== []) {
                    $marcas = implode(',', array_fill(0, count($idsVigentes), '?'));
                    $sobrante = "(paso_id IS NULL OR paso_id NOT IN ({$marcas}))";
                    $argsSobrante = $idsVigentes;
                } else {
                    $sobrante = '(paso_id IS NOT NULL OR orden >= ?)';
                    $argsSobrante = [count($pasos)];
                }

                // Y sobrar no puede costar el avance. Quitar un paso desde «Pasos de contratación»
                // llegaba hasta aquí y borraba la fila con su `fecha_real` dentro, sin aviso ni
                // rastro: la promesa del upsert de arriba cubría el recálculo, no este DELETE. Una
                // propuesta recibida no deja de haberse recibido porque la obra cambie su proceso.
                // Acotado al destino: sin `subpaquete_id` en el WHERE, recalcular un lote borraría los
                // pasos de sus hermanos, que tienen los mismos `paso_id` y por tanto entran todos en
                // la condición de sobrante. Es el borrado más peligroso del servicio.
                $this->db->query(
                    "DELETE FROM pdc_plan_paso
                      WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ? AND fecha_real IS NULL
                       AND {$sobrante}",
                    array_merge([$projectId, $paqueteId, $subpaqueteId], $argsSobrante),
                );
                // Las que se quedan por llevar avance pierden lo programado: son pasos que ya no están
                // en el proceso, así que nadie va a recalcular esas fechas y dejarlas ahí las haría
                // pasar por vigentes. Mismo estado que deja `limpiarPlanCalculado()` tras un reamarre
                // —«esto se hizo, pero el plan ya no lo contempla»—, que la pantalla pinta con un
                // guion en la columna de programado.
                $this->db->query(
                    "UPDATE pdc_plan_paso SET fecha_inicio = NULL, fecha_fin = NULL
                      WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?
                        AND fecha_real IS NOT NULL AND {$sobrante}",
                    array_merge([$projectId, $paqueteId, $subpaqueteId], $argsSobrante),
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
     *
     * Desde A4.1 esto NO depende de la lista de pasos de ninguna obra: es una estadística de la
     * EMPRESA, medida sobre las siete columnas del catálogo. Si dependiera del proyecto que pregunta,
     * la mediana de «a todo costo» valdría una cosa u otra según quién la consultara.
      *
      * @return array<string, int> tipo de negociación → mediana del proceso completo, en días
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
     * Desde A4.1 esto NO depende de la lista de pasos de ninguna obra: es una estadística de la
     * EMPRESA, medida sobre las siete columnas del catálogo. Los pasos que una obra agregue (Licify,
     * aprobación del cliente) no tienen columna ahí y llevan días fijos, así que no entran a esta
     * medición ni la desplazan.
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
     * Exige que todo paso efectivo traiga su identidad (`paso_id`) antes de escribir nada.
     *
     * Desde A4.1 la identidad no es un adorno: es la clave única de `pdc_plan_paso`
     * `(project_id, paquete_id, paso_id)`, y de ella dependen dos cosas a la vez. Sin identidad,
     * (1) en un índice único NULL nunca iguala a NULL, así que el `ON DUPLICATE KEY` del upsert no
     * encontraría la fila y cada recálculo duplicaría los pasos; y (2) `$idsVigentes` quedaría vacío,
     * con lo que el DELETE de sobrantes se quedaría SIN condición y borraría las filas recién
     * insertadas — dejando una cabecera que dice «85 días» y cero pasos, sin que nada fallara.
     *
     * Se para en seco en vez de degradar: un plan a medias es peor que no calcularlo, porque nadie
     * lo nota. El único modo de llegar aquí es un catálogo `general_pasos_contratacion` sin sembrar,
     * que es exactamente lo que arregla la migración que nombra el mensaje.
     *
     * @param list<array{pasoId:int|null, clave:string, nombre:string, colLegacy:string|null, diasFijos:int|null, peso:float|null}> $pasos
     */
    public static function exigirIdentidad(array $pasos): void
    {
        $sinId = [];
        foreach ($pasos as $p) {
            if ($p['pasoId'] === null) {
                $sinId[] = $p['clave'];
            }
        }
        if ($sinId === []) {
            return;
        }
        throw new \RuntimeException(
            'No se puede calcular el plan: estos pasos no existen en el catálogo '
            . 'general_pasos_contratacion (' . implode(', ', $sinId) . '). '
            . 'Aplica la migración 20260728_pdc_v2_pasos_configurables.php.',
        );
    }

    /**
     * Reparte una duración total entre pasos según sus pesos. Sin pesos explícitos, `PESOS_REPARTO`
     * —el proceso por defecto—, para que los llamadores anteriores a A4.1 den el mismo resultado.
     *
     * Un peso de cero significa «este paso no entra al reparto» (los pasos de días fijos, que traen
     * su número puesto): ni recibe su parte proporcional ni puede recibir un día del residuo.
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
     * @param list<float>|null $pesos
     * @return list<int>
     */
    public static function repartirMediana(int $total, ?array $pesos = null): array
    {
        $pesos = $pesos ?? self::PESOS_REPARTO;
        $n = count($pesos);
        $dias = array_fill(0, $n, 0);
        $sumaPesos = array_sum($pesos);
        // Sin total que repartir, sin pasos, o con todos los pesos en cero (una obra cuyos pasos son
        // todos de días fijos): nada que hacer, y sobre todo ninguna división por cero.
        if ($total <= 0 || $n === 0 || $sumaPesos <= 0) {
            return $dias;
        }
        $restos = [];
        $acum = 0;
        foreach ($pesos as $i => $w) {
            $exacto = $total * $w / $sumaPesos;
            $piso = (int) floor($exacto);
            $dias[$i] = $piso;
            $restos[$i] = $w > 0 ? $exacto - $piso : -1.0; // peso cero: fuera del residuo
            $acum += $piso;
        }
        // Los días que dejó el redondeo hacia abajo van a los restos mayores; entre restos iguales
        // gana el paso más temprano, para que el reparto sea determinista.
        $orden = array_values(array_filter(array_keys($restos), static fn (int $i): bool => $restos[$i] >= 0));
        usort($orden, static fn (int $a, int $b): int => $restos[$b] <=> $restos[$a] ?: $a <=> $b);
        $m = count($orden);
        for ($k = 0; $m > 0 && $k < $total - $acum; $k++) {
            $dias[$orden[$k % $m]]++;
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
      *
      * @return list<array{
      *     paqueteId: int,
      *     nombre: string,
      *     tipoNegociacion: string,
      *     modalidad: string,
      *     frenteNombre: string,
      *     uniqueId: int,
      *     fechaAncla: string,
      *     fechaArranque: string,
      *     diasTotales: int,
      *     duracionProvisional: bool,
      *     responsableUserId: int|null,
      *     responsableNombre: string,
      *     responsableCargo: string,
      *     responsableHuerfano: bool,
      *     diasRetraso: int,
      *     pasos: list<array{
      *         orden: int,
      *         paso: string,
      *         dias: int,
      *         fechaInicio: string,
      *         fechaFin: string,
      *         clave: string
      *     }>
      * }> los vencidos primero
      */
    public function plan(int $projectId): array
    {
        $rows = $this->db->query(
            "SELECT pp.paquete_id, pp.subpaquete_id, pp.unique_id, pp.fecha_ancla, pp.fecha_arranque,
                    pp.dias_totales,
                    pp.duracion_provisional, pp.responsable_user_id, p.nombre, p.tipo_negociacion,
                    p.modalidad_contratacion, f.frente_nombre,
                    s.nombre AS lote_nombre, s.modalidad_contratacion AS lote_modalidad, s.es_resto,
                    u.nombre AS responsable_nombre, u.cargo AS responsable_cargo,
                    u.activo AS responsable_activo, pm.user_id AS responsable_miembro
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             -- El amarre se une por DESTINO (paquete + lote) y no solo por paquete: en un paquete
             -- partido hay una fila de amarre por lote, y unir solo por paquete multiplicaría cada
             -- cabecera por el número de lotes del paquete.
             JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
                                      AND f.subpaquete_id = pp.subpaquete_id
             LEFT JOIN pdc_subpaquete s ON s.project_id = pp.project_id AND s.id = pp.subpaquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             LEFT JOIN project_members pm ON pm.project_id = pp.project_id AND pm.user_id = pp.responsable_user_id
             WHERE pp.project_id = ? AND p.activo = 1
               -- Una cabecera sin fechas existe solo para guardar al responsable de un paquete que
               -- ya no tiene plan (ver desamarrar()): no es una fila de la grilla. Sin este filtro
               -- aparecería con las fechas en blanco, indistinguible de un error de cálculo.
               AND pp.fecha_arranque IS NOT NULL
               -- La modalidad que decide es la del DESTINO: la del lote si la fila es un lote, la del
               -- paquete si no está partido. Un lote de cerámica que la obra pasó a orden de compra
               -- sigue generando proceso; uno que pasó a provisión, no, aunque «Pisos» sea contrato.
               AND ((pp.subpaquete_id = 0
                     AND p.modalidad_contratacion IN (" . self::modalidadesConProcesoSql() . '))
                    OR (pp.subpaquete_id <> 0
                        AND s.modalidad_contratacion IN (' . self::modalidadesConProcesoSql() . ')))
             ORDER BY pp.fecha_arranque ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $hoyStr = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $pasos = [];
        foreach ($this->db->query(
            'SELECT pp.paquete_id, pp.subpaquete_id, pp.orden, pp.paso, pp.dias, pp.fecha_inicio,
                    pp.fecha_fin, pp.fecha_real, g.clave
             FROM pdc_plan_paso pp
             LEFT JOIN general_pasos_contratacion g ON g.id = pp.paso_id
             WHERE pp.project_id = ? ORDER BY pp.paquete_id, pp.subpaquete_id, pp.orden',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $fechaReal = $p['fecha_real'] === null ? null : (string) $p['fecha_real'];
            // Indexado por DESTINO. Con la clave puesta solo en el paquete, los pasos de todos los
            // lotes de un paquete partido caían en la misma lista y cada lote mostraba los siete
            // pasos de sus hermanos además de los suyos.
            $pasos[(int) $p['paquete_id'] . ':' . (int) $p['subpaquete_id']][] = [
                'orden' => (int) $p['orden'], 'paso' => (string) $p['paso'], 'dias' => (int) $p['dias'],
                'fechaInicio' => (string) $p['fecha_inicio'], 'fechaFin' => (string) $p['fecha_fin'],
                // La identidad del paso, para que el consumidor no tenga que casar por nombre —que la
                // obra puede haber renombrado con su alias.
                'clave' => (string) ($p['clave'] ?? ''),
                'fechaReal' => $fechaReal,
                // El semáforo lo resuelve la MISMA función que el tablero de vencimientos. Es lo único
                // que garantiza que el color de esta tabla y la lista de la pestaña no se contradigan:
                // si algún día cambian los cortes, cambian en los dos sitios a la vez o en ninguno.
                // Un paso ya cumplido no vence: sale del semáforo, igual que sale del tablero.
                'vencimiento' => $fechaReal !== null
                    ? 'cumplido'
                    : SeguimientoService::clasificarVencimiento(
                        $p['fecha_fin'] === null ? null : (string) $p['fecha_fin'],
                        $hoyStr,
                    )['estado'],
            ];
        }

        $hoy = new \DateTimeImmutable('today');
        $out = [];
        foreach ($rows as $r) {
            $arranque = new \DateTimeImmutable((string) $r['fecha_arranque']);
            $retraso = $arranque < $hoy ? (int) $hoy->diff($arranque)->days : 0;
            $subpaqueteId = (int) $r['subpaquete_id'];
            $esLote = $subpaqueteId !== SubpaquetesService::SIN_PARTIR;
            $out[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'subpaqueteId' => $subpaqueteId,
                'esLote' => $esLote,
                'esResto' => $esLote && (int) $r['es_resto'] === 1,
                // El nombre de la fila es el del lote cuando es un lote, porque es lo que se
                // contrata; el del paquete queda aparte para poder escribir «Pisos › Porcelanato».
                'nombre' => $esLote ? (string) $r['lote_nombre'] : (string) $r['nombre'],
                'paqueteNombre' => (string) $r['nombre'],
                'tipoNegociacion' => (string) $r['tipo_negociacion'],
                'modalidad' => $esLote ? (string) $r['lote_modalidad'] : (string) $r['modalidad_contratacion'],
                'frenteNombre' => (string) ($r['frente_nombre'] ?? ''),
                'uniqueId' => (int) $r['unique_id'],
                'fechaAncla' => (string) $r['fecha_ancla'],
                'fechaArranque' => (string) $r['fecha_arranque'],
                'diasTotales' => (int) $r['dias_totales'],
                'duracionProvisional' => (int) $r['duracion_provisional'] === 1,
                'responsableUserId' => $r['responsable_user_id'] === null ? null : (int) $r['responsable_user_id'],
                'responsableNombre' => (string) ($r['responsable_nombre'] ?? ''),
                'responsableCargo' => (string) ($r['responsable_cargo'] ?? ''),
                // Huérfano = tiene responsable, pero ya no es miembro del proyecto o está inactivo.
                // Sin responsable no hay nadie a quien marcar, así que es false, no true.
                'responsableHuerfano' => $r['responsable_user_id'] !== null
                    && ($r['responsable_miembro'] === null || (int) $r['responsable_activo'] !== 1),
                'diasRetraso' => $retraso,
                'pasos' => $pasos[(int) $r['paquete_id'] . ':' . $subpaqueteId] ?? [],
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
     * Usuarios que pueden ser responsables de un paquete en este proyecto.
     *
     * La FK `fk_ppp_responsable` solo garantiza que el usuario EXISTE, no que pertenezca a este
     * proyecto: sin este filtro, un id de otra obra pasaría la restricción de la base sin que nada
     * lo notara. Esta lista es, por tanto, la definición de «elegible» que usan tanto el selector de
     * la pantalla como la validación de `asignarResponsable()` — deliberadamente una sola.
     *
     * @return list<array{id: int, nombre: string, cargo: string}>
     */
    public function responsablesElegibles(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT u.id, u.nombre, u.cargo
             FROM project_members pm
             JOIN general_usuarios u ON u.id = pm.user_id
             WHERE pm.project_id = ? AND u.activo = 1
             ORDER BY u.nombre',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'nombre' => (string) $r['nombre'],
                'cargo' => (string) $r['cargo'],
            ];
        }
        return $out;
    }

    /**
     * Asigna (o quita, con null) el responsable de uno o varios paquetes.
     *
     * Todo o nada: si cualquier paquete del lote no tiene plan, o el responsable no es elegible, no
     * se escribe ninguno. Dejar la mitad asignada sería peor que no hacer nada, porque nadie sabría
     * qué mitad quedó hecha. Por eso ambas validaciones van antes del UPDATE.
     *
     * No se usa `rowCount()` del UPDATE para decidir si las filas existen: este repo no activa
     * PDO::MYSQL_ATTR_FOUND_ROWS (ver Database.php), así que MySQL reporta filas MODIFICADAS, no
     * coincidentes — guardar el mismo responsable dos veces seguidas daría 0 y parecería que el
     * paquete no tiene plan. La existencia se confirma contando cuántos de los ids pedidos aparecen
     * en `pdc_plan_paquete` y comparando contra el tamaño del lote.
     *
     * @param list<int> $paqueteIds
     * @return array{ok: true, asignados: int}|array{ok: false, code: string}
     */
    public function asignarResponsable(
        int $projectId,
        array $paqueteIds,
        ?int $responsableUserId,
        string $usuario
    ): array {
        $ids = [];
        foreach ($paqueteIds as $id) {
            $n = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($n !== false) {
                $ids[] = (int) $n;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return ['ok' => false, 'code' => 'PAQUETE_SIN_PLAN'];
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $conPlan = (int) $this->db->query(
            "SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id IN ($marcas)",
            array_merge([$projectId], $ids),
        )->fetchColumn();
        if ($conPlan !== count($ids)) {
            return ['ok' => false, 'code' => 'PAQUETE_SIN_PLAN'];
        }

        if ($responsableUserId !== null) {
            $elegible = false;
            foreach ($this->responsablesElegibles($projectId) as $e) {
                if ($e['id'] === $responsableUserId) {
                    $elegible = true;
                    break;
                }
            }
            if (!$elegible) {
                return ['ok' => false, 'code' => 'RESPONSABLE_NO_ELEGIBLE'];
            }
        }

        // `responsable_asignado_por` se escribe también al vaciar: la columna registra quién tocó
        // la asignación por última vez, y quitar a alguien es justo el movimiento que más interesa
        // poder rastrear después.
        $this->db->query(
            "UPDATE pdc_plan_paquete
                SET responsable_user_id = ?, responsable_asignado_por = ?, responsable_asignado_at = NOW()
              WHERE project_id = ? AND paquete_id IN ($marcas)",
            array_merge([$responsableUserId, mb_substr($usuario, 0, 100), $projectId], $ids),
        );

        return ['ok' => true, 'asignados' => count($ids)];
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
      *
      * @return list<array{
      *     paqueteId: int,
      *     nombre: string,
      *     frenteNombre: string,
      *     fechaGuardada: string,
      *     fechaActual: string|null,
      *     diasMovidos: int|null
      * }> `fechaActual` y `diasMovidos` van en null —y no en '' ni en 0— cuando el amarre quedó
      *    huérfano: no hay fecha nueva que comparar, que es distinto de «se movió 0 días»
      */
    /**
     * El antes/después de aplicar al plan la reprogramación del cronograma. NO escribe nada.
     *
     * Es la mitad «mirar» de la operación que más daño puede hacer del módulo; la mitad «escribir»
     * es `aplicarReprogramacion()`, y solo corre si el usuario confirma. Se proyecta con el ancla
     * NUEVA y se compara contra lo que hay guardado, sin tocar la base: por eso cancelar no puede
     * dejar nada a medias, no hay nada que deshacer.
     *
     * Los huérfanos —amarres a un frente que ya no está en el cronograma— van en su propia lista y
     * NUNCA en `movidos`: no hay fecha nueva contra la que proyectar, y elegirles otro frente es
     * una decisión humana que este módulo no toma por nadie.
     *
     * @return array{
     *     movidos: list<array{paqueteId:int,nombre:string,frenteNombre:string,anclaActual:string,
     *         anclaNueva:string,diasMovidos:int,arranqueActual:?string,arranqueNuevo:string,
     *         pasosQueSeMueven:int,pasosConFechaReal:int}>,
     *     huerfanos: list<array{paqueteId:int,nombre:string,frenteNombre:string,anclaActual:string}>
     * }
     */
    public function simularReprogramacion(int $projectId): array
    {
        $desfases = $this->desfases($projectId);
        if ($desfases === []) {
            return ['movidos' => [], 'huerfanos' => []];
        }

        $medianas = $this->medianasPorTipo();
        $pasos = $this->pasos->deProyecto($projectId);
        self::exigirIdentidad($pasos);
        $cols = [];
        foreach ($pasos as $p) {
            if ($p['colLegacy'] !== null && in_array($p['colLegacy'], PasosContratacionService::columnasLegacy(), true)) {
                $cols[$p['colLegacy']] = true;
            }
        }
        $selectCols = $cols === []
            ? ''
            : ', ' . implode(', ', array_map(static fn (string $c): string => 'd.' . $c, array_keys($cols)));

        $movidos = [];
        $huerfanos = [];
        foreach ($desfases as $d) {
            if ($d['fechaActual'] === null) {
                $huerfanos[] = [
                    'paqueteId' => $d['paqueteId'],
                    'nombre' => $d['nombre'],
                    'frenteNombre' => $d['frenteNombre'],
                    'anclaActual' => $d['fechaGuardada'],
                ];
                continue;
            }
            $pr = $this->proyectar($d['paqueteId'], $d['fechaActual'], $pasos, $medianas, $selectCols);
            if ($pr === null) {
                // Inactivo o sin proceso de contratación: `calcular()` tampoco lo tocaría, así que
                // prometer un delta que luego no se aplicaría sería mentir en pantalla.
                continue;
            }
            $cab = $this->db->query(
                'SELECT fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
                [$projectId, $d['paqueteId']],
            )->fetch(\PDO::FETCH_ASSOC);
            // Los pasos que ya ocurrieron se cuentan aparte y se dicen en pantalla: son justo los
            // que NO se van a mover, y es la garantía que hace segura esta operación.
            $conReal = (int) $this->db->query(
                'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NOT NULL',
                [$projectId, $d['paqueteId']],
            )->fetchColumn();

            $movidos[] = [
                'paqueteId' => $d['paqueteId'],
                'nombre' => $d['nombre'],
                'frenteNombre' => $d['frenteNombre'],
                'anclaActual' => $d['fechaGuardada'],
                'anclaNueva' => $d['fechaActual'],
                'diasMovidos' => (int) $d['diasMovidos'],
                'arranqueActual' => $cab === false ? null : (string) $cab['fecha_arranque'],
                'arranqueNuevo' => $pr['arranque'],
                'pasosQueSeMueven' => count($pasos),
                'pasosConFechaReal' => $conReal,
            ];
        }
        return ['movidos' => $movidos, 'huerfanos' => $huerfanos];
    }

    /**
     * Aplica la reprogramación del cronograma SOLO a los paquetes que el usuario confirmó.
     *
     * Refresca la `fecha_ancla` del amarre desde el cronograma en vivo y recalcula. Ese refresco es
     * el punto: sin él, `calcular()` vuelve a proyectar contra la copia congelada del ancla y el
     * desfase no se va nunca — el bug que midió B2, ver
     * `goals/pdc-preparar-b1/evidence/medicion-rematching-2026-07-29.md`.
     *
     * Un amarre cuyo frente ya no está en el cronograma se IGNORA y se cuenta: no hay fecha nueva a
     * la que moverlo, y elegirle otro frente es una decisión humana. Se devuelve en `ignorados` para
     * que la pantalla pueda decir cuántos quedaron pendientes en vez de callarlo.
     *
     * `fecha_real` no corre peligro: `calcular()` hace upsert de `pdc_plan_paso` listando solo las
     * columnas programadas, y lo que no se lista MySQL lo conserva.
     *
     * @param list<int> $paqueteIds los confirmados en pantalla; una lista vacía no escribe nada
     * @return array{ok:true,aplicados:int,ignorados:int}
     */
    public function aplicarReprogramacion(int $projectId, array $paqueteIds, string $usuario): array
    {
        $pedidos = array_values(array_unique(array_map('intval', $paqueteIds)));
        if ($pedidos === []) {
            return ['ok' => true, 'aplicados' => 0, 'ignorados' => 0];
        }

        $frentes = [];
        foreach ($this->frentesDisponibles($projectId) as $f) {
            $frentes[$f['uniqueId']] = $f;
        }

        $aplicados = 0;
        $ignorados = 0;
        $this->db->beginTransaction();
        try {
            foreach ($pedidos as $paqueteId) {
                $r = $this->db->query(
                    'SELECT unique_id, fecha_ancla FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
                    [$projectId, $paqueteId],
                )->fetch(\PDO::FETCH_ASSOC);
                if ($r === false) {
                    $ignorados++;
                    continue;
                }
                $f = $frentes[(int) $r['unique_id']] ?? null;
                if ($f === null) {
                    $ignorados++; // huérfano: sin frente vivo no hay reprogramación que aplicar
                    continue;
                }
                if ($f['fechaInicio'] === (string) $r['fecha_ancla']) {
                    $ignorados++; // ya estaba al día; nada que mover
                    continue;
                }
                $this->db->query(
                    'UPDATE pdc_paquete_frente SET fecha_ancla = ? WHERE project_id = ? AND paquete_id = ?',
                    [$f['fechaInicio'], $projectId, $paqueteId],
                );
                $aplicados++;
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        if ($aplicados > 0) {
            // `calcular()` abre su propia transacción por paquete, así que corre FUERA de la de
            // arriba: PDO no anida transacciones de verdad, y un rollback interno se llevaría por
            // delante el refresco de las anclas ya confirmadas.
            $this->calcular($projectId, $usuario);
        }
        return ['ok' => true, 'aplicados' => $aplicados, 'ignorados' => $ignorados];
    }

    /**
     * @return list<array{
     *     paqueteId: int,
     *     nombre: string,
     *     frenteNombre: string,
     *     fechaGuardada: string,
     *     fechaActual: string|null,
     *     diasMovidos: int|null
     * }> `fechaActual` y `diasMovidos` en null = el frente ya no existe en el cronograma
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
