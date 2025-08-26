<?php
require("../conexion.php");

$db=/*"bts_toberin"*/$_GET['db'];
$opcion=/*"eliminar"*/$_POST["opcion"];
$informacion=[];

if ($_SERVER['REQUEST_METHOD']== 'POST' && $opcion == "registrar"){

    if(empty($_POST['Subcontratista'])){
        $Subcontratista='';
    } else{
        $Subcontratista=filter_var(($_POST['Subcontratista']), FILTER_SANITIZE_STRING);
    }

    if (empty($_POST['Correo'])){
        $email='';
    } else{
        $email=filter_var(($_POST['Correo']), FILTER_SANITIZE_EMAIL);
    }

   if(empty($_POST['Confirmar_Correo'])){
       $confirmar_correo='';
   } else{
       $confirmar_correo=filter_var(($_POST['Confirmar_Correo']), FILTER_SANITIZE_EMAIL);
   }

    if(empty($_POST['NIT'])){
        $NIT='';
    } else{
        $NIT=$_POST['NIT'];
    }

    if(empty($_POST['alcance'])){
        $alcance='';
    } else{
        $alcance=$_POST['alcance'];
    }

    if(empty($_POST['tipo_proveedor'])){
        $tipo_proveedor='';
    } else{
        $tipo_proveedor=$_POST['tipo_proveedor'];
    }

    $errores='';

    if(empty($Subcontratista) or  empty($email) or empty($confirmar_correo) or empty($NIT) or empty($alcance)){
        $errores ='Debe rellenar todos los campos';
        $resultado=false;

    } else {
        $query= "SELECT COUNT(*) FROM $db"."_subcontratistas WHERE subcontratista = '$Subcontratista' LIMIT 1";
        $resultado= mysqli_query($conexion, $query);
        $data=mysqli_fetch_assoc($resultado);
        $conteoSubcontratista = $data["COUNT(*)"];

        $query1= "SELECT COUNT(*) FROM $db"."_subcontratistas WHERE NIT = '$NIT' LIMIT 1";
        $resultado1= mysqli_query($conexion, $query1);
        $data1=mysqli_fetch_assoc($resultado1);
        $conteoNIT = $data1["COUNT(*)"];


        if($email <> $confirmar_correo){
           $errores = 'Por favor confirmar correctamente la dirección de correo';
        }else{
          if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errores = 'Por favor asignar una dirección de correo real (ej. correo@aia.com.co)';
          }
        }

        if($conteoSubcontratista > 0){
          $errores = 'Ya existe un contratista con ese nombre.';
        }

        if($conteoNIT > 0){
          $errores = 'Ya existe un contratista con ese NIT.';
        }

        $longitudNIT = strlen((string)abs($NIT));
        if($longitudNIT < 7 || $longitudNIT > 10){
          $errores = 'Digite un NIT completo.';
        }

        $arrayNIT = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "00", "11", "22", "33", "44", "55", "66", "77", "88", "99", "000", "111", "222", "333", "444", "555", "666", "777", "888", "999", "0000", "1111", "2222", "3333", "4444", "5555", "6666", "7777", "8888", "9999", "00000", "11111", "22222", "33333", "44444", "55555", "66666", "77777", "88888", "99999", "000000", "111111", "222222", "333333", "444444", "555555", "666666", "777777", "888888", "999999", "0000000", "1111111", "2222222", "3333333", "4444444", "5555555", "6666666", "7777777", "8888888", "9999999", "00000000", "11111111", "22222222", "33333333", "44444444", "55555555", "66666666", "77777777", "88888888", "99999999", "000000000", "111111111", "222222222", "333333333", "444444444", "555555555", "666666666", "777777777", "888888888", "999999999", "0000000000", "1111111111", "2222222222", "3333333333", "4444444444", "5555555555", "6666666666", "7777777777", "8888888888", "9999999999", "12", "123", "1234", "12345", "123456", "1234567", "12345678", "123456789", "1234567890", "01", "012", "0123", "01234", "012345", "0123456", "01234567", "012345678", "0123456789");
        if(in_array((string)$NIT, $arrayNIT)){
          $errores = 'Digite un NIT correcto.';
        }

        if($errores==''){
            $query = "INSERT INTO $db"."_subcontratistas (id, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor) VALUES (null, '$Subcontratista', '$confirmar_correo', $NIT, '$alcance', '$tipo_proveedor')";

            $resultado= mysqli_query($conexion, $query);
        }
    }

    verificar_resultado($resultado, $errores);
    mysqli_close($conexion);


}else if($opcion=="modificar"){
    $Id=$_POST['Id'];

    if(empty($_POST['Subcontratista'])){
        $Subcontratista='';
    } else{
        $Subcontratista=filter_var(($_POST['Subcontratista']), FILTER_SANITIZE_STRING);
    }

    if (empty($_POST['Correo_Contacto'])){
        $email='';
    } else{
        $email=filter_var(($_POST['Correo_Contacto']), FILTER_SANITIZE_EMAIL);
    }

    if(empty($_POST['NIT'])){
        $NIT='';
    } else{
        $NIT=$_POST['NIT'];
    }

    if(empty($_POST['Alcance'])){
        $alcance='';
    } else{
        $alcance=$_POST['Alcance'];
    }

    if(empty($_POST['Tipo_Proveedor'])){
        $tipo_proveedor='';
    } else{
        $tipo_proveedor=$_POST['Tipo_Proveedor'];
    }

    $Activo=$_POST['Activo'];

    $errores='';

    if(empty($Subcontratista) or  empty($email) or empty($NIT) or empty($alcance) or empty($tipo_proveedor)){
        $errores .='Debe rellenar todos los campos';
        $resultado=false;

    } else {
        if($errores==''){
            $query = "SELECT (subcontratista) FROM $db"."_subcontratistas WHERE id=$Id";
            $resultado= mysqli_query($conexion, $query);
            $data=mysqli_fetch_assoc($resultado);
            $Subcontratista_Anterior=$data["subcontratista"];

            $query1= "SELECT COUNT(*) FROM $db"."_subcontratistas WHERE subcontratista = '$Subcontratista' AND id != $Id LIMIT 1";
            $resultado1= mysqli_query($conexion, $query1);
            $data1=mysqli_fetch_assoc($resultado1);
            $conteoSubcontratista = $data1["COUNT(*)"];

            $query2= "SELECT COUNT(*) FROM $db"."_subcontratistas WHERE NIT = '$NIT'  AND id != $Id LIMIT 1";
            $resultado2= mysqli_query($conexion, $query2);
            $data2=mysqli_fetch_assoc($resultado2);
            $conteoNIT = $data2["COUNT(*)"];

            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
              $errores = 'Por favor asignar una dirección de correo real (ej. correo@aia.com.co)';
            }

            if($conteoSubcontratista > 0){
              $errores = 'Ya existe un contratista con ese nombre.';
            }

            if($conteoNIT > 0){
              $errores = 'Ya existe un contratista con ese NIT.';
            }

            $longitudNIT = strlen((string)abs($NIT));
            if($longitudNIT < 7 || $longitudNIT > 10){
              $errores = 'Digite un NIT completo.';
            }

            $arrayNIT = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "00", "11", "22", "33", "44", "55", "66", "77", "88", "99", "000", "111", "222", "333", "444", "555", "666", "777", "888", "999", "0000", "1111", "2222", "3333", "4444", "5555", "6666", "7777", "8888", "9999", "00000", "11111", "22222", "33333", "44444", "55555", "66666", "77777", "88888", "99999", "000000", "111111", "222222", "333333", "444444", "555555", "666666", "777777", "888888", "999999", "0000000", "1111111", "2222222", "3333333", "4444444", "5555555", "6666666", "7777777", "8888888", "9999999", "00000000", "11111111", "22222222", "33333333", "44444444", "55555555", "66666666", "77777777", "88888888", "99999999", "000000000", "111111111", "222222222", "333333333", "444444444", "555555555", "666666666", "777777777", "888888888", "999999999", "0000000000", "1111111111", "2222222222", "3333333333", "4444444444", "5555555555", "6666666666", "7777777777", "8888888888", "9999999999", "12", "123", "1234", "12345", "123456", "1234567", "12345678", "123456789", "1234567890", "01", "012", "0123", "01234", "012345", "0123456", "01234567", "012345678", "0123456789");
            if(in_array((string)$NIT, $arrayNIT)){
              $errores = 'Digite un NIT correcto.';
            }

            if($errores != ''){
            }else{
              $query3 = "UPDATE $db"."_subcontratistas SET subcontratista='$Subcontratista', correo_contacto='$email', NIT=$NIT, alcance='$alcance', tipo_proveedor='$tipo_proveedor', activo='$Activo' WHERE id=$Id";

              $resultado3= mysqli_query($conexion, $query3);

              $query4 = "UPDATE $db"."_programacion_semanal SET Sub_Contratista='$Subcontratista' WHERE Sub_Contratista='$Subcontratista_Anterior';UPDATE $db"."_programa_consolidado SET Sub_Contratista='$Subcontratista' WHERE Sub_Contratista='$Subcontratista_Anterior';UPDATE $db"."_cic SET subcontratista='$Subcontratista' WHERE subcontratista='$Subcontratista_Anterior';UPDATE $db"."_indicadores_generales SET subcontratista_profesional='$Subcontratista' WHERE subcontratista_profesional='$Subcontratista_Anterior';";

              $resultado4= mysqli_multi_query($conexion, $query4);
            }

        }
    }

    verificar_resultado($resultado1, $errores);
    mysqli_close($conexion);


}else if($opcion=="eliminar"){
  $Id=/*4*/$_POST["Id"];
  eliminar($Id, $db, $conexion);
}

