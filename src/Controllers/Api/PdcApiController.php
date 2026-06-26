<?php

namespace App\Controllers\Api;

use App\Support\ModuleRequestContext;
use PDO;
use Throwable;

use TableResolver;
class PdcApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Returns allowed provider types based on the current project area.
     */
    private function getAllowedProviderTypes(): array
    {
        $area = $_SESSION['area'] ?? $_GET['area'] ?? 'Construccion';

        if ($area === 'Pre-Construccion') {
            return [
                'Socio',
                'Ventas',
                'Gerencia',
                'Diseñador',
                'Consultor',
                'Entidad',
                'Interventoría',
                'Cliente',
                'Inversionista',
                'Promotor',
            ];
        }

        return [
            'Mano de Obra',
            'Suministro e Instalación',
            'Suministro de Materiales, Herramientas o Equipos',
        ];
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

            $stmt = $this->db->queryWithProject(
                "SELECT COUNT(*) AS conteo FROM " . TableResolver::resolveByPrefix($dbPrefix, 'pdc') . " WHERE semana = ? $condicionContratos",
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

            $stmt1 = $this->db->queryWithProject(
                "SELECT * FROM " . TableResolver::resolveByPrefix($dbPrefix, 'pdc') . " WHERE semana = ? $condicionContratos 
                 ORDER BY tipoPaquete DESC, titulo DESC, fechaElaboracionPliegos ASC, subcontratoPaquete ASC",
                [$semana],
            );
            $resultados = $stmt1->fetchAll();

            $stmtFecha = $this->db->queryWithProject(
                "SELECT Fecha_Inicio_Sem FROM " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . " WHERE Semana = ?",
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

        $stmtInfo = $this->db->queryWithProject("SELECT * FROM " . TableResolver::resolveByPrefix($p, 'pdc') . " WHERE consecutivo = ? AND semana = ?", [$id, $semana]);
        $info = $stmtInfo->fetch();

        if ($info) {
            $this->db->queryWithProject("INSERT INTO " . TableResolver::resolveByPrefix($p, 'papelera_pdc') . " SELECT * FROM " . TableResolver::resolveByPrefix($p, 'pdc') . " WHERE consecutivo = ? AND semana = ?", [$id, $semana]);
            $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($p, 'papelera_pdc') . " SET justificacionEliminacion = ? WHERE consecutivo = ? AND semana = ?", [$justificacion, $id, $semana]);
            $this->db->queryWithProject("DELETE FROM " . TableResolver::resolveByPrefix($p, 'pdc') . " WHERE consecutivo = ? AND semana = ?", [$id, $semana]);
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

        $stmtMax = $this->db->queryWithProject("SELECT MAX(subcontratoPaquete) as maxVal FROM " . TableResolver::resolveByPrefix($p, 'pdc') . " WHERE semana = ? AND paqueteContratacion = ?", [$semana, $paqueteContratacion]);
        $subcontratoPaquete = ($stmtMax->fetch()['maxVal'] ?? 0) + 1;

        $estado = "Proceso de contratación no iniciado";

        $campos = ['fechaElaboracionPliegos','diasElaboracionPliegos','fechaEntregaPliegos','diasEntregaPliegos',
            'fechaEntregaPliegos','diasEntregaPliegos','fechaReciboPropuestas','diasReciboPropuestas',
            'fechaCuadrosComparativos','diasCuadrosComparativos','fechaLegalizacionContrato','diasLegalizacionContrato',
            'fechaFabricacion','diasFabricacion','fechaInsumosObra','diasInsumosObra','fechaInicio'];

        $vals = array_map(fn($c) => $this->checkNull($_POST[$c] ?? ''), $campos);

        $sql = "INSERT INTO " . TableResolver::resolveByPrefix($p, 'pdc') . " (
            semana, titulo, tipoPaquete, paqueteContratacion, contratos, subcontratoPaquete, estado,
            fechaElaboracionPliegos, diasElaboracionPliegos, fechaEntregaPliegos, diasEntregaPliegos,
            fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas,
            fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato,
            fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->queryWithProject($sql, array_merge([$semana, 0, $tipoPaquete, $paqueteContratacion, $contratos, $subcontratoPaquete, $estado], $vals));
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

        $sql = "UPDATE " . TableResolver::resolveByPrefix($p, 'pdc') . " SET 
            idProveedorAdjudicado=?, numeroContrato=?, fechaVencimientoPolizas=?,
            valorPresupuesto=?, valorPrimeraNegociacion=?, valorAdjudicado=?,
            valorAnticipo=?, valorReclamado=?, valorDevoluciones=?, observacionesContrato=?
            WHERE consecutivo=? AND semana=?";
        $this->db->queryWithProject($sql, array_merge($vals, [$id, $semana]));
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

        $sql = "UPDATE " . TableResolver::resolveByPrefix($p, 'pdc') . " SET 
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

        $this->db->queryWithProject($sql, [
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
                $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($p, 'pdc') . " SET numeroSubcontratos = ? WHERE consecutivo = ? AND semana = ?", [$item['numeroSubcontratos'], $item['consecutivo'], $semana]);
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
            $stmtOld = $this->db->queryWithProject("SELECT subcontratista FROM " . TableResolver::resolveByPrefix($p, 'subcontratistas') . " WHERE id=?", [$idProv]);
            $oldName = ($stmtOld->fetch())['subcontratista'] ?? '';

            $this->db->queryWithProject(
                "UPDATE " . TableResolver::resolveByPrefix($p, 'subcontratistas') . " SET subcontratista=?, correo_contacto=?, NIT=?, alcance=?, tipo_proveedor=? WHERE id=?",
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
                    "" . TableResolver::resolveByPrefix($p, 'programacion_semanal') . "" => "Sub_Contratista",
                    "" . TableResolver::resolveByPrefix($p, 'programa_consolidado') . "" => "Sub_Contratista",
                    "" . TableResolver::resolveByPrefix($p, 'cic') . "" => "subcontratista",
                    "" . TableResolver::resolveByPrefix($p, 'indicadores_generales') . "" => "subcontratista_profesional",
                ];
                foreach ($tables as $tbl => $col) {
                    $this->db->queryWithProject("UPDATE $tbl SET $col = ? WHERE $col = ?", [$proveedor['subcontratista'], $oldName]);
                }
            }
            $this->json(["respuesta" => "BIEN", "idProveedor" => $idProv]);
        } else {
            $this->db->queryWithProject(
                "INSERT INTO " . TableResolver::resolveByPrefix($p, 'subcontratistas') . " (subcontratista, correo_contacto, NIT, alcance, tipo_proveedor) VALUES (?,?,?,?,?)",
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
            $query = "SELECT * FROM " . TableResolver::resolveByPrefix($p, 'subcontratistas') . " WHERE Id = ?";
        } else {
            $param = $_POST['nitAdjudicado'] ?? '';
            $query = "SELECT * FROM " . TableResolver::resolveByPrefix($p, 'subcontratistas') . " WHERE NIT = ?";
        }
        $row = $this->db->queryWithProject($query, [$param])->fetch();
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
                $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($p, 'pdc') . " SET $columna = ? WHERE consecutivo = ? AND semana = ?", [$this->checkNull($valor), $id, $semana]);
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
        } elseif (!in_array($data['tipo_proveedor'], $this->getAllowedProviderTypes(), true)) {
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
        $sql = "SELECT Id, subcontratista, correo_contacto, NIT FROM " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . "";

        if ($excludeId !== null) {
            $sql .= ' WHERE Id != ?';
            $params[] = $excludeId;
        }

        $rows = $this->db->queryWithProject($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
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

    // ─── DURACION SUGERIDA ──────────────────────────────────────────────
    public function duracionSugerida(): void
    {
        try {
            $this->requirePermission('lps.pdc.ver', 'No autorizado para consultar duraciones.');

            $paquete = trim((string) ($_GET['paquete'] ?? ''));
            $tipoPaquete = trim((string) ($_GET['tipoPaquete'] ?? ''));
            $categoria = trim((string) ($_GET['categoria'] ?? ''));

            if ($paquete === '') {
                $this->jsonError('El parámetro "paquete" es requerido.');
                return;
            }

            // Nivel 1: Catálogo existente (general_dias_procesos_contratacion)
            $catalog = $this->findCatalogDuration($paquete, $tipoPaquete);
            if ($catalog !== null) {
                $this->json([
                    'respuesta' => 'BIEN',
                    'fuente' => 'catalogo',
                    'paquete' => $paquete,
                    'duracion' => $catalog,
                ]);
                return;
            }

            // Nivel 2: Mediana histórica de {db}_pdc de todos los proyectos
            $median = $this->calculateHistoricalMedian($paquete);
            if ($median !== null) {
                $this->json([
                    'respuesta' => 'BIEN',
                    'fuente' => 'historico',
                    'paquete' => $paquete,
                    'duracion' => $median,
                ]);
                return;
            }

            // Nivel 3: Defaults por categoría
            $defaults = $this->getCategoryDefaults($categoria);
            $this->json([
                'respuesta' => 'BIEN',
                'fuente' => 'default',
                'paquete' => $paquete,
                'duracion' => $defaults,
            ]);
        } catch (Throwable $e) {
            error_log('Error en PdcApiController@duracionSugerida: ' . $e->getMessage());
            $this->jsonError('No se pudo obtener la duración sugerida.', 500);
        }
    }

    private function findCatalogDuration(string $paquete, string $tipoPaquete): ?array
    {
        $params = [$paquete];
        $sql = "SELECT diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
                       diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra
                FROM general_dias_procesos_contratacion
                WHERE paqueteContratacion = ?";

        if ($tipoPaquete !== '') {
            $sql .= " AND tipoPaquete = ?";
            $params[] = $tipoPaquete;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->queryWithProject($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        // Solo devolver si tiene duraciones reales (no todas 1)
        $dias = [
            'dias_elaboracion' => (int) $row['diasElaboracionPliegos'],
            'dias_entrega' => (int) $row['diasEntregaPliegos'],
            'dias_recibo' => (int) $row['diasReciboPropuestas'],
            'dias_cuadros' => (int) $row['diasCuadrosComparativos'],
            'dias_legalizacion' => (int) $row['diasLegalizacionContrato'],
            'dias_fabricacion' => (int) $row['diasFabricacion'],
            'dias_insumos' => (int) $row['diasInsumosObra'],
        ];

        $nonDefault = count(array_filter($dias, static fn($v) => $v > 1));
        return $nonDefault >= 2 ? $dias : null;
    }

    private function calculateHistoricalMedian(string $paquete): ?array
    {
        // Buscar paquetes con nombre similar en todas las tablas PDC de proyectos
        $stmt = $this->db->queryWithProject(
            "SELECT TABLE_NAME FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name LIKE '%_pdc'"
        );
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tables)) {
            return null;
        }

        $allDurations = [];
        foreach ($tables as $table) {
            try {
                $stmt = $this->db->queryWithProject(
                    "SELECT diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
                            diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra
                     FROM `{$table}`
                     WHERE titulo = 0 AND paqueteContratacion = ?
                       AND diasElaboracionPliegos IS NOT NULL AND diasElaboracionPliegos > 1",
                    [$paquete]
                );
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $allDurations[] = $row;
                }
            } catch (Throwable $e) {
                // Tabla sin columnas de duración (ej: general_curvas_pdc), skip
                continue;
            }
        }

        if (count($allDurations) < 1) {
            return null;
        }

        // Calcular medianas por campo
        $fields = [
            'dias_elaboracion' => 'diasElaboracionPliegos',
            'dias_entrega' => 'diasEntregaPliegos',
            'dias_recibo' => 'diasReciboPropuestas',
            'dias_cuadros' => 'diasCuadrosComparativos',
            'dias_legalizacion' => 'diasLegalizacionContrato',
            'dias_fabricacion' => 'diasFabricacion',
            'dias_insumos' => 'diasInsumosObra',
        ];

        $result = [];
        foreach ($fields as $key => $col) {
            $values = array_map(static fn($r) => (int) $r[$col], $allDurations);
            sort($values);
            $count = count($values);
            $mid = (int) floor(($count - 1) / 2);
            $result[$key] = ($count % 2 !== 0)
                ? $values[$mid]
                : (int) round(($values[$mid] + $values[$mid + 1]) / 2);
        }

        return $result;
    }

    private function getCategoryDefaults(string $categoria): array
    {
        // Mapear categorías de familias a categorías de defaults
        $categoryMap = [
            'PRELIMINARES' => 'PRELIMINARES',
            'CIMENTACION' => 'CIMENTACION',
            'ESTRUCTURA' => 'ESTRUCTURA',
            'MAMPOSTERIA' => 'MAMPOSTERIA',
            'ACABADOS' => 'ACABADOS',
            'INSTALACIONES' => 'INSTALACIONES',
            'URBANISMO' => 'URBANISMO',
            'MANO DE OBRA' => 'MANO DE OBRA',
            'EQUIPOS' => 'EQUIPOS',
            'INSUMOS' => 'INSUMOS',
        ];

        $dbCategory = $categoryMap[strtoupper($categoria)] ?? null;

        if ($dbCategory !== null) {
            $stmt = $this->db->queryWithProject(
                "SELECT dias_elaboracion, dias_entrega, dias_recibo, dias_cuadros,
                        dias_legalizacion, dias_fabricacion, dias_insumos
                 FROM general_dias_defaults_categoria WHERE categoria = ?",
                [$dbCategory]
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                return [
                    'dias_elaboracion' => (int) $row['dias_elaboracion'],
                    'dias_entrega' => (int) $row['dias_entrega'],
                    'dias_recibo' => (int) $row['dias_recibo'],
                    'dias_cuadros' => (int) $row['dias_cuadros'],
                    'dias_legalizacion' => (int) $row['dias_legalizacion'],
                    'dias_fabricacion' => (int) $row['dias_fabricacion'],
                    'dias_insumos' => (int) $row['dias_insumos'],
                ];
            }
        }

        // Fallback: defaults genéricos (Estructura)
        return [
            'dias_elaboracion' => 8,
            'dias_entrega' => 7,
            'dias_recibo' => 1,
            'dias_cuadros' => 5,
            'dias_legalizacion' => 10,
            'dias_fabricacion' => 0,
            'dias_insumos' => 0,
        ];
    }
}
