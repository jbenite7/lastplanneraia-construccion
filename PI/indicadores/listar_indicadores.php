<?php

	require ("../conexion.php");
    $db=/*"paris_campestre_pi"*/$_GET['db'];
    $opcion=/*"PAC"*/$_POST["opcion"];
    $semana=/*7*/$_GET["semana"];
    
    
$query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE (Semana=$semana AND PAC>=0)";
$resultado= mysqli_query($conexion, $query);
$data=mysqli_fetch_assoc($resultado);
$conteo=$data["COUNT(*)"];
if($conteo>0){
    switch($opcion){
    case 'PAC':
        
        $nombre=/*"consolidado general"*/$_POST["nombre"];
        $ultimas_semanas=/*"Todas"*/$_POST["ultimas_semanas"];
        
        if($ultimas_semanas=="Todas"){
            $script_ultimas_semanas='';
        }else{
            $ultimas_semanas=$semana+1-$ultimas_semanas;
            $script_ultimas_semanas="AND Semana>=$ultimas_semanas";
        };        
        grafico_PAC_general($conexion, $semana, $db, $nombre, $script_ultimas_semanas);
        break;
            
    case 'pareto_CNC':
        
        $nombre=/*"general"*/$_POST["nombre"];
        pareto_CNC($conexion, $semana, $db, $nombre);
        break;
            
    case 'Semana_CNC':
        
        $nombre=/*"consolidado general"*/$_POST["nombre"];
        $ultimas_semanas=/*3*/$_POST["ultimas_semanas"];
        
        if($ultimas_semanas=="Todas"){
            $script_ultimas_semanas='';
        }else{
            $ultimas_semanas=$semana+1-$ultimas_semanas;
            $script_ultimas_semanas="AND Semana>=$ultimas_semanas";
        };  
            
        Semana_CNC($conexion, $semana, $db, $nombre, $script_ultimas_semanas);
        break;
            
    case 'ind_compromisos':
        
        $nombre=/*"Pileros"*/$_POST["nombre"];
        $ultimas_semanas=/*3*/$_POST["ultimas_semanas"];
        
        if($ultimas_semanas=="Todas"){
            $script_ultimas_semanas='';
        }else{
            $ultimas_semanas=$semana+1-$ultimas_semanas;
            $script_ultimas_semanas="AND Semana>=$ultimas_semanas";
        };  
            
        ind_compromisos($conexion, $semana, $db, $nombre, $script_ultimas_semanas);
        break;
            
    case 'restricciones':
        
        $nombre=/*"consolidado general"*/$_POST["nombre"];
        restricciones($conexion, $semana, $db, $nombre);
        break;
            
    case 'cal_contratistas':
        
        $nombre=/*"general"*/$_POST["nombre"];
        cal_contratistas($conexion, $semana, $db, $nombre);
        break;
            
    case 'cal_profesionales':
        
        $nombre=/*"general"*/$_POST["nombre"];
        cal_profesionales($conexion, $semana, $db, $nombre);
        break;
            
    case 'nombre_PAC':
        $tipo_PAC=/*"subcontratista"*/$_POST["tipo_PAC"];
        nombre_PAC($conexion, $semana, $db, $tipo_PAC);
        break;
            
            
    }
}
    

function grafico_PAC_general($conexion, $semana, $db, $nombre, $script_ultimas_semanas){
    crear_indicadores($conexion, $semana, $db); 
    require ("../conexion.php");
    if($nombre=="general"){
        $nombre="consolidado general";
    }else{
        $nombre=$nombre;
    }
    $query5="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana $script_ultimas_semanas AND subcontratista_profesional='$nombre'";
    $resultado5 = mysqli_query($conexion, $query5);
    $data=mysqli_fetch_assoc($resultado5);
    $conteo=$data["COUNT(*)"];
    if($conteo>0){
        $query5_1="SELECT * FROM $db"."_indicadores_generales WHERE Semana<=$semana $script_ultimas_semanas AND subcontratista_profesional='$nombre' ORDER BY Semana ASC;";
        //echo $query5;
        $resultado5_1 = mysqli_query($conexion, $query5_1);
        if(!$resultado5){
            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'PAC' , 'label' => 'PAC' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'PAC_label' , 'label' => 'PAC' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'PAC_Acum' , 'label' => 'PAC Acumulado' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'P_Completado' , 'label' => '% Completado' , 'type' => 'number');
            $array['cols'][5] = array('id' => 'P_Completado_label' , 'label' => '% Completado' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][6] = array('id' => 'P_Completado_Acum' , 'label' => '% Completado Acumulado' , 'type' => 'number');
        } else{
            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'PAC' , 'label' => 'PAC' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'PAC_label' , 'label' => 'PAC' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'PAC_Acum' , 'label' => 'PAC Acumulado' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'P_Completado' , 'label' => '% Completado' , 'type' => 'number');
            $array['cols'][5] = array('id' => 'P_Completado_label' , 'label' => '% Completado' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][6] = array('id' => 'P_Completado_Acum' , 'label' => '% Completado Acumulado' , 'type' => 'number');

            while($row=mysqli_fetch_assoc($resultado5_1)){ 
                $Semana=(int)$row['Semana'];
                $Semana="Semana $Semana";
                $PAC=(float)$row['PAC'];
                if($row['PAC']=="NA"){
                    $PAC_label="NA";
                }else{
                    $PAC_label=round($PAC*100,0) . "%";
                }
                $PAC_Acum=(float)$row['PAC_Acum'];
                if($row['PAC_Acum']=="NA"){
                    $PAC_Acum=null;
                    $PAC_Acum_label="NA";
                }else{
                    $PAC_Acum_label=round($PAC_Acum*100,0) . "%";
                }
                $P_Completado=(float)$row['P_Completado'];
                if($row['P_Completado']=="NA"){
                    $P_Completado_label="NA";
                }else{
                    $P_Completado_label=round($P_Completado*100,0) . "%";
                }
                $P_Completado_Acum=(float)$row['P_Completado_Acum'];
                if($row['P_Completado_Acum']=="NA"){
                    $P_Completado_Acum=null;
                    $P_Completado_Acum_label="NA";
                }else{
                    $P_Completado_Acum_label=round($P_Completado_Acum*100,0) . "%";
                }

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                      array('v'=>$PAC, 'f'=>$PAC_label),
                                                      array('v'=> $PAC_label),
                                                      array('v'=>$PAC_Acum, 'f'=>$PAC_Acum_label),
                                                      array('v'=>$P_Completado, 'f'=>$P_Completado_label),
                                                      array('v'=> $P_Completado_label),
                                                      array('v'=>$P_Completado_Acum, 'f'=>$P_Completado_Acum_label)
                                                     ));
            }

            $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);

        }    
    }else{
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'PAC' , 'label' => 'PAC' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'PAC_label' , 'label' => 'PAC' , 'type' => 'string', 'role' => 'annotation');
        $array['cols'][3] = array('id' => 'PAC_Acum' , 'label' => 'PAC Acumulado' , 'type' => 'number');
        $array['cols'][4] = array('id' => 'P_Completado' , 'label' => '% Completado' , 'type' => 'number');
        $array['cols'][5] = array('id' => 'P_Completado_label' , 'label' => '% Completado' , 'type' => 'string', 'role' => 'annotation');
        $array['cols'][6] = array('id' => 'P_Completado_Acum' , 'label' => '% Completado Acumulado' , 'type' => 'number');
        $array['rows'][] = array('c' => array( array('v'=> "Semana"),
                                              array('v'=>0, 'f'=>"N/A"),
                                              array('v'=> "N/A"),
                                              array('v'=>0, 'f'=>"N/A"),
                                              array('v'=>0, 'f'=>"N/A"),
                                              array('v'=> "N/A"),
                                              array('v'=>0, 'f'=>"N/A")
                                             ));
        $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
        echo utf8_decode($json_codificado);
        mysqli_close($conexion);
    }
    
}

function pareto_CNC($conexion, $semana, $db, $nombre){
    //crear_indicadores($conexion, $semana, $db); 
    require ("../conexion.php");
    if($nombre=="general"){
        $nombre="consolidado general";
    }else{
        $nombre=$nombre;
    }
    //echo $nombre;
    
    $query5="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre'";
    $resultado5 = mysqli_query($conexion, $query5);
    $data=mysqli_fetch_assoc($resultado5);
    $conteo=$data["COUNT(*)"];
    //echo $conteo;
    if($conteo==0){
        $Rendimiento="";
        $Programacion="";
        $MdeO="";
        $Materiales="";
        $Equipos="";
        $Disenos="";
        $Administrativas="";
        $resultado6=null;
        
    }else{
        for($j=$semana; $j>=0; $j=$j-1) {
            $query6="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$j AND subcontratista_profesional='$nombre' ORDER BY Semana ASC;";
            //echo $query6;
            $resultado6 = mysqli_query($conexion, $query6);
            $data1=mysqli_fetch_assoc($resultado6);
            $json_codificado = json_encode($data1, JSON_UNESCAPED_UNICODE);
            //echo $json_codificado;
            
            if(!$data1){

            }else{
                $j=0;
            }    
        }
        //echo utf8_decode($json_codificado) ."<br><br>" ;

        $Rendimiento=$data1["CNC_Rendimiento_Acum"];
        $Programacion=$data1["CNC_Programacion_Acum"];
        $MdeO=$data1["CNC_MdeO_Acum"];
        $Materiales=$data1["CNC_Materiales_Acum"];
        $Equipos=$data1["CNC_Equipos_Acum"];
        $Disenos=$data1["CNC_Disenos_Acum"];
        $Administrativas=$data1["CNC_Administrativas_Acum"];
        
        //echo $Rendimiento . "<br>" . $Programacion . "<br>" . $MdeO . "<br>" . $Materiales . "<br>" . $Equipos . "<br>" . $Disenos . "<br>" . $Administrativas . "<br>";

        $pareto_CNC_ordenado = array("Rendimiento" => $Rendimiento,"Programación" => $Programacion,"Mano de Obra" => $MdeO,"Materiales" => $Materiales,"Equipos" => $Equipos,"Diseños" => $Disenos,"Administrativas" => $Administrativas);

        arsort($pareto_CNC_ordenado);
        //var_export($pareto_CNC_ordenado);
    }

    
    
    
    $array['cols'][0] = array('id' => 'CNC' , 'label' => 'CNC' , 'type' => 'string');
        
    $array['cols'][1] = array('id' => 'Frecuencia' , 'label' => 'Frecuencia' , 'type' => 'number');

    $array['cols'][2] = array('id' => 'Frecuencia_label' , 'label' => 'Frecuencia_label' , 'type' => 'string', 'role' => 'annotation');

    $array['cols'][3] = array('id' => 'estilos' , 'label' => 'estilos' , 'type' => 'string', 'role' => 'style');

    $array['cols'][4] = array('id' => 'Frecuencia_Acumulada' , 'label' => 'Frecuencia Acumulada' , 'type' => 'number');

    $array['cols'][5] = array('id' => 'Frecuencia_Acumulada_label' , 'label' => 'Frecuencia Acumulada_label' , 'type' => 'string', 'role' => 'annotation');
    
    
    
    if(!$resultado6){
        $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
        echo utf8_decode($json_codificado);
        mysqli_close($conexion);
    } else{
        
        
        
        
        $Suma_CNC=($pareto_CNC_ordenado["Rendimiento"]+$pareto_CNC_ordenado["Programación"]+$pareto_CNC_ordenado["Mano de Obra"]+$pareto_CNC_ordenado["Materiales"]+$pareto_CNC_ordenado["Equipos"]+$pareto_CNC_ordenado["Diseños"]+$pareto_CNC_ordenado["Administrativas"]);
        
        $Porcentaje_Acum=0;
        while ($nombre = current($pareto_CNC_ordenado)) {
            
        
            
            $Causa=key($pareto_CNC_ordenado); 
            
            if($Causa=="Rendimiento"){
                $color="rgb(55,86,54)";
            }else if($Causa=="Programación"){
                $color="rgb(191,215,48)";
            }else if($Causa=="Mano de Obra"){
                $color="rgb(118,68,138)";
            }else if($Causa=="Materiales"){
                $color="rgb(245,176,65)";
            }else if($Causa=="Equipos"){
                $color="rgb(36,113,163)";
            }else if($Causa=="Diseños"){
                $color="rgb(211,84,0)";
            }else if($Causa=="Administrativas"){
                $color="rgb(52,73,94)";
            }
            
            $Frecuencia=$pareto_CNC_ordenado["$Causa"];
            $Porcentaje = $Frecuencia / $Suma_CNC;
            $Porcentaje_Acum= $Porcentaje_Acum + $Porcentaje;
            
            
            $array['rows'][] = array('c' => array( array('v'=> $Causa),
                                                  array('v'=>$Porcentaje, 'f'=>round($Porcentaje*100,0) . "%"),
                                                  array('v'=>round($Porcentaje*100,0) . "%"),
                                                  array('v'=>"color:". $color),
                                                  array('v'=>$Porcentaje_Acum, 'f'=>round($Porcentaje_Acum*100,0) . "%"),
                                                  array('v'=>round($Porcentaje_Acum*100,0) . "%")
                                                 ));

        next($pareto_CNC_ordenado);
        } 

        $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
        echo $json_codificado;
        mysqli_close($conexion);
        
    }
}

function Semana_CNC($conexion, $semana, $db, $nombre, $script_ultimas_semanas){
    //crear_indicadores($conexion, $semana, $db); 
    require ("../conexion.php");
    if($nombre=="general"){
        $nombre="consolidado general";
    }else{
        $nombre=$nombre;
    }
    
    $query5="SELECT * FROM $db"."_indicadores_generales WHERE Semana<=$semana $script_ultimas_semanas AND subcontratista_profesional='$nombre' ORDER BY Semana ASC;";
    $resultado5 = mysqli_query($conexion, $query5);
    if(!$resultado5){
        die("Error");
    } else{
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        
        $array['cols'][1] = array('id' => 'Rendimiento' , 'label' => 'Rendimiento' , 'type' => 'number');
        
        $array['cols'][2] = array('id' => 'Programacion' , 'label' => 'Programacion' , 'type' => 'number');
        
        $array['cols'][3] = array('id' => 'MdeO' , 'label' => 'Mano de Obra' , 'type' => 'number');
        
        $array['cols'][4] = array('id' => 'Materiales' , 'label' => 'Materiales' , 'type' => 'number');
        
        $array['cols'][5] = array('id' => 'Equipos' , 'label' => 'Equipos' , 'type' => 'number');
        
        $array['cols'][6] = array('id' => 'Diseños' , 'label' => 'Diseños' , 'type' => 'number');
        
        $array['cols'][7] = array('id' => 'Administrativas' , 'label' => 'Administrativas' , 'type' => 'number');
        
        
        
        
        while($row=mysqli_fetch_assoc($resultado5)){ 
            $Semana=(int)$row['Semana'];
            $Semana="Semana $Semana";
            
            $Suma_CNC=$row['CNC_Rendimiento']+$row['CNC_Programacion']+$row['CNC_MdeO']+$row['CNC_Materiales']+$row['CNC_Equipos']+$row['CNC_Disenos']+$row['CNC_Administrativas'];
            
            $Rendimiento=$row['CNC_Rendimiento'];
            $Programacion=$row['CNC_Programacion'];
            $MdeO=$row['CNC_MdeO'];
            $Materiales=$row['CNC_Materiales'];
            $Equipos=$row['CNC_Equipos'];
            $Disenos=$row['CNC_Disenos'];
            $Administrativas=$row['CNC_Administrativas'];
            
            
            $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                  array('v'=>$Rendimiento),
                                                  array('v'=>$Programacion),
                                                  array('v'=>$MdeO),
                                                  array('v'=>$Materiales),
                                                  array('v'=>$Equipos),
                                                  array('v'=>$Disenos),
                                                  array('v'=>$Administrativas)
                                                 ));
        }

        $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
        echo $json_codificado;
        mysqli_close($conexion);
        
    }
}

