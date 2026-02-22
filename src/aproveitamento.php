<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . "/gui/widgets/configuration_panels/CatechumensEvaluationActivationPanel/CatechumensEvaluationActivationPanelWidget.php");
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/DataValidationUtils.php');
require_once(__DIR__ . '/core/log_functions.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/statistics.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\DataValidationUtils;
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

        @page {
            size: portrait;
        }

        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .progress {
            background-color: #f5f5f5 !important;
            -webkit-print-color-adjust: exact;
        }

        .progress-bar {
            background-color: #337ab7 !important;
            -webkit-print-color-adjust: exact;
        }

        .progress-bar-danger {
            background-color: #d9534f !important;
            -webkit-print-color-adjust: exact;
        }
	    
	    
	    a[href]:after {
		    content: none;
		  }

        tr.success:nth-child(odd) td {
            background-color: #d0e9c6 !important;
            -webkit-print-color-adjust: exact;
        }

        tr.success:nth-child(even) td {
            background-color: #c1e2b3 !important;
            -webkit-print-color-adjust: exact;
        }

        tr.danger:nth-child(odd) td {
            background-color: #ebcccc !important;
            -webkit-print-color-adjust: exact;
        }

        tr.danger:nth-child(even) td {
            background-color: #e4b9b9 !important;
            -webkit-print-color-adjust: exact;
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
  <link rel="stylesheet" href="css/bootstrap-switch.css">
  <link rel="stylesheet" href="font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">
</head>
<body>

<?php
$menu->renderHTML();
?>

<div class="only-print" style="position: fixed; top: 0;">
    <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
	<h3>Aproveitamento dos catequizandos</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>


<div class="container" id="contentor">

<?php

	$db = new PdoDatabaseManager();

    // Get input parameters
    $ano_lectivo = NULL;
    $catecismo = NULL;
    $turma = NULL;

    if(isset($_POST['ano_lectivo']))
    {
        $ano_lectivo = intval($_POST['ano_lectivo']);
    }
    if(isset($_POST['catecismo']))
    {
        $catecismo = intval($_POST['catecismo']);
    }
    if(isset($_POST['turma']))
    {
        $turma = Utils::sanitizeInput($_POST['turma']);
    }

    $catechistGroups = $db->getCatechistGroups(Authenticator::getUsername(), Utils::currentCatecheticalYear());

    // Set defaults if not provided
    if(!isset($ano_lectivo))
    {
        $ano_lectivo = Utils::currentCatecheticalYear();
    }
    if(!isset($catecismo) || !($catecismo >= 1 && $catecismo <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))))
    {
        if(isset($catechistGroups) && count($catechistGroups) >= 1)
            $catecismo = $catechistGroups[0]["ano_catecismo"];
        else
            $catecismo = 1;
    }
    if(!isset($turma))
    {
        if(isset($catechistGroups) && count($catechistGroups) >= 1)
            $turma = $catechistGroups[0]["turma"];
        else
            $turma = 'A';
    }

    $has_permission = Authenticator::isAdmin() || group_belongs_to_catechist($ano_lectivo, $catecismo, $turma, Authenticator::getUsername());

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
    if(isset($_REQUEST['op']) && $_REQUEST['op']=="guardar" && $periodo_activo && $has_permission)
    {
        $catequizandos_passam = isset($_POST['catequizando']) ? $_POST['catequizando'] : array();	//Lista de cid de catequizandos que passam

        try
        {
            //Listagem dos catequizandos
            $result2 = $db->getCatechumensByCatechismWithFilters($ano_lectivo, $ano_lectivo, $catecismo, $turma, true);

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
                        $db->updateCatechumenAchievement($cid, $ano_lectivo, $catecismo, $turma, $decisao);

                        $log_string = "Catequizando $nome (cid=" . $cid . ")";
                        if($decisao==-1)
                            $log_string .= " reprovado ";
                        else
                            $log_string .= " transita ";
                        $log_string .= " no catecismo " . $catecismo . "º" . $turma . " do ano catequético de " . Utils::formatCatecheticalYear($ano_lectivo). ".";

                        catechumenArchiveLog($cid, $log_string);
                    }
                }
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Dados actualizados com sucesso.</div>");
            }
        }
        catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        //Libertar recursos
        $result2 = null;
    }

