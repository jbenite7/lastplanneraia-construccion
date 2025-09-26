<?php
$db= $_POST["db"];
$semana = $_POST["semana"];


require __DIR__ . '../../../../vendor/autoload.php';
// require '../../composerFiles/vendor/autoload.php';
require ("../conexion.php");

$queryProyecto = "SELECT Proyecto_Proceso FROM general_proyectos_procesos WHERE Base_de_Datos = '$db'";
$resultadoProyecto= mysqli_query($conexion, $queryProyecto);
$dataProyecto=mysqli_fetch_assoc($resultadoProyecto);
$proyecto = $dataProyecto["Proyecto_Proceso"];

$queryFechas = "SELECT * FROM $db"."_semanas_activas WHERE Semana=$semana";
$resultadoFechas= mysqli_query($conexion, $queryFechas);
$dataFechas=mysqli_fetch_assoc($resultadoFechas);
$Fecha_Inicio_Sem=date("Y-m-d",strtotime($dataFechas["Fecha_Inicio_Sem"]));
$Fecha_Fin_Sem=date("Y-m-d",strtotime($dataFechas["Fecha_Fin_Sem"]));




$query="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semanas_Inicio<=6 AND Ejecutado<1 AND Titulo=0 ORDER BY Semanas_Inicio ASC, Estado_Restricciones DESC";
$resultado= mysqli_query($conexion, $query);

$tabla[] = ["Semana", "Consecutivo", "Id", "Actividad", "Semanas al Inicio", "Ejecutado", "Diseños y Especif.", "Materiales", "Mano de Obra", "Equipos", "Predecesoras", "Proced. Constructivo", "Modelación BIM", "% Liberación"];

$tabla2[] = ["Sub-Contratista", "Responsable AIA", "Observaciones"];

while($data=mysqli_fetch_assoc($resultado)){
  $Semana=$data["Semana"];

  $Consecutivo=$data["Consecutivo"];

  $Id=$data["Id"];

  $Actividad=$data["Actividad"];
  $Actividad=str_replace("<small>","",$Actividad);
  $Actividad=str_replace("</small>","",$Actividad);
  $Actividad=str_replace("<b>","",$Actividad);
  $Actividad=str_replace("</b>","",$Actividad);


  $Semanas_Inicio=$data["Semanas_Inicio"];

  $cantidad_ppto=$data["cantidad_ppto"];

  if($cantidad_ppto==0 || $cantidad_ppto==NULL || $cantidad_ppto==''){
    $cantidad_ppto=100;
  }

  if($data["unidad"] == "" || !$data["unidad"] || $data["unidad"] == null  || $data["unidad"] == '%'){
    $unidad="%";
    $Ejecutado= round($data["Ejecutado"] * $cantidad_ppto,2) . "$unidad";
  }else{
    $unidad=$data["unidad"];
    $Ejecutado= round($data["Ejecutado"] * $cantidad_ppto,2) . " $unidad (" . round($data["Ejecutado"] * 100,2) . "%)";
  }

  $D_y_E=$data["D_y_E"] == "N/A" ? $data["D_y_E"] : ($data["D_y_E"] * 100) . "%";

  $Materiales=$data["Materiales"] == "N/A" ? $data["Materiales"] : ($data["Materiales"] * 100) . "%";

  $MdeO=$data["MdeO"] == "N/A" ? $data["MdeO"] : ($data["MdeO"] * 100) . "%";

  $Equipos=$data["Equipos"] == "N/A" ? $data["Equipos"] : ($data["Equipos"] * 100) . "%";

  $Predecesora=$data["Predecesora"] == "N/A" ? $data["Predecesora"] : ($data["Predecesora"] * 100) . "%";

  $Pdto_Cons=$data["Pdto_Cons"] == "N/A" ? $data["Pdto_Cons"] : ($data["Pdto_Cons"] * 100) . "%";


  $Modelo=$data["Modelo"] == "N/A" ? $data["Modelo"] : ($data["Modelo"] * 100) . "%";

  $Estado_Restricciones= ($data["Estado_Restricciones"] * 100) . "%";

  $Sub_Contratista=$data["Sub_Contratista"];

  $Responsable_AIA=$data["Responsable_AIA"];

  $Observaciones=$data["Observaciones"];

  $tabla[] = [$Semana, $Consecutivo, $Id, $Actividad, $Semanas_Inicio, $Ejecutado, $D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo];

  $tabla2[] = [$Sub_Contratista, $Responsable_AIA, $Observaciones];

  //$arreglo[]=array_map("utf8_encode", $data);
}

