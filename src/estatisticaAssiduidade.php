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
  <title>Estatísticas - Assiduidade</title>
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
	}
	
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
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
  <li role="presentation" class="active"><a href="">Assiduidade</a></li>
  <li role="presentation"><a href="estatisticaPercursosCompletos.php">Percursos catequéticos completos</a></li>
  <li role="presentation"><a href="estatisticaResidentes.php">Catequizandos residentes na paróquia</a></li>
  </ul>
 
  </div>

	<div class="row" style="margin-bottom:60px; "></div>

  <?php
      $db = new PdoDatabaseManager();
      $currentYear = Utils::currentCatecheticalYear();

      try
      {
          $attendanceData = $db->getAttendancePercentageByGroup($currentYear);
          
          if(count($attendanceData) == 0)
          {
              echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a>Ainda não existem sessões de catequese registadas para o ano corrente.</div>");
              die();
          }
      }
      catch(Exception $e)
      {
          echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
          die();
      }
 ?>
 
<div class="panel panel-default">
   <div class="panel-heading">Assiduidade por grupo (ano catequético <?php echo(Utils::formatCatecheticalYear($currentYear)); ?>)</div>
   <div class="panel-body">	
  	<div id="grafico_assiduidade" style="width:100%; height:400px"></div>
  	<div style="margin-bottom: 10px;"></div>
  	<div id="legenda_assiduidade" style="width:100%;"></div>
   </div>
  </div>

<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/flot/jquery.flot.js"></script>
<script src="js/flot/jquery.flot.time.js"></script>
<script src="js/flot/jquery.flot.resize.js"></script>

<script>
<?php
    // Organize data by group
    $groups = [];
    $allDates = [];
    foreach($attendanceData as $row) {
        $groupName = $row['ano_catecismo'] . "º " . $row['turma'];
        $dateStr = $row['data'];
        $timestamp = strtotime($dateStr) * 1000; // Flot expects milliseconds
        $allDates[$timestamp] = true;
        
        $percentage = 0;
        if($row['total_enrolled'] > 0) {
            $percentage = ($row['num_present'] / $row['total_enrolled']) * 100;
        }
        
        if(!isset($groups[$groupName])) {
            $groups[$groupName] = [];
        }
        $groups[$groupName][] = [$timestamp, $percentage, $row['num_present'], $row['total_enrolled']];
    }
    
    // Sort dates for the X axis
    ksort($allDates);
    
    echo "var datasets = {\n";
    $firstGroup = true;
    foreach($groups as $name => $data) {
        if(!$firstGroup) echo ",\n";
        echo "  \"" . $name . "\": { label: \"" . $name . "\", data: " . json_encode($data) . " }";
        $firstGroup = false;
    }
    echo "\n};\n";
?>

var data = [];
$.each(datasets, function(key, val) {
    data.push(val);
});

var options = {
    series: {
        lines: { show: true },
        points: { show: true }
    },
    xaxis: {
        mode: "time",
        timeformat: "%d/%m/%Y",
        timezone: "browser"
    },
    yaxis: {
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
    legend: {
        noColumns: 5,
        container: $('#legenda_assiduidade')
    }
};

var plot = $.plot($("#grafico_assiduidade"), data, options);

$("<div id='tooltip'></div>").css({
    position: "absolute",
    display: "none",
    border: "1px solid #fdd",
    padding: "2px",
    "background-color": "#fee",
    opacity: 0.80
}).appendTo("body");

$("#grafico_assiduidade").bind("plothover", function (event, pos, item) {
    if (item) {
        var x = new Date(item.datapoint[0]),
            y = item.datapoint[1].toFixed(2),
            dataPoint = item.series.data[item.dataIndex],
            present = dataPoint[2],
            total = dataPoint[3];
        
        var dateStr = x.getDate() + "/" + (x.getMonth() + 1) + "/" + x.getFullYear();

        $("#tooltip").html(item.series.label + " em " + dateStr + ": " + present + "/" + total)
            .css({top: item.pageY+5, left: item.pageX+5})
            .fadeIn(200);
    } else {
        $("#tooltip").hide();
    }
});
</script>

</div>
  
<script>
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>

</body>
</html>
