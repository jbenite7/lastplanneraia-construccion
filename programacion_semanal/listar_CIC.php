<?php

	require ("../conexion.php");
    $semana=$_GET['semana'];
    $db=$_GET['db'];

		// $semana=19;
    // $db="laMasia";

		for($semanaCiclo = 1; $semanaCiclo < ($semana+1); $semanaCiclo++){
			$query="SELECT  COUNT(*) FROM $db"."_cic WHERE (Semana=$semanaCiclo)";
	    $resultado= mysqli_query($conexion, $query);
	    $data=mysqli_fetch_assoc($resultado);
	    $conteo=$data["COUNT(*)"];
	    if($conteo>0){
	        actualizar_PAC_subcontratistas($semanaCiclo, $db, $conexion, $semanaCiclo);
	    }

	    //require ("../conexion.php");
	    $query1="SELECT * FROM $db"."_cic WHERE (Semana=$semanaCiclo)";
	    $resultado1= mysqli_query($conexion, $query1);
	    $script_subcontratistas="";
	    while ($data1 = mysqli_fetch_assoc($resultado1)){
	        $subcontratista=$data1["subcontratista"];
	        $script_subcontratistas .="AND Sub_Contratista != '$subcontratista' ";
	        //echo $script_subcontratistas;
	    }

	    generar_subcontratistas($semanaCiclo, $db, $conexion, $conteo, $script_subcontratistas);

		}
		listar($db, $semana, $conexion);



    function generar_subcontratistas($semanaCiclo, $db, $conexion, $conteo, $script_subcontratistas){
        //require ("../conexion.php");
        $query2="SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semanaCiclo $script_subcontratistas  AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA') ";
				// AND (PAC='1' OR PAC='0')
        //echo $query2;
        $resultado2= mysqli_query($conexion, $query2);
        while($data2=mysqli_fetch_assoc($resultado2)){
            $subcontratista=$data2["Sub_Contratista"];
            $query3="INSERT INTO $db"."_cic (Semana, subcontratista) VALUES (0, '$subcontratista');";
            //echo $query3;
            $resultado3= mysqli_query($conexion, $query3);
        }


        actualizar_PAC_subcontratistas($semanaCiclo, $db, $conexion, $semanaCiclo1=0);
        actualizar_integral_subcontratistas($semanaCiclo, $db, $conexion);

    }

    function actualizar_PAC_subcontratistas($semanaCiclo, $db, $conexion, $semanaCiclo1){
        $query3 ="SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semanaCiclo AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA')";
            $resultado3= mysqli_query($conexion, $query3);
            //$conteo1=mysqli_num_rows($resultado3);
            //echo $conteo1;
            $script ="";
            while($data1=mysqli_fetch_assoc($resultado3)){
                $subcontratista = $data1['Sub_Contratista'];
                $query4="SELECT (SELECT ROUND((SUM(P_Completado)/COUNT(P_Completado)),3) FROM $db"."_programacion_semanal WHERE Semana=$semanaCiclo AND Sub_Contratista ='$subcontratista' AND (Activa=1 OR Activa='NA')) AS 'P_Completado', (SELECT ROUND((SUM(PAC)/COUNT(PAC)),3) FROM $db"."_programacion_semanal WHERE Semana=$semanaCiclo AND Sub_Contratista ='$subcontratista' AND (Activa=1 OR Activa='NA')) AS 'PAC'";
                //echo $query4;
                $resultado4= mysqli_query($conexion, $query4);
                $data2=mysqli_fetch_assoc($resultado4);
                $PAC=$data2["PAC"];
                $P_Completado=$data2["P_Completado"];
                //echo $PAC, $P_Completado;
								if($subcontratista == "AIA (MO Directa)"){
									$query5 ="UPDATE $db"."_cic SET
	                    P_Completado = '$P_Completado',

	                    PAC = '$PAC',

	                    Semana = $semanaCiclo, correo_contacto = null, NIT = null, alcance = null, tipo_proveedor = 'Mano de Obra' WHERE subcontratista = '$subcontratista'  AND Semana=$semanaCiclo1;";
								}else{
									$query5 ="UPDATE $db"."_cic INNER JOIN $db"."_subcontratistas ON $db"."_cic . subcontratista = $db"."_subcontratistas . subcontratista SET
	                    $db"."_cic . P_Completado = '$P_Completado',

	                    $db"."_cic . PAC = '$PAC',

	                    $db"."_cic . Semana = $semanaCiclo, $db"."_cic . correo_contacto = $db"."_subcontratistas . correo_contacto, $db"."_cic . NIT = $db"."_subcontratistas . NIT, $db"."_cic . alcance = $db"."_subcontratistas . alcance, $db"."_cic . tipo_proveedor = $db"."_subcontratistas . tipo_proveedor WHERE $db"."_cic . subcontratista = '$subcontratista'  AND Semana=$semanaCiclo1;";
								}



                $resultado5= mysqli_query($conexion, $query5);


                //echo $query5 ."<br>" /*. $query4 ."<br>"*/;

                $script .="AND subcontratista != '$subcontratista' ";
            }

            $query6="DELETE FROM $db"."_cic WHERE Semana=$semanaCiclo $script";
            //echo $query6 ."<br>";
            $resultado6= mysqli_query($conexion, $query6);

            mysqli_free_result($resultado3);

            //mysqli_close($conexion);


    }

    function actualizar_integral_subcontratistas($semanaCiclo, $db, $conexion){
        //require("../conexion.php");
        $query5 ="SELECT * FROM $db"."_cic WHERE Semana=$semanaCiclo;";
        $resultado5= mysqli_query($conexion, $query5);

        while ($cic = mysqli_fetch_assoc($resultado5)){
          $Id=$cic['Id'];
          $subcontratista=$cic['subcontratista'];

          $query6 ="SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND PAC!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(PAC),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND PAC!='NA') END) AS 'PAC_Acum',";

					$query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND P_Completado!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(P_Completado),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND P_Completado!='NA') END) AS 'P_Completado_Acum',";

	        $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND Calidad!='NA' AND Calidad!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Calidad),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND Calidad!='NA' AND Calidad!='NR') END) AS 'Calidad_Acum',";

	        $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND GSA!='NA' AND GSA!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(GSA),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND GSA!='NA' AND GSA!='NR') END) AS 'GSA_Acum',";

	        $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND SST!='NA' AND SST!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(SST),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND SST!='NA' AND SST!='NR') END) AS 'SST_Acum',";

	        $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND ADM!='NA' AND ADM!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(ADM),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND ADM!='NA' AND ADM!='NR') END) AS 'ADM_Acum'";

          $resultado6= mysqli_query($conexion, $query6);

          $data=mysqli_fetch_assoc($resultado6);
          $PAC_Acum=$data["PAC_Acum"];
          $P_Completado_Acum=$data["P_Completado_Acum"];
          $Calidad_Acum=$data["Calidad_Acum"];
          $GSA_Acum=$data["GSA_Acum"];
          $SST_Acum=$data["SST_Acum"];
          $ADM_Acum=$data["ADM_Acum"];



          $query6_1 = "UPDATE $db"."_cic SET
              PAC_Acum = '$PAC_Acum',

              P_Completado_Acum = '$P_Completado_Acum',

              Calidad_Acum = '$Calidad_Acum',

              GSA_Acum = '$GSA_Acum',

              SST_Acum = '$SST_Acum',

              ADM_Acum = '$ADM_Acum'

              WHERE Id=$Id";

          $resultado6_1= mysqli_query($conexion, $query6_1);


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

					if($PAC == ""){
						$cal_integral = "NULL";
					}else{
						if($calidad=='NA' || $calidad=='NR'){
								if($sst=='NA' || $sst=='NR'){
										if($gsa=='NA' || $gsa=='NR'){
												if($adm=='NA' || $adm=='NR'){
														$cal_integral=$PAC*(0.3+(0.7/7)*7);
												}else{
														$cal_integral=$PAC*(0.3+(0.6/4)*3)+$adm*(0.1+(0.6/4)*1);
												}
										}else{
												if($adm=='NA' || $adm=='NR'){
														$cal_integral=$PAC*(0.3+(0.5/5)*3)+$gsa*(0.2+(0.5/5)*2);
												}else{
														$cal_integral=$PAC*(0.3+(0.4/6)*3)+$gsa*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
												}
										}
								}else{
										if($gsa=='NA' || $gsa=='NR'){
												if($adm=='NA' || $adm=='NR'){
														$cal_integral=$PAC*(0.3+(0.5/5)*3)+$sst*(0.2+(0.5/5)*2);
												}else{
														$cal_integral=$PAC*(0.3+(0.4/6)*3)+$sst*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
												}
										}else{
												if($adm=='NA' || $adm=='NR'){
														$cal_integral=$PAC*(0.3+(0.3/7)*3)+$sst*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
												}else{
														$cal_integral=$PAC*(0.3+(0.2/8)*3)+$sst*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
												}
										}
								}
						}else{
								if($sst=='NA' || $sst=='NR'){
										if($gsa=='NA' || $gsa=='NR'){
												if($adm=='NA' || $adm=='NR'){
														$cal_integral=$PAC*(0.3+(0.5/5)*3)+$calidad*(0.2+(0.5/5)*2);
												}else{
														$cal_integral=$PAC*(0.3+(0.4/6)*3)+$calidad*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
												}
										}else{
												if($adm=='NA' || $adm=='NR'){
														$cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
												}else{
														$cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
												}
										}
								}else{
										if($gsa=='NA' || $gsa=='NR'){
												if($adm=='NA' || $adm=='NR'){
														$cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$sst*(0.2+(0.3/7)*2);
												}else{
														$cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$sst*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
												}
										}else{
												if($adm=='NA' || $adm=='NR'){
														$cal_integral=$PAC*(0.3+(0.1/9)*3)+$calidad*(0.2+(0.1/9)*2)+$sst*(0.2+(0.1/9)*2)+$gsa*(0.2+(0.1/9)*2);
												}else{
														$cal_integral=$PAC*(0.3+(0.0/10)*3)+$calidad*(0.2+(0.0/10)*2)+$sst*(0.2+(0.0/10)*2)+$gsa*(0.2+(0.0/10)*2)+$adm*(0.1+(0.0/10)*1);
												}
										}
								}
						}
					}


					if($PAC_Acum == ""){
						$cal_integral_acum = "NULL";
					}else{
						if($calidad_acum=='NA' || $calidad_acum=='NR'){
                if($sst_acum=='NA' || $sst_acum=='NR'){
                    if($gsa_acum=='NA' || $gsa_acum=='NR'){
                        if($adm_acum=='NA' || $adm_acum=='NR'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.7/7)*7);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.6/4)*3)+$adm_acum*(0.1+(0.6/4)*1);
                        }
                    }else{
                        if($adm_acum=='NA' || $adm_acum=='NR'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$gsa_acum*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$gsa_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                        }
                    }
                }else{
                    if($gsa_acum=='NA' || $gsa_acum=='NR'){
                        if($adm_acum=='NA' || $adm_acum=='NR'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$sst_acum*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$sst_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                        }
                    }else{
                        if($adm_acum=='NA' || $adm_acum=='NR'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$sst_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$sst_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                        }
                    }
                }
            }else{
                if($sst_acum=='NA' || $sst_acum=='NR'){
                    if($gsa_acum=='NA' || $gsa_acum=='NR'){
                        if($adm_acum=='NA' || $adm_acum=='NR'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$calidad_acum*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$calidad_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                        }
                    }else{
                        if($adm_acum=='NA' || $adm_acum=='NR'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                        }
                    }
                }else{
                    if($gsa_acum=='NA' || $gsa_acum=='NR'){
                        if($adm_acum=='NA' || $adm_acum=='NR'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$sst_acum*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$sst_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                        }
                    }else{
                        if($adm_acum=='NA' || $adm_acum=='NR'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.1/9)*3)+$calidad_acum*(0.2+(0.1/9)*2)+$sst_acum*(0.2+(0.1/9)*2)+$gsa_acum*(0.2+(0.1/9)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.0/10)*3)+$calidad_acum*(0.2+(0.0/10)*2)+$sst_acum*(0.2+(0.0/10)*2)+$gsa_acum*(0.2+(0.0/10)*2)+$adm_acum*(0.1+(0.0/10)*1);
                        }
                    }
                }
            }
					}
          //echo "<li>" . $PAC_acum . "<li>" . $calidad_acum . "<li>" . $gsa_acum . "<li>" . $sst_acum . "<li>" . $adm_acum . "<li>" . $cal_integral_acum ;
          $query7 = "UPDATE $db"."_cic SET Cal_Integral = $cal_integral, Cal_Integral_Acum = $cal_integral_acum WHERE Id=$Id;";
          // echo $query7;
          $resultado7= mysqli_query($conexion, $query7);
      }
    }

    function listar($db, $semana, $conexion){
        //require ("../conexion.php");
        $query7 = "SELECT COUNT(*) FROM $db"."_cic WHERE (Semana<=$semana)AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos' ";
        $resultado7 = mysqli_query($conexion, $query7);
        $data7=mysqli_fetch_assoc($resultado7);
        $conteo7=$data7["COUNT(*)"];
        if($conteo7==0){
            $arreglo["data"][]=array("Id" =>"", "Semana" => "", "subcontratista" => "", "correo_contacto" => "", "NIT" => "", "alcance" => "", "tipo_proveedor" => "", "PAC" => "", "P_Completado" => "", "Calidad" => "", "GSA" => "", "SST" => "", "ADM" => "", "Cal_Integral" => "", "Observaciones" => "", "mdo_cal_1" => "", "mdo_cal_2" => "", "mdo_cal_3" => "", "mdo_adm_1" => "", "mdo_adm_2" => "", "mdo_adm_3" => "", "mdo_adm_4" => "", "mdo_adm_5" => "", "mdo_gsa_1" => "", "mdo_gsa_2" => "", "mdo_gsa_3" => "", "mdo_gsa_4" => "", "mdo_gsa_5" => "", "mdo_gsa_6" => "", "mdo_gsa_7" => "", "mdo_gsa_8" => "", "mdo_sst_1" => "", "mdo_sst_2" => "", "mdo_sst_3" => "", "mdo_sst_4" => "", "mdo_sst_5" => "", "mdo_sst_6" => "", "mdo_sst_7" => "", "mdo_sst_8" => "", "mdo_sst_9" => "", "mdo_sst_10" => "", "si_cal_1" => "", "si_cal_2" => "", "si_cal_3" => "", "si_adm_1" => "", "si_adm_2" => "", "si_adm_3" => "", "si_adm_4" => "", "si_adm_5" => "", "si_adm_6" => "", "si_gsa_1" => "", "si_gsa_2" => "", "si_gsa_3" => "", "si_gsa_4" => "", "si_gsa_5" => "", "si_gsa_6" => "", "si_gsa_7" => "", "si_gsa_8" => "", "si_gsa_9" => "", "si_gsa_10" => "", "si_gsa_11" => "", "si_gsa_12" => "", "si_gsa_13" => "", "si_gsa_14" => "", "si_sst_1" => "", "si_sst_2" => "", "si_sst_3" => "", "si_sst_4" => "", "si_sst_5" => "", "si_sst_6" => "", "si_sst_7" => "", "si_sst_8" => "", "si_sst_9" => "", "si_sst_10" => "");
            echo json_encode($arreglo);
        }else{
            $query7 = "SELECT DISTINCT(subcontratista) FROM $db"."_cic WHERE (Semana<=$semana) AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos' ORDER BY `Semana` DESC, `subcontratista` ASC";
            $resultado7 = mysqli_query($conexion, $query7);
            if(!$resultado7){
                die("Error");
            }else{
							$query8 = "SELECT * FROM";
							$query8 .= " (";
							while($data=mysqli_fetch_assoc($resultado7)){
								$subcontratista = $data["subcontratista"];
								$query8 .= "SELECT `Id`, `Semana`, (SELECT COUNT(*) FROM $db"."_cic WHERE `subcontratista` = '$subcontratista' AND Semana <= $semana AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos')  AS `semanasEnProyecto`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, ";
								$query8 .= "`P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` ";
								$query8 .= "FROM $db"."_cic WHERE `subcontratista` = '$subcontratista' AND Semana = (SELECT MAX(`Semana`) FROM $db"."_cic WHERE `subcontratista` = '$subcontratista' AND Semana <= $semana AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos') AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos' UNION ";
							}
							$query8 = substr($query8, 0, -7);
							$query8 .= ") AS tabla ORDER BY `Semana` DESC, `subcontratista` ASC";
							$resultado8 = mysqli_query($conexion, $query8);

							if(!$resultado8){
	                die("Error");
	            }else{
								$subcontratistaAnterior = array();
	              while($data1=mysqli_fetch_assoc($resultado8)){
									$subcontratista = $data1["subcontratista"];

									$repetido = 0;
									if(count($subcontratistaAnterior)==0){
									}else{
										foreach($subcontratistaAnterior as $sa){
											if($subcontratista == $sa){
												$repetido ++;
											}
										}
									}

									if($repetido == 0){
										$arreglo["data"][] = array_map("utf8_encode", $data1);
										$subcontratistaAnterior[] = $subcontratista;
										$subcontratistaAnterior = array_unique($subcontratistaAnterior);
									}
	              }
	              $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
	              echo utf8_decode($json_codificado);
							}
            }
        }
        mysqli_close($conexion);
    }
