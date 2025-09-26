<?php


require("../conexion.php");

$db=/*"paris_campestre"*/$_GET['db'];
$opcion=/*"detalle_compromisos"*/$_POST["opcion"];
$informacion=[];

switch($opcion){
    case 'detalle_PAC_General':
        
        $semana=/*1*/$_POST["semana"];
        detalle_PAC_General($conexion, $semana, $db);
        break;
        
    case 'detalle_PAC_Subcontratista':
        
        $semana=/*1*/$_POST["semana"];
        $nombre_PAC=/*"Construcciones FG - Felipe Gil"*/$_POST["nombre_PAC"];
        detalle_PAC_Subcontratista($conexion, $semana, $nombre_PAC, $db);
        break;
        
    case 'detalle_PAC_Profesional':
        
        $semana=/*1*/$_POST["semana"];
        $nombre_PAC=/*"Juan Benitez"*/$_POST["nombre_PAC"];
        detalle_PAC_Profesional($conexion, $semana, $nombre_PAC, $db);
        break;
        
    case 'detalle_Pareto_CNC':
        $semana=$_GET["semana"];
        $nombre_PAC=/*"general"*/$_POST["nombre_PAC"];
        $clase_PAC=/*"general"*/$_POST["clase_PAC"];
        $tipo_CNC=/*"Diseños"*/$_POST["tipo_CNC"];
        if($clase_PAC=="general"){
            $script=""; 
        }else if($clase_PAC=="subcontratista"){
            $script="AND Sub_Contratista='$nombre_PAC' ";
        }else if($clase_PAC=="profesional"){
            $script="AND Responsable_AIA='$nombre_PAC' ";
        }
        detalle_Pareto_CNC($semana, $conexion, $nombre_PAC, $tipo_CNC, $clase_PAC, $script, $db);
        break;
    
    case 'detalle_CNC_semanales':
        $semana=$_POST["semana"];
        $nombre_PAC=/*"Daniel Sosa"*/$_POST["nombre_PAC"];
        $clase_PAC=/*"profesional"*/$_POST["clase_PAC"];
        $tipo_CNC=/*"Materiales"*/$_POST["tipo_CNC"];
        if($clase_PAC=="general"){
            $script=""; 
        }else if($clase_PAC=="subcontratista"){
            $script="AND Sub_Contratista='$nombre_PAC' ";
        }else if($clase_PAC=="profesional"){
            $script="AND Responsable_AIA='$nombre_PAC' ";
        }
        detalle_CNC_semanales($semana, $conexion, $nombre_PAC, $tipo_CNC, $clase_PAC, $script, $db);
        break;
        
    case 'detalle_compromisos':
        $semana=/*1*/$_POST["semana"];
        $tipo_compromiso=/*"no_criticas"*/$_POST["tipo_compromiso"];
        $nombre_PAC=/*"general"*/$_POST["nombre_PAC"];
        $clase_PAC=/*"general"*/$_POST["clase_PAC"];
        if($tipo_compromiso=="criticas"){
            if($clase_PAC=="general"){
                $script=""; 
            }else if($clase_PAC=="subcontratista"){
                $script="";
            }else if($clase_PAC=="profesional"){
                $script="AND Responsable_AIA='$nombre_PAC' ";
            }
            
            $script .="AND Critica=1 AND Activa='0' ";
            detalle_compromisos($semana, $conexion, $tipo_compromiso, $script, $db);
        }else if($tipo_compromiso=="no_criticas"){
            if($clase_PAC=="general"){
                $script=""; 
            }else if($clase_PAC=="subcontratista"){
                $script="";
            }else if($clase_PAC=="profesional"){
                $script="AND Responsable_AIA='$nombre_PAC' ";
            }
            
            $script .="AND Critica=0 AND Activa='0' ";
            detalle_compromisos($semana, $conexion, $tipo_compromiso, $script, $db);
        }else if($tipo_compromiso=="atrasadas"){
            if($clase_PAC=="general"){
                $script=""; 
            }else if($clase_PAC=="subcontratista"){
                $script="";
            }else if($clase_PAC=="profesional"){
                $script="AND Responsable_AIA='$nombre_PAC' ";
            }
            
            $script .="AND Atrasada=1 AND Activa='0' ";
            detalle_compromisos($semana, $conexion, $tipo_compromiso, $script, $db);
        }else if($tipo_compromiso=="comp_sin_restr"){
            if($clase_PAC=="general"){
                $script=""; 
            }else if($clase_PAC=="subcontratista"){
                $script="";
            }else if($clase_PAC=="profesional"){
                $script="AND Responsable_AIA='$nombre_PAC' ";
            }
            
            $script .="AND Prog_Sin_Restricciones_100=1 AND Activa='1' ";
            detalle_compromisos_sin_rest($semana, $conexion, $tipo_compromiso, $script, $db);
        }
        
        break;
        
    case 'detalle_restricciones':
        $semana=/*3*/$_POST["semana"];
        $selection=/*1*/$_POST["selection"];
        $comienzan_en=/*4*/$_POST["comienzan_en"];

        if($selection==0){
            $script="AND Estado_Restricciones=0 ";
            detalle_restricciones($semana, $conexion, $comienzan_en, $script, $db);
        }else if($selection==1){
            $script="AND (Estado_Restricciones<1 AND Estado_Restricciones>0) ";
            detalle_restricciones($semana, $conexion, $comienzan_en, $script, $db);
        }else if($selection==2){
            $script="AND Estado_Restricciones=1 ";
            detalle_restricciones($semana, $conexion, $comienzan_en, $script, $db);
        }
        
        break;
        
    case 'calificacion_contratistas':
        $semana=/*2*/$_GET["semana"];
        $subcontratista=/*"H.A"*/$_POST["subcontratista"];
        $tipo_calificacion=/*"integral_acumulada"*/$_POST["tipo_calificacion"];

        if($tipo_calificacion=="integral_acumulada"){
            $script="WHERE subcontratista='$subcontratista' AND Semana<=$semana ";
            detalle_calificacion_contratistas($semana, $conexion, $script, $db);
        }else if($tipo_calificacion=="integral_ultima_sem"){
            $script="WHERE subcontratista='$subcontratista' AND Semana=$semana ";
            detalle_calificacion_contratistas($semana, $conexion, $script, $db);
        }
        
        break;
        
    case 'calificacion_profesionales':
        $semana=/*2*/$_GET["semana"];
        $profesional=/*"Juan Camilo Escobar"*/$_POST["profesional"];
        $tipo_calificacion=/*"integral_acumulada"*/$_POST["tipo_calificacion"];

        if($tipo_calificacion=="integral_acumulada"){
            $script="WHERE profesional='$profesional' AND Semana<=$semana ";
            detalle_calificacion_profesionales($semana, $conexion, $script, $db);
        }else if($tipo_calificacion=="integral_ultima_sem"){
            $script="WHERE profesional='$profesional' AND Semana=$semana ";
            detalle_calificacion_profesionales($semana, $conexion, $script, $db);
        }
        
        break;
}

