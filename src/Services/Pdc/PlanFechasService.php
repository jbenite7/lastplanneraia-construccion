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

    public function __construct(private readonly \Database $db)
    {
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
        $semana = $this->db->query(
            'SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?',
            [$projectId],
        )->fetchColumn();
        if ($semana === false || $semana === null) {
            return [];
        }
        $rows = $this->db->query(
            'SELECT unique_id, Actividad, Fecha_Inicio FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND Titulo = 1 AND unique_id IS NOT NULL
               AND Fecha_Inicio IS NOT NULL
             ORDER BY Fecha_Inicio ASC, unique_id ASC',
            [$projectId, (int) $semana],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static function (array $r): array {
            $l = self::limpiarActividad((string) $r['Actividad']);
            return [
                'uniqueId' => (int) $r['unique_id'],
                'nombre' => $l['nombre'],
                'capitulo' => $l['capitulo'],
                'fechaInicio' => (string) $r['Fecha_Inicio'],
            ];
        }, $rows);
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
        $frente = null;
        foreach ($this->frentesDisponibles($projectId) as $f) {
            if ($f['uniqueId'] === $uniqueId) {
                $frente = $f;
                break;
            }
        }
        if ($frente === null) {
            return ['ok' => false, 'code' => 'FRENTE_INVALIDO'];
        }
        $paquete = $this->db->query(
            'SELECT id FROM general_paquetes_contratacion WHERE id = ? AND activo = 1',
            [$paqueteId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($paquete === false) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }

        $origen = in_array($procedencia['origen'] ?? '', ['similitud', 'rama'], true) ? $procedencia['origen'] : 'humano';
        $delMotor = $origen !== 'humano';
        $semana = (int) $this->db->query('SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?', [$projectId])->fetchColumn();

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
}
