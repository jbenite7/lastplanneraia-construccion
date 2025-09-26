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




$query="SELECT * FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa = '1' OR Activa = 'NA')";
$resultado= mysqli_query($conexion, $query);

$tabla[] = ["Semana", "Id", "Actividad", "Descripcion", "Subcontratista", "Responsable AIA", "Ejecución Actual", "Compromiso Para la Semana", "Ejecutado Real en la Semana", "PAC", "% Completado"];

while($data=mysqli_fetch_assoc($resultado)){
  $Semana=$data["Semana"];

  $Id=$data["Id"];

  $Actividad=$data["Actividad"];
  $Actividad=str_replace("<small>","",$Actividad);
  $Actividad=str_replace("</small>","",$Actividad);
  $Actividad=str_replace("<b>","",$Actividad);
  $Actividad=str_replace("</b>","",$Actividad);


  $Descripcion=$data["Descripcion"];

  $Sub_Contratista=$data["Sub_Contratista"];

  $Responsable_AIA=$data["Responsable_AIA"];

  $Unidad=$data["Unidad"];

  $cantidad_ppto=$data["cantidad_ppto"];

  if($cantidad_ppto==0 || $cantidad_ppto==NULL || $cantidad_ppto==''){
    $cantidad_ppto=100;
  }

  $Ejecutado= round($data["Ejecutado"] * $cantidad_ppto,2) . "$Unidad";

  $Compromiso= round($data["Compromiso"],2) . "$Unidad";

  $Ejecutado_Real= round($data["Ejecutado_Real"],2) . "$Unidad";

  $PAC= empty($data["PAC"]) ? "0%" : floatval(round($data["PAC"],2))*100 . "%";

  $P_Completado= empty($data["P_Completado"]) ? "0%" : floatval(round($data["P_Completado"],2))*100 . "%";

  $tabla[] = [$Semana, $Id, $Actividad, $Descripcion, $Sub_Contratista, $Responsable_AIA, $Ejecutado, $Compromiso, $Ejecutado_Real, $PAC, $P_Completado];

  //$arreglo[]=array_map("utf8_encode", $data);
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;

$archivoExcel = new Spreadsheet();
$archivoExcel->getProperties()->setCreator("Last Planner AIA")->setTitle("Compromisos Last Planner");
$archivoExcel->setActiveSheetIndex(0);
$hojaActiva = $archivoExcel->getActiveSheet();

//Estilos Generales para el archivo
$archivoExcel->getDefaultStyle()->getFont()->setName('Calibri');
$archivoExcel->getDefaultStyle()->getFont()->setSize(12);
$hojaActiva->getStyle('A3:K3')->getFont()->setBold(true)->setSize(14);
$hojaActiva->getStyle('A2:K2')->getFont()->setBold(true)->setSize(22);
$maximaFila = count($tabla);
$hojaActiva->getStyle('A2:K' . ($maximaFila+2))->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
$hojaActiva->getStyle('C2:I2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9D9D9');
$hojaActiva->getStyle('A3:K3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9D9D9');
//$hojaActiva->getStyle('J4:K')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);

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

$hojaActiva->getStyle('A2:B2')->applyFromArray($bordesPrimeraFila);
$hojaActiva->getStyle('C2:I2')->applyFromArray($bordesPrimeraFila);
$hojaActiva->getStyle('J2:K2')->applyFromArray($bordesPrimeraFila);
$hojaActiva->getStyle('A3:K3')->applyFromArray($bordesPrimeraFila);

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
$hojaActiva->getStyle('A4:K' . ($maximaFila+2))->applyFromArray($bordesActividades);
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
$hojaActiva->getColumnDimension('A')->setWidth(10);
$hojaActiva->getColumnDimension('B')->setWidth(15);
$hojaActiva->getColumnDimension('C')->setWidth(40);
$hojaActiva->getColumnDimension('D')->setWidth(15);
$hojaActiva->getColumnDimension('E')->setWidth(16);
$hojaActiva->getColumnDimension('F')->setWidth(15);
$hojaActiva->getColumnDimension('G')->setWidth(15);
$hojaActiva->getColumnDimension('H')->setWidth(15);
$hojaActiva->getColumnDimension('I')->setWidth(15);
$hojaActiva->getColumnDimension('J')->setWidth(15);
$hojaActiva->getColumnDimension('K')->setWidth(15);
$hojaActiva->getRowDimension('2')->setRowHeight(75);
$hojaActiva->mergeCells('A2:B2');
$hojaActiva->mergeCells('C2:I2');
$hojaActiva->mergeCells('J2:K2');

$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
$drawing->setName('logoAIA');
$drawing->setDescription('logoAIA');
$drawing->setPath('../imagenes/logoHorizontal.png'); // put your path and image here
$drawing->setCoordinates('A2');
$drawing->setOffsetX(2);
$drawing->setOffsetY(20);
$drawing->setWidth(185);
// $drawing->setRotation(25);
// $drawing->getShadow()->setVisible(true);
// $drawing->getShadow()->setDirection(45);
$drawing->setWorksheet($hojaActiva);

$hojaActiva->getCell('C2')->setValue("Compromisos Semanales Proyecto $proyecto \nSemana del $Fecha_Inicio_Sem al $Fecha_Fin_Sem");



$hojaActiva->fromArray(
  $tabla,
  NULL,
  'A3'
);

$fechaHoy = date("YmdBis");
$archivoDescargar = "compromisosSemana/". $fechaHoy . "$db"."_"."Compromisos_S"."$semana.xlsx";

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
