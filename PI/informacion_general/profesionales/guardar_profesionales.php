<?php

require("../../conexion.php");

$db=/*"proyectos_inmobiliarios"*/$_GET['db'];
$opcion=/*"nueva_sem"*/$_POST["opcion"];
$informacion=[];
if ($_SERVER['REQUEST_METHOD']== 'POST' && $opcion == "registrar"){
    
    if(empty($_POST['Nombre'])){
        $nombre='';
    } else{
        $nombre=/*"Juan Benitez"*/filter_var(($_POST['Nombre']), FILTER_SANITIZE_STRING);       
    }
    
    if (empty($_POST['Correo'])){
        $email='';
    } else{
        $email=/*"jbenitez@aia.com.co"*/filter_var(($_POST['Correo']), FILTER_SANITIZE_EMAIL); 
    }
   
   if(empty($_POST['Confirmar_Correo'])){
       $confirmar_correo='';  
   } else{
       $confirmar_correo=/*"jbenitez@aia.com.co"*/filter_var(($_POST['Confirmar_Correo']), FILTER_SANITIZE_EMAIL);
   }
    
    if(empty($_POST['Cargo'])){
        $cargo='';
    } else{
        $cargo=/*"jbenitez@aia.com.co"*/filter_var(($_POST['Cargo']), FILTER_SANITIZE_STRING);   
    }
    

    $errores='';
    
    if(empty($nombre) or  empty($email) or empty($confirmar_correo) or empty($cargo)){
        $errores .='Debe rellenar todos los campos';
        $resultado=false;
    } else {
        $query= "SELECT * FROM $db"."_profesionales WHERE email = '$confirmar_correo' OR email = '$email' LIMIT 1";
        
        
        $resultado= mysqli_query($conexion, $query);
        $resultado=mysqli_fetch_assoc($resultado);

        
        if($email <> $confirmar_correo){
           $errores .= 'Por favor confirmar correctamente la dirección de correo';
        }else if($resultado){
            $errores .= 'El usuario que estás intentando registrar ya existe';
        }
        
        if($errores==''){
            $query = "INSERT INTO $db"."_profesionales (id, nombre, email, cargo) VALUES (null, '$nombre', '$confirmar_correo', '$cargo')";
            $resultado= mysqli_query($conexion, $query);
        }
    }
    
    verificar_resultado($resultado, $errores);
    cerrar($conexion);

} else if($opcion=="nueva_sem"){
    $errores="";
    $f_inicio_sem=/*date("Y-m-d",strtotime("2020-07-13"));*/date("Y-m-d",strtotime($_POST["f_inicio_sem"])); 
    nueva_sem($f_inicio_sem, $db, $conexion);
} else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
    eliminar_sem($semana, $db, $conexion);
};

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../../funciones_generales/nueva_semana.php");
    mysqli_close($conexion);
    require("../../conexion.php");
    require("../../funciones_generales/modificar_sem_estado.php");
}

function activar_checklists($semana, $db, $conexion){
    $query = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND (Categoria = 'tramites' OR Categoria = 'consultores' OR Categoria = 'periodicas_compuestas')";
    require("../../conexion.php");
    $resultado=mysqli_query($conexion, $query);
    
    while($data=mysqli_fetch_assoc($resultado)){
        $consecutivo = $data["Consecutivo_en_Programa"];
        $checklist = $data["Checklist"];
        
        if($checklist==NULL){
        }else{
            require("../../conexion.php");
            $query1 = "SELECT MAX(Consecutivo_Requerimiento) FROM $db"."_checklists WHERE Codigo_Tarea=$checklist";
            $resultado1=mysqli_query($conexion, $query1);
            $data1=mysqli_fetch_assoc($resultado1);
            $requerimientos=$data1["MAX(Consecutivo_Requerimiento)"];

            $query2 = "SELECT ";
            for($i=1; $i<=$requerimientos; $i++){

                require("../../conexion.php");
                $query2 .= "(SELECT CASE WHEN R$i = 'NA' THEN 0 ELSE R$i END) AS 'valor$i'";
                if($i<$requerimientos){
                    $query2 .=", ";
                }
            }
            $query2 .= " FROM $db"."_programa_consolidado WHERE Consecutivo_en_Programa = $consecutivo";

            $resultado2=mysqli_query($conexion, $query2);
            $data2=mysqli_fetch_assoc($resultado2);

            $query3 = "UPDATE $db"."_programa_consolidado SET ";
            for($i=1; $i<=$requerimientos; $i++){
                $valor = $data2["valor$i"];

                require("../../conexion.php");
                $query3 .= "R$i = $valor, ";
            }
            $query3 .="Estado_Restricciones=0 WHERE Consecutivo_en_Programa = $consecutivo"; 
            $resultado3=mysqli_query($conexion, $query3);
        }
    }
    $query4 = "UPDATE $db"."_programa_consolidado SET Estado_Restricciones=1 WHERE Categoria = 'periodicas_simples' OR Categoria = 'propias' OR ((Categoria = 'tramites' OR Categoria = 'consultores' OR Categoria = 'periodicas_compuestas') AND Checklist='')";
    $resultado4=mysqli_query($conexion, $query4);
}

function eliminar_sem($semana, $db, $conexion){    
    require("../../funciones_generales/eliminar_semana.php");
}


function verificar_resultado($resultado, $errores){
    if(!$resultado){
        $informacion["respuesta"] ="ERROR";
    }
    if($errores ==''){ 
        $informacion["respuesta"] = "BIEN";        
    }
    if ($errores=='Debe rellenar todos los campos'){
        $informacion["respuesta"] = "VACIO";
    }
    if ($errores=='El usuario que estás intentando registrar ya existe'){
        $informacion["respuesta"] = "EXISTE";
    }
    if ($errores=='Por favor confirmar correctamente la dirección de correo'){
        $informacion["respuesta"] = "CONFIRMAR";
    }
    
    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}





?>