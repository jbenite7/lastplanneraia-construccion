<?php
    require("../conexion.php");
function modificar_semanas($conexion){
    
    $query="SELECT * FROM Programa";
    $resultado= mysqli_query($conexion, $query);
	if(!$resultado){
        die("Error");
    } else{
        $query2='';
        while($data=mysqli_fetch_array($resultado)){
            $Id=$data["Id"];
            $actividad=$data["Actividad"];
            $hoy= date('Y-m-d');
            $mañana= date("Y-m-d",strtotime($data["Fecha_Inicio"]));
            $dias=(strtotime($mañana)-strtotime($hoy))/86400;
            $dias=floor($dias);








            $Estado_Restricciones=round((($D_y_E2+$Materiales2+$MdeO2+$Equipos2+$Predecesora2+$Pdto_Cons2+$Modelo2)/7),5);
            echo $Estado_Restricciones;
            if($dias<0 || $dias==-0){
                $dias=0;
            }
            $query2 .="UPDATE programa SET Dias_Inicio='$semanas' WHERE Titulo=0 AND ID='$Id' AND Actividad='$actividad';";
            $query2 .="UPDATE programa SET Estado_Restricciones=$Estado_Restricciones WHERE Titulo=0 AND ID='$Id' AND Actividad='$actividad';";
        }
    };
    mysqli_free_result($resultado);
    $resultado=mysqli_multi_query($conexion, $query2); 
}

modificar_semanas($conexion);

?>