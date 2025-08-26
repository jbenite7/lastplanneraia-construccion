<?php
	require ("../conexion.php");

  //$db=$_GET['db'];
	$query = "SELECT COUNT(*) FROM general_informe_pdc;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        // $arreglo1["data"][]=array("Id" => "","subcontratista" => "","correo_contacto" => "","NIT" => "","alcance" => "","tipo_proveedor" => "");
        // echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT * FROM general_informe_pdc;";
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
							$data["Fecha_Inicio_Sem"] = ($data["Fecha_Inicio_Sem"] == null || $data["Fecha_Inicio_Sem"] == '') ? "NULL" : date("Y-m-d", strtotime($data["Fecha_Inicio_Sem"]));

							$data["Fecha_Fin_Sem"] = ($data["Fecha_Fin_Sem"] == null || $data["Fecha_Fin_Sem"] == '') ? "NULL" : date("Y-m-d", strtotime($data["Fecha_Fin_Sem"]));

							$data["fechaHoy"] = ($data["FechaHoy"] == null || $data["FechaHoy"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaHoy"]));

							$data["fechaElaboracionPliegos"] = ($data["fechaElaboracionPliegos"] == null || $data["fechaElaboracionPliegos"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaElaboracionPliegos"]));

							$data["fechaRealElaboracionPliegos"] = ($data["fechaRealElaboracionPliegos"] == null || $data["fechaRealElaboracionPliegos"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealElaboracionPliegos"]));

							$data["fechaIngresoLicify"] = ($data["fechaIngresoLicify"] == null || $data["fechaIngresoLicify"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaIngresoLicify"]));

							$data["fechaRealIngresoLicify"] = ($data["fechaRealIngresoLicify"] == null || $data["fechaRealIngresoLicify"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealIngresoLicify"]));

							$data["fechaEntregaPliegos"] = ($data["fechaEntregaPliegos"] == null || $data["fechaEntregaPliegos"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaEntregaPliegos"]));

							$data["fechaRealEntregaPliegos"] = ($data["fechaRealEntregaPliegos"] == null || $data["fechaRealEntregaPliegos"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealEntregaPliegos"]));

							$data["fechaReciboPropuestas"] = ($data["fechaReciboPropuestas"] == null || $data["fechaReciboPropuestas"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaReciboPropuestas"]));

							$data["fechaRealReciboPropuestas"] = ($data["fechaRealReciboPropuestas"] == null || $data["fechaRealReciboPropuestas"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealReciboPropuestas"]));

							$data["fechaCuadrosComparativos"] = ($data["fechaCuadrosComparativos"] == null || $data["fechaCuadrosComparativos"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaCuadrosComparativos"]));

							$data["fechaRealCuadrosComparativos"] = ($data["fechaRealCuadrosComparativos"] == null || $data["fechaRealCuadrosComparativos"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealCuadrosComparativos"]));

							$data["fechaLegalizacionContrato"] = ($data["fechaLegalizacionContrato"] == null || $data["fechaLegalizacionContrato"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaLegalizacionContrato"]));

							$data["fechaRealLegalizacionContrato"] = ($data["fechaRealLegalizacionContrato"] == null || $data["fechaRealLegalizacionContrato"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealLegalizacionContrato"]));

							$data["fechaFabricacion"] = ($data["fechaFabricacion"] == null || $data["fechaFabricacion"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaFabricacion"]));

							$data["fechaRealFabricacion"] = ($data["fechaRealFabricacion"] == null || $data["fechaRealFabricacion"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealFabricacion"]));

							$data["fechaInsumosObra"] = ($data["fechaInsumosObra"] == null || $data["fechaInsumosObra"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaInsumosObra"]));

							$data["fechaRealInsumosObra"] = ($data["fechaRealInsumosObra"] == null || $data["fechaRealInsumosObra"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealInsumosObra"]));

							$data["fechaInicio"] = ($data["fechaInicio"] == null || $data["fechaInicio"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaInicio"]));

							$data["fechaInicioProyectada"] = ($data["fechaInicioProyectada"] == null || $data["fechaInicioProyectada"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaInicioProyectada"]));

							$data["fechaRealInicio"] = ($data["fechaRealInicio"] == null || $data["fechaRealInicio"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaRealInicio"]));

							$data["fechaVencimientoPolizas"] = ($data["fechaVencimientoPolizas"] == null || $data["fechaVencimientoPolizas"] == '') ? "NULL" : date("Y-m-d", strtotime($data["fechaVencimientoPolizas"]));

							$data["Semana_Inicio_Actividad"] = date("Y-m-d", strtotime($data["Fecha_Inicio_Sem"] . " + " . $data["Semanas_Inicio"] . " weeks"));

							if($data["nitProveedorAdjudicado"] == "" || $data["nitProveedorAdjudicado"] == null){
								$data["nitProveedorAdjudicado"] = "No Asignado";
							};

							if($data["proveedorAdjudicado"] == "" || $data["proveedorAdjudicado"] == null){
								$data["proveedorAdjudicado"] = "No Asignado";
							};

							if($data["observacionesContrato"] == "" || $data["observacionesContrato"] == null){
								$data["observacionesContrato"] = "NULL";
							};

              $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado1);
    }
    mysqli_close($conexion);
?>
