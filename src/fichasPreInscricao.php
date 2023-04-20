<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Configurator;
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
  <title>Fichas de pré-inscrição</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-switch.css">
  
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

	.panel.ficha {
		    margin-bottom: 20px;
		    background-color: #FFF;
		    border: 1px solid ;					//Painel do bootstrap com traco mais grosso
		    border-radius: 4px;
		    box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.05);
		}

	.breadcrumb.nomeFicha {
	    padding: 3px 0px 0px 15px;
	    margin-bottom: 0px;
	    list-style: outside none none;
	    background-color: #F5F5F5 !important;
	    border-radius: 4px;
	}


  </style>
</head>
<body>

<?php
$menu->renderHTML();
?>


<div class="container">

  <div class="no-print">
  
  <h2> Área de Impressão</h2>

  <div class="row" style="margin-top:20px; "></div>
  <div class="row" style="margin-top:20px; "></div>
  
  <form id="form_presencas" action="folhasPresencas.php" method="post">
  	<input type="hidden" name="ano_catequetico" value="<?php if($_POST['ano_catequetico']) echo('' . $_POST['ano_catequetico'] . ''); ?>">
  	<input type="hidden" name="catecismo" value="<?php if($_POST['catecismo']) echo('' . $_POST['catecismo'] . ''); ?>">
  	<input type="hidden" name="turma" value="<?php if($_POST['turma']) echo('' . $_POST['turma'] . ''); ?>">
  </form>


    <form id="form_fichas" action="fichasCatequizandos.php" method="post">
  	<input type="hidden" name="ano_catequetico" value="<?php if($_POST['ano_catequetico']) echo('' . $_POST['ano_catequetico'] . ''); ?>">
  	<input type="hidden" name="catecismo" value="<?php if($_POST['catecismo']) echo('' . $_POST['catecismo'] . ''); ?>">
  	<input type="hidden" name="turma" value="<?php if($_POST['turma']) echo('' . $_POST['turma'] . ''); ?>">
  </form>
    
  <ul class="nav nav-tabs">
  <li role="presentation" style="cursor: pointer;"><a onclick="document.getElementById('form_presencas').submit();">Folha de presenças</a></li>
  <li role="presentation" style="cursor: pointer;"><a onclick="document.getElementById('form_fichas').submit();">Fichas dos catequizandos</a></li>
  <li role="presentation" style="cursor: pointer;" class="active"><a >Pré-inscrições</a></li>
  </ul>
  


  <div class="row" style="margin-top:20px; "></div>
  <div class="well well-lg" style="position:relative; z-index:2;">

  	<div class="col-xs-3">
	  	<div class="btn-group" role="group" aria-label="...">
	  		<button type="button" onclick="" class="btn btn-default dropdown-toggle no-print" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-download-alt"></span> Transferir <span class="caret"></span></button>
	  		<ul class="dropdown-menu">
		    	<li><a href="#" onclick="document.getElementById('form_preInscricoes').submit();"><img src="img/word_icon.png" style="width: 10%; height: 10%;"> Como Microsoft Word 2007-2016 (.docx) <span style="margin-right: 20px;"></span></a></li>
		    	<!--<li><a href="#" onclick="document.getElementById('form_preInscricoes').submit();"><img src="imagens/pdf_icon.png" style="width: 10%; height: 10%;"> Como PDF (.pdf) <span style="margin-right: 20px;"></span></a></li>-->
		    </ul>
		</div>
	</div>
	<div class="clearfix"></div>
  </div>

  <div class="row" style="margin-bottom:20px; "></div>
   </div>
</div>  


	<form id="form_preInscricoes" role="form" action="gerarFichasPreInscricaoWord.php" method="post">

 <?php

	//Funcao para criar link de retorno em caso de erro


	



	
	


	//Carregar variaveis por POST
	if ($_SERVER["REQUEST_METHOD"] == "POST") 
	{
		$ano_catequetico = intval($_POST['ano_catequetico']);
		$catecismo = intval($_POST['catecismo']);
		$turma = Utils::sanitizeInput($_POST['turma']);


		if($ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Impossível gerar folhas de presenças.</div>");
			

;
		}
		else if($catecismo <= 0 || $catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Impossível gerar folhas de presenças.</div>");
			

;
		}
		else
		{
			if(!Authenticator::isAdmin() && !group_belongs_to_catechist($ano_catequetico, $catecismo, $turma, Authenticator::getUsername()))
			{
				echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não tem permissões para gerar as fichas dos catequizandos deste grupo de catequese (" . $catecismo . "º$turma).</div>");
				echo("</body></html>");
				die();
			}


			//Obter cid dos catequizandos
            $result = null;
			try
            {
			    $db = new PdoDatabaseManager();
				$result = $db->getCatechumensByCatechismWithFilters($ano_catequetico, $ano_catequetico, $catecismo, $turma, false);
				     	    
			}catch(Exception $e){
				//echo $e->getMessage();
				echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
				die();
			}
			
			
			
			if (count($result) >= 1)
			{
				foreach($result as $row)
				{
					$cid = intval($row['cid']);
									
                    //Preencher formulario
                    echo("<input type='hidden' name='cids[]' value=" . $cid . ">");
				}

				echo("<input type='hidden' name='catecismo' value=" . $catecismo . ">");
				echo("<input type='hidden' name='turma' value=" . $turma . ">");
 			}
 			else
 				echo("<div class=\"container\"><p>Não há catequizandos inscritos neste grupo de catequese</p></div>");
		}
	}
	
	
	//Libertar recursos
	$result = null;
?>
	</form>




<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/bootstrap-switch.js"></script>

<script>
$(function () {
	$("[class='my-checkbox']").bootstrapSwitch({size: 'small',
												onText: 'Modificação',
												offText: 'Alfabética',
												onColor: '',
												offColor: ''
												});
});

/*$('input[class="my-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {

    mudaSwitch(this.closest('tr'), state);
});*/

</script>

</body>
</html>