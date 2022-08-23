<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . "/gui/widgets/CatechumensList/CatechumensListWidget.php");

use catechesis\OrderCatechumensBy;
use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\CatechumensListWidget;
use catechesis\SacramentFilter;
use catechesis\UserData;
use catechesis\Utils;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHUMENS);
$pageUI->addWidget($menu);
$searchResults = new CatechumensListWidget();
$pageUI->addWidget($searchResults);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Listar escuteiros</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">

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

    <style>
        /** Override this property from DataTables to properly hide the catechumen "attributes" column **/
        table.dataTable
        {
            border-collapse: collapse !important;
        }
    </style>
</head>
<body>


<?php
$menu->renderHTML();
?>




<div class="only-print" style="position: fixed; top: 0;">
	<img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
	<h3>Listagem de escuteiros</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:170px; "></div>

<div class="container" id="contentor">

<div class="no-print">

  <h2> Listar catequizandos</h2>

  <div class="row" style="margin-top:20px; "></div>
    
  <ul class="nav nav-tabs">
      <li role="presentation"><a href="meusCatequizandos.php">Os meus catequizandos</a></li>
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
      <li role="presentation" class="active"><a href="listarEscuteiros.php">Escuteiros</a></li>
  </ul>
 
</div>

    <div class="row" style="margin-top:20px;"></div>

    <?php
    if(!Authenticator::isAdmin())
    {
        ?>
    <div class="alert alert-warning">
        <a href="#" class="close" data-dismiss="alert">&times;</a><strong>Nota:</strong> A mostrar apenas os escuteiros que pertencem aos seus grupos de catequese.
    </div>
        <?php
    }
    ?>

    <?php

    $db = new PdoDatabaseManager();

    //Get scouts
    $result = null;
    try
    {
        if(Authenticator::isAdmin())
        {
            $result = $db->getScouts(Utils::currentCatecheticalYear());
        }
        else
        {
            // If the user is not an administrator, show only the scouts belonging to him
            $result = $db->getCatechumensByCatechistWithFilters(Utils::currentCatecheticalYear(), Utils::currentCatecheticalYear(),
                                                                Authenticator::getUsername(), OrderCatechumensBy::NAME_BIRTHDATE,
                                                        SacramentFilter::IRRELEVANT, SacramentFilter::IRRELEVANT,
                                                                array(), true );
        }
    }
    catch (Exception $e)
    {
      echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
      die();
    }

    $searchResults->setCatechumensList($result);
    $searchResults->setEntitiesName("escuteiro");
    $searchResults->renderHTML();

    ?>
</div>



<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>

</body>
</html>