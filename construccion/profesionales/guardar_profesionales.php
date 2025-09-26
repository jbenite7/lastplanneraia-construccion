<?php

require("../conexion.php");

$db=/*"reserva_de_modelia"*/$_GET['db'];
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
    mysqli_close($conexion);

}else if($opcion=="modificar"){
    $Id=$_POST['Id'];

    $Nombre = (empty($_POST['Nombre'])) ? '' : filter_var(($_POST['Nombre']), FILTER_SANITIZE_STRING);

    $Correo = (empty($_POST['Correo'])) ? '' : filter_var(($_POST['Correo']), FILTER_SANITIZE_EMAIL);

    $Cargo = (empty($_POST['Cargo'])) ? '' : filter_var(($_POST['Cargo']), FILTER_SANITIZE_STRING);

    $Activo=$_POST['Activo'];

    $errores='';

    if(empty($Nombre) or  empty($Correo) or empty($Cargo)){
        $errores .='Debe rellenar todos los campos';
        $resultado=false;
    } else {
        if($errores==''){
            $query = "SELECT (nombre) FROM $db"."_profesionales WHERE id=$Id";
            $resultado= mysqli_query($conexion, $query);
            $data=mysqli_fetch_assoc($resultado);
            $Nombre_Anterior=$data["nombre"];

            $query1 = "UPDATE $db"."_profesionales SET nombre='$Nombre', email='$Correo', cargo='$Cargo', activo='$Activo' WHERE id=$Id";

            $resultado1= mysqli_query($conexion, $query1);

            $query2 = "UPDATE $db"."_programacion_semanal SET Responsable_AIA='$Nombre' WHERE Responsable_AIA='$Nombre_Anterior';UPDATE $db"."_programa_consolidado SET Responsable_AIA='$Nombre' WHERE Responsable_AIA='$Nombre_Anterior';UPDATE $db"."_cip SET profesional='$Nombre' WHERE profesional='$Nombre_Anterior';UPDATE $db"."_indicadores_generales SET subcontratista_profesional='$Nombre' WHERE subcontratista_profesional='$Nombre_Anterior';";

            $resultado2= mysqli_multi_query($conexion, $query2);
        }
    }
    verificar_resultado($resultado1, $errores);
    mysqli_close($conexion);


}else if($opcion=="eliminar"){
    $Id=/*4*/$_POST["Id"];
    eliminar($Id, $db, $conexion);
}

function eliminar($Id, $db, $conexion){
    $query="SELECT (nombre) FROM $db"."_profesionales WHERE id=$Id";
    $resultado=mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $profesional=$data["nombre"];

    $query1 = "SELECT COUNT(*) FROM $db"."_cip WHERE profesional='$profesional'";
    $resultado1=mysqli_query($conexion, $query1);
    $data1=mysqli_fetch_assoc($resultado1);
    $conteo_cip=$data1["COUNT(*)"];

    if($conteo_cip>0){
        $errores='No se puede eliminar este profesional';
    }else{
        $query2="DELETE FROM $db"."_profesionales WHERE id=$Id";
        $resultado2=mysqli_query($conexion, $query2);
        $errores='';
    }


    verificar_resultado($resultado1="OK", $errores);


    mysqli_close($conexion);

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
    if ($errores=='No se puede eliminar este profesional'){
        $informacion["respuesta"] = "NO_ELIMINAR";
    }

    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}





?>
