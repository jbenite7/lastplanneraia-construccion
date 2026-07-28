<?php

namespace App\Services\Pdc;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parser de la hoja "Maestro Insumos" del export SINCO (PDC v2 / Fase A2.5).
 * Encabezados por nombre; solo insumos ACTIVO; validación todo-o-nada con
 * reporte por fila (tope 200). La normalización es la canónica del maestro.
 *
 * `valorUnitario` no es nullable aunque `numero()` sí lo sea: la fila que no lo trae numérico se
 * descarta antes de llegar al insumo. `iva` sí puede faltar, es una columna opcional.
 *
 * @phpstan-type SincoInsumo array{
 *     codigoSinco: string,
 *     descripcion: string,
 *     descripcionNorm: string,
 *     unidad: string,
 *     tipoInsumo: string,
 *     agrupacion: string,
 *     tipoRecurso: string,
 *     valorUnitario: float,
 *     iva: float|null
 * }
 * @phpstan-type SincoErrorFila array{fila: int, columna: string, motivo: string}
 * @phpstan-type SincoResumen array{
 *     total: int,
 *     activos: int,
 *     omitidos: int,
 *     agrupaciones: int,
 *     tiposRecurso: int
 * }
 */
final class MaestroSincoParser
{
    public const SHEET = 'Maestro Insumos';
    public const MAX_ERRORES = 200;

    private const REQUERIDAS = ['CODIGO INSUMO', 'INSUMO DESCRIPCION', 'UNIDAD', 'TIPO DESCRIPCION', 'AGRUPACION DESCRIPCION', 'ESTADO', 'VALOR UNITARIO'];

    /**
     * @return array{
     *     valido: bool,
     *     insumos: list<SincoInsumo>,
     *     resumen: SincoResumen,
     *     errores: list<SincoErrorFila>
     * }
     *
     * @throws \RuntimeException si falta la hoja, está vacía o le faltan columnas requeridas
     */
    public function parse(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
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

        $insumos = [];
        $errores = [];
        $omitidos = 0;
        $activos = 0;
        $total = 0;
        $agrup = [];
        $tipos = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }
            if ($this->filaVacia($row)) {
                continue;
            }
            $total++;
            $fila = $i + 1;
            $cel = function (string $k) use ($row, $mapa): string {
                $idx = $mapa[$k] ?? null;
                return $idx === null ? '' : trim((string) ($row[$idx] ?? ''));
            };

            $estado = mb_strtoupper($cel('ESTADO'));
            if ($estado !== 'ACTIVO') {
                $omitidos++;
                continue;
            }
            $activos++;

            $codigo = $cel('CODIGO INSUMO');
            $descripcion = $cel('INSUMO DESCRIPCION');
            $unidad = $cel('UNIDAD');
            $valor = $this->numero($cel('VALOR UNITARIO'));
            $err = function (string $col, string $motivo) use (&$errores, $fila): bool {
                if (count($errores) >= self::MAX_ERRORES) {
                    return false;
                }
                $errores[] = ['fila' => $fila, 'columna' => $col, 'motivo' => $motivo];
                return true;
            };

            $filaValida = true;
            if ($codigo === '') { $filaValida = false; $err('Codigo Insumo', 'Insumo activo sin código SINCO.'); }
            if ($descripcion === '') { $filaValida = false; $err('Insumo Descripcion', 'Insumo activo sin descripción.'); }
            if ($unidad === '') { $filaValida = false; $err('Unidad', 'Insumo activo sin unidad.'); }
            if ($valor === null) { $filaValida = false; $err('Valor Unitario', 'Valor unitario no numérico.'); }
            if (!$filaValida) {
                continue;
            }

            $agrupacion = mb_substr($cel('AGRUPACION DESCRIPCION'), 0, 150);
            $tipoRecurso = mb_substr($cel('TIPO DESCRIPCION'), 0, 60);
            if ($agrupacion !== '') { $agrup[$agrupacion] = true; }
            if ($tipoRecurso !== '') { $tipos[$tipoRecurso] = true; }

            $insumos[] = [
                'codigoSinco' => mb_substr($codigo, 0, 50),
                'descripcion' => mb_substr($descripcion, 0, 500),
                'descripcionNorm' => mb_substr(MaestroInsumosService::normalizar($descripcion), 0, 500),
                'unidad' => mb_substr($unidad, 0, 20),
                // El maestro de A2 usa `tipo_insumo`; para insumos SINCO lo alineamos a la
                // Agrupación (lo que los presupuestos llaman "Tipo Insumo"). `tipoRecurso` va aparte.
                'tipoInsumo' => $agrupacion,
                'agrupacion' => $agrupacion,
                'tipoRecurso' => $tipoRecurso,
                'valorUnitario' => $valor,
                'iva' => $this->numero($cel('PORCENTAJE IVA')),
            ];
        }

        if (count($errores) >= self::MAX_ERRORES) {
            $errores[] = ['fila' => 0, 'columna' => '', 'motivo' => 'Reporte truncado en ' . self::MAX_ERRORES . ' errores.'];
        }

        return [
            'valido' => $errores === [],
            'insumos' => $insumos,
            'resumen' => [
                'total' => $total,
                'activos' => $activos,
                'omitidos' => $omitidos,
                'agrupaciones' => count($agrup),
                'tiposRecurso' => count($tipos),
            ],
            'errores' => $errores,
        ];
    }

    /**
     * @param array<int, mixed> $headerRow la fila 0 tal cual la entrega PhpSpreadsheet
     *
     * @return array<string, int> título normalizado → índice de columna; ante títulos repetidos
     *                            gana el primero
     */
    private function mapearEncabezados(array $headerRow): array
    {
        $mapa = [];
        foreach ($headerRow as $idx => $titulo) {
            $clave = MaestroInsumosService::normalizar((string) $titulo);
            if ($clave !== '' && !isset($mapa[$clave])) {
                $mapa[$clave] = $idx;
            }
        }
        return $mapa;
    }

    private function numero(string $v): ?float
    {
        if ($v === '') {
            return null;
        }
        $v = str_replace([' ', '$'], '', $v);
        if (str_contains($v, ',') && !str_contains($v, '.')) {
            $v = str_replace(',', '.', $v);
        } else {
            $v = str_replace(',', '', $v);
        }
        return is_numeric($v) ? (float) $v : null;
    }

    /**
     * @param array<int, mixed> $row
     */
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
