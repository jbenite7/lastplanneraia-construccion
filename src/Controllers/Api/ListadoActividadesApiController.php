<?php

namespace App\Controllers\Api;

use App\Support\ModuleRequestContext;
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
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semana = $this->resolveMaxSemana($dbPrefix);

            $this->requirePermission('lps.listado_actividades.ver', 'No autorizado para consultar el listado de actividades.');

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
                            COALESCE(
                                (
                                    SELECT pc.Consecutivo_en_Programa
                                    FROM {$dbPrefix}_programa_consolidado pc
                                    WHERE pc.Semana = a.semanaActualizacion
                                      AND (pc.Consecutivo_en_Programa = a.actividadInicio OR pc.Actividad = a.actividadInicio)
                                    ORDER BY pc.Fecha_Inicio ASC
                                    LIMIT 1
                                ),
                                a.actividadInicio
                            ) AS actividadInicio,
                            COALESCE(
                                (
                                    SELECT CONCAT(pc.Id, '. ', pc.Actividad, ' (Inicia en: ', pc.Fecha_Inicio, ')')
                                    FROM {$dbPrefix}_programa_consolidado pc
                                    WHERE pc.Semana = a.semanaActualizacion
                                      AND (pc.Consecutivo_en_Programa = a.actividadInicio OR pc.Actividad = a.actividadInicio)
                                    ORDER BY pc.Fecha_Inicio ASC
                                    LIMIT 1
                                ),
                                a.nombreActividadInicio
                            ) AS nombreActividadInicio,
                            a.fechaInicio, 
                            a.tipoContrato, 
                            a.semanaActualizacion 
                           FROM {$dbPrefix}_actividades AS a
                           WHERE a.semanaActualizacion = ? 
                           ORDER BY a.Id";

                $stmt1 = $this->db->query($query1, [$semana]);
                $response["data"] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->jsonResponse($response);

        } catch (Throwable $e) {
            error_log("Error en ListadoActividadesApiController@list: " . $e->getMessage());
            $this->jsonError('No se pudo cargar el listado de actividades.', 500, ["data" => []]);
        }
    }

    public function save(): void
    {
        $opcion = $_POST["opcion"] ?? '';

        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semana = $this->resolveMaxSemana($dbPrefix);

            $this->requirePermission('lps.listado_actividades.ver', 'No autorizado para consultar el listado de actividades.');

            if (in_array($opcion, ['registrar', 'modificar', 'eliminar', 'cargarExcel'], true)) {
                $this->requirePermission('lps.listado_actividades.editar', 'No autorizado para modificar el listado de actividades.');
            }

            if ($opcion == "registrar") {
                $this->registrar($dbPrefix, $semana);
            } elseif ($opcion == "modificar") {
                $this->modificar($dbPrefix, $semana);
            } elseif ($opcion == "eliminar") {
                $this->eliminar($dbPrefix, $semana);
            } elseif ($opcion == "actualizarFechaInicio") {
                $this->actualizarFechaInicio($dbPrefix, $semana);
            } elseif ($opcion == "cargarExcel") {
                $this->cargarExcel($dbPrefix, $semana);
            } else {
                $this->jsonError('Opción no válida.');
            }
        } catch (Throwable $e) {
            error_log("Error in ListadoActividadesApiController@save: " . $e->getMessage());
            $this->jsonError('No se pudo procesar la solicitud del listado de actividades.', 500);
        }
    }

    private function resolveMaxSemana(string $dbPrefix): int
    {
        $query = "SELECT MAX(Semana) FROM {$dbPrefix}_semanas_activas";
        $maxSemana = (int) $this->db->query($query)->fetchColumn();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['Max_Semana'] = $maxSemana;
            $_SESSION['semana'] = $maxSemana;
        }

        return $maxSemana;
    }

    private function registrar(string $dbPrefix, int $semana): void
    {
        $Actividad = !empty($_POST['actividad']) ? trim($_POST['actividad']) : '';
        $descripcionActividad = !empty($_POST['descripcionActividad']) ? trim($_POST['descripcionActividad']) : '';
        $fechaInicio = !empty($_POST['fechaInicio']) ? date("Y-m-d", strtotime($_POST["fechaInicio"])) : null;
        $tipoContrato = $_POST['tipoContrato'] ?? '';
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
                                VALUES (?, ?, ?, ?, (SELECT CONCAT(Id, '. ', Actividad, ' (Inicia en: ', Fecha_Inicio, ')') FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Consecutivo_en_Programa = ? LIMIT 1), ?, ?, ?)";
                $params = [$codigo, $Actividad, $descripcionActividad, $actividadInicio, $semana, $actividadInicio, $fechaInicio, $tipoContrato, $semana];

                $stmtInsert = $this->db->query($queryInsert, $params);
                $this->db->logActivity('ListadoActividades', 'CREAR', "Creó actividad: $Actividad", $dbPrefix);
                
                $this->verificar_resultado(true, '');
                return;
            }
        }
        $this->verificar_resultado(false, $errores);
    }

    private function modificar(string $dbPrefix, int $semana): void
    {
        $Id = $_POST['Id'] ?? 0;
        $Actividad = !empty($_POST['Actividad']) ? trim($_POST['Actividad']) : '';
        $descripcionActividad = !empty($_POST['descripcionActividad']) ? trim($_POST['descripcionActividad']) : '';
        $fechaInicio = !empty($_POST['fechaInicio']) ? date("Y-m-d", strtotime($_POST["fechaInicio"])) : null;
        $tipoContrato = $_POST['tipoContrato'] ?? '';
        $actividadInicio = !empty($_POST['actividadInicio']) ? $_POST['actividadInicio'] : null;

        $errores = '';
        if (empty($Actividad) || empty($descripcionActividad) || empty($fechaInicio) || empty($tipoContrato) || empty($semana)) {
            $errores = 'Debe rellenar todos los campos';
        } else {
            $queryUpdate = "UPDATE {$dbPrefix}_actividades SET actividad=?, descripcionActividad=?, actividadInicio=?, 
                             nombreActividadInicio=(SELECT CONCAT(Id, '. ', Actividad, ' (Inicia en: ', Fecha_Inicio, ')') FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Consecutivo_en_Programa = ? LIMIT 1), 
                             fechaInicio=?, tipoContrato=?, semanaActualizacion=? WHERE Id=? AND semanaActualizacion=?";
            $params = [$Actividad, $descripcionActividad, $actividadInicio, $semana, $actividadInicio, $fechaInicio, $tipoContrato, $semana, $Id, $semana];
            $stmtUpdate = $this->db->query($queryUpdate, $params);
            $this->db->logActivity('ListadoActividades', 'MODIFICAR', "Modificó actividad ID $Id", $dbPrefix);
            
            $this->verificar_resultado(true, '');
            return;
        }
        $this->verificar_resultado(false, $errores);
    }

    private function eliminar(string $dbPrefix, int $semana): void
    {
        $Id = $_POST["Id"] ?? 0;
        $query = "DELETE FROM {$dbPrefix}_actividades WHERE Id = ? AND semanaActualizacion = ?";
        $stmt = $this->db->query($query, [$Id, $semana]);
        $this->db->logActivity('ListadoActividades', 'ELIMINAR', "Eliminó actividad ID $Id", $dbPrefix);
        $this->verificar_resultado(true, '');
    }

    private function actualizarFechaInicio(string $dbPrefix, int $semana): void
    {
        $Id = $_POST["idActividad"] ?? '';

        try {
            $query = "SELECT Fecha_Inicio FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Consecutivo_en_Programa = ? LIMIT 1";
            $stmt = $this->db->query($query, [$semana, $Id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $this->jsonResponse(["data" => $data]);
            } else {
                $this->jsonResponse(["data" => ["Fecha_Inicio" => ""]]);
            }
        } catch (Throwable $t) {
            error_log("Error en ListadoActividadesApiController@actualizarFechaInicio: " . $t->getMessage());
            $this->jsonResponse(["data" => ["Fecha_Inicio" => ""]]);
        }
    }

    private function cargarExcel(string $dbPrefix, int $semanaActualizacion): void
    {
        $archivoExcel = $_FILES["archivoExcel"] ?? null;
        if (!$archivoExcel) {
            $this->verificar_resultado(false, "No se recibió archivo");
            return;
        }

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
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $message, int $httpCode = 400, array $extra = []): void
    {
        http_response_code($httpCode);
        $this->jsonResponse(array_merge([
            'respuesta' => 'ERROR',
            'mensaje' => $message,
        ], $extra));
    }

    private function requirePermission(string $permissionKey, string $message): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission($permissionKey, ['message' => $message]);
    }
}
