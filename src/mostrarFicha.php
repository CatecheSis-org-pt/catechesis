<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/domain/Locale.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');


use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;
use core\domain\Locale;
use catechesis\Configurator;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHUMENS, true);
$pageUI->addWidget($menu);
$printDialog = new ModalDialogWidget("janelaImprimir");
$pageUI->addWidget($printDialog);
$deleteDialog = new ModalDialogWidget("confirmarEliminarFicha");
$pageUI->addWidget($deleteDialog);


?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Detalhes do catequizando</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <!--<link rel="stylesheet" href="css/bootstrap.min.css">-->
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">
  
  
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

        .panel-heading {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            background-color: #f5f5f5 !important;
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
</head>
<body>

<?php
$menu->renderHTML();
?>

<div class="container" id="contentor">
  <h2> Detalhes do catequizando</h2>
  
  <?php

	$db = new PdoDatabaseManager();

	$cid = intval(Utils::sanitizeInput($_REQUEST['cid']));

	if($cid && $cid>0)
	{
		if(!Authenticator::isAdmin() && !catechumen_belongs_to_catechist($cid, Authenticator::getUsername()))
		{
			echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder aos dados deste catequizando.</div>");
			echo("</div></body></html>");
			die();		
		}
		

		$result = NULL;
		
		$nome = NULL;
        $data_nasc = NULL;
        $nif = NULL;
		$local_nasc = NULL;
		$num_irmaos = NULL;
		$escuteiro = NULL;
		$autorizacao_fotos = NULL;
		$fid_pai = NULL;
		$fid_mae = NULL;
		$fid_ee = NULL;
		$enc_edu_quem = NULL;
		$foto = NULL;
		
		$nome_pai = NULL;
		$prof_pai = NULL;
		$telefone_pai = NULL;
		$telemovel_pai = NULL;
		
		$nome_mae = NULL;
		$prof_mae = NULL;
		$telefone_mae = NULL;
		$telemovel_mae = NULL;
		
		$casados = "Não";
		$casados_como = NULL;
		
		$nome_ee = NULL;
		$prof_ee = NULL;
		$morada = NULL;
		$codigo_postal = NULL;
		$telefone = NULL;
		$telemovel = NULL;
		$email = NULL;
		$rgpd_ee = NULL;
		
		$criado_por = NULL;
		$criado_em = NULL;
		$lastLSN_ficha = NULL;
	
	
		//Obter dados do catequizando
        $catechumen = null;
        try
        {
            $catechumen = $db->getCatechumenById($cid);

            $nome = Utils::sanitizeOutput($catechumen['nome']);
            $data_nasc_row = $catechumen['data_nasc'];
            $data_nasc = Utils::sanitizeOutput($catechumen['data_nasc']);
            $data_nasc = $data_nasc ? date("d-m-Y", strtotime($data_nasc)) : "";
            $nif = intval($catechumen['nif']);
            $local_nasc = Utils::sanitizeOutput($catechumen['local_nasc']);
            $num_irmaos = intval($catechumen['num_irmaos']);
            $escuteiro = Utils::sanitizeOutput($catechumen['escuteiro']);
            $autorizacao_fotos = Utils::sanitizeOutput($catechumen['autorizou_fotos']);
            $fid_pai = (isset($catechumen['pai']))? intval($catechumen['pai']) : null;
            $fid_mae = (isset($catechumen['mae']))? intval($catechumen['mae']) : null;
            $fid_ee = intval($catechumen['enc_edu']);
            $enc_edu_quem = Utils::sanitizeOutput($catechumen['enc_edu_quem']);
            $foto = Utils::sanitizeOutput($catechumen['foto']);
            $criado_por = Utils::sanitizeOutput($catechumen['criado_por_nome']);
            $criado_em = Utils::sanitizeOutput($catechumen['criado_em']);
            $lastLSN_ficha = intval($catechumen['lastLSN_ficha']);
        }
        catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }



		if (isset($catechumen))
		{

			//Obter dados do pai
            if(!is_null($fid_pai))
            {
                try
                {
                    $father = $db->getFamilyMember($fid_pai);
                    $nome_pai = Utils::sanitizeOutput($father['nome']);
                    $prof_pai = Utils::sanitizeOutput($father['prof']);
                    $telefone_pai = Utils::sanitizeOutput($father['telefone']);
                    $telemovel_pai = Utils::sanitizeOutput($father['telemovel']);
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
                    $prof_mae = Utils::sanitizeOutput($mother['prof']);
                    $telefone_mae = Utils::sanitizeOutput($mother['telefone']);
                    $telemovel_mae = Utils::sanitizeOutput($mother['telemovel']);
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                }
            }

			
			
			//Verificar se os pais sao casados
			if(!is_null($fid_mae) && !is_null($fid_pai))
			{
			    try
                {
                    $marriage = $db->getMarriageInformation($fid_mae, $fid_pai);

                    if($marriage)
                    {
                        $casados = "Sim";
                        $casados_como = Utils::sanitizeOutput($marriage['como']);
                    }
                    else
                        $casados = "Não";
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
                $prof_ee = Utils::sanitizeOutput($responsible['prof']);
                $morada = Utils::sanitizeOutput($responsible['morada']);
                $codigo_postal = Utils::sanitizeOutput($responsible['cod_postal']);
                $telefone = Utils::sanitizeOutput($responsible['telefone']);
                $telemovel = Utils::sanitizeOutput($responsible['telemovel']);
                $email = Utils::sanitizeOutput($responsible['email']);
                $rgpd_ee = intval($responsible['RGPD_assinado']);

                //converter para bool
                $rgpd_ee = (!is_null($rgpd_ee) && $rgpd_ee == 1);
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }

			
			
			
			//Obter ultima modificacao do log
			$modificou_quem = NULL;
			$modificou_data = NULL;
            try
            {
                $fileLog = $db->getLogEntry($lastLSN_ficha);

                $modificou_quem = Utils::sanitizeOutput($fileLog['nome_modificacao']);
                $modificou_data = Utils::sanitizeOutput($fileLog['data']);
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }

			
			
			//Libertar recursos
			$result = NULL;
		}
		else
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao tentar aceder à ficha do catequizando.</div>");
			die();
		}
	}
	else
	{
		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não foi especificado nenhum catequizando para consultar.</div>");
		die();
	}

