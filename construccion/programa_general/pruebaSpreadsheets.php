<?php

require '../../../../../vendor/autoload.php';

$db= "clinicaVeterinaria";

require ("../conexion.php");
$query="SELECT Semana, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, ROUND(Ejecutado*100,0) AS Ejecutado FROM $db"."_programa_consolidado WHERE Semana=(SELECT MAX(Semana) FROM $db"."_programa_consolidado)";
$resultado= mysqli_query($conexion, $query);

$tabla[] = ["Semana", "Id", "Actividad", "Titulo", "Fecha Inicio", "Fecha Fin", "Ruta Crítica", "Ejecutado"];

while($data=mysqli_fetch_assoc($resultado)){
  $fila ++;
  $Semana=$data["Semana"];
  $Id=$data["Id"];
  $Actividad=$data["Actividad"];
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

  $tabla[] = [$Semana, $Id, $Actividad, $Titulo, $Fecha_Inicio, $Fecha_Fin, $Ruta_Critica, $Ejecutado];

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
$archivoExcel->getProperties()->setCreator("Last Planner AIA")->setTitle("Corte de Programación Last Planner AIA");
$archivoExcel->setActiveSheetIndex(0);
$hojaActiva = $archivoExcel->getActiveSheet();

//Estilos Generales para el archivo
$archivoExcel->getDefaultStyle()->getFont()->setName('Calibri');
$archivoExcel->getDefaultStyle()->getFont()->setSize(12);
$hojaActiva->getStyle('A1:H1')->getFont()->setBold(true)->setSize(14);
$maximaFila = count($tabla);
$hojaActiva->getStyle('A1:H' . $maximaFila)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
$hojaActiva->getStyle('A1:H1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9D9D9');

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
$hojaActiva->getStyle('A1:H1')->applyFromArray($bordesPrimeraFila);

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
$hojaActiva->getStyle('A2:H' . $maximaFila)->applyFromArray($bordesActividades);
$hojaActiva->freezePane('A2');




// Define some styles for our Conditionals
$yellowStyle = new Style(false, true);
$yellowStyle->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getEndColor()->setARGB('C5D9F1');
$yellowStyle->getFont()->setBold(true);

$cellRange = 'A2:H' . $maximaFila;
$conditionalStyles = [];
$wizardFactory = new Wizard($cellRange);

$expressionWizard = $wizardFactory->newRule(Wizard::EXPRESSION);

$expressionWizard->expression('($D1)=1')
    ->setStyle($yellowStyle);
$conditionalStyles[] = $expressionWizard->getConditional();

$hojaActiva
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



$hojaActiva->fromArray(
  $tabla,
  NULL,
  'A1'
);

$archivoDescargar = "$db"."_"."semana_$Semana.xlsx";

$writer = new Xlsx($archivoExcel);
$writer->save($archivoDescargar);

//Nos aseguramos que el archivo exista
if (!file_exists($archivoDescargar)) {
  echo "El fichero $archivoDescargar no existe";
  exit;
}else{
  header('Content-Disposition: attachment; filename=' . $archivoDescargar );

  header("Content-Type: application/vnd.openxmlformats-   officedocument.spreadsheetml.sheet");

  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

  header('Content-Length: '.filesize($archivoDescargar));

  readfile($archivoDescargar);

  exit;
}
//
//
//   $table = '<table><tbody><tr><td>Semana</td><td>Id</td><td>Actividad</td><td>Titulo</td><td>Fecha_Inicio</td><td>Fecha_Fin</td><td>Ruta_Critica</td><td>Ejecutado</td></tr>';
//
//
//   $table.="</tbody></table>";
//   //echo $table;
//   $nombre=$db . "_semana_" . $Semana . ".xls";
//   header('Content-Encoding: UTF-8');
//   header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//   header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
//   header ("Cache-Control: no-cache, must-revalidate");
//   header ("Pragma: no-cache");
//   header ("Content-type: application/x-msexcel;charset=UTF-8");
//   header ("Content-Disposition: attachment; filename=$nombre" );
//
//   echo $table;
//
//   //$json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
//   //echo utf8_decode($json_codificado);
//
//   // El siguiente key se crea cuando se inicia sesión
//   $_SESSION["timeout"] = time();
//
// } else {
//   header('Location: ../login/login.php');
// }


?>
