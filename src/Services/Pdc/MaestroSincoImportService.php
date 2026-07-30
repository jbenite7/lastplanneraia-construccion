<?php

namespace App\Services\Pdc;

/**
 * Import del maestro SINCO: preview (parsear + guardar temporal) y confirmar
 * (upsert transaccional por codigo_sinco, con enriquecimiento de filas de A2).
 *
 * @phpstan-import-type SincoErrorFila from MaestroSincoParser
 * @phpstan-import-type SincoResumen from MaestroSincoParser
 */
final class MaestroSincoImportService
{
    public function __construct(
        private readonly \Database $db,
        private readonly PresupuestoImportStore $store,
        private readonly MaestroSincoParser $parser,
    ) {
    }

    /**
     * @return array{ok: false, errores: list<SincoErrorFila>}|array{
     *     ok: true,
     *     importToken: string,
     *     resumen: SincoResumen
     * }
     */
    public function preview(string $rutaArchivo, string $nombre, string $usuario): array
    {
        $r = $this->parser->parse($rutaArchivo);
        if (!$r['valido']) {
            return ['ok' => false, 'errores' => $r['errores']];
        }
        $token = $this->store->guardar($rutaArchivo, ['nombre' => $nombre, 'usuario' => $usuario]);
        return ['ok' => true, 'importToken' => $token, 'resumen' => $r['resumen']];
    }

