<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . '/core/domain/WeekDay.php');
require_once(__DIR__ . '/core/domain/Locale.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\UserData;
use catechesis\Utils;
use core\domain\WeekDay;
use core\domain\Locale;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;



// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
$pageUI->addWidget($menu);
$printDialog = new ModalDialogWidget("instrucoesImpressao");
$pageUI->addWidget($printDialog);


?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Folha de presenças</title>
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

        @page {
            size: landscape;
        }

        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
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

        tr:nth-child(odd) td{
            background-color: #f9f9f9 !important;
            -webkit-print-color-adjust: exact;
        }

        tr:nth-child(even) td.success {
            background-color: #d0e9c6 !important;
            -webkit-print-color-adjust: exact;
        }

        tr:nth-child(odd) td.success {
            background-color: #c1e2b3 !important;
            -webkit-print-color-adjust: exact;
        }

        tr:nth-child(even) td.danger {
            background-color: #ebcccc !important;
            -webkit-print-color-adjust: exact;
        }

        tr:nth-child(odd) td.danger {
            background-color: #e4b9b9 !important;
            -webkit-print-color-adjust: exact;
        }
	}
	
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}

        tr:nth-child(odd) td{
            background-color: #f9f9f9 !important;
            -webkit-print-color-adjust: exact;
        }

        tr:nth-child(even) td.success {
            background-color: #d0e9c6 !important;
            -webkit-print-color-adjust: exact;
        }

        tr:nth-child(odd) td.success {
            background-color: #c1e2b3 !important;
            -webkit-print-color-adjust: exact;
        }

        tr:nth-child(even) td.danger {
            background-color: #ebcccc !important;
            -webkit-print-color-adjust: exact;
        }

        tr:nth-child(odd) td.danger {
            background-color: #e4b9b9 !important;
            -webkit-print-color-adjust: exact;
        }
	}
	
	
	
	.table th {
	   text-align: center;  
	   width: 100%;
	}
	
	table { table-layout: fixed; }


  </style>
</head>
<body>

<script>
var linhas = 0;
var to_add = 7;	//Numero de linhas suplementares a adicionar

function add_rows()
{
	var table = document.getElementById("tabela-presencas");


	var numRows = table.rows.length;
	while(linhas > 0)
	{
		table.deleteRow(numRows-1);
		linhas--;
		numRows--;
	}

	if(document.getElementById('linhas-sup-checkbox').checked)
	{
		while(linhas < to_add)
		{
			var row = table.insertRow(-1);
			var numCells = table.rows[2].cells.length;
			for(var j=0; j<numCells; j++)
			{
				var cell = row.insertCell(-1);
				cell.innerHTML = "&nbsp;";
			}
			linhas++;
		}
	}
	
}

</script>




<?php
$menu->renderHTML();
?>





