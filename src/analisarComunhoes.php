<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . '/core/decision_support.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/CatechumensReport/CatechumensReportWidget.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\UserData;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\CatechumensReportWidget;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::SACRAMENTS);
$pageUI->addWidget($menu);
$reportWidget = new CatechumensReportWidget();
$pageUI->addWidget($reportWidget);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Apoio à decisão -- Primeiras Comunhões</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  
  <style>
  	@media print
	{    
	    .no-print, .no-print *
	    {
		display: none !important;
	    }
	    
	    
	    a[href]:after {
		    content: none;
		  }
		  
	    /*@page {
		    size: 297mm 210mm;*/ /* landscape */
		    /* you can also specify margins here: */
		    /*margin: 35mm;*/
		    /*margin-right: 45mm;*/ /* for compatibility with both A4 and Letter */
		 /* }*/

		 /* Para imprimir os crachas dos sacramentos a cores */
	    .label-success
	    {
	      color: white !important;
	      background-color: #5cb85c !important;
	    }
	    /*.label-default
	    {
	      color: white !important;
	      background-color: #777 !important;
	    }*/

	    .panel-default > .panel-heading 
	    {
			color: #333 !important;
			background-color: #f5f5f5 !important;
			border-color: #ddd !important;
		}
		  
	}
	
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
	}
  </style>
  
  <style>
	  .btn-group-hover .btn {
	    /*border-color: white;*/
	    background: white;
	    text-shadow: 0 1px 1px white;
	    -webkit-box-shadow: inset 0 1px 0 white;
	    -moz-box-shadow: inset 0 1px 0 white;
	    box-shadow: inset 0 1px 0 white;
	}
	  .btn-group-hover {
		    opacity: 0;
	}
	
	
	
		.rowlink {
	  
		    cursor: pointer;
		}
  </style>
</head>
<body>


<?php

$menu->renderHTML();
?>



<div class="container" id="contentor">

    <div class="only-print" style="top: 0;">
        <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
        <h3>Apoio à decisão</h3>
        <h4>Primeiras Comunhões</h4>
    </div>

    <div class="no-print">
      <h2> Apoio à decisão</h2>

      <div class="row" style="margin-top:40px; "></div>

      <ul class="nav nav-tabs">
          <li role="presentation"><a href="analisarBaptismos.php">Baptismos</a></li>
          <li role="presentation" class="active"><a href="analisarComunhoes.php">Primeiras Comunhões</a></li>
          <li role="presentation"><a href="analisarCrismas.php">Crismas</a></li>
      </ul>
    </div>

    <div class="row" style="margin-top:20px;"></div>
  
  
  <?php

    $candidatos = array();	//Lista de candidatos ao sacramento
    $excluidos = array();	//Lista de catequizandos que nao podem receber o sacramento
    $concluidos = array();	//Lista de catequizandos que ja receberam o sacramento
    $relatorio = false;		// Indica se existe uma lista de resultados para renderizar

    try
    {
      $db = new PdoDatabaseManager();
      $result = $db->getFirstCommunionAnalysis(Utils::currentCatecheticalYear(), Authenticator::isAdmin(), Authenticator::getUsername());

      if(!empty($result))
      {
          foreach ($result as $row)
          {
              $candidato['catequizando'] = $row;
              $candidato['relatorio'] = array();
              $candidato['relatorio']['info'] = array();
              $candidato['relatorio']['avisos'] = array();
              $candidato['relatorio']['fatais'] = array();

              if (hasPrimeiraComunhao($candidato, RELATORIO::IGNORAR)) {
                  dataComunhaoValida($candidato, RELATORIO::AVISO);
                  paroquiaComunhaoValida($candidato, RELATORIO::AVISO);
                  array_push($concluidos, $candidato);
              } else if (!hasCatecismoMinimoComunhao($candidato, RELATORIO::FATAL) || !hasNumMinimoInscricoes($candidato, RELATORIO::FATAL)) {
                  array_push($excluidos, $candidato);
              } else if (hasBaptismo($candidato, RELATORIO::IGNORAR)) {
                  dataBaptismoValida($candidato, RELATORIO::AVISO);
                  paroquiaBaptismoValida($candidato, RELATORIO::AVISO);
                  hasComprovativoBaptismo($candidato, RELATORIO::AVISO);
                  array_push($candidatos, $candidato);
              } else {
                  hasBaptismo($candidato, RELATORIO::INFO);                // Registar o aviso
                  array_push($candidatos, $candidato);
              }
          }


          //Ordenar os resultados por gravidade dos problemas detetados
          usort($candidatos, "sort_catechumens_by_severity");
          usort($excluidos, "sort_catechumens_by_severity");
          usort($concluidos, "sort_catechumens_by_severity");

          $relatorio = true;
      }
      else
      {
          $relatorio = false;
      }
    }
    catch(Exception $e)
    {
      echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
      die();
    }



    //Renderizar relatorio
    if($relatorio)
    {
        $reportWidget->addCatechumensList("Elegíveis para receber o sacramento", $candidatos, true, "Não tem catequizandos elegíveis para receber o sacramento este ano.");
        $reportWidget->addCatechumensList("Não cumprem os requisitos", $excluidos, false, "Não tem catequizandos impedidos de receber o sacramento este ano.");
        $reportWidget->addCatechumensList("Já receberam o sacramento", $concluidos, false, "Não há catequizandos que já tenham recebido o sacramento.");

        $reportWidget->renderHTML();
    }
    else
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não há dados suficientes para gerar um relatório de apoio à decisão. Por favor tente mais tarde.</div>");
    }


    //Libertar recursos
    $result = null;
?>

</div>


<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>

</body>
</html>