    /**
     * `conflictos` recoge los insumos que comparten descripción normalizada y unidad con otro que
     * YA tiene código SINCO: no se pisan, se reportan.
     *
     * @return array{ok: false, code: 'TOKEN_EXPIRED'|'INVALID_FILE'}|array{
     *     ok: true,
     *     creados: int,
     *     actualizados: int,
     *     enriquecidos: int,
     *     conflictos: list<array{codigoSinco: string, descripcion: string, chocaCon: mixed}>,
     *     reenganchados: int
     * }
     */
    public function confirmar(string $token): array
    {
        $ruta = $this->store->ruta($token);
        $meta = $this->store->meta($token);
        if ($ruta === null || $meta === null) {
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED'];
        }
        try {
            $r = $this->parser->parse($ruta);
        } catch (\RuntimeException) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        if (!$r['valido']) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        $usuario = (string) ($meta['usuario'] ?? '');

        $creados = 0; $actualizados = 0; $enriquecidos = 0; $conflictos = [];

        $this->db->beginTransaction();
        try {
            foreach ($r['insumos'] as $ins) {
                // tipo_insumo es varchar(100); el parser lo trae hasta 150 (espeja Agrupacion).
                $tipoInsumo = mb_substr($ins['tipoInsumo'], 0, 100);

                $porCodigo = $this->db->query(
                    'SELECT id, tipo_recurso, clasificado_at FROM general_maestro_insumos WHERE codigo_sinco = ?',
                    [$ins['codigoSinco']],
                )->fetch(\PDO::FETCH_ASSOC);

                if ($porCodigo !== false) {
                    $tipoRecurso = $this->resolverTipoRecurso(
                        $ins['tipoRecurso'],
                        $porCodigo['tipo_recurso'],
                        $porCodigo['clasificado_at'],
                    );
                    $this->db->query(
                        'UPDATE general_maestro_insumos
                         SET descripcion = ?, descripcion_norm = ?, unidad = ?, tipo_insumo = ?, agrupacion = ?,
                             tipo_recurso = ?, valor_unitario = ?, iva = ?, activo = 1, actualizado_por = ?, updated_at = NOW()
                         WHERE id = ?',
                        [$ins['descripcion'], $ins['descripcionNorm'], $ins['unidad'], $tipoInsumo, $ins['agrupacion'],
                         $tipoRecurso, $ins['valorUnitario'], $ins['iva'], $usuario, (int) $porCodigo['id']],
                    );
                    $actualizados++;
                    continue;
                }

                // Sin match por código: ¿hay una fila con la misma norma+unidad?
                $huerfana = $this->db->query(
                    'SELECT id, codigo_sinco, tipo_recurso, clasificado_at FROM general_maestro_insumos WHERE descripcion_norm = ? AND unidad = ?',
                    [$ins['descripcionNorm'], $ins['unidad']],
                )->fetch(\PDO::FETCH_ASSOC);

                if ($huerfana !== false) {
                    if ($huerfana['codigo_sinco'] === null || $huerfana['codigo_sinco'] === '') {
                        $tipoRecurso = $this->resolverTipoRecurso(
                            $ins['tipoRecurso'],
                            $huerfana['tipo_recurso'],
                            $huerfana['clasificado_at'],
                        );
                        $this->db->query(
                            'UPDATE general_maestro_insumos
                             SET codigo_sinco = ?, descripcion = ?, tipo_insumo = ?, agrupacion = ?, tipo_recurso = ?,
                                 valor_unitario = ?, iva = ?, activo = 1, actualizado_por = ?, updated_at = NOW()
                             WHERE id = ?',
                            [$ins['codigoSinco'], $ins['descripcion'], $tipoInsumo, $ins['agrupacion'], $tipoRecurso,
                             $ins['valorUnitario'], $ins['iva'], $usuario, (int) $huerfana['id']],
                        );
                        $enriquecidos++;
                    } else {
                        // Otro insumo SINCO ya ocupa esa norma+unidad: no pisar; reportar.
                        $conflictos[] = ['codigoSinco' => $ins['codigoSinco'], 'descripcion' => $ins['descripcion'], 'chocaCon' => $huerfana['codigo_sinco']];
                    }
                    continue;
                }

                $this->db->query(
                    'INSERT INTO general_maestro_insumos
                        (codigo_sinco, descripcion, descripcion_norm, unidad, tipo_insumo, agrupacion, tipo_recurso, valor_unitario, iva, activo, creado_por, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())',
                    [$ins['codigoSinco'], $ins['descripcion'], $ins['descripcionNorm'], $ins['unidad'], $tipoInsumo,
                     $ins['agrupacion'], $ins['tipoRecurso'], $ins['valorUnitario'], $ins['iva'], $usuario],
                );
                $creados++;
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        // Fuera de la transacción a propósito: el maestro ya está guardado y no debe deshacerse si
        // el re-enganche falla. Son dos cosas distintas —el catálogo y la cola de vínculos— y la
        // primera vale por sí sola.
        //
        // Sin esta llamada, cargar el maestro metía miles de insumos y dejaba la cola de pendientes
        // exactamente igual de larga, con el insumo que faltaba ya dentro del catálogo. El auto-match
        // sólo vivía en `generarVinculos()`, que se dispara desde el lado del presupuesto.
        $reenganchados = (new MaestroInsumosService($this->db))->reengancharPendientes();

        $this->store->eliminar($token);
        return ['ok' => true, 'creados' => $creados, 'actualizados' => $actualizados, 'enriquecidos' => $enriquecidos, 'conflictos' => $conflictos, 'reenganchados' => $reenganchados];
    }

    /**
     * Qué `tipo_recurso` queda tras un re-import, cuando el entrante y el guardado no coinciden.
     *
     * Punto 5 de la condición de hecho de la Ola 2: reimportar el maestro NO puede devolver a «sin
     * clasificar» un equipo que una persona ya clasificó. Este servicio escribía `tipo_recurso` a
     * ciegas por `codigo_sinco`, y los 167 equipos que la migración movió a tránsito tienen TODOS
     * código: sin esto, la siguiente carga de SINCO borraba el trabajo humano entero y en silencio.
     *
     * La regla es estrecha a propósito — sólo protege equipos, y sólo contra una DEGRADACIÓN:
     * - Entrante genérico o de tránsito, guardado clasificado, con autor humano → gana la persona.
     * - Entrante ya clasificado → gana SINCO: la fuente se puso más precisa, eso es dato nuevo.
     * - Entrante que no es equipo → gana SINCO: dejó de ser equipo, no es asunto de esta regla.
     *
     * Lo que permite distinguir los casos es `clasificado_at`: sin él, «lo dijo una persona» y «lo
     * trajo el Excel» son indistinguibles. La migración deja ese campo NULL a propósito.
     */
    private function resolverTipoRecurso(?string $entrante, ?string $guardado, ?string $clasificadoAt): ?string
    {
        // Sólo se protege un equipo clasificado por una persona.
        if ($clasificadoAt === null || !TipoRecursoEquipo::esClasificado($guardado)) {
            return $entrante;
        }
        // Si lo entrante ya es una clasificación, es una corrección de la fuente: pasa.
        if (TipoRecursoEquipo::esClasificado($entrante)) {
            return $entrante;
        }
        // Si lo entrante dejó de ser equipo, SINCO reclasificó de verdad: pasa.
        if (!TipoRecursoEquipo::esEquipo($entrante)) {
            return $entrante;
        }
        // Queda el único caso peligroso: SINCO manda el genérico o el de tránsito sobre una
        // clasificación humana. Se conserva lo que dijo la persona.
        return $guardado;
    }
}
