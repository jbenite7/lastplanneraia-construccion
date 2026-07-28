<?php

namespace App\Services\Pdc;

/**
 * Orquesta el import del presupuesto: preview (parsear + guardar temporal)
 * y confirmación transaccional (Task 5).
 *
 * Los valores que salen de PDO sin cast quedan como `mixed` a propósito: con
 * `ATTR_EMULATE_PREPARES => false` el driver devuelve tipos nativos para unas columnas y strings
 * para otras (los DECIMAL), así que afirmar `string` sería mentir. Donde el código castea, el
 * tipo sí es exacto.
 *
 * @phpstan-import-type PresupuestoErrorFila from PresupuestoExcelParser
 * @phpstan-import-type PresupuestoResumen from PresupuestoExcelParser
 */
final class PresupuestoImportService
{
    public function __construct(
        private readonly \Database $db,
        private readonly PresupuestoImportStore $store,
        private readonly PresupuestoExcelParser $parser,
    ) {
    }

    /**
     * Hash SHA-256 del CONTENIDO canónico del presupuesto (items + insumos), estable
     * ante reordenamiento de filas y metadata del Excel. Base del anti-duplicado por contenido.
     *
     * Lee un subconjunto de claves y tolera que falten (de ahí los `?? ''`), así que no exige la
     * forma completa del parser.
     *
     * @param list<array<string, mixed>> $items   usa codigo, tipo_fila, unidad, cantidad
     * @param list<array<string, mixed>> $insumos usa codigo_actividad, descripcion, tipo_insumo,
     *                                            unidad, cantidad_total, valor_total
     */
    public function hashContenido(array $items, array $insumos): string
    {
        $itemLineas = array_map(static function (array $i): string {
            return implode('|', [
                (string) ($i['codigo'] ?? ''),
                (string) ($i['tipo_fila'] ?? ''),
                (string) ($i['unidad'] ?? ''),
                number_format((float) ($i['cantidad'] ?? 0), 4, '.', ''),
            ]);
        }, $items);
        sort($itemLineas, SORT_STRING);

        $insumoLineas = array_map(static function (array $x): string {
            return implode('|', [
                (string) ($x['codigo_actividad'] ?? ''),
                MaestroInsumosService::normalizar((string) ($x['descripcion'] ?? '')),
                // tipo_insumo (raw, como unidad/codigo): la categoría alimenta el maestro A2 y
                // su recategorización debe contar como cambio de contenido → versión nueva.
                (string) ($x['tipo_insumo'] ?? ''),
                (string) ($x['unidad'] ?? ''),
                number_format((float) ($x['cantidad_total'] ?? 0), 4, '.', ''),
                number_format((float) ($x['valor_total'] ?? 0), 2, '.', ''),
            ]);
        }, $insumos);
        sort($insumoLineas, SORT_STRING);

        return hash('sha256', implode("\n", $itemLineas) . "\n##\n" . implode("\n", $insumoLineas));
    }