function ind_compromisos($conexion, $semana, $db, $nombre, $script_ultimas_semanas){
    //crear_indicadores($conexion, $semana, $db); 
    require ("../conexion.php");
    if($nombre=="general"){
        $nombre="consolidado general";
    }else{
        $nombre=$nombre;
    }
    
    $query5="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana $script_ultimas_semanas AND subcontratista_profesional='$nombre'";
    $resultado5 = mysqli_query($conexion, $query5);
    $data=mysqli_fetch_assoc($resultado5);
    $conteo=$data["COUNT(*)"];
    if($conteo==0){
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Criticas_Comp' , 'label' => '% Actividades Críticas Comprometidas' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Criticas_Comp_label' , 'label' => '% Actividades Críticas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
        $array['cols'][3] = array('id' => 'No_Criticas_Comp' , 'label' => '% Actividades No Críticas Comprometidas' , 'type' => 'number');
        $array['cols'][4] = array('id' => 'No_Criticas_Comp_label' , 'label' => '% Actividades No Críticas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
        $array['cols'][5] = array('id' => 'Atrasadas_Comp' , 'label' => '% Actividades Atrasadas Comprometidas' , 'type' => 'number');
        $array['cols'][6] = array('id' => 'Atrasadas_Comp_label' , 'label' => '% Actividades Atrasadas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
        $array['cols'][7] = array('id' => 'Comp_Sin_Rest_100' , 'label' => '% Actividades Comprometidas Sin Liberar Restricciones' , 'type' => 'number');
        $array['cols'][8] = array('id' => 'Comp_Sin_Rest_100_label' , 'label' => '% Actividades Comprometidas Sin Liberar Restricciones' , 'type' => 'string', 'role' => 'annotation' );

        
        $array = array([$Criticas_Comp_val=0, $No_Criticas_Comp_val=0, $Atrasadas_Comp_val=0, $Comp_Sin_Rest_100_val=0, 100], [$Criticas_Comp_Acum_val=0, $No_Criticas_Comp_Acum_val=0, $Atrasadas_Comp_Acum_val=0, $Comp_Sin_Rest_100_Acum_val=0, 100],[$Criticas_Comp="NA", $No_Criticas_Comp="NA", $Atrasadas_Comp="NA", $Comp_Sin_Rest_100="NA", 100], [$Criticas_Comp_Acum="NA", $No_Criticas_Comp_Acum="NA", $Atrasadas_Comp_Acum="NA", $Comp_Sin_Rest_100_Acum="NA", 100],[$array]);
        
    }else{
        $query5="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='$nombre';";
        $resultado5 = mysqli_query($conexion, $query5);
        if(!$resultado5){
            die("Error");
        } else{
            $data = mysqli_fetch_assoc($resultado5);
            if(!$data){
                $Criticas_Comp_val=0;
                $Criticas_Comp = "NA";
                $No_Criticas_Comp_val=0;
                $No_Criticas_Comp = "NA";
                $Atrasadas_Comp_val=0;
                $Atrasadas_Comp = "NA";
                $Comp_Sin_Rest_100_val=0;
                $Comp_Sin_Rest_100 = "NA";
            }else{
                $Criticas_Comp = $data['Criticas_Comp'];
                $No_Criticas_Comp = $data['No_Criticas_Comp'];
                $Atrasadas_Comp = $data['Atrasadas_Comp'];
                $Comp_Sin_Rest_100 = $data['Comp_Sin_Rest_100'];

                if($Criticas_Comp == "NA" || $Criticas_Comp === null){
                    $Criticas_Comp_val=0;
                    $Criticas_Comp = "NA";
                }else{
                    $Criticas_Comp_val=$Criticas_Comp*100;
                }
                if($No_Criticas_Comp == "NA" || $No_Criticas_Comp === null){
                    $No_Criticas_Comp_val=0;
                    $No_Criticas_Comp = "NA";
                }else{
                    $No_Criticas_Comp_val=$No_Criticas_Comp*100;
                }
                if($Atrasadas_Comp == "NA" || $Atrasadas_Comp === null){
                    $Atrasadas_Comp_val=0;
                    $Atrasadas_Comp = "NA";
                }else{
                    $Atrasadas_Comp_val=$Atrasadas_Comp*100;
                }
                if($Comp_Sin_Rest_100 == "NA" || $Comp_Sin_Rest_100 === null){
                    $Comp_Sin_Rest_100_val=0;
                    $Comp_Sin_Rest_100 = "NA";
                }else{
                    $Comp_Sin_Rest_100_val=$Comp_Sin_Rest_100*100;
                }    
            }
            
        }




        for($j=$semana; $j>=0; $j=$j-1) {
            $query6="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$j AND subcontratista_profesional='$nombre';";
            $resultado6 = mysqli_query($conexion, $query6);
            if(!$resultado6){
                die("Error");
            } else{

                $data = mysqli_fetch_assoc($resultado6);
                if (!$data){

                }else{

                    $Criticas_Comp_Acum = $data['Criticas_Comp_Acum'];

                    $No_Criticas_Comp_Acum = $data['No_Criticas_Comp_Acum'];

                    $Atrasadas_Comp_Acum = $data['Atrasadas_Comp_Acum'];

                    $Comp_Sin_Rest_100_Acum = $data['Comp_Sin_Rest_100_Acum'];

                    if($Criticas_Comp_Acum == "NA"){
                        $Criticas_Comp_Acum_val=0;
                    }else{
                        $Criticas_Comp_Acum_val=$Criticas_Comp_Acum*100;
                    }
                    if($No_Criticas_Comp_Acum == "NA"){
                        $No_Criticas_Comp_Acum_val=0;
                    }else{
                        $No_Criticas_Comp_Acum_val=$No_Criticas_Comp_Acum*100;
                    }
                    if($Atrasadas_Comp_Acum == "NA"){
                        $Atrasadas_Comp_Acum_val=0;
                    }else{
                        $Atrasadas_Comp_Acum_val=$Atrasadas_Comp_Acum*100;
                    }
                    if($Comp_Sin_Rest_100_Acum == "NA"){
                        $Comp_Sin_Rest_100_Acum_val=0;
                    }else{
                        $Comp_Sin_Rest_100_Acum_val=$Comp_Sin_Rest_100_Acum*100;
                    }

                    $j=0;
                } 
            }
        }

        require ("../conexion.php");

            $query7="SELECT * FROM $db"."_indicadores_generales WHERE Semana<=$semana $script_ultimas_semanas AND subcontratista_profesional='$nombre' ORDER BY Semana ASC;";
            $resultado7 = mysqli_query($conexion, $query7);
            if(!$resultado7){
                die("Error");
            } else{
                $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
                $array['cols'][1] = array('id' => 'Criticas_Comp' , 'label' => '% Actividades Críticas Comprometidas' , 'type' => 'number');
                $array['cols'][2] = array('id' => 'Criticas_Comp_label' , 'label' => '% Actividades Críticas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
                $array['cols'][3] = array('id' => 'No_Criticas_Comp' , 'label' => '% Actividades No Críticas Comprometidas' , 'type' => 'number');
                $array['cols'][4] = array('id' => 'No_Criticas_Comp_label' , 'label' => '% Actividades No Críticas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
                $array['cols'][5] = array('id' => 'Atrasadas_Comp' , 'label' => '% Actividades Atrasadas Comprometidas' , 'type' => 'number');
                $array['cols'][6] = array('id' => 'Atrasadas_Comp_label' , 'label' => '% Actividades Atrasadas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
                $array['cols'][7] = array('id' => 'Comp_Sin_Rest_100' , 'label' => '% Actividades Comprometidas Sin Liberar Restricciones' , 'type' => 'number');
                $array['cols'][8] = array('id' => 'Comp_Sin_Rest_100_label' , 'label' => '% Actividades Comprometidas Sin Liberar Restricciones' , 'type' => 'string', 'role' => 'annotation' );

                while($row=mysqli_fetch_assoc($resultado7)){ 
                    $Semana = "Semana " . $row['Semana'];
                    $Criticas_Comp_1 = $row['Criticas_Comp'];
                    if($Criticas_Comp_1 == "NA"){
                        $Criticas_Comp_2=0;
                    }else{
                        $Criticas_Comp_2=$Criticas_Comp_1;
                        $Criticas_Comp_1=round($Criticas_Comp_1*100,0) . "%";
                    }
                    $No_Criticas_Comp_1 = $row['No_Criticas_Comp'];
                    if($No_Criticas_Comp_1 == "NA"){
                        $No_Criticas_Comp_2=0;
                    }else{
                        $No_Criticas_Comp_2=$No_Criticas_Comp_1;
                        $No_Criticas_Comp_1=round($No_Criticas_Comp_1*100,0) . "%";
                    }
                    $Atrasadas_Comp_1 = $row['Atrasadas_Comp'];
                    if($Atrasadas_Comp_1 == "NA"){
                        $Atrasadas_Comp_2=0;
                    }else{
                        $Atrasadas_Comp_2=$Atrasadas_Comp_1;
                        $Atrasadas_Comp_1=round($Atrasadas_Comp_1*100,0) . "%";
                    }
                    $Comp_Sin_Rest_100_1 = $row['Comp_Sin_Rest_100'];
                    if($Comp_Sin_Rest_100_1 == "NA"){
                        $Comp_Sin_Rest_100_2=0;
                    }else{
                        $Comp_Sin_Rest_100_2=$Comp_Sin_Rest_100_1;
                        $Comp_Sin_Rest_100_1=round($Comp_Sin_Rest_100_1*100,0) . "%";
                    }

                    $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                          array('v'=>$Criticas_Comp_2, 'f'=>$Criticas_Comp_1),
                                                          array('v'=> $Criticas_Comp_1),
                                                          array('v'=>$No_Criticas_Comp_2, 'f'=>$No_Criticas_Comp_1),
                                                          array('v'=> $No_Criticas_Comp_1),
                                                          array('v'=>$Atrasadas_Comp_2, 'f'=>$Atrasadas_Comp_1),
                                                          array('v'=> $Atrasadas_Comp_1),
                                                          array('v'=>$Comp_Sin_Rest_100_2, 'f'=>$Comp_Sin_Rest_100_1),
                                                          array('v'=> $Comp_Sin_Rest_100_1)

                                                         ));
                }
            }  
        $array = array([$Criticas_Comp_val, $No_Criticas_Comp_val, $Atrasadas_Comp_val, $Comp_Sin_Rest_100_val, 100], [$Criticas_Comp_Acum_val, $No_Criticas_Comp_Acum_val, $Atrasadas_Comp_Acum_val, $Comp_Sin_Rest_100_Acum_val, 100],[$Criticas_Comp, $No_Criticas_Comp, $Atrasadas_Comp, $Comp_Sin_Rest_100, 100], [$Criticas_Comp_Acum, $No_Criticas_Comp_Acum, $Atrasadas_Comp_Acum, $Comp_Sin_Rest_100_Acum, 100],[$array]);
    }
    
    

    
    
            
    
        $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
        echo $json_codificado;
        mysqli_close($conexion);
        
}

function restricciones($conexion, $semana, $db, $nombre){
    //crear_indicadores($conexion, $semana, $db); 
    require ("../conexion.php");
    if($nombre=="general"){
        $nombre="consolidado general";
    }else{
        $nombre=$nombre;
    }
    $query5="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general';";
    $resultado5 = mysqli_query($conexion, $query5);
    $data5 = mysqli_fetch_assoc($resultado5);
    $conteo=$data5["COUNT(*)"];
    if($conteo==0){
        $Arreglo = array("sem_6_6" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_6_6=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_6=0],
                  ['100% Liberada', $Act_100_Lib_Sem_6_6=0]],

                                 "sem_6_5" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_6_5=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_5=0],
                  ['100% Liberada', $Act_100_Lib_Sem_6_5=0]],

                                 "sem_6_4" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_6_4=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_4=0],
                  ['100% Liberada', $Act_100_Lib_Sem_6_4=0]],

                                 "sem_6_3" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_6_3=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_3=0],
                  ['100% Liberada', $Act_100_Lib_Sem_6_3=0]],

                                 "sem_6_2" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_6_2=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_2=0],
                  ['100% Liberada', $Act_100_Lib_Sem_6_2=0]],

                                 "sem_6_1" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_6_1=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_1=0],
                  ['100% Liberada', $Act_100_Lib_Sem_6_1=0]],

                                 "sem_5_6" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_5_6=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_6=0],
                  ['100% Liberada', $Act_100_Lib_Sem_5_6=0]],

                                 "sem_5_5" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_5_5=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_5=0],
                  ['100% Liberada', $Act_100_Lib_Sem_5_5=0]],

                                 "sem_5_4" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_5_4=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_4=0],
                  ['100% Liberada', $Act_100_Lib_Sem_5_4=0]],

                                 "sem_5_3" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_5_3=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_3=0],
                  ['100% Liberada', $Act_100_Lib_Sem_5_3=0]],

                                 "sem_5_2" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_5_2=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_2=0],
                  ['100% Liberada', $Act_100_Lib_Sem_5_2=0]],

                                 "sem_4_6" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_4_6=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_4_6=0],
                  ['100% Liberada', $Act_100_Lib_Sem_4_6=0]],

                                 "sem_4_5" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_4_5=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_4_5=0],
                  ['100% Liberada', $Act_100_Lib_Sem_4_5=0]],

                                 "sem_4_4" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_4_4=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_4_4=0],
                  ['100% Liberada', $Act_100_Lib_Sem_4_4=0]],

                                 "sem_4_3" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_4_3=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_4_3=0],
                  ['100% Liberada', $Act_100_Lib_Sem_4_3=0]],

                                 "sem_3_6" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_3_6=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_3_6=0],
                  ['100% Liberada', $Act_100_Lib_Sem_3_6=0]],

                                 "sem_3_5" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_3_5=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_3_5=0],
                  ['100% Liberada', $Act_100_Lib_Sem_3_5=0]],

                                 "sem_3_4" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_3_4=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_3_4=0],
                  ['100% Liberada', $Act_100_Lib_Sem_3_4=0]],

                                 "sem_2_6" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_2_6=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_2_6=0],
                  ['100% Liberada', $Act_100_Lib_Sem_2_6=0]],

                                 "sem_2_5" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_2_5=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_2_5=0],
                  ['100% Liberada', $Act_100_Lib_Sem_2_5=0]],

                                 "sem_1_6" => [['Estado de Liberación', '%'],
                  ['0% Liberada', $Act_0_Lib_Sem_1_6=0],
                  ['Parcialmente Liberada', $Act_Par_Lib_Sem_1_6=0],
                  ['100% Liberada', $Act_100_Lib_Sem_1_6=0]]

                                );

                $json_codificado = json_encode($Arreglo, JSON_UNESCAPED_UNICODE);
                echo $json_codificado;    
    }else{
        $query5="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
        $resultado5 = mysqli_query($conexion, $query5);
        $data5 = mysqli_fetch_assoc($resultado5);
        $conteo=$data5["COUNT(*)"];
        if(!$resultado5){
            $Act_Inician_Sem_6_1 = 0;
            $Act_0_Lib_Sem_6_1 = 0;
            $Act_Par_Lib_Sem_6_1= 0;
            $Act_100_Lib_Sem_6_1= 0;

            $Act_Inician_Sem_6_2= 0;
            $Act_0_Lib_Sem_6_2= 0;
            $Act_Par_Lib_Sem_6_2= 0;
            $Act_100_Lib_Sem_6_2= 0;

            $Act_Inician_Sem_6_3= 0;
            $Act_0_Lib_Sem_6_3= 0;
            $Act_Par_Lib_Sem_6_3= 0;
            $Act_100_Lib_Sem_6_3= 0;

            $Act_Inician_Sem_6_4= 0;
            $Act_0_Lib_Sem_6_4= 0;
            $Act_Par_Lib_Sem_6_4= 0;
            $Act_100_Lib_Sem_6_4= 0;

            $Act_Inician_Sem_6_5= 0;
            $Act_0_Lib_Sem_6_5= 0;
            $Act_Par_Lib_Sem_6_5= 0;
            $Act_100_Lib_Sem_6_5= 0;

            $Act_Inician_Sem_6_6= 0;
            $Act_0_Lib_Sem_6_6= 0;
            $Act_Par_Lib_Sem_6_6= 0;
            $Act_100_Lib_Sem_6_6= 0;
        } else{
            $query5="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
            $resultado5 = mysqli_query($conexion, $query5);
            $data5 = mysqli_fetch_assoc($resultado5);

            $Act_Inician_Sem_6_1 = $data5['Act_Inician_Sem_1'];
            $Act_0_Lib_Sem_6_1 = $data5['Act_0_Lib_Sem_1']*100;
            $Act_Par_Lib_Sem_6_1= $data5['Act_Par_Lib_Sem_1']*100;
            $Act_100_Lib_Sem_6_1= $data5['Act_100_Lib_Sem_1']*100;

            $Act_Inician_Sem_6_2= $data5['Act_Inician_Sem_2'];
            $Act_0_Lib_Sem_6_2= $data5['Act_0_Lib_Sem_2']*100;
            $Act_Par_Lib_Sem_6_2= $data5['Act_Par_Lib_Sem_2']*100;
            $Act_100_Lib_Sem_6_2= $data5['Act_100_Lib_Sem_2']*100;

            $Act_Inician_Sem_6_3= $data5['Act_Inician_Sem_3'];
            $Act_0_Lib_Sem_6_3= $data5['Act_0_Lib_Sem_3']*100;
            $Act_Par_Lib_Sem_6_3= $data5['Act_Par_Lib_Sem_3']*100;
            $Act_100_Lib_Sem_6_3= $data5['Act_100_Lib_Sem_3']*100;

            $Act_Inician_Sem_6_4= $data5['Act_Inician_Sem_4'];
            $Act_0_Lib_Sem_6_4= $data5['Act_0_Lib_Sem_4']*100;
            $Act_Par_Lib_Sem_6_4= $data5['Act_Par_Lib_Sem_4']*100;
            $Act_100_Lib_Sem_6_4= $data5['Act_100_Lib_Sem_4']*100;

            $Act_Inician_Sem_6_5= $data5['Act_Inician_Sem_5'];
            $Act_0_Lib_Sem_6_5= $data5['Act_0_Lib_Sem_5']*100;
            $Act_Par_Lib_Sem_6_5= $data5['Act_Par_Lib_Sem_5']*100;
            $Act_100_Lib_Sem_6_5= $data5['Act_100_Lib_Sem_5']*100;

            $Act_Inician_Sem_6_6= $data5['Act_Inician_Sem_6'];
            $Act_0_Lib_Sem_6_6= $data5['Act_0_Lib_Sem_6']*100;
            $Act_Par_Lib_Sem_6_6= $data5['Act_Par_Lib_Sem_6']*100;
            $Act_100_Lib_Sem_6_6= $data5['Act_100_Lib_Sem_6']*100;
        }

        $semana=$semana-1;

        $query6="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
        $resultado6 = mysqli_query($conexion, $query6);
        $data6 = mysqli_fetch_assoc($resultado6);
        $conteo=$data6["COUNT(*)"];
        if($conteo==0 || $semana<=0){
            $Act_Inician_Sem_5_2= 0;
            $Act_0_Lib_Sem_5_2= 0;
            $Act_Par_Lib_Sem_5_2= 0;
            $Act_100_Lib_Sem_5_2= 0;

            $Act_Inician_Sem_5_3= 0;
            $Act_0_Lib_Sem_5_3= 0;
            $Act_Par_Lib_Sem_5_3= 0;
            $Act_100_Lib_Sem_5_3= 0;

            $Act_Inician_Sem_5_4= 0;
            $Act_0_Lib_Sem_5_4= 0;
            $Act_Par_Lib_Sem_5_4= 0;
            $Act_100_Lib_Sem_5_4= 0;

            $Act_Inician_Sem_5_5= 0;
            $Act_0_Lib_Sem_5_5= 0;
            $Act_Par_Lib_Sem_5_5= 0;
            $Act_100_Lib_Sem_5_5= 0;

            $Act_Inician_Sem_5_6= 0;
            $Act_0_Lib_Sem_5_6= 0;
            $Act_Par_Lib_Sem_5_6= 0;
            $Act_100_Lib_Sem_5_6= 0;
        } else{
            $query6="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
            $resultado6 = mysqli_query($conexion, $query6);
            $data6 = mysqli_fetch_assoc($resultado6);

            $Act_Inician_Sem_5_2= $data6['Act_Inician_Sem_2'];
            $Act_0_Lib_Sem_5_2= $data6['Act_0_Lib_Sem_2']*100;
            $Act_Par_Lib_Sem_5_2= $data6['Act_Par_Lib_Sem_2']*100;
            $Act_100_Lib_Sem_5_2= $data6['Act_100_Lib_Sem_2']*100;

            $Act_Inician_Sem_5_3= $data6['Act_Inician_Sem_3'];
            $Act_0_Lib_Sem_5_3= $data6['Act_0_Lib_Sem_3']*100;
            $Act_Par_Lib_Sem_5_3= $data6['Act_Par_Lib_Sem_3']*100;
            $Act_100_Lib_Sem_5_3= $data6['Act_100_Lib_Sem_3']*100;

            $Act_Inician_Sem_5_4= $data6['Act_Inician_Sem_4'];
            $Act_0_Lib_Sem_5_4= $data6['Act_0_Lib_Sem_4']*100;
            $Act_Par_Lib_Sem_5_4= $data6['Act_Par_Lib_Sem_4']*100;
            $Act_100_Lib_Sem_5_4= $data6['Act_100_Lib_Sem_4']*100;

            $Act_Inician_Sem_5_5= $data6['Act_Inician_Sem_5'];
            $Act_0_Lib_Sem_5_5= $data6['Act_0_Lib_Sem_5']*100;
            $Act_Par_Lib_Sem_5_5= $data6['Act_Par_Lib_Sem_5']*100;
            $Act_100_Lib_Sem_5_5= $data6['Act_100_Lib_Sem_5']*100;

            $Act_Inician_Sem_5_6 = $data6['Act_Inician_Sem_6'];
            $Act_0_Lib_Sem_5_6 = $data6['Act_0_Lib_Sem_6']*100;
            $Act_Par_Lib_Sem_5_6= $data6['Act_Par_Lib_Sem_6']*100;
            $Act_100_Lib_Sem_5_6= $data6['Act_100_Lib_Sem_6']*100;

        }

        $semana=$semana-1;
        
        $query7="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
        $resultado7 = mysqli_query($conexion, $query7);
        $data7 = mysqli_fetch_assoc($resultado7);
        $conteo=$data7["COUNT(*)"];
        if($conteo==0 || $semana<=0){

            $Act_Inician_Sem_4_3= 0;
            $Act_0_Lib_Sem_4_3= 0;
            $Act_Par_Lib_Sem_4_3= 0;
            $Act_100_Lib_Sem_4_3= 0;

            $Act_Inician_Sem_4_4= 0;
            $Act_0_Lib_Sem_4_4= 0;
            $Act_Par_Lib_Sem_4_4= 0;
            $Act_100_Lib_Sem_4_4= 0;

            $Act_Inician_Sem_4_5= 0;
            $Act_0_Lib_Sem_4_5= 0;
            $Act_Par_Lib_Sem_4_5= 0;
            $Act_100_Lib_Sem_4_5= 0;

            $Act_Inician_Sem_4_6= 0;
            $Act_0_Lib_Sem_4_6= 0;
            $Act_Par_Lib_Sem_4_6= 0;
            $Act_100_Lib_Sem_4_6= 0;
        } else{
            $query7="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
            $resultado7 = mysqli_query($conexion, $query7);
            $data7 = mysqli_fetch_assoc($resultado7);

            $Act_Inician_Sem_4_3= $data7['Act_Inician_Sem_3'];
            $Act_0_Lib_Sem_4_3= $data7['Act_0_Lib_Sem_3']*100;
            $Act_Par_Lib_Sem_4_3= $data7['Act_Par_Lib_Sem_3']*100;
            $Act_100_Lib_Sem_4_3= $data7['Act_100_Lib_Sem_3']*100;

            $Act_Inician_Sem_4_4= $data7['Act_Inician_Sem_4'];
            $Act_0_Lib_Sem_4_4= $data7['Act_0_Lib_Sem_4']*100;
            $Act_Par_Lib_Sem_4_4= $data7['Act_Par_Lib_Sem_4']*100;
            $Act_100_Lib_Sem_4_4= $data7['Act_100_Lib_Sem_4']*100;

            $Act_Inician_Sem_4_5= $data7['Act_Inician_Sem_5'];
            $Act_0_Lib_Sem_4_5= $data7['Act_0_Lib_Sem_5']*100;
            $Act_Par_Lib_Sem_4_5= $data7['Act_Par_Lib_Sem_5']*100;
            $Act_100_Lib_Sem_4_5= $data7['Act_100_Lib_Sem_5']*100;

            $Act_Inician_Sem_4_6 = $data7['Act_Inician_Sem_6'];
            $Act_0_Lib_Sem_4_6 = $data7['Act_0_Lib_Sem_6']*100;
            $Act_Par_Lib_Sem_4_6= $data7['Act_Par_Lib_Sem_6']*100;
            $Act_100_Lib_Sem_4_6= $data7['Act_100_Lib_Sem_6']*100;

        }

        $semana=$semana-1;
        
        $query8="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
        $resultado8 = mysqli_query($conexion, $query8);
        $data8 = mysqli_fetch_assoc($resultado8);
        $conteo=$data8["COUNT(*)"];
        if($conteo==0 || $semana<=0){

            $Act_Inician_Sem_3_4= 0;
            $Act_0_Lib_Sem_3_4= 0;
            $Act_Par_Lib_Sem_3_4= 0;
            $Act_100_Lib_Sem_3_4= 0;

            $Act_Inician_Sem_3_5= 0;
            $Act_0_Lib_Sem_3_5= 0;
            $Act_Par_Lib_Sem_3_5= 0;
            $Act_100_Lib_Sem_3_5= 0;

            $Act_Inician_Sem_3_6= 0;
            $Act_0_Lib_Sem_3_6= 0;
            $Act_Par_Lib_Sem_3_6= 0;
            $Act_100_Lib_Sem_3_6= 0;
        } else{
            $query8="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
            $resultado8 = mysqli_query($conexion, $query8);
            $data8 = mysqli_fetch_assoc($resultado8);

            $Act_Inician_Sem_3_4= $data8['Act_Inician_Sem_4'];
            $Act_0_Lib_Sem_3_4= $data8['Act_0_Lib_Sem_4']*100;
            $Act_Par_Lib_Sem_3_4= $data8['Act_Par_Lib_Sem_4']*100;
            $Act_100_Lib_Sem_3_4= $data8['Act_100_Lib_Sem_4']*100;

            $Act_Inician_Sem_3_5= $data8['Act_Inician_Sem_5'];
            $Act_0_Lib_Sem_3_5= $data8['Act_0_Lib_Sem_5']*100;
            $Act_Par_Lib_Sem_3_5= $data8['Act_Par_Lib_Sem_5']*100;
            $Act_100_Lib_Sem_3_5= $data8['Act_100_Lib_Sem_5']*100;

            $Act_Inician_Sem_3_6 = $data8['Act_Inician_Sem_6'];
            $Act_0_Lib_Sem_3_6 = $data8['Act_0_Lib_Sem_6']*100;
            $Act_Par_Lib_Sem_3_6= $data8['Act_Par_Lib_Sem_6']*100;
            $Act_100_Lib_Sem_3_6= $data8['Act_100_Lib_Sem_6']*100;

        }

        $semana=$semana-1;
        
        $query9="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
        $resultado9 = mysqli_query($conexion, $query9);
        $data9 = mysqli_fetch_assoc($resultado9);
        $conteo=$data9["COUNT(*)"];
        if($conteo==0 || $semana<=0){

            $Act_Inician_Sem_2_5= 0;
            $Act_0_Lib_Sem_2_5= 0;
            $Act_Par_Lib_Sem_2_5= 0;
            $Act_100_Lib_Sem_2_5= 0;

            $Act_Inician_Sem_2_6= 0;
            $Act_0_Lib_Sem_2_6= 0;
            $Act_Par_Lib_Sem_2_6= 0;
            $Act_100_Lib_Sem_2_6= 0;
        } else{
            $query9="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
            $resultado9 = mysqli_query($conexion, $query9);
            $data9 = mysqli_fetch_assoc($resultado9);

            $Act_Inician_Sem_2_5= $data9['Act_Inician_Sem_5'];
            $Act_0_Lib_Sem_2_5= $data9['Act_0_Lib_Sem_5']*100;
            $Act_Par_Lib_Sem_2_5= $data9['Act_Par_Lib_Sem_5']*100;
            $Act_100_Lib_Sem_2_5= $data9['Act_100_Lib_Sem_5']*100;

            $Act_Inician_Sem_2_6 = $data9['Act_Inician_Sem_6'];
            $Act_0_Lib_Sem_2_6 = $data9['Act_0_Lib_Sem_6']*100;
            $Act_Par_Lib_Sem_2_6= $data9['Act_Par_Lib_Sem_6']*100;
            $Act_100_Lib_Sem_2_6= $data9['Act_100_Lib_Sem_6']*100;

        }

        $semana=$semana-1;
        
        $query10="SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
        $resultado10 = mysqli_query($conexion, $query10);
        $data10 = mysqli_fetch_assoc($resultado10);
        $conteo=$data10["COUNT(*)"];
        if($conteo==0 || $semana<=0){

            $Act_Inician_Sem_1_6= 0;
            $Act_0_Lib_Sem_1_6= 0;
            $Act_Par_Lib_Sem_1_6= 0;
            $Act_100_Lib_Sem_1_6= 0;
        } else{
            $query10="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";
            $resultado10 = mysqli_query($conexion, $query10);
            $data10 = mysqli_fetch_assoc($resultado10);

            $Act_Inician_Sem_1_6 = $data10['Act_Inician_Sem_6'];
            $Act_0_Lib_Sem_1_6 = $data10['Act_0_Lib_Sem_6']*100;
            $Act_Par_Lib_Sem_1_6= $data10['Act_Par_Lib_Sem_6']*100;
            $Act_100_Lib_Sem_1_6= $data10['Act_100_Lib_Sem_6']*100;

        }
            


        $Arreglo = array("sem_6_6" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_6_6],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_6],
          ['100% Liberada', $Act_100_Lib_Sem_6_6]],

                         "sem_6_5" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_6_5],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_5],
          ['100% Liberada', $Act_100_Lib_Sem_6_5]],

                         "sem_6_4" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_6_4],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_4],
          ['100% Liberada', $Act_100_Lib_Sem_6_4]],

                         "sem_6_3" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_6_3],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_3],
          ['100% Liberada', $Act_100_Lib_Sem_6_3]],

                         "sem_6_2" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_6_2],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_2],
          ['100% Liberada', $Act_100_Lib_Sem_6_2]],

                         "sem_6_1" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_6_1],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_6_1],
          ['100% Liberada', $Act_100_Lib_Sem_6_1]],

                         "sem_5_6" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_5_6],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_6],
          ['100% Liberada', $Act_100_Lib_Sem_5_6]],

                         "sem_5_5" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_5_5],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_5],
          ['100% Liberada', $Act_100_Lib_Sem_5_5]],

                         "sem_5_4" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_5_4],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_4],
          ['100% Liberada', $Act_100_Lib_Sem_5_4]],

                         "sem_5_3" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_5_3],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_3],
          ['100% Liberada', $Act_100_Lib_Sem_5_3]],

                         "sem_5_2" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_5_2],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_5_2],
          ['100% Liberada', $Act_100_Lib_Sem_5_2]],

                         "sem_4_6" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_4_6],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_4_6],
          ['100% Liberada', $Act_100_Lib_Sem_4_6]],

                         "sem_4_5" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_4_5],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_4_5],
          ['100% Liberada', $Act_100_Lib_Sem_4_5]],

                         "sem_4_4" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_4_4],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_4_4],
          ['100% Liberada', $Act_100_Lib_Sem_4_4]],

                         "sem_4_3" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_4_3],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_4_3],
          ['100% Liberada', $Act_100_Lib_Sem_4_3]],

                         "sem_3_6" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_3_6],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_3_6],
          ['100% Liberada', $Act_100_Lib_Sem_3_6]],

                         "sem_3_5" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_3_5],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_3_5],
          ['100% Liberada', $Act_100_Lib_Sem_3_5]],

                         "sem_3_4" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_3_4],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_3_4],
          ['100% Liberada', $Act_100_Lib_Sem_3_4]],

                         "sem_2_6" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_2_6],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_2_6],
          ['100% Liberada', $Act_100_Lib_Sem_2_6]],

                         "sem_2_5" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_2_5],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_2_5],
          ['100% Liberada', $Act_100_Lib_Sem_2_5]],

                         "sem_1_6" => [['Estado de Liberación', '%'],
          ['0% Liberada', $Act_0_Lib_Sem_1_6],
          ['Parcialmente Liberada', $Act_Par_Lib_Sem_1_6],
          ['100% Liberada', $Act_100_Lib_Sem_1_6]]

                        );

        $json_codificado = json_encode($Arreglo, JSON_UNESCAPED_UNICODE);
        echo $json_codificado;
  
    }
    

        /*$Criticas_Comp = $data['Criticas_Comp'];
        $No_Criticas_Comp = $data['No_Criticas_Comp'];
        $Atrasadas_Comp = $data['Atrasadas_Comp'];
        $Comp_Sin_Rest_100 = $data['Comp_Sin_Rest_100'];
        
        if($Criticas_Comp == "NA" || $Criticas_Comp === null){
            $Criticas_Comp_val=0;
            $Criticas_Comp = "NA";
        }else{
            $Criticas_Comp_val=$Criticas_Comp*100;
        }
        if($No_Criticas_Comp == "NA" || $No_Criticas_Comp === null){
            $No_Criticas_Comp_val=0;
            $No_Criticas_Comp = "NA";
        }else{
            $No_Criticas_Comp_val=$No_Criticas_Comp*100;
        }
        if($Atrasadas_Comp == "NA" || $Atrasadas_Comp === null){
            $Atrasadas_Comp_val=0;
            $Atrasadas_Comp = "NA";
        }else{
            $Atrasadas_Comp_val=$Atrasadas_Comp*100;
        }
        if($Comp_Sin_Rest_100 == "NA" || $Comp_Sin_Rest_100 === null){
            $Comp_Sin_Rest_100_val=0;
            $Comp_Sin_Rest_100 = "NA";
        }else{
            $Comp_Sin_Rest_100_val=$Comp_Sin_Rest_100*100;
        }
    }
    
    
    
    
    for($j=$semana; $j>=0; $j=$j-1) {
        $query6="SELECT * FROM $db"."_indicadores_generales WHERE Semana=$j AND subcontratista_profesional='$nombre';";
        $resultado6 = mysqli_query($conexion, $query6);
        if(!$resultado6){
            die("Error");
        } else{
            
            $data = mysqli_fetch_assoc($resultado6);
            if (!$data){
                
            }else{
                
                $Criticas_Comp_Acum = $data['Criticas_Comp_Acum'];
                
                $No_Criticas_Comp_Acum = $data['No_Criticas_Comp_Acum'];
                
                $Atrasadas_Comp_Acum = $data['Atrasadas_Comp_Acum'];
                
                $Comp_Sin_Rest_100_Acum = $data['Comp_Sin_Rest_100_Acum'];
                
                if($Criticas_Comp_Acum == "NA"){
                    $Criticas_Comp_Acum_val=0;
                }else{
                    $Criticas_Comp_Acum_val=$Criticas_Comp_Acum*100;
                }
                if($No_Criticas_Comp_Acum == "NA"){
                    $No_Criticas_Comp_Acum_val=0;
                }else{
                    $No_Criticas_Comp_Acum_val=$No_Criticas_Comp_Acum*100;
                }
                if($Atrasadas_Comp_Acum == "NA"){
                    $Atrasadas_Comp_Acum_val=0;
                }else{
                    $Atrasadas_Comp_Acum_val=$Atrasadas_Comp_Acum*100;
                }
                if($Comp_Sin_Rest_100_Acum == "NA"){
                    $Comp_Sin_Rest_100_Acum_val=0;
                }else{
                    $Comp_Sin_Rest_100_Acum_val=$Comp_Sin_Rest_100_Acum*100;
                }
                
                $j=0;
            } 
        }
    }
    
    require ("../conexion.php");
        
        $query7="SELECT * FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre';";
        $resultado7 = mysqli_query($conexion, $query7);
        if(!$resultado7){
            die("Error");
        } else{
            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Criticas_Comp' , 'label' => '% Actividades Críticas Comprometidas' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Criticas_Comp_label' , 'label' => '% Actividades Críticas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
            $array['cols'][3] = array('id' => 'No_Criticas_Comp' , 'label' => '% Actividades No Críticas Comprometidas' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'No_Criticas_Comp_label' , 'label' => '% Actividades No Críticas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
            $array['cols'][5] = array('id' => 'Atrasadas_Comp' , 'label' => '% Actividades Atrasadas Comprometidas' , 'type' => 'number');
            $array['cols'][6] = array('id' => 'Atrasadas_Comp_label' , 'label' => '% Actividades Atrasadas Comprometidas' , 'type' => 'string', 'role' => 'annotation' );
            $array['cols'][7] = array('id' => 'Comp_Sin_Rest_100' , 'label' => '% Actividades Comprometidas Sin Liberar Restricciones' , 'type' => 'number');
            $array['cols'][8] = array('id' => 'Comp_Sin_Rest_100_label' , 'label' => '% Actividades Comprometidas Sin Liberar Restricciones' , 'type' => 'string', 'role' => 'annotation' );

            while($row=mysqli_fetch_assoc($resultado7)){ 
                $Semana = "Semana " . $row['Semana'];
                $Criticas_Comp_1 = $row['Criticas_Comp'];
                if($Criticas_Comp_1 == "NA"){
                    $Criticas_Comp_2=0;
                }else{
                    $Criticas_Comp_2=$Criticas_Comp_1;
                    $Criticas_Comp_1=round($Criticas_Comp_1*100,0) . "%";
                }
                $No_Criticas_Comp_1 = $row['No_Criticas_Comp'];
                if($No_Criticas_Comp_1 == "NA"){
                    $No_Criticas_Comp_2=0;
                }else{
                    $No_Criticas_Comp_2=$No_Criticas_Comp_1;
                    $No_Criticas_Comp_1=round($No_Criticas_Comp_1*100,0) . "%";
                }
                $Atrasadas_Comp_1 = $row['Atrasadas_Comp'];
                if($Atrasadas_Comp_1 == "NA"){
                    $Atrasadas_Comp_2=0;
                }else{
                    $Atrasadas_Comp_2=$Atrasadas_Comp_1;
                    $Atrasadas_Comp_1=round($Atrasadas_Comp_1*100,0) . "%";
                }
                $Comp_Sin_Rest_100_1 = $row['Comp_Sin_Rest_100'];
                if($Comp_Sin_Rest_100_1 == "NA"){
                    $Comp_Sin_Rest_100_2=0;
                }else{
                    $Comp_Sin_Rest_100_2=$Comp_Sin_Rest_100_1;
                    $Comp_Sin_Rest_100_1=round($Comp_Sin_Rest_100_1*100,0) . "%";
                }

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                      array('v'=>$Criticas_Comp_2, 'f'=>$Criticas_Comp_1),
                                                      array('v'=> $Criticas_Comp_1),
                                                      array('v'=>$No_Criticas_Comp_2, 'f'=>$No_Criticas_Comp_1),
                                                      array('v'=> $No_Criticas_Comp_1),
                                                      array('v'=>$Atrasadas_Comp_2, 'f'=>$Atrasadas_Comp_1),
                                                      array('v'=> $Atrasadas_Comp_1),
                                                      array('v'=>$Comp_Sin_Rest_100_2, 'f'=>$Comp_Sin_Rest_100_1),
                                                      array('v'=> $Comp_Sin_Rest_100_1)

                                                     ));
            }
        }

    
    
        $array = array([$Criticas_Comp_val, $No_Criticas_Comp_val, $Atrasadas_Comp_val, $Comp_Sin_Rest_100_val, 100], [$Criticas_Comp_Acum_val, $No_Criticas_Comp_Acum_val, $Atrasadas_Comp_Acum_val, $Comp_Sin_Rest_100_Acum_val, 100],[$Criticas_Comp, $No_Criticas_Comp, $Atrasadas_Comp, $Comp_Sin_Rest_100, 100], [$Criticas_Comp_Acum, $No_Criticas_Comp_Acum, $Atrasadas_Comp_Acum, $Comp_Sin_Rest_100_Acum, 100],[$array]);    
    
        $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
        echo utf8_decode($json_codificado);
        mysqli_close($conexion);*/
        
}

