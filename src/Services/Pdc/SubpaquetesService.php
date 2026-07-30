<?php

declare(strict_types=1);

namespace App\Services\Pdc;

/**
 * Subpaquetes: partir un paquete de preconstrucción en los lotes que la obra de verdad contrata.
 *
 * El plan se arma con paquetes grandes —«Pisos»— porque para preconstrucción eso es lo correcto. La
 * obra no contrata así: dentro de Pisos hay porcelanato, tableta gres y cerámica, con proveedores y
 * marcas distintos, y cada uno con su propio «esto lo contrato en dos meses / esto lo necesito ya».
 * Este servicio añade ese nivel sin quitar el de arriba: el paquete sombrilla se conserva y resume;
 * el que se contrata es el lote.
 *
 * ------------------------------------------------------------------------------------------------
 * LA PIEZA QUE IMPORTA ES `destinos()`
 * ------------------------------------------------------------------------------------------------
 * Un nivel de jerarquía nuevo vuelve ambigua toda cifra que antes contaba paquetes: ¿11 de 96
 * paquetes o 11 de 130 lotes? La respuesta se decide UNA vez, aquí, y todo el módulo la consume:
 * el plan de fechas, el tablero de vencimientos, la cobertura y el flujo de caja. La unidad es el
 * **destino contratable**: un paquete sin partir cuenta como uno; un paquete partido cuenta como
 * tantos como lotes tenga, y él mismo no cuenta. Si cada vista lo decidiera por su cuenta, dos
 * pantallas del mismo módulo darían números distintos — que es exactamente lo que el spec prohíbe.
 *
 * ------------------------------------------------------------------------------------------------
 * TRES REGLAS QUE NO SE NEGOCIAN
 * ------------------------------------------------------------------------------------------------
 * 1. **Un paquete partido nunca se contrata él mismo.** Lo que nadie mueve a un lote cae en el lote
 *    «Resto», que nace solo al partir. Así ninguna vista tiene que decidir si la fila del sombrilla
 *    es un contrato o un total, que es por donde se cuelan los números dobles.
 * 2. **Un insumo, un destino.** La regla que sostiene el módulo desde A3 sigue en la clave única
 *    `uq_pip_insumo (project_id, descripcion_norm, unidad)`: mover un insumo a un lote es un UPDATE
 *    de su fila, nunca una fila nueva. No hay reparto de un insumo entre dos lotes.
 * 3. **Los lotes no suben al catálogo global.** Son casuística de una obra. El comité del 2026-07-29
 *    lo pidió expresamente, y el motor de sugerencias sigue trabajando a nivel de paquete grande:
 *    no aprende de lotes. Si el motor asigna un insumo a un paquete que está partido, el insumo
 *    aterriza en su «Resto» (ver `destinoDeAsignacion()`).
 *
 * `subpaquete_id = 0` significa «el paquete mismo, sin partir». Es un centinela, no un id: ver la
 * migración `20260729_pdc_v2_subpaquetes.sql` para por qué no es NULL (en un índice UNIQUE de MySQL
 * dos NULL son distintos y el upsert de `PlanFechasService::calcular()` dejaría de dispararse).
 */
final class SubpaquetesService
{
    /** El centinela que significa «el paquete grande, sin partir». */
    public const SIN_PARTIR = 0;

    /** Nombre del lote que recoge lo que nadie movió. Se compone como «Resto de <paquete>». */
    public const PREFIJO_RESTO = 'Resto de ';

    /**
     * Modalidades que generan proceso de contratación y por tanto fechas. Misma lista que
     * `PlanFechasService::MODALIDADES_CON_PROCESO`, y no una copia libre: un lote cuya modalidad no
     * genera proceso queda fuera del plan y del flujo de caja igual que un paquete, y la pantalla
     * declara cuánto valor del sombrilla se queda fuera por esa razón.
     */
    private const MODALIDADES_CON_PROCESO = ['contrato', 'orden_compra'];

    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * ¿Está partido este paquete en esta obra?
     */
    public function estaPartido(int $projectId, int $paqueteId): bool
    {
        return (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_subpaquete WHERE project_id = ? AND paquete_id = ?',
            [$projectId, $paqueteId],
        )->fetchColumn() > 0;
    }

