<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
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
  <li role="presentation" class="active"><a href="">Número de catequizandos por catequista</a></li>
  <li role="presentation"><a href="estatisticaDesistencias.php">Desistências</a></li>
  <li role="presentation"><a href="estatisticaPercursosCompletos.php">Percursos catequéticos completos</a></li>
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
          if(!$db->isDataSufficientForNumberOfCatechumensByCatechist())
          {
              echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a>Ainda não existem dados suficientes na base de dados para gerar estas estatísticas. Por favor volte a tentar mais tarde.</div>");
              die();
          }

          $catecheticalYearsRange = $db->getCatecheticalYearsRangeForCatechumensAndCatechists();
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
   <div class="panel-heading">Evolução no tempo</div>
   <div class="panel-body">	
  	<div id="grafico1" style="width:100%; height:300px"></div>
  	<div style="margin-bottom: 10px;"></div>
  	<div id="legenda1" style="width:100%;"></div>
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

	try
    {
        //Obter contagem de catequizandos por catequista, por ano catequetico
		$result = $db->getCatechumensByCatechistAndYear(false);

        $data_labels = "";
        $count_max = 0;

        $last_username=NULL;
        $last_ano_lec=$ano_lec_i;
        $data_count=0;
        $x_count=0;
        foreach($result as $row)
        {
            if($row['username']!=$last_username)
            {
                if($last_username!=NULL)
                {
                    while($last_ano_lec <= $ano_lec_f)
                    {
                        if($x_count>0)
                            echo(", ");

                        echo("[" . $x_count . ",0]");
                        $x_count++;
                        $last_ano_lec += 10001;
                    }


                    echo("];\n");
                    $data_labels .= ", ";

                }

                echo("var d" . intval($data_count+1) . " = [");
                $data_labels .= "{ label: \"" . Utils::sanitizeOutput($row['nome']) . "\", data: d" . intval($data_count+1) . "}";
                $last_username = Utils::sanitizeOutput($row['username']);
                $data_count++;
                $x_count=0;
                $last_ano_lec=$ano_lec_i;
            }

            while($last_ano_lec < $row['ano_lectivo'] && $last_ano_lec < $ano_lec_f)
            {
                if($x_count>0)
                    echo(", ");

                echo("[" . $x_count . ",0]");
                $x_count++;
                $last_ano_lec += 10001;
            }

            if($x_count>0)
                echo(", ");
            echo("[" . $x_count . ", " . $row['contagem'] . "]");
            if($row['contagem']>$count_max)
                $count_max = $row['contagem'];
            $x_count++;
            $last_ano_lec += 10001;

        }

        if($data_count>0)
        {
            while($last_ano_lec <= $ano_lec_f)
            {
                if($x_count>0)
                    echo(", ");

                echo("[" . $x_count . ",0]");
                $x_count++;
                $last_ano_lec += 10001;
            }

            echo("];\n");
        }
?>

var plot = $.plot($("#grafico1"), [ <?php echo($data_labels); ?> ], {
			series: {
				lines: { show: true },
				points: { show: true }
			},
			xaxis: {
				ticks: [
					<?php
						$ano_lec_aux = $ano_lec_i;
						$ticks_count = 0;
						while($ano_lec_aux <= $ano_lec_f)
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
			},
			legend: {
				noColumns: 5,
				container: $('#legenda1')
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
		
</script>

<div class="panel panel-default">
   <div class="panel-heading">Totais (de <?php echo(Utils::formatCatecheticalYear($ano_lec_i)); ?> a <?php echo(Utils::formatCatecheticalYear($ano_lec_f)); ?>)</div>
   <div class="panel-body">	
  	<table class="table table-striped table-hover">
  		<tr>
  			<th>Catequista</th>
  			<th><span data-toggle="tooltip" data-placement="top" title="Somatório do número de catequizandos de todos os grupos em que deu catequese. Pode contabilizar catequizandos repetidos, se se mantiverem durante vários anos.">Total de catequizandos</span></th>
  			<th><span data-toggle="tooltip" data-placement="top" title="Cada catequizando só é contabilizado 1 vez, se for catequizando deste catequista por vários anos.">Total de catequizandos distintos</span></th>
  		</tr>
  		
<?php
        //Contagem acumulada de catequizandos por catequista
        $result = $db->getCatechumensByCatechistAndYear(true);

        foreach($result as $row)
        {
            echo("<tr>\n");
            echo("<td>" . Utils::sanitizeOutput($row['nome']) . "</td>\n");
            echo("<td>" . $row['nao distintos'] . "</td>\n");
            echo("<td>" . $row['distintos'] . "</td>\n");
            echo("</tr>\n");
        }
?>
  	</table>
   </div>
  </div>
  
</div>

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

  
<script>
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>

</body>
</html>