function cal_contratistas($conexion, $semana, $db, $nombre){
    //crear_indicadores($conexion, $semana, $db); 
    require ("../conexion.php");
    if($nombre=="general"){
        $nombre="consolidado general";
        $script="";
    }else{
        $nombre=$nombre;
        $script="WHERE subcontratista='$nombre'";
    }
    
    $query5="SELECT COUNT(DISTINCT subcontratista) FROM $db"."_cic $script";
    $resultado5 = mysqli_query($conexion, $query5);
    $data=mysqli_fetch_assoc($resultado5);
    $conteo=$data["COUNT(DISTINCT subcontratista)"];
    //echo $conteo;
    if($conteo==0){
        $array['cols'][0] = array('id' => 'Sub-Contratista' , 'label' => 'Sub-Contratista' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Calificacion_Integral_Acumulada' , 'label' => 'Calificación Integral Acumulada' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Calificacion_Integral_Acumulada_label' , 'label' => 'Calificación Integral Acumulada' , 'type' => 'string', 'role' => 'annotation' );
        $array['cols'][3] = array('id' => 'Calificacion_Integral' , 'label' => "Calificación Integral Última Semana" , 'type' => 'number');
        $array['cols'][4] = array('id' => 'Calificacion_Integral_label' , 'label' => "Calificación Integral Última Semana" , 'type' => 'string', 'role' => 'annotation' );
        
    }else{

        require ("../conexion.php");

        $query6="SELECT DISTINCT subcontratista FROM $db"."_cic $script ;";
        $resultado6 = mysqli_query($conexion, $query6);
        if(!$resultado6){
            die("Error");
        } else{
            $array['cols'][0] = array('id' => 'Sub-Contratista' , 'label' => 'Sub-Contratista' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Calificacion_Integral_Acumulada' , 'label' => 'Calificación Integral Acumulada' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Calificacion_Integral_Acumulada_label' , 'label' => 'Calificación Integral Acumulada' , 'type' => 'string', 'role' => 'annotation' );
            $array['cols'][3] = array('id' => 'Calificacion_Integral' , 'label' => "Calificación Integral Última Semana" , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Calificacion_Integral_label' , 'label' => "Calificación Integral Última Semana" , 'type' => 'string', 'role' => 'annotation' );
            

            while($row=mysqli_fetch_assoc($resultado6)){ 
                $subcontratista = $row['subcontratista'];
                $query7="SELECT * FROM $db"."_cic WHERE subcontratista='$subcontratista' AND Semana =(SELECT CASE WHEN (SELECT MAX(Semana) FROM $db"."_cic WHERE subcontratista='$subcontratista') > $semana THEN $semana 
                ELSE (SELECT MAX(Semana) FROM $db"."_cic WHERE subcontratista='$subcontratista') 
                END);";
                //echo $query7;
                $resultado7 = mysqli_query($conexion, $query7);
                while($data1=mysqli_fetch_assoc($resultado7)){
                    $ultima_semana=$data1['Semana'];
                    $subcontratista = $data1['subcontratista'];
                    if($ultima_semana<$semana){
                        $cic_ultima_semana = 0;
                        $cic_ultima_semana1 = "NA";
                    }else{
                        $cic_ultima_semana = $data1['Cal_Integral'];
                        $cic_ultima_semana1 = round($data1['Cal_Integral']*100,0) . "%";    
                    }
                    
                    
                    $cic_acum = $data1['Cal_Integral_Acum'];
                    $cic_acum1 = round($data1['Cal_Integral_Acum']*100,0) . "%";


                    $array['rows'][] = array('c' => array( array('v'=> $subcontratista),
                                                          array('v'=>$cic_acum, 'f'=>$cic_acum1),
                                                          array('v'=> $cic_acum1),
                                                          array('v'=>$cic_ultima_semana, 'f'=>$cic_ultima_semana1),
                                                          array('v'=> $cic_ultima_semana1)

                                                         ));
                } 
            }
        }  
    }
    
    

    
    
            
    
        $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
        echo $json_codificado;
        mysqli_close($conexion);
        
}

