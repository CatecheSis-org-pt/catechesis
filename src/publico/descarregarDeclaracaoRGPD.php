<?php
require_once(__DIR__ . "/../core/Utils.php");
require_once(__DIR__ . "/../core/document_generators/libraries/vendor/autoload.php");

use catechesis\Utils;
use Dompdf\Dompdf;
use Dompdf\Options;


$dompdf = new Dompdf();
//$dompdf->setOptions((new Options())->setChroot(__DIR__ . "/../core/document_generators/")); //Set permissions to read template files
$dompdf->loadHtml(Utils::renderPhp(__DIR__ . "/../core/document_generators/templateGDPR.php"));

//Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream();

?>