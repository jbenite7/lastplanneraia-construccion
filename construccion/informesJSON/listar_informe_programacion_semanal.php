<?php
	require ("../conexion.php");

  //$db=$_GET['db'];
	$query = "SELECT COUNT(*) FROM general_informe_consolidado;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        // $arreglo1["data"][]=array("Id" => "","subcontratista" => "","correo_contacto" => "","NIT" => "","alcance" => "","tipo_proveedor" => "");
        // echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT * FROM general_informe_consolidado;";
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
							if($data["Activa"] == "" || $data["Activa"] == null){
								$data["Activa"] = "NA";
							};

							if($data["Categoria_CNC"] == "" || $data["Categoria_CNC"] == null){
								$data["Categoria_CNC"] = "No Asignado";
							};

							if(["Categoria_CNP"] == "" || $data["Categoria_CNP"] == null){
								$data["Categoria_CNP"] = "No Asignado";
							};

							if($data["CNC"] == "" || $data["CNC"] == null){
								$data["CNC"] = "No Asignado";
							};

							if(["CNP"] == "" || $data["CNP"] == null){
								$data["CNP"] = "No Asignado";
							};

							if($data["Observaciones_CNC"] == "" || $data["Observaciones_CNC"] == null){
								$data["Observaciones_CNC"] = "No Asignado";
							};

							if(["Observaciones_CNP"] == "" || $data["Observaciones_CNP"] == null){
								$data["Observaciones_CNP"] = "No Asignado";
							};

							if($data["Unidad"] == "" || $data["Unidad"] == null){
								$data["Unidad"] = "%";
							};

							if($data["Sub_Contratista"] == "" || $data["Sub_Contratista"] == null){
								$data["Sub_Contratista"] = "No Asignado";
							};

							if($data["Responsable_AIA"] == "" || $data["Responsable_AIA"] == null ){
								$data["Responsable_AIA"] = "No Asignado";
							};

							if($data["Compromiso"] == "" || $data["Compromiso"] == null ){
								$data["Compromiso"] = "No Asignado";
							};

							if($data["Ejecutado_Real"] == "" || $data["Ejecutado_Real"] == null ){
								$data["Ejecutado_Real"] = "No Asignado";
							};

							$diasInicio = (strtotime($data["Fecha_Inicio"]) - strtotime($data["Fecha_Inicio_Sem"])) / (60 * 60 * 24);

							$semanasInicio = floor($diasInicio / 7);

							$data["semanasInicio"] = $semanasInicio;

							if($data["Ejecutado"] == 0){
								if($semanasInicio == 0){
									$data["Estado"] = "Debe comenzar esta semana";
								}else{
									$data["Estado"] = "Debió comenzar hace " . abs($semanasInicio) . " semanas";
								}
							}else{
								$data["Estado"] = "En Ejecución";
							}


							$data["Fecha_Inicio"] = date("Y-m-d", strtotime($data["Fecha_Inicio"]));
							$data["Fecha_Inicio_Sem"] = date("Y-m-d", strtotime($data["Fecha_Inicio_Sem"]));
							$data["Fecha_Fin"] = date("Y-m-d", strtotime($data["Fecha_Fin"]));
							$data["Fecha_Fin"] = date("Y-m-d", strtotime($data["Fecha_Fin"]));


              $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado1);
    }
    mysqli_close($conexion);
?>
