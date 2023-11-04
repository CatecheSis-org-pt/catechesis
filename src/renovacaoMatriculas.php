<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/log_functions.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/enrollment_functions.php");
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\UserData;
use catechesis\Utils;
use core\domain\EnrollmentStatus;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;



// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::ENROLMENTS);
$pageUI->addWidget($menu);
$deleteRenewalDialog = new ModalDialogWidget("confirmarEliminarPedido");
$pageUI->addWidget($deleteRenewalDialog);
$renewalDetailsDialog = new ModalDialogWidget("detalhesPedido");
$pageUI->addWidget($renewalDetailsDialog);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Renovação de matrículas</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-switch.css">
  <link rel="stylesheet" href="css/btn-group-hover.css">
  <link rel="stylesheet" href="font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">
  <link rel="stylesheet" type="text/css" href="css/DataTables/datatables.min.css"/>


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
	<h3>Renovação de matrículas</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:170px; "></div>


<div class="container" id="contentor">

 <div class="no-print">
  <h2 class> Renovação de matrículas</h2>
  <div class="row" style="margin-bottom:40px; "></div>
 </div>

 <div>
<?php

if(!Authenticator::isAdmin())
{
    echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
    echo("</div></body></html>");
    die();

}


$username = Authenticator::getUsername();
$db = new PdoDatabaseManager();



if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="escolher")
{ 

	$ano_precedente = intval(Utils::sanitizeInput($_POST['ano_catequetico_prec']));
	$ano_matricula = $ano_precedente + 10001;
	$catecismo_precedente = intval(Utils::sanitizeInput($_POST['catecismo_prec']));
	$turma_precedente = Utils::sanitizeInput($_POST['turma_prec']);
	$ano_mat_i = Utils::getCatecheticalYearStart($ano_matricula);
	$ano_mat_f = Utils::getCatecheticalYearEnd($ano_matricula);
}