    /**
     * Versión activa del proyecto (con su número y hash de contenido), o null.
     *
     * @return array<string, mixed>|null fila cruda con id, version_numero, version_label,
     *                                   contenido_hash y created_at
     */
    private function versionActivaDe(int $projectId): ?array
    {
        $row = $this->db->query(
            'SELECT id, version_numero, version_label, contenido_hash, created_at
             FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1',
            [$projectId],
        )->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * @return array{ok: false, errores: list<PresupuestoErrorFila>}|array{
     *     ok: true,
     *     importToken: string,
     *     versionLabel: string|null,
     *     resumen: PresupuestoResumen,
     *     advertencias: list<string>,
     *     sinCambios: bool,
     *     versionActiva: array{id: int, numero: int, label: mixed, createdAt: mixed}|null
     * }
     */
    public function previewDesdeArchivo(string $rutaArchivo, string $nombreOriginal, int $projectId, string $usuario): array
    {
        $resultado = $this->parser->parse($rutaArchivo);
        if (!$resultado['valido']) {
            return ['ok' => false, 'errores' => $resultado['errores']];
        }

        $hash = hash_file('sha256', $rutaArchivo);
        $advertencias = [];
        $repetida = (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ? AND archivo_hash = ?',
            [$projectId, $hash],
        )->fetchColumn();
        if ($repetida > 0) {
            $advertencias[] = 'Este archivo ya fue importado antes en este proyecto (contenido idéntico).';
        }

        $contenidoHash = $this->hashContenido($resultado['items'], $resultado['insumos']);
        $activa = $this->versionActivaDe($projectId);
        $sinCambios = $activa !== null && $activa['contenido_hash'] !== null && $activa['contenido_hash'] === $contenidoHash;

        $token = $this->store->guardar($rutaArchivo, [
            'nombre' => $nombreOriginal,
            'hash' => $hash,
            'contenidoHash' => $contenidoHash,
            'projectId' => $projectId,
            'usuario' => $usuario,
        ]);

        return [
            'ok' => true,
            'importToken' => $token,
            'versionLabel' => $resultado['versionLabel'],
            'resumen' => $resultado['resumen'],
            'advertencias' => $advertencias,
            'sinCambios' => $sinCambios,
            'versionActiva' => $activa === null ? null : [
                'id' => (int) $activa['id'],
                'numero' => (int) $activa['version_numero'],
                'label' => $activa['version_label'],
                'createdAt' => $activa['created_at'],
            ],
        ];
    }

    /**
     * `sinCambios` distingue el caso en que el contenido coincide con la versión activa y no se
     * crea una nueva. `idempotente` sólo aparece cuando el token ya había producido una versión
     * (reintento tras un commit que el cliente no llegó a ver).
     *
     * @return array{ok: false, code: 'TOKEN_EXPIRED'|'INVALID_FILE'}|array{
     *     ok: true,
     *     sinCambios: bool,
     *     versionId: int,
     *     versionNumero: int,
     *     versionLabel: mixed,
     *     versionIdAnterior: int|null,
     *     resumen: PresupuestoResumen,
     *     idempotente?: true
     * }
     */
    public function confirmar(string $token, int $projectId): array
    {
        $ruta = $this->store->ruta($token);
        $meta = $this->store->meta($token);
        if ($ruta === null || $meta === null || (int) ($meta['projectId'] ?? 0) !== $projectId) {
            // Idempotencia: si el token ya produjo una versión (retry tras commit,
            // p.ej. timeout HTTP con el proceso PHP terminado), responder esa versión.
            $existente = $this->versionPorToken($token, $projectId);
            if ($existente !== null) {
                return $existente + ['idempotente' => true];
            }
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED'];
        }

        try {
            $resultado = $this->parser->parse($ruta);
        } catch (\RuntimeException) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        if (!$resultado['valido']) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }

        $contenidoHash = $this->hashContenido($resultado['items'], $resultado['insumos']);
        $activa = $this->versionActivaDe($projectId);
        if ($activa !== null && $activa['contenido_hash'] !== null && $activa['contenido_hash'] === $contenidoHash) {
            // Anti-duplicado: el contenido es idéntico a la versión activa → no se crea una versión nueva.
            $this->store->eliminar($token);
            return [
                'ok' => true,
                'sinCambios' => true,
                'versionId' => (int) $activa['id'],
                'versionNumero' => (int) $activa['version_numero'],
                'versionLabel' => $activa['version_label'],
                'versionIdAnterior' => null,
                'resumen' => $resultado['resumen'],
            ];
        }
        $versionIdAnterior = $activa === null ? null : (int) $activa['id'];

        $this->db->beginTransaction();
        try {
            $numero = (int) $this->db->query(
                'SELECT COALESCE(MAX(version_numero), 0) + 1 FROM pdc_presupuesto_versiones WHERE project_id = ?',
                [$projectId],
            )->fetchColumn();
            $this->db->query('UPDATE pdc_presupuesto_versiones SET activa = 0 WHERE project_id = ? AND activa = 1', [$projectId]);
            $this->db->query(
                'INSERT INTO pdc_presupuesto_versiones
                    (project_id, version_label, version_numero, archivo_nombre, archivo_hash, contenido_hash, import_token, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())',
                [
                    $projectId,
                    (string) ($resultado['versionLabel'] ?? ''),
                    $numero,
                    (string) ($meta['nombre'] ?? ''),
                    (string) ($meta['hash'] ?? ''),
                    $contenidoHash,
                    $token,
                    $resultado['resumen']['actividades'],
                    $resultado['resumen']['insumos'],
                    $resultado['resumen']['costoTotal'],
                    (string) ($meta['usuario'] ?? ''),
                ],
            );
            $versionId = (int) $this->db->lastInsertId();

            $idPorCodigo = [];
            foreach ($resultado['items'] as $item) {
                $this->db->query(
                    'INSERT INTO pdc_presupuesto_items
                        (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad, id_apu)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$projectId, $versionId, $item['codigo'], $item['codigo_padre'], $item['nivel'], $item['tipo_fila'], $item['descripcion'], $item['unidad'], $item['cantidad'], $item['id_apu']],
                );
                $idPorCodigo[$item['codigo']] = (int) $this->db->lastInsertId();
            }

            foreach ($resultado['insumos'] as $insumo) {
                $this->db->query(
                    'INSERT INTO pdc_presupuesto_apu_insumos
                        (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $projectId, $versionId, $idPorCodigo[$insumo['codigo_actividad']],
                        $insumo['descripcion'], $insumo['tipo_insumo'], $insumo['unidad'],
                        $insumo['cant_apu'], $insumo['rendimiento'], $insumo['cantidad_total'],
                        $insumo['valor_unitario'], $insumo['valor_total'], $insumo['iva'],
                    ],
                );
            }

            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        $this->store->eliminar($token); // un solo uso

        return [
            'ok' => true,
            'sinCambios' => false,
            'versionId' => $versionId,
            'versionNumero' => $numero,
            'versionLabel' => $resultado['versionLabel'],
            'versionIdAnterior' => $versionIdAnterior,
            'resumen' => $resultado['resumen'],
        ];
    }