?>

  <div class="no-print">
  
  <div class="btn-group" role="group" aria-label="...">
    <button type="button" class="btn btn-default glyphicon glyphicon-pencil" onclick="editar()" > Editar</button>
  	<?php
	if(Authenticator::isAdmin())
	{
	?>
  	    <button type="button" class="btn btn-default glyphicon glyphicon-trash" data-toggle="modal" data-target="#confirmarEliminarFicha" onclick="" > Eliminar</button>
	<?php
	}
	?>  
  	<button type="button" class="btn btn-default glyphicon glyphicon-print" data-toggle="modal" data-target="#janelaImprimir" onclick=""> Imprimir</button>
  	<button type="button" class="btn btn-default glyphicon glyphicon-download-alt" onclick="transferir_ficha()"> Transferir</button>
  </div>
    
  <div class="row" style="margin-top:20px; "></div>
    
  <ul class="nav nav-tabs">
  <li role="presentation" class="active"><a href="mostrarFicha.php?cid=<?php echo('' . $cid . ''); ?>">Ficha</a></li>
  <li role="presentation"><a href="mostrarArquivo.php?cid=<?php echo('' . $cid . ''); ?>">Arquivo</a></li>
  <li role="presentation"><a href="mostrarAutorizacoes.php?cid=<?php echo('' . $cid . ''); ?>">Autorizações</a></li>
  </ul>
 
  </div>
 
 
 
 
  <div class="panel panel-default" id="painel_ficha">
   <div class="panel-body">
  
	
	
	  <div class="container" id="contentor_foto" >	  	
	  	<img src="<?php if($foto && $foto!="") echo("resources/catechumenPhoto.php?foto_name=$foto"); else echo("img/default-user-icon-profile.png");?>" class="img-thumbnail "  alt="Foto do catequizando" width="200" height="150" >
	  </div>
	 

	<div class="row" style="margin-top:20px; "></div>
    
  
    
   <div class="panel panel-default">
   <div class="panel-heading">Dados pessoais do catequizando</div>
   <div class="panel-body">
   
   
   
   
	 <!--nome-->
	 <div class="form-group">
	    <div class="col-xs-6">
	      <label for="nome">Nome:</label>
	      <input class="form-control" id="nome" name="nome" size="16" type="text" style="cursor: auto;" 
	      	<?php if($nome){ echo("value='" . $nome . "'");}?> readonly>
	    </div>
	    
	    	    
	   <!--data nascimento-->
	     <div class="col-xs-2">
	      <label for="data_nasc">Nasceu a:</label>
	      <input class="form-control" id="data_nasc" name="data_nasc" size="16" type="text" style="cursor: auto;" 
	      	<?php 
	      		if($data_nasc_row)
	      		{
	      			 echo("value='" . $data_nasc . "'");
	      		}
	      	?> readonly>
	     </div>    
	    
	    
	    
	   <!--local nascimento-->
	    <div class="col-xs-3">
	      <label for="localidade">Em:</label>
	      <input type="text" class="form-control" id="localidade" name="localidade" style="cursor: auto;" 
	      	<?php if($local_nasc){ echo("value='" . $local_nasc . "'");}?> readonly>
	    </div>
	    
	    
	     <!--numero irmaos-->
	   <div class="col-xs-1">
	    <div id="num_irmaos_div">
	      <label for="num_irmaos">Irmãos:</label>
	      <input type="text" min=0 class="form-control" id="num_irmaos" name="num_irmaos" style="cursor: auto;" 
	      	<?php if($num_irmaos){ echo("value='" . $num_irmaos . "'");} else {echo("value='0'");}?> readonly>
	    </div>
	    </div>   
	    <div class="clearfix"></div>
	    </div>
	    
	    
	   	    
	    
	       <!--escuteiro-->
	   <div class="form-group">
	    <div class="col-xs-6">
	    	<label for="e_escuteiro">É escuteiro(a):</label>
	    	<span class="input-xlarge uneditable-input"> <?php if($escuteiro==1 || $escuteiro=='Sim'){ echo("Sim");} else { echo("Não");}?> </span>
	    </div>
	    <!--<div class="clearfix"></div>-->
	    </div>
	    
	    
       <!--idade-->
	   <div class="form-group">
	    <div class="col-xs-2">
	    	<label for="idade">Idade:</label><br>
	    	<span class="input-xlarge uneditable-input">
	    	<?= date_diff(date_create($data_nasc_row), date_create('today'))->y	?> anos
	    	</span>
	    </div>
       </div>

       <?php if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED)) { ?>
       <!--NIF-->
       <div class="col-xs-3">
           <label for="nif">NIF:</label>
           <input type="text" class="form-control" id="nif" name="nif" style="cursor: auto;"
               <?php if($nif){ echo("value='" . $nif . "'");}?> readonly>
           <div class="clearfix"></div>
       </div>
       <?php } ?>
    
    </div>
   </div>

    


    <div class="panel panel-default">
    <div class="panel-heading">Informação de contacto</div>
    <div class="panel-body">
    
	   <!--morada-->
	    <div class="form-group">
	    <div class="col-xs-12">
	      <label for="morada">Morada:</label>
	      <input type="text" class="form-control" id="morada" name="morada"  style="cursor: auto;" 
	      	<?php if($morada){ echo("value='" . $morada . "'");}?>  readonly>
	    </div>
	  
	    
	    
	    
	    <!--codigo postal-->
	    <div class="col-xs-4">
	    <div id="codigo_postal_div">
	      <label for="codigo_postal">Código postal:</label>
	      <input type="text" class="form-control" id="codigo_postal" name="codigo_postal"  style="cursor: auto;"
	      	<?php if($codigo_postal){ echo("value='" . $codigo_postal . "'");}?>  readonly>
	    </div>
	    </div>
	    <div class="clearfix"></div>
	   </div>
	    
	    
	    
	    
	    <!--encarregado educacao-->
	   <div class="form-group">
	    <div class="col-xs-8">
	    	<label for="encarregado educacao">Encarregado de educação:</label>
	    	<span class="input-xlarge uneditable-input">  
	    		<?php 
	    			if($fid_ee==$fid_pai)
	    			{ 
	    				echo("Pai");
	    			}
	    			else if($fid_ee==$fid_mae)
	    			{ 
	    				echo("Mãe");
	    			}
	    			else
	    			{
	    				echo("" . $enc_edu_quem . "");
	    			}
	    			
	    		?>
	    	</span>
	    </div>
	    <div class="clearfix"></div>
	    </div>
	    
	    
	    <?php
	    	
	    	if($fid_ee!=$fid_pai && $fid_ee!=$fid_mae)
	    	{
	    
	    		echo('<div class="col-xs-8">');
	    		echo(' <label for="nome_end_edu"> Nome:</label>');
	    		echo(' <input type="text" class="form-control" id="nome_enc_edu" name="nome_enc_edu" style="cursor: auto;" value="' . $nome_ee . '" readonly>');
		   	echo('</div>');
		    
		   
		   	echo('<div class="col-xs-4">');
		   	echo('  <label for="prof_enc_edu"> Profissão:</label>');
		   	echo('  <input type="text" class="form-control" id="prof_enc_edu" name="prof_enc_edu" style="cursor: auto;"  value="' . $prof_ee . '" readonly>');
		   	echo('</div>');
		   	echo('<div class="clearfix"></div>');
	    
	    	}
	    
	    
	    ?>
	    
	    <!--email-->  
	    <div class="form-group">
	    <div class="col-xs-4">
	      <label for="email">E-mail:</label>
	      <div class="input-group">
	      <input type="email" class="form-control" id="email" name="email"  style="cursor: auto;" 
	      	<?php if($email){ echo("value='" . $email . "'");}?> readonly>
	      	<span class="input-group-addon no-print"><?php if($email){ echo('<a href="mailto:' . $email . '"><span class="glyphicon glyphicon-envelope"></span></a>');}?></span>
	      </div>
	    </div>
	    
	    
	    
	    <!--telemovel-->
	    <div class="col-xs-2">
	    <div id="telemovel_div">
	      <label for="telm"><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>:</label>
	      <div class="input-group">
	      <input type="tel" class="form-control" id="telemovel" name="telemovel"  style="cursor: auto;" 
	      	<?php if($telemovel){ echo("value='" . $telemovel . "'");}?> readonly>
	      	<span class="input-group-addon no-print"><?php if($telemovel){ echo('<a href="tel:' . $telemovel . '"><span class="glyphicon glyphicon-earphone"></span></a>');}?></span>
	      </div>
	    </div>
	    </div>   
	    
	    
	    
	    <!--telefone-->
	    <div class="col-xs-2">
	    <div id="telefone_div">
	      <label for="tel">Telefone:</label>
	      <div class="input-group">
	      <input type="tel" class="form-control" id="telefone" name="telefone"  style="cursor: auto;" 
	      	<?php if($telefone){ echo("value='" . $telefone . "'");}?> readonly>
	      <span class="input-group-addon no-print"><?php if($telefone){ echo('<a href="tel:' . $telefone . '"><span class="glyphicon glyphicon-earphone"></span></a>');}?></span>
	      </div>
	    </div>
	    </div>   
	    <div class="clearfix"></div>
	    </div>
	
	</div>
	</div>
   

    
    
    
    <div class="panel panel-default">
    <div class="panel-heading">Pais</div>
    <div class="panel-body">    
    
    
	    <!--pai-->
	    <div class="form-group">
	    <div class="col-xs-4">
	      <label for="pai">Pai:</label>
	      <input type="text" class="form-control" id="pai" name="pai"  style="cursor: auto;" 
	      	<?php if($nome_pai){ echo("value='" . $nome_pai . "'");}?> readonly>
	    </div>
	    
	    <!--profissao pai-->
	    <div class="col-xs-4">
	      <label for="prof_pai">Profissão:</label>
	      <input type="text" class="form-control" id="prof_pai" name="prof_pai"  style="cursor: auto;" 
	      	<?php if($prof_pai){ echo("value='" . $prof_pai . "'");}?> readonly>
	    </div>

	    <!--telemovel pai-->
	    <div class="col-xs-2">
	    <div id="telemovel_pai_div">
	      <label for="telemovel_pai"><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>:</label>
	      <div class="input-group">
	      <input type="tel" class="form-control" id="telemovel_pai" name="telemovel_pai"  style="cursor: auto;" 
	      	<?php if($telemovel_pai){ echo("value='" . $telemovel_pai . "'");}?> readonly>
	      	<span class="input-group-addon no-print"><?php if($telemovel_pai){ echo('<a href="tel:' . $telemovel_pai . '"><span class="glyphicon glyphicon-earphone"></span></a>');}?></span>
	      </div>
	    </div>
	    </div>

	    <!--telefone pai-->
	    <div class="col-xs-2">
	    <div id="telefone_pai_div">
	      <label for="telefone_pai">Telefone:</label>
	      <div class="input-group">
	      <input type="tel" class="form-control" id="telefone_pai" name="telefone_pai"  style="cursor: auto;" 
	      	<?php if($telefone_pai){ echo("value='" . $telefone_pai . "'");}?> readonly>
	      <span class="input-group-addon no-print"><?php if($telefone_pai){ echo('<a href="tel:' . $telefone_pai . '"><span class="glyphicon glyphicon-earphone"></span></a>');}?></span>
	      </div>
	    </div>
	    </div>
	    <div class="clearfix"></div>
	    </div>
	    
	    
	    
	    <!--mae-->
	    <div class="form-group">
	    <div class="col-xs-4">
	      <label for="mae">Mãe:</label>
	      <input type="text" class="form-control" id="mae" name="mae"  style="cursor: auto;" 
	      	<?php if($nome_mae){ echo("value='" . $nome_mae . "'");}?> readonly>
	    </div>
	    
	    <!--profissao mae-->
	    <div class="col-xs-4">
	      <label for="prof_mae">Profissão:</label>
	      <input type="text" class="form-control" id="prof_mae" name="prof_mae"  style="cursor: auto;" 
	      	<?php if($prof_mae){ echo("value='" . $prof_mae . "'");}?> readonly>
	    </div>

	    <!--telemovel mae-->
	    <div class="col-xs-2">
	    <div id="telemovel_mae_div">
	      <label for="telemovel_mae"><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>:</label>
	      <div class="input-group">
	      <input type="tel" class="form-control" id="telemovel_mae" name="telemovel_mae"  style="cursor: auto;" 
	      	<?php if($telemovel_mae){ echo("value='" . $telemovel_mae . "'");}?> readonly>
	      	<span class="input-group-addon no-print"><?php if($telemovel_mae){ echo('<a href="tel:' . $telemovel_mae . '"><span class="glyphicon glyphicon-earphone"></span></a>');}?></span>
	      </div>
	    </div>
	    </div>

	    <!--telefone mae-->
	    <div class="col-xs-2">
	    <div id="telefone_mae_div">
	      <label for="telefone_mae">Telefone:</label>
	      <div class="input-group">
	      <input type="tel" class="form-control" id="telefone_mae" name="telefone_mae"  style="cursor: auto;" 
	      	<?php if($telefone_mae){ echo("value='" . $telefone_mae . "'");}?> readonly>
	      <span class="input-group-addon no-print"><?php if($telefone_mae){ echo('<a href="tel:' . $telefone_mae . '"><span class="glyphicon glyphicon-earphone"></span></a>');}?></span>
	      </div>
	    </div>
	    </div>
	    <div class="clearfix"></div>
	    </div>
	    
	    
	    
	    <!--casados-->
	    <div class="form-group">
	    <div class="col-xs-8">
	    	<label for="casados">Casados:</label>
	    	<span class="input-xlarge uneditable-input">
	    		<?php
	    		 	if($casados=="Sim")
	    		 	{
	    		 		$casados_como_display = $casados_como;
	    		 		if($casados_como_display=="uniao de facto")
						$casados_como_display = "união de facto";
						
	    		 		echo("Sim, " . $casados_como_display . "");
	    		 	}
	    		 	else
	    		 		echo("Não");
	    		 ?>
	    	</span>
	    </div>
	    <div class="clearfix"></div>
	    </div>
     
     
     </div>
     </div>



       <div class="panel panel-default">
           <div class="panel-heading">Outros</div>
           <div class="panel-body">
           <?php

        //Autorizacao photos
       	if($autorizacao_fotos==1 || $autorizacao_fotos=='on' || $autorizacao_fotos=='Sim')
       	{
       		 echo('<span class="text-success"><span class="glyphicon glyphicon-ok"></span> Autoriza a utilização e divulgação de fotografias do educando, tiradas no âmbito das atividades catequéticas.</span>');
       	}
       	else
       		echo('<span class="text-danger"><span class="fas fa-ban"></span> NÃO autoriza a utilização e divulgação de fotografias do educando, tiradas no âmbito das atividades catequéticas.</span>');
        echo('<div class="clearfix"></div>');

       	//Consentimento RGPD
        if($rgpd_ee)
        {
            echo('<span class="text-success"><span class="glyphicon glyphicon-ok"></span> Consentimento do tratamento de dados (RGPD) assinado e entregue.</span>');
        }
        else
            echo('<span class="text-danger"><span class="glyphicon glyphicon-exclamation-sign"></span> NÃO assinou o consentimento de tratamento de dados (RGPD).</span>');

    ?>
           </div>
       </div>
    
    
    <div class="row" style="margin-top:20px; "></div>
    
    <div class="col-xs-6">
    	<span>Inscrito por: &nbsp;<span class="glyphicon glyphicon-user"></span> <?php echo('' . Utils::firstAndLastName($criado_por)); ?> &nbsp;&nbsp;&nbsp; <span class="glyphicon glyphicon-calendar"></span> <?php echo('' . $criado_em); ?></span>
    </div>
    <div class="col-xs-6">
        <span>Última alteração: <?php if($modificou_quem) echo('&nbsp;<span class="glyphicon glyphicon-user"></span> ' . Utils::firstAndLastName($modificou_quem) .' &nbsp;&nbsp;&nbsp; <span class="glyphicon glyphicon-calendar"></span> ' . $modificou_data); else echo('nunca');?></span>
    </div>
   
   <!--
   <div class="row" style="margin-bottom:10px; "></div>
   <div class="col-xs-12">
   <span>ID do catequizando: <?php echo('' . $cid); ?></span>
   </div>
    <div class="clearfix"></div>
    -->
    
     </div>
    </div>
