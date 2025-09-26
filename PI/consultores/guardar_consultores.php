<?php


require("../conexion.php");

$db=/*"proyectos_inmobiliarios"*/$_GET['db'];
$opcion=/*"modificar_checklist"*/$_POST["opcion"];
$informacion=[];

if ($opcion == "checklist") {
    $Id=/*15*/$_POST["Id"];
    $semana=/*1*/$_POST["semana"];
    $checklist=/*5*/$_POST["checklist"]; 
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
} else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
} else if($opcion=="CNC"){
    $categoria=$_POST["categoria"];
} else if($opcion=="generar"){
    $semana=$_POST["semana"];
} else if($opcion=="nuevo_requerimiento"){
    $checklist=$_POST["checklist"];
    $descripcion=$_POST["descripcion"];
    $semana=$_POST["semana"];
    $Id=$_POST["Id"];
} else if($opcion=="modificar_checklist"){
    $Id=/*14 */$_POST["Id"];
    $semana=/*1 */$_POST["semana"];
    $Observaciones=/*"A" */$_POST["Observaciones"];
    $relevancia=$_POST["relevancia"];
    $ejecutado=$_POST["ejecutado"];
    $checklist=$_POST["checklist"];
    
    
    if(isset($_POST["R1"])){
        $R1= $_POST["R1"];
        $url_R1= $_POST["url_R1"];
    }else{
        $R1= 'NA';
        $url_R1= '';
    }
    if(isset($_POST["R2"])){
        $R2= $_POST["R2"];
        $url_R2= $_POST["url_R2"];
    }else{
        $R2= 'NA';
        $url_R2= '';
    }
    if(isset($_POST["R3"])){
        $R3= $_POST["R3"];
        $url_R3= $_POST["url_R3"];
    }else{
        $R3= 'NA';
        $url_R3= '';
    }
    if(isset($_POST["R4"])){
        $R4= $_POST["R4"];
        $url_R4= $_POST["url_R4"];
    }else{
        $R4= 'NA';
        $url_R4= '';
    }
    if(isset($_POST["R5"])){
        $R5= $_POST["R5"];
        $url_R5= $_POST["url_R5"];
    }else{
        $R5= 'NA';
        $url_R5= '';
    }
    if(isset($_POST["R6"])){
        $R6= $_POST["R6"];
        $url_R6= $_POST["url_R6"];
    }else{
        $R6= 'NA';
        $url_R6= '';
    }
    if(isset($_POST["R7"])){
        $R7= $_POST["R7"];
        $url_R7= $_POST["url_R7"];
    }else{
        $R7= 'NA';
        $url_R7= '';
    }
    if(isset($_POST["R8"])){
        $R8= $_POST["R8"];
        $url_R8= $_POST["url_R8"];
    }else{
        $R8= 'NA';
        $url_R8= '';
    }
    if(isset($_POST["R9"])){
        $R9= $_POST["R9"];
        $url_R9= $_POST["url_R9"];
    }else{
        $R9= 'NA';
        $url_R9= '';
    }
    if(isset($_POST["R10"])){
        $R10= $_POST["R10"];
        $url_R10= $_POST["url_R10"];
    }else{
        $R10= 'NA';
        $url_R10= '';
    }
    if(isset($_POST["R11"])){
        $R11= $_POST["R11"];
        $url_R11= $_POST["url_R11"];
    }else{
        $R11= 'NA';
        $url_R11= '';
    }
    if(isset($_POST["R12"])){
        $R12= $_POST["R12"];
        $url_R12= $_POST["url_R12"];
    }else{
        $R12= 'NA';
        $url_R12= '';
    }
    if(isset($_POST["R13"])){
        $R13= $_POST["R13"];
        $url_R13= $_POST["url_R13"];
    }else{
        $R13= 'NA';
        $url_R13= '';
    }
    if(isset($_POST["R14"])){
        $R14= $_POST["R14"];
        $url_R14= $_POST["url_R14"];
    }else{
        $R14= 'NA';
        $url_R14= '';
    }
    if(isset($_POST["R15"])){
        $R15= $_POST["R15"];
        $url_R15= $_POST["url_R15"];
    }else{
        $R15= 'NA';
        $url_R15= '';
    }
    if(isset($_POST["R16"])){
        $R16= $_POST["R16"];
        $url_R16= $_POST["url_R16"];
    }else{
        $R16= 'NA';
        $url_R16= '';
    }
    if(isset($_POST["R17"])){
        $R17= $_POST["R17"];
        $url_R17= $_POST["url_R17"];
    }else{
        $R17= 'NA';
        $url_R17= '';
    }
    if(isset($_POST["R18"])){
        $R18= $_POST["R18"];
        $url_R18= $_POST["url_R18"];
    }else{
        $R18= 'NA';
        $url_R18= '';
    }
    if(isset($_POST["R19"])){
        $R19= $_POST["R19"];
        $url_R19= $_POST["url_R19"];
    }else{
        $R19= 'NA';
        $url_R19= '';
    }
    if(isset($_POST["R20"])){
        $R20= $_POST["R20"];
        $url_R20= $_POST["url_R20"];
    }else{
        $R20= 'NA';
        $url_R20= '';
    }
    if(isset($_POST["R21"])){
        $R21= $_POST["R21"];
        $url_R21= $_POST["url_R21"];
    }else{
        $R21= 'NA';
        $url_R21= '';
    }
    if(isset($_POST["R22"])){
        $R22= $_POST["R22"];
        $url_R22= $_POST["url_R22"];
    }else{
        $R22= 'NA';
        $url_R22= '';
    }
    if(isset($_POST["R23"])){
        $R23= $_POST["R23"];
        $url_R23= $_POST["url_R23"];
    }else{
        $R23= 'NA';
        $url_R23= '';
    }
    if(isset($_POST["R24"])){
        $R24= $_POST["R24"];
        $url_R24= $_POST["url_R24"];
    }else{
        $R24= 'NA';
        $url_R24= '';
    }
    if(isset($_POST["R25"])){
        $R25= $_POST["R25"];
        $url_R25= $_POST["url_R25"];
    }else{
        $R25= 'NA';
        $url_R25= '';
    }
    
    /*$R1=1;
    $R2=0;
    $R3=1;
    $R4='NA';
    $R5='NA';
    $R6='NA';
    $R7='NA';
    $R8='NA';
    $R9='NA';
    $R10='NA';
    $R11='NA';
    $R12='NA';
    $R13='NA';
    $R14='NA';
    $R15='NA';
    $R16='NA';
    $R17='NA';
    $R18='NA';
    $R19='NA';
    $R20='NA';
    $R21='NA';
    $R22='NA';
    $R23='NA';
    $R24='NA';
    $R25='NA';*/
} else if($opcion=="comprometer_requerimiento"){
    $checklist=$_POST["checklist_requerimiento"];
    $requerimiento=$_POST["requerimiento"];
    $consecutivo_requerimiento=$_POST["consecutivo_requerimiento"];
    $semana=$_POST["semana"];
    $consecutivo=$_POST["id_tarea"];
}




