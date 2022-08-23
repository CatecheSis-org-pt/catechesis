<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/log_functions.php'); //Para poder escrever no log
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/catechist_belongings.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\UserData;
use catechesis\Utils;
use core\domain\Sacraments;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;

// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::SACRAMENTS);
$pageUI->addWidget($menu);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Registar sacramentos</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">
  <link rel="stylesheet" href="css/bootstrap-switch.css">
  
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

      .form-group {
          margin-bottom: 0; /* Hack to align first row of form select boxes */
      }
  </style>
</head>
<body>

<?php
$menu->renderHTML();
?>



<div class="only-print" style="position: fixed; top: 0;">
	<img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
	<h3>Registar sacramentos</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:170px; "></div>


<div class="container" id="contentor">

 <div class="no-print">
  <h2> Registar sacramentos</h2>
  
  <div class="row" style="margin-bottom:40px; "></div>
</div>
  
  


<?php

$db = new PdoDatabaseManager();
$username = Authenticator::getUsername();

//Get catechist groups
$catechistGroups = null;
try
{
    $catechistGroups = $db->getCatechistGroups(Authenticator::getUsername(), Utils::currentCatecheticalYear());
}
catch(Exception $e)
{
}


// Init variables with default values
$ano_precedente = Utils::currentCatecheticalYear();
$catecismo_precedente = 1;
$turma_precedente = 'A';


if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="escolher")
{
	$ano_precedente = intval(Utils::sanitizeInput($_POST['ano_catequetico_prec']));
	$catecismo_precedente = intval(Utils::sanitizeInput($_POST['catecismo_prec']));
	$turma_precedente = Utils::sanitizeInput($_POST['turma_prec']);
	$sacramento = Sacraments::sacramentFromString(Utils::sanitizeInput($_POST['sacramento']));
	$data_sacramento = Utils::sanitizeInput($_POST['data_sacramento']);
}




//Guardar alteracoes
if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="guardar" )
{
 	$catequizandos_registam = $_POST['catequizando'];	//Lista de cid de catequizandos que registam o sacramento
	$ano_prec = intval(Utils::sanitizeInput($_POST['ano_prec']));
	$cat_prec = intval(Utils::sanitizeInput($_POST['cat_prec']));
	$turma_prec = Utils::sanitizeInput($_POST['t_prec']);
	$sacramento = Sacraments::sacramentFromString(Utils::sanitizeInput($_POST['sacramento']));
	$data_sacramento = Utils::sanitizeInput($_POST['data_sacramento']);
    $username_catequista = Authenticator::getUsername();

    if(!Authenticator::isAdmin() && !group_belongs_to_catechist($ano_prec, $cat_prec, $turma_prec, $username))
    {
        echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para registar sacramentos neste grupo.</div>");
        echo("</div></body></html>");
        die();
    }

					
    //Obter listagem dos catequizandos para registar sacramento
    $result = NULL;
    try
    {
        $result = $db->getCatechumensByCatechismWithFilters($ano_prec, $ano_prec, $cat_prec, $turma_prec, true);

        if(count($result) >= 1)
        {
            foreach($result as $row)
            {
                $cid = $row['cid'];

                //Renovar matricula
                if(in_array($cid, $catequizandos_registam))
                {
                    if($db->insertSacramentRecord($cid, $sacramento, $data_sacramento, Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME)))
                    {
                        catechumenArchiveLog($cid, "Registo de " . Sacraments::toExternalString($sacramento) . " do catequizando com id=" . $cid . ".");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao registar " . Sacraments::toExternalString($sacramento) . " do catequizando com cid=" . $cid . ".</div>");

;
                    }
                }
            }

            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Registos de sacramento efetuados!</div>");
        }
        else
        {
            //Sem catequizandos
        }
    }
    catch (Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }



	
	//Inicializar variaveis com os valores do ultimo pedido
	$ano_precedente = $ano_prec;
	$catecismo_precedente = $cat_prec;
	$turma_precedente = $turma_prec;
}

?>



<!-- ESCOLHER ANO E CATECISMO -->

