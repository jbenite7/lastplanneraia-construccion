<?php

require '../../../../../vendor/autoload.php';
// require '../vendor/autoload.php';
require ("../conexion.php");

$db= $_POST["db"];
//$db= "accesibilidadMetroB";

$query="SELECT * FROM $db"."_cambios";
$resultado= mysqli_query($conexion, $query);

$tabla[] = ["Id", "Solicitante", "Fecha Solicitud", "Prioridad", "Tipo de Cambio", "Responsable", "Descripcion","Días Afectación Cronograma", "Costo Directo + AIU + IVA", "Valor Aprobado", "Fecha Tentativa de Definición", "Fecha de Entrega a Interventoría", "Fecha de Definición", "Aprobación"];

while($data=mysqli_fetch_assoc($resultado)){

  extract($data);

  switch ($solicitanteCambio) {
    case 1:
      $solicitanteCambio = "Obra";
      break;

    case 2:
      $solicitanteCambio = "Cliente";
      break;

    case 3:
      $solicitanteCambio = "Interventoría";
      break;

    case 4:
      $solicitanteCambio = "Otro [$detalleSolicitanteOtro]";
      break;
  }

  switch ($responsableSolucion) {
    case 1:
      $responsableSolucion = "Obra";
      break;

    case 2:
      $responsableSolucion = "Cliente";
      break;

    case 3:
      $responsableSolucion = "Interventoría";
      break;

    case 4:
      $responsableSolucion = "Otro [$detalleResponsableSolucion]";
      break;
  }

  switch ($aprobacion) {
    case 1:
      $aprobacion = "Aprobado";
      break;

    case 2:
      $aprobacion = "Aprobado con Restricciones";
      break;

    case 3:
      $aprobacion = "No Aprobado";
      break;

    case 4:
      $aprobacion = "En Estudio";
      break;
    
    case 5:
      $aprobacion = "Desistido";
      break;
  }

  $tipoCambio = json_decode($tipoCambio, true);
  $listaCambios = "";
  foreach ($tipoCambio["tiposCambio"] as $key => $value) {
    if ($value === '0') { // if it's valued to '0'
    } else {
        $listaCambios .= $key . ", ";
    }
  } 
  $listaCambios = substr($listaCambios, 0, -2);
  $tabla[] = [$id, $solicitanteCambio, $fechaSolicitud, $prioridad, $listaCambios, $responsableSolucion, $descripcion, $inputTiempoCronogramaAfectado, $costoDirectoAIUIVA, $valorAprobado, $fechaTentativaDefinicion, $fechaEntregaInterventoria, $fechaDefinicion, $aprobacion];
}
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$archivoExcel = new Spreadsheet();
$archivoExcel->getProperties()->setCreator("Last Planner AIA")->setTitle("Consolidado Ordenes de Cambio");
$archivoExcel->setActiveSheetIndex(0);
$hojaActiva = $archivoExcel->getActiveSheet();
$hojaActiva->setTitle("Consolidado ODC");

//Estilos Generales para el archivo
$archivoExcel->getDefaultStyle()->getFont()->setName('Calibri');
$archivoExcel->getDefaultStyle()->getFont()->setSize(12);
$hojaActiva->getStyle('A1:N1')->getFont()->setBold(true)->setSize(14);
$maximaFila = count($tabla);
$hojaActiva->getStyle('A1:N' . $maximaFila)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
$hojaActiva->getStyle('A1:N1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9D9D9');

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
$hojaActiva->getStyle('A1:N1')->applyFromArray($bordesPrimeraFila);

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
$hojaActiva->getStyle('A2:N' . $maximaFila)->applyFromArray($bordesActividades);
$hojaActiva->freezePane('A2');

// Define some styles for our Conditionals
$formatoCondicionalEstudio = new Style(false, true);
$formatoCondicionalEstudio->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getEndColor()->setARGB('C5D9F1');
$formatoCondicionalEstudio->getFont()->setBold(true);