function cal_profesionales($conexion, $semana, $db, $nombre){
    //crear_indicadores($conexion, $semana, $db); 
    require ("../conexion.php");
    if($nombre=="general"){
        $nombre="consolidado general";
        $script="";
    }else{
        $nombre=$nombre;
        $script="WHERE profesional='$nombre'";
    }
    
    $query5="SELECT COUNT(DISTINCT profesional) FROM $db"."_cip $script";
    $resultado5 = mysqli_query($conexion, $query5);
    $data=mysqli_fetch_assoc($resultado5);
    $conteo=$data["COUNT(DISTINCT profesional)"];
    //echo $conteo;
    if($conteo==0){
        $array['cols'][0] = array('id' => 'Profesional' , 'label' => 'Sub-Contratista' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Calificacion_Integral_Acumulada' , 'label' => 'Calificación Integral Acumulada' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Calificacion_Integral_Acumulada_label' , 'label' => 'Calificación Integral Acumulada' , 'type' => 'string', 'role' => 'annotation' );
        $array['cols'][3] = array('id' => 'Calificacion_Integral' , 'label' => "Calificación Integral Última Semana" , 'type' => 'number');
        $array['cols'][4] = array('id' => 'Calificacion_Integral_label' , 'label' => "Calificación Integral Última Semana" , 'type' => 'string', 'role' => 'annotation' );
        
    }else{

        require ("../conexion.php");

        $query6="SELECT DISTINCT profesional FROM $db"."_cip $script ;";
        $resultado6 = mysqli_query($conexion, $query6);
        if(!$resultado6){
            die("Error");
        } else{
            $array['cols'][0] = array('id' => 'Profesional' , 'label' => 'Sub-Contratista' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Calificacion_Integral_Acumulada' , 'label' => 'Calificación Integral Acumulada' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Calificacion_Integral_Acumulada_label' , 'label' => 'Calificación Integral Acumulada' , 'type' => 'string', 'role' => 'annotation' );
            $array['cols'][3] = array('id' => 'Calificacion_Integral' , 'label' => "Calificación Integral Última Semana" , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Calificacion_Integral_label' , 'label' => "Calificación Integral Última Semana" , 'type' => 'string', 'role' => 'annotation' );
            

            while($row=mysqli_fetch_assoc($resultado6)){ 
                $profesional = $row['profesional'];
                $query7="SELECT * FROM $db"."_cip WHERE profesional='$profesional' AND Semana =(SELECT CASE WHEN (SELECT MAX(Semana) FROM $db"."_cip WHERE profesional='$profesional') > $semana THEN $semana 
                ELSE (SELECT MAX(Semana) FROM $db"."_cip WHERE profesional='$profesional') 
                END);";
                //echo $query7;
                $resultado7 = mysqli_query($conexion, $query7);
                while($data1=mysqli_fetch_assoc($resultado7)){
                    $ultima_semana=$data1['Semana'];
                    $profesional = $data1['profesional'];
                    if($ultima_semana<$semana){
                        $pac_consolidado = 0;
                        $pac_consolidado1 ="NA";
                    }else{
                        $pac_consolidado = $data1['PAC_Consolidado'];
                        $pac_consolidado1 = round($data1['PAC_Consolidado']*100,0) . "%";   
                    }
                    
                    $pac_consolidado_acum = $data1['PAC_Consolidado_Acum'];
                    $pac_consolidado_acum1 = round($data1['PAC_Consolidado_Acum']*100,0) . "%";


                    $array['rows'][] = array('c' => array( array('v'=> $profesional),
                                                          array('v'=>$pac_consolidado_acum, 'f'=>$pac_consolidado_acum1),
                                                          array('v'=> $pac_consolidado_acum1),
                                                          array('v'=>$pac_consolidado, 'f'=>$pac_consolidado1),
                                                          array('v'=> $pac_consolidado1)

                                                         ));
                } 
            }
        }  
    }
    
    

    
    
            
    
        $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
        echo $json_codificado;
        mysqli_close($conexion);
        
}