</div>


<!-- Dialogo confirmacao eliminar ficha -->
<?php

	if(Authenticator::isAdmin())
    {
        // Dialogo confirmacao eliminar ficha

        $deleteDialog->setTitle("Confirmar eliminação");

        $deleteDialog->setBodyContents(<<<HTML_CODE
            <p>Se clicar em 'Sim' irá eliminar IRREVERSIVELMENTE a ficha deste catequizando, bem como todo o seu percurso catequético registado no arquivo. Os registos deste catequizando, tais como sacramentos, deixarão de ser contabilizados nas estatísticas da paróquia e não surgirão nas listagens de baptismos, crismas, etc.
            <p>Tem a certeza de que pretende eliminar a ficha deste catequizando?</p>
HTML_CODE
        );

        $deleteDialog->addButton(new Button("Não", ButtonType::SECONDARY))
            ->addButton(new Button("Sim", ButtonType::DANGER, "eliminar_ficha()"));

        $deleteDialog->renderHTML();
    }


// Dialogo imprimir

$printDialog->setTitle("Imprimir");

$printDialog->setBodyContents(<<<HTML_CODE
        <p>A página foi automaticamente configurada para otimizar a legibilidade da ficha impressa.</p>
	    <p>Poderá ainda obter melhores resultados se transferir a ficha e imprimir no Microsoft Word.</p>
	    
	    <div class="clearfix" style="margin-bottom: 20px;"></div>
	    
     <div class="radio">
          <label><input type="radio" name="imprimir_como" id="imprimir_word" value="word" checked> Transferir como documento do Microsoft Word <b><i>[RECOMENDADO]</i></b></label>
     </div>
     <div class="radio">
          <label><input type="radio" name="imprimir_como" id="imprimir_borwser" value="browser"> Imprimir directamente no navegador</label>
     </div>
HTML_CODE
);