$queryProfesionales="SELECT nombre FROM $db"."_profesionales WHERE activo = 1";
$resultadoProfesionales= mysqli_query($conexion, $queryProfesionales);

$tablaProfesionales[] = ["nombre"];

while($dataProfesionales=mysqli_fetch_assoc($resultadoProfesionales)){

  $nombre=$dataProfesionales["nombre"];


  $tablaProfesionales[] = [$nombre];
}

$querySubcontratistas="SELECT subcontratista FROM $db"."_subcontratistas WHERE activo = 1";
$resultadoSubcontratistas= mysqli_query($conexion, $querySubcontratistas);

$tablaSubcontratistas[] = ["subcontratista"];

while($dataSubcontratistas=mysqli_fetch_assoc($resultadoSubcontratistas)){

  $subcontratista=$dataSubcontratistas["subcontratista"];


  $tablaSubcontratistas[] = [$subcontratista];
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;

$archivoExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load("cortesRestricciones/plantillaLiberacionRestricciones.xlsx");
$archivoExcel->getProperties()->setCreator("Last Planner AIA")->setTitle("Liberación de Restricciones");
$archivoExcel->setActiveSheetIndexByName("Restricciones");
$hojaActiva = $archivoExcel->getActiveSheet();
$hojaListas = $archivoExcel->getSheetByName("Listas");

//Estilos Generales para el archivo
$archivoExcel->getDefaultStyle()->getFont()->setName('Calibri');
$archivoExcel->getDefaultStyle()->getFont()->setSize(12);
$hojaActiva->getStyle('A3:Q3')->getFont()->setBold(true)->setSize(12);
$hojaActiva->getStyle('A2:Q2')->getFont()->setBold(true)->setSize(22);
$maximaFila = count($tabla);
$hojaActiva->getStyle('A2:Q' . ($maximaFila+2))->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
$hojaActiva->getStyle('C2:O2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9D9D9');
$hojaActiva->getStyle('A3:Q3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9D9D9');

$bordesPrimeraFila = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['argb' => '000000'],
        ],
        'outline' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
            'color' => ['argb' => '000000'],
        ],
    ],
];

$hojaActiva->getStyle('A2:C2')->applyFromArray($bordesPrimeraFila);
$hojaActiva->getStyle('D2:O2')->applyFromArray($bordesPrimeraFila);
$hojaActiva->getStyle('P2:Q2')->applyFromArray($bordesPrimeraFila);
$hojaActiva->getStyle('A3:Q3')->applyFromArray($bordesPrimeraFila);

$bordesActividades = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR,
            'color' => ['argb' => '000000'],
        ],
        'outline' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
            'color' => ['argb' => '000000'],
        ],
    ],
];
$hojaActiva->getStyle('A4:Q' . ($maximaFila+2))->applyFromArray($bordesActividades);
$hojaActiva->freezePane('A4');




// Define some styles for our Conditionals
// $yellowStyle = new Style(false, true);
// $yellowStyle->getFill()
//     ->setFillType(Fill::FILL_SOLID)
//     ->getEndColor()->setARGB('C5D9F1');
// $yellowStyle->getFont()->setBold(true);

// $cellRange = 'A4:H' . ($maximaFila+1);
// $conditionalStyles = [];
// $wizardFactory = new Wizard($cellRange);
//
// $expressionWizard = $wizardFactory->newRule(Wizard::EXPRESSION);
//
// $expressionWizard->expression('($D1)=1')
//     ->setStyle($yellowStyle);
// $conditionalStyles[] = $expressionWizard->getConditional();
//
// $hojaActiva
//   ->getStyle($expressionWizard->getCellRange())
//   ->setConditionalStyles($conditionalStyles);

