<?php
require_once(__DIR__ . "/core/config/catechesis_config.inc.php");
require_once(__DIR__ . "/authentication/utils/authentication_verify.php");
require_once(__DIR__ . "/core/Utils.php");
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/DataValidationUtils.php");
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . "/gui/widgets/CatechumensList/CatechumensListWidget.php");
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');

use catechesis\DataValidationUtils;
use catechesis\PdoDatabaseManager;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\CatechumensListWidget;
use catechesis\gui\ModalDialogWidget;
use catechesis\UserData;
use catechesis\Utils;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHUMENS);
$pageUI->addWidget($menu);
$longSearchWarning = new ModalDialogWidget("pesquisaLonga");
$pageUI->addWidget($longSearchWarning);
$searchResults = new CatechumensListWidget();
$pageUI->addWidget($searchResults);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Pesquisar catequizandos</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">
  
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
	<h3>Pesquisa de catequizandos</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:150px; "></div>


<div class="container" id="contentor">
	
  <h2 class="no-print"> Pesquisar catequizandos</h2>
  
  <div class="no-print">
      <div class="row" style="margin-top:20px; "></div>
      <ul class="nav nav-tabs">
          <li role="presentation" class="active"><a href="">Por nome / data nascimento</a></li>
          <li role="presentation"><a href="pesquisarAno.php">Por ano / catecismo</a></li>
          <li role="presentation"><a href="pesquisarCatequista.php">Por catequista</a></li>
      </ul>
  </div>
  

  <div class="well well-lg" style="position:relative; z-index:2;">
  	<form role="form" onsubmit="return validar();" action="pesquisarNome.php" method="post" id="form_pesquisa">
  
        <!--nome-->
        <div class="form-group">
            <div class="col-xs-6">
              <label for="nome">Nome:</label>
              <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome do catequizando" onchange="verifica_catequizando_inscrito()">
            </div>
        </div>

        <!--data nascimento-->
        <div class="form-group">
            <div class="col-xs-2">
                <div class="input-append date" id="data_nasc_div" data-date="" data-date-format="dd-mm-yyyy">
                  <label for="data_nasc">Nasceu a:</label>
                  <input class="form-control" id="data_nasc" name="data_nasc" size="16" type="text" onclick="verifica_data_nasc()" onchange="verifica_data_nasc()" placeholder="dd-mm-aaaa">
                  <span id="erro_nasc_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                </div>
            </div>
        </div>

        <div class="col-xs-4 no-print">
            <label for="botao"> <br></label>
            <div>
                <button type="submit" class="btn btn-primary glyphicon glyphicon-search"> Pesquisar</button>
            </div>
        </div>

        <div class="clearfix"></div>
	 
    </form>
  </div>
  
  
  <?php

	$nome_orig = NULL;
	$nome = NULL;
	$data_nasc = NULL;

    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
		$nome_orig = $_POST['nome'];
		$nome = Utils::sanitizeInput($_POST['nome']);
		$data_nasc = Utils::sanitizeInput($_POST['data_nasc']);
	
		if(!DataValidationUtils::validateDate($data_nasc) && $data_nasc && $data_nasc!="")
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data de nascimento que introduziu é inválida. Deve ser da forma dd-mm-aaaa.</div>");
	  		die();	  	
	  	}

		$result = null;
		try
        {
            $db = new PdoDatabaseManager();
            $result = $db->findCatechumensByNameAndBirthdate($nome, $data_nasc, Utils::currentCatecheticalYear());
        }
		catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        $searchResults->setCatechumensList($result);
        $searchResults->renderHTML();

        //Libertar recursos
        $result = null;
	}
?>

</div>



<?php

// Dialog to warn about long search

$longSearchWarning->setTitle("Aviso");

$longSearchWarning->setBodyContents(<<<HTML_CODE
        <p>Não especificou nenhum nome nem data de nascimento. A pesquisa irá retornar todos os catequizandos existentes na base de dados.</p>
        <p>Tem a certeza de que pretende continuar com esta pesquisa?</p>
HTML_CODE
);

$longSearchWarning->addButton(new Button("Não", ButtonType::SECONDARY))
                  ->addButton(new Button("Sim", ButtonType::WARNING, "pesquisar()"));

$longSearchWarning->renderHTML();
?>



<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>
<script src="js/form-validation-utils.js"></script>
<script src="js/rowlink.js"></script>

<script>
function pesquisar()
{
	document.getElementById("form_pesquisa").submit();
}
</script>


<script>
	document.getElementById('nome').value = "<?php echo('' . $nome_orig . ''); ?>";
	document.getElementById('data_nasc').value = "<?php echo('' . $data_nasc . ''); ?>";
</script>


<script>
$(function(){
   $('#data_nasc').datepicker({
       format: "dd-mm-yyyy",
       defaultViewDate: { year: 2010, month: 1, day: 1 },
       startView: 2,
       language: "pt",
       autoclose: true
    });
});
</script>

<script>

function verifica_data_nasc()
{
	var data_nasc = document.getElementById('data_nasc').value;
	
	if(!data_valida(data_nasc) && data_nasc!="" && data_nasc!=undefined)
	{ 
		$('#data_nasc_div').addClass('has-error');
		$('#data_nasc_div').addClass('has-feedback');
		$('#erro_nasc_icon').show(); 
		return false;
	} else {
	 	$('#data_nasc_div').removeClass('has-error');
		$('#data_nasc_div').removeClass('has-feedback');
		$('#erro_nasc_icon').hide();  
		return true;
	}
}


function validar()
{
	var data_nasc = document.getElementById('data_nasc').value;
	var nome = document.getElementById('nome').value;
	
	if(!data_valida(data_nasc) && data_nasc!=="" && data_nasc!==undefined)
    {
        alert("A data de nascimento que introduziu é inválida. Deve ser da forma dd-mm-aaaa.");
        return false;
    }

    if((data_nasc==="" || data_nasc===undefined) && (nome==="" || nome===undefined))
    {
        $('#pesquisaLonga').modal('show');
        return false;
    }

    return true;
}
</script>


<!-- Definir foco automaticamente no campo de pesquisa -->
<script>
    $(document).ready(function(){
            $('#nome').focus();
            })
</script>



</body>
</html>