switch($opcion){
    case 'checklist':
        generar_checklist($db, $Id, $checklist, $semana, $conexion);
        break;
        
    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;
    
    case 'eliminar_sem':
        eliminar_sem($semana, $db, $conexion);
        break;
    
    case 'CNC':
        CNC($categoria, $db, $conexion);
        break; 
        
    case 'generar':
        generar($semana, $db, $conexion);
        break;
        
    case 'nuevo_requerimiento':
        nuevo_requerimiento($Id, $semana, $checklist, $descripcion, $db, $conexion);
        break;
        
    case 'comprometer_requerimiento':
        comprometer_requerimiento($consecutivo, $semana, $checklist, $requerimiento, $consecutivo_requerimiento, $db, $conexion);
        break;
    
    case 'modificar_checklist':
        modificar_checklist($Id, $semana, $relevancia, $ejecutado, fecha_inicio_sem($semana, $db, $conexion), $R1, $R2, $R3, $R4, $R5, $R6, $R7, $R8, $R9, $R10, $R11, $R12, $R13, $R14, $R15, $R16, $R17, $R18, $R19, $R20, $R21, $R22, $R23, $R24, $R25, $checklist, $url_R1, $url_R2, $url_R3, $url_R4, $url_R5, $url_R6, $url_R7, $url_R8, $url_R9, $url_R10, $url_R11, $url_R12, $url_R13, $url_R14, $url_R15, $url_R16, $url_R17, $url_R18, $url_R19, $url_R20, $url_R21, $url_R22, $url_R23, $url_R24, $url_R25, $Observaciones, $db, $conexion);
        break;
        
}