    /**
     * Versión ya creada con este token (idempotencia de confirmar), o null.
     *
     * Los conteos de jerarquía van a cero a propósito: la cabecera sólo persiste actividades,
     * insumos y costo.
     *
     * @return array{
     *     ok: true,
     *     sinCambios: false,
     *     versionId: int,
     *     versionNumero: int,
     *     versionLabel: mixed,
     *     versionIdAnterior: null,
     *     resumen: PresupuestoResumen
     * }|null
     */
    private function versionPorToken(string $token, int $projectId): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $row = $this->db->query(
            'SELECT id, version_label, version_numero, total_actividades, total_insumos, costo_total
             FROM pdc_presupuesto_versiones WHERE project_id = ? AND import_token = ?',
            [$projectId, $token],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return [
            'ok' => true,
            'sinCambios' => false,
            'versionId' => (int) $row['id'],
            'versionNumero' => (int) $row['version_numero'],
            'versionLabel' => $row['version_label'],
            'versionIdAnterior' => null,
            // Solo los campos persistidos; la jerarquía no se almacena en la cabecera.
            'resumen' => [
                'capitulos' => 0,
                'subcapitulos' => 0,
                'grupos' => 0,
                'actividades' => (int) $row['total_actividades'],
                'insumos' => (int) $row['total_insumos'],
                'costoTotal' => (float) $row['costo_total'],
            ],
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     versionNumero: int,
     *     versionLabel: mixed,
     *     archivoNombre: mixed,
     *     totalActividades: int,
     *     totalInsumos: int,
     *     costoTotal: float,
     *     activa: int,
     *     importadoPor: mixed,
     *     createdAt: mixed
     * }>
     */
    public function versiones(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT id, version_label, version_numero, archivo_nombre, total_actividades, total_insumos, costo_total, activa, importado_por, created_at
             FROM pdc_presupuesto_versiones WHERE project_id = ? ORDER BY created_at DESC, id DESC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'versionNumero' => (int) $r['version_numero'],
            'versionLabel' => $r['version_label'],
            'archivoNombre' => $r['archivo_nombre'],
            'totalActividades' => (int) $r['total_actividades'],
            'totalInsumos' => (int) $r['total_insumos'],
            'costoTotal' => (float) $r['costo_total'],
            // int 1/0 deliberado: AG Grid infiere cellDataType boolean para true/false
            // y renderiza checkbox ignorando el valueFormatter ("Activa" desaparecería).
            // El tipo SPA (VersionPresupuesto.activa: number) está alineado a esto.
            'activa' => (int) $r['activa'],
            'importadoPor' => $r['importado_por'],
            'createdAt' => $r['created_at'],
        ], $rows);
    }

    /**
     * Árbol plano del presupuesto de una versión (default: la activa), o null si no existe.
     *
     * @return array{
     *     version: array{id: int, versionLabel: mixed, activa: int},
     *     items: list<array{
     *         id: int,
     *         codigo: mixed,
     *         codigoPadre: mixed,
     *         nivel: int,
     *         tipoFila: mixed,
     *         descripcion: mixed,
     *         unidad: mixed,
     *         cantidad: float|null
     *     }>,
     *     insumos: list<array{
     *         itemId: int,
     *         descripcion: mixed,
     *         tipoInsumo: mixed,
     *         unidad: mixed,
     *         cantApu: float|null,
     *         rendimiento: float|null,
     *         cantidadTotal: float|null,
     *         valorUnitario: float|null,
     *         valorTotal: float|null
     *     }>
     * }|null
     */
    public function arbol(int $projectId, ?int $versionId = null): ?array
    {
        if ($versionId === null) {
            $version = $this->db->query(
                'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1',
                [$projectId],
            )->fetch(\PDO::FETCH_ASSOC);
        } else {
            $version = $this->db->query(
                'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?',
                [$projectId, $versionId],
            )->fetch(\PDO::FETCH_ASSOC);
        }
        if ($version === false) {
            return null;
        }
        $vid = (int) $version['id'];

        $items = $this->db->query(
            'SELECT id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad
             FROM pdc_presupuesto_items WHERE project_id = ? AND version_id = ? ORDER BY id ASC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $insumos = $this->db->query(
            'SELECT item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total
             FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ? ORDER BY id ASC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $num = static fn ($v): ?float => $v === null ? null : (float) $v;

        return [
            'version' => ['id' => $vid, 'versionLabel' => $version['version_label'], 'activa' => (int) $version['activa']],
            'items' => array_map(static fn (array $r): array => [
                'id' => (int) $r['id'],
                'codigo' => $r['codigo'],
                'codigoPadre' => $r['codigo_padre'],
                'nivel' => (int) $r['nivel'],
                'tipoFila' => $r['tipo_fila'],
                'descripcion' => $r['descripcion'],
                'unidad' => $r['unidad'],
                'cantidad' => $num($r['cantidad']),
            ], $items),
            'insumos' => array_map(static fn (array $r): array => [
                'itemId' => (int) $r['item_id'],
                'descripcion' => $r['descripcion'],
                'tipoInsumo' => $r['tipo_insumo'],
                'unidad' => $r['unidad'],
                'cantApu' => $num($r['cant_apu']),
                'rendimiento' => $num($r['rendimiento']),
                'cantidadTotal' => $num($r['cantidad_total']),
                'valorUnitario' => $num($r['valor_unitario']),
                'valorTotal' => $num($r['valor_total']),
            ], $insumos),
        ];
    }

    /**
     * Compara dos versiones del presupuesto: diff por actividad (roll-up) y por insumo consolidado.
     *
     * `deltaPct` es null cuando la versión A vale cero: el porcentaje no está definido y no se
     * fuerza a 100.
     *
     * @return array{
     *     versionA: array{id: int, label: mixed},
     *     versionB: array{id: int, label: mixed},
     *     resumen: array{
     *         costoA: float,
     *         costoB: float,
     *         delta: float,
     *         sobrecostos: float,
     *         ahorros: float,
     *         nuevos: int,
     *         eliminados: int,
     *         modificados: int
     *     },
     *     actividades: list<array{
     *         codigo: mixed,
     *         codigoPadre: mixed,
     *         nivel: mixed,
     *         tipoFila: mixed,
     *         descripcion: mixed,
     *         valorA: float,
     *         valorB: float,
     *         deltaValor: float,
     *         deltaPct: float|null,
     *         estado: 'nuevo'|'eliminado'|'igual'|'modificado'
     *     }>,
     *     insumos: list<array{
     *         descripcionNorm: mixed,
     *         unidad: mixed,
     *         descripcion: mixed,
     *         tipoInsumo: mixed,
     *         cantidadA: float,
     *         cantidadB: float,
     *         valorA: float,
     *         valorB: float,
     *         deltaValor: float,
     *         deltaPct: float|null,
     *         estado: 'nuevo'|'eliminado'|'igual'|'modificado'
     *     }>
     * }|null
     */
    public function comparar(int $projectId, int $versionA, int $versionB): ?array
    {
        $va = $this->versionMeta($projectId, $versionA);
        $vb = $this->versionMeta($projectId, $versionB);
        if ($va === null || $vb === null) {
            return null;
        }

        // --- Insumos consolidados por (norm, unidad) en cada versión ---
        $insA = $this->insumosConsolidados($projectId, $versionA);
        $insB = $this->insumosConsolidados($projectId, $versionB);
        $claves = array_unique(array_merge(array_keys($insA), array_keys($insB)));
        $insumos = [];
        $costoA = 0.0; $costoB = 0.0; $sobrecostos = 0.0; $ahorros = 0.0;
        $nuevos = 0; $eliminados = 0; $modificados = 0;
        foreach ($claves as $k) {
            $a = $insA[$k] ?? null;
            $b = $insB[$k] ?? null;
            $valorA = $a['valorTotal'] ?? 0.0;
            $valorB = $b['valorTotal'] ?? 0.0;
            $cantA = $a['cantidadTotal'] ?? 0.0;
            $cantB = $b['cantidadTotal'] ?? 0.0;
            $delta = $valorB - $valorA;
            $costoA += $valorA; $costoB += $valorB;
            if ($delta > 0) { $sobrecostos += $delta; } elseif ($delta < 0) { $ahorros += $delta; }
            $estado = $this->estadoDiff($a !== null, $b !== null, $delta, $cantB - $cantA);
            if ($estado === 'nuevo') { $nuevos++; } elseif ($estado === 'eliminado') { $eliminados++; } elseif ($estado === 'modificado') { $modificados++; }
            $ref = $b ?? $a;
            $insumos[] = [
                'descripcionNorm' => $ref['norm'],
                'unidad' => $ref['unidad'],
                'descripcion' => $ref['descripcion'],
                'tipoInsumo' => $ref['tipoInsumo'],
                'cantidadA' => $cantA, 'cantidadB' => $cantB,
                'valorA' => $valorA, 'valorB' => $valorB,
                'deltaValor' => $delta,
                'deltaPct' => $valorA == 0.0 ? null : round($delta / $valorA * 100, 1),
                'estado' => $estado,
            ];
        }
        usort($insumos, static fn ($x, $y) => max($y['valorA'], $y['valorB']) <=> max($x['valorA'], $x['valorB']));

        // --- Actividades por codigo con roll-up en cada versión ---
        $totA = $this->totalesPorCodigo($projectId, $versionA);
        $totB = $this->totalesPorCodigo($projectId, $versionB);
        $codigos = array_unique(array_merge(array_keys($totA), array_keys($totB)));
        $actividades = [];
        foreach ($codigos as $cod) {
            $a = $totA[$cod] ?? null;
            $b = $totB[$cod] ?? null;
            $ref = $b ?? $a;
            $valorA = $a['total'] ?? 0.0;
            $valorB = $b['total'] ?? 0.0;
            $delta = $valorB - $valorA;
            $actividades[] = [
                'codigo' => $cod,
                'codigoPadre' => $ref['codigoPadre'],
                'nivel' => $ref['nivel'],
                'tipoFila' => $ref['tipoFila'],
                'descripcion' => $ref['descripcion'],
                'valorA' => $valorA, 'valorB' => $valorB,
                'deltaValor' => $delta,
                'deltaPct' => $valorA == 0.0 ? null : round($delta / $valorA * 100, 1),
                'estado' => $this->estadoDiff($a !== null, $b !== null, $delta, 0.0),
            ];
        }
        usort($actividades, fn ($x, $y) => $this->compararCodigos($x['codigo'], $y['codigo']));

        return [
            'versionA' => ['id' => (int) $va['id'], 'label' => $va['version_label']],
            'versionB' => ['id' => (int) $vb['id'], 'label' => $vb['version_label']],
            'resumen' => [
                'costoA' => round($costoA, 2), 'costoB' => round($costoB, 2),
                'delta' => round($costoB - $costoA, 2),
                'sobrecostos' => round($sobrecostos, 2), 'ahorros' => round($ahorros, 2),
                'nuevos' => $nuevos, 'eliminados' => $eliminados, 'modificados' => $modificados,
            ],
            'actividades' => $actividades,
            'insumos' => $insumos,
        ];
    }

    /**
     * Cabecera de una versión del proyecto, o null.
     *
     * @return array<string, mixed>|null fila cruda con id y version_label
     */
    private function versionMeta(int $projectId, int $versionId): ?array
    {
        $row = $this->db->query(
            'SELECT id, version_label FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?',
            [$projectId, $versionId],
        )->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Insumos consolidados por (descripcion_norm, unidad) de una versión.
     *
     * @return array<string, array{
     *     norm: string,
     *     descripcion: mixed,
     *     tipoInsumo: mixed,
     *     unidad: mixed,
     *     cantidadTotal: float,
     *     valorTotal: float
     * }> indexado por "descripcion_norm|unidad", que es la clave de fusión del diff (spec A1.6)
     */
    private function insumosConsolidados(int $projectId, int $versionId): array
    {
        $rows = $this->db->query(
            'SELECT descripcion, tipo_insumo, unidad, cantidad_total, valor_total
             FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ?',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $acc = [];
        foreach ($rows as $r) {
            $norm = MaestroInsumosService::normalizar($r['descripcion']);
            $clave = $norm . '|' . $r['unidad'];
            if (!isset($acc[$clave])) {
                $acc[$clave] = ['norm' => $norm, 'descripcion' => $r['descripcion'], 'tipoInsumo' => $r['tipo_insumo'], 'unidad' => $r['unidad'], 'cantidadTotal' => 0.0, 'valorTotal' => 0.0];
            }
            $acc[$clave]['cantidadTotal'] += (float) ($r['cantidad_total'] ?? 0);
            $acc[$clave]['valorTotal'] += (float) ($r['valor_total'] ?? 0);
        }
        return $acc; // indexado por "norm|unidad" (clave de fusión del diff = descripcion_norm + unidad, spec A1.6)
    }

    /**
     * Total por codigo de item con roll-up de hojas a raíces (hoja = suma de sus insumos).
     *
     * @return array<string, array{
     *     codigo: mixed,
     *     codigoPadre: mixed,
     *     nivel: int,
     *     tipoFila: mixed,
     *     descripcion: mixed,
     *     total: float
     * }> indexado por código de item
     */
    private function totalesPorCodigo(int $projectId, int $versionId): array
    {
        $items = $this->db->query(
            'SELECT id, codigo, codigo_padre, nivel, tipo_fila, descripcion
             FROM pdc_presupuesto_items WHERE project_id = ? AND version_id = ? ORDER BY id ASC',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $sumaHojas = $this->db->query(
            'SELECT item_id, SUM(valor_total) AS total
             FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ? GROUP BY item_id',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        $porCodigo = [];
        foreach ($items as $it) {
            $porCodigo[$it['codigo']] = [
                'codigo' => $it['codigo'], 'codigoPadre' => $it['codigo_padre'],
                'nivel' => (int) $it['nivel'], 'tipoFila' => $it['tipo_fila'], 'descripcion' => $it['descripcion'],
                'total' => (float) ($sumaHojas[$it['id']] ?? 0),
            ];
        }
        // Propagar de hojas a raíces: por nivel descendente, sumar cada hijo a su padre.
        usort($items, static fn ($a, $b) => (int) $b['nivel'] <=> (int) $a['nivel']);
        foreach ($items as $it) {
            $padre = $it['codigo_padre'];
            if ($padre !== null && isset($porCodigo[$padre])) {
                $porCodigo[$padre]['total'] += $porCodigo[$it['codigo']]['total'];
            }
        }
        return $porCodigo;
    }

    /** Clasifica un renglón del diff. */
    private function estadoDiff(bool $enA, bool $enB, float $deltaValor, float $deltaCantidad): string
    {
        if (!$enA && $enB) { return 'nuevo'; }
        if ($enA && !$enB) { return 'eliminado'; }
        return (abs($deltaValor) < 0.01 && abs($deltaCantidad) < 0.01) ? 'igual' : 'modificado';
    }

    /** Compara dos códigos de presupuesto en orden jerárquico (pre-orden): "01" < "01.01" < "01.02" < "02". */
    private function compararCodigos(string $a, string $b): int
    {
        $pa = array_map('intval', explode('.', $a));
        $pb = array_map('intval', explode('.', $b));
        $n = min(count($pa), count($pb));
        for ($i = 0; $i < $n; $i++) {
            if ($pa[$i] !== $pb[$i]) {
                return $pa[$i] <=> $pb[$i];
            }
        }
        return count($pa) <=> count($pb); // prefijo común igual → el más corto (padre) va primero
    }
}