    /**
     * Los lotes de un paquete, con sus insumos y su valor. Vacío si no está partido.
     *
     * @return list<array<string, mixed>>
     */
    public function listar(int $projectId, int $paqueteId, ?int $versionId = null): array
    {
        $vid = $this->versionActivaId($projectId, $versionId);
        $rows = $this->db->query(
            'SELECT s.id, s.nombre, s.modalidad_contratacion, s.responsable_user_id, s.es_resto, s.orden,
                    COUNT(a.id) AS insumos,
                    COALESCE(SUM(v.valor_total), 0) AS valor
               FROM pdc_subpaquete s
               LEFT JOIN pdc_insumo_paquete a
                      ON a.project_id = s.project_id AND a.subpaquete_id = s.id
               LEFT JOIN pdc_insumo_vinculos v
                      ON v.project_id = a.project_id AND v.descripcion_norm = a.descripcion_norm
                     AND v.unidad = a.unidad AND v.version_id = ?
              WHERE s.project_id = ? AND s.paquete_id = ?
              GROUP BY s.id, s.nombre, s.modalidad_contratacion, s.responsable_user_id, s.es_resto, s.orden
              ORDER BY s.es_resto ASC, s.orden ASC, s.id ASC',
            [$vid, $projectId, $paqueteId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static fn (array $r): array => [
            'subpaqueteId' => (int) $r['id'],
            'nombre' => (string) $r['nombre'],
            'modalidad' => (string) $r['modalidad_contratacion'],
            'responsableUserId' => $r['responsable_user_id'] === null ? null : (int) $r['responsable_user_id'],
            'esResto' => (int) $r['es_resto'] === 1,
            'orden' => (int) $r['orden'],
            'insumos' => (int) $r['insumos'],
            'valor' => (float) $r['valor'],
            'generaProceso' => in_array((string) $r['modalidad_contratacion'], self::MODALIDADES_CON_PROCESO, true),
        ], $rows);
    }

    /**
     * Parte un paquete en lotes. Crea los que se piden MÁS el «Resto», en una sola transacción.
     *
     * Es la única puerta por la que un paquete pasa de sin partir a partido, y por eso el «Resto»
     * nace aquí y no más tarde: si el paquete quedara partido un solo instante sin lote «Resto», sus
     * insumos no movidos no tendrían destino contratable y desaparecerían del plan sin avisar.
     *
     * @param list<string> $nombres nombres de los lotes a crear (sin el «Resto»)
     * @return array{ok: bool, code?: string, mensaje?: string, subpaquetes?: list<array<string, mixed>>}
     */
    public function partir(int $projectId, int $paqueteId, array $nombres, string $usuario): array
    {
        $nombres = array_values(array_filter(array_map('trim', $nombres), static fn (string $n): bool => $n !== ''));
        if ($nombres === []) {
            return ['ok' => false, 'code' => 'SIN_NOMBRES', 'mensaje' => 'Hay que darle nombre a por lo menos un lote.'];
        }
        if (count($nombres) !== count(array_unique($nombres))) {
            return ['ok' => false, 'code' => 'NOMBRES_REPETIDOS', 'mensaje' => 'Dos lotes del mismo paquete no pueden llamarse igual.'];
        }
        if ($this->estaPartido($projectId, $paqueteId)) {
            return ['ok' => false, 'code' => 'YA_PARTIDO', 'mensaje' => 'Este paquete ya está partido: agrega lotes de a uno en vez de volver a partirlo.'];
        }
        $paquete = $this->db->query(
            'SELECT id, nombre, modalidad_contratacion FROM general_paquetes_contratacion WHERE id = ? AND activo = 1',
            [$paqueteId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($paquete === false) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO', 'mensaje' => 'Ese paquete no existe o está retirado.'];
        }

        $this->db->beginTransaction();
        try {
            $orden = 0;
            foreach ($nombres as $n) {
                $this->insertar($projectId, $paqueteId, $n, (string) $paquete['modalidad_contratacion'], false, $orden, $usuario);
                $orden += 10;
            }
            // El «Resto» hereda la modalidad del paquete y va siempre al final del orden visual.
            $restoId = $this->insertar(
                $projectId,
                $paqueteId,
                self::PREFIJO_RESTO . (string) $paquete['nombre'],
                (string) $paquete['modalidad_contratacion'],
                true,
                $orden,
                $usuario,
            );
            // Los insumos que ya estaban en el paquete pasan al «Resto». Ninguno se queda con
            // `subpaquete_id = 0` en un paquete partido: ese valor significa «sin partir» y dejarlo
            // aquí crearía un destino fantasma que ninguna vista lista.
            $this->db->query(
                'UPDATE pdc_insumo_paquete SET subpaquete_id = ?
                  WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?',
                [$restoId, $projectId, $paqueteId, self::SIN_PARTIR],
            );
            // El plan viejo del paquete como unidad contratable deja de tener sentido: ahora las
            // unidades son sus lotes. Se mueve al «Resto», que es quien hereda su frente y su
            // proceso, en vez de borrarse — borrarlo se llevaría el avance real ya registrado.
            foreach (['pdc_paquete_frente', 'pdc_plan_paquete', 'pdc_plan_paso'] as $t) {
                $this->db->query(
                    "UPDATE {$t} SET subpaquete_id = ? WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?",
                    [$restoId, $projectId, $paqueteId, self::SIN_PARTIR],
                );
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        return ['ok' => true, 'subpaquetes' => $this->listar($projectId, $paqueteId)];
    }

    /**
     * Agrega un lote a un paquete ya partido.
     *
     * @return array{ok: bool, code?: string, mensaje?: string, subpaqueteId?: int}
     */
    public function agregar(int $projectId, int $paqueteId, string $nombre, string $modalidad, string $usuario): array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return ['ok' => false, 'code' => 'SIN_NOMBRE', 'mensaje' => 'El lote necesita un nombre.'];
        }
        if (!$this->estaPartido($projectId, $paqueteId)) {
            return ['ok' => false, 'code' => 'NO_PARTIDO', 'mensaje' => 'Este paquete no está partido todavía: pártelo primero.'];
        }
        if (!in_array($modalidad, self::modalidades(), true)) {
            return ['ok' => false, 'code' => 'MODALIDAD_INVALIDA', 'mensaje' => 'Esa modalidad de contratación no existe.'];
        }
        $orden = (int) $this->db->query(
            'SELECT COALESCE(MAX(orden), 0) + 10 FROM pdc_subpaquete WHERE project_id = ? AND paquete_id = ? AND es_resto = 0',
            [$projectId, $paqueteId],
        )->fetchColumn();
        try {
            $id = $this->insertar($projectId, $paqueteId, $nombre, $modalidad, false, $orden, $usuario);
        } catch (\PDOException $e) {
            // 1062 = choque con `uq_psub_nombre`. Se traduce en vez de propagarse: el usuario acaba
            // de escribir un nombre y merece saber que ya existe, no un error de base de datos.
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'code' => 'NOMBRE_REPETIDO', 'mensaje' => 'Ya hay un lote con ese nombre en este paquete.'];
            }
            throw $e;
        }
        return ['ok' => true, 'subpaqueteId' => $id];
    }

