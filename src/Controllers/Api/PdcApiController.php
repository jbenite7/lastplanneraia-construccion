<?php

namespace App\Controllers\Api;

use App\Support\ModuleRequestContext;
use PDO;
use Throwable;

class PdcApiController
{
    private const ALLOWED_PROVIDER_TYPES = [
        'Mano de Obra',
        'Suministro e Instalación',
        'Suministro de Materiales, Herramientas o Equipos',
    ];

    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    // ─── LIST ───────────────────────────────────────────────────────────
    public function list(): void
    {
        $definirContratos = (int) ($_GET['definirContratos'] ?? 0);

        $condicionContratos = ($definirContratos == 1)
            ? "AND numeroSubcontratos IS NOT NULL AND titulo = 0 "
            : "";

        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];

            $this->requirePermission('lps.pdc.ver', 'No autorizado para consultar el plan de compras.');

            $stmt = $this->db->query(
                "SELECT COUNT(*) AS conteo FROM {$dbPrefix}_pdc WHERE semana = ? $condicionContratos",
                [$semana],
            );
            $conteo = (int) ($stmt->fetch()["conteo"] ?? 0);

            if ($conteo == 0) {
                $arreglo["data"][] = [
                    "boton" => "", "consecutivo" => "", "id" => "", "titulo" => "", "semana" => "",
                    "tipoPaquete" => "", "paqueteContratacion" => "", "contratos" => "", "numeroSubcontratos" => "",
                    "subcontratoPaquete" => "", "estado" => "", "fechaElaboracionPliegos" => "",
                    "diasElaboracionPliegos" => "", "fechaRealElaboracionPliegos" => "", "fechaEntregaPliegos" => "",
                    "diasEntregaPliegos" => "", "fechaRealEntregaPliegos" => "", "fechaReciboPropuestas" => "",
                    "diasEntregaPliegos" => "", "fechaRealEntregaPliegos" => "", "fechaReciboPropuestas" => "",
                    "diasReciboPropuestas" => "", "fechaRealReciboPropuestas" => "", "fechaCuadrosComparativos" => "",
                    "diasCuadrosComparativos" => "", "fechaRealCuadrosComparativos" => "", "fechaLegalizacionContrato" => "",
                    "diasLegalizacionContrato" => "", "fechaRealLegalizacionContrato" => "", "fechaFabricacion" => "",
                    "diasFabricacion" => "", "fechaRealFabricacion" => "", "fechaInsumosObra" => "",
                    "diasInsumosObra" => "", "fechaRealInsumosObra" => "", "fechaInicio" => "",
                    "fechaInicioProyectada" => "", "fechaRealInicio" => "", "idProveedorAdjudicado" => "",
                    "fechaVencimientoPolizas" => "", "observacionesContrato" => "", "ordenVisual" => "",
                    "diasDelta" => 0, "necesitaConfiguracion" => 0, "listoParaIniciar" => 0,
                ];
                $this->json($arreglo);
                return;
            }

            $stmt1 = $this->db->query(
                "SELECT * FROM {$dbPrefix}_pdc WHERE semana = ? $condicionContratos 
                 ORDER BY tipoPaquete DESC, titulo DESC, fechaElaboracionPliegos ASC, subcontratoPaquete ASC",
                [$semana],
            );
            $resultados = $stmt1->fetchAll();

            $stmtFecha = $this->db->query(
                "SELECT Fecha_Inicio_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ?",
                [$semana],
            );
            $dataFecha = $stmtFecha->fetch();
            $semanaSem = $dataFecha["Fecha_Inicio_Sem"] ?? date('Y-m-d');

            $esquemaSI = 0;
            $esquemaMO = 0;
            $esquemaS = 0;
            $ordenVisual = 0;
            $arreglo = ["data" => []];

