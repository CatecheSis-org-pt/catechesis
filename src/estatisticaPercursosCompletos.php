<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\PdoDatabaseManager;
use catechesis\Utils;
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
  <li role="presentation" class="active"><a href="">Percursos catequéticos completos</a></li>
  <li role="presentation"><a href="estatisticaResidentes.php">Catequizandos residentes na paróquia</a></li>
  </ul>
 
  </div>

	<div class="row" style="margin-bottom:60px; "></div>

  
  <?php

      $db = new PdoDatabaseManager();
      $ano_lec_i=0;
      $ano_lec_f=0;

      //Verificar que existem dados suficientes para a estatistica, e obter intervalo de anos catequeticos para a estatistica
      try
      {
          if(!$db->isDataSufficientForCompleteCatecheticalJourneyStatistic(Utils::currentCatecheticalYear()))
          {
              echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a>Ainda não existem dados suficientes na base de dados para gerar estas estatísticas. Por favor volte a tentar mais tarde.</div>");
              die();
          }

          $catecheticalYearsRange = $db->getCatecheticalYearsRangeForCatechumens();
          $ano_lec_i = $catecheticalYearsRange['first'];
          $ano_lec_f = $catecheticalYearsRange['last'];
      }
      catch(Exception $e)
      {
          echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
          die();
      }
 ?>
 
<div class="panel panel-default">
   <div class="panel-heading">Evolução no tempo (frequência absoluta)</div>
   <div class="panel-body">	
  	<div id="grafico1" style="width:100%; height:300px"></div>
   </div>
  </div>

<div class="panel panel-default">
   <div class="panel-heading">Evolução no tempo (percentagem face ao ideal esperado)</div>
   <div class="panel-body">	
  	<div id="grafico2" style="width:100%; height:300px"></div>
  	
  	<div style="margin-bottom: 20px;"></div>
  	<a style="cursor: help;" class="no-print" data-toggle="tooltip" data-placement="top" title="O ideal esperado corresponde ao número máximo de catequizandos que poderiam terminar o 10º catecismo e receber o sacramento da Confirmação. Para mais info, consulte Ajuda > Manual do utilizador."><span class="glyphicon glyphicon-question-sign"></span> Como é calculada esta percentagem?</a>
   </div>
  </div>



<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/rowlink.js"></script>
<script src="js/flot/jquery.flot.js"></script>
<script src="js/flot/jquery.flot.navigate.js"></script>
<script src="js/flot/jquery.flot.resize.js"></script>

