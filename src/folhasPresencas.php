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



	    tr:nth-child(even) td{
		    background-color: #f9f9f9 !important;
		    -webkit-print-color-adjust: exact; 
		}
		

		
		
	}
	
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
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
			var numCells = table.rows[1].cells.length;
			while(numCells >= 0)
			{
				var cell = row.insertCell(0);
				cell.innerHTML = "&nbsp;";
				numCells--;
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

      <div class="well well-lg" style="position:relative; z-index:2;">

        <div class="col-xs-3">
          <div class="btn-group" role="group" aria-label="...">
          <button type="button" class="btn btn-default glyphicon glyphicon-print" data-toggle="modal" data-target="#instrucoesImpressao" onclick=""> Imprimir</button>
          </div>
        </div>

        <div class="col-xs-9">
          <input type="checkbox" id="linhas-sup-checkbox" class="my-checkbox" onchange="add_rows();">  &nbsp; Linhas suplementares
        </div>

        <div class="clearfix"></div>
      </div>

   </div>
   
   <div class="row" style="margin-bottom:20px; "></div>
   
</div>  


<?php


	function escreve_dias($ano_i, $ano_f)
	{
        $weekDay = WeekDay::toString(Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_WEEK_DAY));
		$timestamp = strtotime('first ' . $weekDay . ' of ' . Locale::catechesisStartMonth(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)), strtotime('1-1-' . $ano_i));

		setlocale(LC_TIME, "pt_PT");
		$mes_actual = strftime('%m', $timestamp);
		$ultimo_mes = strftime('%m', $timestamp);
	
		echo("<tr>\n");
		for($i=0; $timestamp <= strtotime('last ' . $weekDay . ' of ' . Locale::catechesisEndMonth(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)), strtotime('1-1-' . $ano_f)); $i++)
		{
		 	 while($mes_actual==$ultimo_mes)
			 {
			 	echo("\t<th><small>" . intval(strftime('%d', $timestamp)) . "</small></th>\n");
			 	$timestamp = strtotime('next ' . $weekDay, $timestamp);
			 	
			 	$ultimo_mes = $mes_actual;
				$mes_actual = strftime('%m', $timestamp);
			 }

			 $mes_actual = strftime('%m', $timestamp);
			 $ultimo_mes = strftime('%m', $timestamp);
		
			 echo("\n");
		}
		echo("</tr>\n");
	}
	
	
	
	function computeNumCatechesisDays($ano_i, $ano_f, $mes)
	{
        $weekDay = WeekDay::toString(Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_WEEK_DAY));
        $months = array(1 => 'January', 2 =>'February', 3 =>'March', 4 =>'April', 5 =>'May', 6 =>'June', 7 =>'July', 8 =>'August', 9 =>'September', 10 =>'October', 11 =>'November', 12 =>'December' );
		
		$ano = $ano_i;
		if($mes<9)
			$ano = $ano_f;
		
		$timestamp = strtotime('first ' . $weekDay . ' of ' . $months[intval($mes)], strtotime('1-1-' . $ano));

		setlocale(LC_TIME, "pt_PT");
		$mes_actual = strftime('%m', $timestamp);
		$ultimo_mes = strftime('%m', $timestamp);
		
		$res = 0;
		if($timestamp <= strtotime('last ' . $weekDay . ' of ' . Locale::catechesisEndMonth(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)), strtotime('1-1-' . $ano_f)))
		{
		 	 while($mes_actual==$ultimo_mes)
			 {
			 	if($mes_actual==$mes)
					$res++;
					
			 	$timestamp = strtotime('next ' . $weekDay, $timestamp);
			 	
			 	$ultimo_mes = $mes_actual;
				$mes_actual = strftime('%m', $timestamp);			
				
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
				echo("<h4 class='only-print'>Folha de presenças</h4>\n");
				echo("<span>Ano catequético: ".  Utils::formatCatecheticalYear($ano_catequetico) ."&nbsp;&nbsp;&nbsp;Catecismo: " . intval($catecismo) . "º" . $turma . "</span>");
                $ano_i = Utils::getCatecheticalYearStart($ano_catequetico);
                $ano_f = Utils::getCatecheticalYearEnd($ano_catequetico);
				?>
					<table id="tabela-presencas" class="table table-striped table-bordered table-condensed">
					<thead>
						<tr>
							<th rowspan="2" style="width: 200px; ">Nome</th>
                            <?php
                            $month_i = date_parse(Locale::catechesisStartMonth(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)));
                            $month_f = date_parse(Locale::catechesisEndMonth(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)));
                            $months_abrv = array(1 => 'Jan', 2 =>'Fev', 3 =>'Mar', 4 =>'Abr', 5 =>'Mai', 6 =>'Jun', 7 =>'Jul', 8 =>'Ago', 9 =>'Set', 10 =>'Out', 11 =>'Nov', 12 =>'Dez' );

                            $month = $month_i['month'];
                            for($i = 0; $i < 12; $i++)
                            {
                                ?>
                                <th colspan="<?= computeNumCatechesisDays($ano_i, $ano_f, $month)  ?>"><?= $months_abrv[$month] ?></th>
                                <?php

                                if($month == $month_f['month'])
                                    break;
                                $month = ($month %12 + 1);
                            }
							?>
						</tr>
			
			
				<?php	
		
				escreve_dias(Utils::getCatecheticalYearStart($ano_catequetico), Utils::getCatecheticalYearEnd($ano_catequetico)); //Gera linha da tabela com os dias dos sabados
			
				?>
			
					</thead>
					<tbody>
				
				<?php	

				foreach($result as $row)
				{
					$nome = Utils::sanitizeOutput($row['nome']);

					//Preencher ficha
					echo("\t<tr>\n");
					echo("\t\t<td>" . Utils::firstAndLastName($nome) . "</td>\n\n");

                    $month = $month_i['month'];
                    for($i = 0; $i < 10; $i++)
                    {
                        for($j=0; $j<computeNumCatechesisDays(Utils::getCatecheticalYearStart($ano_catequetico), Utils::getCatecheticalYearEnd($ano_catequetico), $month); $j++)
                            echo("\t\t<td></td>\n");

                        $month = ($month %12 + 1);
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
});

/*$('input[class="my-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {

    mudaSwitch(this.closest('tr'), state);
});*/

</script>

</body>
</html>