<?php

namespace App\Services\Pdc;

/**
 * B1 · Amarra cada fila de `pdc_insumo_actividades` a una actividad del cronograma (`unique_id`).
 *
 * POR QUÉ NO ES UN EMPAREJAMIENTO DIRECTO. La tabla nació con `unique_id` NULL y una nota que decía
 * «lo llena A4». A4 nunca lo llenó, y al medirlo (DAPORTO v292, 2026-07-28) se vio que no podía:
 * de 820 filas, UNA casa por nombre con una actividad del cronograma. No es un problema de tildes
 * ni de mayúsculas — presupuesto y cronograma hablan idiomas distintos:
 *
 *   · el presupuesto (401 actividades) dice lo que se mide y se paga: «ACERO ESTRUCTURA»,
 *     «MURO EN LADRILLO SUCIO INTERIOR E=10CM», «PORCELANATO REF. POR DEFINIR BEIGE MATE»;
 *   · el cronograma (242 hojas) dice la secuencia constructiva: «COLUMNAS PISO 5»,
 *     «LOSA AÉREA SÓTANO 2», y para acabados apenas «SÓTANO 3» colgando de un frente.
 *
 * «ACERO ESTRUCTURA» es UNA fila del presupuesto que alimenta ~30 actividades del cronograma: la
 * relación es muchos-a-muchos. Tampoco hay puente por código: `programa_consolidado.codigo_actividad`
 * está vacío en las 273 filas de las 4 semanas de DAPORTO.
 *
 * QUÉ SE AMARRA ENTONCES. La RAMA, no la hoja: el subcapítulo (o el grupo) del presupuesto al que
 * pertenece el insumo se amarra al frente del cronograma donde esa rama se construye. Es la misma
 * ruta que A4 ya usa para los paquetes en `pdc_paquete_frente` (`origen = 'rama'`), aplicada un
 * nivel más abajo. La decisión y el mapa los confirmó obra el 2026-07-28.
 *
 * SIGNIFICADO DE `unique_id` (leer antes de usarlo): NO es «la actividad que consume este insumo»,
 * es «el nodo del cronograma que marca cuándo arranca la rama que lo consume». La fecha que da es
 * `Fecha_Inicio` de ese nodo. Como la `Fecha_Inicio` de un frente es la mínima de sus hijos, esa
 * fecha ES la del primer consumo de la rama — que es justo lo que Seguimiento pide para la primera
 * entrega de una orden de compra.
 *
 * ORDEN DE RESOLUCIÓN (gana el primero que acierta, de lo más específico a lo más general):
 *   1. override sembrado sobre el GRUPO       (nivel 3 del presupuesto)
 *   2. override sembrado sobre el SUBCAPÍTULO (nivel 2)
 *   3. nombre exacto  subcapítulo ↔ frente
 *   4. similitud de palabras subcapítulo ↔ frente
 * El capítulo (nivel 1) queda fuera a propósito: solo vale «COSTO DIRECTO»/«COSTO INDIRECTO» y no
 * dice nada de obra. Los overrides van ANTES que lo automático porque hay parejas que el texto
 * resuelve mal: «CARPINTERIA METALICA» se parece a «CARPINTERIA EN MADERA» (Jaccard 0,33) pero su
 * frente real es VENTANERÍA.
 *
 * @phpstan-type Nodo array{uniqueId: int, nombre: string, norm: string, tok: list<string>, titulo: bool, fechaInicio: string}
 * @phpstan-type Amarre array{uniqueId: int|null, origen: string, evidencia: string}
 */
class AmarreCronogramaService
{
    /**
     * Similitud mínima de nombre (Jaccard sobre palabras) para amarrar un subcapítulo a un frente.
     *
     * Es el mismo 0,33 de `PlanFechasService::SIMILITUD_MINIMA`, y a propósito: las dos comparan el
     * vocabulario del presupuesto contra el mismo árbol de frentes, así que dos umbrales distintos
     * solo producirían amarres incoherentes entre el paquete y sus insumos. Con este valor
     * «URBANISMO Y OBRAS EXTERIORES» (3 palabras) alcanza el frente «URBANISMO» (1 palabra) con
     * 1/3 = 0,3333, que es el caso límite que el umbral tiene que dejar pasar.
     */
    private const SIMILITUD_MINIMA = 0.33;