//Estilos para columnas
$hojaActiva->getColumnDimension('A')->setWidth(11);
$hojaActiva->getColumnDimension('B')->setWidth(11);
$hojaActiva->getColumnDimension('C')->setWidth(11);
$hojaActiva->getColumnDimension('D')->setWidth(30);
$hojaActiva->getColumnDimension('E')->setWidth(10);
$hojaActiva->getColumnDimension('F')->setWidth(10);
$hojaActiva->getColumnDimension('G')->setWidth(11);
$hojaActiva->getColumnDimension('H')->setWidth(11);
$hojaActiva->getColumnDimension('I')->setWidth(11);
$hojaActiva->getColumnDimension('J')->setWidth(11);
$hojaActiva->getColumnDimension('K')->setWidth(12);
$hojaActiva->getColumnDimension('L')->setWidth(12);
$hojaActiva->getColumnDimension('M')->setWidth(12);
$hojaActiva->getColumnDimension('N')->setWidth(11);
$hojaActiva->getColumnDimension('O')->setWidth(15);
$hojaActiva->getColumnDimension('P')->setWidth(15);
$hojaActiva->getColumnDimension('Q')->setWidth(25);
$hojaActiva->getRowDimension('2')->setRowHeight(75);
$hojaActiva->mergeCells('A2:C2');
$hojaActiva->mergeCells('D2:O2');
$hojaActiva->mergeCells('P2:Q2');

$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
$drawing->setName('logoAIA');
$drawing->setDescription('logoAIA');
$drawing->setPath('../imagenes/logoHorizontal.png'); // put your path and image here
$drawing->setCoordinates('A2');
$drawing->setOffsetX(2);
$drawing->setOffsetY(20);
$drawing->setWidth(170);
// $drawing->setRotation(25);
// $drawing->getShadow()->setVisible(true);
// $drawing->getShadow()->setDirection(45);
$drawing->setWorksheet($hojaActiva);

$hojaActiva->getCell('D2')->setValue("Liberación de Restricciones Proyecto $proyecto \nSemana del $Fecha_Inicio_Sem al $Fecha_Fin_Sem");



$hojaActiva->fromArray(
  $tabla,
  NULL,
  'A3'
);

$hojaActiva->fromArray(
  $tabla2,
  NULL,
  'O3'
);

$hojaListas->fromArray(
  $tablaProfesionales,
  NULL,
  'A1'
);

$hojaListas->fromArray(
  $tablaSubcontratistas,
  NULL,
  'B1'
);

//Proteger hoja
$protection = $archivoExcel->getActiveSheet()->getProtection();
$protection->setPassword('aia2022*');
$protection->setSheet(true);
$protection->setSort(true);
$protection->setInsertRows(true);
$protection->setFormatCells(true);

$protection = $archivoExcel->getSheetByName("Listas")->getProtection();
$protection->setPassword('aia2022*');
$protection->setSheet(true);
$protection->setSort(true);
$protection->setInsertRows(true);
$protection->setFormatCells(true);

//Ocultar columnas
$hojaActiva->getColumnDimension('B')->setVisible(false);

//Ocultar hoja
$hojaListas->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);


$fechaHoy = date("YmdBis");
$archivoDescargar = "cortesRestricciones/". $fechaHoy . "$db"."_"."Liberacion_Restricciones_S"."$semana.xlsx";

$writer = new Xlsx($archivoExcel);
$writer->save($archivoDescargar);

sleep(2);

//Nos aseguramos que el archivo exista
if (!file_exists($archivoDescargar)) {
  echo "El fichero $archivoDescargar no existe";
  exit;
}else{
  $respuesta = "Funciona";
  echo json_encode($archivoDescargar);
  // unlink($archivoDescargar);

  exit;
}


?>