    /**
     * Cambia nombre, modalidad o responsable de un lote. El «Resto» admite modalidad y responsable
     * pero no cambio de nombre: su nombre lo determina el paquete del que cuelga.
     *
     * @param array{nombre?: string, modalidad?: string, responsableUserId?: int|null} $campos
     * @return array{ok: bool, code?: string, mensaje?: string}
     */
    public function actualizar(int $projectId, int $subpaqueteId, array $campos, string $usuario): array
    {
        $sub = $this->buscar($projectId, $subpaqueteId);
        if ($sub === null) {
            return ['ok' => false, 'code' => 'NO_EXISTE', 'mensaje' => 'Ese lote no existe en esta obra.'];
        }
        $sets = [];
        $args = [];
        if (array_key_exists('nombre', $campos)) {
            if ((int) $sub['es_resto'] === 1) {
                return ['ok' => false, 'code' => 'RESTO_NO_SE_RENOMBRA', 'mensaje' => 'El lote «Resto» toma su nombre del paquete: no se renombra.'];
            }
            $nombre = trim((string) $campos['nombre']);
            if ($nombre === '') {
                return ['ok' => false, 'code' => 'SIN_NOMBRE', 'mensaje' => 'El lote necesita un nombre.'];
            }
            $sets[] = 'nombre = ?';
            $args[] = $nombre;
        }
        if (array_key_exists('modalidad', $campos)) {
            if (!in_array((string) $campos['modalidad'], self::modalidades(), true)) {
                return ['ok' => false, 'code' => 'MODALIDAD_INVALIDA', 'mensaje' => 'Esa modalidad de contratación no existe.'];
            }
            $sets[] = 'modalidad_contratacion = ?';
            $args[] = (string) $campos['modalidad'];
        }
        if (array_key_exists('responsableUserId', $campos)) {
            $sets[] = 'responsable_user_id = ?';
            $args[] = $campos['responsableUserId'] === null ? null : (int) $campos['responsableUserId'];
        }
        if ($sets === []) {
            return ['ok' => false, 'code' => 'NADA_QUE_CAMBIAR', 'mensaje' => 'No llegó ningún cambio.'];
        }
        $args[] = $usuario;
        $args[] = $projectId;
        $args[] = $subpaqueteId;
        try {
            $this->db->query(
                'UPDATE pdc_subpaquete SET ' . implode(', ', $sets) . ', creado_por = ?, updated_at = NOW()
                  WHERE project_id = ? AND id = ?',
                $args,
            );
        } catch (\PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'code' => 'NOMBRE_REPETIDO', 'mensaje' => 'Ya hay un lote con ese nombre en este paquete.'];
            }
            throw $e;
        }
        return ['ok' => true];
    }

    /**
     * Borra un lote. Sus insumos vuelven al «Resto», nunca se quedan sin destino.
     *
     * Si al borrarlo no queda ningún lote de verdad, el paquete **se desparte**: el «Resto» también
     * desaparece y todo vuelve a `subpaquete_id = 0`. Sin esto, un paquete que alguien partió y
     * deshizo se quedaría para siempre con un único lote «Resto» —el «subpaquete de una sola fila
     * por compatibilidad» que el alcance prohíbe— y contaría distinto en la cobertura que un paquete
     * que nunca se tocó.
     *
     * @return array{ok: bool, code?: string, mensaje?: string, desparte?: bool}
     */
    public function eliminar(int $projectId, int $subpaqueteId): array
    {
        $sub = $this->buscar($projectId, $subpaqueteId);
        if ($sub === null) {
            return ['ok' => false, 'code' => 'NO_EXISTE', 'mensaje' => 'Ese lote no existe en esta obra.'];
        }
        if ((int) $sub['es_resto'] === 1) {
            return ['ok' => false, 'code' => 'RESTO_NO_SE_BORRA', 'mensaje' => 'El «Resto» no se borra solo: se va al deshacer la partición del paquete.'];
        }
        $paqueteId = (int) $sub['paquete_id'];
        $restoId = (int) $this->db->query(
            'SELECT id FROM pdc_subpaquete WHERE project_id = ? AND paquete_id = ? AND es_resto = 1',
            [$projectId, $paqueteId],
        )->fetchColumn();

        $this->db->beginTransaction();
        try {
            $this->db->query(
                'UPDATE pdc_insumo_paquete SET subpaquete_id = ? WHERE project_id = ? AND subpaquete_id = ?',
                [$restoId, $projectId, $subpaqueteId],
            );
            $this->borrarPlanDe($projectId, $paqueteId, $subpaqueteId);
            $this->db->query('DELETE FROM pdc_subpaquete WHERE project_id = ? AND id = ?', [$projectId, $subpaqueteId]);

            $quedan = (int) $this->db->query(
                'SELECT COUNT(*) FROM pdc_subpaquete WHERE project_id = ? AND paquete_id = ? AND es_resto = 0',
                [$projectId, $paqueteId],
            )->fetchColumn();
            $desparte = $quedan === 0;
            if ($desparte) {
                foreach (['pdc_insumo_paquete', 'pdc_paquete_frente', 'pdc_plan_paquete', 'pdc_plan_paso'] as $t) {
                    $this->db->query(
                        "UPDATE {$t} SET subpaquete_id = ? WHERE project_id = ? AND subpaquete_id = ?",
                        [self::SIN_PARTIR, $projectId, $restoId],
                    );
                }
                $this->db->query('DELETE FROM pdc_subpaquete WHERE project_id = ? AND id = ?', [$projectId, $restoId]);
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true, 'desparte' => $desparte];
    }

    /**
     * Mueve insumos a un lote. Los insumos se identifican como en todo el módulo, por
     * (descripcion_norm, unidad), y tienen que pertenecer ya al paquete del lote: mover un insumo
     * entre paquetes distintos es asignar, y eso lo hace `PaquetesService::asignar()`.
     *
     * @param list<array{descripcionNorm: string, unidad: string}> $insumos
     * @return array{ok: bool, code?: string, mensaje?: string, movidos?: int}
     */
    public function moverInsumos(int $projectId, int $subpaqueteId, array $insumos): array
    {
        $sub = $this->buscar($projectId, $subpaqueteId);
        if ($sub === null) {
            return ['ok' => false, 'code' => 'NO_EXISTE', 'mensaje' => 'Ese lote no existe en esta obra.'];
        }
        if ($insumos === []) {
            return ['ok' => false, 'code' => 'SIN_INSUMOS', 'mensaje' => 'No llegó ningún insumo que mover.'];
        }
        $movidos = 0;
        $this->db->beginTransaction();
        try {
            foreach ($insumos as $i) {
                $this->db->query(
                    'UPDATE pdc_insumo_paquete SET subpaquete_id = ?, updated_at = NOW()
                      WHERE project_id = ? AND paquete_id = ? AND descripcion_norm = ? AND unidad = ?',
                    [$subpaqueteId, $projectId, (int) $sub['paquete_id'], (string) $i['descripcionNorm'], (string) $i['unidad']],
                );
                $movidos++;
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true, 'movidos' => $movidos];
    }

    /**
     * LA unidad del módulo: los destinos contratables de una obra.
     *
     * Un paquete sin partir da un destino con `subpaqueteId = 0`; un paquete partido da uno por lote
     * y NO se cuenta a sí mismo. Todo lo que antes contaba paquetes cuenta esto: el plan de fechas,
     * el tablero de vencimientos, la cobertura del plan y el flujo de caja. Que la lista salga de un
     * solo sitio es lo que impide que dos pantallas den números distintos.
     *
     * `generaProceso` viene ya resuelto —modalidad del lote si está partido, del paquete si no—
     * para que ningún consumidor tenga que volver a decidirlo.
     *
     * La lista se construye desde las ASIGNACIONES de insumos, así que un lote recién creado y
     * todavía vacío no aparece: no tiene valor ni nada que contratar. Es deliberado —la cobertura y
     * el flujo de caja miden dinero, y un lote vacío aportaría un cero que solo ensancha la tabla—,
     * pero conviene saberlo: `listar()` sí lo devuelve, porque la pantalla que reparte insumos tiene
     * que poder ver el lote al que va a moverlos.
     *
     * @return list<array<string, mixed>>
     */
    public function destinos(int $projectId, ?int $versionId = null): array
    {
        $vid = $this->versionActivaId($projectId, $versionId);
        $rows = $this->db->query(
            'SELECT a.paquete_id,
                    a.subpaquete_id,
                    p.nombre AS paquete_nombre,
                    p.modalidad_contratacion AS paquete_modalidad,
                    s.nombre AS sub_nombre,
                    s.modalidad_contratacion AS sub_modalidad,
                    s.es_resto,
                    s.responsable_user_id AS sub_responsable,
                    COUNT(*) AS insumos,
                    COALESCE(SUM(v.valor_total), 0) AS valor
               FROM pdc_insumo_paquete a
               JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
               LEFT JOIN pdc_subpaquete s ON s.id = a.subpaquete_id AND s.project_id = a.project_id
               LEFT JOIN pdc_insumo_vinculos v
                      ON v.project_id = a.project_id AND v.descripcion_norm = a.descripcion_norm
                     AND v.unidad = a.unidad AND v.version_id = ?
              WHERE a.project_id = ? AND a.paquete_id IS NOT NULL AND a.omitido = 0
              -- `s.orden` va en el GROUP BY y no solo en el ORDER BY: con `only_full_group_by`
              -- —que es el modo de este MySQL— ordenar por una columna no agrupada es un error, no
              -- un aviso.
              GROUP BY a.paquete_id, a.subpaquete_id, p.nombre, p.modalidad_contratacion,
                       s.nombre, s.modalidad_contratacion, s.es_resto, s.orden, s.responsable_user_id
              ORDER BY p.nombre, s.es_resto, s.orden, a.subpaquete_id',
            [$vid, $projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $subId = (int) $r['subpaquete_id'];
            $partido = $subId !== self::SIN_PARTIR;
            $modalidad = $partido ? (string) $r['sub_modalidad'] : (string) $r['paquete_modalidad'];
            $out[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'subpaqueteId' => $subId,
                'paqueteNombre' => (string) $r['paquete_nombre'],
                // El nombre que se muestra: el del lote cuando está partido, porque es lo que se
                // contrata. El del paquete queda aparte para poder decir «Pisos › Porcelanato».
                'nombre' => $partido ? (string) $r['sub_nombre'] : (string) $r['paquete_nombre'],
                'esLote' => $partido,
                'esResto' => $partido && (int) $r['es_resto'] === 1,
                'modalidad' => $modalidad,
                'generaProceso' => in_array($modalidad, self::MODALIDADES_CON_PROCESO, true),
                'responsableUserId' => $partido && $r['sub_responsable'] !== null ? (int) $r['sub_responsable'] : null,
                'insumos' => (int) $r['insumos'],
                'valor' => (float) $r['valor'],
            ];
        }
        return $out;
    }

    /**
     * A qué `subpaquete_id` debe caer un insumo que se acaba de asignar a un paquete.
     *
     * El motor de sugerencias trabaja a nivel de paquete grande y no sabe de lotes —así se decidió,
     * porque los lotes son casuística de obra y el motor aprende para toda la empresa—. Cuando el
     * paquete destino está partido, su asignación aterriza en el «Resto»: es el único destino del
     * paquete que existe sin que un humano haya decidido nada.
     */
    public function destinoDeAsignacion(int $projectId, int $paqueteId): int
    {
        $resto = $this->db->query(
            'SELECT id FROM pdc_subpaquete WHERE project_id = ? AND paquete_id = ? AND es_resto = 1',
            [$projectId, $paqueteId],
        )->fetchColumn();
        return $resto === false ? self::SIN_PARTIR : (int) $resto;
    }

    /**
     * Resumen del paquete sombrilla: rango de fechas de sus lotes, avance agregado y —lo que hace
     * honesta la pantalla— cuánto de su valor NO entra al plan y por qué.
     *
     * Derivado, nunca almacenado: guardarlo obligaría a invalidarlo en cada recálculo del plan y
     * sería lo primero que se queda viejo.
     *
     * @return array<string, mixed>|null null si el paquete no está partido
     */
    public function resumenSombrilla(int $projectId, int $paqueteId, ?int $versionId = null): ?array
    {
        $lotes = $this->listar($projectId, $paqueteId, $versionId);
        if ($lotes === []) {
            return null;
        }
        $ids = array_map(static fn (array $l): int => $l['subpaqueteId'], $lotes);
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $rango = $this->db->query(
            "SELECT MIN(fecha_arranque) AS desde, MAX(fecha_ancla) AS hasta, COUNT(*) AS con_plan
               FROM pdc_plan_paquete
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id IN ({$marcas})",
            array_merge([$projectId, $paqueteId], $ids),
        )->fetch(\PDO::FETCH_ASSOC);
        $avance = $this->db->query(
            "SELECT COUNT(*) AS pasos, SUM(CASE WHEN fecha_real IS NOT NULL THEN 1 ELSE 0 END) AS cumplidos
               FROM pdc_plan_paso
              WHERE project_id = ? AND paquete_id = ? AND subpaquete_id IN ({$marcas})",
            array_merge([$projectId, $paqueteId], $ids),
        )->fetch(\PDO::FETCH_ASSOC);

        $valorTotal = 0.0;
        $valorFuera = 0.0;
        $fuera = [];
        foreach ($lotes as $l) {
            $valorTotal += $l['valor'];
            if (!$l['generaProceso']) {
                $valorFuera += $l['valor'];
                $fuera[] = ['nombre' => $l['nombre'], 'modalidad' => $l['modalidad'], 'valor' => $l['valor']];
            }
        }
        $pasos = (int) ($avance['pasos'] ?? 0);
        return [
            'lotes' => count($lotes),
            'valorTotal' => $valorTotal,
            'valorFueraDelPlan' => $valorFuera,
            'lotesFueraDelPlan' => $fuera,
            'desde' => $rango['desde'] ?? null,
            'hasta' => $rango['hasta'] ?? null,
            'lotesConPlan' => (int) ($rango['con_plan'] ?? 0),
            'pasos' => $pasos,
            'pasosCumplidos' => (int) ($avance['cumplidos'] ?? 0),
            'avance' => $pasos === 0 ? null : round(((int) $avance['cumplidos']) * 100 / $pasos, 1),
        ];
    }

    /** @return list<string> */
    public static function modalidades(): array
    {
        return ['contrato', 'orden_compra', 'consumo_directo', 'no_contratable'];
    }

    // ---------------------------------------------------------------------------------------------

    private function insertar(
        int $projectId,
        int $paqueteId,
        string $nombre,
        string $modalidad,
        bool $esResto,
        int $orden,
        string $usuario,
    ): int {
        $this->db->query(
            'INSERT INTO pdc_subpaquete
                (project_id, paquete_id, nombre, modalidad_contratacion, es_resto, orden, creado_por, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$projectId, $paqueteId, $nombre, $modalidad, $esResto ? 1 : 0, $orden, $usuario],
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscar(int $projectId, int $subpaqueteId): ?array
    {
        $row = $this->db->query(
            'SELECT id, project_id, paquete_id, nombre, modalidad_contratacion, es_resto
               FROM pdc_subpaquete WHERE project_id = ? AND id = ?',
            [$projectId, $subpaqueteId],
        )->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Borra el plan de un destino que deja de existir. A diferencia del recálculo normal, aquí sí se
     * borra el avance real: el lote desaparece, y conservar sus fechas reales sin lote al que
     * pertenecer las volvería huérfanas e invisibles. Quien borra un lote está diciendo que ese
     * contrato no existe.
     */
    private function borrarPlanDe(int $projectId, int $paqueteId, int $subpaqueteId): void
    {
        foreach (['pdc_plan_paso', 'pdc_plan_paquete', 'pdc_paquete_frente'] as $t) {
            $this->db->query(
                "DELETE FROM {$t} WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?",
                [$projectId, $paqueteId, $subpaqueteId],
            );
        }
    }

    private function versionActivaId(int $projectId, ?int $versionId): ?int
    {
        if ($versionId !== null) {
            return $versionId;
        }
        $id = $this->db->query(
            'SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1 ORDER BY id DESC LIMIT 1',
            [$projectId],
        )->fetchColumn();
        return $id === false ? null : (int) $id;
    }
}