//Guardar alteracoes
if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="guardar" )
{
 	$catequizandos_renovam = $_POST['catequizando'];	    //Lista de cid de catequizandos que renovam matricula
 	$pagamentos = $_POST['pagamentos'];						//Lista de cid de catequizandos que pagaram a matricula

	$ano_prec = intval(Utils::sanitizeInput($_POST['ano_prec']));
	$cat_prec = intval(Utils::sanitizeInput($_POST['cat_prec']));
	$turma_prec = Utils::sanitizeInput($_POST['turma_prec']);
	$ano_mat = intval(Utils::sanitizeInput($_POST['ano_mat']));
	$cat_mat_ap = intval(Utils::sanitizeInput($_POST['cat_mat_ap']));
	$turma_mat_ap = Utils::sanitizeInput($_POST['turma_mat_ap']);
	$cat_mat_rp = intval(Utils::sanitizeInput($_POST['cat_mat_rp']));
	$turma_mat_rp = Utils::sanitizeInput($_POST['turma_mat_rp']);

    $pedidos_processa = $_POST['pedidoProcessado'];         //Lista de rid de pedidos que foram marcados como processados
    $pedidos_desprocessa = $_POST['pedidoNaoProcessado'];   //Lista de rid de pedidos que foram desmarcados do estado de processados



    //Obter listagem dos catequizandos
    $result2 = NULL;
    try
    {
        $result2 = $db->getCatechumensByCatechismWithFilters($ano_prec, $ano_prec, $cat_prec, $turma_prec, true);
    }
    catch (Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }


    if(count($result2) >= 1)
    {
        foreach($result2 as $row2)
        {
            $cid = $row2['cid'];
            $passa = $row2['passa'];

            $ins_passa = true;
            if(in_array($cid, $pagamentos))
                $ins_pago = true;
            else
                $ins_pago = false;

            if($passa!=-1)
            {
                $ins_catecismo = $cat_mat_ap;
                $ins_turma = $turma_mat_ap;
            }
            else
            {
                $ins_catecismo = $cat_mat_rp;
                $ins_turma = $turma_mat_rp;
            }
            $ins_ano_catequetico = $ano_mat;


            //Renova matricula
            if(in_array($cid, $catequizandos_renovam))
            {
                try
                {
                    if($db->enrollCatechumenInGroup($cid, $ano_mat, $ins_catecismo, $ins_turma, $ins_passa, $ins_pago, Authenticator::getUsername()))
                    {
                        catechumenArchiveLog($cid, "Catequizando com id=" . $cid . " inscrito no " . $ins_catecismo . "º" . $ins_turma . ", no ano catequético de " . Utils::formatCatecheticalYear($ins_ano_catequetico) . ".");
                        if($ins_pago)
                            catechumenArchiveLog($cid, "Pagamento do catequizando com id=" . $cid . " referente ao catecismo " . $ins_catecismo . "º" . $ins_turma . " do ano catequético de " . Utils::formatCatecheticalYear($ins_ano_catequetico) . ".");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao inscrever o catequizando no grupo de catequese.</div>");

;
                    }
                }
                catch (Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                }
            }
        }

        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Renovações de matrícula efectuadas!</div>");
    }
    else
    {
        //Sem catequizandos
    }


    // Marcar pedidos como processados / nao processados
    foreach($pedidos_processa as $pedido)
        setRenewalOrderStatus($pedido, EnrollmentStatus::PROCESSED, $ano_mat, $cat_mat_ap, $turma_mat_ap); //Limitacao: Assumimos que todos sao aprovados...
    foreach($pedidos_desprocessa as $pedido)
        setRenewalOrderStatus($pedido, EnrollmentStatus::PENDING);


	//Libertar recursos
	$result2 = null;

	
	//Inicializar variaveis com os valores do ultimo pedido
	$ano_precedente = $ano_prec;
	$ano_matricula = $ano_precedente + 10001;
	$catecismo_precedente = $cat_prec;
	$turma_precedente = $turma_prec;
	$ano_mat_i = Utils::getCatecheticalYearStart($ano_matricula);
	$ano_mat_f = Utils::getCatecheticalYearEnd($ano_matricula);
}

//Eliminar pedido de renovacao online
if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="eliminarPedido" )
{
    $rid = intval(Utils::sanitizeInput($_POST['rid_el']));

    if(deleteRenewalOrder($rid))
        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Pedido de renovação de matrícula eliminado!</div>");
    else
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar pedido de renovação de matricula.</div>");
}
?>



<!-- ESCOLHER ANO E CATECISMO -->
<div class="well well-lg" style="position:relative; z-index:2;">
    <form role="form" action="renovacaoMatriculas.php?op=escolher" method="post">
      <div class="form-group">
        <div class="col-xs-5">
         <label for="ano_catequetico">Ano catequético precedente: </label>
         <select name="ano_catequetico_prec" onchange="this.form.submit()">
        <?php

            //Obter anos lectivos
            try
            {
                $result = $db->getCatecheticalYears();

                foreach($result as $row)
                {
                    echo("<option value='" . $row['ano_lectivo'] . "'");
                    if ($_SERVER["REQUEST_METHOD"] == "POST")
                    {
                        if($ano_precedente==$row['ano_lectivo'])
                            echo(" selected");
                    }
                    else if (Utils::currentCatecheticalYear() ==$row['ano_lectivo'])
                        echo(" selected");
                    echo(">");
                    echo(Utils::formatCatecheticalYear($row['ano_lectivo']) . "</option>\n");
                }
            }
            catch (Exception $e)
            {
                //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                //die();
            }
        ?>
          </select>
        </div>
       </div>
      <div class="form-group">
        <div class="col-xs-4">
            <label for="catecismo">Catecismo:</label>
            <select name="catecismo_prec" onchange="this.form.submit()">
        <?php

            //Obter anos de catequese
            try
            {
                $result = $db->getCatechisms();

                if (isset($result) && count($result)>=1)
                {
                    foreach($result as $row)
                    {
                        echo("<option value='" . $row['ano_catecismo'] . "'");
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && $catecismo_precedente==$row['ano_catecismo'])
                            echo(" selected");
                        echo(">");
                        echo("" . $row['ano_catecismo'] . "º" . "</option>\n");
                    }
                }
            }
            catch (Exception $e)
            {
                //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                //die();
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

            //Obter turmas de catequese
            try
            {
                $result = $db->getGroupLetters();

                if (isset($result) && count($result)>=1)
                {
                    foreach($result as $row)
                    {
                        echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && $turma_precedente==$row['turma'])
                            echo(" selected");
                        echo(">");
                        echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                    }
                }
            }
            catch (Exception $e)
            {
                //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                //die();
            }
        ?>
            </select>
        </div>
       </div>
      <div class="clearfix"></div>
    </form>