function generar_checklist($db, $Id, $checklist, $semana, $conexion){
    $query= "SELECT (Relevancia)FROM $db"."_programa_consolidado WHERE Consecutivo_en_Programa=$Id AND Semana=$semana";
    $resultado= mysqli_query($conexion, $query);
    $data= mysqli_fetch_assoc($resultado);
    $relevancia=$data["Relevancia"];
    
    $cadena ="<div class='form_eval form-group'>
                    <h3 id='form_relevancia'>Relevancia</h3>
               </div>
               <div class='pregunta form-group' style='margin:auto; margin-top:10px'>
                    <p style='display:inline-block'>Indique la relevancia de la tarea:   </p>
                    <select name='relevancia' id='relevancia' style='width:100%; max-width:200px; height:30px; font-size:1em; text-align: center; padding-left:1px; padding-right:0px; margin:0px 2px; border-radius: 5px; border-style:solid; border-color:darkgrey'>
                      <option value='NA' selected>No Asignada</option> 
                      <option value=1>1</option>
                      <option value=2>2</option>
                      <option value=3>3</option>
                      <option value=4>4</option>
                      <option value=5>5</option>
                      <option value=6>6</option>
                      <option value=7>7</option>
                      <option value=8>8</option>
                      <option value=9>9</option>
                      <option value=10>10</option>
                    </select>
               </div>
               <div class='pregunta form-group' style='margin:auto; margin-top:10px'>
                    <p style='display:inline-block'>Indique el estado de ejecución:   </p>
                    <select name='ejecutado' id='ejecutado' style='width:100%; max-width:200px; height:30px; font-size:1em; text-align: center; padding-left:1px; padding-right:0px; margin:0px 2px; border-radius: 5px; border-style:solid; border-color:darkgrey'>
                      <option value=0 selected>No Iniciado</option> 
                      <option value=0.5>En Ejecución</option>
                      <option value=1>Terminado</option>
                    </select>
               </div>
                 ";
    
    $query1= "SELECT * FROM $db"."_checklists WHERE Codigo_Tarea=$checklist";
    //echo $query ."<br>";
    
    $resultado1= mysqli_query($conexion, $query1);
    if(!$resultado1){
        die("Error");
    } else{
        $cadena .="<div class='form_eval form-group'>
                    <h3 id='form_tarea'>Checklist</h3>
                 </div>";
        while($data1=mysqli_fetch_assoc($resultado1)){
            $requerimiento=$data1["Requerimiento"];
            $consecutivo_requerimiento=$data1["Consecutivo_Requerimiento"];
            $clase=$data1["clase"];
            $url=$data1["url"];
            $Semana_url=$data1["Semana_url"];
            $cadena .= "<div class='pregunta form-group'>
                            <div class='cuerpo_pregunta' style='width:73%; padding:0; margin:0; display:inline-block'>
                            <p>$requerimiento:</p>
                                <input type='radio' name='R$consecutivo_requerimiento' id='R$consecutivo_requerimiento' value=0 checked> No<br>
                                <input type='radio' name='R$consecutivo_requerimiento' id='R$consecutivo_requerimiento' value=1> Si<br>
                                <input type='radio' name='R$consecutivo_requerimiento' id='R$consecutivo_requerimiento' value='D'> No Aplica<br>
                            <br>
                            ";
            if($Semana_url <= $semana){
                $cadena .= "<p>Indique la dirección del archivo (si aplica):</p>
                                <input type='url' name='url_R$consecutivo_requerimiento' id='url_R$consecutivo_requerimiento' placeholder='https://...'; style='width:100%; max-width:500px; height:30px; font-size:1em; padding-left:1px; padding-right:0px; margin:0px 2px; border-radius: 5px; border-style:solid; border-color:darkgrey; border-width:1px' value='$url'><br>
                            </div>
                            <div class='boton_comprometer_tarea' style='width:25%; margin-left:5px; display:inline-block'>
                                <button id='btn_comprometer_R$consecutivo_requerimiento' type='button' class='btn btn-success btn-sm'  aria-pressed='true' onclick=comprometer_requerimiento($consecutivo_requerimiento) style='float:left; margin:0px 0px 10px 10px'><i class='fas fa-handshake'></i> Comprometer</button>
                            </div>
                            <input type='hidden' id='nombre_R$consecutivo_requerimiento' value='$requerimiento' </input>
                        </div>";
            }else{
                $cadena.= "<p>Indique la dirección del archivo (si aplica):</p>
                                <input type='url' name='url_R$consecutivo_requerimiento' id='url_R$consecutivo_requerimiento' placeholder='https://...'; style='width:100%; max-width:500px; height:30px; font-size:1em; padding-left:1px; padding-right:0px; margin:0px 2px; border-radius: 5px; border-style:solid; border-color:darkgrey; border-width:1px'><br>
                            </div>
                            <div class='boton_comprometer_tarea' style='width:25%; margin-left:5px; display:inline-block'>
                                <button id='btn_comprometer_R$consecutivo_requerimiento' type='button' class='btn btn-success btn-sm'  aria-pressed='true' onclick=comprometer_requerimiento($consecutivo_requerimiento) style='float:left; margin:0px 0px 10px 10px'><i class='fas fa-handshake'></i> Comprometer</button>
                            </div>
                            <input type='hidden' id='nombre_R$consecutivo_requerimiento' value='$requerimiento' </input>
                        </div>";
            }
        }
        $cadena .="                                                                                                             <br><button id='btn_nuevo_requerimiento' type='button' class='btn btn-primary btn-sm'  aria-pressed='true' onclick=nuevo_requerimiento() style='float:left; margin:0px 0px 10px 10px'><i class='fas fa-plus'></i> Nuevo Requerimiento</button><input type='hidden' id='Id_tarea' value=$Id </input><input type='hidden' id='checklist_requerimiento' value=$checklist </input>";
        
        echo $cadena;
    }
    
}

