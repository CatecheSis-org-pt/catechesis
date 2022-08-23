<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\PdoDatabaseManager;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;

// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::ANALYSIS);
$pageUI->addWidget($menu);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Estatísticas</title>
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


<div class="row only-print" style="margin-bottom:170px; "></div>


<div class="container" id="contentor">


  <h2> Estatísticas</h2>
  
  <div class="row" style="margin-bottom:40px; "></div>

<div class="no-print">    
  <div class="row" style="margin-top:20px; "></div>
  
  <ul class="nav nav-tabs">
  <li role="presentation"><a href="estatisticaNumCatequizandos.php">Número de catequizandos por catequista</a></li>
  <li role="presentation"><a href="estatisticaDesistencias.php">Desistências</a></li>
  <li role="presentation"><a href="estatisticaPercursosCompletos.php">Percursos catequéticos completos</a></li>
  <li role="presentation" class="active"><a href="">Catequizandos residentes na paróquia</a></li>
  </ul>
 
  </div>

	<div class="row" style="margin-bottom:60px; "></div>
  
  
  <?php

    $db = new PdoDatabaseManager();

	//Verificar que existem dados suficientes para a estatistica
    try
    {
        if(!$db->isDataSufficientForResidentsStatistic())
        {
            echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a>Ainda não existem dados suficientes na base de dados para gerar estas estatísticas. Por favor volte a tentar mais tarde.</div>");
        }
        else
        {
            ?>
            <div class="panel panel-default">
                <div class="panel-heading">Percentagem de catequizandos residentes na paróquia</div>
                <div class="panel-body">
                    <div id="grafico1" style="width:100%; height:300px"></div>
                </div>
            </div>
        <?php
        }
    }
    catch(Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }


 ?>


<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/rowlink.js"></script>
<link rel="stylesheet" href="js/morris.js-0.5.1/morris.css">
<script src="js/morris.js-0.5.1/raphael-min.js"></script>
<script src="js/morris.js-0.5.1/morris.min.js"></script>

<?php
if($db->isDataSufficientForResidentsStatistic())
{
?>
    <script>
    Morris.Donut({
          element: 'grafico1',
          data: [

    <?php

          //Obter percentagem de catequizandos residentes na paroquia
          try
          {
              $residents = $db->getResidentCatechumensPercentage();
              echo("{value: " . round($residents) . ", label: 'Residentes'},\n");
              echo("{value: " . round((100-$residents)) . ", label: 'Não residentes'}\n");
          }
          catch(Exception $e)
          {
              echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
              die();
          }
    ?>
          ],
          formatter: function (x) { return x + "%"}
        }).on('click', function(i, row){
          console.log(i, row);
        });

    </script>
<?php
}
?>

</body>
</html>