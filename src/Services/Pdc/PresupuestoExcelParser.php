<?php

namespace App\Services\Pdc;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parser de la hoja "Presupuesto" del Excel del software de presupuestos.
 * Reglas (spec A1): encabezados por nombre; fila con "Tipo Insumo" = insumo de la
 * actividad vigente; fila jerárquica = actividad cuando tiene "ID APU" no vacío,
 * o cuando tiene CANTIDAD numérica en nivel >= 3; validación todo-o-nada con
 * reporte por fila/columna (tope 200 errores).
 */
final class PresupuestoExcelParser
{
    public const SHEET = 'Presupuesto';
    public const MAX_ERRORES = 200;

    /** columnas requeridas → clave normalizada */
    private const REQUERIDAS = ['CODIGO', 'DESCRIPCION', 'UM', 'CANTIDAD', 'VERSION', 'ID APU', 'CANT APU', 'REND', 'VRUNIT', 'TIPO INSUMO'];

    public function parse(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true); // sin estilos: menos memoria (SiteGround)
        $book = $reader->load($filePath);
        $sheet = $book->getSheetByName(self::SHEET);
        if ($sheet === null) {
            throw new \RuntimeException('El archivo no tiene la hoja "' . self::SHEET . '".');
        }
        $rows = $sheet->toArray(null, true, false, false);
        $book->disconnectWorksheets();

        if (count($rows) < 2) {
            throw new \RuntimeException('La hoja "' . self::SHEET . '" está vacía.');
        }

        $mapa = $this->mapearEncabezados($rows[0]);
        $faltantes = array_diff(self::REQUERIDAS, array_keys($mapa));
        if ($faltantes !== []) {
            throw new \RuntimeException('Faltan columnas requeridas: ' . implode(', ', $faltantes) . '.');
        }

        $items = [];
        $insumos = [];
        $errores = [];
        $porCodigo = [];
        $actividadVigente = null; // ['codigo'=>..., 'cantidad'=>float]
        $versionLabel = null;
        $conteo = ['capitulo' => 0, 'subcapitulo' => 0, 'grupo' => 0, 'actividad' => 0];
        $costoTotal = 0.0;