<div class="container">

    <div class="only-print" style="top: 0;">
        <img src="<?= UserData::getParishLogoQueryURL() ?>" style="width: 150px;">
    </div>

  <div class="no-print">
      <h2> Área de Impressão</h2>

      <div class="row" style="margin-top:40px; "></div>

      <form id="form_presencas" action="fichasCatequizandos.php" method="post">
        <input type="hidden" name="ano_catequetico" value="<?php if($_POST['ano_catequetico']) echo('' . $_POST['ano_catequetico'] . ''); ?>">
        <input type="hidden" name="catecismo" value="<?php if($_POST['catecismo']) echo('' . $_POST['catecismo'] . ''); ?>">
        <input type="hidden" name="turma" value="<?php if($_POST['turma']) echo('' . $_POST['turma'] . ''); ?>">
      </form>

      <form id="form_preInscricoes" action="fichasPreInscricao.php" method="post">
        <input type="hidden" name="ano_catequetico" value="<?php if($_POST['ano_catequetico']) echo('' . $_POST['ano_catequetico'] . ''); ?>">
        <input type="hidden" name="catecismo" value="<?php if($_POST['catecismo']) echo('' . $_POST['catecismo'] . ''); ?>">
        <input type="hidden" name="turma" value="<?php if($_POST['turma']) echo('' . $_POST['turma'] . ''); ?>">
      </form>

      <ul class="nav nav-tabs">
          <li role="presentation" style="cursor: pointer;" class="active"><a>Folha de presenças</a></li>
          <li role="presentation" style="cursor: pointer;"><a onclick="document.getElementById('form_presencas').submit();">Fichas dos catequizandos</a></li>
          <li role="presentation" style="cursor: pointer;"><a onclick="document.getElementById('form_preInscricoes').submit();">Pré-inscrições</a></li>
      </ul>


      <div class="row" style="margin-top:20px; "></div>

      <form id="form_filtros" action="folhasPresencas.php" method="post">
        <input type="hidden" name="ano_catequetico" value="<?php if(isset($_POST['ano_catequetico'])) echo('' . $_POST['ano_catequetico'] . ''); ?>">
        <input type="hidden" name="catecismo" value="<?php if(isset($_POST['catecismo'])) echo('' . $_POST['catecismo'] . ''); ?>">
        <input type="hidden" name="turma" value="<?php if(isset($_POST['turma'])) echo('' . $_POST['turma'] . ''); ?>">
        
        <div class="well well-lg" style="position:relative; z-index:2;">

            <div class="col-xs-3">
              <div class="btn-group" role="group" aria-label="...">
              <button type="button" class="btn btn-default glyphicon glyphicon-print" data-toggle="modal" data-target="#instrucoesImpressao" onclick=""> Imprimir</button>
              </div>
            </div>

            <div class="col-xs-4">
              <input type="checkbox" id="linhas-sup-checkbox" class="my-checkbox" onchange="add_rows();">  &nbsp; Linhas suplementares
            </div>

            <div class="col-xs-5">
                <?php
                $show_actual_data = true;
                if (isset($_POST['dados_reais']) && $_POST['dados_reais'] == 'off') {
                    $show_actual_data = false;
                }
                ?>
                <input type="checkbox" name="dados_reais_checkbox" id="dados-reais-checkbox" class="my-checkbox" <?= $show_actual_data ? "checked" : "" ?>> &nbsp; Mostrar presenças/faltas
                <input type="hidden" name="dados_reais" id="dados_reais_hidden" value="<?= $show_actual_data ? "on" : "off" ?>">
            </div>

            <div class="clearfix"></div>
        </div>
      </form>

   </div>
   
   <div class="row" style="margin-bottom:20px; "></div>
   
</div>  