function modificar_checklist($Id, $semana, $relevancia, $ejecutado, $f_inicio_sem, $R1, $R2, $R3, $R4, $R5, $R6, $R7, $R8, $R9, $R10, $R11, $R12, $R13, $R14, $R15, $R16, $R17, $R18, $R19, $R20, $R21, $R22, $R23, $R24, $R25, $checklist, $url_R1, $url_R2, $url_R3, $url_R4, $url_R5, $url_R6, $url_R7, $url_R8, $url_R9, $url_R10, $url_R11, $url_R12, $url_R13, $url_R14, $url_R15, $url_R16, $url_R17, $url_R18, $url_R19, $url_R20, $url_R21, $url_R22, $url_R23, $url_R24, $url_R25, $Observaciones, $db, $conexion){
    $suma=0;
    $conteo=0;
    
    if($R1==='NA' || $R1==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R1;
        $conteo=$conteo+1;
    }
    if($R2==='NA'  || $R2==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R2;
        $conteo=$conteo+1; 
    }
    if($R3==='NA'  || $R3==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R3;
        $conteo=$conteo+1;
    }
    if($R4==='NA'  || $R4==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R4;
        $conteo=$conteo+1;
    }
    if($R5==='NA'  || $R5==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R5;
        $conteo=$conteo+1;
    }
    if($R6==='NA'  || $R6==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R6;
        $conteo=$conteo+1;
    }
    if($R7==='NA'  || $R7==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R7;
        $conteo=$conteo+1;
    }
    if($R8==='NA'  || $R8==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R8;
        $conteo=$conteo+1;
    }
    if($R9==='NA'  || $R9==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R9;
        $conteo=$conteo+1;
    }
    if($R10==='NA'  || $R10==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R10;
        $conteo=$conteo+1;
    }
    if($R11==='NA'  || $R11==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R11;
        $conteo=$conteo+1;
    }
    if($R12==='NA'  || $R12==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R12;
        $conteo=$conteo+1;
    }
    if($R13==='NA'  || $R13==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R13;
        $conteo=$conteo+1;
    }
    if($R14==='NA'  || $R14==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R14;
        $conteo=$conteo+1;
    }
    if($R15==='NA'  || $R15==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R15;
        $conteo=$conteo+1;
    }
    if($R16==='NA'  || $R16==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R16;
        $conteo=$conteo+1;
    }
    if($R17==='NA'  || $R17==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R17;
        $conteo=$conteo+1;
    }
    if($R18==='NA'  || $R18==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R18;
        $conteo=$conteo+1;
    }
    if($R19==='NA'  || $R19==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R19;
        $conteo=$conteo+1;
    }
    if($R20==='NA'  || $R20==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R20;
        $conteo=$conteo+1;
    }
    if($R21==='NA'  || $R21==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R21;
        $conteo=$conteo+1;
    }
    if($R22==='NA'  || $R22==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R22;
        $conteo=$conteo+1;
    }
    if($R23==='NA'  || $R23==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R23;
        $conteo=$conteo+1;
    }
    if($R24==='NA'  || $R24==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R24;
        $conteo=$conteo+1;
    }
    if($R25==='NA'  || $R25==='D'){
        $suma=$suma;
        $conteo=$conteo;
    }else{
        $suma=$suma+$R25;
        $conteo=$conteo+1;
    }
    
    if($suma == 0 && $conteo == 0){
        $Estado_Restricciones=0;
    }else{
        $Estado_Restricciones= round($suma/$conteo , 3);
    }
    
    $query="UPDATE $db"."_programa_consolidado SET Estado_Restricciones=$Estado_Restricciones, Relevancia='$relevancia', Ejecutado='$ejecutado', R1='$R1', R2='$R2', R3='$R3', R4='$R4', R5='$R5', R6='$R6', R7='$R7', R8='$R8', R9='$R9', R10='$R10', R11='$R11', R12='$R12', R13='$R13', R14='$R14', R15='$R15', R16='$R16', R17='$R17', R18='$R18', R19='$R19', R20='$R20', R21='$R21', R22='$R22', R23='$R23', R24='$R24', R25='$R25', Observaciones='$Observaciones' WHERE Consecutivo_en_Programa=$Id AND Semana=$semana";
    
    $resultado= mysqli_multi_query($conexion, $query);
    
    $url=array($url_R1, $url_R2, $url_R3, $url_R4, $url_R5, $url_R6, $url_R7, $url_R8, $url_R9, $url_R10, $url_R11, $url_R12, $url_R13, $url_R14, $url_R15, $url_R16, $url_R17, $url_R18, $url_R19, $url_R20, $url_R21, $url_R22, $url_R23, $url_R24, $url_R25);
    $query1 ="";
    for($i=0;$i<25;$i++){
        $Consecutivo_Requerimiento=$i + 1;
        if($url[$i]!=''){
            $query1 .="UPDATE $db"."_checklists SET url='$url[$i]', Semana_url=$semana WHERE Codigo_Tarea=$checklist AND Consecutivo_Requerimiento=$Consecutivo_Requerimiento; ";
        }
    }
    
    $resultado1= mysqli_multi_query($conexion, $query1);
    echo $query1;
    cerrar($conexion);
    
    
    require("../conexion.php");
    modificar_estado_act($Id, $semana, $f_inicio_sem, $db, $conexion);
    cerrar($conexion);
}

