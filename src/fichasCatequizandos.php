<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/domain/Locale.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');

use catechesis\OrderCatechumensBy;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use core\domain\Locale;
use core\domain\Sacraments;
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
  <title>Fichas dos catequizandos</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-switch.css">
  <link rel="stylesheet" href="font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">

  
  
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
  
  <h2> Reprografia</h2>

    
  <div class="row" style="margin-top:20px; "></div>
  
  <div class="row" style="margin-top:20px; "></div>
  
  <form id="form_presencas" action="folhasPresencas.php" method="post">
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
  <li role="presentation" style="cursor: pointer;"><a onclick="document.getElementById('form_presencas').submit();">Folha de presenças</a></li>
  <li role="presentation" style="cursor: pointer;" class="active"><a >Fichas dos catequizandos</a></li>
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
	  	<form id="form-ordem" role="form" action="fichasCatequizandos.php" method="post">
	  		<input type="hidden" name='ano_catequetico' value="<?php echo(intval($_POST['ano_catequetico'])); ?>" />
	  		<input type="hidden" name='catecismo' value="<?php echo(intval($_POST['catecismo'])); ?>" />
	  		<input type="hidden" name='turma' value="<?php echo(Utils::sanitizeInput($_POST['turma'])); ?>" />
	  		<span> <b>Ordem: </b>&nbsp;  <input type="checkbox" id="ordem-modificacao" name="ordem-modificacao" class="my-checkbox" onchange="document.getElementById('form-ordem').submit();" value="1" <?php  if(Utils::sanitizeInput($_POST['ordem-modificacao']) =="1") echo('checked');  ?> /> </span>
	    </form>
	</div>

	<duv class="clearfix"></div>
  </div>

  <div class="row" style="margin-bottom:20px; "></div>
  
   </div>
   
   
</div>  