function nombre_PAC($conexion, $semana, $db, $tipo_PAC){
        $cadena="";
        if($tipo_PAC=="general"){
            $cadena .= "<option value='general'>General</option>";
            
        }else if($tipo_PAC=="profesional"){
            $query="SELECT DISTINCT profesional FROM $db"."_cip";
            $resultado= mysqli_query($conexion, $query);
            while ($valores = mysqli_fetch_assoc($resultado)){
                $cadena .= '<option value="'.$valores["profesional"].'">'.$valores["profesional"].'</option>';
            };
            
        }else if($tipo_PAC=="subcontratista"){
            $query="SELECT DISTINCT subcontratista FROM $db"."_cic";
            $resultado= mysqli_query($conexion, $query);
            while ($valores = mysqli_fetch_assoc($resultado)){
                $cadena .= '<option value="'.$valores["subcontratista"].'">'.$valores["subcontratista"].'</option>';
            };
        };
        echo $cadena;
}

function crear_indicadores($conexion, $semana, $db){
    $semana1=$semana;
    if($semana1>6){
        $semana=($semana1-6);
    }else{
        $semana=1;
    }
    $query="DELETE FROM $db"."_indicadores_generales WHERE (Semana>=$semana AND Semana<=$semana1)";
    $resultado= mysqli_query($conexion, $query);
    
    while($semana<=$semana1){
        require ("../conexion.php");
        $query="SELECT  COUNT(*) FROM $db"."_indicadores_generales WHERE (Semana=$semana)";
        $resultado= mysqli_query($conexion, $query);
        $data=mysqli_fetch_assoc($resultado);
        $conteo=$data["COUNT(*)"];
        mysqli_close($conexion);
        if ($conteo==0){
            require ("../conexion.php");
            $query1= "SELECT 'consolidado general' AS 'subcontratista_profesional', 

                             'consolidado general' AS 'rol', 

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA'))=0 THEN 'NA' ELSE ROUND((SELECT AVG(PAC) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA')),3) END) AS 'PAC',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA'))=0 THEN 'NA' ELSE ROUND((SELECT AVG(P_Completado) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA')),3) END) AS 'P_Completado',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Rendimiento',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Rendimiento_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Programacion',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Programacion_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_MdeO',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_MdeO_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Materiales',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Materiales_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Equipos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Equipos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Disenos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Disenos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Administrativas',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Administrativas_Acum',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Atrasada=0)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Activa=1 AND Atrasada=0 AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Atrasada=0)) ,3) END) AS 'Criticas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND Atrasada=0)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Atrasada=0 AND Compromiso>0 AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND Atrasada=0)) ,3) END) AS 'No_Criticas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Atrasada=1)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Atrasada=1 AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Atrasada=1)) ,3) END) AS 'Atrasadas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Prog_Sin_Restricciones_100=1 AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1)) ,3) END) AS 'Comp_Sin_Rest_100',

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7) AS 'Act_Inician_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7) ,3) END) AS 'Act_0_Lib_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7) ,3) END) AS 'Act_Par_Lib_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7) ,3) END) AS 'Act_100_Lib_Sem_1',

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14) AS Act_Inician_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14) ,3) END) AS Act_0_Lib_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14) ,3) END) AS Act_Par_Lib_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14) ,3) END) AS Act_100_Lib_Sem_2,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141) AS Act_Inician_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141) ,3) END) AS Act_0_Lib_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141) ,3) END) AS Act_Par_Lib_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141) ,3) END) AS Act_100_Lib_Sem_3,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28) AS Act_Inician_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28) ,3) END) AS Act_0_Lib_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28) ,3) END) AS Act_Par_Lib_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28) ,3) END) AS Act_100_Lib_Sem_4,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) AS Act_Inician_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) ,3) END) AS Act_0_Lib_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) ,3) END) AS Act_Par_Lib_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) ,3) END) AS Act_100_Lib_Sem_5,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42) AS Act_Inician_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42) ,3) END) AS Act_0_Lib_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42) ,3) END) AS Act_Par_Lib_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42) ,3) END) AS Act_100_Lib_Sem_6
                              ;";
            
            //echo $query1;
            $resultado1= mysqli_query($conexion, $query1);
            $data1=mysqli_fetch_assoc($resultado1);
            $subcontratista_profesional=$data1["subcontratista_profesional"];
            $rol=$data1["rol"];
            $PAC=$data1["PAC"];
            $P_Completado=$data1["P_Completado"];
            $CNC_Rendimiento=$data1["CNC_Rendimiento"];
            $CNC_Rendimiento_Acum=$data1["CNC_Rendimiento_Acum"];
            $CNC_Programacion=$data1["CNC_Programacion"];
            $CNC_Programacion_Acum=$data1["CNC_Programacion_Acum"];
            $CNC_MdeO=$data1["CNC_MdeO"];
            $CNC_MdeO_Acum=$data1["CNC_MdeO_Acum"];
            $CNC_Materiales=$data1["CNC_Materiales"];
            $CNC_Materiales_Acum=$data1["CNC_Materiales_Acum"];
            $CNC_Equipos=$data1["CNC_Equipos"];
            $CNC_Equipos_Acum=$data1["CNC_Equipos_Acum"];
            $CNC_Disenos=$data1["CNC_Disenos"];
            $CNC_Disenos_Acum=$data1["CNC_Disenos_Acum"];
            $CNC_Administrativas=$data1["CNC_Administrativas"];
            $CNC_Administrativas_Acum=$data1["CNC_Administrativas_Acum"];
            $Criticas_Comp=$data1["Criticas_Comp"];
            $No_Criticas_Comp=$data1["No_Criticas_Comp"];
            $Atrasadas_Comp=$data1["Atrasadas_Comp"];
            $Comp_Sin_Rest_100=$data1["Comp_Sin_Rest_100"];
            $Act_Inician_Sem_1=$data1["Act_Inician_Sem_1"];
            $Act_0_Lib_Sem_1=$data1["Act_0_Lib_Sem_1"];
            $Act_Par_Lib_Sem_1=$data1["Act_Par_Lib_Sem_1"];
            $Act_100_Lib_Sem_1=$data1["Act_100_Lib_Sem_1"];
            $Act_Inician_Sem_2=$data1["Act_Inician_Sem_2"];
            $Act_0_Lib_Sem_2=$data1["Act_0_Lib_Sem_2"];
            $Act_Par_Lib_Sem_2=$data1["Act_Par_Lib_Sem_2"];
            $Act_100_Lib_Sem_2=$data1["Act_100_Lib_Sem_2"];
            $Act_Inician_Sem_3=$data1["Act_Inician_Sem_3"];
            $Act_0_Lib_Sem_3=$data1["Act_0_Lib_Sem_3"];
            $Act_Par_Lib_Sem_3=$data1["Act_Par_Lib_Sem_3"];
            $Act_100_Lib_Sem_3=$data1["Act_100_Lib_Sem_3"];
            $Act_Inician_Sem_4=$data1["Act_Inician_Sem_4"];
            $Act_0_Lib_Sem_4=$data1["Act_0_Lib_Sem_4"];
            $Act_Par_Lib_Sem_4=$data1["Act_Par_Lib_Sem_4"];
            $Act_100_Lib_Sem_4=$data1["Act_100_Lib_Sem_4"];
            $Act_Inician_Sem_5=$data1["Act_Inician_Sem_5"];
            $Act_0_Lib_Sem_5=$data1["Act_0_Lib_Sem_5"];
            $Act_Par_Lib_Sem_5=$data1["Act_Par_Lib_Sem_5"];
            $Act_100_Lib_Sem_5=$data1["Act_100_Lib_Sem_5"];
            $Act_Inician_Sem_6=$data1["Act_Inician_Sem_6"];
            $Act_0_Lib_Sem_6=$data1["Act_0_Lib_Sem_6"];
            $Act_Par_Lib_Sem_6=$data1["Act_Par_Lib_Sem_6"];
            $Act_100_Lib_Sem_6=$data1["Act_100_Lib_Sem_6"];
            
            
            $query1_1="INSERT INTO $db"."_indicadores_generales (
                                                              Id, 
                                                              Semana, 
                                                              subcontratista_profesional, 
                                                              rol, 
                                                              PAC, 
                                                              P_Completado,
                                                              CNC_Rendimiento,
                                                              CNC_Rendimiento_Acum,
                                                              CNC_Programacion,
                                                              CNC_Programacion_Acum,
                                                              CNC_MdeO,
                                                              CNC_MdeO_Acum,
                                                              CNC_Materiales,
                                                              CNC_Materiales_Acum,
                                                              CNC_Equipos,
                                                              CNC_Equipos_Acum,
                                                              CNC_Disenos,
                                                              CNC_Disenos_Acum,
                                                              CNC_Administrativas,
                                                              CNC_Administrativas_Acum,
                                                              Criticas_Comp,
                                                              No_Criticas_Comp,
                                                              Atrasadas_Comp,
                                                              Comp_Sin_Rest_100,
                                                              Act_Inician_Sem_1,
                                                              Act_0_Lib_Sem_1,
                                                              Act_Par_Lib_Sem_1,
                                                              Act_100_Lib_Sem_1,
                                                              Act_Inician_Sem_2,
                                                              Act_0_Lib_Sem_2,
                                                              Act_Par_Lib_Sem_2,
                                                              Act_100_Lib_Sem_2,
                                                              Act_Inician_Sem_3,
                                                              Act_0_Lib_Sem_3,
                                                              Act_Par_Lib_Sem_3,
                                                              Act_100_Lib_Sem_3,
                                                              Act_Inician_Sem_4,
                                                              Act_0_Lib_Sem_4,
                                                              Act_Par_Lib_Sem_4,
                                                              Act_100_Lib_Sem_4,
                                                              Act_Inician_Sem_5,
                                                              Act_0_Lib_Sem_5,
                                                              Act_Par_Lib_Sem_5,
                                                              Act_100_Lib_Sem_5,
                                                              Act_Inician_Sem_6,
                                                              Act_0_Lib_Sem_6,
                                                              Act_Par_Lib_Sem_6,
                                                              Act_100_Lib_Sem_6
                                                              ) 

                                                               VALUES(
                                                                     NULL, 
                                                                     $semana,
                                                                     '$subcontratista_profesional', 
                                                                     '$rol', 
                                                                     '$PAC', 
                                                                     '$P_Completado',
                                                                     '$CNC_Rendimiento',
                                                                     '$CNC_Rendimiento_Acum',
                                                                     '$CNC_Programacion',
                                                                     '$CNC_Programacion_Acum',
                                                                     '$CNC_MdeO',
                                                                     '$CNC_MdeO_Acum',
                                                                     '$CNC_Materiales',
                                                                     '$CNC_Materiales_Acum',
                                                                     '$CNC_Equipos',
                                                                     '$CNC_Equipos_Acum',
                                                                     '$CNC_Disenos',
                                                                     '$CNC_Disenos_Acum',
                                                                     '$CNC_Administrativas',
                                                                     '$CNC_Administrativas_Acum',
                                                                     '$Criticas_Comp',
                                                                     '$No_Criticas_Comp',
                                                                     '$Atrasadas_Comp',
                                                                     '$Comp_Sin_Rest_100',
                                                                     '$Act_Inician_Sem_1',
                                                                     '$Act_0_Lib_Sem_1',
                                                                     '$Act_Par_Lib_Sem_1',
                                                                     '$Act_100_Lib_Sem_1',
                                                                     '$Act_Inician_Sem_2',
                                                                     '$Act_0_Lib_Sem_2',
                                                                     '$Act_Par_Lib_Sem_2',
                                                                     '$Act_100_Lib_Sem_2',
                                                                     '$Act_Inician_Sem_3',
                                                                     '$Act_0_Lib_Sem_3',
                                                                     '$Act_Par_Lib_Sem_3',
                                                                     '$Act_100_Lib_Sem_3',
                                                                     '$Act_Inician_Sem_4',
                                                                     '$Act_0_Lib_Sem_4',
                                                                     '$Act_Par_Lib_Sem_4',
                                                                     '$Act_100_Lib_Sem_4',
                                                                     '$Act_Inician_Sem_5',
                                                                     '$Act_0_Lib_Sem_5',
                                                                     '$Act_Par_Lib_Sem_5',
                                                                     '$Act_100_Lib_Sem_5',
                                                                     '$Act_Inician_Sem_6',
                                                                     '$Act_0_Lib_Sem_6',
                                                                     '$Act_Par_Lib_Sem_6',
                                                                     '$Act_100_Lib_Sem_6'
                                                                     );";
            //echo $query1_1;
            $resultado1= mysqli_multi_query($conexion, $query1_1);
            
            $query1_2 = "SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(PAC) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general') ,3) END) AS PAC_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(P_Completado) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general') ,3) END) AS P_Completado_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Criticas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Criticas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Criticas_Comp!='NA') ,3) END) AS Criticas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND No_Criticas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(No_Criticas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND No_Criticas_Comp!='NA') ,3) END) AS No_Criticas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Atrasadas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Atrasadas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Atrasadas_Comp!='NA') ,3) END) AS Atrasadas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Comp_Sin_Rest_100!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Comp_Sin_Rest_100) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Comp_Sin_Rest_100!='NA') ,3) END) AS Comp_Sin_Rest_100_Acum;";
                
            $resultado1_2= mysqli_query($conexion, $query1_2);
            $data1_2=mysqli_fetch_assoc($resultado1_2);
            $PAC_Acum=$data1_2["PAC_Acum"];
            $P_Completado_Acum=$data1_2["P_Completado_Acum"];
            $Criticas_Comp_Acum=$data1_2["Criticas_Comp_Acum"];
            $No_Criticas_Comp_Acum=$data1_2["No_Criticas_Comp_Acum"];
            $Atrasadas_Comp_Acum=$data1_2["Atrasadas_Comp_Acum"];
            $Comp_Sin_Rest_100_Acum=$data1_2["Comp_Sin_Rest_100_Acum"];
            
            $query1_3 = "UPDATE $db"."_indicadores_generales SET 
                                                               
                                                               PAC_Acum='$PAC_Acum',

                                                               P_Completado_Acum='$P_Completado_Acum',

                                                               Criticas_Comp_Acum='$Criticas_Comp_Acum',

                                                               No_Criticas_Comp_Acum='$No_Criticas_Comp_Acum',

                                                               Atrasadas_Comp_Acum='$Atrasadas_Comp_Acum',

                                                               Comp_Sin_Rest_100_Acum='$Comp_Sin_Rest_100_Acum'

                                                               WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";

            $resultado1_3 = mysqli_multi_query($conexion, $query1_3);   
            
            
        } else{
            require ("../conexion.php");
            $query1= "SELECT 'consolidado general' AS 'subcontratista_profesional', 

                             'consolidado general' AS 'rol', 

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA'))=0 THEN 'NA' ELSE ROUND((SELECT AVG(PAC) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA')),3) END) AS 'PAC',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA'))=0 THEN 'NA' ELSE ROUND((SELECT AVG(P_Completado) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA')),3) END) AS 'P_Completado',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Rendimiento',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Rendimiento_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Programacion',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Programacion_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_MdeO',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_MdeO_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Materiales',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Materiales_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Equipos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Equipos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Disenos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Disenos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Administrativas',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana<=$semana AND (Activa=1 OR Activa='NA')) AS 'CNC_Administrativas_Acum',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Atrasada=0)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Activa=1 AND Atrasada=0 AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Atrasada=0)) ,3) END) AS 'Criticas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND Atrasada=0)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Atrasada=0 AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND Atrasada=0)) ,3) END) AS 'No_Criticas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Atrasada=1)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Atrasada=1 AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Atrasada=1)) ,3) END) AS 'Atrasadas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Prog_Sin_Restricciones_100=1 AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1)) ,3) END) AS 'Comp_Sin_Rest_100',

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7) AS 'Act_Inician_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7) ,3) END) AS 'Act_0_Lib_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7) ,3) END) AS 'Act_Par_Lib_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7) ,3) END) AS 'Act_100_Lib_Sem_1',

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14) AS Act_Inician_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14) ,3) END) AS Act_0_Lib_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14) ,3) END) AS Act_Par_Lib_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14) ,3) END) AS Act_100_Lib_Sem_2,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141) AS Act_Inician_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141) ,3) END) AS Act_0_Lib_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141) ,3) END) AS Act_Par_Lib_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141) ,3) END) AS Act_100_Lib_Sem_3,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28) AS Act_Inician_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28) ,3) END) AS Act_0_Lib_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28) ,3) END) AS Act_Par_Lib_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28) ,3) END) AS Act_100_Lib_Sem_4,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) AS Act_Inician_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) ,3) END) AS Act_0_Lib_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) ,3) END) AS Act_Par_Lib_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) ,3) END) AS Act_100_Lib_Sem_5,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42) AS Act_Inician_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones=0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42) ,3) END) AS Act_0_Lib_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones<1 AND Estado_Restricciones>0) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42) ,3) END) AS Act_Par_Lib_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42)=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones=1) / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42) ,3) END) AS Act_100_Lib_Sem_6
                             ;";
            
            
            $resultado1= mysqli_query($conexion, $query1);
            $data1=mysqli_fetch_assoc($resultado1);
            $subcontratista_profesional=$data1["subcontratista_profesional"];
            $rol=$data1["rol"];
            $PAC=$data1["PAC"];
            $P_Completado=$data1["P_Completado"];
            $CNC_Rendimiento=$data1["CNC_Rendimiento"];
            $CNC_Rendimiento_Acum=$data1["CNC_Rendimiento_Acum"];
            $CNC_Programacion=$data1["CNC_Programacion"];
            $CNC_Programacion_Acum=$data1["CNC_Programacion_Acum"];
            $CNC_MdeO=$data1["CNC_MdeO"];
            $CNC_MdeO_Acum=$data1["CNC_MdeO_Acum"];
            $CNC_Materiales=$data1["CNC_Materiales"];
            $CNC_Materiales_Acum=$data1["CNC_Materiales_Acum"];
            $CNC_Equipos=$data1["CNC_Equipos"];
            $CNC_Equipos_Acum=$data1["CNC_Equipos_Acum"];
            $CNC_Disenos=$data1["CNC_Disenos"];
            $CNC_Disenos_Acum=$data1["CNC_Disenos_Acum"];
            $CNC_Administrativas=$data1["CNC_Administrativas"];
            $CNC_Administrativas_Acum=$data1["CNC_Administrativas_Acum"];
            $Criticas_Comp=$data1["Criticas_Comp"];
            $No_Criticas_Comp=$data1["No_Criticas_Comp"];
            $Atrasadas_Comp=$data1["Atrasadas_Comp"];
            $Comp_Sin_Rest_100=$data1["Comp_Sin_Rest_100"];
            $Act_Inician_Sem_1=$data1["Act_Inician_Sem_1"];
            $Act_0_Lib_Sem_1=$data1["Act_0_Lib_Sem_1"];
            $Act_Par_Lib_Sem_1=$data1["Act_Par_Lib_Sem_1"];
            $Act_100_Lib_Sem_1=$data1["Act_100_Lib_Sem_1"];
            $Act_Inician_Sem_2=$data1["Act_Inician_Sem_2"];
            $Act_0_Lib_Sem_2=$data1["Act_0_Lib_Sem_2"];
            $Act_Par_Lib_Sem_2=$data1["Act_Par_Lib_Sem_2"];
            $Act_100_Lib_Sem_2=$data1["Act_100_Lib_Sem_2"];
            $Act_Inician_Sem_3=$data1["Act_Inician_Sem_3"];
            $Act_0_Lib_Sem_3=$data1["Act_0_Lib_Sem_3"];
            $Act_Par_Lib_Sem_3=$data1["Act_Par_Lib_Sem_3"];
            $Act_100_Lib_Sem_3=$data1["Act_100_Lib_Sem_3"];
            $Act_Inician_Sem_4=$data1["Act_Inician_Sem_4"];
            $Act_0_Lib_Sem_4=$data1["Act_0_Lib_Sem_4"];
            $Act_Par_Lib_Sem_4=$data1["Act_Par_Lib_Sem_4"];
            $Act_100_Lib_Sem_4=$data1["Act_100_Lib_Sem_4"];
            $Act_Inician_Sem_5=$data1["Act_Inician_Sem_5"];
            $Act_0_Lib_Sem_5=$data1["Act_0_Lib_Sem_5"];
            $Act_Par_Lib_Sem_5=$data1["Act_Par_Lib_Sem_5"];
            $Act_100_Lib_Sem_5=$data1["Act_100_Lib_Sem_5"];
            $Act_Inician_Sem_6=$data1["Act_Inician_Sem_6"];
            $Act_0_Lib_Sem_6=$data1["Act_0_Lib_Sem_6"];
            $Act_Par_Lib_Sem_6=$data1["Act_Par_Lib_Sem_6"];
            $Act_100_Lib_Sem_6=$data1["Act_100_Lib_Sem_6"];
            
            $query1_1 = "UPDATE $db"."_indicadores_generales SET 
                                                               PAC='$PAC',
                                                               P_Completado='$P_Completado',
                                                               CNC_Rendimiento='$CNC_Rendimiento',
                                                               CNC_Rendimiento_Acum='$CNC_Rendimiento_Acum',
                                                               CNC_Programacion='$CNC_Programacion',
                                                               CNC_Programacion_Acum='$CNC_Programacion_Acum',
                                                               CNC_MdeO='$CNC_MdeO',
                                                               CNC_MdeO_Acum='$CNC_MdeO_Acum',
                                                               CNC_Materiales='$CNC_Materiales',
                                                               CNC_Materiales_Acum='$CNC_Materiales_Acum',
                                                               CNC_Equipos='$CNC_Equipos',
                                                               CNC_Equipos_Acum='$CNC_Equipos_Acum',
                                                               CNC_Disenos='$CNC_Disenos',
                                                               CNC_Disenos_Acum='$CNC_Disenos_Acum',
                                                               CNC_Administrativas='$CNC_Administrativas',
                                                               CNC_Administrativas_Acum='$CNC_Administrativas_Acum',
                                                               Criticas_Comp='$Criticas_Comp',
                                                               No_Criticas_Comp='$No_Criticas_Comp',
                                                               Atrasadas_Comp='$Atrasadas_Comp',
                                                               Comp_Sin_Rest_100='$Comp_Sin_Rest_100',
                                                               Act_Inician_Sem_1='$Act_Inician_Sem_1',
                                                               Act_0_Lib_Sem_1='$Act_0_Lib_Sem_1',
                                                               Act_Par_Lib_Sem_1='$Act_Par_Lib_Sem_1',
                                                               Act_100_Lib_Sem_1='$Act_100_Lib_Sem_1',
                                                               Act_Inician_Sem_2='$Act_Inician_Sem_2',
                                                               Act_0_Lib_Sem_2='$Act_0_Lib_Sem_2',
                                                               Act_Par_Lib_Sem_2='$Act_Par_Lib_Sem_2',
                                                               Act_100_Lib_Sem_2='$Act_100_Lib_Sem_2',
                                                               Act_Inician_Sem_3='$Act_Inician_Sem_3',
                                                               Act_0_Lib_Sem_3='$Act_0_Lib_Sem_3',
                                                               Act_Par_Lib_Sem_3='$Act_Par_Lib_Sem_3',
                                                               Act_100_Lib_Sem_3='$Act_100_Lib_Sem_3',
                                                               Act_Inician_Sem_4='$Act_Inician_Sem_4',
                                                               Act_0_Lib_Sem_4='$Act_0_Lib_Sem_4',
                                                               Act_Par_Lib_Sem_4='$Act_Par_Lib_Sem_4',
                                                               Act_100_Lib_Sem_4='$Act_100_Lib_Sem_4',
                                                               Act_Inician_Sem_5='$Act_Inician_Sem_5',
                                                               Act_0_Lib_Sem_5='$Act_0_Lib_Sem_5',
                                                               Act_Par_Lib_Sem_5='$Act_Par_Lib_Sem_5',
                                                               Act_100_Lib_Sem_5='$Act_100_Lib_Sem_5',
                                                               Act_Inician_Sem_6='$Act_Inician_Sem_6',
                                                               Act_0_Lib_Sem_6='$Act_0_Lib_Sem_6',
                                                               Act_Par_Lib_Sem_6='$Act_Par_Lib_Sem_6',
                                                               Act_100_Lib_Sem_6='$Act_100_Lib_Sem_6'

                                                               WHERE Semana=$semana AND subcontratista_profesional='consolidado general'";
            //echo $query1_1;
            $resultado1_1 = mysqli_multi_query($conexion, $query1_1); 
            
            $query1_2 = "SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(PAC) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general') ,3) END) AS PAC_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(P_Completado) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general') ,3) END) AS P_Completado_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Criticas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Criticas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Criticas_Comp!='NA') ,3) END) AS Criticas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND No_Criticas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(No_Criticas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND No_Criticas_Comp!='NA') ,3) END) AS No_Criticas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Atrasadas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Atrasadas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Atrasadas_Comp!='NA') ,3) END) AS Atrasadas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Comp_Sin_Rest_100!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Comp_Sin_Rest_100) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Comp_Sin_Rest_100!='NA') ,3) END) AS Comp_Sin_Rest_100_Acum;";
                
            $resultado1_2= mysqli_query($conexion, $query1_2);
            $data1_2=mysqli_fetch_assoc($resultado1_2);
            $PAC_Acum=$data1_2["PAC_Acum"];
            $P_Completado_Acum=$data1_2["P_Completado_Acum"];
            $Criticas_Comp_Acum=$data1_2["Criticas_Comp_Acum"];
            $No_Criticas_Comp_Acum=$data1_2["No_Criticas_Comp_Acum"];
            $Atrasadas_Comp_Acum=$data1_2["Atrasadas_Comp_Acum"];
            $Comp_Sin_Rest_100_Acum=$data1_2["Comp_Sin_Rest_100_Acum"];
            
            $query1_3 = "UPDATE $db"."_indicadores_generales SET 
                                                               
                                                               PAC_Acum='$PAC_Acum',

                                                               P_Completado_Acum='$P_Completado_Acum',

                                                               Criticas_Comp_Acum='$Criticas_Comp_Acum',

                                                               No_Criticas_Comp_Acum='$No_Criticas_Comp_Acum',

                                                               Atrasadas_Comp_Acum='$Atrasadas_Comp_Acum',

                                                               Comp_Sin_Rest_100_Acum='$Comp_Sin_Rest_100_Acum'

                                                               WHERE Semana=$semana AND subcontratista_profesional='consolidado general';";

            $resultado1_3 = mysqli_multi_query($conexion, $query1_3);
            
        }
    
    
        require ("../conexion.php");
        $query2="SELECT DISTINCT (Sub_Contratista) FROM $db"."_programacion_semanal WHERE (Semana=$semana AND (Activa=1 OR Activa='NA')) AND Sub_Contratista!='' AND Sub_Contratista IS NOT NULL AND PAC IS NOT NULL";
        $resultado2= mysqli_query($conexion, $query2);

        while($row=mysqli_fetch_assoc($resultado2)){
            require ("../conexion.php");
            $nombre=$row['Sub_Contratista'];
            $query3="SELECT  COUNT(*) FROM $db"."_indicadores_generales WHERE (Semana=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista')";
            $resultado3= mysqli_query($conexion, $query3);
            $data3=mysqli_fetch_assoc($resultado3);
            $conteo1=$data3["COUNT(*)"];
            mysqli_close($conexion);
            if ($conteo1==0){
                require ("../conexion.php");
                $query4= "SELECT '$nombre' AS 'subcontratista_profesional', 

                             'subcontratista' AS 'rol', 

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre')=0 THEN 'NA' ELSE ROUND((SELECT AVG(PAC) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre'),3) END) AS 'PAC',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre')=0 THEN 'NA' ELSE ROUND((SELECT AVG(P_Completado) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre'),3) END) AS 'P_Completado',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Rendimiento',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Rendimiento_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Programacion',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Programacion_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_MdeO',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_MdeO_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Materiales',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Materiales_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Equipos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Equipos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Disenos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Disenos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Administrativas',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Administrativas_Acum'

                             ;";
            
            //echo $query1;
            $resultado4= mysqli_query($conexion, $query4);
            $data4=mysqli_fetch_assoc($resultado4);
            $subcontratista_profesional=$data4["subcontratista_profesional"];
            $rol=$data4["rol"];
            $PAC=$data4["PAC"];
            $P_Completado=$data4["P_Completado"];
            $CNC_Rendimiento=$data4["CNC_Rendimiento"];
            $CNC_Rendimiento_Acum=$data4["CNC_Rendimiento_Acum"];
            $CNC_Programacion=$data4["CNC_Programacion"];
            $CNC_Programacion_Acum=$data4["CNC_Programacion_Acum"];
            $CNC_MdeO=$data4["CNC_MdeO"];
            $CNC_MdeO_Acum=$data4["CNC_MdeO_Acum"];
            $CNC_Materiales=$data4["CNC_Materiales"];
            $CNC_Materiales_Acum=$data4["CNC_Materiales_Acum"];
            $CNC_Equipos=$data4["CNC_Equipos"];
            $CNC_Equipos_Acum=$data4["CNC_Equipos_Acum"];
            $CNC_Disenos=$data4["CNC_Disenos"];
            $CNC_Disenos_Acum=$data4["CNC_Disenos_Acum"];
            $CNC_Administrativas=$data4["CNC_Administrativas"];
            $CNC_Administrativas_Acum=$data4["CNC_Administrativas_Acum"];
            $Criticas_Comp='NA';
            $No_Criticas_Comp='NA';
            $Atrasadas_Comp='NA';
            $Comp_Sin_Rest_100='NA';
            
            $query4_1="INSERT INTO $db"."_indicadores_generales (
                                                              Id, 
                                                              Semana, 
                                                              subcontratista_profesional, 
                                                              rol, 
                                                              PAC, 
                                                              P_Completado,
                                                              CNC_Rendimiento,
                                                              CNC_Rendimiento_Acum,
                                                              CNC_Programacion,
                                                              CNC_Programacion_Acum,
                                                              CNC_MdeO,
                                                              CNC_MdeO_Acum,
                                                              CNC_Materiales,
                                                              CNC_Materiales_Acum,
                                                              CNC_Equipos,
                                                              CNC_Equipos_Acum,
                                                              CNC_Disenos,
                                                              CNC_Disenos_Acum,
                                                              CNC_Administrativas,
                                                              CNC_Administrativas_Acum,
                                                              Criticas_Comp,
                                                              No_Criticas_Comp,
                                                              Atrasadas_Comp,
                                                              Comp_Sin_Rest_100
                                                              ) 

                                                               VALUES(
                                                                     NULL, 
                                                                     $semana,
                                                                     '$subcontratista_profesional', 
                                                                     '$rol', 
                                                                     '$PAC', 
                                                                     '$P_Completado',
                                                                     '$CNC_Rendimiento',
                                                                     '$CNC_Rendimiento_Acum',
                                                                     '$CNC_Programacion',
                                                                     '$CNC_Programacion_Acum',
                                                                     '$CNC_MdeO',
                                                                     '$CNC_MdeO_Acum',
                                                                     '$CNC_Materiales',
                                                                     '$CNC_Materiales_Acum',
                                                                     '$CNC_Equipos',
                                                                     '$CNC_Equipos_Acum',
                                                                     '$CNC_Disenos',
                                                                     '$CNC_Disenos_Acum',
                                                                     '$CNC_Administrativas',
                                                                     '$CNC_Administrativas_Acum',
                                                                     '$Criticas_Comp',
                                                                     '$No_Criticas_Comp',
                                                                     '$Atrasadas_Comp',
                                                                     '$Comp_Sin_Rest_100'
                                                                     );";
            //echo $query4_1;
            $resultado4_1= mysqli_multi_query($conexion, $query4_1);
            
            $query4_2 = "SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(PAC) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista') ,3) END) AS PAC_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(P_Completado) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista') ,3) END) AS P_Completado_Acum;
                                ";
                
            $resultado4_2= mysqli_query($conexion, $query4_2);
            $data4_2=mysqli_fetch_assoc($resultado4_2);
            $PAC_Acum=$data4_2["PAC_Acum"];
            $P_Completado_Acum=$data4_2["P_Completado_Acum"];
            $Criticas_Comp_Acum='NA';
            $No_Criticas_Comp_Acum='NA';
            $Atrasadas_Comp_Acum='NA';
            $Comp_Sin_Rest_100_Acum='NA';
            
            $query4_3 = "UPDATE $db"."_indicadores_generales SET 
                                                               
                                                               PAC_Acum='$PAC_Acum',

                                                               P_Completado_Acum='$P_Completado_Acum',

                                                               Criticas_Comp_Acum='$Criticas_Comp_Acum',

                                                               No_Criticas_Comp_Acum='$No_Criticas_Comp_Acum',

                                                               Atrasadas_Comp_Acum='$Atrasadas_Comp_Acum',

                                                               Comp_Sin_Rest_100_Acum='$Comp_Sin_Rest_100_Acum'

                                                               WHERE Semana=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista';";

            $resultado4_3 = mysqli_multi_query($conexion, $query4_3);

            } else{
                require ("../conexion.php");
                $query4= "SELECT '$nombre' AS 'subcontratista_profesional', 

                             'subcontratista' AS 'rol', 

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre')=0 THEN 'NA' ELSE ROUND((SELECT AVG(PAC) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre'),3) END) AS 'PAC',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre')=0 THEN 'NA' ELSE ROUND((SELECT AVG(P_Completado) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre'),3) END) AS 'P_Completado',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Rendimiento',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Rendimiento_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Programacion',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Programacion_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_MdeO',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_MdeO_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Materiales',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Materiales_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Equipos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Equipos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Disenos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Disenos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Administrativas',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Sub_Contratista='$nombre') AS 'CNC_Administrativas_Acum';
                              ";
            
            
            $resultado4= mysqli_query($conexion, $query4);
            $data4=mysqli_fetch_assoc($resultado4);
            $subcontratista_profesional=$data4["subcontratista_profesional"];
            $rol=$data4["rol"];
            $PAC=$data4["PAC"];
            $P_Completado=$data4["P_Completado"];
            $CNC_Rendimiento=$data4["CNC_Rendimiento"];
            $CNC_Rendimiento_Acum=$data4["CNC_Rendimiento_Acum"];
            $CNC_Programacion=$data4["CNC_Programacion"];
            $CNC_Programacion_Acum=$data4["CNC_Programacion_Acum"];
            $CNC_MdeO=$data4["CNC_MdeO"];
            $CNC_MdeO_Acum=$data4["CNC_MdeO_Acum"];
            $CNC_Materiales=$data4["CNC_Materiales"];
            $CNC_Materiales_Acum=$data4["CNC_Materiales_Acum"];
            $CNC_Equipos=$data4["CNC_Equipos"];
            $CNC_Equipos_Acum=$data4["CNC_Equipos_Acum"];
            $CNC_Disenos=$data4["CNC_Disenos"];
            $CNC_Disenos_Acum=$data4["CNC_Disenos_Acum"];
            $CNC_Administrativas=$data4["CNC_Administrativas"];
            $CNC_Administrativas_Acum=$data4["CNC_Administrativas_Acum"];
            $Criticas_Comp='NA';
            $No_Criticas_Comp='NA';
            $Atrasadas_Comp='NA';
            $Comp_Sin_Rest_100='NA';
            
            $query4_1 = "UPDATE $db"."_indicadores_generales SET 
                                                               PAC='$PAC',
                                                               P_Completado='$P_Completado',
                                                               CNC_Rendimiento='$CNC_Rendimiento',
                                                               CNC_Rendimiento_Acum='$CNC_Rendimiento_Acum',
                                                               CNC_Programacion='$CNC_Programacion',
                                                               CNC_Programacion_Acum='$CNC_Programacion_Acum',
                                                               CNC_MdeO='$CNC_MdeO',
                                                               CNC_MdeO_Acum='$CNC_MdeO_Acum',
                                                               CNC_Materiales='$CNC_Materiales',
                                                               CNC_Materiales_Acum='$CNC_Materiales_Acum',
                                                               CNC_Equipos='$CNC_Equipos',
                                                               CNC_Equipos_Acum='$CNC_Equipos_Acum',
                                                               CNC_Disenos='$CNC_Disenos',
                                                               CNC_Disenos_Acum='$CNC_Disenos_Acum',
                                                               CNC_Administrativas='$CNC_Administrativas',
                                                               CNC_Administrativas_Acum='$CNC_Administrativas_Acum',
                                                               Criticas_Comp='$Criticas_Comp',
                                                               No_Criticas_Comp='$No_Criticas_Comp',
                                                               Atrasadas_Comp='$Atrasadas_Comp',
                                                               Comp_Sin_Rest_100='$Comp_Sin_Rest_100'

                                                               WHERE Semana=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista'";
            //echo $query4_1;
            $resultado4_1 = mysqli_multi_query($conexion, $query4_1); 
            
            $query4_2 = "SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(PAC) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista') ,3) END) AS PAC_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(P_Completado) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista') ,3) END) AS P_Completado_Acum;";
                
            $resultado4_2= mysqli_query($conexion, $query4_2);
            $data4_2=mysqli_fetch_assoc($resultado4_2);
            $PAC_Acum=$data4_2["PAC_Acum"];
            $P_Completado_Acum=$data4_2["P_Completado_Acum"];
            $Criticas_Comp_Acum='NA';
            $No_Criticas_Comp_Acum='NA';
            $Atrasadas_Comp_Acum='NA';
            $Comp_Sin_Rest_100_Acum='NA';
            
            $query4_3 = "UPDATE $db"."_indicadores_generales SET 
                                                               
                                                               PAC_Acum='$PAC_Acum',

                                                               P_Completado_Acum='$P_Completado_Acum',

                                                               Criticas_Comp_Acum='$Criticas_Comp_Acum',

                                                               No_Criticas_Comp_Acum='$No_Criticas_Comp_Acum',

                                                               Atrasadas_Comp_Acum='$Atrasadas_Comp_Acum',

                                                               Comp_Sin_Rest_100_Acum='$Comp_Sin_Rest_100_Acum'

                                                               WHERE Semana=$semana AND subcontratista_profesional='$nombre' AND rol='subcontratista';";

            $resultado4_3 = mysqli_multi_query($conexion, $query4_3);
                
            }
        }
        $query5="SELECT DISTINCT (Responsable_AIA) FROM $db"."_programacion_semanal WHERE (Semana=$semana AND (Activa=1 OR Activa='NA')) AND Responsable_AIA!='' AND Responsable_AIA IS NOT NULL AND PAC IS NOT NULL";
        $resultado5= mysqli_query($conexion, $query5);
        while($row2=mysqli_fetch_assoc($resultado5)){
            require ("../conexion.php");
            $nombre=$row2['Responsable_AIA'];
            //echo $nombre;
            $query6="SELECT  COUNT(*) FROM $db"."_indicadores_generales WHERE (Semana=$semana AND subcontratista_profesional='$nombre' AND rol='profesional')";
            $resultado6= mysqli_query($conexion, $query6);
            $data6=mysqli_fetch_assoc($resultado6);
            $conteo2=$data6["COUNT(*)"];
            mysqli_close($conexion);
            if ($conteo2==0){
                require ("../conexion.php");
                $query7= "SELECT '$nombre' AS 'subcontratista_profesional', 

                             'profesional' AS 'rol', 

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND((SELECT AVG(PAC) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre'),3) END) AS 'PAC',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND((SELECT AVG(P_Completado) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre'),3) END) AS 'P_Completado',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Rendimiento',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Rendimiento_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Programacion',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Programacion_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_MdeO',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_MdeO_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Materiales',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Materiales_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Equipos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Equipos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Disenos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Disenos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Administrativas',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Administrativas_Acum',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Atrasada=0 AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Activa=1 AND Atrasada=0 AND Responsable_AIA='$nombre' AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Atrasada=0 AND Responsable_AIA='$nombre')) ,3) END) AS 'Criticas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND Atrasada=0 AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Atrasada=0 AND Responsable_AIA='$nombre' AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND Atrasada=0 AND Responsable_AIA='$nombre')) ,3) END) AS 'No_Criticas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Atrasada=1 AND Responsable_AIA='$nombre' AND PAC IS NOT NULL)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Atrasada=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Atrasada=1 AND Responsable_AIA='$nombre')) ,3) END) AS 'Atrasadas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA='$nombre' AND PAC IS NOT NULL)=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Prog_Sin_Restricciones_100=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Responsable_AIA='$nombre')) ,3) END) AS 'Comp_Sin_Rest_100'
                             
                             /*,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre') AS 'Act_Inician_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre') ,3) END) AS 'Act_0_Lib_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre') ,3) END) AS 'Act_Par_Lib_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre') ,3) END) AS 'Act_100_Lib_Sem_1',

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_2,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_3,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_4,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35) AS Act_Inician_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_5,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_6*/
                              ;";
            
            //echo $query7;
            $resultado7= mysqli_query($conexion, $query7);
            $data7=mysqli_fetch_assoc($resultado7);
            $subcontratista_profesional=$data7["subcontratista_profesional"];
            $rol=$data7["rol"];
            $PAC=$data7["PAC"];
            $P_Completado=$data7["P_Completado"];
            $CNC_Rendimiento=$data7["CNC_Rendimiento"];
            $CNC_Rendimiento_Acum=$data7["CNC_Rendimiento_Acum"];
            $CNC_Programacion=$data7["CNC_Programacion"];
            $CNC_Programacion_Acum=$data7["CNC_Programacion_Acum"];
            $CNC_MdeO=$data7["CNC_MdeO"];
            $CNC_MdeO_Acum=$data7["CNC_MdeO_Acum"];
            $CNC_Materiales=$data7["CNC_Materiales"];
            $CNC_Materiales_Acum=$data7["CNC_Materiales_Acum"];
            $CNC_Equipos=$data7["CNC_Equipos"];
            $CNC_Equipos_Acum=$data7["CNC_Equipos_Acum"];
            $CNC_Disenos=$data7["CNC_Disenos"];
            $CNC_Disenos_Acum=$data7["CNC_Disenos_Acum"];
            $CNC_Administrativas=$data7["CNC_Administrativas"];
            $CNC_Administrativas_Acum=$data7["CNC_Administrativas_Acum"];
            $Criticas_Comp=$data7["Criticas_Comp"];
            $No_Criticas_Comp=$data7["No_Criticas_Comp"];
            $Atrasadas_Comp=$data7["Atrasadas_Comp"];
            $Comp_Sin_Rest_100=$data7["Comp_Sin_Rest_100"];
            /*$Act_Inician_Sem_1=$data7["Act_Inician_Sem_1"];
            $Act_0_Lib_Sem_1=$data7["Act_0_Lib_Sem_1"];
            $Act_Par_Lib_Sem_1=$data7["Act_Par_Lib_Sem_1"];
            $Act_100_Lib_Sem_1=$data7["Act_100_Lib_Sem_1"];
            $Act_Inician_Sem_2=$data7["Act_Inician_Sem_2"];
            $Act_0_Lib_Sem_2=$data7["Act_0_Lib_Sem_2"];
            $Act_Par_Lib_Sem_2=$data7["Act_Par_Lib_Sem_2"];
            $Act_100_Lib_Sem_2=$data7["Act_100_Lib_Sem_2"];
            $Act_Inician_Sem_3=$data7["Act_Inician_Sem_3"];
            $Act_0_Lib_Sem_3=$data7["Act_0_Lib_Sem_3"];
            $Act_Par_Lib_Sem_3=$data7["Act_Par_Lib_Sem_3"];
            $Act_100_Lib_Sem_3=$data7["Act_100_Lib_Sem_3"];
            $Act_Inician_Sem_4=$data7["Act_Inician_Sem_4"];
            $Act_0_Lib_Sem_4=$data7["Act_0_Lib_Sem_4"];
            $Act_Par_Lib_Sem_4=$data7["Act_Par_Lib_Sem_4"];
            $Act_100_Lib_Sem_4=$data7["Act_100_Lib_Sem_4"];
            $Act_Inician_Sem_5=$data7["Act_Inician_Sem_5"];
            $Act_0_Lib_Sem_5=$data7["Act_0_Lib_Sem_5"];
            $Act_Par_Lib_Sem_5=$data7["Act_Par_Lib_Sem_5"];
            $Act_100_Lib_Sem_5=$data7["Act_100_Lib_Sem_5"];
            $Act_Inician_Sem_6=$data7["Act_Inician_Sem_6"];
            $Act_0_Lib_Sem_6=$data7["Act_0_Lib_Sem_6"];
            $Act_Par_Lib_Sem_6=$data7["Act_Par_Lib_Sem_6"];
            $Act_100_Lib_Sem_6=$data7["Act_100_Lib_Sem_6"];*/
            
            $query7_1="INSERT INTO $db"."_indicadores_generales (
                                                              Id, 
                                                              Semana, 
                                                              subcontratista_profesional, 
                                                              rol, 
                                                              PAC, 
                                                              P_Completado,
                                                              CNC_Rendimiento,
                                                              CNC_Rendimiento_Acum,
                                                              CNC_Programacion,
                                                              CNC_Programacion_Acum,
                                                              CNC_MdeO,
                                                              CNC_MdeO_Acum,
                                                              CNC_Materiales,
                                                              CNC_Materiales_Acum,
                                                              CNC_Equipos,
                                                              CNC_Equipos_Acum,
                                                              CNC_Disenos,
                                                              CNC_Disenos_Acum,
                                                              CNC_Administrativas,
                                                              CNC_Administrativas_Acum,
                                                              Criticas_Comp,
                                                              No_Criticas_Comp,
                                                              Atrasadas_Comp,
                                                              Comp_Sin_Rest_100
                                                              /*,
                                                              Act_Inician_Sem_1,
                                                              Act_0_Lib_Sem_1,
                                                              Act_Par_Lib_Sem_1,
                                                              Act_100_Lib_Sem_1,
                                                              Act_Inician_Sem_2,
                                                              Act_0_Lib_Sem_2,
                                                              Act_Par_Lib_Sem_2,
                                                              Act_100_Lib_Sem_2,
                                                              Act_Inician_Sem_3,
                                                              Act_0_Lib_Sem_3,
                                                              Act_Par_Lib_Sem_3,
                                                              Act_100_Lib_Sem_3,
                                                              Act_Inician_Sem_4,
                                                              Act_0_Lib_Sem_4,
                                                              Act_Par_Lib_Sem_4,
                                                              Act_100_Lib_Sem_4,
                                                              Act_Inician_Sem_5,
                                                              Act_0_Lib_Sem_5,
                                                              Act_Par_Lib_Sem_5,
                                                              Act_100_Lib_Sem_5,
                                                              Act_Inician_Sem_6,
                                                              Act_0_Lib_Sem_6,
                                                              Act_Par_Lib_Sem_6,
                                                              Act_100_Lib_Sem_6*/
                                                              ) 

                                                               VALUES(
                                                                     NULL, 
                                                                     $semana,
                                                                     '$subcontratista_profesional', 
                                                                     '$rol', 
                                                                     '$PAC', 
                                                                     '$P_Completado',
                                                                     '$CNC_Rendimiento',
                                                                     '$CNC_Rendimiento_Acum',
                                                                     '$CNC_Programacion',
                                                                     '$CNC_Programacion_Acum',
                                                                     '$CNC_MdeO',
                                                                     '$CNC_MdeO_Acum',
                                                                     '$CNC_Materiales',
                                                                     '$CNC_Materiales_Acum',
                                                                     '$CNC_Equipos',
                                                                     '$CNC_Equipos_Acum',
                                                                     '$CNC_Disenos',
                                                                     '$CNC_Disenos_Acum',
                                                                     '$CNC_Administrativas',
                                                                     '$CNC_Administrativas_Acum',
                                                                     '$Criticas_Comp',
                                                                     '$No_Criticas_Comp',
                                                                     '$Atrasadas_Comp',
                                                                     '$Comp_Sin_Rest_100'
                                                                     /*,
                                                                     '$Act_Inician_Sem_1',
                                                                     '$Act_0_Lib_Sem_1',
                                                                     '$Act_Par_Lib_Sem_1',
                                                                     '$Act_100_Lib_Sem_1',
                                                                     '$Act_Inician_Sem_2',
                                                                     '$Act_0_Lib_Sem_2',
                                                                     '$Act_Par_Lib_Sem_2',
                                                                     '$Act_100_Lib_Sem_2',
                                                                     '$Act_Inician_Sem_3',
                                                                     '$Act_0_Lib_Sem_3',
                                                                     '$Act_Par_Lib_Sem_3',
                                                                     '$Act_100_Lib_Sem_3',
                                                                     '$Act_Inician_Sem_4',
                                                                     '$Act_0_Lib_Sem_4',
                                                                     '$Act_Par_Lib_Sem_4',
                                                                     '$Act_100_Lib_Sem_4',
                                                                     '$Act_Inician_Sem_5',
                                                                     '$Act_0_Lib_Sem_5',
                                                                     '$Act_Par_Lib_Sem_5',
                                                                     '$Act_100_Lib_Sem_5',
                                                                     '$Act_Inician_Sem_6',
                                                                     '$Act_0_Lib_Sem_6',
                                                                     '$Act_Par_Lib_Sem_6',
                                                                     '$Act_100_Lib_Sem_6'*/
                                                                     );";
            //echo $query7_1;
            $resultado7= mysqli_multi_query($conexion, $query7_1);
            
            
            $query7_2 = "SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(PAC) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional') ,3) END) AS PAC_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(P_Completado) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional') ,3) END) AS P_Completado_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Criticas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Criticas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Criticas_Comp!='NA') ,3) END) AS Criticas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND No_Criticas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(No_Criticas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND No_Criticas_Comp!='NA') ,3) END) AS No_Criticas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='consolidado general' AND Atrasadas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Atrasadas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Atrasadas_Comp!='NA') ,3) END) AS Atrasadas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Comp_Sin_Rest_100!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Comp_Sin_Rest_100) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Comp_Sin_Rest_100!='NA') ,3) END) AS Comp_Sin_Rest_100_Acum;";
                
            $resultado7_2= mysqli_query($conexion, $query7_2);
            $data7_2=mysqli_fetch_assoc($resultado7_2);
            $PAC_Acum=$data7_2["PAC_Acum"];
            $P_Completado_Acum=$data7_2["P_Completado_Acum"];
            $Criticas_Comp_Acum=$data7_2["Criticas_Comp_Acum"];
            $No_Criticas_Comp_Acum=$data7_2["No_Criticas_Comp_Acum"];
            $Atrasadas_Comp_Acum=$data7_2["Atrasadas_Comp_Acum"];
            $Comp_Sin_Rest_100_Acum=$data7_2["Comp_Sin_Rest_100_Acum"];
            
            $query7_3 = "UPDATE $db"."_indicadores_generales SET 
                                                               
                                                               PAC_Acum='$PAC_Acum',

                                                               P_Completado_Acum='$P_Completado_Acum',

                                                               Criticas_Comp_Acum='$Criticas_Comp_Acum',

                                                               No_Criticas_Comp_Acum='$No_Criticas_Comp_Acum',

                                                               Atrasadas_Comp_Acum='$Atrasadas_Comp_Acum',

                                                               Comp_Sin_Rest_100_Acum='$Comp_Sin_Rest_100_Acum'

                                                               WHERE Semana=$semana AND subcontratista_profesional='$nombre' AND rol='profesional';";

            $resultado7_3 = mysqli_multi_query($conexion, $query7_3); 
                
                
            } else{
                require ("../conexion.php");
                $query7= "SELECT '$nombre' AS 'subcontratista_profesional', 

                             'profesional' AS 'rol', 

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND((SELECT AVG(PAC) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre'),3) END) AS 'PAC',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND((SELECT AVG(P_Completado) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre'),3) END) AS 'P_Completado',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Rendimiento',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Rendimiento' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Rendimiento_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Programacion',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Programacion' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Programacion_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_MdeO',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Mano de Obra' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_MdeO_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Materiales',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Materiales' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Materiales_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Equipos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Equipos' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Equipos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Disenos',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Disenos' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Disenos_Acum',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Administrativas',

                             (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Categoria_CNC='Administrativas' AND Semana<=$semana AND (Activa=1 OR Activa='NA') AND Responsable_AIA='$nombre') AS 'CNC_Administrativas_Acum'
                              ;
                              ";
                              
            $query7_0= "SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Atrasada=0 AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Activa=1 AND Atrasada=0 AND Responsable_AIA='$nombre' AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=1 AND Semana=$semana AND Atrasada=0 AND Responsable_AIA='$nombre')) ,3) END) AS 'Criticas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND Atrasada=0 AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND (Activa=1 OR Activa='NA') AND Atrasada=0 AND Responsable_AIA='$nombre' AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Critica=0 AND Semana=$semana AND Atrasada=0 AND Responsable_AIA='$nombre')) ,3) END) AS 'No_Criticas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Atrasada=1 AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Atrasada=1 AND Responsable_AIA='$nombre' AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Atrasada=1 AND Responsable_AIA='$nombre')) ,3) END) AS 'Atrasadas_Comp',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA='$nombre')=0 THEN 'NA' ELSE ROUND( 
                             ((SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Prog_Sin_Restricciones_100=1 AND Responsable_AIA='$nombre' AND PAC IS NOT NULL) / (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Responsable_AIA='$nombre')) ,3) END) AS 'Comp_Sin_Rest_100'
                             
                             /*,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre') AS 'Act_Inician_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre') ,3) END) AS 'Act_0_Lib_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre') ,3) END) AS 'Act_Par_Lib_Sem_1',

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=7 AND Responsable_AIA='$nombre') ,3) END) AS 'Act_100_Lib_Sem_1',

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_2,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=14 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_2,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_3,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=141 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_3,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_4,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=28 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_4,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_5,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=35 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_5,

                             (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre') AS Act_Inician_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones=0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre') ,3) END) AS Act_0_Lib_Sem_6,

                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones<1 AND Estado_Restricciones>0 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre') ,3) END) AS Act_Par_Lib_Sem_6,
                             
                             (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre')=0 THEN 0 ELSE ROUND( (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Estado_Restricciones=1 AND Responsable_AIA='$nombre') / (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Dias_Inicio=42 AND Responsable_AIA='$nombre') ,3) END) AS Act_100_Lib_Sem_6*/
                             ;";
            
            //echo $query7;
            $resultado7= mysqli_query($conexion, $query7);
            $data7=mysqli_fetch_assoc($resultado7);
            $subcontratista_profesional=$data7["subcontratista_profesional"];
            $rol=$data7["rol"];
            $PAC=$data7["PAC"];
            $P_Completado=$data7["P_Completado"];
            $CNC_Rendimiento=$data7["CNC_Rendimiento"];
            $CNC_Rendimiento_Acum=$data7["CNC_Rendimiento_Acum"];
            $CNC_Programacion=$data7["CNC_Programacion"];
            $CNC_Programacion_Acum=$data7["CNC_Programacion_Acum"];
            $CNC_MdeO=$data7["CNC_MdeO"];
            $CNC_MdeO_Acum=$data7["CNC_MdeO_Acum"];
            $CNC_Materiales=$data7["CNC_Materiales"];
            $CNC_Materiales_Acum=$data7["CNC_Materiales_Acum"];
            $CNC_Equipos=$data7["CNC_Equipos"];
            $CNC_Equipos_Acum=$data7["CNC_Equipos_Acum"];
            $CNC_Disenos=$data7["CNC_Disenos"];
            $CNC_Disenos_Acum=$data7["CNC_Disenos_Acum"];
            $CNC_Administrativas=$data7["CNC_Administrativas"];
            $CNC_Administrativas_Acum=$data7["CNC_Administrativas_Acum"];
            
            //echo $rol, $PAC;
            $resultado7_0= mysqli_query($conexion, $query7_0);
            $data7_0=mysqli_fetch_assoc($resultado7_0);
            $Criticas_Comp=$data7_0["Criticas_Comp"];
            $No_Criticas_Comp=$data7_0["No_Criticas_Comp"];
            $Atrasadas_Comp=$data7_0["Atrasadas_Comp"];
            $Comp_Sin_Rest_100=$data7_0["Comp_Sin_Rest_100"];
            /*$Act_Inician_Sem_1=$data7_0["Act_Inician_Sem_1"];
            $Act_0_Lib_Sem_1=$data7_0["Act_0_Lib_Sem_1"];
            $Act_Par_Lib_Sem_1=$data7_0["Act_Par_Lib_Sem_1"];
            $Act_100_Lib_Sem_1=$data7_0["Act_100_Lib_Sem_1"];
            $Act_Inician_Sem_2=$data7_0["Act_Inician_Sem_2"];
            $Act_0_Lib_Sem_2=$data7_0["Act_0_Lib_Sem_2"];
            $Act_Par_Lib_Sem_2=$data7_0["Act_Par_Lib_Sem_2"];
            $Act_100_Lib_Sem_2=$data7_0["Act_100_Lib_Sem_2"];
            $Act_Inician_Sem_3=$data7_0["Act_Inician_Sem_3"];
            $Act_0_Lib_Sem_3=$data7_0["Act_0_Lib_Sem_3"];
            $Act_Par_Lib_Sem_3=$data7_0["Act_Par_Lib_Sem_3"];
            $Act_100_Lib_Sem_3=$data7_0["Act_100_Lib_Sem_3"];
            $Act_Inician_Sem_4=$data7_0["Act_Inician_Sem_4"];
            $Act_0_Lib_Sem_4=$data7_0["Act_0_Lib_Sem_4"];
            $Act_Par_Lib_Sem_4=$data7_0["Act_Par_Lib_Sem_4"];
            $Act_100_Lib_Sem_4=$data7_0["Act_100_Lib_Sem_4"];
            $Act_Inician_Sem_5=$data7_0["Act_Inician_Sem_5"];
            $Act_0_Lib_Sem_5=$data7_0["Act_0_Lib_Sem_5"];
            $Act_Par_Lib_Sem_5=$data7_0["Act_Par_Lib_Sem_5"];
            $Act_100_Lib_Sem_5=$data7_0["Act_100_Lib_Sem_5"];
            $Act_Inician_Sem_6=$data7_0["Act_Inician_Sem_6"];
            $Act_0_Lib_Sem_6=$data7_0["Act_0_Lib_Sem_6"];
            $Act_Par_Lib_Sem_6=$data7_0["Act_Par_Lib_Sem_6"];
            $Act_100_Lib_Sem_6=$data7_0["Act_100_Lib_Sem_6"];*/
            
            
            $query7_1 = "UPDATE $db"."_indicadores_generales SET 
                                                               PAC='$PAC',
                                                               P_Completado='$P_Completado',
                                                               CNC_Rendimiento='$CNC_Rendimiento',
                                                               CNC_Rendimiento_Acum='$CNC_Rendimiento_Acum',
                                                               CNC_Programacion='$CNC_Programacion',
                                                               CNC_Programacion_Acum='$CNC_Programacion_Acum',
                                                               CNC_MdeO='$CNC_MdeO',
                                                               CNC_MdeO_Acum='$CNC_MdeO_Acum',
                                                               CNC_Materiales='$CNC_Materiales',
                                                               CNC_Materiales_Acum='$CNC_Materiales_Acum',
                                                               CNC_Equipos='$CNC_Equipos',
                                                               CNC_Equipos_Acum='$CNC_Equipos_Acum',
                                                               CNC_Disenos='$CNC_Disenos',
                                                               CNC_Disenos_Acum='$CNC_Disenos_Acum',
                                                               CNC_Administrativas='$CNC_Administrativas',
                                                               CNC_Administrativas_Acum='$CNC_Administrativas_Acum',
                                                               Criticas_Comp='$Criticas_Comp',
                                                               No_Criticas_Comp='$No_Criticas_Comp',
                                                               Atrasadas_Comp='$Atrasadas_Comp',
                                                               Comp_Sin_Rest_100='$Comp_Sin_Rest_100'
                                                               /*,
                                                               Act_Inician_Sem_1='$Act_Inician_Sem_1',
                                                               Act_0_Lib_Sem_1='$Act_0_Lib_Sem_1',
                                                               Act_Par_Lib_Sem_1='$Act_Par_Lib_Sem_1',
                                                               Act_100_Lib_Sem_1='$Act_100_Lib_Sem_1',
                                                               Act_Inician_Sem_2='$Act_Inician_Sem_2',
                                                               Act_0_Lib_Sem_2='$Act_0_Lib_Sem_2',
                                                               Act_Par_Lib_Sem_2='$Act_Par_Lib_Sem_2',
                                                               Act_100_Lib_Sem_2='$Act_100_Lib_Sem_2',
                                                               Act_Inician_Sem_3='$Act_Inician_Sem_3',
                                                               Act_0_Lib_Sem_3='$Act_0_Lib_Sem_3',
                                                               Act_Par_Lib_Sem_3='$Act_Par_Lib_Sem_3',
                                                               Act_100_Lib_Sem_3='$Act_100_Lib_Sem_3',
                                                               Act_Inician_Sem_4='$Act_Inician_Sem_4',
                                                               Act_0_Lib_Sem_4='$Act_0_Lib_Sem_4',
                                                               Act_Par_Lib_Sem_4='$Act_Par_Lib_Sem_4',
                                                               Act_100_Lib_Sem_4='$Act_100_Lib_Sem_4',
                                                               Act_Inician_Sem_5='$Act_Inician_Sem_5',
                                                               Act_0_Lib_Sem_5='$Act_0_Lib_Sem_5',
                                                               Act_Par_Lib_Sem_5='$Act_Par_Lib_Sem_5',
                                                               Act_100_Lib_Sem_5='$Act_100_Lib_Sem_5',
                                                               Act_Inician_Sem_6='$Act_Inician_Sem_6',
                                                               Act_0_Lib_Sem_6='$Act_0_Lib_Sem_6',
                                                               Act_Par_Lib_Sem_6='$Act_Par_Lib_Sem_6',
                                                               Act_100_Lib_Sem_6='$Act_100_Lib_Sem_6'*/

                                                               WHERE Semana=$semana AND subcontratista_profesional='$nombre' AND rol='profesional';";
            //echo $query7_1;
            $resultado7_1 = mysqli_multi_query($conexion, $query7_1); 
            
            $query7_2 = "SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(PAC) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional') ,3) END) AS PAC_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(P_Completado) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional') ,3) END) AS P_Completado_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Criticas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Criticas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Criticas_Comp!='NA') ,3) END) AS Criticas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND No_Criticas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(No_Criticas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND No_Criticas_Comp!='NA') ,3) END) AS No_Criticas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Atrasadas_Comp!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Atrasadas_Comp) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Atrasadas_Comp!='NA') ,3) END) AS Atrasadas_Comp_Acum,

                                (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Comp_Sin_Rest_100!='NA')=0 THEN 'NA' ELSE ROUND( (SELECT AVG(Comp_Sin_Rest_100) FROM $db"."_indicadores_generales WHERE Semana<=$semana AND subcontratista_profesional='$nombre' AND rol='profesional' AND Comp_Sin_Rest_100!='NA') ,3) END) AS Comp_Sin_Rest_100_Acum;";
                
            $resultado7_2= mysqli_query($conexion, $query7_2);
            $data7_2=mysqli_fetch_assoc($resultado7_2);
            $PAC_Acum=$data7_2["PAC_Acum"];
            $P_Completado_Acum=$data7_2["P_Completado_Acum"];
            $Criticas_Comp_Acum=$data7_2["Criticas_Comp_Acum"];
            $No_Criticas_Comp_Acum=$data7_2["No_Criticas_Comp_Acum"];
            $Atrasadas_Comp_Acum=$data7_2["Atrasadas_Comp_Acum"];
            $Comp_Sin_Rest_100_Acum=$data7_2["Comp_Sin_Rest_100_Acum"];
            
            $query7_3 = "UPDATE $db"."_indicadores_generales SET 
                                                               
                                                               PAC_Acum='$PAC_Acum',

                                                               P_Completado_Acum='$P_Completado_Acum',

                                                               Criticas_Comp_Acum='$Criticas_Comp_Acum',

                                                               No_Criticas_Comp_Acum='$No_Criticas_Comp_Acum',

                                                               Atrasadas_Comp_Acum='$Atrasadas_Comp_Acum',

                                                               Comp_Sin_Rest_100_Acum='$Comp_Sin_Rest_100_Acum'

                                                               WHERE Semana=$semana AND subcontratista_profesional='$nombre' AND rol='profesional';";

            $resultado7_3 = mysqli_multi_query($conexion, $query7_3);
            }
        }

        //mysqli_free_result($resultado);
        $semana=$semana+1;
        
    }
    
    
};
    
?>
    

