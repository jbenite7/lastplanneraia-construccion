<?php

require ("../conexion.php");

$db=$_GET['db'];
$semana=$_GET['semana'];
$definirContratos=$_GET['definirContratos'];

//$db="brizaDelCabrero";
//$semana=11;
//$definirContratos=0;

if($definirContratos == 1){
	$definirContratos = "AND numeroSubcontratos IS NOT NULL AND titulo = 0 ";
}else{
	$definirContratos = "";
}

// $db="concejo_bogota_pc";
// $semana=18;


$query = "SELECT COUNT(*) AS conteo FROM $db"."_pdc WHERE semana=$semana $definirContratos";
//echo $query;
$resultado = mysqli_query($conexion, $query);
$data=mysqli_fetch_assoc($resultado);
$conteo=$data["conteo"];
//echo $conteo;
if ($conteo==0){
    $arreglo1["data"][]=array("boton" => "", "consecutivo" => "", "id" => "", "titulo" => "","semana" => "","tipoPaquete" => "","paqueteContratacion" => "","contratos" => "", "numeroSubcontratos" => "", "subcontratoPaquete" => "", "estado" => "","fechaElaboracionPliegos" => "","diasElaboracionPliegos" => "","fechaRealElaboracionPliegos" => "","fechaIngresoLicify" => "","diasIngresoLicify" => "","fechaRealIngresoLicify" => "","fechaEntregaPliegos" => "","diasEntregaPliegos" => "","fechaRealEntregaPliegos" => "","fechaReciboPropuestas" => "","diasReciboPropuestas" => "","fechaRealReciboPropuestas" => "","fechaCuadrosComparativos" => "","diasCuadrosComparativos" => "","fechaRealCuadrosComparativos" => "","fechaLegalizacionContrato" => "","diasLegalizacionContrato" => "","fechaRealLegalizacionContrato" => "","fechaFabricacion" => "","diasFabricacion" => "","fechaRealFabricacion" => "","fechaInsumosObra" => "","diasInsumosObra" => "","fechaRealInsumosObra" => "","fechaInicio" => "", "fechaInicioProyectada" => "", "fechaRealInicio" => "", "idProveedorAdjudicado" => "", "fechaVencimientoPolizas" => "", "observacionesContrato" => "", "ordenVisual" => "");
    echo json_encode($arreglo1);
}else{
		$query1 ="SELECT * FROM $db"."_pdc WHERE semana=$semana $definirContratos ORDER BY `$db"."_pdc`.`tipoPaquete` DESC, `$db"."_pdc`.`titulo` DESC, `$db"."_pdc`.`fechaElaboracionPliegos` ASC, `$db"."_pdc`.`subcontratoPaquete` ASC";
		//echo $query1;
    $resultado1 = mysqli_query($conexion, $query1);
	//print_r(mysqli_fetch_assoc($resultado1));
    if(!$resultado1){
        die(mysqli_error($conexion));
    } else{
		$esquemaSI = 0;
		$esquemaMO = 0;
		$esquemaS = 0;
        $ordenVisual = 0;
        while($data=mysqli_fetch_assoc($resultado1)){
          $data["ordenVisual"] = $ordenVisual++;

			if($data["titulo"]==0){
            if(is_null($data["fechaRealElaboracionPliegos"]) && is_null($data["fechaRealIngresoLicify"]) && is_null($data["fechaRealEntregaPliegos"]) && is_null($data["fechaRealReciboPropuestas"]) && is_null($data["fechaRealCuadrosComparativos"]) && is_null($data["fechaRealLegalizacionContrato"]) && is_null($data["fechaRealFabricacion"]) && is_null($data["fechaRealInsumosObra"]) && is_null($data["fechaRealInicio"])){
              $data["procesoIniciado"]=0;
            }else{
              $data["procesoIniciado"]=1;
            }

						if(is_null($data["diasElaboracionPliegos"])){
							$data["diasElaboracionPliegos"] = 1;
						}
						if(is_null($data["diasIngresoLicify"])){
							$data["diasIngresoLicify"] = 1;
						}
						if(is_null($data["diasEntregaPliegos"])){
							$data["diasEntregaPliegos"] = 1;
						}
						if(is_null($data["diasReciboPropuestas"])){
							$data["diasReciboPropuestas"] = 1;
						}
						if(is_null($data["diasCuadrosComparativos"])){
							$data["diasCuadrosComparativos"] = 1;
						}
						if(is_null($data["diasLegalizacionContrato"])){
							$data["diasLegalizacionContrato"] = 1;
						}
						if(is_null($data["diasFabricacion"])){
							$data["diasFabricacion"] = 1;
						}
						if(is_null($data["diasInsumosObra"])){
							$data["diasInsumosObra"] = 1;
						}
					}

					$tipoPaquete=$data["tipoPaquete"];
					if($tipoPaquete == "Suministro e Instalación"){
						if($data["titulo"]==1){
							$id=1;
						}else{
							$esquemaSI = $esquemaSI+1;
							$id="1." . $esquemaSI;
						}
					}else if($tipoPaquete == "Mano de Obra"){
						if($data["titulo"]==1){
							$id=3;
						}else{
							$esquemaMO = $esquemaMO+1;
							$id="3." . $esquemaMO;
						}
					}else{
						if($data["titulo"]==1){
							$id=2;
						}else{
							$esquemaS = $esquemaS+1;
							$id="2." . $esquemaS;
						}
					}
					$data["id"]=$id;
          $arreglo["data"][]=array_map("utf8_encode", $data);
        }
        $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
        echo utf8_decode($json_codificado);
    }
    mysqli_free_result($resultado1);
}
mysqli_close($conexion);
?>