?>

 <div class="no-print">
  <h2> Aproveitamento dos catequizandos</h2>
  
  <div class="row" style="margin-bottom:40px; "></div>

    <!-- Group Selector -->
    <div class="well no-print">
        <form class="form-inline" role="form" action="aproveitamento.php" method="post">
            <div class="row">
                <!--ano lectivo-->
                <div class="form-group col-xs-12 col-sm-4">
                    <label for="ano_lectivo">Ano catequético:</label>
                    <select id="ano_lectivo" name="ano_lectivo" class="form-control" onchange="this.form.submit()">
                        <?php
                        $years = $db->getCatecheticalYears();
                        if (isset($years)) {
                            foreach($years as $row) {
                                echo("<option value='" . intval($row['ano_lectivo']) . "'");
                                if ($ano_lectivo == $row['ano_lectivo']) echo(" selected");
                                echo(">" . Utils::formatCatecheticalYear($row['ano_lectivo']) . "</option>\n");
                            }
                        }
                        ?>
                    </select>
                </div>
                <!--catecismo-->
                <div class="form-group col-xs-4 col-sm-2">
                    <label for="catecismo">Catecismo:</label>
                    <select id="catecismo" name="catecismo" class="form-control" onchange="this.form.submit()">
                        <?php
                        $catechisms = $db->getCatechisms($ano_lectivo);
                        if (isset($catechisms)) {
                            foreach($catechisms as $row) {
                                echo("<option value='" . intval($row['ano_catecismo']) . "'");
                                if ($catecismo == $row['ano_catecismo']) echo(" selected");
                                echo(">" . intval($row['ano_catecismo']) . "º</option>\n");
                            }
                        }
                        ?>
                    </select>
                </div>
                <!--turma-->
                <div class="form-group col-xs-4 col-sm-2">
                    <label for="turma">Grupo:</label>
                    <select id="turma" name="turma" class="form-control" onchange="this.form.submit()">
                        <?php
                        $groups = $db->getCatechismGroups($ano_lectivo, $catecismo);
                        if (isset($groups)) {
                            foreach($groups as $row) {
                                echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
                                if ($turma == $row['turma']) echo(" selected");
                                echo(">" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="clearfix"></div>
        </form>
    </div>
</div>

  <?php
    if (!$has_permission) {
        echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder ao grupo selecionado.</div>");
    }
  ?>

  <div class="row" style="margin-top:0px; "></div>
  
  <div>

      <form role="form" id="form_aproveitamento" name="form_aproveitamento" onsubmit="" action="aproveitamento.php?op=guardar" method="post">
        <input type="hidden" name="ano_lectivo" value="<?= $ano_lectivo ?>">
        <input type="hidden" name="catecismo" value="<?= $catecismo ?>">
        <input type="hidden" name="turma" value="<?= $turma ?>">
  <?php

    if ($has_permission) {
        try
        {

        //Listagem dos catequizandos
        $result2 = $db->getCatechumensByCatechismWithFilters($ano_lectivo, $ano_lectivo, $catecismo, $turma, true);

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
                            <th><input type="checkbox" class="checkbox-geral" checked> <span> Todos </span></th>
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
                        <th>Presenças</th>
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

                $stats = getCatechumenAttendanceStats($cid, $ano_lectivo, $catecismo, $turma);
                $percentage = $stats['percentage'];
                $attended = $stats['attended'];
                $totalSessions = $stats['total'];

                echo("<tr class='"); if($passa==-1) echo("danger"); else echo("success"); echo("'>\n");
                    if($periodo_activo)
                    {	echo("<td><input type=\"checkbox\" class=\"my-checkbox\" name=\"catequizando[]\" value='$cid' "); if($passa!=-1) echo("checked"); echo("></td>"); }
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

                    echo("\t<td style=\"width: 20%; min-width: 150px; vertical-align: middle;\">\n");
                    $progressBarClass = ($percentage < 50.0) ? "progress-bar-danger" : "";
                    echo("\t\t<div class=\"progress\" style=\"margin-bottom: 0; height: 15px; position: relative; width: 60%; float: left; margin-right: 5px;\">\n");
                    echo("\t\t\t<div class=\"progress-bar $progressBarClass\" role=\"progressbar\" aria-valuenow=\"$attended\" aria-valuemin=\"0\" aria-valuemax=\"$totalSessions\" style=\"width: $percentage%;\">\n");
                    echo("\t\t\t</div>\n");
                    echo("\t\t</div>\n");
                    echo("\t\t<span style=\"font-size: 11px; font-weight: bold;\">$attended / $totalSessions</span>\n");
                    echo("\t</td>\n");

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
	catch(Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }


	//Libertar recursos
	$result2 = null;
    }
    else
    {
        $periodo_activo = false;
    }
?>

      </form>

      <div class="clearfix" style="margin-bottom: 40px"></div>

      <div class="no-print">
          <div class="btn-group" role="group" aria-label="...">
              <?php if ($has_permission): ?>
              <button type="button" class="btn btn-primary" onclick="document.getElementById('form_aproveitamento').submit();" <?php if(!$periodo_activo) echo("disabled"); ?>><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
              <button type="button" class="btn btn-default" onclick="window.print()"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
              <?php endif; ?>
          </div>
      </div>

      <div class="clearfix" style="margin-bottom: 40px"></div>

  </div>



<?php $pageUI->renderJS(); ?>
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
        $(function () {
            $("[class='my-checkbox']").bootstrapSwitch({size: 'mini',
                onText: 'Transita',
                offText: 'Reprova',
                onColor: 'success',
                offColor: 'danger'
            });
        });

        $('input[class="my-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {
            mudaSwitch(this.closest('tr'), state);
        });

        $(function () {
            $("[class='checkbox-geral']").bootstrapSwitch({size: 'mini',
                                                        onText: 'Transita',
                                                        offText: 'Reprova',
                                                        onColor: 'success',
                                                        offColor: 'danger'
                                                        });
        });

        $('input[class="checkbox-geral"]').on('switchChange.bootstrapSwitch', function(event, state) {
            $('input[class="my-checkbox"]').bootstrapSwitch('state', state, false);
        });
    </script>

<?php
}
?>

</body>
</html>
