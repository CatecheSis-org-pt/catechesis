<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
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
  <title>Registos de actividade do sistema</title>
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

	<?php

	if(!Authenticator::isAdmin())
	{
		echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
		echo("</div></body></html>");
		die();
	
	}

	?>
	
  <h2> Registos de actividade do sistema</h2>
  
   
  <div class="row" style="margin-bottom:40px; "></div>
  
  
  <ul class="nav nav-tabs">
  <li role="presentation" class="active"><a href="logCatechesis.php">Actividade no CatecheSis</a></li>
  <li role="presentation"><a href="logAutenticacoes.php">Autenticações</a></li>
  </ul>
  
  
  <div class="row" style="margin-bottom:20px; "></div>
  
  <table class="table table-hover" id="actividade">
  <thead>
	<tr>
		<th>Data / hora</th>
		<th>Utilizador</th>
		<th>Acção</th>
	</tr>
  </thead>
  <tbody>
  
  <?php

	//Funcao para criar link de retorno em caso de erro





    $db = new PdoDatabaseManager();

    $result = null;
    try
    {
  	    $result = $db->getCatechesisLog();
	}
	catch(PDOException $e)
    {
		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
		die();
	}
	
	
	if(isset($result) && count($result)>=1)
	{
		foreach($result as $row)
		{
			echo("<tr>\n");
			
			echo("\t<td>" . date( "d-m-Y", strtotime($row['data'])) . "&nbsp;&nbsp;&nbsp;" . $row['hora'] . "</td>\n");
			echo("\t<td>" . Utils::sanitizeOutput($row['username']) . "</td>\n");
			echo("\t<td>" . $row['accao'] . "</td>\n");		
			
			echo("</tr>\n");
			
		}
	}
	
	
	//Libertar recursos
	$result = NULL;
	
   ?>
  </tbody>
  </table>
</div>

<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/rowlink.js"></script>

</body>
</html>