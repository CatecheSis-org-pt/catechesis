<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . "/gui/widgets/configuration_panels/CatechumensEvaluationActivationPanel/CatechumensEvaluationActivationPanelWidget.php");
require_once(__DIR__ . '/core/log_functions.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\CatechumensEvaluationActivationPanelWidget;
use catechesis\UserData;
use catechesis\Utils;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
$pageUI->addWidget($menu);
$evaluationPeriodPanel = new CatechumensEvaluationActivationPanelWidget();
$pageUI->addWidget($evaluationPeriodPanel);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Aproveitamento</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); ?>
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

// Handle the POST request to open/close catechumens evaluation period
$evaluationPeriodPanel->handlePost();
?>

<div class="only-print" style="position: fixed; top: 0;">
    <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
	<h3>Aproveitamento dos catequizandos</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:170px; "></div>


<div class="container" id="contentor">

 <div class="no-print">
  <h2> Aproveitamento dos catequizandos</h2>
  
  <div class="row" style="margin-bottom:40px; "></div>




<?php

	$db = new PdoDatabaseManager();


	//Verificar se o periodo de avaliacao esta activo
    $periodo_activo = false;
    try
    {
        $periodo_activo = Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHUMENS_EVALUATION);
    }
    catch(Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
    }



    //Guardar alteracoes
    if($_REQUEST['op']=="guardar" && $periodo_activo)
    {
        $catequizandos_passam = $_POST['catequizando'];	//Lista de cid de catequizandos que passam

        //Obter turma onde actualmente da catequese
        $result = null;
        try
        {
            $result = $db->getCatechistGroups(Authenticator::getUsername(), Utils::currentCatecheticalYear());

            if($result && count($result) > 0)
            {
                foreach($result as $row)
                {
                    //Listagem dos catequizandos
                    $result2 = $db->getCatechumensByCatechismWithFilters($row['ano_lectivo'], $row['ano_lectivo'], $row['ano_catecismo'], Utils::sanitizeOutput($row['turma']), true);

                    if(count($result2) >= 1)
                    {
                        foreach($result2 as $row2)
                        {
                            $passa = intval($row2['passa']);
                            $cid = intval($row2['cid']);
                            $nome = Utils::sanitizeOutput($row2['nome']);

                            $decisao = NULL;
                            if($passa==NULL || $passa=="")
                                $passa = 1;

                            if(in_array($cid, $catequizandos_passam))
                                $decisao = 1;								//Passa
                            else
                                $decisao = -1;								//Reprova


                            //Ha alteracoes a guardar para este catequizando
                            if($passa!=$decisao)
                            {
                                $db->updateCatechumenAchievement($cid, $row['ano_lectivo'], $row['ano_catecismo'], Utils::sanitizeOutput($row['turma']), $decisao);

                                $log_string = "Catequizando $nome (cid=" . $cid . ")";
                                if($decisao==-1)
                                    $log_string .= " reprovado ";
                                else
                                    $log_string .= " transita ";
                                $log_string .= " no catecismo " . $row['ano_catecismo'] . "º" . Utils::sanitizeOutput($row['turma']) . " do ano catequético de " . intval($row['ano_lectivo'] / 10000) . "/" .  intval($row['ano_lectivo'] % 10000) . ".";

                                catechumenArchiveLog($cid, $log_string);
                            }
                        }
                    }
                    else
                    {
                        //Sem catequizandos
                    }
                }
            }
            else
            {
                //Nao tem catequizandos neste ano catequetico
            }
        }
        catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Dados actualizados com sucesso.</div>");


        //Libertar recursos
        $result = null;
    }