<?php


	function get_dates($ano_catequetico, $catecismo, $turma, $show_actual_data = true)
	{
		$db = new PdoDatabaseManager();
		$dates = array();

		$weekDay = WeekDay::toString(Utils::getEffectiveWeekDay($ano_catequetico, $catecismo, $turma));
		$ano_i = Utils::getCatecheticalYearStart($ano_catequetico);
		$ano_f = Utils::getCatecheticalYearEnd($ano_catequetico);

		$start_month = Locale::catechesisStartMonth(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE));
		$end_month = Locale::catechesisEndMonth(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE));

		$timestamp = strtotime('first ' . $weekDay . ' of ' . $start_month, strtotime('1-1-' . $ano_i));
		$end_timestamp = strtotime('last ' . $weekDay . ' of ' . $end_month, strtotime('1-1-' . $ano_f));

		$today = date('Y-m-d');

		if ($show_actual_data) {
			$registered_sessions = $db->getCatechesisSessions($ano_catequetico, $catecismo, $turma);
			foreach($registered_sessions as $s) {
				$dates[] = $s['data'];
			}

			// Add future dates
			while ($timestamp <= $end_timestamp) {
				$d = date('Y-m-d', $timestamp);
				if ($d > $today && !in_array($d, $dates)) {
					$dates[] = $d;
				}
				$timestamp = strtotime('next ' . $weekDay, $timestamp);
			}
		} else {
			// Old behavior: only planned week days
			while ($timestamp <= $end_timestamp) {
				$dates[] = date('Y-m-d', $timestamp);
				$timestamp = strtotime('next ' . $weekDay, $timestamp);
			}
		}
		
		sort($dates);
		return $dates;
	}

	function escreve_dias($dates)
	{
		setlocale(LC_TIME, "pt_PT");
		echo("<tr>\n");
		foreach($dates as $date)
		{
			echo("\t<th><small>" . intval(date('d', strtotime($date))) . "</small></th>\n");
		}
		echo("</tr>\n");
	}
	
	function computeNumCatechesisDays($dates, $mes)
	{
		$res = 0;
		foreach($dates as $date)
		{
			if (intval(date('m', strtotime($date))) == intval($mes)) {
				$res++;
			}
		}
		return $res;
	}


	//Carregar variaveis por POST
	if ($_SERVER["REQUEST_METHOD"] == "POST") 
	{
		$ano_catequetico = intval($_POST['ano_catequetico']);
		$catecismo = intval($_POST['catecismo']);
		$turma = Utils::sanitizeInput($_POST['turma']);
        $show_actual_data = !isset($_POST['dados_reais']) || $_POST['dados_reais'] !== 'off';

		if($ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Impossível gerar folhas de presenças.</div>");
		}
		else if($catecismo <= 0 || $catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Impossível gerar folhas de presenças.</div>");
		}
		else
		{
			if(!Authenticator::isAdmin() && !group_belongs_to_catechist($ano_catequetico, $catecismo, $turma, Authenticator::getUsername()))
			{
				echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não tem permissões para gerar as folhas de presenças para este grupo de catequese (" . $catecismo . "º$turma).</div>");
				echo("</body></html>");
				die();
			}

			
			$result = NULL;
			
			//Obter nomes dos catequizandos
			try
            {
                $db = new PdoDatabaseManager();
                $result = $db->getCatechumensByCatechismWithFilters($ano_catequetico, $ano_catequetico, $catecismo, $turma, false);

			}catch(Exception $e){
				//echo $e->getMessage();
				echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
				$db = null;
				die();
			}
			

			if (count($result) >= 1)
			{
                $effectiveWeekDay = Utils::getEffectiveWeekDay($ano_catequetico, $catecismo, $turma);
                $start_time = Utils::getEffectiveStartTime($ano_catequetico, $catecismo, $turma);
                $end_time = Utils::getEffectiveEndTime($ano_catequetico, $catecismo, $turma);
                $formatted_time = WeekDay::toPortugueseString($effectiveWeekDay) . " " . substr($start_time, 0, 5) . " - " . substr($end_time, 0, 5);

				echo("<h4 class='only-print'>Folha de presenças</h4>\n");
				echo("<span>Ano catequético: ".  Utils::formatCatecheticalYear($ano_catequetico) ."&nbsp;&nbsp;&nbsp;Catecismo: " . intval($catecismo) . "º" . $turma . "&nbsp;&nbsp;&nbsp;Horário: " . $formatted_time . "</span>");
                $ano_i = Utils::getCatecheticalYearStart($ano_catequetico);
                $ano_f = Utils::getCatecheticalYearEnd($ano_catequetico);
				$dates = get_dates($ano_catequetico, $catecismo, $turma, $show_actual_data);
				?>
					<table id="tabela-presencas" class="table table-striped table-bordered table-condensed">
					<thead>
						<tr>
							<th rowspan="2" style="width: 200px; ">Nome</th>
                            <?php
                            $months_abrv = array(1 => 'Jan', 2 =>'Fev', 3 =>'Mar', 4 =>'Abr', 5 =>'Mai', 6 =>'Jun', 7 =>'Jul', 8 =>'Ago', 9 =>'Set', 10 =>'Out', 11 =>'Nov', 12 =>'Dez' );

                            $current_month = -1;
                            $current_year = -1;
                            $month_dates_count = 0;
                            $months_to_render = array();

                            foreach ($dates as $date) {
                                $d = strtotime($date);
                                $m = intval(date('m', $d));
                                $y = intval(date('Y', $d));

                                if ($m !== $current_month || $y !== $current_year) {
                                    if ($current_month !== -1) {
                                        $months_to_render[] = array('name' => $months_abrv[$current_month], 'count' => $month_dates_count);
                                    }
                                    $current_month = $m;
                                    $current_year = $y;
                                    $month_dates_count = 1;
                                } else {
                                    $month_dates_count++;
                                }
                            }
                            // Add last month
                            if ($current_month !== -1) {
                                $months_to_render[] = array('name' => $months_abrv[$current_month], 'count' => $month_dates_count);
                            }

                            foreach ($months_to_render as $m_data) {
                                ?>
                                <th colspan="<?= $m_data['count'] ?>"><?= $m_data['name'] ?></th>
                                <?php
                            }
							?>
						</tr>
			
			
				<?php	
		
				escreve_dias($dates); //Gera linha da tabela com os dias das sessoes de catequese
			
				?>
			
					</thead>
					<tbody>
				
				<?php	

				foreach($result as $row)
				{
					$nome = Utils::sanitizeOutput($row['nome']);
					$cid = intval($row['cid']);
					$attendance = array();
                    if ($show_actual_data) {
                        $raw_attendance = $db->getCatechumenAttendanceForGroup($ano_catequetico, $catecismo, $turma, $cid);
                        foreach($raw_attendance as $ra) {
                            $attendance[$ra['data']] = $ra['presenca'];
                        }
                    }

					//Preencher ficha
					echo("\t<tr>\n");
					echo("\t\t<td>" . Utils::firstAndLastName($nome) . "</td>\n\n");

                    foreach($dates as $date)
					{
						$symbol = "";
						if (isset($attendance[$date]) && (intval($attendance[$date]) === 1))
                        {
							//$symbol = (intval($attendance[$date]) === 1) ? "<i class=\"fas fa-solid fa-check\"></i>✅" : "❌";
                            echo("\t\t<td class='success text-center'><b><i class='fas fa-solid fa-check'></i></b></td>\n");
						}
                        else if(isset($attendance[$date]) && (intval($attendance[$date]) === 0))
                        {
                            echo("\t\t<td class='danger text-center'><b><i class='fas fa-solid fa-times'></i></b></td>\n");
                        }
                        else
                        {
                            echo("\t\t<td class='text-center'><b></b></td>\n");
                        }
					}
					
					echo("\t</tr>\n");
				}
			}
			else
			{
				echo("<div class=\"container\"><p>Não há catequizandos inscritos neste grupo de catequese</p></div>");
			}
		}
	}

	
	
	//Libertar recursos
	$result = null;
	
