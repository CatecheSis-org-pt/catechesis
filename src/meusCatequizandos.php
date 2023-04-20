<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . "/gui/widgets/CatechumensList/CatechumensListWidget.php");

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\CatechumensListWidget;
use catechesis\UserData;
use catechesis\Utils;


$username = Authenticator::getUsername();
$db = new PdoDatabaseManager();


//Get group(s) where the catechist teaches in the current catechetical year
$result = null;
$error_msg = null;
try
{
    $result = $db->getCatechistGroups($username, Utils::currentCatecheticalYear());
}
catch(Exception $e)
{
    $error_msg = $e->getMessage();
}



// Create the widgets manager
$pageUI = new WidgetManager();

//Add necessary styles (in case there are no catechumens and no widget is added)
$pageUI->addCSSDependency("css/bootstrap.min.css")
        ->addCSSDependency("font-awesome/fontawesome-free-5.15.1-web/css/all.min.css");

$menu = new MainNavbar(null, MENU_OPTION::CATECHUMENS);
$pageUI->addWidget($menu);

// Instantiate the widgets used in this page and register them in the manager
$catechumensListWidgets = array();
if($result && count($result) > 0)
{
    foreach ($result as $row)
    {
        // Instantiate a catechumens list widget per group
        $searchResults = new CatechumensListWidget();
        $searchResults->setEntitiesName("catequizando");
        $pageUI->addWidget($searchResults);
        $catechumensListWidgets[] = $searchResults;
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Os meus catequizandos</title>
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




<div class="only-print" style="position: fixed; top: 0;">
	<img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
	<h3>Listagem de catequizandos</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:170px; "></div>


<div class="container" id="contentor">

 <div class="no-print">
  <h2> Listar catequizandos</h2>

  <div class="row" style="margin-top:20px; "></div>
    
  <ul class="nav nav-tabs">
    <li role="presentation" class="active"><a href="meusCatequizandos.php">Os meus catequizandos</a></li>
  <?php
  if(Authenticator::isAdmin())
  {
	?>
      <li role="presentation"><a href="listarBaptismos.php">Baptismos</a></li>
      <li role="presentation"><a href="listarComunhoes.php">Primeiras Comunhões</a></li>
      <li role="presentation"><a href="listarProfissoesFe.php">Profissões de Fé</a></li>
      <li role="presentation"><a href="listarConfirmacoes.php">Crismas</a></li>
  <?php
  }
  ?>
      <li role="presentation"><a href="listarEscuteiros.php">Escuteiros</a></li>
  </ul>
 
  </div>

  <?php

    // Check if errors occurred during query
    if(isset($error_msg))
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $error_msg . "</div>");
        die();
    }

	if($result && count($result) > 0)
	{
		$contador = 0;
		foreach($result as $row)
		{
			$contador++;

			if($contador>1)
				echo('<div class="row" style="margin-bottom:150px;"></div>');
			
			//Cabecalho com ano catequetico, catecismo e turma
            ?>
            <div class="well well-lg" style="position:relative; z-index:2;">
                <div class="form-group">
                    <div class="col-xs-6">
                        <label for="ano_catequetico">Ano catequético: </label>
                        <span><?= Utils::formatCatecheticalYear($row['ano_lectivo']); ?></span>
                    </div>
                    <div class="col-xs-3">
                        <label for="catecismo">Catecismo: </label>
                        <span><?= $row['ano_catecismo'] ?>º</span>
                    </div>
                    <div class="col-xs-3">
                        <label for="turma">Grupo: </label>
                        <span><?= Utils::sanitizeOutput($row['turma']) ?></span>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>

            <form id="form_imprime_presencas<?= $contador ?>" action="folhasPresencas.php" method="post">
                <input type="hidden" name="ano_catequetico" value="<?= $row['ano_lectivo'] ?>">
                <input type="hidden" name="catecismo" value="<?= $row['ano_catecismo'] ?>">
                <input type="hidden" name="turma" value="<?= Utils::sanitizeOutput($row['turma']) ?>">
            </form>
			
			
			<?php

			//Listagem dos catequizandos
            $result2 = $db->getCatechumensByCatechismWithFilters($row['ano_lectivo'], $row['ano_lectivo'], $row['ano_catecismo'], Utils::sanitizeOutput($row['turma']), false);
			
			if(count($result2) >= 1)
			{
                $catechumensListWidgets[$contador-1]->setCatechumensList($result2);
                $catechumensListWidgets[$contador-1]->addButtonsToToolbar("
                    <button type=\"button\" onclick=\"imprime_presencas($contador)\" class=\"btn btn-default no-print\"><span class=\"fas fa-stamp\"></span> Ir para a área de impressão</button>
                    <button type=\"button\" onclick=\"window.open('aproveitamento.php');\" class=\"btn btn-default no-print\"><span class=\"fas fa-user-check\"></span> Registar aproveitamento </button>
                ");
                $catechumensListWidgets[$contador-1]->renderHTML();

                //Libertar recursos
                $result2 = null;
			}
			else
			{
			?>
				<div class="row" style="margin-top:20px; "></div>
				  <div class="page-header">
				    <h1><small><span id="numero_resultados"></span>Sem catequizandos</small></h1>
				  </div>
				  
				  <div class="row" style="margin-top:20px;"></div>
			<?php
			}
		}
	}
	else
	{
		echo("<div class=\"well well-lg\">\n");
		echo("<p>Não tem catequizandos neste ano catequético.</p>\n");
	}
	

	//Libertar recursos
	$result = null;
?>
</div>


<?php
//Add necessary scripts (in case there are no catechumens and no widget is added)
$pageUI->addJSDependency("js/jquery.min.js")
        ->addJSDependency("js/bootstrap.min.js");

$pageUI->renderJS(); // Render the widgets' JS code
?>

<script>
function imprime_presencas(num)
{
	document.getElementById("form_imprime_presencas".concat(num.toString())).submit();
}
</script>


</body>
</html>