<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/document_generators/classes/PHPExcel.php');

use catechesis\PdoDatabaseManager;
use core\domain\Sacraments;
use catechesis\Utils;
use catechesis\Configurator;



/** Error reporting */
//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);		
//ini_set('display_startup_errors', TRUE);	
date_default_timezone_set('Europe/London');

define('EOL', (PHP_SAPI == 'cli') ? PHP_EOL : '<br />');


//Funcao para libertar recursos e terminar em caso de erro
function abortar()
{
    die();
}


//Funcao para escrever erro em vez de devolver ficheiro
function erro($msg)
{
    header('Content-Type: text/html; charset=UTF-8');
    echo("<p>" . $msg . "</p>");
    abortar();
}


// Constants
$HEADER_BACKGROUND_COLOR = new PHPExcel_Style_Color();
$HEADER_BACKGROUND_COLOR->setARGB('ff008fcf');
$HEADER_FONT_COLOR = new PHPExcel_Style_Color();
$HEADER_FONT_COLOR->setARGB('fffff9f9');
$ODD_ROW_COLOR = new PHPExcel_Style_Color();
$ODD_ROW_COLOR->setARGB('fff5f5f5');


$fileType = null;
$catechumensList = array();
$entityName = null;

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    if ($_POST['file_type'])
        $fileType = Utils::sanitizeInput($_POST['file_type']);

    if($_POST['entity_name'])
        $entityName = Utils::sanitizeInput($_POST['entity_name']);

    if ($_POST['catechumens_list'])
        $catechumensList = $_POST['catechumens_list'];

    if (!$fileType || $fileType == "")
    {
        erro("Tipo de ficheiro de saída não especificado.");
    }


    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();

    // Set document properties
    $objPHPExcel->getProperties()->setCreator($_SESSION['nome_utilizador'])
        ->setLastModifiedBy($_SESSION['nome_utilizador'])
        ->setTitle("Listagem de " . $entityName . "s")
        ->setSubject("Listagem de " . $entityName . "s")
        ->setDescription("Listagem de " . $entityName . "s")
        ->setKeywords("catequizandos CatecheSis")
        ->setCategory("Listagem");

    $db = new PdoDatabaseManager();

    //Get catechumens
    $result = null;

    // Optional fields configuration
    $nifEnabled = false;
    try {
        $nifEnabled = Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED);
    } catch (Exception $e) { /* keep default false on error */ }
    try
    {
        foreach ($catechumensList as $cid)
        {
            $catechumen = $db->getCatechumenById(intval($cid));
            $catechismGroup =  $db->getCatechumenCurrentCatechesisGroup(intval($cid), Utils::currentCatecheticalYear());
            if(!isset($catechismGroup))
            {
                $catechismGroup['ano_catecismo'] = null;
                $catechismGroup['turma'] = null;
            }

            $catechumen = array_merge($catechumen, $catechismGroup);
            $result[] = $catechumen;
        }
    }
    catch (Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        abortar();
    }


    //Resultados
    if ($result)
    {
        // Set outline levels
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setOutlineLevel(1)
            ->setVisible(true)
            ->setCollapsed(false)
            ->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setOutlineLevel(1)
            ->setVisible(true)
            ->setCollapsed(false)
            ->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setOutlineLevel(1)
            ->setVisible(true)
            ->setCollapsed(false)
            ->setAutoSize(true);
        if($nifEnabled)
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setOutlineLevel(1)
                ->setVisible(true)
                ->setCollapsed(false)
                ->setAutoSize(true);

        // Freeze panes
        $objPHPExcel->getActiveSheet()->freezePane('A2');

        // Rows to repeat at top
        $objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);


        // Set titles in bold
        $objNomeTitle = new PHPExcel_RichText();
        $objNomeTitle->createText('');
        $objNomePayable = $objNomeTitle->createTextRun('Nome');
        $objNomePayable->getFont()->setBold(true);
        if($fileType == "pdf")
            $objNomePayable->getFont()->setColor($HEADER_FONT_COLOR);

        $objNifTitle = null;
        if($nifEnabled)
        {
            $objNifTitle = new PHPExcel_RichText();
            $objNifTitle->createText('');
            $objNifPayable = $objNifTitle->createTextRun('NIF');
            $objNifPayable->getFont()->setBold(true);
            if($fileType == "pdf")
                $objNifPayable->getFont()->setColor($HEADER_FONT_COLOR);
        }

        $objDataNascTitle = new PHPExcel_RichText();
        $objDataNascTitle->createText('');
        $objDataNascPayable = $objDataNascTitle->createTextRun('Data de nascimento');
        $objDataNascPayable->getFont()->setBold(true);
        if($fileType == "pdf")
            $objDataNascPayable->getFont()->setColor($HEADER_FONT_COLOR);


        $objCatTitle = new PHPExcel_RichText();
        $objCatTitle->createText('');
        $objCatPayable = $objCatTitle->createTextRun('Catecismo (' . Utils::formatCatecheticalYear(Utils::currentCatecheticalYear()) . ')');
        $objCatPayable->getFont()->setBold(true);
        if($fileType == "pdf")
            $objCatPayable->getFont()->setColor($HEADER_FONT_COLOR);

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', $objNomeTitle);
        if($nifEnabled)
            $objPHPExcel->getActiveSheet()->setCellValue('B1', $objNifTitle);
        $objPHPExcel->getActiveSheet()
            ->setCellValue(($nifEnabled?'C1':'B1'), $objDataNascTitle)
            ->setCellValue(($nifEnabled?'D1':'C1'), $objCatTitle);

        $lastHeaderCol = ($nifEnabled ? 'D' : 'C');

        // Set header line color
        if($fileType == "pdf")
        {
            $objPHPExcel->getActiveSheet()->getStyle('A1:' . $lastHeaderCol . '1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A1:' . $lastHeaderCol . '1')->getFill()->setStartColor($HEADER_BACKGROUND_COLOR);
            $objPHPExcel->getActiveSheet()->getStyle('A1:' . $lastHeaderCol . '1')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        }

        // Set auto filters in all the columns
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:' . $lastHeaderCol . '1');

        $linha = 2;
        foreach ($result as $row)
        {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $linha, $row['nome']);
            if($nifEnabled)
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $linha, $row['nif']);
            $objPHPExcel->getActiveSheet()->setCellValue(($nifEnabled?'C':'B') . $linha, date("d-m-Y", strtotime($row['data_nasc'])));
            $objPHPExcel->getActiveSheet()->setCellValue(($nifEnabled?'D':'C') . $linha, ($row['ano_catecismo'] ? ($row['ano_catecismo'] . "º" . $row['turma']) : "-"));

            $lastCol = ($nifEnabled ? 'D' : 'C');
            // Tint alternate lines in PDF output (in Excel output it does not make sense, because the user may want to apply filters)
            if($fileType == "pdf" && ($linha % 2 != 0))
            {
                $objPHPExcel->getActiveSheet()->getStyle('A' . $linha . ":" . $lastCol . $linha)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $objPHPExcel->getActiveSheet()->getStyle('A' . $linha . ":" . $lastCol . $linha)->getFill()->setStartColor($ODD_ROW_COLOR);
            }

            $linha++;
        }
    }
    else
    {
        erro("Não há resultados para transferir.");
    }


    //Construir nome do ficheiro
    $filename = "Listagem de " . $entityName . "s";

    switch ($fileType)
    {
        case "xls":

            $filename .= ".xls";

            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');

            break;


        case "pdf":

            $rendererName = PHPExcel_Settings::PDF_RENDERER_DOMPDF;
            $rendererLibrary = 'dompdf-0.6.1';
            $rendererLibraryPath = __DIR__ . '/core/document_generators/libraries/PDF/' . $rendererLibrary;


            if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath))
            {
                erro("FATAL! Contactar o programador...");
            }
            /*else
                erro($rendererLibraryPath);*/


            $filename .= ".pdf";

            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
            $objWriter->save('php://output');

            break;


        default:
            erro("Tipo de ficheiro de saída desconhecido.");
            break;
    }
}


//Libertar recursos
$result = null;
$db = null;
?>

