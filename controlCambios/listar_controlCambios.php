<?php
	require ("../conexion.php");

    $db=$_GET['db'];
	$semana=$_GET['semana'];

	 //$db='accesibilidadMetroB';
	 //$semana=6;
	$query = "SELECT COUNT(*) FROM $db"."_cambios;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        $arreglo1["data"][]=array("id" => "","solicitanteCambio" => "","detalleSolicitanteOtro" => "","fechaSolicitud" => "","prioridad" => "","tipoCambio" => "","responsableSolucion" => "","detalleResponsableSolucion" => "","justificacion" => "","descripcion" => "","incidenciaAlcance" => "","tiempoCronograma" => "","tiempoCronogramaAfectado" => "", "incidenciaCronograma" => "","costoDirecto" => "","valorPresupuesto" => "","costoDirectoAIU" => "","costoDirectoAIUIVA" => "","valorAprobado" => "","incidenciaPresupuesto" => "","incidenciaCalidad" => "","incidenciaRiesgo" => "","incidenciaRecurso" => "","fechaTentativaDefinicion" => "","fechaEntregaInterventoria" => "","Observaciones" => "","fechaDefinicion" => "","aprobacion" => "","soportes" => "{\"soportes\": [{\"consecutivo\":1,\"descripcion\":\"\",\"link\":\"\"}]}");
        echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM $db"."_cambios;";
        //echo $query1;
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die(mysqli_error($conexion));
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
								
				
                $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado1);
    }
    mysqli_close($conexion);
?>
