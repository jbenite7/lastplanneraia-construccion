<?php
$db= $_POST["db"];
$semana = $_POST["semana"];


require __DIR__ . '../../../../vendor/autoload.php';
// require '../../composerFiles/vendor/autoload.php';
require ("../conexion.php");

$query="SELECT Semana, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, ROUND(Ejecutado*100,0) AS Ejecutado, cantidad_ppto, unidad FROM $db"."_programa_consolidado WHERE Semana=$semana";
$resultado= mysqli_query($conexion, $query);

$tabla[] = ["Semana", "Id", "Actividad", "Titulo", "Fecha Inicio", "Fecha Fin", "Ruta Crítica", "Cantidad PPTO", "Ejecutado", "Cantidad Ejecutada", "Unidad"];
$tablaAssemble[] = ["Semana", "Id", "Actividad", "Titulo", "Fecha Inicio", "Fecha Fin", "Ruta Crítica", "Cantidad PPTO", "Ejecutado", "Cantidad Ejecutada", "Unidad"];

while($data=mysqli_fetch_assoc($resultado)){

  $Semana=$data["Semana"];
  $Id=$data["Id"];
  $Actividad=$data["Actividad"];
  $Actividad=str_replace("<small>","",$Actividad);
  $Actividad=str_replace("</small>","",$Actividad);
  $Actividad=str_replace("<b>","",$Actividad);
  $Actividad=str_replace("</b>","",$Actividad);
  $data["Actividad"]=$Actividad;
  $Fecha_Inicio=date("Y-m-d", strtotime($data["Fecha_Inicio"]));
  $Fecha_Fin=date("Y-m-d", strtotime($data["Fecha_Fin"]));
  $Fecha_Inicio_Assemble=date("m/d/Y", strtotime($data["Fecha_Inicio"]));
  $Fecha_Fin_Assemble=date("m/d/Y", strtotime($data["Fecha_Fin"]));
  $Ejecutado=$data["Ejecutado"];
  $Titulo=$data["Titulo"];
  $Ruta_Critica=$data["Ruta_Critica"];
  $cantidad_ppto=$data["cantidad_ppto"];
  $unidad=$data["unidad"];

  if($Ejecutado=="" || $Ejecutado==null){
    $cantidadEjecutada="";
    $unidad="";
  }else{
    if(empty($cantidad_ppto)){
      $cantidadEjecutada=$Ejecutado;
    }else{
      
      $cantidadEjecutada = ($Ejecutado == 0) ? "0" : ($Ejecutado * $cantidad_ppto / 100);
    }
    if(empty($unidad)){
      $unidad="%";
    }else{
      $unidad= $unidad;
    }
    $Ejecutado="$Ejecutado%";
  }

  if($Ruta_Critica == 1){
      $Ruta_Critica="Ruta critica";
  }else{
      $Ruta_Critica="Actividades NO criticas";
  }
  $data["Ejecutado"]=$Ejecutado;

  $tabla[] = [$Semana, $Id, $Actividad, $Titulo, $Fecha_Inicio, $Fecha_Fin, $Ruta_Critica, $cantidad_ppto, $Ejecutado, $cantidadEjecutada, $unidad];
  $tablaAssemble[] = [$Semana, $Id, $Actividad, $Titulo, $Fecha_Inicio_Assemble, $Fecha_Fin_Assemble, $Ruta_Critica, $cantidad_ppto, $Ejecutado, $cantidadEjecutada, $unidad];
}

//print_r($tabla);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$archivoExcel = new Spreadsheet();
$archivoExcel->getProperties()->setCreator("Last Planner AIA")->setTitle("Corte de Programación Last Planner AIA");
$archivoExcel->setActiveSheetIndex(0);
$hojaActiva = $archivoExcel->getActiveSheet();
$hojaActiva->setTitle("Corte Programacion");
$archivoExcel->createSheet();
$hojaAssemble = $archivoExcel->getSheet(1);
$hojaAssemble->setTitle("ASSEMBLE");