function eliminar($Id, $db, $conexion){
    $query="SELECT (subcontratista) FROM $db"."_subcontratistas WHERE Id=$Id";
    $resultado=mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $subcontratista=$data["subcontratista"];

    $query1 = "SELECT COUNT(*) FROM $db"."_cic WHERE subcontratista='$subcontratista'";
    $resultado1=mysqli_query($conexion, $query1);
    $data1=mysqli_fetch_assoc($resultado1);
    $conteo_cic=$data1["COUNT(*)"];

    if($conteo_cic>0){
        $errores='No se puede eliminar este subcontratista';
    }else{
        $query2="DELETE FROM $db"."_subcontratistas WHERE Id=$Id";
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
    if ($errores=='Por favor asignar una dirección de correo real (ej. correo@aia.com.co)'){
        $informacion["respuesta"] = "NO_CORREO";
    }
    if ($errores=='Ya existe un contratista con ese nombre.'){
        $informacion["respuesta"] = "NOMBRE_REPETIDO";
    }
    if ($errores=='Ya existe un contratista con ese NIT.'){
        $informacion["respuesta"] = "NIT_REPETIDO";
    }
    if ($errores=='Digite un NIT completo.'){
        $informacion["respuesta"] = "NIT_INCOMPLETO";
    }
    if ($errores=='Digite un NIT correcto.'){
        $informacion["respuesta"] = "NIT_INCORRECTO";
    }
    if ($errores=='No se puede eliminar este subcontratista'){
        $informacion["respuesta"] = "NO_ELIMINAR";
    }

    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}
?>