//Apresentar painel de administracao, caso o utilizador seja administrador
if(Authenticator::isAdmin())
{
    $evaluationPeriodPanel->renderHTML();
?>
  	<div class="row" style="margin-bottom:20px; "></div>
<?php
}
?>
 
  <div class="no-print">  
  <div class="btn-group" role="group" aria-label="...">
  	<button type="button" class="btn btn-primary glyphicon glyphicon-floppy-disk" onclick="document.getElementById('form_aproveitamento').submit();" <?php if(!$periodo_activo) echo("disabled"); ?>> Guardar</button>
  	<button type="button" class="btn btn-default glyphicon glyphicon-print" onclick="window.print()"> Imprimir</button>
  </div>
  </div>  
</div>

  <div class="row" style="margin-top:60px; "></div>
  
  <div>

      <form role="form" id="form_aproveitamento" name="form_aproveitamento" onsubmit="" action="aproveitamento.php?op=guardar" method="post">

  <?php

  $contadorGrupos = 0; //Conta o numero de grupos deste catequista

  $result = null;
    try
    {
        //Obter turma onde actualmente da catequese
        $result = $db->getCatechistGroups(Authenticator::getUsername(), Utils::currentCatecheticalYear());

        if($result && count($result) > 0)
        {
            foreach($result as $row)
            {
                $contadorGrupos++;
                if($contadorGrupos>1)
                    echo('<div class="row" style="margin-bottom:150px;"></div>');

                //Cabecalho com ano catequetico, catecismo e turma
                echo("<div class=\"well well-lg\" style=\"position:relative; z-index:2;\">\n");

                  echo("\t<div class=\"form-group\">\n");
                   echo("\t <div class=\"col-xs-6\">\n");
                    echo("\t <label for=\"ano_catequetico\">Ano catequético: </label>\n");
                    echo("\t\t<span>" . Utils::formatCatecheticalYear($row['ano_lectivo']) . "</span>\n");
                    echo("\t</div>\n\n");

                    echo("\t <div class=\"col-xs-3\">\n");
                    echo("\t <label for=\"catecismo\">Catecismo: </label>\n");
                    echo("\t\t<span>" . $row['ano_catecismo'] . "º</span>\n");
                    echo("\t</div>\n\n");

                    echo("\t<div class=\"col-xs-3\">\n");
                    echo("\t <label for=\"turma\">Grupo: </label>\n");
                    echo("\t\t<span>" . Utils::sanitizeOutput($row['turma']) . "</span>\n");
                   echo("\t </div>\n");
                   echo("</div>\n\n");


                  echo("<div class=\"clearfix\"></div>\n");
                  echo("</div>\n");



                //Listagem dos catequizandos
                $result2 = $db->getCatechumensByCatechismWithFilters($row['ano_lectivo'], $row['ano_lectivo'], $row['ano_catecismo'], Utils::sanitizeOutput($row['turma']), true);

                if(count($result2) >= 1)
                {
                ?>
                    <div class="row" style="margin-top:20px; "></div>
                      <div class="page-header" style="position:relative; z-index:2;">
                        <h1><small><span class="numero_resultados"></span> catequizandos</small></h1>
                      </div>
                      <div class="row" style="margin-top:20px; "></div>

                      <?php
                        if($periodo_activo)
                        {
                        ?>
                              <div class="no-print">
                              <div class="col-xs-12">
                              <table class="table table-hover">
                              <thead>
                                <tr>
                                    <th><input type="checkbox" class="checkbox-geral-<?=$contadorGrupos?>" checked> <span> Todos </span></th>
                                </tr>
                              </thead>
                             </table>
                            </div>
                            </div>
                    <?php
                        }
                        else
                        {
                            echo("<div class=\"no-print alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a> O período de avaliação encontra-se encerrado. </div>");
                        }
                    ?>

                      <div class="row" style="margin-top:20px; "></div>

				  <!-- Resultados -->
				  <div class="only-print" style="margin-top: -100px; position:relative; z-index:1;"></div>
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
							Aproveitamento</th>
								<th>Nome</th>
				    			<th>Data nascimento</th>
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

                        echo("<tr class='"); if($passa==-1) echo("danger"); else echo("success"); echo("'>\n");
                            if($periodo_activo)
                            {	echo("<td><input type=\"checkbox\" class=\"my-checkbox-$contadorGrupos\" name=\"catequizando[]\" value='$cid' "); if($passa!=-1) echo("checked"); echo("></td>"); }
                            else if($passa!=-1)
                                echo('<td><span class="label label-success">Transita</span></td>');
                            else
                                echo('<td><span class="label label-danger">Reprovado</span></td>');
                            echo("\t<td ");
                                echo("data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" data-content=\"<img src='");
                                    if($foto && $foto!="")
                                        echo("resources/catechumenPhoto.php?foto_name=$foto");
                                    else
                                        echo("img/default-user-icon-profile.png");
                                echo("' style='height:133px; widht:100px;'>\"");
                                echo("><a href=\"mostrarFicha.php?cid=" . $row2['cid'] . "\" target=\"_blank\"></a>" . Utils::sanitizeOutput($row2['nome']) . "</td>\n");
                            echo("\t<td>" . date( "d-m-Y", strtotime($row2['data_nasc'])) . "</td>\n");
                        echo("</tr>\n");
                    }
                    ?>
                    </tbody>
                    </table>
                  </div>
                <?php
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
            //die();
        }
    }
	catch(Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }


	//Libertar recursos
	$result = null;
?>

      </form>

      <div class="clearfix" style="margin-bottom: 40px"></div>

      <div class="no-print">
          <div class="btn-group" role="group" aria-label="...">
              <button type="button" class="btn btn-primary glyphicon glyphicon-floppy-disk" onclick="document.getElementById('form_aproveitamento').submit();" <?php if(!$periodo_activo) echo("disabled"); ?>> Guardar</button>
              <button type="button" class="btn btn-default glyphicon glyphicon-print" onclick="window.print()"> Imprimir</button>
          </div>
      </div>

      <div class="clearfix" style="margin-bottom: 40px"></div>

  </div>



<?php $pageUI->renderJS(); ?>
<script src="js/rowlink.js"></script>

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
		linha.className = "success";
	}
	else
	{
		linha.className = "danger";
	}
}