</div>
<!-- //ESCOLHER ANO E CATECISMO -->



<?php

	//Inicializar as variaveis quando nao houve POST
	if($_SERVER["REQUEST_METHOD"] != "POST")
	{	 

		$ano_precedente = Utils::currentCatecheticalYear();
		$ano_matricula = $ano_precedente + 10001;
		$catecismo_precedente = 1;
		$turma_precedente = 'A';
		$ano_mat_i = Utils::getCatecheticalYearStart($ano_matricula);
		$ano_mat_f = Utils::getCatecheticalYearEnd($ano_matricula);
	}

	$catecismo_matricula_ap = $catecismo_precedente + 1;
	$catecismo_matricula_rp = $catecismo_precedente;

	if((!$db->hasCatechism($ano_matricula, $catecismo_matricula_ap) && $catecismo_precedente!=intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))) || !$db->hasCatechism($ano_matricula, $catecismo_matricula_rp))
	{
	    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Ainda não foram criados os grupos de catequese para " . Utils::formatCatecheticalYear($ano_matricula) .  ". Ir para <a href='gerirGrupos.php'> Gerir grupos de catequese </a>. </div>");
	}
	else 
	{
?>

  <div class="row" style="margin-top:20px; "></div>


  <!-- Escolher destino das matriculas -->
  <form role="form" id="form_renovacao" name="form_renovacao" onsubmit="" action="renovacaoMatriculas.php?op=guardar" method="post">
  
  <input type="hidden" name="ano_prec" value=<?php echo('"' . $ano_precedente . '"');  ?> />
  <input type="hidden" name="cat_prec" value=<?php echo('"' . $catecismo_precedente . '"');  ?> />
  <input type="hidden" name="turma_prec" value=<?php echo('"' . $turma_precedente . '"');  ?> />
  
  <div class="well well-lg" style="position:relative; z-index:2;">
	<div class="form-group">
	
	 <div class="col-xs-6">
	 <label for="ano_catequetico">Ano catequético da matrícula: </label>
		<span>
		<?php
			echo("" . $ano_mat_i . "/" . $ano_mat_f);
		?>		
		</span>
		<input type="hidden" name="ano_mat" value=<?php echo('"' . $ano_matricula . '"');  ?> />
	</div>

	<div class="clearfix"></div>
	<div class="row" style="margin-top:20px; "></div>
	
	<div class="col-xs-6">
        <div class="panel panel-default">
        <div class="panel-heading">Aprovados</div>
        <div class="panel-body">

             <div class="col-xs-6">
             <label for="catecismo">Catecismo: </label>
                <span>
                <?php
                    $catecismo_matricula_ap = $catecismo_precedente + 1;
                    if($catecismo_matricula_ap <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
                        echo($catecismo_matricula_ap . "º");
                    else
                        echo("-");
                ?>
                </span>
                <select style="visibility:hidden"></select>
                <input type="hidden" name="cat_mat_ap" value="<?php if($catecismo_matricula_ap <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))) echo($catecismo_matricula_ap); else echo(intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))); ?>" />
            </div>

            <div class="col-xs-6">
             <label for="turma">Grupo: </label>
            <?php
            if($catecismo_matricula_ap > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
            {
                echo("<span> - </span><select style=\"visibility:hidden\"></select>");
            }
            else
            {
                echo("<select name=\"turma_mat_ap\">");

                //Obter turmas de catequese
                try
                {
                    $result = $db->getCatechismGroups($ano_matricula, $catecismo_matricula_ap);

                    if (isset($result) && count($result) >= 1)
                    {
                        foreach($result as $row)
                        {
                            echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
                            if ($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="escolher" && $turma_precedente==$row['turma'])
                                echo(" selected");
                            echo(">");
                            echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                        }
                    }

                }
                catch (Exception $e)
                {
                    //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    //die();
                }

                echo("</select>");
            }
            ?>
             </div>
        </div>
        </div>
	</div>
	
	
	<div class="col-xs-6">
	<div class="panel panel-default">
    <div class="panel-heading">Reprovados</div>
    <div class="panel-body">
    
	 <div class="col-xs-6">
	 <label for="catecismo">Catecismo: </label>
		<span>
		<?php
			$catecismo_matricula_rp = $catecismo_precedente;
			echo($catecismo_matricula_rp . "º");
		?>		
		</span>
		<select style="visibility:hidden"></select>
		<input type="hidden" name="cat_mat_rp" value=<?php echo('"' . $catecismo_matricula_rp . '"');  ?> />
	</div>

	<div class="col-xs-6">
	 <label for="turma">Grupo: </label>
		<select name="turma_mat_rp">
		<?php

		//Obter turmas de catequese
		try
        {
			$result = $db->getCatechismGroups($ano_matricula, $catecismo_matricula_rp);

            if (isset($result) && count($result) >= 1)
            {
                foreach($result as $row)
                {
                    echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
                    if ($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="escolher" && $turma_precedente==$row['turma'])
                        echo(" selected");
                    echo(">");
                    echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                }
            }
		}
        catch (Exception $e)
        {
            //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            //die();
        }

		?>
		</select>
	 </div>
	</div>
	</div>
	</div>
	
	</div>
  </div>
 <?php
  
    }		//IF ELSE que verifica se existem o ano catequetico seguinte na BD
 ?>

  <!-- //Escolher destino das matriculas -->
  <?php
        //Listagem dos catequizandos
        $result2 = NULL;
        try
        {
            $result2 = $db->getCatechumensEnrollmentRenewalCandidateList($ano_precedente, $catecismo_precedente, $turma_precedente, $ano_matricula);
        }
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        if(isset($result2) && count($result2) >= 1)
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
				    	<table class="table table-hover resultados" id="tabela-resultados">
				    	  <thead>
				    		<tr>
				    			<th style="background-color: transparent;">
					    			<div class="only-print" style="opacity:0;">
                                        <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
                                        <h3>Aproveitamento dos catequizandos</h3>
                                        <div class="row" style="margin-bottom:0px; "></div>
                                    </div>
							Matrícula</th>
								<th>Pago?</th>
								<th>Nome</th>
				    			<th>Data nascimento
                                <th>Escuteiro?</th>
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
					$foto = $row2['foto'];
					$passa = $row2['passa'];
					$cid = $row2['cid'];
					$renovou = $row2['renovou']; 	//1 se o catequizando ja se inscreveu no ano seguinte, 0 caso contrario
                    $escuteiro = ($row2['escuteiro'] != 0);

					if($renovou!=1)
						echo("<tr class='default'>\n");
					else
						echo("<tr class='active'>\n");
					
						if($renovou!=1)
							echo("<td class='rowlink-skip'><input type=\"checkbox\" class=\"my-checkbox\" name=\"catequizando[]\" value='$cid' ></td>");
						else 
							echo("<td><i> Renovada </i></td>");
							
						if($renovou!=1)	
							echo("<td class='rowlink-skip'><input type=\"checkbox\" class=\"rowlink-skip\" name=\"pagamentos[]\" value='$cid' ></td>");
						else
							echo("<td></td>");
								    	  			
		    	  		echo("\t<td ");
		    	  			echo("data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" data-content=\"<img src='");
		    	  				if($foto && $foto!="") 
		    	  					echo("resources/catechumenPhoto.php?foto_name=$foto");
		    	  				else
		    	  					echo("img/default-user-icon-profile.png");
		    	  			echo("' style='height:133px; widht:100px;'>\"");
		    	  			echo("><a href=\"mostrarFicha.php?cid=" . $row2['cid'] . "\" target=\"_blank\"></a>" . Utils::sanitizeOutput($row2['nome']) . "</td>\n");
		    	  		echo("\t<td>" . date( "d-m-Y", strtotime($row2['data_nasc'])) . "</td>\n");

		    	  		echo("\t<td>");
		    	  		if($escuteiro)
		    	  		    echo("<span class='fas fa-campground'></span>");
		    	  		echo("</td>\n");

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
?>


   <!-- Renovacoes online -->
<?php

    $pedidosPendentes = array();
    $pedidosProcessados = array();

    try
    {
        $pedidosRenovacao = $db->getRenewalSubmissions($ano_matricula, $catecismo_precedente);

        foreach ($pedidosRenovacao as $pedido)
        {
            if ($pedido['processado'] == 1)
                array_push($pedidosProcessados, $pedido);
            else
                array_push($pedidosPendentes, $pedido);
        }

        //Libertar recursos
        $pedidosRenovacao = null;
    }
    catch(Exception $e)
    {
        echo("<p><strong>Erro!</strong> " . $e->getMessage() . "</p>");
    }

?>

      <div class="row" style="margin-top:20px; "></div>
      <div class="page-header" style="position:relative; z-index:2;">
          <h1><small></small></h1>
      </div>

     <div class="clearfix"></div>
     <div class="row" style="margin-top:40px; "></div>



     <div class="panel panel-info no-print">
        <div class="panel-heading"><span class="glyphicon glyphicon-globe"></span>&nbsp; Pedidos de renovação online</div>
        <div class="panel-body">

            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                <div class="panel panel-info">
                    <div class="panel-heading" role="tab" id="headingOne">
                        <div class="panel-title" style="font-size: 14px">
                            <a role="button" data-toggle="collapse" href="#collapseOne" aria-expanded="<?php if(count($pedidosPendentes)>0) echo('true'); else echo('false'); ?>" aria-controls="collapseOne">
                                Pendentes (<?php echo(count($pedidosPendentes)); ?>)
                            </a>
                        </div>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse <?php if(count($pedidosPendentes)>0) echo('in'); ?>" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            <div class="col-xs-12">
                                <?php if(count($pedidosPendentes) > 0)
                                {?>
                               <table class="table table-hover" id="tabela-renovacoes-pendentes">
                                    <thead>
                                        <tr>
                                            <th>Estado</th>
                                            <th>ID do pedido</th>
                                            <th>Nome do catequizando</th>
                                            <th>Último catecismo frequentado</th>
                                            <th>Observações</th>
                                            <th></th> <!-- Accoes -->
                                        </tr>
                                    </thead>
                                    <tbody data-link="row" class="rowlink">
                                    <?php

                                        foreach($pedidosPendentes as $pedido)
                                        {
                                            $rid = intval($pedido['rid']);
                                            $enc_edu_nome = Utils::sanitizeOutput($pedido['enc_edu_nome']);
                                            $enc_edu_tel = Utils::sanitizeOutput($pedido['enc_edu_tel']);
                                            $enc_edu_email = Utils::sanitizeOutput($pedido['enc_edu_email']);
                                            $catequizando_nome = Utils::sanitizeOutput($pedido['catequizando_nome']);
                                            $ultimo_catecismo = intval($pedido['ultimo_catecismo']);
                                            $observacoes = Utils::escapeSingleQuotes(Utils::doubleEscapeDoubleQuotes(Utils::doubleEscapeWhiteSpaces(Utils::sanitizeOutput($pedido['observacoes']))));


                                            echo("<tr class='default'>\n");

                                            if($pedido['processado'] != 1)
                                                echo("<td class='rowlink-skip'><input type=\"checkbox\" class=\"checkbox-pedidos-processados\" name=\"pedidoProcessado[]\" value='$rid' ></td>");
                                            else
                                                echo("<td class='rowlink-skip'><input type=\"checkbox\" class=\"checkbox-pedidos-processados\" name=\"pedidoProcessado[]\" value='$rid' checked></td>");


                                            echo("\t<td>" . $rid . "</td>\n");

                                            echo("\t<td><a href='' data-toggle=\"modal\" data-target=\"#detalhesPedido\" onclick=\"preparar_detalhes("
                                                . $rid . ",'" . $enc_edu_nome . "','" . $enc_edu_tel . "','" . $enc_edu_email
                                                . "','" . $catequizando_nome . "'," . $ultimo_catecismo . ",'" . $observacoes . "')\"></a>");
                                            echo($catequizando_nome . "</td>");

                                            echo("\t<td>" . $ultimo_catecismo . "º</td>\n");

                                            echo("\t<td>");
                                            if(isset($observacoes) && $observacoes != "")
                                            {
                                                echo("<span class='glyphicon glyphicon-comment' data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" title=\"Observações\" data-content=\"" . $observacoes . "\"></span>");
                                            }
                                            echo("</td>\n");

                                            echo("<td class='rowlink-skip'>");
                                            echo('<div class="btn-group-xs btn-group-hover pull-right" role="group" aria-label="..."><button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarEliminarPedido" onclick="preparar_eliminacao_pedido(' . $rid . ')"><span class="glyphicon glyphicon-trash text-danger"> Eliminar</span></button></div>'); //Botao eliminar
                                            echo('</td>');

                                            echo("</tr>\n");
                                        }

                                    ?>
                                    </tbody>
                                </table>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-info">
                    <div class="panel-heading" role="tab" id="headingTwo">
                        <div class="panel-title" style="font-size: 14px">
                            <a class="collapsed" role="button" data-toggle="collapse" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Processados (<?php echo(count($pedidosProcessados)); ?>)
                            </a>
                        </div>
                    </div>
                    <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                        <div class="panel-body">
                            <div class="col-xs-12">
                                <?php if(count($pedidosProcessados) > 0)
                                {?>
                                <table class="table table-hover" id="tabela-renovacoes-processadas">
                                    <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th>ID do pedido</th>
                                        <th>Nome do catequizando</th>
                                        <th>Último catecismo frequentado</th>
                                        <th>Observações</th>
                                        <th></th> <!-- Accoes -->
                                    </tr>
                                    </thead>
                                    <tbody data-link="row" class="rowlink">
                                    <?php

                                    foreach($pedidosProcessados as $pedido)
                                    {
                                        $rid = intval($pedido['rid']);
                                        $enc_edu_nome = Utils::sanitizeOutput($pedido['enc_edu_nome']);
                                        $enc_edu_tel = Utils::sanitizeOutput($pedido['enc_edu_tel']);
                                        $enc_edu_email = Utils::sanitizeOutput($pedido['enc_edu_email']);
                                        $catequizando_nome = Utils::sanitizeOutput($pedido['catequizando_nome']);
                                        $ultimo_catecismo = intval($pedido['ultimo_catecismo']);
                                        $observacoes = Utils::escapeSingleQuotes(Utils::doubleEscapeDoubleQuotes(Utils::doubleEscapeWhiteSpaces(Utils::sanitizeOutput($pedido['observacoes']))));


                                        echo("<tr class='default'>\n");

                                        if($pedido['processado'] == 1)
                                            echo("<td class='rowlink-skip'><input type=\"checkbox\" class=\"checkbox-pedidos-nao-processados\" name=\"pedidoNaoProcessado[]\" value='$rid' ></td>");
                                        else
                                            echo("<td class='rowlink-skip'><input type=\"checkbox\" class=\"checkbox-pedidos-nao-processados\" name=\"pedidoNaoProcessado[]\" value='$rid' checked></td>");

                                        echo("\t<td>" . $pedido['rid'] . "</td>\n");

                                        echo("\t<td><a href='' data-toggle=\"modal\" data-target=\"#detalhesPedido\" onclick=\"preparar_detalhes("
                                            . $rid . ",'" . $enc_edu_nome . "','" . $enc_edu_tel . "','" . $enc_edu_email
                                            . "','" . $catequizando_nome . "'," . $ultimo_catecismo . ",'" . $observacoes . "')\"></a>");
                                        echo($catequizando_nome . "</td>");

                                        echo("\t<td>" . $ultimo_catecismo . "º</td>\n");

                                        echo("\t<td>");
                                        if(isset($observacoes) && $observacoes != "")
                                        {
                                            echo("<span class='glyphicon glyphicon-comment' data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" title=\"Observações\" data-content=\"" . $observacoes . "\"></span>");
                                        }
                                        echo("</td>\n");

                                        echo("<td class='rowlink-skip'>");
                                        echo('<div class="btn-group-xs btn-group-hover pull-right" role="group" aria-label="..."><button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarEliminarPedido" onclick="preparar_eliminacao_pedido(' . $rid . ')"><span class="glyphicon glyphicon-trash text-danger"> Eliminar</span></button></div>'); //Botao eliminar
                                        echo('</td>');

                                        echo("</tr>\n");
                                    }

                                    ?>
                                    </tbody>
                                </table>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

      </div>
     </div>

     </form> <!-- //form form_renovacao -->


     <div class="clearfix" style="margin-bottom: 40px"></div>

     <div class="no-print">
         <div class="btn-group" role="group" aria-label="...">
             <button type="button" class="btn btn-primary glyphicon glyphicon-floppy-disk" onclick="document.getElementById('form_renovacao').submit();"> Guardar</button>
             <button type="button" class="btn btn-default glyphicon glyphicon-print" onclick="window.print()"> Imprimir</button>
         </div>
     </div>

 </div>


<div class="clearfix" style="margin-bottom: 40px"></div>

   
</div>
<!-- // container -->



<!-- Forms e dialogos -->

<form id="form_eliminar_pedido" role="form" action="renovacaoMatriculas.php?op=eliminarPedido" method="post">
    <input type="hidden" id="rid_el" name="rid_el">
</form>

<?php

// Dialog to confirm deletion of renewal order

$deleteRenewalDialog->setTitle("Confirmar eliminação");
$deleteRenewalDialog->setBodyContents(<<<HTML_CODE
                <p>Se existir um problema com este pedido de inscrição, é preferível contactar o requerente primeiro.<br>
                Se eliminar este pedido de renovação de matrícula perderá os contactos deste requerente.<br>
                <b>Esta ação é irreversível.</b></p>
                <p>Tem a certeza de que pretende eliminar este pedido de renovação de matrícula?</p>
HTML_CODE
);
$deleteRenewalDialog->addButton(new Button("Não", ButtonType::SECONDARY))
           ->addButton(new Button("Sim", ButtonType::DANGER, "eliminar_pedido()"));
$deleteRenewalDialog->renderHTML();



// Dialog with details about the renewal order

$renewalDetailsDialog->setTitle("Detalhes do pedido");
$renewalDetailsDialog->setBodyContents(<<<HTML_CODE
                <p><span><b>ID: </b></span><span id="dialogo_id"></span></p>
                <div style="margin-top: 20px"></div>
                <p><span><b>Nome do catequizando: </b></span><span id="dialogo_catequizando"></span></p>
                <p><span><b>Último catecismo frequentado: </b></span><span id="dialogo_catecismo"></span></p>
                <div style="margin-top: 20px"></div>
                <p><span><b>Encarregado de educação: </b></span><span id="dialogo_enc_edu"></span></p>
                <p><span><b>Telefone: </b></span><span id="dialogo_tel"></span></p>
                <p><span><b>Email: </b></span><span id="dialogo_email"></span></p>
                <div style="margin-top: 20px"></div>
                <p><span><b>Observações: </b><br></span><span id="dialogo_observações"></span></p>
HTML_CODE
);
$renewalDetailsDialog->addButton(new Button("Fechar", ButtonType::SECONDARY));
$renewalDetailsDialog->renderHTML();

?>


<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/rowlink.js"></script>
<script src="js/bootstrap-switch.js"></script>
<script type="text/javascript" src="js/DataTables/datatables.min.js"></script>
<script src="js/btn-group-hover.js"></script>

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

$(document).ready( function () {
    $('#tabela-resultados').DataTable({
        paging: false,
        info: false,
        language: {
            url: 'js/DataTables/Portuguese.json'
        }
    });
} );

$(document).ready( function () {
    $('#tabela-renovacoes-pendentes').DataTable({
        paging: false,
        info: false,
        language: {
            url: 'js/DataTables/Portuguese.json'
        }
    });
});

$(document).ready( function () {
    $('#tabela-renovacoes-processadas').DataTable({
        paging: false,
        info: false,
        language: {
            url: 'js/DataTables/Portuguese.json'
        }
    });
} );

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
												onText: 'Renova',
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
        $("[class='checkbox-pedidos-processados']").bootstrapSwitch({size: 'mini',
            onText: 'Processado',
            offText: 'Pendente',
            onColor: 'success',
            offColor: 'default'
        });
    });

    $('input[class="checkbox-pedidos-processados"]').on('switchChange.bootstrapSwitch', function(event, state) {

        mudaSwitch(this.closest('tr'), state);
    });
</script>

<script>
    $(function () {
        $("[class='checkbox-pedidos-nao-processados']").bootstrapSwitch({size: 'mini',
            onText: 'Pendente',
            offText: 'Processado',
            onColor: 'default',
            offColor: 'success'
        });
    });

    $('input[class="checkbox-pedidos-nao-processados"]').on('switchChange.bootstrapSwitch', function(event, state) {

        mudaSwitch(this.closest('tr'), state);
    });
</script>


<script>
$(function () {
	$("[class='checkbox-geral']").bootstrapSwitch({size: 'mini',
												onText: 'Renova',
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

function preparar_eliminacao_pedido(rid)
{
    document.getElementById("rid_el").value = rid;
}


function eliminar_pedido()
{
    document.getElementById("form_eliminar_pedido").submit();
}



function preparar_detalhes(rid, enc_edu_nome, enc_edu_tel, enc_edu_email, catequizando_nome, ultimo_catecismo, observacoes)
{
    document.getElementById("dialogo_id").innerText = rid;
    document.getElementById("dialogo_enc_edu").innerText = enc_edu_nome;
    document.getElementById("dialogo_tel").innerHTML = "<a href=\"tel:" + enc_edu_tel + "\">" + enc_edu_tel + "</a>";
    document.getElementById("dialogo_email").innerHTML = "<a href=\"mailto:" + enc_edu_email + "\">" + enc_edu_email + "</a>";
    document.getElementById("dialogo_catequizando").innerText = catequizando_nome;
    document.getElementById("dialogo_catecismo").innerText = ultimo_catecismo + "º";
    document.getElementById("dialogo_observações").innerHTML = observacoes;
}
</script>

</body>
</html>