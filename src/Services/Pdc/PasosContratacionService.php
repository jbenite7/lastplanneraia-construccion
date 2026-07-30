<?php

namespace App\Services\Pdc;

/**
 * A4.1 · Qué pasos tiene el proceso de contratación de una obra.
 *
 * Única fuente de verdad: `calcular()`, la API y la pantalla preguntan aquí y nadie más recorre
 * `PlanFechasService::PASOS` por su cuenta. La regla de cero regresión vive en `deProyecto()`: una
 * obra sin filas propias usa la constante de código, tal cual, en el mismo orden.
 */
class PasosContratacionService
{
    /**
     * Lista blanca de columnas de `general_dias_procesos_contratacion` que un paso puede referenciar.
     *
     * `col_legacy` sale de la base y se interpola en el SELECT de `calcular()` —no puede ir como
     * parámetro, es un nombre de columna—, así que sin este filtro una fila del catálogo con texto
     * arbitrario sería una inyección SQL. Se deriva de PASOS para no poder desalinearse.
     *
     * @return list<string>
     */
    public static function columnasLegacy(): array
    {
        return array_column(PlanFechasService::PASOS, 'col');
    }

    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * @return list<array{id:int,clave:string,nombre:string,colLegacy:?string,diasSugeridos:?int,peso:?float,ordenDefault:int}>
     */
    public function catalogo(): array
    {
        $rows = $this->db->query(
            'SELECT id, clave, nombre, col_legacy, dias_sugeridos, peso_reparto, orden_default
             FROM general_pasos_contratacion WHERE activo = 1 ORDER BY orden_default, id',
        )->fetchAll(\PDO::FETCH_ASSOC);
        $legales = self::columnasLegacy();
        $out = [];
        foreach ($rows as $r) {
            $col = $r['col_legacy'] === null ? null : (string) $r['col_legacy'];
            $out[] = [
                'id' => (int) $r['id'],
                'clave' => (string) $r['clave'],
                'nombre' => (string) $r['nombre'],
                // Una columna que no esté en la lista blanca se trata como «sin respaldo legacy»,
                // no como error: el paso sigue siendo usable con días fijos y nunca llega al SQL.
                'colLegacy' => $col !== null && in_array($col, $legales, true) ? $col : null,
                'diasSugeridos' => $r['dias_sugeridos'] === null ? null : (int) $r['dias_sugeridos'],
                'peso' => $r['peso_reparto'] === null ? null : (float) $r['peso_reparto'],
                'ordenDefault' => (int) $r['orden_default'],
            ];
        }
        return $out;
    }

    public function configurado(int $projectId): bool
    {
        return (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_proyecto_pasos WHERE project_id = ?',
            [$projectId],
        )->fetchColumn() > 0;
    }

    /**
     * Los pasos efectivos de la obra. Sin filas propias, los siete por defecto.
     *
     * @return list<array{pasoId:?int,clave:string,nombre:string,colLegacy:?string,diasFijos:?int,peso:?float}>
     */
    public function deProyecto(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT c.id, c.clave, c.nombre, c.col_legacy, c.peso_reparto, p.alias, p.dias_fijos
             FROM pdc_proyecto_pasos p
             JOIN general_pasos_contratacion c ON c.id = p.paso_id
             WHERE p.project_id = ? AND c.activo = 1
             ORDER BY p.orden, p.id',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        if ($rows === []) {
            return $this->porDefecto();
        }

