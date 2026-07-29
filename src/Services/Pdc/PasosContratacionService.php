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
     * Los siete de la constante de código, con su id del catálogo cuando existe.
     *
     * El id se busca, pero NO se exige: si el catálogo estuviera vacío el plan se sigue calculando
     * igual (columnas y pesos salen de la constante) y las filas quedan sin identidad de paso. Las
     * fechas nunca dependen de que la semilla esté puesta.
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
     * @param list<array{clave:string,alias?:string,diasFijos?:int|null}> $pasos en el orden deseado
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
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true, 'pasos' => count($pasos)];
    }

    /** La obra vuelve al proceso por defecto de la empresa. */
    public function restablecer(int $projectId): void
    {
        $this->db->query('DELETE FROM pdc_proyecto_pasos WHERE project_id = ?', [$projectId]);
    }
}