function modificar_estado_act($Id, $semana, $f_inicio_sem, $db, $conexion){

    $fin_semana= date("Y-m-d",strtotime("$f_inicio_sem + 7 days"));
    
    $query = "UPDATE $db"."_programa_consolidado SET                                                
                                                 Estado= CASE
                                                    WHEN Fecha_Fin<'$fin_semana' AND Ejecutado=1 THEN 'Terminada' 
                                                    WHEN Fecha_Fin<'$f_inicio_sem' AND Ejecutado<1 THEN 'Atrasada' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Estado_Restricciones!='NA' AND Estado_Restricciones<1 AND R1!='NA' AND Ejecutado=0 THEN 'No Puede Comenzar' 
                                                    WHEN (Fecha_Inicio>='$fin_semana' OR Fecha_Fin>='$fin_semana') AND Ejecutado=1 THEN 'Terminada Antes' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Ejecutado<1 AND Ejecutado>0 THEN 'En Ejecución'
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Estado_Restricciones!='NA' AND (Estado_Restricciones=1 OR R1='NA') AND Ejecutado=0 THEN 'Pendiente de Iniciar'
                                                    WHEN Dias_Inicio <= Lookahead AND Ejecutado=0 THEN 'Porgramación Intermedia'
                                                    ELSE 'No Requerida'
                                                 END  
                                                WHERE Titulo=0 AND Consecutivo_en_Programa=$Id AND Semana=$semana
                                                ";
    //echo $query;                            
    $resultado=mysqli_multi_query($conexion, $query); 
}