$formatoCondicionalNoAprobado = new Style(false, true);
$formatoCondicionalNoAprobado->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getEndColor()->setARGB('DA9694');
$formatoCondicionalNoAprobado->getFont()->setBold(true);

$formatoCondicionalAprobadoRestricciones = new Style(false, true);
$formatoCondicionalAprobadoRestricciones->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getEndColor()->setARGB('FFFFBD');
$formatoCondicionalAprobadoRestricciones->getFont()->setBold(true);

$formatoCondicionalAprobado = new Style(false, true);
$formatoCondicionalAprobado->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getEndColor()->setARGB('C0E399');
$formatoCondicionalAprobado->getFont()->setBold(true);

$formatoCondicionalDesistido = new Style(false, true);
$formatoCondicionalDesistido->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getEndColor()->setARGB('808080');
$formatoCondicionalDesistido->getFont()->setBold(true);

$cellRange = 'A2:N' . $maximaFila;
$conditionalStyles = [];
$wizardFactory = new Wizard($cellRange);

$expressionWizard = $wizardFactory->newRule(Wizard::EXPRESSION);

$expressionWizard->expression('($N1)="En Estudio"')->setStyle($formatoCondicionalEstudio);
$conditionalStyles[] = $expressionWizard->getConditional();

$expressionWizard->expression('($N1)="No Aprobado"')->setStyle($formatoCondicionalNoAprobado);
$conditionalStyles[] = $expressionWizard->getConditional();

$expressionWizard->expression('($N1)="Aprobado con Restricciones"')->setStyle($formatoCondicionalAprobadoRestricciones);
$conditionalStyles[] = $expressionWizard->getConditional();

$expressionWizard->expression('($N1)="Aprobado"')->setStyle($formatoCondicionalAprobado);
$conditionalStyles[] = $expressionWizard->getConditional();

$expressionWizard->expression('($N1)="Desistido"')->setStyle($formatoCondicionalDesistido);
$conditionalStyles[] = $expressionWizard->getConditional();

$hojaActiva
  ->getStyle($expressionWizard->getCellRange())
  ->setConditionalStyles($conditionalStyles);

//Estilos para columnas
$hojaActiva->getColumnDimension('A')->setWidth(10);
$hojaActiva->getColumnDimension('B')->setWidth(15);
$hojaActiva->getColumnDimension('C')->setWidth(15);
$hojaActiva->getStyle('C')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
$hojaActiva->getColumnDimension('D')->setWidth(10);
$hojaActiva->getColumnDimension('E')->setWidth(20);
$hojaActiva->getColumnDimension('F')->setWidth(15);
$hojaActiva->getColumnDimension('G')->setWidth(30);
$hojaActiva->getColumnDimension('H')->setWidth(15);
$hojaActiva->getColumnDimension('I')->setWidth(15);
$hojaActiva->getColumnDimension('J')->setWidth(15);
$hojaActiva->getColumnDimension('K')->setWidth(15);
$hojaActiva->getStyle('K')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
$hojaActiva->getColumnDimension('L')->setWidth(15);
$hojaActiva->getStyle('L')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
$hojaActiva->getColumnDimension('M')->setWidth(15);
$hojaActiva->getStyle('M')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
$hojaActiva->getColumnDimension('N')->setWidth(15);
$hojaActiva->getStyle('I:J')->getNumberFormat()->setFormatCode('"$"#,##0');



$hojaActiva->fromArray(
  $tabla,
  NULL,
  'A1'
);

// Cambiar el zoom al 80%
$hojaActiva->getSheetView()->setZoomScale(80);

$archivoExcel->setActiveSheetIndex(0);

$fechaHoy = date("YmdBis");
$archivoDescargar = "ordenes/". $fechaHoy . "_$db"."_"."ConsolidadoODC.xlsx";

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