<form role="form" id="form_registo_sacramento" action="registarSacramentos.php" method="post">
<div class="well well-lg" style="position:relative; z-index:2;">
  
  <div class="form-group">
    <div class="col-xs-4">
 	 <label for="ano_catequetico">Ano catequético: </label>
 	 <select name="ano_catequetico_prec" onchange="this.form.submit()">
  
	<?php

        //Get catechetical years
        $result = NULL;
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
                if ($ano_precedente == $row['ano_lectivo'])
                    echo(" selected");
            }
            else if(isset($catechistGroups) && count($catechistGroups) >= 1 && $catechistGroups[0]["ano_lectivo"]==$row['ano_lectivo'])
                echo(" selected");
            else if (Utils::currentCatecheticalYear() == $row['ano_lectivo'])
                echo(" selected");
            echo(">");
            echo(Utils::formatCatecheticalYear($row['ano_lectivo']) . "</option>\n");
        }

	?>
	  </select>
	</div>
   </div>
   
   	<div class="form-group">
   	<div class="col-xs-3">
   		<label for="catecismo">Catecismo:</label>
		<select name="catecismo_prec" onchange="this.form.submit()">
			
	<?php

        //Get catechisms
        $result = null;
		try
        {
            $result = $db->getCatechisms();
		}
        catch (Exception $e)
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
                    if ($catecismo_precedente == $row['ano_catecismo'])
                        echo(" selected");
                }
                else if(isset($catechistGroups) && count($catechistGroups) >= 1 && $catechistGroups[0]["ano_catecismo"]==$row['ano_catecismo'])
                    echo(" selected");
				echo(">");
				echo("" . $row['ano_catecismo'] . "º" . "</option>\n"); 
			}
		}
	?>
		</select>
	</div>
   </div>
   
   
   
   <div class="form-group">
   	<div class="col-xs-3">
   		<label for="turma">Grupo:</label>
		<select name="turma_prec" onchange="this.form.submit()">
			
	<?php

        //Get distinct catechesis group letters
        $result = null;
		try
        {
            $result = $db->getGroupLetters();
		}
        catch (Exception $e)
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
                    if ($turma_precedente == $row['turma'])
                        echo(" selected");
                }
                else if(isset($catechistGroups) && count($catechistGroups) >= 1 && $catechistGroups[0]["turma"]==$row['turma'])
                    echo(" selected");
				echo(">");
				echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
			}
		}
		   				
	?>
		</select>
	</div>
   </div>

   <div class="clearfix"></div>

  


<!-- //ESCOLHER ANO E CATECISMO -->

<?php
	
	//Inicializar as variaveis quando nao houve POST
	if($_SERVER["REQUEST_METHOD"] != "POST")
	{
        if(isset($catechistGroups) && count($catechistGroups) >= 1)
        {
            $ano_precedente = $catechistGroups[0]["ano_lectivo"];
            $catecismo_precedente = $catechistGroups[0]["ano_catecismo"];
            $turma_precedente = $catechistGroups[0]["turma"];
            $sacramento = Sacraments::BAPTISM;
        }
        else
        {
            $ano_precedente = Utils::currentCatecheticalYear();
            $catecismo_precedente = 1;
            $turma_precedente = 'A';
            $sacramento = Sacraments::BAPTISM;
        }
	}

?>


  <!-- Escolher destino das matriculas -->
  
  <input type="hidden" name="ano_prec" value=<?php echo('"' . $ano_precedente . '"');  ?> />
  <input type="hidden" name="cat_prec" value=<?php echo('"' . $catecismo_precedente . '"');  ?> />
  <input type="hidden" name="t_prec" value=<?php echo('"' . $turma_precedente . '"');  ?> />
  <input type="hidden" name="op" id="input_op" value="escolher"/>
  
	<div class="form-group">

	<div class="clearfix"></div>
	<div class="row" style="margin-top:60px; "></div>
	
	<div class="col-xs-6">
	 <label for="sacramento">Sacramento: </label>
	 <select name="sacramento" onchange="this.form.submit()"> 
	 	<option value="<?= Sacraments::toInternalString(Sacraments::BAPTISM)?>"     <?php if(!is_null($sacramento) && $sacramento==Sacraments::BAPTISM) echo('selected'); ?>     >Batismo</option>
	 	<option value="<?= Sacraments::toInternalString(Sacraments::FIRST_COMMUNION)?>"  <?php if(!is_null($sacramento) && $sacramento==Sacraments::FIRST_COMMUNION) echo('selected'); ?>  >Eucaristia (Primeira Comunhão)</option>
	 	<option value="<?= Sacraments::toInternalString(Sacraments::PROFESSION_OF_FAITH)?>" <?php if(!is_null($sacramento) && $sacramento==Sacraments::PROFESSION_OF_FAITH) echo('selected'); ?> >Profissão de Fé</option>
	 	<option value="<?= Sacraments::toInternalString(Sacraments::CHRISMATION)?>" <?php if(!is_null($sacramento) && $sacramento==Sacraments::CHRISMATION) echo('selected'); ?> >Confirmação (Crisma)</option>
	 </select>
	</div>

	<div class="clearfix"></div>
	<div class="row" style="margin-top:20px; "></div>

 	<div class="form-group">
	<div class="col-xs-4">
	 <label for="turma">Data: </label>
	 <div class="input-append date" id="data_sacramento_div" data-date="" data-date-format="dd-mm-yyyy">
	 	<input class="form-control" id="data_sacramento" name="data_sacramento" value="<?php if($data_sacramento!='') echo($data_sacramento); ?>" size="16" type="text" placeholder="dd-mm-aaaa" style="cursor: auto;" onclick="verifica_data_sacramento()" onchange="verifica_data_sacramento()" required>
	 	<span id="erro_data_sacramento_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
	 </div>
    </div>

    <div class="col-xs-6">
	 <label for="catecismo">Paróquia: </label>
	 <input type="text" class="form-control" id="paroquia_sacramento" name="paroquia_sacramento" value="<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME) ?>"  style="cursor: auto;" readonly>
	</div>
	</div>

	<div class="clearfix"></div>

 </div>