        $err = function (int $fila, string $col, string $motivo) use (&$errores): bool {
            if (count($errores) >= self::MAX_ERRORES) {
                return false;
            }
            $errores[] = ['fila' => $fila, 'columna' => $col, 'motivo' => $motivo];
            return count($errores) < self::MAX_ERRORES;
        };

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }
            $fila = $i + 1; // número de fila del Excel (1-based, encabezado = 1)
            // Encabezados opcionales (PADRE, SUBCAPITULO, AGRUPACION…) pueden no existir: '' en ese caso.
            $cel = function (string $k) use ($row, $mapa): string {
                $idx = $mapa[$k] ?? null;
                return $idx === null ? '' : trim((string) ($row[$idx] ?? ''));
            };

            if ($this->filaVacia($row)) {
                continue;
            }
            if ($versionLabel === null && $cel('VERSION') !== '') {
                $versionLabel = $cel('VERSION');
            }

            $tipoInsumo = $cel('TIPO INSUMO');
            if ($tipoInsumo !== '') {
                // ---- Fila de insumo ----
                if ($actividadVigente === null) {
                    if (!$err($fila, 'Tipo Insumo', 'Insumo sin actividad previa que lo contenga.')) break;
                    continue;
                }
                $um = $cel('UM');
                if ($um === '') {
                    if (!$err($fila, 'UM', 'El insumo no tiene unidad.')) break;
                }
                $rend = $this->numero($cel('REND'));
                $cantApu = $this->numero($cel('CANT APU'));
                $vrUnit = $this->numero($cel('VRUNIT'));
                if ($vrUnit === null) {
                    if (!$err($fila, 'VrUnit', 'Valor unitario no numérico o vacío.')) break;
                }
                if ($rend === null) {
                    if (!$err($fila, 'Rend', 'Rendimiento no numérico o vacío.')) break;
                }
                if ($um === '' || $vrUnit === null || $rend === null) {
                    continue; // fila insumo inválida: no acumular
                }
                $cantidadTotal = round($rend * $actividadVigente['cantidad'], 4);
                $valorTotal = round($cantidadTotal * $vrUnit, 2);
                $costoTotal += $valorTotal;
                $insumos[] = [
                    'codigo_actividad' => $actividadVigente['codigo'],
                    'descripcion' => mb_substr($cel('DESCRIPCION'), 0, 500),
                    'tipo_insumo' => mb_substr($tipoInsumo, 0, 100),
                    'unidad' => mb_substr($um, 0, 20),
                    'cant_apu' => $cantApu,
                    'rendimiento' => $rend,
                    'cantidad_total' => $cantidadTotal,
                    'valor_unitario' => $vrUnit,
                    'valor_total' => $valorTotal,
                    'iva' => $this->numero($cel('IVA')),
                ];
                continue;
            }

            // ---- Fila jerárquica ----
            $codigo = $cel('CODIGO');
            if ($codigo === '') {
                if (!$err($fila, 'Código', 'Fila sin código ni tipo de insumo.')) break;
                continue;
            }
            if (isset($porCodigo[$codigo])) {
                if (!$err($fila, 'Código', "Código duplicado: {$codigo}.")) break;
                continue;
            }
            $segmentos = explode('.', $codigo);
            $nivel = count($segmentos);
            $codigoPadre = $nivel > 1 ? implode('.', array_slice($segmentos, 0, -1)) : null;
            if ($codigoPadre !== null && !isset($porCodigo[$codigoPadre])) {
                if (!$err($fila, 'Código', "El código padre {$codigoPadre} no existe antes de {$codigo}.")) break;
            }
            $idApu = $cel('ID APU');
            $cantidad = $this->numero($cel('CANTIDAD'));
            // Actividad: fila con ID APU no vacío (formato legado, señal fuerte a
            // cualquier nivel) o con CANTIDAD numérica en nivel >= 3 (formato real
            // de exportación AIA, donde ID APU siempre viene vacío). El guard de
            // nivel evita que un capítulo o subcapítulo con un total numérico
            // accidental en CANTIDAD se clasifique como actividad: en el
            // presupuesto real de DAPORTO los niveles 1-2 nunca traen CANTIDAD.
            $esActividad = $idApu !== '' || ($cantidad !== null && $nivel >= 3);
            $tipoFila = $esActividad ? 'actividad' : ($nivel === 1 ? 'capitulo' : ($nivel === 2 ? 'subcapitulo' : 'grupo'));
            if ($esActividad && $cantidad === null) {
                if (!$err($fila, 'CANTIDAD', "La actividad {$codigo} no tiene cantidad numérica.")) break;
                $cantidad = 0.0;
            }
            $conteo[$tipoFila]++;
            $porCodigo[$codigo] = true;
            $items[] = [
                'codigo' => mb_substr($codigo, 0, 50),
                'codigo_padre' => $codigoPadre,
                'nivel' => $nivel,
                'tipo_fila' => $tipoFila,
                'descripcion' => mb_substr($cel('DESCRIPCION'), 0, 500),
                'unidad' => $cel('UM') !== '' ? mb_substr($cel('UM'), 0, 20) : null,
                'cantidad' => $cantidad,
                'id_apu' => $idApu !== '' ? mb_substr($idApu, 0, 50) : null,
            ];
            if ($esActividad) {
                $actividadVigente = ['codigo' => $codigo, 'cantidad' => (float) $cantidad];
            }
        }

        if (count($errores) >= self::MAX_ERRORES) {
            $errores[] = ['fila' => 0, 'columna' => '', 'motivo' => 'Reporte truncado en ' . self::MAX_ERRORES . ' errores.'];
        }

        return [
            'valido' => $errores === [],
            'versionLabel' => $versionLabel,
            'resumen' => [
                'capitulos' => $conteo['capitulo'],
                'subcapitulos' => $conteo['subcapitulo'],
                'grupos' => $conteo['grupo'],
                'actividades' => $conteo['actividad'],
                'insumos' => count($insumos),
                'costoTotal' => round($costoTotal, 2),
            ],
            'items' => $items,
            'insumos' => $insumos,
            'errores' => $errores,
        ];
    }

    private function mapearEncabezados(array $headerRow): array
    {
        $mapa = [];
        foreach ($headerRow as $idx => $titulo) {
            $clave = $this->normalizar((string) $titulo);
            if ($clave !== '' && !isset($mapa[$clave])) {
                $mapa[$clave] = $idx;
            }
        }
        return $mapa;
    }

    private function normalizar(string $s): string
    {
        $s = mb_strtoupper(trim($s));
        return strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
    }

    private function numero(string $v): ?float
    {
        if ($v === '') {
            return null;
        }
        $v = str_replace([' ', '$'], '', $v);
        // Formato es-CO: coma decimal cuando no hay punto decimal claro
        if (str_contains($v, ',') && !str_contains($v, '.')) {
            $v = str_replace(',', '.', $v);
        } else {
            $v = str_replace(',', '', $v);
        }
        return is_numeric($v) ? (float) $v : null;
    }

    private function filaVacia(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
    }
}