function detalle_PAC_General($conexion, $semana, $db){
    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana='$semana' AND (Activa='1' OR Activa='NA')";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Consecutivo" => "","Id" => "","Actividad" => "","Prog_Sin_Restricciones_100" => "","Descripcion" => "","Clase" => "","Sub_Contratista" => "","Responsable_AIA" => "", "Unidad" => "","Compromiso" => "","Ejecutado_Real" => "","P_Completado" => "","PAC" => "","Activa" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programacion_semanal WHERE Semana='$semana' AND (Activa='1' OR Activa='NA')";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
            $arreglo1["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo1, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_PAC_Subcontratista($conexion, $semana, $nombre_PAC, $db){
    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana='$semana' AND Sub_Contratista='$nombre_PAC' AND (Activa='1' OR Activa='NA')";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Consecutivo" => "","Id" => "","Actividad" => "","Prog_Sin_Restricciones_100" => "","Descripcion" => "","Clase" => "","Sub_Contratista" => "","Responsable_AIA" => "", "Unidad" => "","Compromiso" => "","Ejecutado_Real" => "","P_Completado" => "","PAC" => "","Activa" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programacion_semanal WHERE Semana='$semana' AND Sub_Contratista='$nombre_PAC' AND (Activa='1' OR Activa='NA')";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
            $arreglo1["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo1, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_PAC_Profesional($conexion, $semana, $nombre_PAC, $db){
    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana='$semana' AND Responsable_AIA='$nombre_PAC' AND (Activa='1' OR Activa='NA')";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Consecutivo" => "","Id" => "","Actividad" => "","Prog_Sin_Restricciones_100" => "","Descripcion" => "","Clase" => "","Sub_Contratista" => "","Responsable_AIA" => "", "Unidad" => "","Compromiso" => "","Ejecutado_Real" => "","P_Completado" => "","PAC" => "","Activa" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programacion_semanal WHERE Semana='$semana' AND Responsable_AIA='$nombre_PAC' AND (Activa='1' OR Activa='NA')";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
            $arreglo1["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo1, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_Pareto_CNC($semana, $conexion, $nombre_PAC, $tipo_CNC, $clase_PAC, $script, $db){
    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana<=$semana AND Categoria_CNC='$tipo_CNC' $script AND (Activa='1' OR Activa='NA')";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Semana" => "","Id" => "","Actividad" => "","Descripcion" => "","Clase" => "","Sub_Contratista" => "","Responsable_AIA" => "", "Unidad" => "","Compromiso" => "","Ejecutado_Real" => "","CNC"=>"","Observaciones_CNC"=>"","Activa" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programacion_semanal WHERE Semana<=$semana AND Categoria_CNC='$tipo_CNC' $script AND (Activa='1' OR Activa='NA')";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
            $arreglo1["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo1, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_CNC_semanales($semana, $conexion, $nombre_PAC, $tipo_CNC, $clase_PAC, $script, $db){
    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Categoria_CNC='$tipo_CNC' $script AND (Activa='1' OR Activa='NA')";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Semana" => "","Id" => "","Actividad" => "","Descripcion" => "","Clase" => "","Sub_Contratista" => "","Responsable_AIA" => "", "Unidad" => "","Compromiso" => "","Ejecutado_Real" => "","CNC"=>"","Observaciones_CNC"=>"","Activa" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programacion_semanal WHERE Semana=$semana AND Categoria_CNC='$tipo_CNC' $script AND (Activa='1' OR Activa='NA')";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
            $arreglo1["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo1, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_compromisos($semana, $conexion, $tipo_compromiso, $script, $db){
    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana $script";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Semana" => "","Id" => "","Actividad" => "","Descripcion" => "","Clase" => "", "Prog_Sin_Restricciones_100" => "", "Responsable_AIA" =>"", "Categoria_CNP"=>"","CNP"=>"","Observaciones_CNP" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programacion_semanal WHERE Semana=$semana $script";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
            $arreglo1["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo1, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_compromisos_sin_rest($semana, $conexion, $tipo_compromiso, $script, $db){
    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana $script";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Semana" => "","Id" => "","Actividad" => "","Descripcion" => "","Clase" => "","Estado_Restricciones"=>"","D_y_E"=>"","Materiales" => "","MdeO" => "","Equipos" => "","Predecesora" => "","Pdto_Cons" => "","Modelo" => "","Responsable_AIA" => "","Observaciones" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT Consecutivo_En_Programa FROM $db"."_programacion_semanal WHERE Semana=$semana $script";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
                $consecutivo=$data["Consecutivo_En_Programa"];
                $query2="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_En_Programa=$consecutivo";
                $resultado2 = mysqli_query($conexion, $query2);
                $data2=mysqli_fetch_assoc($resultado2);
                $arreglo2["data"][]=array_map("utf8_encode", $data2);
            }
            $json_codificado = json_encode($arreglo2, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_restricciones($semana, $conexion, $comienzan_en, $script, $db){
    $query="SELECT  COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio='$comienzan_en' $script";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Semana" => "","Id" => "","Actividad" => "","Descripcion" => "","Clase" => "","Ruta_Critica" => "","Estado_Restricciones" => "","D_y_E" => "","Materiales" => "","MdeO" => "","Equipos" => "","Predecesora" => "","Pdto_Cons" => "","Modelo" => "","Responsable_AIA" => "","Observaciones" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio='$comienzan_en' $script";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
                $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_calificacion_contratistas($semana, $conexion, $script, $db){
    $query="SELECT  COUNT(*) FROM $db"."_cic $script";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Semana" => "","Id" => "","subcontratista" => "","alcance" => "","tipo_proveedor" => "","PAC" => "","P_Completado" => "","Calidad" => "","GSA" => "","SST" => "","ADM" => "","Cal_Integral" => "","Observaciones" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT  * FROM $db"."_cic $script";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
                $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}

function detalle_calificacion_profesionales($semana, $conexion, $script, $db){
    $query="SELECT  COUNT(*) FROM $db"."_cip $script";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo["data"][]=array("Semana" => "","Id" => "","profesional" => "","PAC" => "","P_Completado" => "","Act_Criticas_Cumplidas" => "","Act_No_Criticas_Cumplidas" => "","Act_Atrasadas_Cumplidas" => "","PAC_Consolidado" => ""); 
        echo json_encode($arreglo);
    } else{
        require ("../conexion.php");
        $query1 = "SELECT  * FROM $db"."_cip $script";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
                $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
}
    





?>