        $legales = self::columnasLegacy();
        $out = [];
        foreach ($rows as $r) {
            $col = $r['col_legacy'] === null ? null : (string) $r['col_legacy'];
            $col = $col !== null && in_array($col, $legales, true) ? $col : null;
            $alias = trim((string) $r['alias']);
            $out[] = [
                'pasoId' => (int) $r['id'],
                'clave' => (string) $r['clave'],
                'nombre' => $alias !== '' ? $alias : (string) $r['nombre'],
                'colLegacy' => $col,
                'diasFijos' => $r['dias_fijos'] === null ? null : (int) $r['dias_fijos'],
                'peso' => $r['peso_reparto'] === null ? null : (float) $r['peso_reparto'],
            ];
        }
        return $out;
    }

    /**
     * Los siete de la constante de código, con su id del catálogo.
     *
     * Las **duraciones** no dependen de la semilla: columnas y pesos salen de la constante, así que
     * una obra sin configurar da las mismas fechas aunque el catálogo cambie. La **identidad** sí:
     * `pdc_plan_paso` se indexa por `paso_id` desde A4.1, y un id que falte deja el plan sin poder
     * escribirse de forma idempotente. Por eso `PlanFechasService::exigirIdentidad()` para el cálculo
     * en seco si algún id no aparece, en vez de escribir un plan a medias que nadie notaría. Aquí se
     * devuelve el null tal cual —resolver o inventar un id sería esconder el problema— y quien decide
     * qué hacer con él es el llamador.
     *
     * @return list<array{pasoId:?int,clave:string,nombre:string,colLegacy:?string,diasFijos:?int,peso:?float}>
     */
    private function porDefecto(): array
    {
        $ids = [];
        foreach ($this->db->query('SELECT id, clave FROM general_pasos_contratacion')->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $ids[(string) $r['clave']] = (int) $r['id'];
        }
        $out = [];
        foreach (PlanFechasService::PASOS as $i => $p) {
            $out[] = [
                'pasoId' => $ids[$p['clave']] ?? null,
                'clave' => $p['clave'],
                'nombre' => $p['paso'],
                'colLegacy' => $p['col'],
                'diasFijos' => null,
                'peso' => PlanFechasService::PESOS_REPARTO[$i],
            ];
        }
        return $out;
    }

    /**
     * Reemplaza la configuración de la obra. Todo o nada.
     *
     * El tipo es deliberadamente flojo: esto llega de un JSON del cliente, así que ni las claves
     * están garantizadas ni los índices tienen por qué ser contiguos. Prometer
     * `list<array{clave:string,...}>` haría que el análisis estático diera por ciertas unas garantías
     * que el cliente no da, y marcaría como redundantes justo las comprobaciones que hacen falta.
     *
     * @param array<int, array<string, mixed>> $pasos en el orden deseado
     * @return array{ok:bool,code?:string,mensaje?:string,pasos?:int}
     */
    public function guardar(int $projectId, array $pasos, string $usuario): array
    {
        if ($pasos === []) {
            return ['ok' => false, 'code' => 'SIN_PASOS', 'mensaje' => 'El proceso necesita al menos un paso.'];
        }
        $cat = [];
        foreach ($this->catalogo() as $c) {
            $cat[$c['clave']] = $c;
        }
        $vistas = [];
        foreach ($pasos as $p) {
            $clave = (string) ($p['clave'] ?? '');
            if (!isset($cat[$clave])) {
                return ['ok' => false, 'code' => 'PASO_DESCONOCIDO', 'mensaje' => "El paso «{$clave}» no está en el catálogo."];
            }
            if (isset($vistas[$clave])) {
                return ['ok' => false, 'code' => 'PASO_REPETIDO', 'mensaje' => "El paso «{$cat[$clave]['nombre']}» aparece dos veces."];
            }
            $vistas[$clave] = true;
            $dias = $p['diasFijos'] ?? null;
            if ($cat[$clave]['colLegacy'] === null && (!is_int($dias) || $dias < 0)) {
                return [
                    'ok' => false, 'code' => 'DIAS_FIJOS_REQUERIDOS',
                    'mensaje' => "«{$cat[$clave]['nombre']}» no tiene duración en el catálogo de la empresa: hay que decir cuántos días dura en esta obra.",
                ];
            }
        }

        // La forma limpia de la lista, para el historial: lo que llegó del cliente ya está validado
        // arriba, pero conserva claves sueltas y tipos flojos que no queremos congelar en el JSON.
        $normalizados = [];
        foreach (array_values($pasos) as $p) {
            $c = $cat[(string) $p['clave']];
            $normalizados[] = [
                'clave' => (string) $p['clave'],
                'alias' => trim((string) ($p['alias'] ?? '')),
                'diasFijos' => $c['colLegacy'] === null ? (int) $p['diasFijos'] : null,
            ];
        }

        $this->db->beginTransaction();
        try {
            // Se borra y se reescribe entera: la lista es corta, el orden queda contiguo desde 0, y
            // no sobrevive ninguna fila de una configuración anterior que ya nadie eligió.
            $this->db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$projectId]);
            foreach (array_values($pasos) as $i => $p) {
                $c = $cat[(string) $p['clave']];
                $this->db->query(
                    'INSERT INTO pdc_proyecto_pasos
                        (project_id, paso_id, orden, alias, dias_fijos, actualizado_por, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())',
                    [
                        $projectId, $c['id'], $i, trim((string) ($p['alias'] ?? '')),
                        $c['colLegacy'] === null ? (int) $p['diasFijos'] : null, $usuario,
                    ],
                );
            }
            $this->anotarHistorial($projectId, $normalizados, $usuario);
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true, 'pasos' => count($pasos)];
    }

    /**
     * Anota la configuración que acaba de quedar vigente. Solo anexa: nunca actualiza ni borra.
     *
     * Va DENTRO de la transacción de quien la llama, para que no pueda quedar una configuración
     * guardada sin su entrada de historial ni al revés.
     *
     * @param list<array{clave:string,alias:string,diasFijos:?int}> $pasos lista vacía = la obra
     *        volvió al proceso por defecto de la empresa, que también es un cambio que registrar
     */
    private function anotarHistorial(int $projectId, array $pasos, string $usuario): void
    {
        $this->db->query(
            'INSERT INTO pdc_proyecto_pasos_historial (project_id, configuracion, pasos, actualizado_por, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [
                $projectId,
                json_encode($pasos, JSON_UNESCAPED_UNICODE),
                count($pasos),
                mb_substr($usuario, 0, 100),
            ],
        );
    }

    /**
     * Quién cambió la configuración de esta obra, cuándo y a qué quedó.
     *
     * @return list<array{id:int,usuario:string,cuando:string,pasos:list<array{clave:string,alias:string,diasFijos:?int}>}>
     */
    public function historial(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT id, configuracion, actualizado_por, created_at
             FROM pdc_proyecto_pasos_historial WHERE project_id = ? ORDER BY created_at DESC, id DESC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $pasos = json_decode((string) $r['configuracion'], true);
            $out[] = [
                'id' => (int) $r['id'],
                'usuario' => (string) $r['actualizado_por'],
                'cuando' => (string) $r['created_at'],
                'pasos' => is_array($pasos) ? $pasos : [],
            ];
        }
        return $out;
    }

    /**
     * Obras de las que se puede copiar: las que este usuario ve Y tienen configuración propia.
     *
     * Se excluyen a propósito las obras sin filas en `pdc_proyecto_pasos`. Ofrecerlas copiaría «los
     * siete por defecto» como si fueran una decisión de esa obra, y a partir de ahí el destino
     * dejaría de seguir el proceso por defecto de la empresa aunque nadie lo hubiera elegido —
     * justo el contrato de cero regresión que A4.1 demostró.
     *
     * El filtro por `project_members` no es cosmético: `origenId` llega del cliente, y sin él la
     * pantalla sería una forma de leer cómo trabaja una obra a la que no se tiene acceso.
     *
     * @return list<array{projectId:int,nombre:string,pasos:int}>
     */
    public function origenesDisponibles(int $projectIdActual, int $userId): array
    {
        $rows = $this->db->query(
            'SELECT p.Id AS project_id, p.Proyecto_Proceso AS nombre, COUNT(pp.id) AS pasos
             FROM project_members pm
             JOIN general_proyectos_procesos p ON p.Id = pm.project_id
             JOIN pdc_proyecto_pasos pp ON pp.project_id = p.Id
             WHERE pm.user_id = ? AND p.Id <> ? AND p.Activo = 1
             GROUP BY p.Id, p.Proyecto_Proceso
             ORDER BY p.Proyecto_Proceso',
            [$userId, $projectIdActual],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'projectId' => (int) $r['project_id'],
                'nombre' => (string) $r['nombre'],
                'pasos' => (int) $r['pasos'],
            ];
        }
        return $out;
    }

    /**
     * Qué se copiaría, para poder enseñarlo antes de copiarlo.
     *
     * `incompleta` marca el riesgo que registró el diseño: si la obra origen quedó a medias, la
     * copia hereda ese hueco. Se decide con el mismo criterio que valida `guardar()` —un paso sin
     * respaldo en el catálogo necesita días fijos—, así que lo que aquí se advierte es exactamente
     * lo que allí sería un error.
     *
     * @return array{pasos: list<array{clave:string,nombre:string,alias:string,diasFijos:?int,tieneCatalogo:bool}>, incompleta: bool}
     */
    public function previsualizarCopia(int $origenId): array
    {
        $rows = $this->db->query(
            'SELECT c.clave, c.nombre, c.col_legacy, p.alias, p.dias_fijos
             FROM pdc_proyecto_pasos p
             JOIN general_pasos_contratacion c ON c.id = p.paso_id
             WHERE p.project_id = ? AND c.activo = 1
             ORDER BY p.orden, p.id',
            [$origenId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $legales = self::columnasLegacy();
        $pasos = [];
        $incompleta = false;
        foreach ($rows as $r) {
            $col = $r['col_legacy'] === null ? null : (string) $r['col_legacy'];
            $tieneCatalogo = $col !== null && in_array($col, $legales, true);
            $dias = $r['dias_fijos'] === null ? null : (int) $r['dias_fijos'];
            if (!$tieneCatalogo && $dias === null) {
                $incompleta = true;
            }
            $pasos[] = [
                'clave' => (string) $r['clave'],
                'nombre' => (string) $r['nombre'],
                'alias' => trim((string) $r['alias']),
                'diasFijos' => $dias,
                'tieneCatalogo' => $tieneCatalogo,
            ];
        }
        return ['pasos' => $pasos, 'incompleta' => $incompleta];
    }

    /**
     * Copia la configuración de una obra a otra. Puntual, no un vínculo vivo.
     *
     * Se reutiliza `guardar()` en vez de un `INSERT ... SELECT`: así la copia pasa por exactamente
     * las mismas validaciones que una configuración escrita a mano (paso desconocido, repetido,
     * días fijos obligatorios), y no hay forma de meter por la puerta de atrás una configuración
     * que la pantalla habría rechazado. Terminada la copia los dos proyectos son independientes:
     * editar el destino no toca el origen, porque no queda ninguna referencia entre ellos.
     *
     * @return array{ok:bool,code?:string,mensaje?:string,pasos?:int}
     */
    public function copiarDesde(int $origenId, int $destinoId, string $usuario): array
    {
        if ($origenId === $destinoId) {
            return ['ok' => false, 'code' => 'ORIGEN_ES_DESTINO', 'mensaje' => 'Una obra no puede copiarse a sí misma.'];
        }
        if (!$this->configurado($origenId)) {
            return [
                'ok' => false,
                'code' => 'ORIGEN_SIN_CONFIGURAR',
                'mensaje' => 'Esa obra no tiene un proceso propio: usa el proceso por defecto de la empresa, que esta obra ya tiene.',
            ];
        }
        $pasos = [];
        foreach ($this->previsualizarCopia($origenId)['pasos'] as $p) {
            $pasos[] = ['clave' => $p['clave'], 'alias' => $p['alias'], 'diasFijos' => $p['diasFijos']];
        }
        return $this->guardar($destinoId, $pasos, $usuario);
    }

    /**
     * La obra vuelve al proceso por defecto de la empresa.
     *
     * Deja entrada en el historial con la lista vacía: renunciar a la configuración propia mueve las
     * fechas igual que cambiarla, y es exactamente el movimiento que alguien va a querer rastrear
     * cuando pregunte «¿por qué se movieron mis fechas?».
     */
    public function restablecer(int $projectId, string $usuario): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$projectId]);
            $this->anotarHistorial($projectId, [], $usuario);
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
    }
}
