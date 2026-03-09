<?php

namespace App\Controllers\Api;

use App\Core\Database; // Ajusta si la ruta de tu DB Singleton es distinta (legacy usa Database::getInstance())
use Exception;
use PDO;
use Throwable;
use SplFileInfo;

class ListadoActividadesApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance(); // En legacy está mapeado globalmente o requiere su propio namespace
    }

    public function list(): void
    {
        $dbPrefix = $_GET['db'] ?? '';
        
        if (empty($dbPrefix) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonResponse(["data" => []]);
            return;
        }

        $semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        try {
            $query = "SELECT COUNT(*) FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ?";
            $stmt = $this->db->query($query, [$semana]);
            $conteo = $stmt->fetchColumn();

            $response = ["data" => []];

            if ($conteo == 0) {
                // Return default empty structure
                $response["data"][] = [
                    "Id" => "",
                    "codigo" => "",
                    "actividad" => "",
                    "descripcionActividad" => "",
                    "actividadInicio" => "",
                    "nombreActividadInicio" => "",
                    "fechaInicio" => "",
                    "tipoContrato" => "",
                    "semanaActualizacion" => "",
                ];
            } else {
                $query1 = "SELECT 
                            a.Id, 
                            a.codigo, 
                            a.actividad, 
                            a.descripcionActividad, 
                            a.actividadInicio, 
                            CONCAT(p.Id, '. ', p.Actividad, ' (Inicia en: ', p.Fecha_Inicio, ')') AS nombreActividadInicio, 
                            a.fechaInicio, 
                            a.tipoContrato, 
                            a.semanaActualizacion 
                           FROM {$dbPrefix}_actividades AS a
                           LEFT JOIN {$dbPrefix}_programa_consolidado AS p 
                           ON p.Actividad = a.actividadInicio AND p.Semana = a.semanaActualizacion 
                           WHERE a.semanaActualizacion = ? 
                           ORDER BY a.Id";

                $stmt1 = $this->db->query($query1, [$semana]);
                $response["data"] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->jsonResponse($response);

        } catch (Throwable $e) {
            error_log("Error en ListadoActividadesApiController@list: " . $e->getMessage());
            $this->jsonResponse(["data" => [], "error" => $e->getMessage()]);
        }
    }

    public function save(): void
    {
        $dbPrefix = $_GET['db'] ?? '';
        if (empty($dbPrefix) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonResponse(["respuesta" => "ERROR", "mensaje" => "Prefijo de base de datos inválido."]);
            return;
        }

        $opcion = $_POST["opcion"] ?? '';

        try {
            if ($opcion == "registrar") {
                $this->registrar($dbPrefix);
            } elseif ($opcion == "modificar") {
                $this->modificar($dbPrefix);
            } elseif ($opcion == "eliminar") {
                $this->eliminar($dbPrefix);
            } elseif ($opcion == "actualizarFechaInicio") {
                $this->actualizarFechaInicio($dbPrefix);
            } elseif ($opcion == "cargarExcel") {
                $this->cargarExcel($dbPrefix);
            } else {
                $this->jsonResponse(["respuesta" => "ERROR", "mensaje" => "Opción no válida."]);
            }
        } catch (Throwable $e) {
            error_log("Error in ListadoActividadesApiController@save: " . $e->getMessage());
            $this->jsonResponse(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
        }
    }

    private function registrar(string $dbPrefix): void
    {
        $Actividad = !empty($_POST['actividad']) ? trim($_POST['actividad']) : '';
        $descripcionActividad = !empty($_POST['descripcionActividad']) ? trim($_POST['descripcionActividad']) : '';
        $fechaInicio = !empty($_POST['fechaInicio']) ? date("Y-m-d", strtotime($_POST["fechaInicio"])) : null;
        $tipoContrato = $_POST['tipoContrato'] ?? '';
        $semana = $_POST['semana'] ?? '';
        $actividadInicio = !empty($_POST['actividadInicio']) ? $_POST['actividadInicio'] : null;

        $errores = '';
        if (empty($Actividad) || empty($descripcionActividad) || empty($fechaInicio) || empty($tipoContrato) || empty($semana)) {
            $errores = 'Debe rellenar todos los campos';
        } else {
            $queryCheck = "SELECT COUNT(*) FROM {$dbPrefix}_actividades WHERE actividad = ? LIMIT 1";
            $stmtCheck = $this->db->query($queryCheck, [$Actividad]);
            if ($stmtCheck->fetchColumn() > 0) {
                $errores = 'La actividad que estás intentando registrar ya existe';
            }

            if (empty($errores)) {
                $queryMax = "SELECT MAX(codigo) FROM {$dbPrefix}_actividades";
                $stmtMax = $this->db->query($queryMax);
                $maxCode = $stmtMax->fetchColumn();
                $codigo = empty($maxCode) ? 1 : $maxCode + 1;

                $queryInsert = "INSERT INTO {$dbPrefix}_actividades (codigo, actividad, descripcionActividad, actividadInicio, nombreActividadInicio, fechaInicio, tipoContrato, semanaActualizacion) 
                                VALUES (?, ?, ?, ?, (SELECT Actividad FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Actividad = ? LIMIT 1), ?, ?, ?)";
                $params = [$codigo, $Actividad, $descripcionActividad, $actividadInicio, $semana, $actividadInicio, $fechaInicio, $tipoContrato, $semana];

                $stmtInsert = $this->db->query($queryInsert, $params);
                $this->db->logActivity('ListadoActividades', 'CREAR', "Creó actividad: $Actividad", $dbPrefix);
                
                $this->verificar_resultado(true, '');
                return;
            }
        }
        $this->verificar_resultado(false, $errores);
    }

    private function modificar(string $dbPrefix): void
    {
        $Id = $_POST['Id'] ?? 0;
        $Actividad = !empty($_POST['Actividad']) ? trim($_POST['Actividad']) : '';
        $descripcionActividad = !empty($_POST['descripcionActividad']) ? trim($_POST['descripcionActividad']) : '';
        $fechaInicio = !empty($_POST['fechaInicio']) ? date("Y-m-d", strtotime($_POST["fechaInicio"])) : null;
        $tipoContrato = $_POST['tipoContrato'] ?? '';
        $semana = $_POST['semana'] ?? '';
        $actividadInicio = !empty($_POST['actividadInicio']) ? $_POST['actividadInicio'] : null;

        $errores = '';
        if (empty($Actividad) || empty($descripcionActividad) || empty($fechaInicio) || empty($tipoContrato) || empty($semana)) {
            $errores = 'Debe rellenar todos los campos';
        } else {
            $queryUpdate = "UPDATE {$dbPrefix}_actividades SET actividad=?, descripcionActividad=?, actividadInicio=?, 
                             nombreActividadInicio=(SELECT Actividad FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Actividad = ? LIMIT 1), 
                             fechaInicio=?, tipoContrato=?, semanaActualizacion=? WHERE Id=?";
            $params = [$Actividad, $descripcionActividad, $actividadInicio, $semana, $actividadInicio, $fechaInicio, $tipoContrato, $semana, $Id];
            $stmtUpdate = $this->db->query($queryUpdate, $params);
            $this->db->logActivity('ListadoActividades', 'MODIFICAR', "Modificó actividad ID $Id", $dbPrefix);
            
            $this->verificar_resultado(true, '');
            return;
        }
        $this->verificar_resultado(false, $errores);
    }

    private function eliminar(string $dbPrefix): void
    {
        $Id = $_POST["Id"] ?? 0;
        $query = "DELETE FROM {$dbPrefix}_actividades WHERE Id = ?";
        $stmt = $this->db->query($query, [$Id]);
        $this->db->logActivity('ListadoActividades', 'ELIMINAR', "Eliminó actividad ID $Id", $dbPrefix);
        $this->verificar_resultado(true, '');
    }

    private function actualizarFechaInicio(string $dbPrefix): void
    {
        $Id = $_POST["idActividad"] ?? '';
        $semana = $_POST["semana"] ?? 0;

        try {
            $query = "SELECT Fecha_Inicio FROM {$dbPrefix}_programa_consolidado WHERE Actividad = ? AND Semana = ?";
            $stmt = $this->db->query($query, [$Id, $semana]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $this->jsonResponse(["data" => $data]);
            } else {
                $this->jsonResponse(["data" => ["Fecha_Inicio" => ""]]);
            }
        } catch (Throwable $t) {
            $this->jsonResponse(["data" => ["Fecha_Inicio" => ""], "error" => $t->getMessage()]);
        }
    }

    private function cargarExcel(string $dbPrefix): void
    {
        $archivoExcel = $_FILES["archivoExcel"] ?? null;
        if (!$archivoExcel) {
            $this->verificar_resultado(false, "No se recibió archivo");
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $semanaActualizacion = $_SESSION["Max_Semana"] ?? 0;
        
        $info = new SplFileInfo($archivoExcel["name"]);
        $extension = $info->getExtension();

        if (strtolower($extension) == "csv") {
            $filename = $archivoExcel['tmp_name'];
            if (($handle = fopen($filename, "r")) !== false) {
                $error = false;
                try {
                    $this->db->beginTransaction();

                    $this->db->query("TRUNCATE TABLE {$dbPrefix}_actividades");

                    $sql = "INSERT INTO {$dbPrefix}_actividades (codigo, actividad, descripcionActividad, semanaActualizacion) VALUES (?, ?, ?, ?)";
                    $stmt = $this->db->prepare($sql);

                    $numeroFila = 0;
                    while (($data = fgetcsv($handle, 10000, ";")) !== false) {
                        if ($numeroFila != 0) {
                            $actividad = $data[0] ?? '';
                            $descripcion = $data[1] ?? '';
                            $stmt->execute([$numeroFila, $actividad, $descripcion, $semanaActualizacion]);
                        }
                        $numeroFila++;
                    }
                    $this->db->commit();
                    $this->db->logActivity('ListadoActividades', 'IMPORTAR', "Importó actividades desde Excel", $dbPrefix);
                    fclose($handle);
                } catch (Exception $e) {
                    $this->db->rollBack();
                    $error = true;
                    error_log("Excel Import Error: " . $e->getMessage());
                }

                if ($error) {
                    $this->verificar_resultado(false, "No carga desde excel");
                } else {
                    $this->verificar_resultado(true, "");
                }

            } else {
                $this->verificar_resultado(false, "Error al abrir archivo");
            }
        } else {
            $this->verificar_resultado(false, "Formato invalido (debe ser .csv)");
        }
    }

    private function verificar_resultado(bool $success, string $errores): void
    {
        $informacion = [];
        $informacion["respuesta"] = $success ? "BIEN" : "ERROR";

        if ($errores == 'Debe rellenar todos los campos') {
            $informacion["respuesta"] = "VACIO";
        } elseif ($errores == 'La actividad que estás intentando registrar ya existe') {
            $informacion["respuesta"] = "EXISTE";
        } elseif (!empty($errores) && $informacion["respuesta"] == "ERROR") {
            $informacion["mensaje"] = $errores;
        }

        $this->jsonResponse($informacion);
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
