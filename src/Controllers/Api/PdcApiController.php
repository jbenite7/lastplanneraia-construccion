<?php

namespace App\Controllers\Api;

use PDO;
use Throwable;

class PdcApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    // ─── LIST ───────────────────────────────────────────────────────────
    public function list(): void
    {
        $dbPrefix = $_GET['db'] ?? '';
        $semana = (int)($_GET['semana'] ?? 0);
        $definirContratos = (int)($_GET['definirContratos'] ?? 0);

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->json(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]);
            return;
        }

        $condicionContratos = ($definirContratos == 1)
            ? "AND numeroSubcontratos IS NOT NULL AND titulo = 0 "
            : "";

        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) AS conteo FROM {$dbPrefix}_pdc WHERE semana = ? $condicionContratos",
                [$semana]
            );
            $conteo = (int)($stmt->fetch()["conteo"] ?? 0);

            if ($conteo == 0) {
                $arreglo["data"][] = [
                    "boton" => "", "consecutivo" => "", "id" => "", "titulo" => "", "semana" => "",
                    "tipoPaquete" => "", "paqueteContratacion" => "", "contratos" => "", "numeroSubcontratos" => "",
                    "subcontratoPaquete" => "", "estado" => "", "fechaElaboracionPliegos" => "",
                    "diasElaboracionPliegos" => "", "fechaRealElaboracionPliegos" => "", "fechaIngresoLicify" => "",
                    "diasIngresoLicify" => "", "fechaRealIngresoLicify" => "", "fechaEntregaPliegos" => "",
                    "diasEntregaPliegos" => "", "fechaRealEntregaPliegos" => "", "fechaReciboPropuestas" => "",
                    "diasReciboPropuestas" => "", "fechaRealReciboPropuestas" => "", "fechaCuadrosComparativos" => "",
                    "diasCuadrosComparativos" => "", "fechaRealCuadrosComparativos" => "", "fechaLegalizacionContrato" => "",
                    "diasLegalizacionContrato" => "", "fechaRealLegalizacionContrato" => "", "fechaFabricacion" => "",
                    "diasFabricacion" => "", "fechaRealFabricacion" => "", "fechaInsumosObra" => "",
                    "diasInsumosObra" => "", "fechaRealInsumosObra" => "", "fechaInicio" => "",
                    "fechaInicioProyectada" => "", "fechaRealInicio" => "", "idProveedorAdjudicado" => "",
                    "fechaVencimientoPolizas" => "", "observacionesContrato" => "", "ordenVisual" => "",
                ];
                $this->json($arreglo);
                return;
            }

            $stmt1 = $this->db->query(
                "SELECT * FROM {$dbPrefix}_pdc WHERE semana = ? $condicionContratos 
                 ORDER BY tipoPaquete DESC, titulo DESC, fechaElaboracionPliegos ASC, subcontratoPaquete ASC",
                [$semana]
            );
            $resultados = $stmt1->fetchAll();

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
                        is_null($data["fechaRealElaboracionPliegos"]) && is_null($data["fechaRealIngresoLicify"]) &&
                        is_null($data["fechaRealEntregaPliegos"]) && is_null($data["fechaRealReciboPropuestas"]) &&
                        is_null($data["fechaRealCuadrosComparativos"]) && is_null($data["fechaRealLegalizacionContrato"]) &&
                        is_null($data["fechaRealFabricacion"]) && is_null($data["fechaRealInsumosObra"]) &&
                        is_null($data["fechaRealInicio"])
                    ) ? 0 : 1;

                    $camposDias = ["diasElaboracionPliegos", "diasIngresoLicify", "diasEntregaPliegos",
                                   "diasReciboPropuestas", "diasCuadrosComparativos", "diasLegalizacionContrato",
                                   "diasFabricacion", "diasInsumosObra"];
                    foreach ($camposDias as $campo) {
                        if (is_null($data[$campo])) {
                            $data[$campo] = 1;
                        }
                    }
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
            $this->json(["respuesta" => "ERROR", "mensaje" => "Error interno al cargar PDC."]);
        }
    }

    // ─── SAVE (switch central) ──────────────────────────────────────────
    public function save(): void
    {
        $opcion = $_POST['opcion'] ?? '';
        $dbPrefix = $_POST['db'] ?? '';
        $semana = $_POST['semana'] ?? 0;

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->json(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]);
            return;
        }

        try {
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
                    $this->eliminarActividad($dbPrefix);
                    break;

                case "guardar_actividad_pdc":
                    $this->guardarActividad($dbPrefix, $semana);
                    break;

                case "adjudicar_pdc":
                    $this->adjudicarPdc($dbPrefix);
                    break;

                case "modificar":
                    $this->modificar($dbPrefix);
                    break;

                case "guardar_DefinirContratos":
                    $this->guardarDefinirContratos($dbPrefix);
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
                    $this->handleCeldaDinamica($dbPrefix);
                    break;
            }
        } catch (Throwable $e) {
            error_log("Error en PdcApiController@save: " . $e->getMessage());
            $this->json(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
        }
    }

    // ─── OPERACIONES PRIVADAS ───────────────────────────────────────────

    private function eliminarActividad(string $p): void
    {
        $id = $_POST['id'];
        $justificacion = $_POST['justificacion'] ?? '';

        $stmtInfo = $this->db->query("SELECT * FROM {$p}_pdc WHERE consecutivo = ?", [$id]);
        $info = $stmtInfo->fetch();

        if ($info) {
            $this->db->query("INSERT INTO {$p}_papelera_pdc SELECT * FROM {$p}_pdc WHERE consecutivo = ?", [$id]);
            $this->db->query("UPDATE {$p}_papelera_pdc SET justificacionEliminacion = ? WHERE consecutivo = ?", [$justificacion, $id]);
            $this->db->query("DELETE FROM {$p}_pdc WHERE consecutivo = ?", [$id]);
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

        $campos = ['fechaElaboracionPliegos','diasElaboracionPliegos','fechaIngresoLicify','diasIngresoLicify',
                    'fechaEntregaPliegos','diasEntregaPliegos','fechaReciboPropuestas','diasReciboPropuestas',
                    'fechaCuadrosComparativos','diasCuadrosComparativos','fechaLegalizacionContrato','diasLegalizacionContrato',
                    'fechaFabricacion','diasFabricacion','fechaInsumosObra','diasInsumosObra','fechaInicio'];

        $vals = array_map(fn($c) => $this->checkNull($_POST[$c] ?? ''), $campos);

        $sql = "INSERT INTO {$p}_pdc (
            semana, titulo, tipoPaquete, paqueteContratacion, contratos, subcontratoPaquete, estado,
            fechaElaboracionPliegos, diasElaboracionPliegos, fechaIngresoLicify, diasIngresoLicify,
            fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas,
            fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato,
            fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, array_merge([$semana, 0, $tipoPaquete, $paqueteContratacion, $contratos, $subcontratoPaquete, $estado], $vals));
        $this->db->logActivity('PDC', 'CREAR_ACTIVIDAD', "Creó nueva actividad PDC: Paquete $paqueteContratacion", $p);
        $this->json(["respuesta" => "BIEN"]);
    }

    private function adjudicarPdc(string $p): void
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
            WHERE consecutivo=?";
        $this->db->query($sql, array_merge($vals, [$id]));
        $this->db->logActivity('PDC', 'ADJUDICAR_PDC', "Adjudicó PDC consecutivo $id", $p);
        $this->json(["respuesta" => "BIEN"]);
    }

    private function modificar(string $p): void
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
        $dEl=$cn('diasElaboracionPliegos'); $dIL=$cn('diasIngresoLicify'); $dEP=$cn('diasEntregaPliegos');
        $dRP=$cn('diasReciboPropuestas'); $dCC=$cn('diasCuadrosComparativos'); $dLC=$cn('diasLegalizacionContrato');
        $dF=$cn('diasFabricacion'); $dIO=$cn('diasInsumosObra');

        $fREl=$cn('fechaRealElaboracionPliegos'); $fRIL=$cn('fechaRealIngresoLicify'); $fREP=$cn('fechaRealEntregaPliegos');
        $fRRP=$cn('fechaRealReciboPropuestas'); $fRCC=$cn('fechaRealCuadrosComparativos'); $fRLC=$cn('fechaRealLegalizacionContrato');
        $fRF=$cn('fechaRealFabricacion'); $fRIO=$cn('fechaRealInsumosObra'); $fRIn=$cn('fechaRealInicioProyectadaContrato');

        $obs = $cn('observacionesContrato');
        $est = $_POST['estadoProceso'] ?? '';

        $sql = "UPDATE {$p}_pdc SET 
            idProveedorAdjudicado=?, numeroContrato=?, aplicaPolizas=?, fechaVencimientoPolizas=?,
            valorPresupuesto=?, valorPrimeraNegociacion=?, valorAdjudicado=?, valorAnticipo=?,
            valorReclamado=?, valorDevoluciones=?, 
            diasElaboracionPliegos=?, diasIngresoLicify=?, diasEntregaPliegos=?, diasReciboPropuestas=?,
            diasCuadrosComparativos=?, diasLegalizacionContrato=?, diasFabricacion=?, diasInsumosObra=?,
            fechaRealElaboracionPliegos=?, fechaRealIngresoLicify=?, fechaRealEntregaPliegos=?, 
            fechaRealReciboPropuestas=?, fechaRealCuadrosComparativos=?, fechaRealLegalizacionContrato=?,
            fechaRealFabricacion=?, fechaRealInsumosObra=?, fechaRealInicio=?,
            observacionesContrato=?, estado=?
            WHERE consecutivo=?";

        $this->db->query($sql, [
            $idProv, $numContrato, $aplicaPolizas, $fVencPolizas,
            $vPres, $vInit, $vAdj, $vAnt, $vRec, $vDev,
            $dEl, $dIL, $dEP, $dRP, $dCC, $dLC, $dF, $dIO,
            $fREl, $fRIL, $fREP, $fRRP, $fRCC, $fRLC, $fRF, $fRIO, $fRIn,
            $obs, $est, $id,
        ]);
        $this->db->logActivity('PDC', 'MODIFICAR_ACTIVIDAD', "Modificó paquete PDC consecutivo $id", $p);
        echo json_encode("OK");
    }

    private function guardarDefinirContratos(string $p): void
    {
        $json = $_POST['numeroSubcontratos'] ?? '';
        $data = json_decode($json, true);
        if (isset($data['numeroSubcontratos'])) {
            foreach ($data['numeroSubcontratos'] as $item) {
                $this->db->query("UPDATE {$p}_pdc SET numeroSubcontratos = ? WHERE consecutivo = ?", [$item['numeroSubcontratos'], $item['consecutivo']]);
            }
            echo json_encode("conModificaciones");
        } else {
            echo json_encode("sinModificaciones");
        }
    }

    private function adjudicarContrato(string $p): void
    {
        $idProv = $_POST['idProveedorAdjudicado'] ?? 0;
        $sub = $_POST['Subcontratista'] ?? '';
        $nit = $_POST['NIT'] ?? '';
        $email = $_POST['email'] ?? '';
        $alcance = $_POST['alcance'] ?? '';
        $tipo = $_POST['tipo_proveedor'] ?? 1;
        if ($tipo != 1 && $tipo != 2) $tipo = 1;

        if ($idProv != 0 && $idProv != "0") {
            $stmtOld = $this->db->query("SELECT subcontratista FROM {$p}_subcontratistas WHERE id=?", [$idProv]);
            $oldName = ($stmtOld->fetch())['subcontratista'] ?? '';

            $this->db->query("UPDATE {$p}_subcontratistas SET subcontratista=?, correo_contacto=?, NIT=?, alcance=?, tipo_proveedor=? WHERE id=?",
                [$sub, $email, $nit, $alcance, $tipo, $idProv]);

            if ($oldName && $oldName != $sub) {
                $tables = [
                    "{$p}_programacion_semanal" => "Sub_Contratista",
                    "{$p}_programa_consolidado" => "Sub_Contratista",
                    "{$p}_cic" => "subcontratista",
                    "{$p}_indicadores_generales" => "subcontratista_profesional",
                ];
                foreach ($tables as $tbl => $col) {
                    $this->db->query("UPDATE $tbl SET $col = ? WHERE $col = ?", [$sub, $oldName]);
                }
            }
            $this->json(["respuesta" => "BIEN", "idProveedor" => $idProv]);
        } else {
            $this->db->query("INSERT INTO {$p}_subcontratistas (subcontratista, correo_contacto, NIT, alcance, tipo_proveedor) VALUES (?,?,?,?,?)",
                [$sub, $email, $nit, $alcance, $tipo]);
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
        if (!$fechaInicio) { $this->json(["data" => [[]]]); return; }

        $dIO=(int)($_POST['diasInsumosObra']??0); $dF=(int)($_POST['diasFabricacion']??0);
        $dLC=(int)($_POST['diasLegalizacionContrato']??0); $dCC=(int)($_POST['diasCuadrosComparativos']??0);
        $dRP=(int)($_POST['diasReciboPropuestas']??0); $dEP=(int)($_POST['diasEntregaPliegos']??0);
        $dIL=(int)($_POST['diasIngresoLicify']??0); $dEl=(int)($_POST['diasElaboracionPliegos']??0);

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
            "fechaIngresoLicify" => $fIL, "fechaElaboracionPliegos" => $fEl,
            "fechaInicioProyectada" => $fechaInicio,
        ]]]);
    }

    private function handleCeldaDinamica(string $p): void
    {
        if (isset($_POST['columna'])) {
            $columna = $_POST['columna'];
            $valor = $_POST['valor'];
            $id = $_POST['id'];
            if (preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
                $this->db->query("UPDATE {$p}_pdc SET $columna = ? WHERE consecutivo = ?", [$this->checkNull($valor), $id]);
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

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