function nuevo_requerimiento($Id, $semana, $checklist, $descripcion, $db, $conexion){
    $query="SELECT Tarea, MAX(Consecutivo_Requerimiento) FROM $db"."_checklists WHERE Codigo_Tarea=$checklist";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $tarea=$data['Tarea'];
    $numero_requerimiento=$data['MAX(Consecutivo_Requerimiento)']+1;
    
    $query1="INSERT INTO $db"."_checklists (Id, Tarea, Codigo_Tarea, Consecutivo_Requerimiento, Requerimiento, clase) SELECT NULL, '$tarea', $checklist, $numero_requerimiento, '$descripcion', 'adicional'";
    $resultado1= mysqli_query($conexion, $query1);
    
    $query2="UPDATE $db"."_programa_consolidado SET R$numero_requerimiento=0 WHERE Consecutivo_en_Programa=$Id AND Semana=$semana";
    $resultado2= mysqli_query($conexion, $query2);
    cerrar($conexion);
    //echo $query1;
}

function comprometer_requerimiento($consecutivo, $semana, $checklist, $requerimiento, $consecutivo_requerimiento, $db, $conexion){
    $query="SELECT Id, Actividad, Ruta_Critica, Estado FROM $db"."_programa_consolidado WHERE Consecutivo_en_Programa=$consecutivo AND Semana=$semana";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $Id=$data['Id'];
    $Actividad=$data['Actividad'];
    $Ruta_Critica=$data['Ruta_Critica'];
    $Estado=$data['Estado'];
    if($Estado=='Atrasada'){
        $Atrasada=1;
    }else{
        $Atrasada=0;
    }
    
    $query1="SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Id='$Id (R$consecutivo_requerimiento)'";
    $resultado1= mysqli_query($conexion, $query1);
    $data1=mysqli_fetch_assoc($resultado1);
    $conteo=$data1["COUNT(*)"];
    if($conteo>0){ 
        echo "duplicado";
    }else{
        $query2="INSERT INTO $db"."_programacion_semanal (Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Descripcion, Clase, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100) VALUES (NULL, $semana, $consecutivo, '$Id (R$consecutivo_requerimiento)', '$requerimiento', 'Requerimiento de la actividad: $Actividad', 'Checklist Consultores', $Ruta_Critica, $Atrasada, 'NA', 0)";
        $resultado2= mysqli_query($conexion, $query2);
    }
    cerrar($conexion);
}

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    mysqli_close($conexion);
    require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}

function eliminar_sem($semana, $db, $conexion){    
    require("../funciones_generales/eliminar_semana.php");
}


function activar_checklists($semana, $db, $conexion){
    $query = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND (Categoria = 'tramites' OR Categoria = 'consultores' OR Categoria = 'periodicas_compuestas')";
    require("../conexion.php");
    $resultado=mysqli_query($conexion, $query);
    
    while($data=mysqli_fetch_assoc($resultado)){
        $consecutivo = $data["Consecutivo_en_Programa"];
        $checklist = $data["Checklist"];
        
        if($checklist==NULL){
        }else{
            require("../conexion.php");
            $query1 = "SELECT MAX(Consecutivo_Requerimiento) FROM $db"."_checklists WHERE Codigo_Tarea=$checklist";
            $resultado1=mysqli_query($conexion, $query1);
            $data1=mysqli_fetch_assoc($resultado1);
            $requerimientos=$data1["MAX(Consecutivo_Requerimiento)"];

            $query2 = "SELECT ";
            for($i=1; $i<=$requerimientos; $i++){

                require("../conexion.php");
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

                require("../conexion.php");
                $query3 .= "R$i = $valor, ";
            }
            $query3 .="Estado_Restricciones=0 WHERE Consecutivo_en_Programa = $consecutivo"; 
            $resultado3=mysqli_query($conexion, $query3);
        }
    }
    $query4 = "UPDATE $db"."_programa_consolidado SET Estado_Restricciones=1 WHERE Categoria = 'periodicas_simples' OR Categoria = 'propias' OR ((Categoria = 'tramites' OR Categoria = 'consultores' OR Categoria = 'periodicas_compuestas') AND Checklist='')";
    $resultado4=mysqli_query($conexion, $query4);
}