<script>
<?php
	//Obter contagem de percursos catequeticos completos por ano catequetico
	try
    {
		$result = $db->getCompleteCatecheticalJourneysByCatecheticalYear(Utils::currentCatecheticalYear(), false);

        $data_labels = "";
        $count_max = 0;
        $last_ano_lec = $ano_lec_i;

        $data_count=0;
        $x_count=0;
        echo("var d1 = [");
        foreach($result as $row)
        {

            while($last_ano_lec < $row['ano_lectivo'] && $last_ano_lec < Utils::currentCatecheticalYear())
            {
                if($x_count>0)
                    echo(", ");

                echo("[" . $x_count . ",0]");
                $x_count++;
                $last_ano_lec += 10001;
            }

            if($x_count>0)
                echo(", ");
            echo("[" . $x_count . ", " . $row['completos'] . "]");
            if($row['completos']>$count_max)
                $count_max = $row['completos'];
            $x_count++;
            $last_ano_lec += 10001;

        }

        while($last_ano_lec < Utils::currentCatecheticalYear())
        {
            if($x_count>0)
                echo(", ");

            echo("[" . $x_count . ",0]");
            $x_count++;
            $last_ano_lec += 10001;
        }

        echo("];\n");

?>




var plot = $.plot($("#grafico1"), [ { label: "percursos catequéticos completos (frequência absoluta)", data: d1} ], {
			series: {
				lines: { show: true },
				points: { show: true }
			},
			xaxis: {
				ticks: [
					<?php
						$ano_lec_aux = $ano_lec_i;
						$ticks_count = 0;
						while($ano_lec_aux < Utils::currentCatecheticalYear())
						{
							if($ticks_count > 0)
								echo(", ");
								
							echo("[" . $ticks_count . ", \"" . Utils::formatCatecheticalYear($ano_lec_aux) . "\"]");
							$ticks_count++;
							$ano_lec_aux += 10001;
						}
					
					?>
				]
			},
			yaxis: {
				ticks: 10,
				min: 0,
				max: <?php echo($count_max); ?>,
				tickDecimals: 0
			},
			grid: {
				backgroundColor: { colors: [ "#fff", "#eee" ] },
				borderWidth: {
					top: 1,
					right: 1,
					bottom: 2,
					left: 2
				},
				hoverable: true,
				clickable: true
			},
			zoom: {
				interactive: true
			},
			pan: {
				interactive: true
			}			
			});
			
			
$("<div id='tooltip'></div>").css({
			position: "absolute",
			display: "none",
			border: "1px solid #fdd",
			padding: "2px",
			"background-color": "#fee",
			opacity: 0.80
		}).appendTo("body");


$("#grafico1").bind("plothover", function (event, pos, item) {

	
	var str = "(" + pos.x.toFixed(2) + ", " + pos.y.toFixed(2) + ")";
	$("#hoverdata").text(str);
	
	if (item) {
		var x = item.datapoint[0].toFixed(2),
			y = item.datapoint[1].toFixed(0);

		$("#tooltip").html(item.series.label + ": " + y)
			.css({top: item.pageY+5, left: item.pageX+5})
			.fadeIn(200);
	} else {
		$("#tooltip").hide();
	}
	
});

<?php
	}
    catch (Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }



    //Obter percentagem de percursos catequeticos completos por ano catequetico
	try
    {
		$result = $db->getCompleteCatecheticalJourneysByCatecheticalYear(Utils::currentCatecheticalYear(), true);

        $data_labels = "";
        $count_max = 0;
        $last_ano_lec = $ano_lec_i;

        $data_count=0;
        $x_count=0;
        echo("var d2 = [");
        foreach($result as $row)
        {

            while($last_ano_lec < $row['ano_lectivo'] && $last_ano_lec < Utils::currentCatecheticalYear())
            {
                if($x_count>0)
                    echo(", ");

                echo("[" . $x_count . ",0]");
                $x_count++;
                $last_ano_lec += 10001;
            }

            if($x_count>0)
                echo(", ");
            echo("[" . $x_count . ", " . $row['percentagem'] . "]");
            if($row['percentagem'] > $count_max)
                $count_max = $row['percentagem'];
            $x_count++;
            $last_ano_lec += 10001;

        }

        while($last_ano_lec < Utils::currentCatecheticalYear())
        {
            if($x_count>0)
                echo(", ");

            echo("[" . $x_count . ",0]");
            $x_count++;
            $last_ano_lec += 10001;
        }

        echo("];\n");
	?>
	
	
	var plot = $.plot($("#grafico2"), [ { label: "percursos catequéticos completos (percentagem)", data: d2} ], {
			series: {
				lines: { show: true },
				points: { show: true }
			},
			xaxis: {
				ticks: [
					<?php
						$ano_lec_aux = $ano_lec_i;
						$ticks_count = 0;
						while($ano_lec_aux < Utils::currentCatecheticalYear())
						{
							if($ticks_count > 0)
								echo(", ");
								
							echo("[" . $ticks_count . ", \"" . Utils::formatCatecheticalYear($ano_lec_aux) . "\"]");
							$ticks_count++;
							$ano_lec_aux += 10001;
						}
					
					?>
				]
			},
			yaxis: {
				ticks: 20,
				min: 0,
				max: 100,
				tickDecimals: 0
			},
			grid: {
				backgroundColor: { colors: [ "#fff", "#eee" ] },
				borderWidth: {
					top: 1,
					right: 1,
					bottom: 2,
					left: 2
				},
				hoverable: true,
				clickable: true
			},
			zoom: {
				interactive: true
			},
			pan: {
				interactive: true
			}			
			});
			
			
$("<div id='tooltip2'></div>").css({
			position: "absolute",
			display: "none",
			border: "1px solid #fdd",
			padding: "2px",
			"background-color": "#fee",
			opacity: 0.80
		}).appendTo("body");


$("#grafico2").bind("plothover", function (event, pos, item) {

	
	var str = "(" + pos.x.toFixed(2) + ", " + pos.y.toFixed(2) + ")";
	$("#hoverdata").text(str);
	
	if (item) {
		var x = item.datapoint[0].toFixed(2),
			y = item.datapoint[1].toFixed(2);

		$("#tooltip2").html(item.series.label + ": " + y)
			.css({top: item.pageY+5, left: item.pageX+5})
			.fadeIn(200);
	} else {
		$("#tooltip2").hide();
	}
	
});

	
	
<?php
	}
	catch (Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }
		    
		    
	//Libertar recursos
	$result = null;
?>
		
</script>

  
</div>
  
<script>
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})

$(function () {
  $('[data-toggle="tooltip2"]').tooltip()
})
</script>

</body>
</html>