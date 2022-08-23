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
require_once(__DIR__ . "/gui/widgets/SacramentList/SacramentListWidget.php");

use catechesis\OrderCatechumensBy;
use catechesis\PdoDatabaseManager;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\SacramentListWidget;
use catechesis\Authenticator;
use catechesis\UserData;
use catechesis\Utils;
use core\domain\Sacraments;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::SACRAMENTS);
$pageUI->addWidget($menu);
$listingResults = new SacramentListWidget();
$pageUI->addWidget($listingResults);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Listar Profissões de Fé</title>
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


<div class="only-print" style="position: fixed; top: 0;">
	<img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
	<h3>Listagem de Profissões de Fé</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:170px; "></div>



<div class="container" id="contentor">

	<?php

	if(!Authenticator::isAdmin())
	{
		echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
		echo("</div></body></html>");
		die();
	
	}

	?>

 <div class="no-print">
  <h2> Listar catequizandos</h2>
  
  <div class="row" style="margin-top:20px; "></div>
    
  <ul class="nav nav-tabs">
  <li role="presentation"><a href="meusCatequizandos.php">Os meus catequizandos</a></li>
  <li role="presentation"><a href="listarBaptismos.php">Baptismos</a></li>
  <li role="presentation"><a href="listarComunhoes.php">Primeiras Comunhões</a></li>
  <li role="presentation" class="active"><a href="listarProfissoesFe.php">Profissões de Fé</a></li>
  <li role="presentation"><a href="listarConfirmacoes.php">Crismas</a></li>
  <li role="presentation"><a href="listarEscuteiros.php">Escuteiros</a></li>
  </ul>
 
</div>
  

  <div class="well well-lg" style="position:relative; z-index:2;">
  	<form role="form" action="listarProfissoesFe.php" method="post">
  
      <div class="form-group">
        <div class="col-xs-3">
         <label for="ano_civil">Ano: </label>
         <select name="ano_civil" >
             <option value="" <?php if (!$_POST['ano_civil'] || $_POST['ano_civil']=="") echo("selected"); ?>>Todos</option>
	<?php

        $db = new PdoDatabaseManager();

        //Get civil years
        $result = null;
        try
        {
            $result = $db->getSacramentsCivilYears(Sacraments::PROFESSION_OF_FAITH);
        }
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        foreach($result as $row)
        {
            echo("<option value='" . intval($row['ano_civil']) . "'");
            if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['ano_civil']==$row['ano_civil'])
                echo(" selected");
            echo(">");
            echo("" . intval($row['ano_civil']) . "</option>\n");
        }

		$result = null;
	?>
          </select>
        </div>
       </div>
   
       <div class="form-group">
       <div class="col-xs-9">
            <label for="paroquia">Paróquia:</label>
            <select name="paroquia">
                <option value="" <?php if (!$_POST['paroquia'] || $_POST['paroquia']=="") echo("selected"); ?>>Todas</option>
	<?php

        $result = null;
        try
        {
            $result = $db->getDistinctParishes(Sacraments::PROFESSION_OF_FAITH);
        }
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        foreach($result as $row)
        {
            echo("<option value='" . Utils::sanitizeOutput($row['paroquia']) . "'");
            if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['paroquia']==$row['paroquia'])
                echo(" selected");
            echo(">");
            echo("" . Utils::sanitizeOutput($row['paroquia']) . "</option>\n");
        }

		$result = null;
	    ?>
            </select>
        </div>
       </div>
   
   
   <div class="form-group">
    <div class="col-xs-8">
    <div class="row" style="margin-top:20px; "></div>
    	<label for="ordernar_por">Ordenar por: </label>
    	<label class="radio-inline"><input type="radio" name="ordenar_por" value="Nome" 
    		<?php if (!$_POST['ordenar_por'] || $_POST['ordenar_por']=="" || $_POST['ordenar_por']=="Nome") echo("checked"); ?>>Nome</label>
	    <label class="radio-inline"><input type="radio" name="ordenar_por" value="Data"
		<?php if ($_POST['ordenar_por']=="Data") echo("checked"); ?>>Data</label>
     </div>
    </div>
   
    
    <div class="col-xs-2">
    	<label for="botao"> <br></label>
    	<div>
    		<button type="submit" class="btn btn-primary glyphicon glyphicon-th-list no-print"> Listar</button> 
    	</div>	
    </div>
   
    <div class="clearfix"></div>
    </form>
  
  </div>

  <?php

	$ano_civil = NULL;
	$paroquia = NULL;
	$ordem = NULL;
	
	if ($_SERVER["REQUEST_METHOD"] == "POST") 
	{
		if($_POST['ano_civil'])
			$ano_civil = intval($_POST['ano_civil']);
		if($_POST['paroquia'])
			$paroquia = Utils::sanitizeInput($_POST['paroquia']);
		$ordem = Utils::sanitizeInput($_POST['ordenar_por']);
		  	
		if($ano_civil && $ano_civil < 1000)	//Tem de ser da forma '2015', logo, com 4 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano civil é inválido.</div>");
		}
		else if(!$ordem || ($ordem!="Nome" && $ordem!="Data"))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O tipo de ordenação escolhido é inválido.</div>");
		}


        // Get profession of faith listing
        $result = null;
        try
        {
            $result = $db->getCatechumensBySacrament(Sacraments::PROFESSION_OF_FAITH, $ano_civil, $paroquia,
                ($ordem == "Nome") ? OrderCatechumensBy::NAME_BIRTHDATE : OrderCatechumensBy::SACRAMENT_DATE);
        }
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        $listingResults->setCatechumensList($result);
        $listingResults->setEntitiesName("Profissão de Fé", "Profissões de Fé");

        $listingResults->renderHTML();
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