</div>

<?php
if(Authenticator::isAdmin() || group_belongs_to_catechist($ano_precedente, $catecismo_precedente, $turma_precedente, $username))
{
?>

 <div class="no-print">  
  <div class="btn-group" role="group" aria-label="...">
  	<button type="button" class="btn btn-default glyphicon glyphicon-floppy-disk" onclick="guardar();"> Guardar</button>
  	<button type="button" class="btn btn-default glyphicon glyphicon-print" onclick="window.print()"> Imprimir</button>
  </div>
  </div>  


  <div class="row" style="margin-top:20px; "></div>
  
  <div> 
  
 <?php

    //Listagem dos catequizandos
    $result2 = NULL;
    try
    {
        $result2 = $db->getCatechumensWithAndWithoutSacramentByCatechismAndGroup($sacramento, $ano_precedente, $catecismo_precedente, $turma_precedente);
    }
    catch (Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }

    if(isset($result2) && count($result2)>=1)
    {
            ?>
                <div class="row" style="margin-top:20px; "></div>
                  <div class="page-header" style="position:relative; z-index:2;">
                    <h1><small><span class="numero_resultados"></span> catequizandos</small></h1>
                  </div>
                  <div class="row" style="margin-top:20px; "></div>


                          <div class="no-print">
                          <div class="col-xs-12">
                          <table class="table table-hover">
                          <thead>
                            <tr>
                                <th><input type="checkbox" class="checkbox-geral"> <span> Todos </span></th>
                            </tr>
                          </thead>
                         </table>
                        </div>
                        </div>

                  <div class="row" style="margin-top:20px; "></div>


                  <!-- Resultados -->
                  <div class="only-print" style="margin-top: -150px; position:relative; z-index:1;"></div>
                  <div class="col-xs-12" style="page-break-after: always;">
                        <table class="table table-hover resultados">
                          <thead>
                            <tr>
                                <th style="background-color: transparent;">
                                    <div class="only-print" style="opacity:0;">
                                <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
                                <h3>Aproveitamento dos catequizandos</h3>
                                <div class="row" style="margin-bottom:0px; "></div>
                                </div>
                            Sacramento</th>
                                <th>Nome</th>
                                <th>Data nascimento</th>
                                <th>Aproveitamento</th>
                            </tr>
                          </thead>
                          <tfoot class="only-print">
                            <tr>
                                <td colspan="4"><?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER); ?></td>
                            </tr>
                          </tfoot>
                          <tbody data-link="row" class="rowlink">

            <?php

                foreach($result2 as $row2)
                {
                    $foto = Utils::sanitizeOutput($row2['foto']);
                    $passa = intval($row2['passa']);
                    $cid = intval($row2['cid']);
                    $registado = intval($row2['registado']); 	//1 se o catequizando ja tem o sacramento registado, 0 caso contrario

                    if($registado!=1)
                        echo("<tr class='default'>\n");
                    else
                        echo("<tr class='active'>\n");

                        if($registado!=1)
                            echo("<td class='rowlink-skip'><input type=\"checkbox\" class=\"my-checkbox\" name=\"catequizando[]\" value='$cid' ></td>");
                        else
                            echo("<td><i> Registado </i></td>");


                        echo("\t<td ");
                            echo("data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" data-content=\"<img src='");
                                if($foto && $foto!="")
                                    echo("resources/catechumenPhoto.php?foto_name=$foto");
                                else
                                    echo("img/default-user-icon-profile.png");
                            echo("' style='height:133px; widht:100px;'>\"");
                            echo("><a href=\"mostrarFicha.php?cid=" . intval($row2['cid']) . "\" target=\"_blank\"></a>" . Utils::sanitizeOutput($row2['nome']) . "</td>\n");
                        echo("\t<td>" . date( "d-m-Y", strtotime($row2['data_nasc'])) . "</td>\n");

                        if($passa!=-1)
                            echo('<td><span class="label label-success">Transita</span></td>');
                        else
                            echo('<td><span class="label label-danger">Reprovado</span></td>');
                    echo("</tr>\n");

                }
                ?>

                </tbody>
                </table>
              </div>
              <!--<div class="row" style="margin-bottom:150px;"></div>-->

    <?php
    } //--if(isset($result2) && count($result2)>=1)
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
 else
 {
     ?>
     <div class="row" style="margin-top:20px;"></div>
     <div class="alert alert-danger"><strong>Erro!</strong> Não tem permissões para registar sacramentos neste grupo.</div>
     <?php
 }