    /**
     * Palabras que no distinguen un oficio de otro y por tanto no deben contar en la similitud.
     * Las de una o dos letras se filtran por longitud («Y», «DE», «EN», «A», «E», y el «-» suelto de
     * «ZONAS COMUNES - DOTACIONES»); estas son las que sobreviven a ese filtro.
     */
    private const VACIAS = ['DEL', 'LOS', 'LAS', 'CON', 'PARA', 'POR', 'SIN', 'SUS', 'QUE'];

    /** @var array<string, array{frente: string, nota: string}>|null caché del seed por proyecto */
    private ?array $reglas = null;

    /**
     * `$conReglas = false` apaga el mapa sembrado y deja solo el emparejamiento automático.
     *
     * Existe por la misma razón que `PaquetesService::$conOverrides` (A3.3): sin poder correr el
     * motor a secas no hay forma de saber qué reglas están sosteniendo el resultado y cuáles son
     * memoria redundante de un ejercicio. Se usa desde el test, que falla si alguna regla deja de
     * cambiar el destino de su rama.
     */
    public function __construct(private readonly \Database $db, private readonly bool $conReglas = true)
    {
    }

    /**
     * Resuelve el amarre de cada rama de una versión del presupuesto.
     *
     * Devuelve el mapa `codigo de la actividad del presupuesto` → amarre, para que quien escriba
     * (la migración o el motor de paquetes) solo tenga que buscar por el `codigo` que ya tiene en
     * la mano. `semana` es la del consolidado contra el que se resolvió: se persiste para que B2
     * sepa contra qué versión del cronograma se calculó este amarre.
     *
     * @return array{semana: int|null, porCodigo: array<string, Amarre>}
     */
    public function resolverVersion(int $projectId, int $versionId): array
    {
        $cron = $this->nodosDeCronograma($projectId);
        if ($cron['semana'] === null || $cron['nodos'] === []) {
            return ['semana' => null, 'porCodigo' => []];
        }

        $ramas = $this->ramasDe($projectId, $versionId);
        $codigos = $this->db->query(
            'SELECT DISTINCT codigo FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ?',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_COLUMN);

        $porCodigo = [];
        foreach ($codigos as $codigo) {
            $porCodigo[(string) $codigo] = $this->resolverCodigo((string) $codigo, $ramas, $cron['nodos'], $projectId);
        }

        return ['semana' => $cron['semana'], 'porCodigo' => $porCodigo];
    }

