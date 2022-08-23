<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/Utils.php');
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
		
		$nome_mae = NULL;
		$prof_mae = NULL;
		
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
            $data_nasc = Utils::sanitizeOutput($catechumen['data_nasc']);
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
			
			
			//Passar dados para esta sessao
			
			$_SESSION['nome'] = $nome;
			$_SESSION['data_nasc_row'] = $data_nasc;
			$_SESSION['data_nasc'] = $data_nasc = date( "d-m-Y", strtotime($data_nasc));
			$_SESSION['local_nasc'] = $local_nasc;
			$_SESSION['num_irmaos'] = $num_irmaos;
			$_SESSION['escuteiro'] = $escuteiro;
			$_SESSION['autorizacao'] = $autorizacao_fotos;
			$_SESSION['fid_pai'] = $fid_pai;
			$_SESSION['fid_mae'] = $fid_mae;
			$_SESSION['fid_ee'] = $fid_ee;
			$_SESSION['enc_edu_quem'] = $_SESSION['outro_enc_edu_quem'] = $enc_edu_quem;
			$_SESSION['foto'] = $foto;
			
			$_SESSION['nome_pai'] = $_SESSION['pai'] = $nome_pai;
			$_SESSION['prof_pai'] = $prof_pai;
		
			$_SESSION['nome_mae'] = $_SESSION['mae'] = $nome_mae;
			$_SESSION['prof_mae'] = $prof_mae;
			
			$_SESSION['casados'] = $casados;
			$_SESSION['casados_como'] = $casados_como;
		
			$_SESSION['nome_ee'] = $nome_ee;
			$_SESSION['prof_ee'] = $_SESSION['prof_enc_edu'] = $prof_ee;
			$_SESSION['morada'] = $morada;
			$_SESSION['codigo_postal'] = $_SESSION['cod_postal'] = $codigo_postal;
			$_SESSION['telefone'] = $telefone;
			$_SESSION['telemovel'] = $telemovel;
			$_SESSION['email'] = $email;
            $_SESSION['RGPD_assinado'] = $rgpd_ee;

			$_SESSION['cid'] = $cid;
			
			//Para compatibilidade com o codigo inscricao.php:
			if($fid_ee==$fid_pai)
			{
				$_SESSION['enc_edu'] = "Pai";
				$_SESSION['nome_enc_edu'] = "";
				$_SESSION['prof_enc_edu'] = "";
			}
			else if($fid_ee==$fid_mae)
			{
				$_SESSION['enc_edu'] = "Mae";
				$_SESSION['nome_enc_edu'] = "";
				$_SESSION['prof_enc_edu'] = "";
			}
			else
			{
				$_SESSION['enc_edu'] = "Outro";
				$_SESSION['nome_enc_edu'] = $nome_ee;
				$_SESSION['prof_enc_edu'] = $prof_ee;
			}
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
  <li role="presentation" class="active"><a href="mostrarFicha.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Ficha</a></li>
  <li role="presentation"><a href="mostrarArquivo.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Arquivo</a></li>
  <li role="presentation"><a href="mostrarAutorizacoes.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Autorizações</a></li>
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
	      	<?php if($_SESSION['nome']){ echo("value='" . $_SESSION['nome'] . "'");}?> readonly>
	    </div>
	    
	    	    
	   <!--data nascimento-->
	     <div class="col-xs-2">
	      <label for="data_nasc">Nasceu a:</label>
	      <input class="form-control" id="data_nasc" name="data_nasc" size="16" type="text" style="cursor: auto;" 
	      	<?php 
	      		if($_SESSION['data_nasc_row'])
	      		{
	      			 echo("value='" . $_SESSION['data_nasc'] . "'");
	      		}
	      	?> readonly>
	     </div>    
	    
	    
	    
	   <!--local nascimento-->
	    <div class="col-xs-3">
	      <label for="localidade">Em:</label>
	      <input type="text" class="form-control" id="localidade" name="localidade" style="cursor: auto;" 
	      	<?php if($_SESSION['local_nasc']){ echo("value='" . $_SESSION['local_nasc'] . "'");}?> readonly>
	    </div>
	    
	    
	     <!--numero irmaos-->
	   <div class="col-xs-1">
	    <div id="num_irmaos_div">
	      <label for="num_irmaos">Irmãos:</label>
	      <input type="text" min=0 class="form-control" id="num_irmaos" name="num_irmaos" style="cursor: auto;" 
	      	<?php if($_SESSION['num_irmaos']){ echo("value='" . $_SESSION['num_irmaos'] . "'");} else {echo("value='0'");}?> readonly>
	    </div>
	    </div>   
	    <div class="clearfix"></div>
	    </div>
	    
	    
	   	    
	    
	       <!--escuteiro-->
	   <div class="form-group">
	    <div class="col-xs-6">
	    	<label for="e_escuteiro">É escuteiro(a):</label>
	    	<span class="input-xlarge uneditable-input"> <?php if($_SESSION['escuteiro']==1){ echo("Sim");} else { echo("Não");}?> </span>
	    </div>
	    <!--<div class="clearfix"></div>-->
	    </div>
	    
	    
	     <!--idade-->
	   <div class="form-group">
	    <div class="col-xs-6">
	    	<label for="idade">Idade:</label>
	    	<span class="input-xlarge uneditable-input">
	    	<?php
	    		echo("" . date_diff(date_create($_SESSION['data_nasc_row']), date_create('today'))->y . "");
	    	?>
	    	</span>
	    </div>
	    <div class="clearfix"></div>
	    </div>
    
    
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
	      	<?php if($_SESSION['morada']){ echo("value='" . $_SESSION['morada'] . "'");}?>  readonly>
	    </div>
	  
	    
	    
	    
	    <!--codigo postal-->
	    <div class="col-xs-4">
	    <div id="codigo_postal_div">
	      <label for="codigo_postal">Código postal:</label>
	      <input type="text" class="form-control" id="codigo_postal" name="codigo_postal"  style="cursor: auto;"
	      	<?php if($_SESSION['codigo_postal']){ echo("value='" . $_SESSION['codigo_postal'] . "'");}?>  readonly>
	    </div>
	    </div>
	    
	    
	    
	    <!--telefone-->
	    <div class="col-xs-2">
	    <div id="telefone_div">
	      <label for="tel">Telefone:</label>
	      <div class="input-group">
	      <input type="tel" class="form-control" id="telefone" name="telefone"  style="cursor: auto;" 
	      	<?php if($_SESSION['telefone']){ echo("value='" . $_SESSION['telefone'] . "'");}?> readonly>
	      <span class="input-group-addon no-print"><?php if($_SESSION['telefone']){ echo('<a href="tel:' . $_SESSION['telefone'] . '"><span class="glyphicon glyphicon-earphone"></span></a>');}?></span>
	      </div>
	    </div>
	    </div>
	    
	    
	    
	    <!--telemovel-->
	    <div class="col-xs-2">
	    <div id="telemovel_div">
	      <label for="telm">Telemóvel:</label>
	      <div class="input-group">
	      <input type="tel" class="form-control" id="telemovel" name="telemovel"  style="cursor: auto;" 
	      	<?php if($_SESSION['telemovel']){ echo("value='" . $_SESSION['telemovel'] . "'");}?> readonly>
	      	<span class="input-group-addon no-print"><?php if($_SESSION['telemovel']){ echo('<a href="tel:' . $_SESSION['telemovel'] . '"><span class="glyphicon glyphicon-earphone"></span></a>');}?></span>
	      </div>
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
	    			if($_SESSION['fid_ee']==$_SESSION['fid_pai'])
	    			{ 
	    				echo("Pai");
	    			}
	    			else if($_SESSION['fid_ee']==$_SESSION['fid_mae'])
	    			{ 
	    				echo("Mãe");
	    			}
	    			else
	    			{
	    				echo("" . $_SESSION['enc_edu_quem'] . "");
	    			}
	    			
	    		?>
	    	</span>
	    </div>
	    <div class="clearfix"></div>
	    </div>
	    
	    
	    <?php
	    	
	    	if($_SESSION['fid_ee']!=$_SESSION['fid_pai'] && $_SESSION['fid_ee']!=$_SESSION['fid_mae'])
	    	{
	    
	    		echo('<div class="col-xs-8">');
	    		echo(' <label for="nome_end_edu"> Nome:</label>');
	    		echo(' <input type="text" class="form-control" id="nome_enc_edu" name="nome_enc_edu" style="cursor: auto;" value="' . $_SESSION['nome_ee'] . '" readonly>');
		   	echo('</div>');
		    
		   
		   	echo('<div class="col-xs-4">');
		   	echo('  <label for="prof_enc_edu"> Profissão:</label>');
		   	echo('  <input type="text" class="form-control" id="prof_enc_edu" name="prof_enc_edu" style="cursor: auto;"  value="' . $_SESSION['prof_ee'] . '" readonly>');
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
	      	<?php if($_SESSION['email']){ echo("value='" . $_SESSION['email'] . "'");}?> readonly>
	      	<span class="input-group-addon no-print"><?php if($_SESSION['email']){ echo('<a href="mailto:' . $_SESSION['email'] . '"><span class="glyphicon glyphicon-envelope"></span></a>');}?></span>
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
	    <div class="col-xs-8">
	      <label for="pai">Pai:</label>
	      <input type="text" class="form-control" id="pai" name="pai"  style="cursor: auto;" 
	      	<?php if($_SESSION['nome_pai']){ echo("value='" . $_SESSION['nome_pai'] . "'");}?> readonly>
	    </div>
	    
	    <!--profissao pai-->
	    <div class="col-xs-4">
	      <label for="prof_pai">Profissão:</label>
	      <input type="text" class="form-control" id="prof_pai" name="prof_pai"  style="cursor: auto;" 
	      	<?php if($_SESSION['prof_pai']){ echo("value='" . $_SESSION['prof_pai'] . "'");}?> readonly>
	    </div>
	    </div>
	    
	    
	    
	    <!--mae-->
	    <div class="form-group">
	    <div class="col-xs-8">
	      <label for="mae">Mãe:</label>
	      <input type="text" class="form-control" id="mae" name="mae"  style="cursor: auto;" 
	      	<?php if($_SESSION['nome_mae']){ echo("value='" . $_SESSION['nome_mae'] . "'");}?> readonly>
	    </div>
	    
	    <!--profissao mae-->
	    <div class="col-xs-4">
	      <label for="prof_mae">Profissão:</label>
	      <input type="text" class="form-control" id="prof_mae" name="prof_mae"  style="cursor: auto;" 
	      	<?php if($_SESSION['prof_mae']){ echo("value='" . $_SESSION['prof_mae'] . "'");}?> readonly>
	    </div>
	    <div class="clearfix"></div>
	    </div>
	    
	    
	    
	    <!--casados-->
	    <div class="form-group">
	    <div class="col-xs-8">
	    	<label for="casados">Casados:</label>
	    	<span class="input-xlarge uneditable-input">
	    		<?php
	    		 	if($_SESSION['casados']=="Sim")
	    		 	{
	    		 		$casados_como = $_SESSION['casados_como'];
	    		 		if($casados_como=="uniao de facto")
						$casados_como = "união de facto";
						
	    		 		echo("Sim, " . $casados_como . "");
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
       	if($_SESSION['autorizacao']==1)
       	{
       		 echo('<span class="text-success"><span class="glyphicon glyphicon-ok"></span> Autoriza a utilização e divulgação de fotografias do educando, tiradas no âmbito das actividades catequéticas.</span>');
       	}
       	else
       		echo('<span class="text-danger"><span class="fas fa-ban"></span> NÃO autoriza a utilização e divulgação de fotografias do educando, tiradas no âmbito das actividades catequéticas.</span>');
        echo('<div class="clearfix"></div>');

       	//Consentimento RGPD
        if($_SESSION['RGPD_assinado'])
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
        <p>Dependendo das configurações de impressão do seu navegador, o conteúdo da ficha poderá não ser totalmente legível numa página A4.</p>
	    <p>Obterá melhores resultados se transferir a ficha e imprimir no Microsoft Word.</p>
	    
	    <div class="clearfix" style="margin-bottom: 20px;"></div>
	    
	    <p><input type="radio" name="imprimir_como" id="imprimir_word" value="word" checked> Transferir como documento do Microsoft Word <b><i>[RECOMENDADO]</i></b></p>
	    <p><input type="radio" name="imprimir_como" id="imprimir_borwser" value="browser"> Imprimir directamente no navegador</p>
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
        mywindow.document.write('<style>@media print{.no-print, .no-print *  {display: none !important; }   .btn { display: none !important; }	}');
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
        window.location.assign('inscricao.php?modo=editar');
    }

	<?php
	if(Authenticator::isAdmin())
	{
	?>
  	function eliminar_ficha()
	{
		window.location.assign('eliminarFicha.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>');
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