<?php

    $db = new PdoDatabaseManager();


	//Carregar variaveis por POST
	if ($_SERVER["REQUEST_METHOD"] == "POST") 
	{
		$ano_catequetico = intval($_POST['ano_catequetico']);
		$catecismo = intval($_POST['catecismo']);
		$turma = Utils::sanitizeInput($_POST['turma']);
		$ordem_mod = Utils::sanitizeInput($_POST['ordem-modificacao']) == "1";
		

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
			
			

			
			//Obter catequistas
            $catequistas = "";
            $catechistsQueryResults = null;
            try
            {
                $catechistsQueryResults = $db->getGroupCatechists($ano_catequetico, $catecismo, $turma);
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }

			if (isset($catechistsQueryResults) && count($catechistsQueryResults)>=1)
			{
				$count=0;
				foreach($catechistsQueryResults as $row)
				{
					if($count!=0)
						$catequistas = $catequistas . ", ";
					$catequistas = $catequistas . " " . Utils::sanitizeOutput($row['nome']);
					$count++;
				}
			}
			else
			{
				$catequistas = "<i>Por definir</i>";
			}

			//Libertar recursos
            $catechistsQueryResults = null;
			
		
		
			//Obter catequizandos
            $catechumens = null;
            try
            {
                if($ordem_mod)
                    $orderBy = OrderCatechumensBy::LAST_CHANGED;
                else
                    $orderBy = OrderCatechumensBy::NAME_BIRTHDATE;

                $catechumens = $db->getCatechumensByCatechismWithFilters(Utils::currentCatecheticalYear(), $ano_catequetico, $catecismo, $turma, false, $orderBy);
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }

			
			if (count($catechumens) >= 1)
			{
				foreach($catechumens as $row)
				{

					$cid = intval($row['cid']);

                    $nome = Utils::sanitizeOutput($row['nome']);
                    $data_nasc = Utils::sanitizeOutput($row['data_nasc']);
                    $fid_pai = intval($row['pai']);
                    $fid_mae = intval($row['mae']);
                    $fid_ee = intval($row['enc_edu']);
                    $enc_edu_quem = Utils::sanitizeOutput($row['enc_edu_quem']);
                    $obs = $row['obs'];
                    $foto = Utils::sanitizeOutput($row['foto']);
                    $autorizou_fotos = intval($row['autorizou_fotos']);

                    $nome_pai = NULL;
                    $nome_mae = NULL;
                    $nome_ee = NULL;
                    $telefone = NULL;
                    $telemovel = NULL;
                    $email = NULL;

                    $baptizado = false;
                    $data_baptismo = NULL;
                    $paroquia_baptismo = NULL;
                    $comunhao = false;
                    $data_comunhao = NULL;
                    $paroquia_comunhao = NULL;


						
                    //Obter dados do pai
                    if(!is_null($fid_pai))
                    {
                        try
                        {
                            $father = $db->getFamilyMember($fid_pai);
                            $nome_pai = Utils::sanitizeOutput($father['nome']);
                        }
                        catch(Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                            die();
                        }
                    }


                    //Obter dados da mae
                    if(!is_null($fid_mae))
                    {
                        try
                        {
                            $mother = $db->getFamilyMember($fid_mae);
                            $nome_mae = Utils::sanitizeOutput($mother['nome']);
                        }
                        catch(Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                            die();
                        }
                    }


                    //Obter dados do encarregado educacao
                    try
                    {
                        $responsible = $db->getFamilyMember($fid_ee);
                        $nome_ee = Utils::sanitizeOutput($responsible['nome']);
                        $telefone = Utils::sanitizeOutput($responsible['telefone']);
                        $telemovel = Utils::sanitizeOutput($responsible['telemovel']);
                        $email = Utils::sanitizeOutput($responsible['email']);
                    }
                    catch(Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        die();
                    }


                    //Obter baptismo
                    try
                    {
                        $baptism = $db->getCatechumenSacramentRecord(Sacraments::BAPTISM, $cid);

                        if($baptism)
                        {
                            $baptizado = true;
                            $data_baptismo = Utils::sanitizeOutput($baptism['data']);
                            $paroquia_baptismo = Utils::sanitizeOutput($baptism['paroquia']);
                        }
                    }
                    catch(Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        die();
                    }



                    //Obter primeira comunhao
                    try
                    {
                        $communion = $db->getCatechumenSacramentRecord(Sacraments::FIRST_COMMUNION, $cid);

                        if($communion)
                        {
                            $comunhao = true;
                            $data_comunhao = Utils::sanitizeOutput($communion['data']);
                            $paroquia_comunhao = Utils::sanitizeOutput($communion['paroquia']);
                        }
                    }
                    catch(Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        die();
                    }




                    //Preencher ficha

                    ?>
						
					
<!-- INICIO FICHA -->
<div class="panel ficha panel-default nao-quebrar">
<div class="panel-body">

<!-- foto -->
<img src="<?php if($foto && $foto!="") echo("resources/catechumenPhoto.php?foto_name=$foto"); else echo("img/default-user-icon-profile.png");?>" class="img-thumbnail pull-right"  alt="Foto do catequizando" width="150" height="150" >

<!--catecismo-->
 <div class="form-group">
    <div class="col-xs-3">
      <label for="nome">Catecismo:</label>
      <span><?php echo("" . $catecismo . "º " . $turma . "");?></span>
    </div>
    
    <!--ano-->
     <div class="col-xs-3">
      <label for="data_nasc">Ano:</label>
      <span><?= Utils::formatCatecheticalYear($ano_catequetico); ?></span>
     </div> 
     
     
     <!--catequistas-->
     <div class="col-xs-9">
      <label for="data_nasc">Catequistas:</label>
      <span><?php echo("" . $catequistas . "");?></span>
     </div> 
     <div class="clearfix"></div>
</div>

<!--nome-->
 <div class="form-group">
    <div class="col-xs-6 breadcrumb nomeFicha">										<!-- Breadcrumb para destacar o nome -->
      <label for="nome">Nome:</label>
      <span><?php echo("" . $nome . "");?></span>
    </div>
    
    <!--data nascimento-->
     <div class="col-xs-4">
      <label for="data_nasc">Nasceu a:</label>
      <span><?php echo("" . date( "d-m-Y", strtotime($data_nasc)) . "");?></span>
     </div> 
     
     
     <!--idade-->
     <div class="col-xs-2">
      <label for="data_nasc">Idade:</label>
      <span><?php echo("" . date_diff(date_create($data_nasc), date_create('today'))->y . "");?></span>
     </div> 
     <div class="clearfix"></div>
</div>



<!--baptismo-->
 <div class="form-group">
    <div class="col-xs-6">
      <label for="baptismo">Baptismo:</label>
      <span><?php if($baptizado){ echo('' . date( "d-m-Y", strtotime($data_baptismo)) . ''); } else { echo("-"); } ?></span>
    </div>

    <!--primeira comunhao-->
    <div class="col-xs-6">
      <label for="baptismo">Primeira comunhão:</label>
      <span><?php if($comunhao){ echo('' . date( "d-m-Y", strtotime($data_comunhao)) . ''); } else { echo("-"); } ?></span>
    </div>
    
     <div class="clearfix"></div>
</div>



<!--encarregado educacao-->   
    <div class="col-xs-6">
    	<label for="encarregado educacao">Encarregado de educação:</label>
    	<span class="input-xlarge uneditable-input"><?php 
    								if($fid_ee==$fid_pai)
    									echo("Pai");
    								else if($fid_ee==$fid_mae)
    									echo("Mae");
    								else
    									echo("" . $enc_edu_quem . "");
    							?></span>
    </div>
    
    
    <?php 
		if($fid_ee!=$fid_pai && $fid_ee!=$fid_mae)
		{
		
	?>
    <div class="col-xs-6">
    	<label for="encarregado educacao_nome">Nome:</label>
    	<span class="input-xlarge uneditable-input"><?php echo("" . $nome_ee . "");?></span>
    </div>
    
    <?php 	} ?>
    
    <div class="clearfix"></div>
      
    
    
    <!--pai-->   
    <div class="col-xs-6">
      <label for="pai">Pai:</label>
      <span><?php echo("" . $nome_pai . "");?></span>
    </div>
   
    
     <!--mae-->
    <div class="col-xs-6">
      <label for="mae">Mãe:</label>
      <span><?php echo("" . $nome_mae . "");?></span>
    </div>
    <div class="clearfix"></div>    
    
     
    <div class="clearfix"></div>
      
    
   </div>
   
   
 
   
   
   
   
   
   <div class="col-xs-6">
   
   
   	<div class="clearfix" style="margin-bottom: 20pt"></div>
   	<!--telefone-->
	    <div class="col-xs-12">
	    <div id="telefone_div">
	      <label for="tel">Telefone:</label>
	      <span><?php echo("" . $telefone . "");?></span>
	    </div>
	    </div>
	    
	    
	    
	    
	<!--telemovel-->
	    <div class="col-xs-12">
	    <div id="telemovel_div">
	      <label for="telm"><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>:</label>
	      <span><?php echo("" . $telemovel . "");?></span>
	    </div>
	    </div>
   
   	<!--email-->
	    <div class="col-xs-12">
	      <label for="email">E-mail:</label>
	      <span><?php echo("" . $email . "");?></span>
	    </div>
	    
	
	<!--autorizacao_foto-->
	<div class="clearfix" style="margin-bottom: 10pt"></div>    
	<div class="col-xs-12">
   	<?php 	if($autorizou_fotos==1)
   			echo('<span class="text-success"><span class="glyphicon glyphicon-ok"></span> Autorizou fotografias do educando.</span>');
   	 	else
   	 		echo('<span class="text-danger"><span class="fas fa-ban"></span> NÃO autorizou fotografias do educando.</span>');
   	 ?>
   	 </div>
   
   <?php
   	echo("</div>");
   	echo('<div class="col-xs-6">');
   ?>
   	
   	<!-- obs -->
   	 <!--<div class="col-xs-12">-->
   		<label for="obs">Observações:</label>
   		<textarea type="text" class="form-control" id="obs" name="obs" cols="20" rows="6" style="cursor: auto;" readonly><?php echo("" . $obs . "");?></textarea>
   	<!--</div>-->
   
   
   </div>
   <div class="clearfix" style="margin-bottom: 20pt"></div>
   
   
</div>
<!--</div>-->
<!-- FIM DA FICHA -->





   
			<?php
					}

                //Libertar recursos
                $catechumens = null;
 			}
 			else
 				echo("<div class=\"container\"><p>Não há catequizandos inscritos neste grupo de catequese</p></div>");
		}

	}
?>



   
<div class="no-print">

    <?php

    // Dialog with printing instructions

    $printDialog->setSize(ModalDialogWidget::SIZE_LARGE);
    $printDialog->setTitle("Recomendação");

    $printDialog->setBodyContents(<<<HTML_CODE
        <p>É recomendado que configure a escala de impressão de tal modo que caibam várias fichas em cada página impressa. Utilize a janela de pré-visualização de impressão do seu navegador para ajustar a escala antes de imprimir.</p>
      	<p>Quando clicar em OK abrir-se-á a janela de configuração da impressora.</p>
      	<a style="cursor: pointer;" data-toggle="collapse" data-target="#exemplo">Mostre-me um exemplo <span class="glyphicon glyphicon-chevron-down"></span></a>
      	<div id="exemplo" class="collapse">
      		<div style="overflow: auto;">
      			<img src="img/exemplo_print_fichas.jpg" width=800px>
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