    /**
     * Resuelve Y escribe el amarre de una versión. Es el único camino de escritura: lo usan tanto el
     * backfill (`20260729_pdc_v2_amarre_cronograma.php`) como el motor de paquetes al materializar el
     * mapa. Que sean el mismo código es lo que sostiene el invariante — si el backfill y la escritura
     * en caliente divergieran, una reimportación volvería a dejar filas sin amarre, que es justo cómo
     * se llegó a las 820 en NULL.
     *
     * Idempotente: solo escribe las filas cuyo `unique_id` cambiaría. Con `$aplicar = false` calcula
     * y reporta sin tocar nada (es el dry-run de la migración).
     *
     * @return array{
     *     semana: int|null,
     *     porOrigen: array<string, array{filas: int, valor: float}>,
     *     huerfanas: array<string, array{filas: int, valor: float, motivo: string}>,
     *     cambios: int
     * }
     */
    public function amarrarVersion(int $projectId, int $versionId, bool $aplicar = true): array
    {
        $vacio = ['semana' => null, 'porOrigen' => [], 'huerfanas' => [], 'cambios' => 0];

        $res = $this->resolverVersion($projectId, $versionId);
        if ($res['semana'] === null) {
            return $vacio;
        }

        $filas = $this->db->query(
            'SELECT id, codigo, unique_id, valor, origen_amarre, evidencia_amarre, semana_amarre
             FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ?',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $porOrigen = [];
        $huerfanas = [];
        $cambios = [];

        foreach ($filas as $f) {
            $amarre = $res['porCodigo'][(string) $f['codigo']] ?? null;
            if ($amarre === null) {
                continue;
            }
            $valor = (float) $f['valor'];
            $origen = $amarre['origen'];
            $porOrigen[$origen]['filas'] = ($porOrigen[$origen]['filas'] ?? 0) + 1;
            $porOrigen[$origen]['valor'] = ($porOrigen[$origen]['valor'] ?? 0.0) + $valor;

            if ($amarre['uniqueId'] === null) {
                $clave = (string) $f['codigo'];
                $huerfanas[$clave]['filas'] = ($huerfanas[$clave]['filas'] ?? 0) + 1;
                $huerfanas[$clave]['valor'] = ($huerfanas[$clave]['valor'] ?? 0.0) + $valor;
                $huerfanas[$clave]['motivo'] = $amarre['evidencia'];
            }

            // La comparación incluye la trazabilidad, no solo el `unique_id`. Si solo mirara el id,
            // una fila sin frente (NULL → NULL) nunca se escribiría y se quedaría sin su motivo:
            // indistinguible de una que jamás se calculó, que es el agujero que dejó A4.
            $evidencia = mb_substr($amarre['evidencia'], 0, 500);
            $igual = ($f['unique_id'] === null ? null : (int) $f['unique_id']) === $amarre['uniqueId']
                && (string) ($f['origen_amarre'] ?? '') === $amarre['origen']
                && (string) ($f['evidencia_amarre'] ?? '') === $evidencia
                && ($f['semana_amarre'] === null ? null : (int) $f['semana_amarre']) === $res['semana'];
            if (!$igual) {
                $cambios[] = ['id' => (int) $f['id'], 'amarre' => $amarre, 'evidencia' => $evidencia];
            }
        }

        if ($aplicar) {
            foreach ($cambios as $c) {
                $this->db->query(
                    'UPDATE pdc_insumo_actividades
                        SET unique_id = ?, origen_amarre = ?, evidencia_amarre = ?, semana_amarre = ?
                      WHERE id = ?',
                    [$c['amarre']['uniqueId'], $c['amarre']['origen'], $c['evidencia'], $res['semana'], $c['id']],
                );
            }
        }

        ksort($porOrigen);
        uasort($huerfanas, static fn (array $a, array $b): int => $b['valor'] <=> $a['valor']);

        return [
            'semana' => $res['semana'],
            'porOrigen' => $porOrigen,
            'huerfanas' => $huerfanas,
            'cambios' => count($cambios),
        ];
    }

    /**
     * Amarre de un código del presupuesto, subiendo por su rama.
     *
     * @param array<string, array{nombre: string, tipo: string}> $ramas
     * @param list<Nodo>                                         $nodos
     *
     * @return Amarre
     */
    private function resolverCodigo(string $codigo, array $ramas, array $nodos, int $projectId): array
    {
        $partes = explode('.', $codigo);
        $reglas = $this->reglas($projectId);
        $visitadas = [];

        // De lo más específico (grupo) a lo más general (subcapítulo). El capítulo queda fuera.
        for ($n = count($partes) - 1; $n >= 2; $n--) {
            $prefijo = implode('.', array_slice($partes, 0, $n));
            $rama = $ramas[$prefijo] ?? null;
            if ($rama === null) {
                continue;
            }
            $norm = MaestroInsumosService::normalizar($rama['nombre']);
            $visitadas[] = $rama['nombre'];

            if (isset($reglas[$norm])) {
                $nodo = $this->nodoPorNombre($reglas[$norm]['frente'], $nodos);
                if ($nodo !== null) {
                    return [
                        'uniqueId' => $nodo['uniqueId'],
                        'origen' => 'override',
                        'evidencia' => sprintf(
                            'La rama «%s» se ancla a «%s» (arranca %s) por regla sembrada: %s',
                            $rama['nombre'],
                            $nodo['nombre'],
                            $nodo['fechaInicio'],
                            $reglas[$norm]['nota'],
                        ),
                    ];
                }
                // La regla existe pero su frente no está en ESTE cronograma: se sigue subiendo, y
                // si nada más acierta el motivo dirá que la regla apuntó a un frente inexistente.
                $visitadas[] = 'regla → «' . $reglas[$norm]['frente'] . '» (no existe en el cronograma)';
            }

            // Lo automático solo se intenta sobre el subcapítulo. Sobre el grupo produce falsos
            // positivos (p. ej. «PISOS EN ZONAS COMUNES» compite con el frente «PISOS Y ENCHAPES»
            // sin ser lo mismo) y su nivel correcto es siempre una regla explícita.
            if ($rama['tipo'] !== 'subcapitulo') {
                continue;
            }

            $exacto = $this->nodoPorNombre($rama['nombre'], $nodos, true);
            if ($exacto !== null) {
                return [
                    'uniqueId' => $exacto['uniqueId'],
                    'origen' => 'exacta',
                    'evidencia' => sprintf(
                        'El subcapítulo «%s» se llama igual que el frente «%s» del cronograma (arranca %s)',
                        $rama['nombre'],
                        $exacto['nombre'],
                        $exacto['fechaInicio'],
                    ),
                ];
            }

            $similar = $this->mejorPorTokens($rama['nombre'], $nodos);
            if ($similar !== null) {
                return [
                    'uniqueId' => $similar['nodo']['uniqueId'],
                    'origen' => 'tokens',
                    'evidencia' => sprintf(
                        'El subcapítulo «%s» se parece al frente «%s» (arranca %s, similitud %.2f)',
                        $rama['nombre'],
                        $similar['nodo']['nombre'],
                        $similar['nodo']['fechaInicio'],
                        $similar['punt'],
                    ),
                ];
            }
        }

        return [
            'uniqueId' => null,
            'origen' => 'sin_frente',
            'evidencia' => $visitadas === []
                ? sprintf('El código «%s» no cuelga de ningún subcapítulo del presupuesto', $codigo)
                : sprintf(
                    'Ninguna rama de «%s» tiene frente en el cronograma (se probó: %s)',
                    $codigo,
                    implode(' → ', $visitadas),
                ),
        ];
    }

    /**
     * Nodo del cronograma con ese nombre. Entre varios gana el que arranca antes —es el que fija la
     * primera entrega—, y eso resuelve solo el caso de un frente padre y un hijo homónimos
     * («PISOS Y ENCHAPES» aparece en DAPORTO como padre el 2027-05-12 y como hijo el 2027-07-08).
     *
     * `$soloTitulos` restringe la búsqueda a los frentes (encabezados): es lo que usa el
     * emparejamiento automático, porque una hoja puede llamarse igual que un frente y significar
     * otra cosa —«RED DE GAS» es una hoja del urbanismo exterior y también parte del frente interno
     * «RED HIDROSANITARIA Y DE GAS»—. Las reglas sembradas sí pueden apuntar a una hoja: es la
     * única forma de anclar una rama cuyo frente el cronograma no modeló, como CUBIERTA.
     *
     * @param list<Nodo> $nodos
     *
     * @return Nodo|null
     */
    private function nodoPorNombre(string $nombre, array $nodos, bool $soloTitulos = false): ?array
    {
        $norm = MaestroInsumosService::normalizar($nombre);
        $mejor = null;
        foreach ($nodos as $nodo) {
            if ($nodo['norm'] !== $norm || ($soloTitulos && !$nodo['titulo'])) {
                continue;
            }
            if ($mejor === null || $nodo['fechaInicio'] < $mejor['fechaInicio']) {
                $mejor = $nodo;
            }
        }
        return $mejor;
    }

    /**
     * Frente más parecido por palabras. Solo mira títulos, por lo mismo que `nodoPorNombre()`.
     *
     * @param list<Nodo> $nodos
     *
     * @return array{nodo: Nodo, punt: float}|null
     */
    private function mejorPorTokens(string $nombre, array $nodos): ?array
    {
        $tp = self::tokens($nombre);
        if ($tp === []) {
            return null;
        }
        $mejor = null;
        $mejorPunt = 0.0;
        foreach ($nodos as $nodo) {
            if (!$nodo['titulo'] || $nodo['tok'] === []) {
                continue;
            }
            $comunes = count(array_intersect($tp, $nodo['tok']));
            if ($comunes === 0) {
                continue;
            }
            $punt = $comunes / max(1, count(array_unique(array_merge($tp, $nodo['tok']))));
            // Empate: gana el que arranca antes, que es el que fija la primera entrega.
            if ($punt > $mejorPunt || ($punt === $mejorPunt && $mejor !== null && $nodo['fechaInicio'] < $mejor['fechaInicio'])) {
                $mejorPunt = $punt;
                $mejor = $nodo;
            }
        }
        return ($mejor !== null && $mejorPunt >= self::SIMILITUD_MINIMA)
            ? ['nodo' => $mejor, 'punt' => $mejorPunt]
            : null;
    }

    /**
     * Palabras significativas de un nombre: sin puntuación, sin partículas y sin repetidos.
     *
     * @return list<string>
     */
    private static function tokens(string $s): array
    {
        $limpio = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', MaestroInsumosService::normalizar($s)) ?? $s;
        $palabras = array_filter(
            explode(' ', MaestroInsumosService::normalizar($limpio)),
            static fn (string $w): bool => mb_strlen($w) > 2 && !in_array($w, self::VACIAS, true),
        );
        return array_values(array_unique($palabras));
    }

    /**
     * Subcapítulos y grupos de una versión, indexados por código.
     *
     * @return array<string, array{nombre: string, tipo: string}>
     */
    private function ramasDe(int $projectId, int $versionId): array
    {
        $rows = $this->db->query(
            "SELECT codigo, descripcion, tipo_fila FROM pdc_presupuesto_items
             WHERE project_id = ? AND version_id = ? AND tipo_fila IN ('subcapitulo', 'grupo')",
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['codigo']] = [
                'nombre' => (string) $r['descripcion'],
                'tipo' => (string) $r['tipo_fila'],
            ];
        }
        return $out;
    }

