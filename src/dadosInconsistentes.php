<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/core/decision_support.php');
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
$menu = new MainNavbar(null, MENU_OPTION::ANALYSIS);
$pageUI->addWidget($menu);
$reportWidget = new CatechumensReportWidget();
$pageUI->addWidget($reportWidget);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Dados inconsistentes</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">

  <style>
  	@media print
	{    
	    .no-print, .no-print * {
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
	    .label-success {
            color: white !important;
            background-color: #5cb85c !important;
	    }
	    /*.label-default
	    {
	      color: white !important;
	      background-color: #777 !important;
	    }*/

	    .panel-default > .panel-heading {
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
        <h3>Dados inconsistentes</h3>
    </div>

    <div class="no-print">
      <h2>Dados inconsistentes</h2>
      <div class="row" style="margin-top:40px; "></div>
    </div>
  

    <?php

    $db = new PdoDatabaseManager();

    if(Authenticator::isAdmin())
    {

    ?>
	<!-- Selector de ano, catecismo e turma -->
    <div class="well well-lg" style="position:relative; z-index:2;">
  	    <form role="form" action="dadosInconsistentes.php" method="post">
  
            <div class="form-group">
                <div class="col-xs-5">
                    <label for="ano_catequetico">Ano catequético: </label>
                    <select name="ano_catequetico" >
                        <option value="" <?php if (!$_POST['ano_catequetico'] || $_POST['ano_catequetico']=="") echo("selected"); ?>>Todos</option>
                        <?php

                        //Obter anos lectivos
                        $result = null;
                        try
                        {
                            $result = $db->getCatecheticalYears();
                        }
                        catch(Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                            die();
                        }

                        foreach($result as $row)
                        {
                            echo("<option value='" . $row['ano_lectivo'] . "'");
                            if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['ano_catequetico']==$row['ano_lectivo'])
                                echo(" selected");
                            echo(">");
                            echo(Utils::formatCatecheticalYear($row['ano_lectivo']) . "</option>\n");
                        }

                        $result = null;
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <div class="col-xs-4">
                    <label for="catecismo">Catecismo:</label>
                    <select name="catecismo">
                        <option value="" <?php if (!$_POST['catecismo'] || $_POST['catecismo']=="") echo("selected"); ?>>Todos</option>
                        <?php

                        //Obter anos de catequese
                        $result = NULL;
                        try
                        {
                            $ano_atual = Utils::currentCatecheticalYear();
                            $result = $db->getCatechisms($ano_atual);
                        }
                        catch(Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                            die();
                        }

                        if (isset($result) && count($result)>=1)
                        {
                            foreach($result as $row)
                            {
                                echo("<option value='" . $row['ano_catecismo'] . "'");
                                if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['catecismo']==$row['ano_catecismo'])
                                    echo(" selected");
                                echo(">");
                                echo("" . $row['ano_catecismo'] . "º" . "</option>\n");
                            }
                        }

                        $result = null;
                        ?>
                    </select>
                </div>
            </div>


            <div class="form-group">
                <div class="col-xs-3">
                <label for="turma">Grupo:</label>
                <select name="turma">
                    <option value="" <?php if (!$_POST['catecismo'] || $_POST['catecismo']=="") echo("selected"); ?>>Todas</option>
                    <?php

                    //Obter turmas de catequese
                    $result = NULL;
                    try
                    {
                    $ano_atual = Utils::currentCatecheticalYear();
                    $result = $db->getGroupLetters($ano_atual);
                    }
                    catch(Exception $e)
                    {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                    }

                    if(isset($result) && count($result)>=1)
                    {
                    foreach($result as $row)
                    {
                        echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['turma']==$row['turma'])
                            echo(" selected");
                        echo(">");
                        echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                    }
                    }

                    $result = null;
                    ?>
                </select>
                </div>
            </div>

            <div class="col-xs-3">
                <label for="botao"> <br></label>
                <div>
                    <button type="submit" class="btn btn-primary no-print"> <span class="glyphicon glyphicon-search no-print"></span> Gerar relatório</button>
                </div>
            </div>
            <div class="clearfix"></div>

        </form>
    </div>
    <?php 		//--Selector de ano, catecismo e turma
    }
    ?>
  


  <?php

	$ano_catequetico = NULL;
	$catecismo = NULL;
	$turma = NULL;

	if($_SERVER["REQUEST_METHOD"] == "POST")
	{
		$ano_catequetico = intval($_POST['ano_catequetico']);
		$catecismo = intval($_POST['catecismo']);
		$turma = Utils::sanitizeInput($_POST['turma']);
	}

    list($problemas, $sem_problemas, $relatorio) = runDecisionSupportAnalysis(Authenticator::getUsername(), Authenticator::isAdmin(), $ano_catequetico, $catecismo, $turma);


    //Renderizar relatorio
	if($relatorio)
	{
        $reportWidget->addCatechumensList("Catequizandos com dados inconsistentes", $problemas, true, "Não há catequizandos com dados inconsistentes.");
        $reportWidget->addCatechumensList("Catequizandos sem problemas", $sem_problemas, false, "Não há catequizandos sem dados inconsistentes.");

        $reportWidget->renderHTML();
	}
	else
	{
		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não há dados suficientes para gerar um relatório. Por favor tente mais tarde.</div>");
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