//Estilos Generales para el archivo
$archivoExcel->getDefaultStyle()->getFont()->setName('Calibri');
$archivoExcel->getDefaultStyle()->getFont()->setSize(12);
$hojaActiva->getStyle('A1:K1')->getFont()->setBold(true)->setSize(14);
$hojaAssemble->getStyle('A1:K1')->getFont()->setBold(true)->setSize(14);
$maximaFila = count($tabla);
$hojaActiva->getStyle('A1:K' . $maximaFila)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
$hojaActiva->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9D9D9');
$hojaAssemble->getStyle('A1:K' . $maximaFila)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
$hojaAssemble->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9D9D9');

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
$hojaActiva->getStyle('A1:K1')->applyFromArray($bordesPrimeraFila);
$hojaAssemble->getStyle('A1:K1')->applyFromArray($bordesPrimeraFila);

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
$hojaActiva->getStyle('A2:K' . $maximaFila)->applyFromArray($bordesActividades);
$hojaActiva->freezePane('A2');
$hojaAssemble->getStyle('A2:K' . $maximaFila)->applyFromArray($bordesActividades);
$hojaAssemble->freezePane('A2');




// Define some styles for our Conditionals
$formatoCondicionalTitulos = new Style(false, true);
$formatoCondicionalTitulos->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getEndColor()->setARGB('C5D9F1');
$formatoCondicionalTitulos->getFont()->setBold(true);

$cellRange = 'A2:K' . $maximaFila;
$conditionalStyles = [];
$wizardFactory = new Wizard($cellRange);

$expressionWizard = $wizardFactory->newRule(Wizard::EXPRESSION);

$expressionWizard->expression('($D1)=1')
    ->setStyle($formatoCondicionalTitulos);
$conditionalStyles[] = $expressionWizard->getConditional();

$hojaActiva
  ->getStyle($expressionWizard->getCellRange())
  ->setConditionalStyles($conditionalStyles);

$hojaAssemble
  ->getStyle($expressionWizard->getCellRange())
  ->setConditionalStyles($conditionalStyles);

//Estilos para columnas
$hojaActiva->getColumnDimension('A')->setWidth(10);
$hojaActiva->getColumnDimension('B')->setWidth(15);
$hojaActiva->getColumnDimension('C')->setWidth(40);
$hojaActiva->getColumnDimension('D')->setWidth(10);
$hojaActiva->getColumnDimension('E')->setWidth(12);
$hojaActiva->getColumnDimension('F')->setWidth(12);
$hojaActiva->getStyle('E')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
$hojaActiva->getStyle('F')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
$hojaActiva->getColumnDimension('G')->setWidth(20);
$hojaActiva->getColumnDimension('H')->setWidth(12);
$hojaActiva->getColumnDimension('I')->setWidth(12);
$hojaActiva->getColumnDimension('J')->setWidth(12);
$hojaActiva->getColumnDimension('K')->setWidth(12);

$hojaAssemble->getColumnDimension('A')->setWidth(10);
$hojaAssemble->getColumnDimension('B')->setWidth(15);
$hojaAssemble->getColumnDimension('C')->setWidth(40);
$hojaAssemble->getColumnDimension('D')->setWidth(10);
$hojaAssemble->getColumnDimension('E')->setWidth(12);
$hojaAssemble->getColumnDimension('F')->setWidth(12);
$hojaAssemble->getStyle('E')->getNumberFormat()->setFormatCode('MM/DD/YYYY');
$hojaAssemble->getStyle('F')->getNumberFormat()->setFormatCode('MM/DD/YYYY');
$hojaAssemble->getColumnDimension('G')->setWidth(20);
$hojaAssemble->getColumnDimension('H')->setWidth(12);
$hojaAssemble->getColumnDimension('I')->setWidth(12);
$hojaAssemble->getColumnDimension('J')->setWidth(12);
$hojaAssemble->getColumnDimension('K')->setWidth(12);




$hojaActiva->fromArray(
  $tabla,
  NULL,
  'A1'
);

$hojaAssemble->fromArray(
  $tablaAssemble,
  NULL,
  'A1'
);

$archivoExcel->setActiveSheetIndex(0);

$fechaHoy = date("YmdBis");
$archivoDescargar = "cortesProgramacion/". $fechaHoy . "_$db"."_"."semana_$Semana.xlsx";

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
  exit;
}


?>