    /**
     * Nodos del consolidado de la semana activa. Se traen títulos Y hojas: los primeros son los que
     * hablan el idioma de las ramas, las segundas son el único ancla posible cuando el cronograma no
     * modeló un frente para una rama que sí existe en el presupuesto.
     *
     * @return array{semana: int|null, nodos: list<Nodo>}
     */
    private function nodosDeCronograma(int $projectId): array
    {
        $semana = $this->db->query(
            'SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?',
            [$projectId],
        )->fetchColumn();
        if ($semana === false || $semana === null) {
            return ['semana' => null, 'nodos' => []];
        }

        $rows = $this->db->query(
            'SELECT unique_id, Actividad, Titulo, Fecha_Inicio FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND unique_id IS NOT NULL AND Fecha_Inicio IS NOT NULL',
            [$projectId, (int) $semana],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $nodos = [];
        foreach ($rows as $r) {
            // El nombre viene envuelto en HTML con el capítulo embebido; `PlanFechasService` ya sabe
            // desenvolverlo y se reusa su limpiador para no tener dos definiciones del mismo nombre.
            $limpio = PlanFechasService::limpiarActividad((string) $r['Actividad']);
            $nombre = $limpio['nombre'];
            if ($nombre === '') {
                continue;
            }
            $nodos[] = [
                'uniqueId' => (int) $r['unique_id'],
                'nombre' => $nombre,
                'norm' => MaestroInsumosService::normalizar($nombre),
                'tok' => self::tokens($nombre),
                'titulo' => (int) $r['Titulo'] === 1,
                'fechaInicio' => (string) $r['Fecha_Inicio'],
            ];
        }
        return ['semana' => (int) $semana, 'nodos' => $nodos];
    }

    /**
     * Reglas sembradas: rama normalizada → frente. Mismo contrato que los overrides de A3.3
     * (`alcance` global o de proyecto), para que una decisión atada a un presupuesto concreto no se
     * cuele en las demás obras.
     *
     * @return array<string, array{frente: string, nota: string}>
     */
    private function reglas(int $projectId): array
    {
        if ($this->reglas !== null) {
            return $this->reglas;
        }
        $ruta = __DIR__ . '/../../../database/seeds/sembrado_ramas_frentes.json';
        if (!$this->conReglas || !is_file($ruta)) {
            return $this->reglas = [];
        }
        $data = json_decode((string) file_get_contents($ruta), true);
        $crudas = is_array($data['reglas'] ?? null) ? $data['reglas'] : [];

        $out = [];
        foreach ($crudas as $rama => $valor) {
            if (!is_array($valor) || !is_string($valor['frente'] ?? null)) {
                continue;
            }
            $esDeProyecto = ($valor['alcance'] ?? 'global') === 'proyecto';
            if ($esDeProyecto && (int) ($valor['projectId'] ?? 0) !== $projectId) {
                continue;
            }
            $out[MaestroInsumosService::normalizar((string) $rama)] = [
                'frente' => $valor['frente'],
                'nota' => (string) ($valor['nota'] ?? ''),
            ];
        }
        return $this->reglas = $out;
    }
}