function CNC($categoria, $db, $conexion){
        $query="SELECT * FROM general_cnc WHERE Categoria_CNC='$categoria'";
        $resultado= mysqli_query($conexion, $query);
        $cadena="<option value=''></option>";
        while ($valores = mysqli_fetch_array($resultado)){
            $valores=$valores['CNC'];
            $cadena.= "<option value='$valores'>$valores</option>";
        };
        echo $cadena;
}
    
function generar($semana, $db, $conexion){
    $query="SELECT  COUNT(*) FROM $db"."_cic WHERE Semana=$semana";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    //echo $conteo;
    if ($conteo==0){
        $query1="INSERT INTO $db"."_cic (subcontratista) SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista !='' AND Activa=1";
        $resultado1= mysqli_query($conexion, $query1);    
    }
    verificar_resultado($resultado);
    actualizar_PAC($semana, $db, $conexion);
    actualizar_integral($semana, $db, $conexion);
            
}

function actualizar_PAC($semana, $db, $conexion){
    $query5 ="SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista !=''";
        $resultado5= mysqli_query($conexion, $query5);
        $query6 ="";
        while($data=mysqli_fetch_assoc($resultado5)){
            $subcontratista = $data['Sub_Contratista'];
            $query6 ="UPDATE $db"."_cic INNER JOIN $db"."_subcontratistas ON $db"."_cic . subcontratista = $db"."_subcontratistas . subcontratista SET 
                $db"."_cic . P_Completado = (SELECT ROUND(SUM(P_Completado)/COUNT(P_Completado),3) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista ='$subcontratista' AND Activa=1), 
                
                $db"."_cic . PAC = (SELECT ROUND(SUM(PAC)/COUNT(PAC),3) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista ='$subcontratista' AND Activa=1), 
                
                $db"."_cic . Semana = $semana, $db"."_cic . correo_contacto = $db"."_subcontratistas . correo_contacto, $db"."_cic . NIT = $db"."_subcontratistas . NIT, $db"."_cic . alcance = $db"."_subcontratistas . alcance, $db"."_cic . tipo_proveedor = $db"."_subcontratistas . tipo_proveedor WHERE $db"."_cic . subcontratista = '$subcontratista'  AND Semana=0;";
                
            $resultado6= mysqli_query($conexion, $query6);
            //echo $query3 ."<br>" . $query4;
        }
        
        mysqli_free_result($resultado5);
        mysqli_close($conexion);
    
        
}

