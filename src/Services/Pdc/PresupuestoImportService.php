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
    /**
     * Unidades que en el export de presupuestos significan «esto es una suma global»: el APU no se
     * descompone, se resuelve con una cifra. Medido contra Da Porto, donde son `SG` (54 actividades)
     * y `GL` (3); `GLB` y `GLOBAL` se aceptan porque el mismo software las emite según la plantilla.
     */
    public const UNIDADES_GLOBALES = ['SG', 'GL', 'GLB', 'GLOBAL'];

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
     * Fija a mano cuál versión del presupuesto es la oficial del proyecto.
     *
     * Hasta ahora la marca «Activa» se la llevaba siempre la última importación y no había forma
     * de volver atrás: un presupuesto cargado por equivocación dejaba colgando de él al proyecto
     * entero. La versión activa es la raíz de lo que se ve en el visor, en el maestro y en el
     * Pareto, así que elegirla es una decisión que tiene que poder tomarse.
     *
     * Todo en una transacción y apagando primero: `pdc_presupuesto_versiones` tiene la columna
     * generada `activa_unica` con índice único, así que encender la nueva antes de apagar la vieja
     * lo rechazaría la propia base.
     *
     * No recibe `$usuario`: la tabla no tiene dónde registrar quién activó, y un parámetro con ese
     * nombre prometería una auditoría inexistente. El permiso que lo protege es `lps.pdc.importar`,
     * el mismo que carga presupuestos — quien puede traer una versión nueva puede decidir cuál rige.
     *
     * @return array{ok: bool, code?: string}
     */
    public function activar(int $projectId, int $versionId): array
    {
        $existe = $this->db->query(
            'SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?',
            [$projectId, $versionId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($existe === false) {
            return ['ok' => false, 'code' => 'VERSION_INVALIDA'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                'UPDATE pdc_presupuesto_versiones SET activa = 0 WHERE project_id = ? AND activa = 1',
                [$projectId],
            );
            $this->db->query(
                'UPDATE pdc_presupuesto_versiones SET activa = 1 WHERE project_id = ? AND id = ?',
                [$projectId, $versionId],
            );
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true];
    }

    /**
     * Qué trabajo quedará apuntando a otra versión si se cambia la oficial.
     *
     * Solo cuenta los vínculos del maestro: son lo ÚNICO atado a una versión concreta
     * (`pdc_insumo_vinculos.version_id`). Las asignaciones a paquete y el plan de fechas no llevan
     * `version_id` — viven a nivel de proyecto y sobreviven al cambio. Por eso esto sirve para
     * avisar y no para bloquear: bloquear protegería de un peligro que no existe.
     *
     * Calcula por su cuenta de qué versión se sale, en vez de recibirla: quien llama no puede
     * equivocarse de versión al preguntar.
     *
     * @return array{vinculosAfectados: int, versionActual: array{id: int, label: mixed}|null}
     */
    public function impactoDeCambiarVersion(int $projectId): array
    {
        $activa = $this->versionActivaDe($projectId);
        if ($activa === null) {
            return ['vinculosAfectados' => 0, 'versionActual' => null];
        }
        $n = (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_insumo_vinculos WHERE project_id = ? AND version_id = ?',
            [$projectId, (int) $activa['id']],
        )->fetchColumn();

        return [
            'vinculosAfectados' => $n,
            'versionActual' => ['id' => (int) $activa['id'], 'label' => $activa['version_label']],
        ];
    }

    /**
     * Qué le pasa al trabajo ya hecho si se confirma esta versión candidata.
     *
     * Se responde ANTES de confirmar porque hoy el usuario carga a ciegas: la herencia de A3 existe
     * —las asignaciones se conservan y el auto-match vuelve a correr—, pero nadie sabe cuánto de su
     * trabajo va a quedar huérfano hasta después de haberlo hecho.
     *
     * No hay consulta nueva contra el presupuesto: la versión activa se consolida con la misma
     * función que usa el comparativo de A1.6 y la candidata con esa misma función sobre las filas que
     * el parser acaba de leer. `pdc_insumo_paquete` tiene como clave única exactamente
     * `(project_id, descripcion_norm, unidad)`, así que el cruce es un join más.
     *
     * **Informa; no decide.** Un insumo que cambió de tipo se señala y nada más: reasignarlo solo
     * rompería la única regla sobre la que se sostiene el módulo, que es la confirmación humana.
     *
     * Sobre «cambia de tipo» y no «cambia de agrupación»: la columna `Agrupacion` del export de
     * SINCO se lee y se descarta (`PresupuestoExcelParser` no la persiste), y la `agrupacion` que sí
     * existe vive en `general_maestro_insumos` indexada por `(descripcion_norm, unidad)` — o sea, es
     * propiedad de la identidad del insumo y no puede cambiar entre dos versiones del presupuesto.
     * Lo que sí se persiste y sí alimenta al motor de sugerencias es `tipo_insumo`, y es eso lo que
     * se compara. Diferenciar la agrupación real exigiría una migración, que el spec excluye.
     *
     * @param list<array<string, mixed>> $insumosCandidatos filas del parser (sin persistir todavía)
     * @return array{
     *     versionActiva: array{id: int, label: mixed}|null,
     *     nuevosSinPaquete: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *     desaparecenConPaquete: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *     cambianTipo: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *     valorAfectado: float
     * }
     */
    public function impactoDeReimportar(int $projectId, array $insumosCandidatos): array
    {
        $vacio = static fn (): array => ['cantidad' => 0, 'valor' => 0.0, 'detalle' => []];
        $activa = $this->versionActivaDe($projectId);
        if ($activa === null) {
            return [
                'versionActiva' => null,
                'nuevosSinPaquete' => $vacio(),
                'desaparecenConPaquete' => $vacio(),
                'cambianTipo' => $vacio(),
                'valorAfectado' => 0.0,
            ];
        }

        $antes = $this->insumosConsolidados($projectId, (int) $activa['id']);
        $despues = self::consolidarInsumos($insumosCandidatos);

        // Destino de cada insumo: nombre del paquete, o null cuando está omitido a propósito (omitir
        // también es una decisión tomada, así que tampoco es trabajo pendiente). La clave existe en
        // el mapa en los dos casos: lo que distingue «sin destino» es que no exista la clave.
        $rows = $this->db->query(
            'SELECT a.descripcion_norm, a.unidad, a.omitido, p.nombre
             FROM pdc_insumo_paquete a
             LEFT JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             WHERE a.project_id = ? AND (a.paquete_id IS NOT NULL OR a.omitido = 1)',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $destino = [];
        foreach ($rows as $r) {
            $destino[$r['descripcion_norm'] . '|' . $r['unidad']] = $r['nombre'];
        }

        $fila = static fn (array $ins, ?string $paquete, ?string $tipoAnterior = null): array => [
            'descripcion' => $ins['descripcion'],
            'unidad' => $ins['unidad'],
            'tipoInsumo' => $ins['tipoInsumo'],
            'tipoInsumoAnterior' => $tipoAnterior,
            'valorTotal' => round($ins['valorTotal'], 2),
            'paquete' => $paquete,
        ];
        $norma = static fn (mixed $t): string => mb_strtoupper(trim((string) $t));

        $nuevos = [];
        $desaparecen = [];
        $cambian = [];
        foreach ($despues as $clave => $ins) {
            if (!isset($antes[$clave])) {
                if (!array_key_exists($clave, $destino)) {
                    $nuevos[] = $fila($ins, null);
                }
                continue;
            }
            if ($norma($ins['tipoInsumo']) !== $norma($antes[$clave]['tipoInsumo'])) {
                $cambian[] = $fila($ins, $destino[$clave] ?? null, (string) $antes[$clave]['tipoInsumo']);
            }
        }
        foreach ($antes as $clave => $ins) {
            // Omitido (nombre null en el mapa) fuera: no se pierde trabajo al desaparecer algo que ya
            // se había decidido no contratar.
            if (!isset($despues[$clave]) && ($destino[$clave] ?? null) !== null) {
                $desaparecen[] = $fila($ins, (string) $destino[$clave]);
            }
        }

        $porValor = static fn (array $x, array $y): int => $y['valorTotal'] <=> $x['valorTotal'];
        usort($nuevos, $porValor);
        usort($desaparecen, $porValor);
        usort($cambian, $porValor);

        $grupo = static fn (array $detalle): array => [
            'cantidad' => count($detalle),
            'valor' => round(array_sum(array_column($detalle, 'valorTotal')), 2),
            'detalle' => $detalle,
        ];
        $g1 = $grupo($nuevos);
        $g2 = $grupo($desaparecen);
        $g3 = $grupo($cambian);

        return [
            'versionActiva' => ['id' => (int) $activa['id'], 'label' => $activa['version_label']],
            'nuevosSinPaquete' => $g1,
            'desaparecenConPaquete' => $g2,
            'cambianTipo' => $g3,
            'valorAfectado' => round($g1['valor'] + $g2['valor'] + $g3['valor'], 2),
        ];
    }

    /**
     * @return array{ok: false, errores: list<PresupuestoErrorFila>}|array{
     *     ok: true,
     *     importToken: string,
     *     versionLabel: string|null,
     *     resumen: PresupuestoResumen,
     *     advertencias: list<string>,
     *     sinCambios: bool,
     *     versionActiva: array{id: int, numero: int, label: mixed, createdAt: mixed}|null,
     *     impacto: array{
     *         versionActiva: array{id: int, label: mixed}|null,
     *         nuevosSinPaquete: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *         desaparecenConPaquete: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *         cambianTipo: array{cantidad: int, valor: float, detalle: list<array<string, mixed>>},
     *         valorAfectado: float
     *     }
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
            // El informe de impacto viaja aquí y no en un endpoint aparte: es la misma pregunta que
            // la pantalla ya está haciendo («¿qué voy a cargar?»), y separarlo permitiría mostrar la
            // previsualización sin él.
            'impacto' => $this->impactoDeReimportar($projectId, $resultado['insumos']),
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
     *     obsoleta: int,
     *     obsoletaMotivo: mixed,
     *     importadoPor: mixed,
     *     createdAt: mixed
     * }>
     */
    public function versiones(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT id, version_label, version_numero, archivo_nombre, total_actividades, total_insumos, costo_total, activa,
                    obsoleta, obsoleta_motivo, importado_por, created_at
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
            // Misma razón que `activa` para el int 1/0: esta columna también se pinta en la grilla.
            'obsoleta' => (int) $r['obsoleta'],
            'obsoletaMotivo' => $r['obsoleta_motivo'],
            'importadoPor' => $r['importado_por'],
            'createdAt' => $r['created_at'],
        ], $rows);
    }

    /**
     * Árbol plano del presupuesto de una versión (default: la activa), o null si no existe.
     *
     * @return array{
     *     version: array{id: int, versionLabel: mixed, activa: int},
     *     avisos: array{
     *         costoTotal: float,
     *         insumosDistintos: int,
     *         aparicionesApu: int,
     *         actividadesSinCantidad: array{cantidad: int, lineasEnCero: int, detalle: list<array<string, mixed>>},
     *         insumosEnCero: array{cantidad: int, detalle: list<array<string, mixed>>},
     *         partidasGlobales: array{unidades: list<string>, candidatos: list<array<string, mixed>>}
     *     },
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
            // Los avisos viajan con el árbol y no en un endpoint aparte, para que un presupuesto no
            // pueda mostrarse sin ellos.
            'avisos' => $this->avisosDelPresupuesto($projectId, $vid),
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
     * Lo que el presupuesto no explica solo, señalado sin bloquear nada.
     *
     * Viaja dentro de `arbol()` —el servicio del visor— y no en un endpoint aparte, para que un
     * presupuesto no pueda mostrarse sin sus avisos. Es la mejor oportunidad de la empresa para cazar
     * los machetazos del presupuesto: el que arma el plan de compras es el primero que los ve.
     *
     * Los dos avisos de ceros están **separados a propósito**. La regla del spec (`cantidad = 0` o
     * `valor_unitario = 0`) da 112 líneas en Da Porto, pero 102 de ellas son consecuencia de otra
     * cosa: 47 actividades que nadie cuantificó todavía. Reportar «112 insumos vacíos» sería un
     * número verdadero que señala mal, y el 47 es además el que le cuadra a quien recorrió el
     * presupuesto a mano. 102 + 10 = 112: no hay doble conteo ni fuga.
     *
     * El umbral del «globalazo» **no se aplica aquí**: se devuelven todos los candidatos con su valor
     * y el costo total de la versión, y quien pone el umbral es el usuario en la pantalla. Un umbral
     * cocinado en el servidor sería un juicio disfrazado de constante.
     *
     * @return array{
     *   costoTotal: float,
     *   insumosDistintos: int,
     *   aparicionesApu: int,
     *   actividadesSinCantidad: array{cantidad: int, lineasEnCero: int, detalle: list<array<string, mixed>>},
     *   insumosEnCero: array{cantidad: int, detalle: list<array<string, mixed>>},
     *   partidasGlobales: array{unidades: list<string>, candidatos: list<array<string, mixed>>}
     * }
     */
    private function avisosDelPresupuesto(int $projectId, int $versionId): array
    {
        $rows = $this->db->query(
            'SELECT i.descripcion, i.tipo_insumo, i.unidad, i.cantidad_total, i.valor_unitario, i.valor_total,
                    it.id AS item_id, it.codigo, it.descripcion AS actividad, it.unidad AS unidad_actividad,
                    it.cantidad AS cantidad_actividad
             FROM pdc_presupuesto_apu_insumos i
             JOIN pdc_presupuesto_items it ON it.id = i.item_id AND it.project_id = i.project_id
             WHERE i.project_id = ? AND i.version_id = ?
             ORDER BY it.codigo ASC, i.id ASC',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $sinCantidad = [];
        $enCero = [];
        $porItem = [];
        $costoTotal = 0.0;

        foreach ($rows as $r) {
            $valor = (float) ($r['valor_total'] ?? 0);
            $costoTotal += $valor;
            $itemId = (int) $r['item_id'];
            if (!isset($porItem[$itemId])) {
                $porItem[$itemId] = [
                    'codigo' => $r['codigo'], 'descripcion' => $r['actividad'],
                    'unidad' => $r['unidad_actividad'], 'insumos' => 0, 'valorTotal' => 0.0,
                ];
            }
            $porItem[$itemId]['insumos']++;
            $porItem[$itemId]['valorTotal'] += $valor;

            if (((float) ($r['cantidad_actividad'] ?? 0)) === 0.0) {
                $cod = (string) $r['codigo'];
                if (!isset($sinCantidad[$cod])) {
                    $sinCantidad[$cod] = ['codigo' => $cod, 'descripcion' => $r['actividad'], 'valorTotal' => 0.0, 'lineas' => 0];
                }
                $sinCantidad[$cod]['lineas']++;
                $sinCantidad[$cod]['valorTotal'] += $valor;
                continue; // su línea de insumo la explica la actividad: no se cuenta dos veces
            }
            if (((float) ($r['cantidad_total'] ?? 0)) === 0.0 || ((float) ($r['valor_unitario'] ?? 0)) === 0.0) {
                $enCero[] = [
                    'codigo' => $r['codigo'], 'actividad' => $r['actividad'],
                    'descripcion' => $r['descripcion'], 'unidad' => $r['unidad'],
                    'cantidad' => (float) ($r['cantidad_total'] ?? 0),
                    'valorUnitario' => (float) ($r['valor_unitario'] ?? 0),
                ];
            }
        }

        // Actividades sin cantidad que además no tienen ninguna línea de insumo: existen y también
        // hay que mirarlas, así que el conteo no puede salir solo del recorrido de arriba.
        $huerfanas = $this->db->query(
            "SELECT it.codigo, it.descripcion FROM pdc_presupuesto_items it
             WHERE it.project_id = ? AND it.version_id = ? AND it.tipo_fila = 'actividad'
               AND (it.cantidad = 0 OR it.cantidad IS NULL)
               AND it.id NOT IN (SELECT ai.item_id FROM pdc_presupuesto_apu_insumos ai WHERE ai.project_id = ? AND ai.version_id = ?)",
            [$projectId, $versionId, $projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($huerfanas as $h) {
            $sinCantidad[(string) $h['codigo']] = ['codigo' => $h['codigo'], 'descripcion' => $h['descripcion'], 'valorTotal' => 0.0, 'lineas' => 0];
        }

        $candidatos = [];
        foreach ($porItem as $it) {
            if ($it['insumos'] <= 2 && in_array(mb_strtoupper(trim((string) $it['unidad'])), self::UNIDADES_GLOBALES, true)) {
                $it['valorTotal'] = round($it['valorTotal'], 2);
                $candidatos[] = $it;
            }
        }
        usort($candidatos, static fn (array $x, array $y): int => $y['valorTotal'] <=> $x['valorTotal']);

        $detalleSinCantidad = array_values($sinCantidad);
        usort($detalleSinCantidad, static fn (array $x, array $y): int => strcmp((string) $x['codigo'], (string) $y['codigo']));

        return [
            'costoTotal' => round($costoTotal, 2),
            'insumosDistintos' => count(self::consolidarInsumos($rows)),
            'aparicionesApu' => count($rows),
            'actividadesSinCantidad' => [
                'cantidad' => count($detalleSinCantidad),
                'lineasEnCero' => array_sum(array_column($detalleSinCantidad, 'lineas')),
                'detalle' => $detalleSinCantidad,
            ],
            'insumosEnCero' => ['cantidad' => count($enCero), 'detalle' => $enCero],
            'partidasGlobales' => ['unidades' => self::UNIDADES_GLOBALES, 'candidatos' => $candidatos],
        ];
    }

    /**
     * Compara dos versiones del presupuesto: diff por actividad (roll-up) y por insumo consolidado.
     *
     * `deltaPct` es null cuando la versión A vale cero: el porcentaje no está definido y no se
     * fuerza a 100.
     *
     * @return array{
     *     versionA: array{id: int, label: mixed, obsoleta: int, obsoletaMotivo: mixed},
     *     versionB: array{id: int, label: mixed, obsoleta: int, obsoletaMotivo: mixed},
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
            // `obsoleta` viaja con cada lado para que el comparativo pueda advertir que lo que se
            // está viendo no son cambios del presupuesto, sino el rastro de un import defectuoso
            // (ver database/migrations/20260728_pdc_v2_versiones_obsoletas.php).
            'versionA' => [
                'id' => (int) $va['id'], 'label' => $va['version_label'],
                'obsoleta' => (int) $va['obsoleta'], 'obsoletaMotivo' => $va['obsoleta_motivo'],
            ],
            'versionB' => [
                'id' => (int) $vb['id'], 'label' => $vb['version_label'],
                'obsoleta' => (int) $vb['obsoleta'], 'obsoletaMotivo' => $vb['obsoleta_motivo'],
            ],
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
            'SELECT id, version_label, obsoleta, obsoleta_motivo
             FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?',
            [$projectId, $versionId],
        )->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Agrupa filas de insumo por la clave de fusión del diff: `(descripcion_norm, unidad)`, que es
     * también la clave única de `pdc_insumo_paquete`. Estático y puro a propósito: lo llaman el
     * comparativo (con filas de la base) y el informe de impacto (con filas recién parseadas, de una
     * versión candidata que aún no existe). Si las dos rutas no compartieran esta función, el impacto
     * podría contar como «nuevo» un insumo que el comparativo considera el mismo.
     *
     * @param list<array<string, mixed>> $rows usa descripcion, tipo_insumo, unidad, cantidad_total,
     *                                        valor_total
     * @return array<string, array{
     *     norm: string,
     *     descripcion: mixed,
     *     tipoInsumo: mixed,
     *     unidad: mixed,
     *     cantidadTotal: float,
     *     valorTotal: float,
     *     apariciones: int
     * }> indexado por "descripcion_norm|unidad", que es la clave de fusión del diff (spec A1.6)
     */
    public static function consolidarInsumos(array $rows): array
    {
        $acc = [];
        foreach ($rows as $r) {
            $norm = MaestroInsumosService::normalizar((string) ($r['descripcion'] ?? ''));
            $clave = $norm . '|' . ($r['unidad'] ?? '');
            if (!isset($acc[$clave])) {
                $acc[$clave] = [
                    'norm' => $norm,
                    'descripcion' => $r['descripcion'] ?? '',
                    'tipoInsumo' => $r['tipo_insumo'] ?? '',
                    'unidad' => $r['unidad'] ?? '',
                    'cantidadTotal' => 0.0,
                    'valorTotal' => 0.0,
                    'apariciones' => 0,
                ];
            }
            $acc[$clave]['cantidadTotal'] += (float) ($r['cantidad_total'] ?? 0);
            $acc[$clave]['valorTotal'] += (float) ($r['valor_total'] ?? 0);
            $acc[$clave]['apariciones']++;
        }
        return $acc;
    }

    /**
     * Insumos consolidados de una versión ya persistida. La agrupación la hace
     * `consolidarInsumos()`, compartida con el informe de impacto.
     *
     * @return array<string, array{
     *     norm: string,
     *     descripcion: mixed,
     *     tipoInsumo: mixed,
     *     unidad: mixed,
     *     cantidadTotal: float,
     *     valorTotal: float,
     *     apariciones: int
     * }> indexado por "descripcion_norm|unidad", que es la clave de fusión del diff (spec A1.6)
     */
    private function insumosConsolidados(int $projectId, int $versionId): array
    {
        $rows = $this->db->query(
            'SELECT descripcion, tipo_insumo, unidad, cantidad_total, valor_total
             FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ?',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        return self::consolidarInsumos($rows);
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
