<?php session_start();
    $_SESSION['usuario'] = "ok";
// Establecer tiempo de vida de la sesión en segundos
    $inactividad = 3600
        ;
    // Comprobar si $_SESSION["timeout"] está establecida
    if(isset($_SESSION["timeout"])){
        // Calcular el tiempo de vida de la sesión (TTL = Time To Live)
        $sessionTTL = time() - $_SESSION["timeout"];
        if($sessionTTL > $inactividad){
            echo "<script> alert('Se cerrará la sesión por un tiempo de inactividad mayor a 1 hora.');
                            window.location.href='../cerrar.php';</script>";
        }
    }

if (isset($_SESSION['usuario'])) {
    $db=$_GET['b'];
    $_SESSION['db']=$db;
    //$semana=$_GET['w'];
    $_SESSION['semana']=$semana;

    require ("../conexion.php");
    $query="SELECT Semana, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, ROUND(Ejecutado*100,0) AS Ejecutado FROM $db"."_programa_consolidado WHERE Semana=(SELECT MAX(Semana) FROM $db"."_programa_consolidado)";
    $resultado= mysqli_query($conexion, $query);
    $table = '<table><tbody><tr><td>Semana</td><td>Id</td><td>Actividad</td><td>Titulo</td><td>Fecha_Inicio</td><td>Fecha_Fin</td><td>Ruta_Critica</td><td>Ejecutado</td></tr>';
    while($data=mysqli_fetch_assoc($resultado)){
        $Semana=$data["Semana"];
        $Id=$data["Id"];
        $Actividad=utf8_decode($data["Actividad"]);
        $Actividad=str_replace("<small>","",$Actividad);
        $Actividad=str_replace("</small>","",$Actividad);
        $Actividad=str_replace("<b>","",$Actividad);
        $Actividad=str_replace("</b>","",$Actividad);
        $data["Actividad"]=$Actividad;
        $Fecha_Inicio=$data["Fecha_Inicio"];
        $Fecha_Fin=$data["Fecha_Fin"];
        $Ejecutado=$data["Ejecutado"];
        $Titulo=$data["Titulo"];
        $Ruta_Critica=$data["Ruta_Critica"];
        if($Ejecutado=="" || $Ejecutado==null){

        }else{
            $Ejecutado="$Ejecutado%";
        }

        if($Ruta_Critica == 1){
            $Ruta_Critica="Ruta critica";
        }else{
            $Ruta_Critica="Actividades NO criticas";
        }
        $data["Ejecutado"]=$Ejecutado;

        $table.= "<tr><td>$Semana</td><td>$Id</td><td>$Actividad</td><td>$Titulo</td><td>$Fecha_Inicio</td><td>$Fecha_Fin</td><td>$Ruta_Critica</td><td>$Ejecutado</td></tr>";

        //$arreglo[]=array_map("utf8_encode", $data);
    }

    $table.="</tbody></table>";
    //echo $table;
    $nombre=$db . "_semana_" . $Semana . ".xls";
    header('Content-Encoding: UTF-8');
    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
    header ("Cache-Control: no-cache, must-revalidate");
    header ("Pragma: no-cache");
    header ("Content-type: application/x-msexcel;charset=UTF-8");
    header ("Content-Disposition: attachment; filename=$nombre" );

    echo $table;

    //$json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
    //echo utf8_decode($json_codificado);

    // El siguiente key se crea cuando se inicia sesión
    $_SESSION["timeout"] = time();

} else {
    header('Location: ../login/login.php');
}

session_destroy();

?>