$printDialog->addButton(new Button("Cancelar", ButtonType::SECONDARY))
            ->addButton(new Button("OK", ButtonType::PRIMARY, "decisao_impressao()"));

$printDialog->renderHTML();
?>



<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>

<script type="text/javascript">

    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data) 
    {
        var mywindow = window.open('', 'Ficha', 'height=800,width=600');
        mywindow.document.write('<html><head><title>Ficha</title><link rel="stylesheet" href="css/bootstrap.min.css">\
                                    <link rel="stylesheet" href="css/custom-navbar-colors.css">');
        mywindow.document.write('<style>@media print{.no-print, .no-print *  {display: none !important; }   .btn { display: none !important; } @page { size: portrait; } body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }	.panel-heading { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; background-color: #f5f5f5 !important; } }');
        mywindow.document.write('@media screen { .only-print, .only-print * { display: none !important;	} } textarea { resize: vertical; }</style>');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10

        mywindow.addEventListener('load', function () {
            mywindow.print();
            mywindow.close();
        });

        return true;
    }
</script>

<script>
    function editar()
    {
        window.location.assign('inscricao.php?modo=editar&cid=<?php echo($cid); ?>');
    }

	<?php
	if(Authenticator::isAdmin())
	{
	?>
  	function eliminar_ficha()
	{
		window.location.assign('eliminarFicha.php?cid=<?php echo('' . $cid . ''); ?>');
	}
	<?php
    }
    ?>

	function imprimir()
  	{
  		//Solucao antiga --> So funciona no FireFox
  		/*$('#contentor').removeClass('container');
  		$('#contentor_foto').removeClass('container');
  		window.print();
  		$('#contentor').addClass('container');
  		$('#contentor_foto').addClass('container');*/
  		
  		//Nova solucao
  		PrintElem(document.getElementById('painel_ficha'));
  	}
  	
  	
	function transferir_ficha()
    {
		window.open('gerarFichaWord.php?cid=<?php echo($cid); ?>', '_blank');
	}
	
	function decisao_impressao()
	{
		var decisao_word = document.getElementsByName('imprimir_como')[0].checked;
		var decisao_browser = document.getElementsByName('imprimir_como')[1].checked;
		
		$('#janelaImprimir').modal('hide');
		
		if(decisao_word)
			transferir_ficha();
		else
			imprimir();
	}
</script>

</body>
</html>