?>
  

  <div class="clearfix" style="margin-bottom: 40px"></div>
  
   
</div>
</form>


<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>
<script src="js/rowlink.js"></script>
<script src="js/bootstrap-switch.js"></script>

<script>
	
	var els = document.getElementsByClassName("resultados");
	var els2 = document.getElementsByClassName("numero_resultados");
	var i = 0;
	

	Array.prototype.forEach.call(els, function(el) {
	    
	    var numero = el.getElementsByTagName("tbody")[0].getElementsByTagName("tr").length;
	    els2[i].innerHTML = numero;
	    
	    i++;
	});

</script>





<script>
$(function () {
  $('[data-toggle="popover"]').popover({ trigger: "hover", 
                                          html: true,
                                          /*content: function () {
                                            return '<img src="'+$(this).data('img') + '" />';
                                          },*/
                                          delay: { "show": 500, "hide": 100 }
                                        });
})
</script>





<script>
function mudaSwitch(linha, state)
{
	
	if(state)
	{
		linha.className = "active";
	}
	else
	{
		linha.className = "default";
	}

	
	
}

</script>



<script>
$(function () {
	$("[class='my-checkbox']").bootstrapSwitch({size: 'mini',
												onText: 'Regista',
												offText: '&nbsp;',
												onColor: 'success',
												offColor: 'default'
												});
});

$('input[class="my-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {

    mudaSwitch(this.closest('tr'), state);
});

</script>


<script>
$(function () {
	$("[class='checkbox-geral']").bootstrapSwitch({size: 'mini',
												onText: 'Regista',
												offText: '&nbsp;',
												onColor: 'success',
												offColor: 'default'
												});
});

$('input[class="checkbox-geral"]').on('switchChange.bootstrapSwitch', function(event, state) {
  	$('input[class="my-checkbox"]').bootstrapSwitch('state', state, false);
});

</script>



<script>

function data_valida(data)
{
	var pattern = /^[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}$/;
	
	return (pattern.test(data));

}

function verifica_data_sacramento()
{
	var data_bap = document.getElementById('data_sacramento').value;
	
	if(!data_valida(data_bap) && data_bap!="" && data_bap!=undefined)
	{ 
		$('#data_sacramento_div').addClass('has-error');
		$('#data_sacramento_div').addClass('has-feedback');
		$('#erro_data_sacramento_icon').show();  
		return false;
	} else {
	 	$('#data_sacramento_div').removeClass('has-error');
		$('#data_sacramento_div').removeClass('has-feedback');
		$('#erro_data_sacramento_icon').hide();  
		return true;
	}
}


function guardar()
{
	document.getElementById('input_op').value = "guardar";
    data_sacramento = document.getElementById('data_sacramento').value;
    if(data_sacramento=="")
        alert("Por favor preencha o campo data.");
    else
	    document.getElementById('form_registo_sacramento').submit();
}

</script>


<script>
	$('#data_sacramento').datepicker({
        format: "dd-mm-yyyy",
        defaultViewDate: { year: <?= date("Y") ?>, month: 1, day: 1 },
        startView: 2,
        language: "pt",
        autoclose: true
	   	 });
</script>

</body>
</html>