function actualizar_integral($semana, $db, $conexion){
    require("../conexion.php");
    $query5 ="SELECT * FROM $db"."_cic WHERE Semana=$semana;";
    $resultado5= mysqli_query($conexion, $query5);

    while ($cic = mysqli_fetch_assoc($resultado5)){
        $Id=$cic['Id'];
        $subcontratista=$cic['subcontratista'];
        $query6 = "UPDATE $db"."_cic SET 
            PAC_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND PAC!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(PAC),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND PAC!='NA') END), 

            P_Completado_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND P_Completado!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(P_Completado),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND P_Completado!='NA') END), 

            Calidad_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND Calidad!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Calidad),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND Calidad!='NA') END),

            GSA_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND GSA!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(GSA),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND GSA!='NA') END),

            SST_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND SST!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(SST),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND SST!='NA') END),

            ADM_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND ADM!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(ADM),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND ADM!='NA') END)

            WHERE Id=$Id";
        //echo $query6;
        $resultado6= mysqli_query($conexion, $query6); 

        $query7 ="SELECT * FROM $db"."_cic WHERE Id=$Id;";
        $resultado7= mysqli_query($conexion, $query7);
        $cic1 = mysqli_fetch_assoc($resultado7);

        $PAC=$cic1['PAC'];
        $PAC_acum=$cic1['PAC_Acum'];
        $calidad=$cic1['Calidad'];
        $calidad_acum=$cic1['Calidad_Acum'];
        $adm=$cic1['ADM'];
        $adm_acum=$cic1['ADM_Acum'];
        $gsa=$cic1['GSA'];
        $gsa_acum=$cic1['GSA_Acum'];
        $sst=$cic1['SST'];
        $sst_acum=$cic1['SST_Acum'];

        if($calidad=='NA'){
            if($sst=='NA'){
                if($gsa=='NA'){
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.7/7)*7);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.6/4)*3)+$adm*(0.1+(0.6/4)*1);
                    }
                }else{
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$gsa*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$gsa*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }
            }else{
                if($gsa=='NA'){
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$sst*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$sst*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$sst*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$sst*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }
            }
        }else{
            if($sst=='NA'){
                if($gsa=='NA'){
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$calidad*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$calidad*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }
            }else{
                if($gsa=='NA'){
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$sst*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$sst*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }else{
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.1/9)*3)+$calidad*(0.2+(0.1/9)*2)+$sst*(0.2+(0.1/9)*2)+$gsa*(0.2+(0.1/9)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.0/10)*3)+$calidad*(0.2+(0.0/10)*2)+$sst*(0.2+(0.0/10)*2)+$gsa*(0.2+(0.0/10)*2)+$adm*(0.1+(0.0/10)*1);
                    }
                }
            }
        }


        if($calidad_acum=='NA'){
            if($sst_acum=='NA'){
                if($gsa_acum=='NA'){
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.7/7)*7);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.6/4)*3)+$adm_acum*(0.1+(0.6/4)*1);
                    }
                }else{
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$gsa_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$gsa_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }
            }else{
                if($gsa_acum=='NA'){
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$sst_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$sst_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$sst_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$sst_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }
            }
        }else{
            if($sst_acum=='NA'){
                if($gsa_acum=='NA'){
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$calidad_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$calidad_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }
            }else{
                if($gsa_acum=='NA'){
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$sst_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$sst_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }else{
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.1/9)*3)+$calidad_acum*(0.2+(0.1/9)*2)+$sst_acum*(0.2+(0.1/9)*2)+$gsa_acum*(0.2+(0.1/9)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.0/10)*3)+$calidad_acum*(0.2+(0.0/10)*2)+$sst_acum*(0.2+(0.0/10)*2)+$gsa_acum*(0.2+(0.0/10)*2)+$adm_acum*(0.1+(0.0/10)*1);
                    }
                }
            }
        }

        //echo "<li>" . $PAC_acum . "<li>" . $calidad_acum . "<li>" . $gsa_acum . "<li>" . $sst_acum . "<li>" . $adm_acum . "<li>" . $cal_integral_acum ;

        $query7 = "UPDATE $db"."_cic SET Cal_Integral = ROUND($cal_integral,3), Cal_Integral_Acum = ROUND($cal_integral_acum,3) WHERE Id=$Id;";
        //echo $query7;

        //echo $query7;
        $resultado7= mysqli_query($conexion, $query7); 


    };
        //echo $query8;  

        //$resultado4= mysqli_multi_query($conexion, $query4);
        mysqli_close($conexion);
        //mysqli_free_result($resultado);
}

function verificar_resultado($resultado){
    if(!$resultado) $informacion["respuesta"] ="ERROR";
    else $informacion["respuesta"] = "BIEN";
    echo json_encode($informacion);   
}

function cerrar($conexion){
    mysqli_close($conexion);
}


function fecha_inicio_sem($semana, $db, $conexion){
    require("../conexion.php");
    $query="SELECT COUNT(*) FROM $db"."_semanas_activas";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];

    if($conteo==0){
        $inicio_semana=date("Y-m-d");

    }else{
        $query1="SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=$semana";
        $resultado1= mysqli_query($conexion, $query1);
        $data1=mysqli_fetch_assoc($resultado1);
        $inicio_semana=$data1["Fecha_Inicio_Sem"];
    }


    return $inicio_semana;
}



?>