            foreach ($resultados as $data) {
                $data["ordenVisual"] = $ordenVisual++;

                if ($data["titulo"] == 1) {
                    $data["estado"] = "Capítulo";
                }
                if ($data["titulo"] == 0) {
                    $data["procesoIniciado"] = (
                        is_null($data["fechaRealElaboracionPliegos"]) && is_null($data["fechaRealEntregaPliegos"])
                        && is_null($data["fechaRealEntregaPliegos"]) && is_null($data["fechaRealReciboPropuestas"])
                        && is_null($data["fechaRealCuadrosComparativos"]) && is_null($data["fechaRealLegalizacionContrato"])
                        && is_null($data["fechaRealFabricacion"]) && is_null($data["fechaRealInsumosObra"])
                        && is_null($data["fechaRealInicio"])
                    ) ? 0 : 1;

                    $camposDias = ["diasElaboracionPliegos", "diasEntregaPliegos",
                        "diasReciboPropuestas", "diasCuadrosComparativos", "diasLegalizacionContrato",
                        "diasFabricacion", "diasInsumosObra"];

                    $todosEnUno = true;
                    foreach ($camposDias as $campo) {
                        if ((int) ($data[$campo] ?? 1) !== 1) {
                            $todosEnUno = false;
                            break;
                        }
                    }
                    $data["necesitaConfiguracion"] = $todosEnUno ? 1 : 0;

                    foreach ($camposDias as $campo) {
                        if (is_null($data[$campo])) {
                            $data[$campo] = 1;
                        }
                    }

                    $data["listoParaIniciar"] = (
                        !empty($data['fechaRealInsumosObra'])
                        && !empty($data['fechaInicio'])
                        && $data['fechaRealInsumosObra'] <= $data['fechaInicio']
                    ) ? 1 : 0;

                    $deltaInfo = $this->calcularDiasDelta($data, $semanaSem);
                    $data["diasDelta"] = $deltaInfo['diasDelta'];
                }

                $tipoPaquete = $data["tipoPaquete"];
                if ($tipoPaquete == "Suministro e Instalación") {
                    $id = ($data["titulo"] == 1) ? 1 : "1." . (++$esquemaSI);
                } elseif ($tipoPaquete == "Mano de Obra") {
                    $id = ($data["titulo"] == 1) ? 3 : "3." . (++$esquemaMO);
                } else {
                    $id = ($data["titulo"] == 1) ? 2 : "2." . (++$esquemaS);
                }
                $data["id"] = $id;

                $arreglo["data"][] = $data;
            }