</script>


<?php

if($periodo_activo)
{
?>

    <script>
    <?php

    // Checkbox geral individual, para cada grupo
    for($i = 1; $i <= $contadorGrupos; $i = $i+1)
    {
       ?>

        $(function () {
            $("[class='my-checkbox-<?= $i ?>']").bootstrapSwitch({size: 'mini',
                onText: 'Transita',
                offText: 'Reprova',
                onColor: 'success',
                offColor: 'danger'
            });
        });

        $('input[class="my-checkbox-<?= $i ?>"]').on('switchChange.bootstrapSwitch', function(event, state) {

            mudaSwitch(this.closest('tr'), state);
        });

        $(function () {
            $("[class='checkbox-geral-<?= $i ?>']").bootstrapSwitch({size: 'mini',
                                                        onText: 'Transita',
                                                        offText: 'Reprova',
                                                        onColor: 'success',
                                                        offColor: 'danger'
                                                        });
        });

        $('input[class="checkbox-geral-<?= $i ?>"]').on('switchChange.bootstrapSwitch', function(event, state) {
            $('input[class="my-checkbox-<?= $i ?>"]').bootstrapSwitch('state', state, false);
        });

    <?php
    }
    ?>
    </script>

<?php
}
?>

<?php 
	if(Authenticator::isAdmin())
	{
?>
<script>
$(function () {
	$("[class='checkbox-admin']").bootstrapSwitch({size: 'small',
												onText: 'On',
												offText: 'Off'
												});
});

$('input[class="checkbox-admin"]').on('switchChange.bootstrapSwitch', function(event, state) {
  	
  	$('#form_admin').submit();
});

</script>
<?php
	}
?>

</body>
</html>