?>
					</tbody>
				</table>

                <table>
                    <tfoot class="only-print">
                        <tr>
                            <td colspan="4"><?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER); ?></td>
                        </tr>
                    </tfoot>
                </table>
   
<div class="no-print">

    <?php

    // Dialog with printing instructions

    $printDialog->setSize(ModalDialogWidget::SIZE_LARGE);
    $printDialog->setTitle("Recomendação");

    $printDialog->setBodyContents(<<<HTML_CODE
        <p>A página foi automaticamente configurada para ser impressa na horizontal. É recomendado que configure a escala de impressão de tal modo que a tabela caiba, em toda a sua largura, na página impressa. Utilize a janela de pré-visualização de impressão do seu navegador para ajustar a escala antes de imprimir.</p>
      	<p>Quando clicar em OK abrir-se-á a janela de configuração da impressora.</p>
      	<a style="cursor: pointer;" data-toggle="collapse" data-target="#exemplo">Mostre-me um exemplo <span class="glyphicon glyphicon-chevron-down"></span></a>
      	<div id="exemplo" class="collapse">
      		<div style="overflow: auto;">
      			<img src="img/exemplo_print_presencas.jpg" width=800px>
      		</div>
      	</div>
HTML_CODE
    );

    $printDialog->addButton(new Button("Cancelar", ButtonType::SECONDARY))
        ->addButton(new Button("OK", ButtonType::PRIMARY, "imprimir()"));

    $printDialog->renderHTML();
    ?>

</div>




<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/bootstrap-switch.js"></script>


<script>
function imprimir()
{
	$('#instrucoesImpressao').modal('hide')
	window.print();

}
</script>


<script>
$(function () {
	$("[class='my-checkbox']").bootstrapSwitch({size: 'small',
												onText: '&nbsp; &nbsp;',
												offText: '&nbsp; &nbsp;',
												onColor: 'success',
												offColor: ''
												});

    $('#dados-reais-checkbox').on('switchChange.bootstrapSwitch', function(event, state) {
        $('#dados_reais_hidden').val(state ? 'on' : 'off');
        $('#form_filtros').submit();
    });
});

/*$('input[class="my-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {

    mudaSwitch(this.closest('tr'), state);
});*/

</script>

</body>
</html>