            $this->json($arreglo);

        } catch (Throwable $e) {
            error_log("Error en PdcApiController@list: " . $e->getMessage());
            $this->jsonError('No se pudo cargar el plan de compras.', 500, ["data" => []]);
        }
    }

    // ─── SAVE (switch central) ──────────────────────────────────────────
    public function save(): void
    {
        $opcion = $_POST['opcion'] ?? '';

        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];

            $this->requirePermission('lps.pdc.ver', 'No autorizado para consultar el plan de compras.');

            $isEditOperation = in_array($opcion, [
                'nueva_sem',
                'eliminar_sem',
                'eliminar_actividad_pdc',
                'guardar_actividad_pdc',
                'adjudicar_pdc',
                'modificar',
                'guardar_DefinirContratos',
                'adjudicar_contrato',
            ], true) || isset($_POST['columna']);

            if ($isEditOperation) {
                $this->requirePermission('lps.pdc.editar', 'No autorizado para modificar el plan de compras.');
            }

            switch ($opcion) {
                case "nueva_sem":
                    $f_inicio_sem = $_POST['f_inicio_sem'] ?? '';
                    $db = $dbPrefix;
                    require(PROJECT_ROOT . "/src/Legacy/nueva_semana.php");
                    break;

                case "eliminar_sem":
                    $db = $dbPrefix;
                    require(PROJECT_ROOT . "/src/Legacy/eliminar_semana.php");
                    break;

                case "eliminar_actividad_pdc":
                    $this->eliminarActividad($dbPrefix, $semana);
                    break;

                case "guardar_actividad_pdc":
                    $this->guardarActividad($dbPrefix, $semana);
                    break;

                case "adjudicar_pdc":
                    $this->adjudicarPdc($dbPrefix, $semana);
                    break;

                case "modificar":
                    $this->modificar($dbPrefix, $semana);
                    break;

                case "guardar_DefinirContratos":
                    $this->guardarDefinirContratos($dbPrefix, $semana);
                    break;

                case "adjudicar_contrato":
                    $this->adjudicarContrato($dbPrefix);
                    break;

                case "verificarProveedor":
                    $this->verificarProveedor($dbPrefix);
                    break;

                case "recalcularProcesoContratacion":
                    $this->recalcularProcesoContratacion();
                    break;

                default:
                    $this->handleCeldaDinamica($dbPrefix, $semana);
                    break;
            }
        } catch (Throwable $e) {
            error_log("Error en PdcApiController@save: " . $e->getMessage());
            $this->jsonError('No se pudo procesar la solicitud del plan de compras.', 500);
        }
    }

    // ─── OPERACIONES PRIVADAS ───────────────────────────────────────────

    private function eliminarActividad(string $p, int $semana): void
    {
        $id = $_POST['id'] ?? $_POST['Id'] ?? null;
        $justificacion = $_POST['justificacion'] ?? '';

        $stmtInfo = $this->db->query("SELECT * FROM {$p}_pdc WHERE consecutivo = ? AND semana = ?", [$id, $semana]);
        $info = $stmtInfo->fetch();

        if ($info) {
            $this->db->query("INSERT INTO {$p}_papelera_pdc SELECT * FROM {$p}_pdc WHERE consecutivo = ? AND semana = ?", [$id, $semana]);
            $this->db->query("UPDATE {$p}_papelera_pdc SET justificacionEliminacion = ? WHERE consecutivo = ? AND semana = ?", [$justificacion, $id, $semana]);
            $this->db->query("DELETE FROM {$p}_pdc WHERE consecutivo = ? AND semana = ?", [$id, $semana]);
            $this->db->logActivity('PDC', 'ELIMINAR_ACTIVIDAD', "Eliminó actividad PDC con ID $id", $p);
            $this->json(["respuesta" => "BIEN"]);
        } else {
            $this->json(["respuesta" => "ERROR", "mensaje" => "Registro no encontrado"]);
        }
    }

    private function guardarActividad(string $p, $semana): void
    {
        $tipoPaquete = $_POST['tipoPaquete'];
        $paqueteContratacion = $_POST['paqueteContratacion'];
        $contratos = $_POST['contratos'];

        $stmtMax = $this->db->query("SELECT MAX(subcontratoPaquete) as maxVal FROM {$p}_pdc WHERE semana = ? AND paqueteContratacion = ?", [$semana, $paqueteContratacion]);
        $subcontratoPaquete = ($stmtMax->fetch()['maxVal'] ?? 0) + 1;

        $estado = "Proceso de contratación no iniciado";

        $campos = ['fechaElaboracionPliegos','diasElaboracionPliegos','fechaEntregaPliegos','diasEntregaPliegos',
            'fechaEntregaPliegos','diasEntregaPliegos','fechaReciboPropuestas','diasReciboPropuestas',
            'fechaCuadrosComparativos','diasCuadrosComparativos','fechaLegalizacionContrato','diasLegalizacionContrato',
            'fechaFabricacion','diasFabricacion','fechaInsumosObra','diasInsumosObra','fechaInicio'];

        $vals = array_map(fn($c) => $this->checkNull($_POST[$c] ?? ''), $campos);

        $sql = "INSERT INTO {$p}_pdc (
            semana, titulo, tipoPaquete, paqueteContratacion, contratos, subcontratoPaquete, estado,
            fechaElaboracionPliegos, diasElaboracionPliegos, fechaEntregaPliegos, diasEntregaPliegos,
            fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas,
            fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato,
            fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, array_merge([$semana, 0, $tipoPaquete, $paqueteContratacion, $contratos, $subcontratoPaquete, $estado], $vals));
        $this->db->logActivity('PDC', 'CREAR_ACTIVIDAD', "Creó nueva actividad PDC: Paquete $paqueteContratacion", $p);
        $this->json(["respuesta" => "BIEN"]);
    }

    private function adjudicarPdc(string $p, int $semana): void
    {
        $id = $_POST['id'];
        $fields = ['idProveedorAdjudicado','numeroContrato','fechaVencimientoPolizas','valorPresupuesto',
            'valorPrimeraNegociacion','valorAdjudicado','valorAnticipo','valorReclamado',
            'valorDevoluciones','observacionesContrato'];
        $vals = array_map(fn($c) => $this->checkNull($_POST[$c] ?? ''), $fields);

        $sql = "UPDATE {$p}_pdc SET 
            idProveedorAdjudicado=?, numeroContrato=?, fechaVencimientoPolizas=?,
            valorPresupuesto=?, valorPrimeraNegociacion=?, valorAdjudicado=?,
            valorAnticipo=?, valorReclamado=?, valorDevoluciones=?, observacionesContrato=?
            WHERE consecutivo=? AND semana=?";
        $this->db->query($sql, array_merge($vals, [$id, $semana]));
        $this->db->logActivity('PDC', 'ADJUDICAR_PDC', "Adjudicó PDC consecutivo $id", $p);
        $this->json(["respuesta" => "BIEN"]);
    }

    private function modificar(string $p, int $semana): void
    {
        $id = $_POST['Id'] ?? 0;
        $idProv = $this->checkNull($_POST['idProveedorExistente'] ?? '');
        $numContrato = $this->checkNull($_POST['numeroContrato'] ?? '');
        $aplicaPolizas = $_POST['aplicaPolizas'] ?? 1;
        $fVencPolizas = ($aplicaPolizas == 1) ? $this->checkNull($_POST['fechaVencimientoPolizas'] ?? '') : null;

        $cleanCurrency = fn($k) => $this->checkNull(str_replace(['$', ','], '', $_POST[$k] ?? ''));
        $vPres = $cleanCurrency('valorPresupuesto');
        $vInit = $cleanCurrency('valorPrimeraNegociacion');
        $vAdj  = $cleanCurrency('valorAdjudicado');
        $vAnt  = $cleanCurrency('valorAnticipo');
        $vRec  = $cleanCurrency('valorReclamado');
        $vDev  = $cleanCurrency('valorDevoluciones');

        $cn = fn($k) => $this->checkNull($_POST[$k] ?? '');
        $dEl = $cn('diasElaboracionPliegos');
        $dIL = $cn('diasEntregaPliegos');
        $dEP = $cn('diasEntregaPliegos');
        $dRP = $cn('diasReciboPropuestas');
        $dCC = $cn('diasCuadrosComparativos');
        $dLC = $cn('diasLegalizacionContrato');
        $dF = $cn('diasFabricacion');
        $dIO = $cn('diasInsumosObra');

        $fREl = $cn('fechaRealElaboracionPliegos');
        $fRIL = $cn('fechaRealEntregaPliegos');
        $fREP = $cn('fechaRealEntregaPliegos');
        $fRRP = $cn('fechaRealReciboPropuestas');
        $fRCC = $cn('fechaRealCuadrosComparativos');
        $fRLC = $cn('fechaRealLegalizacionContrato');
        $fRF = $cn('fechaRealFabricacion');
        $fRIO = $cn('fechaRealInsumosObra');
        $fRIn = $cn('fechaRealInicioProyectadaContrato');

        $obs = $cn('observacionesContrato');
        $est = $_POST['estadoProceso'] ?? '';

        $sql = "UPDATE {$p}_pdc SET 
            idProveedorAdjudicado=?, numeroContrato=?, aplicaPolizas=?, fechaVencimientoPolizas=?,
            valorPresupuesto=?, valorPrimeraNegociacion=?, valorAdjudicado=?, valorAnticipo=?,
            valorReclamado=?, valorDevoluciones=?, 
            diasElaboracionPliegos=?, diasEntregaPliegos=?, diasReciboPropuestas=?,
            diasCuadrosComparativos=?, diasLegalizacionContrato=?, diasFabricacion=?, diasInsumosObra=?,
            fechaRealElaboracionPliegos=?, fechaRealEntregaPliegos=?, 
            fechaRealReciboPropuestas=?, fechaRealCuadrosComparativos=?, fechaRealLegalizacionContrato=?,
            fechaRealFabricacion=?, fechaRealInsumosObra=?, fechaRealInicio=?,
            observacionesContrato=?, estado=?
            WHERE consecutivo=? AND semana=?";

        $this->db->query($sql, [
            $idProv, $numContrato, $aplicaPolizas, $fVencPolizas,
            $vPres, $vInit, $vAdj, $vAnt, $vRec, $vDev,
            $dEl, $dIL, $dEP, $dRP, $dCC, $dLC, $dF, $dIO,
            $fREl, $fRIL, $fREP, $fRRP, $fRCC, $fRLC, $fRF, $fRIO, $fRIn,
            $obs, $est, $id, $semana,
        ]);
        $this->db->logActivity('PDC', 'MODIFICAR_ACTIVIDAD', "Modificó paquete PDC consecutivo $id", $p);
        echo json_encode("OK");
    }

    private function guardarDefinirContratos(string $p, int $semana): void
    {
        $json = $_POST['numeroSubcontratos'] ?? '';
        $data = json_decode($json, true);
        if (isset($data['numeroSubcontratos'])) {
            foreach ($data['numeroSubcontratos'] as $item) {
                $this->db->query("UPDATE {$p}_pdc SET numeroSubcontratos = ? WHERE consecutivo = ? AND semana = ?", [$item['numeroSubcontratos'], $item['consecutivo'], $semana]);
            }
            echo json_encode("conModificaciones");
        } else {
            echo json_encode("sinModificaciones");
        }
    }

    private function adjudicarContrato(string $p): void
    {
        $idProv = (int) ($_POST['idProveedorAdjudicado'] ?? 0);
        $proveedor = $this->sanitizarProveedorPdc([
            'subcontratista' => $_POST['Subcontratista'] ?? '',
            'correo_contacto' => $_POST['email'] ?? '',
            'NIT' => $_POST['NIT'] ?? '',
            'alcance' => $_POST['alcance'] ?? '',
            'tipo_proveedor' => $_POST['tipo_proveedor'] ?? '',
        ]);

        $errores = $this->validarProveedorPdc($p, $proveedor, $idProv > 0 ? $idProv : null);
        if (!empty($errores)) {
            $this->json(["respuesta" => "ERROR", "mensaje" => implode("\n", $errores), "errores" => $errores]);
            return;
        }

        if ($idProv != 0 && $idProv != "0") {
            $stmtOld = $this->db->query("SELECT subcontratista FROM {$p}_subcontratistas WHERE id=?", [$idProv]);
            $oldName = ($stmtOld->fetch())['subcontratista'] ?? '';

            $this->db->query(
                "UPDATE {$p}_subcontratistas SET subcontratista=?, correo_contacto=?, NIT=?, alcance=?, tipo_proveedor=? WHERE id=?",
                [
                    $proveedor['subcontratista'],
                    $proveedor['correo_contacto'],
                    $proveedor['NIT'],
                    $proveedor['alcance'],
                    $proveedor['tipo_proveedor'],
                    $idProv,
                ],
            );

            if ($oldName && $this->normalizarTextoPdc($oldName) !== $this->normalizarTextoPdc($proveedor['subcontratista'])) {
                $tables = [
                    "{$p}_programacion_semanal" => "Sub_Contratista",
                    "{$p}_programa_consolidado" => "Sub_Contratista",
                    "{$p}_cic" => "subcontratista",
                    "{$p}_indicadores_generales" => "subcontratista_profesional",
                ];
                foreach ($tables as $tbl => $col) {
                    $this->db->query("UPDATE $tbl SET $col = ? WHERE $col = ?", [$proveedor['subcontratista'], $oldName]);
                }
            }
            $this->json(["respuesta" => "BIEN", "idProveedor" => $idProv]);
        } else {
            $this->db->query(
                "INSERT INTO {$p}_subcontratistas (subcontratista, correo_contacto, NIT, alcance, tipo_proveedor) VALUES (?,?,?,?,?)",
                [
                    $proveedor['subcontratista'],
                    $proveedor['correo_contacto'],
                    $proveedor['NIT'],
                    $proveedor['alcance'],
                    $proveedor['tipo_proveedor'],
                ],
            );
            $this->json(["respuesta" => "BIEN", "idProveedor" => $this->db->lastInsertId()]);
        }
    }

    private function verificarProveedor(string $p): void
    {
        $base = $_POST['base'] ?? '';
        if ($base == 'idProveedorExistente') {
            $param = $_POST['idProveedorExistente'] ?? 0;
            $query = "SELECT * FROM {$p}_subcontratistas WHERE Id = ?";
        } else {
            $param = $_POST['nitAdjudicado'] ?? '';
            $query = "SELECT * FROM {$p}_subcontratistas WHERE NIT = ?";
        }
        $row = $this->db->query($query, [$param])->fetch();
        echo json_encode($row ? ["data" => [$row]] : "No Existe");
    }

    private function recalcularProcesoContratacion(): void
    {
        $fechaInicio = $_POST['fechaInicioContrato'] ?? '';
        if (!$fechaInicio) {
            $this->json(["data" => [[]]]);
            return;
        }

        $dIO = (int) ($_POST['diasInsumosObra'] ?? 0);
        $dF = (int) ($_POST['diasFabricacion'] ?? 0);
        $dLC = (int) ($_POST['diasLegalizacionContrato'] ?? 0);
        $dCC = (int) ($_POST['diasCuadrosComparativos'] ?? 0);
        $dRP = (int) ($_POST['diasReciboPropuestas'] ?? 0);
        $dEP = (int) ($_POST['diasEntregaPliegos'] ?? 0);
        $dIL = (int) ($_POST['diasEntregaPliegos'] ?? 0);
        $dEl = (int) ($_POST['diasElaboracionPliegos'] ?? 0);

        $fIO = date('Y-m-d', strtotime("$fechaInicio - $dIO days"));
        $fF  = date('Y-m-d', strtotime("$fIO - $dF days"));
        $fLC = date('Y-m-d', strtotime("$fF - $dLC days"));
        $fCC = date('Y-m-d', strtotime("$fLC - $dCC days"));
        $fRP = date('Y-m-d', strtotime("$fCC - $dRP days"));
        $fEP = date('Y-m-d', strtotime("$fRP - $dEP days"));
        $fIL = date('Y-m-d', strtotime("$fEP - $dIL days"));
        $fEl = date('Y-m-d', strtotime("$fIL - $dEl days"));

        $this->json(["data" => [[
            "fechaInsumosObra" => $fIO, "fechaFabricacion" => $fF,
            "fechaLegalizacionContrato" => $fLC, "fechaCuadrosComparativos" => $fCC,
            "fechaReciboPropuestas" => $fRP, "fechaEntregaPliegos" => $fEP,
            "fechaEntregaPliegos" => $fIL, "fechaElaboracionPliegos" => $fEl,
            "fechaInicioProyectada" => $fechaInicio,
        ]]]);
    }

    private function handleCeldaDinamica(string $p, int $semana): void
    {
        if (isset($_POST['columna'])) {
            $columna = $_POST['columna'];
            $valor = $_POST['valor'];
            $id = $_POST['id'];
            if (preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
                $this->db->query("UPDATE {$p}_pdc SET $columna = ? WHERE consecutivo = ? AND semana = ?", [$this->checkNull($valor), $id, $semana]);
                $this->db->logActivity('PDC', 'MODIFICAR_CELDA', "Actualizó columna $columna de PDC $id", $p);
                $this->json(["respuesta" => "BIEN"]);
            } else {
                $this->json(["respuesta" => "ERROR", "mensaje" => "Columna inválida"]);
            }
        } else {
            $this->json(["respuesta" => "ERROR", "mensaje" => "Opción no válida"]);
        }
    }

    // ─── HELPERS ────────────────────────────────────────────────────────
    private function checkNull($val)
    {
        return ($val === "NULL" || $val === "") ? null : $val;
    }

    private function sanitizarProveedorPdc(array $data): array
    {
        return [
            'subcontratista' => $this->limpiarTextoPdc($data['subcontratista'] ?? ''),
            'correo_contacto' => $this->normalizarEmailPdc($data['correo_contacto'] ?? ''),
            'NIT' => $this->limpiarTextoPdc($data['NIT'] ?? ''),
            'alcance' => $this->limpiarTextoPdc($data['alcance'] ?? ''),
            'tipo_proveedor' => $this->normalizarTipoProveedorPdc($data['tipo_proveedor'] ?? ''),
        ];
    }

    private function validarProveedorPdc(string $dbPrefix, array $data, ?int $excludeId = null): array
    {
        $errores = [];

        if ($data['subcontratista'] === '') {
            $errores[] = 'El nombre del subcontratista es obligatorio.';
        }

        if ($data['correo_contacto'] === '') {
            $errores[] = 'El correo de contacto es obligatorio.';
        } elseif (!filter_var($data['correo_contacto'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo de contacto no tiene un formato válido.';
        }

        if ($data['NIT'] === '') {
            $errores[] = 'El NIT es obligatorio.';
        }

        if ($data['alcance'] === '') {
            $errores[] = 'El alcance es obligatorio.';
        }

        if ($data['tipo_proveedor'] === '') {
            $errores[] = 'El tipo de proveedor es obligatorio.';
        } elseif (!in_array($data['tipo_proveedor'], self::ALLOWED_PROVIDER_TYPES, true)) {
            $errores[] = 'El tipo de proveedor seleccionado no es válido.';
        }

        foreach ($this->buscarDuplicadosProveedorPdc($dbPrefix, $data, $excludeId) as $error) {
            $errores[] = $error;
        }

        return array_values(array_unique($errores));
    }

    private function buscarDuplicadosProveedorPdc(string $dbPrefix, array $data, ?int $excludeId = null): array
    {
        $errores = [];
        $params = [];
        $sql = "SELECT Id, subcontratista, correo_contacto, NIT FROM {$dbPrefix}_subcontratistas";

        if ($excludeId !== null) {
            $sql .= ' WHERE Id != ?';
            $params[] = $excludeId;
        }

        $rows = $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        $nombreNormalizado = $this->normalizarTextoPdc($data['subcontratista']);
        $correoNormalizado = $this->normalizarEmailPdc($data['correo_contacto']);
        $nitNormalizado = $this->normalizarNitPdc($data['NIT']);

        foreach ($rows as $row) {
            if ($nombreNormalizado !== '' && $this->normalizarTextoPdc($row['subcontratista'] ?? '') === $nombreNormalizado) {
                $errores[] = 'Ya existe un subcontratista con ese nombre.';
            }
            if ($correoNormalizado !== '' && $this->normalizarEmailPdc($row['correo_contacto'] ?? '') === $correoNormalizado) {
                $errores[] = 'Ya existe un subcontratista con ese correo.';
            }
            if ($nitNormalizado !== '' && $this->normalizarNitPdc($row['NIT'] ?? '') === $nitNormalizado) {
                $errores[] = 'Ya existe un subcontratista con ese NIT.';
            }
        }

        return array_values(array_unique($errores));
    }

    private function limpiarTextoPdc($valor): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $valor)) ?? '';
    }

    private function normalizarTextoPdc($valor): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($this->limpiarTextoPdc($valor), 'UTF-8')
            : strtolower($this->limpiarTextoPdc($valor));
    }

    private function normalizarEmailPdc($valor): string
    {
        $email = trim((string) $valor);
        return function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
    }

    private function normalizarNitPdc($valor): string
    {
        $nit = trim((string) $valor);
        return preg_replace('/[^a-zA-Z0-9]/', '', $nit) ?? '';
    }

    private function normalizarTipoProveedorPdc($valor): string
    {
        $tipo = $this->limpiarTextoPdc($valor);
        if ($tipo === '1') {
            return 'Mano de Obra';
        }
        if ($tipo === '2') {
            return 'Suministro e Instalación';
        }
        return $tipo;
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $message, int $httpCode = 400, array $extra = []): void
    {
        http_response_code($httpCode);
        $this->json(array_merge([
            'respuesta' => 'ERROR',
            'mensaje' => $message,
        ], $extra));
    }

    private function requirePermission(string $permissionKey, string $message): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission($permissionKey, ['message' => $message]);
    }

    private function calcularDiasDelta(array $data, string $fechaSemana): array
    {
        $fechaInicio = $data['fechaInicio'] ?? '';
        $fechaActual = date('Y-m-d');

        $duraciones = [
            (int) ($data['diasElaboracionPliegos'] ?? 0),
            (int) ($data['diasEntregaPliegos'] ?? 0),
            (int) ($data['diasEntregaPliegos'] ?? 0),
            (int) ($data['diasReciboPropuestas'] ?? 0),
            (int) ($data['diasCuadrosComparativos'] ?? 0),
            (int) ($data['diasLegalizacionContrato'] ?? 0),
            (int) ($data['diasFabricacion'] ?? 0),
            (int) ($data['diasInsumosObra'] ?? 0),
        ];

        $fechaRealFields = [
            'fechaRealElaboracionPliegos', 'fechaRealEntregaPliegos',
            'fechaRealReciboPropuestas', 'fechaRealCuadrosComparativos', 'fechaRealLegalizacionContrato',
            'fechaRealFabricacion', 'fechaRealInsumosObra', 'fechaRealInicio',
        ];

        $totalDias = array_sum($duraciones);

        $fechasTeoricas = [];
        $acumulado = $totalDias;
        foreach ($duraciones as $i => $duracion) {
            $fechasTeoricas[$i] = date('Y-m-d', strtotime("$fechaInicio - $acumulado days"));
            $acumulado -= $duracion;
        }
        $fechasTeoricas[8] = $fechaInicio;

        $posicion = -1;
        foreach ($fechaRealFields as $i => $campo) {
            if (!empty($data[$campo])) {
                $posicion = $i;
            }
        }

        $deberiaHoy = -1;
        foreach ($fechasTeoricas as $i => $fecha) {
            if ($fecha <= $fechaActual) {
                $deberiaHoy = $i;
            }
        }

        $diasDelta = 0;
        if ($posicion === -1 && $deberiaHoy >= 0) {
            for ($i = 0; $i <= $deberiaHoy && $i <= 8; $i++) {
                $diasDelta += ($i < 8) ? $duraciones[$i] : 0;
            }
            $diasDelta = -$diasDelta;
        } elseif ($posicion >= 0 && $deberiaHoy >= 0) {
            if ($posicion < $deberiaHoy) {
                for ($i = $posicion + 1; $i <= $deberiaHoy && $i <= 8; $i++) {
                    $diasDelta += ($i < 8) ? $duraciones[$i] : 0;
                }
                $diasDelta = -$diasDelta;
            } elseif ($posicion > $deberiaHoy) {
                for ($i = $deberiaHoy + 1; $i <= $posicion && $i <= 8; $i++) {
                    $diasDelta += ($i < 8) ? $duraciones[$i] : 0;
                }
            }
        }

        return [
            'posicion' => $posicion,
            'deberiaHoy' => $deberiaHoy,
            'diasDelta' => $diasDelta,
        ];
    }
}
