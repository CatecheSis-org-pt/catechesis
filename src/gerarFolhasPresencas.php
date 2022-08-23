<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . "/gui/widgets/Navbar/MainNavbar.php");

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;

// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
$pageUI->addWidget($menu);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Reprografia</title>
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
	    
	    /*@page {
		    size: 297mm 210mm;*/ /* landscape */
		    /* you can also specify margins here: */
		    /*margin: 35mm;*/
		    /*margin-right: 45mm;*/ /* for compatibility with both A4 and Letter */
		 /* }*/
		 
		 
		.nao-quebrar
		{
			page-break-inside: avoid;
		}
	}
	
	.table td {
	   text-align: center;  
	}
  </style>
</head>
<body>


<?php

$menu->renderHTML();


$db = new PdoDatabaseManager();


//Get catechist groups
$catechistGroups = null;
try
{
    $catechistGroups = $db->getCatechistGroups(Authenticator::getUsername(), Utils::currentCatecheticalYear());
}
catch(Exception $e)
{
}

?>



<div class="container">

	<h2> Reprografia</h2>

	<div class="row" style="margin-bottom:20px; "></div>
	
	<div class="well well-lg">
  	<form role="form" action="folhasPresencas.php" method="post">
  
  <div class="form-group">
    <div class="col-xs-3">
 	 <label for="ano_catequetico">Ano catequético: </label> 
 	 <select name="ano_catequetico" required>
    		<option value="" disabled <?php if ((!$_POST['ano_catequetico'] || $_POST['ano_catequetico']=="") && !(isset($catechistGroups) && count($catechistGroups) >= 1)) echo("selected"); ?>></option>
	<?php

        //Get catechetical years
        $result = null;
        try
        {
            $result = $db->getCatecheticalYears();
        }
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        foreach($result as $row)
        {
            echo("<option value='" . $row['ano_lectivo'] . "'");
            if ($_SERVER["REQUEST_METHOD"] == "POST")
            {
                if ($_POST['ano_catequetico'] == $row['ano_lectivo'])
                    echo(" selected");
            }
            else if(isset($catechistGroups) && count($catechistGroups) >= 1 && $catechistGroups[0]["ano_lectivo"]==$row['ano_lectivo'])
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
   	<div class="col-xs-3">
   		<label for="catecismo">Catecismo:</label>
		<select name="catecismo" required>
			<option value="" disabled <?php if((!$_POST['catecismo'] || $_POST['catecismo']=="") && !(isset($catechistGroups) && count($catechistGroups) >= 1)) echo("selected"); ?>></option>
	<?php

        //Get catechisms
        try
        {
            $result = $db->getCatechisms();
        } catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

		if (isset($result) && count($result)>=1)
		{
			foreach($result as $row)
			{
				echo("<option value='" . $row['ano_catecismo'] . "'");
				if ($_SERVER["REQUEST_METHOD"] == "POST")
                {
                    if ($_POST['catecismo'] == $row['ano_catecismo'])
                        echo(" selected");
                }
                else if(isset($catechistGroups) && count($catechistGroups) >= 1 && $catechistGroups[0]["ano_catecismo"]==$row['ano_catecismo'])
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
   	<div class="col-xs-2">
   		<label for="turma">Grupo:</label>
		<select name="turma" required>
			<option value="" disabled <?php if((!$_POST['catecismo'] || $_POST['catecismo']=="") && !(isset($catechistGroups) && count($catechistGroups) >= 1)) echo("selected"); ?>></option>
	<?php

        //Get distinct catechesis group letters
        try
        {
            $result = $db->getGroupLetters();
        } catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }


        if (isset($result) && count($result)>=1)
		{
			foreach($result as $row)
			{
				echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
				if ($_SERVER["REQUEST_METHOD"] == "POST")
                {
                    if ($_POST['turma'] == $row['turma'])
                        echo(" selected");
                }
                else if(isset($catechistGroups) && count($catechistGroups) >= 1 && $catechistGroups[0]["turma"]==$row['turma'])
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
  

    <div class="col-xs-4">
    	<label for="botao"> <br></label>
    	<div>
    		<button type="submit" class="btn btn-primary glyphicon glyphicon-file"> Gerar</button> 
    	</div>	
    </div>
    
    
    <div class="clearfix"></div>
	 
    </form>
  
  </div>

</div>


<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
</body>
</html>