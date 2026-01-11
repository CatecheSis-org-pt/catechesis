<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/log_functions.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . '/core/DataValidationUtils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . "/core/domain/Locale.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');
require_once(__DIR__ . '/gui/widgets/SacramentRecordPanel/SacramentRecordPanelWidget.php');

use catechesis\DataValidationUtils;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use core\domain\Sacraments;
use catechesis\Utils;
use catechesis\UserData;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\SacramentRecordPanelWidget;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;
use core\domain\Locale;



// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHUMENS, true);
$pageUI->addWidget($menu);
$deleteCatDialog = new ModalDialogWidget("confirmarEliminarCat");
$pageUI->addWidget($deleteCatDialog);
$deleteSchoolDialog = new ModalDialogWidget("confirmarEliminarEsc");
$pageUI->addWidget($deleteSchoolDialog);
$confirmPaymentDialog = new ModalDialogWidget("confirmarPago");
$pageUI->addWidget($confirmPaymentDialog);

$baptismPanel = new SacramentRecordPanelWidget();
$pageUI->addWidget($baptismPanel);
$firstComunionPanel = new SacramentRecordPanelWidget();
$pageUI->addWidget($firstComunionPanel);
$professionOfFaithPanel = new SacramentRecordPanelWidget();
$pageUI->addWidget($professionOfFaithPanel);
$chrismationPanel = new SacramentRecordPanelWidget();
$pageUI->addWidget($chrismationPanel);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Detalhes do catequizando</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">
  <link rel="stylesheet" href="css/jquery.fileupload.css">
  <link rel="stylesheet" href="css/dropzone.css">
  
  
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

        .panel-success > .panel-heading {
            background-color: #dff0d8 !important;
        }
	    
	    .btn
	    {
	    	display: none !important;
	    }
	    /*@page {*/
		    /*size: 297mm 210mm*/; /* landscape */
		    /* you can also specify margins here: */
		    /*margin: 35mm;*/
		    /*margin-right: 45mm;*/ /* for compatibility with both A4 and Letter */
		  /*}*/
		  
	}
	
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
	}
	
	textarea { resize: vertical; }
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
  </style>


<style>
	@media print
	{
		a[href]:after			/* Nao imprimir os links href */
		{
			content:none
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
    $cid = intval(Utils::sanitizeInput($_REQUEST['cid']));

    $db = new PdoDatabaseManager();

	
	if($cid && $cid>0)
	{
		if(!Authenticator::isAdmin() && !catechumen_belongs_to_catechist($cid, Authenticator::getUsername()))
		{
			
			echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder aos dados deste catequizando.</div>");
			echo("</div></body></html>");
			die();		
		}


		$result = NULL;
		$op_eliminar_sacramento = false;
		$sacramento_eliminado = NULL;
		
		$nome = NULL;
		$data_nasc = NULL;
		$local_nasc = NULL;
		$num_irmaos = NULL;
		$escuteiro = NULL;
		$obs = NULL;
		$foto = NULL;
		
		$baptizado = false;
		$data_baptismo = NULL;
		$paroquia_baptismo = NULL;
		$comprovativo_baptismo = NULL;
		
		$comunhao = false;
		$data_comunhao = NULL;
		$paroquia_comunhao = NULL;
		$comprovativo_comunhao = NULL;
		
		$profissaoFe = false;
		$data_profissaoFe = NULL;
		$paroquia_profissaoFe = NULL;
		$comprovativo_profissaoFe = NULL;
		
		$crismado = false;
		$data_crisma = NULL;
		$paroquia_crisma = NULL;
		$comprovativo_confirmacao = NULL;
		
		$criado_por = NULL;
		$criado_em = NULL;
		$lastLSN_arquivo = NULL;
		
	
	
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
            $foto = Utils::sanitizeOutput($catechumen['foto']);
            $obs = $catechumen['obs'];                                              //Do not sanitizeOutput because it was already done when "obs" was saved, and we want to keep formating
            $criado_por = Utils::sanitizeOutput($catechumen['criado_por_nome']);
            $criado_em = Utils::sanitizeOutput($catechumen['criado_em']);
            $lastLSN_arquivo = intval($catechumen['lastLSN_arquivo']);
        }
        catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }


		if ($catechumen)
		{
			//Obter baptismo
            try
            {
                $baptism = $db->getCatechumenSacramentRecord(Sacraments::BAPTISM, $cid);

                if($baptism)
                {
                    $baptizado = true;
                    $data_baptismo = Utils::sanitizeOutput($baptism['data']);
                    $paroquia_baptismo = Utils::sanitizeOutput($baptism['paroquia']);
                    $comprovativo_baptismo = Utils::sanitizeOutput($baptism['comprovativo']);
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
                    $comprovativo_comunhao = Utils::sanitizeOutput($communion['comprovativo']);
                }
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }


			
			//Obter profissao fe
            try
            {
                $professionOfFaith = $db->getCatechumenSacramentRecord(Sacraments::PROFESSION_OF_FAITH, $cid);

                if($professionOfFaith)
                {
                    $profissaoFe = true;
                    $data_profissaoFe = Utils::sanitizeOutput($professionOfFaith['data']);
                    $paroquia_profissaoFe = Utils::sanitizeOutput($professionOfFaith['paroquia']);
                    $comprovativo_profissaoFe = Utils::sanitizeOutput($professionOfFaith['comprovativo']);
                }
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }

			

			//Obter crisma
            try
            {
                $chrismation = $db->getCatechumenSacramentRecord(Sacraments::CHRISMATION, $cid);

                if($chrismation)
                {
                    $crismado = true;
                    $data_crisma = Utils::sanitizeOutput($chrismation['data']);
                    $paroquia_crisma = Utils::sanitizeOutput($chrismation['paroquia']);
                    $comprovativo_confirmacao = Utils::sanitizeOutput($chrismation['comprovativo']);
                }
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
                $fileLog = $db->getLogEntry($lastLSN_arquivo);

                $modificou_quem = Utils::sanitizeOutput($fileLog['nome_modificacao']);
                $modificou_data = Utils::sanitizeOutput($fileLog['data']);
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }

			
			//Passar dados para esta sessao

			$_SESSION['nome'] = $nome;
			$_SESSION['data_nasc'] = $data_nasc;
			$_SESSION['local_nasc'] = $local_nasc;
			$_SESSION['num_irmaos'] = $num_irmaos;
			$_SESSION['escuteiro'] = $escuteiro;
			$_SESSION['obs'] = $obs;
			$_SESSION['foto'] = $foto;
		
			$_SESSION['baptizado'] = $baptizado;
			$_SESSION['data_baptismo'] = $data_baptismo;
			$_SESSION['paroquia_baptismo'] = $paroquia_baptismo;
			$_SESSION['comprovativo_baptismo'] = $comprovativo_baptismo;
		
			$_SESSION['comunhao'] = $comunhao;
			$_SESSION['data_comunhao'] = $data_comunhao;
			$_SESSION['paroquia_comunhao'] = $paroquia_comunhao;
			$_SESSION['comprovativo_comunhao'] = $comprovativo_comunhao;
		
			$_SESSION['profissaoFe'] = $profissaoFe;
			$_SESSION['data_profissaoFe'] = $data_profissaoFe;
			$_SESSION['paroquia_profissaoFe'] = $paroquia_profissaoFe;
			$_SESSION['comprovativo_profissaoFe'] = $comprovativo_profissaoFe;
		
			$_SESSION['crismado'] = $crismado;
			$_SESSION['data_crisma'] = $data_crisma;
			$_SESSION['paroquia_crisma'] = $paroquia_crisma;
			$_SESSION['comprovativo_confirmacao'] = $comprovativo_confirmacao;
			
			$_SESSION['cid'] = $cid;


            // Configure sacrament panels
            $baptismPanel->setData(Sacraments::BAPTISM, $_SESSION['cid'], $_SESSION['baptizado'], $_SESSION['data_baptismo'], $_SESSION['paroquia_baptismo'], $_SESSION['comprovativo_baptismo']);
            $firstComunionPanel->setData(Sacraments::FIRST_COMMUNION, $_SESSION['cid'], $_SESSION['comunhao'], $_SESSION['data_comunhao'], $_SESSION['paroquia_comunhao'], $_SESSION['comprovativo_comunhao']);
            $professionOfFaithPanel->setData(Sacraments::PROFESSION_OF_FAITH, $_SESSION['cid'], $_SESSION['profissaoFe'], $_SESSION['data_profissaoFe'], $_SESSION['paroquia_profissaoFe'], $_SESSION['comprovativo_profissaoFe']);
            $chrismationPanel->setData(Sacraments::CHRISMATION, $_SESSION['cid'], $_SESSION['crismado'], $_SESSION['data_crisma'], $_SESSION['paroquia_crisma'], $_SESSION['comprovativo_confirmacao']);


			// Apply user requested changes (if any)

            // These changes can only be made by administrators
			if(Authenticator::isAdmin())
			{

				//Enroll in a catechesis group
				if($_REQUEST['op']=="inscrever")
				{
					$ins_ano_catequetico = intval($_POST['ano_catequetico']);
					$ins_catecismo_turma = Utils::sanitizeInput($_POST['catecismo']);
					$ins_pago = Utils::sanitizeInput($_POST['pago']);
					$ins_passa = Utils::sanitizeInput($_POST['transita']);

                    $ins_pago = ($ins_pago=="Sim");
                    $ins_passa = ($ins_passa!="reprovado");
					
					$matches = NULL;	
					preg_match('/^([0-9]+)º(.*)$/', $ins_catecismo_turma, $matches);		
					$ins_catecismo = intval($matches[1]);
					$ins_turma = $matches[2];

					
					if($ins_ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
					{
						echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Inscrição cancelada.</div>");
					}
					else if($ins_catecismo <= 0 || $ins_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
					{
						echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Inscrição cancelada.</div>");
					}
					else
					{
						try
						{
							if($db->enrollCatechumenInGroup($cid, $ins_ano_catequetico, $ins_catecismo, $ins_turma,
                                                            $ins_passa, $ins_pago, Authenticator::getUsername()))
							{
									catechumenArchiveLog($cid, "Catequizando com id=" . $cid . " inscrito no " . $ins_catecismo . "º" . $ins_turma . ", no ano catequético de " . Utils::formatCatecheticalYear($ins_ano_catequetico) . ".");
									
									if($ins_pago)
										catechumenArchiveLog($cid, "Pagamento do catequizando com id=" . $cid . " referente ao catecismo " . $ins_catecismo . "º" . $ins_turma . " do ano catequético de " . Utils::formatCatecheticalYear($ins_ano_catequetico) . ".");
									
									echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Catequizando inscrito no " . $ins_catecismo . "º catecismo!</div>");
							}
							else
							{	
								echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao inscrever o catequizando no grupo de catequese.</div>");
							}
						}
                        catch (Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                            die();
                        }
					}
				} // --$_REQUEST['op']=="inscrever"
				
				
				
				
				//Enrollment payment
				if($_REQUEST['op']=="pago")
				{ 
					$pago_ano_catequetico = intval($_POST['ano_lectivo_pago']);
					$pago_catecismo = intval($_POST['catecismo_pago']);
					$pago_turma = Utils::sanitizeInput($_POST['turma_pago']);

					if($pago_ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
					{
						echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Registo de pagamento cancelado.</div>");
					}
					else if($pago_catecismo <= 0 || $pago_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
					{
						echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Registo de pagamento cancelado.</div>");
					}
					else
					{
						try
                        {
							if($db->updateCatechumenEnrollmentPayment($cid, $pago_ano_catequetico, $pago_catecismo, $pago_turma, true))
							{
							  	catechumenArchiveLog($cid, "Pagamento do catequizando com id=" . $cid . " referente ao catecismo " . $pago_catecismo . "º" . $pago_turma . " do ano catequético de " . Utils::formatCatecheticalYear($pago_ano_catequetico) . ".");
							    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Pagamento de inscrição registado. </div>");
							}
							else
                            {
                                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao registar pagamento de inscrição.</div>");
                            }
						}
                        catch (Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                            die();
                        }
					}				
				
				} //-- $_REQUEST['op']=="pago"
				

				
				//Delete catechesis group enrollment
				if($_REQUEST['op']=="eliminar_cat")
				{ 
					$el_ano_catequetico = intval($_POST['ano_lectivo_el']);
					$el_catecismo = intval($_POST['ano_catequetico_el']);
					$el_turma = Utils::sanitizeInput($_POST['turma_el']);
					
					if($el_ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
					{
						echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Eliminação cancelada.</div>");
					}
					else if($el_catecismo <= 0 || $el_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
					{
						echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Eliminação cancelada.</div>");
					}
					else
					{
						try
                        {
                            $db->unenrollCatechumenFromGroup($cid, $el_ano_catequetico, $el_catecismo, $el_turma);
                            catechumenArchiveLog($cid, "Catequizando com id=" . $cid . " desinscrito do " . $el_catecismo . "º" . $el_turma . ", do ano catequético de " . Utils::formatCatecheticalYear($el_ano_catequetico) . ".");
                            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Entrada removida do percurso catequético. </div>");
						}
                        catch (Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        }
					}
				} //--$_REQUEST['op']=="eliminar_cat"
            } //--Authenticator::isAdmin()



            // The following changes can be made by any user owning this catechumen file

            //Scholling record
            if($_REQUEST['op']=="escolaridade")
            {
                $esc_ano_lectivo = Utils::sanitizeInput($_POST['ano_lectivo']);
                $esc_ano_escolar = Utils::sanitizeInput($_POST['ano_escolar']);

                if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::PORTUGAL)
                {
                    $matches = NULL;
                    preg_match('/^([0-9]{4})\/([0-9]{4})$/', $esc_ano_lectivo, $matches);
                    $esc_ano_lectivo = 10000 * intval($matches[1]) + intval($matches[2]);
                }
                else //if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)
                {
                    $matches = NULL;
                    preg_match('/^([0-9]{4})$/', $esc_ano_lectivo, $matches);
                    $esc_ano_lectivo = 10000 * intval($matches[1]) + intval($matches[1]); //NOTE: We repeat the same number twice (e.g. '20232023') for compatibility reasons
                }

                if($esc_ano_lectivo < 1000000 )	//Tem de ser da forma '20152016', logo, com 8 digitos
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano lectivo é inválido. Registo de escolaridade cancelado.</div>");
                }
                else
                {
                    try
                    {
                        if($db->insertCatechumenSchoolingRecord($cid, $esc_ano_lectivo, $esc_ano_escolar))
                        {
                            catechumenArchiveLog($cid, "Actualizado percurso escolar do catequizando com id=" . $cid . ". Acrescentou-se que no ano lectivo de " . Utils::formatCatecheticalYear($esc_ano_lectivo) . " o ano escolar era " . $esc_ano_escolar . ".");
                            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Percurso escolar do catequizando actualizado!</div>");
                        }
                        else
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao registar percurso escolar do catequizando.</div>");
                        }
                    }
                    catch(Exception $e)
                    {
                        //echo $e->getMessage();
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao registar percurso escolar do catequizando.</div>");
                    }
                }
            } //--$_REQUEST['op']=="escolaridade"



            //Delete schooling record
            if($_REQUEST['op']=="eliminar_esc")
            {
                $el_ano_lectivo_esc = intval($_POST['ano_lectivo_el_esc']);

                if($el_ano_lectivo_esc < 1000000 )	//Tem de ser da forma '20152016', logo, com 8 digitos
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano lectivo é inválido. Eliminação de registo de escolaridade cancelada.</div>");
                }
                else
                {
                    try
                    {
                        if($db->deleteCatechumenSchoolingRecord($cid, $el_ano_lectivo_esc))
                        {
                            catechumenArchiveLog($cid, "Actualizado percurso escolar do catequizando com id=" . $cid . ". Removeu-se dados do ano lectivo de " . Utils::formatCatecheticalYear($el_ano_lectivo_esc) . ".");
                            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Registo eliminado do percurso escolar do catequizando.</div>");
                        }
                        else
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar registo do percurso escolar do catequizando.</div>");
                        }
                    }
                    catch (Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    }
                }

            } //--$_REQUEST['op']=="eliminar_esc"



            //Update sacrament records and/or proof documents
            $baptismPanel->handlePost();
            $firstComunionPanel->handlePost();
            $professionOfFaithPanel->handlePost();
            $chrismationPanel->handlePost();


		  	//Update observations field
			if($_REQUEST['op']=="obs")
			{
				$novo_obs = Utils::sanitizeInput($_POST['obs']);

				try
				{
					if($db->setCatechumenObservationsFields($cid, $novo_obs))
					{
					   $_SESSION['obs'] = $obs = $novo_obs;
					   catechumenArchiveLog($cid, "Actualizado campo 'observações' do catequizando com id=" . $cid . ".");
					   echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Observações sobre o catequizando actualizadas!</div>");
					}
					else
					{	
						echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar observações sobre o catequizando.</div>");

;
					}
				}
                catch (Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                }
			}
			
		} //--if ($result && ($result->rowCount())>=1) 
		else
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao tentar aceder ao arquivo do catequizando.</div>");
			die();
		}

	
	} //--if($cid && $cid>0)
	else
	{
		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não foi especificado nenhum catequizando para consultar.</div>");
		die();
	}
	$result = null;
?>
  
  
  <div class="no-print">
  
  <div class="btn-group" role="group" aria-label="...">
  <button type="button" class="btn btn-default glyphicon glyphicon-print" onclick="imprimir()"> Imprimir</button>
  </div>
    
  <div class="row" style="margin-top:20px; "></div>
    
  <ul class="nav nav-tabs">
  <li role="presentation"><a href="mostrarFicha.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Ficha</a></li>
  <li role="presentation" class="active"><a href="mostrarArquivo.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Arquivo</a></li>
  <li role="presentation"><a href="mostrarAutorizacoes.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Autorizações</a></li>
  </ul>
 
  </div>
 
 
 
 
  <div class="panel panel-default" id="painel_arquivo">
   <div class="panel-body">
  
       <div class="container" id="contentor_foto">
           <img src="<?php if($foto && $foto!="") echo("resources/catechumenPhoto.php?foto_name=$foto"); else echo("img/default-user-icon-profile.png");?>" class="img-thumbnail "  alt="Foto do catequizando" width="200" height="150" >
       </div>
	 
       <div class="row" style="margin-top:20px; "></div>
    
  
       <div class="panel panel-default">
           <div class="panel-heading">Dados pessoais do catequizando</div>
           <div class="panel-body">

               <div class="form-group">

                   <!--nome-->
                   <div class="col-xs-6">
                       <label for="nome">Nome:</label>
                       <input class="form-control" id="nome" name="nome" placeholder="Nome do catequizando" size="16" type="text" style="cursor: auto;" <?php if($_SESSION['nome']){ echo("value='" . $_SESSION['nome'] . "'");}?> readonly>
                   </div>

                   <!--data nascimento-->
                   <div class="col-xs-2">
                       <label for="data_nasc">Nasceu a:</label>
                       <input class="form-control" id="data_nasc" name="data_nasc" size="16" type="text" placeholder="dd-mm-aaaa" style="cursor: auto;"
                        <?php
                            if($_SESSION['data_nasc'])
                            {
                                 echo("value='" . date( "d-m-Y", strtotime($_SESSION['data_nasc'])) . "'");
                            }
                        ?> readonly>
                   </div>

                   <!--local nascimento-->
                   <div class="col-xs-3">
                       <label for="localidade">Em:</label>
                       <input type="text" class="form-control" id="localidade" name="localidade" placeholder="Local de nascimento" style="cursor: auto;" <?php if($_SESSION['local_nasc']){ echo("value='" . $_SESSION['local_nasc'] . "'");}?> readonly>
                   </div>

                   <!--numero irmaos-->
                   <div class="col-xs-1">
                       <div id="num_irmaos_div">
                           <label for="num_irmaos">Irmãos:</label>
                           <input type="text" min=0 class="form-control" id="num_irmaos" name="num_irmaos" style="cursor: auto;" <?php if($_SESSION['num_irmaos']){ echo("value='" . $_SESSION['num_irmaos'] . "'");} else {echo("value='0'");}?> readonly>
                       </div>
                   </div>
                   <div class="clearfix"></div>
               </div>

               <!--escuteiro-->
               <div class="form-group">
                   <div class="col-xs-6">
                       <label for="e_escuteiro">É escuteiro(a):</label>
                       <span class="input-xlarge uneditable-input"><?php if($_SESSION['escuteiro']==1){ echo("Sim");} else { echo("Não");}?></span>
                   </div>
                   <!-- <div class="clearfix"></div>-->
               </div>

               <!--idade-->
               <div class="form-group">
                   <div class="col-xs-2">
                       <label for="idade">Idade:</label><br>
                       <span class="input-xlarge uneditable-input">
                        <?= date_diff(date_create($_SESSION['data_nasc_row']), date_create('today'))->y	?> anos
                        </span>
                   </div>
               </div>

               <!--NIF-->
               <div class="col-xs-3">
                   <label for="nif">NIF:</label>
                   <input type="text" class="form-control" id="nif" name="nif" style="cursor: auto;"
                       <?php if($_SESSION['nif']){ echo("value='" . $_SESSION['nif'] . "'");}?> readonly>
                   <div class="clearfix"></div>
               </div>

           </div>
       </div>

       <div class="panel panel-default">
           <div class="panel-heading">Sacramentos e celebrações</div>
           <div class="panel-body">
               <div class="col-xs-6">
                   <?php
                   $baptismPanel->renderHTML();
                   ?>
               </div>
               <div class="col-xs-6">
                   <?php
                   $firstComunionPanel->renderHTML();
                   ?>
               </div>

               <div class="clearfix"></div>

               <div class="col-xs-6">
                   <?php
                    $professionOfFaithPanel->renderHTML();
                    ?>
               </div>
               <div class="col-xs-6">
                   <?php
                    $chrismationPanel->renderHTML();
                    ?>
               </div>

               <div class="clearfix"></div>
           </div>
       </div>


    <div class="panel panel-default">
    <div class="panel-heading">Percurso catequético</div>
    <div class="panel-body">

    <?php
    if(Authenticator::isAdmin())
	{
    ?>
    <form role="form" onsubmit="" action="mostrarArquivo.php?cid=<?= $_SESSION['cid'] ?>&amp;op=inscrever" method="post">
    <?php
    }
    ?>

    <div class="col-xs-12">
    	<table class="table table-hover">
    	  <thead>
    		<tr>
    			<th>Ano catequético</th>
    			<th>Catecismo</th>
    			<th>Catequistas</th>
    			<th>Inscrito por</th>
    			<th>Aproveitamento</th>
    			<th>Pago</th>
    		</tr>
    	  </thead>
    	  <tbody>
    	  	<?php

			$cid = intval($_REQUEST['cid']);
			
			//Obter percurso catequetico
            $result = NULL;
			try
            {
                $result = $db->getCatechumenCatecheticalRecord($cid);
			}
			catch(Exception $e)
            {
				//echo $e->getMessage();
                //Fail silently
			}
	
			if (isset($result) && count($result)>=1)
			{
				foreach($result as $row)
				{
					echo("<tr>");
					echo("<td>" . Utils::formatCatecheticalYear($row['ano_lectivo']) . "</td>\n");
					echo("<td>" . $row['ano_catecismo'] . "º" . Utils::sanitizeOutput($row['turma']) . "</td>");
					
					
					//Obter nomes dos catequistas
					$result2 = NULL;
					try
                    {
                        $result2 = $db->getGroupCatechists($row['ano_lectivo'], $row['ano_catecismo'], Utils::sanitizeOutput($row['turma']));

                        if(isset($result2) && count($result2)>=1)
                        {
                            echo("<td>");
                            $count=0;
                            foreach($result2 as $row2)
                            {
                                if($count!=0)
                                    echo(", ");
                                echo("" . Utils::firstAndLastName(Utils::sanitizeOutput($row2['nome'])) . "");
                                $count++;
                            }
                            echo("</td>");
                        }
                        else
                        {
                            echo("<td><i>Por definir</i></td>");
                        }
					}
					catch(Exception $e)
                    {
						//Fail silently
					}

					echo("<td>" . Utils::firstAndLastName(Utils::sanitizeOutput($row['nome'])) . "</td>");	//Nome do catequista que fez a inscricao

					//Passou ou nao de ano
					if(isset($row['passa']) && $row['passa']==-1)
						echo('<td><span class="label label-danger">Reprovado</span></td>');
					else
						echo('<td><span class="label label-success">Transita</span></td>');


					
					if($row['pago']==1)
					{
						echo('<td><span class="glyphicon glyphicon-ok text-success"></span>');
						if(Authenticator::isAdmin())
							echo('<div class="btn-group-xs pull-right btn-group-hover" role="group" aria-label="..."><button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarEliminarCat" onclick="preparar_eliminacao_cat(' . $row['ano_lectivo'] . ', ' . $row['ano_catecismo'] . ', \'' . Utils::sanitizeOutput($row['turma']) . '\')"><span class="glyphicon glyphicon-trash text-danger"> Eliminar</span></button></div>'); //Botao eliminar
					}
					else
					{
						echo('<td><span class="glyphicon glyphicon-remove text-danger"></span>');
						if(Authenticator::isAdmin())
						{						
							echo('<div class="btn-group-xs pull-right btn-group-hover" role="group" aria-label="..."><button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarEliminarCat" onclick="preparar_eliminacao_cat(' . $row['ano_lectivo'] . ', ' . $row['ano_catecismo'] . ', \'' . Utils::sanitizeOutput($row['turma']) . '\')"><span class="glyphicon glyphicon-trash text-danger"> Eliminar</span></button></div>'); //Botao eliminar
							echo('<div class="btn-group-xs pull-right btn-group-hover" role="group" aria-label="..."><button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarPago" onclick="preparar_pago(' . $row['ano_lectivo'] . ', ' . $row['ano_catecismo'] . ', \'' . Utils::sanitizeOutput($row['turma']) . '\')"><span class="glyphicon glyphicon-ok text-success"> Pago</span></button></div>'); //Botao pago
						}
					}				
					echo('</td>');
					
					echo("</tr>");
				}
			}
			$result = null;
			
		if(Authenticator::isAdmin())
		{
    	  	?>
    		
    		<tr class="active no-print">
    			
    			<td><div class="input-group input-group-sm"><select class="" name="ano_catequetico" required>
    									<option disabled selected></option>
    				<?php
					
					$cid = intval($_REQUEST['cid']);
					
					//Obter anos lectivos onde o catequisando nao esta inscrito
                    $result = NULL;
					try
                    {
                        $result = $db->getCatecheticalYearsWhereCatechumenIsNotEnrolled($cid);
					}
					catch(Exception $e)
                    {
						//echo $e->getMessage();
                        //Fail silently
					}
			
					if (isset($result) && count($result)>=1)
					{
						foreach($result as $row)
						{
							echo("<option value='" . $row['ano_lectivo'] . "'>");
							echo(Utils::formatCatecheticalYear($row['ano_lectivo']) . "</option>\n");
						}
					}

					$result = null;  				
    				?>
									</select> </div></td>
    			<td><div class="input-group input-group-sm"> <select class="" name="catecismo" required>
    									<option disabled selected></option>
    				<?php

					$cid = intval($_REQUEST['cid']);
					
					//Obter anos e turmas de catequese
					try
                    {
					    $result = $db->getCatechismsAndGroups();

                        foreach($result as $row)
                        {
                            echo("<option value='" . $row['ano_catecismo'] . "º" . Utils::sanitizeOutput($row['turma']) . "'>");
                            echo("" . $row['ano_catecismo'] . "º" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                        }

                        $result = null;
					}
					catch(Exception $e)
                    {
                        //Do nothing
					}

    				?>
									</select> </div></td>
    			<td><span class=""><i>Preenchido automaticamente</i></span></td>
    			<td class=""><span class="glyphicon glyphicon-user"></span> <?= Utils::firstAndLastName(Authenticator::getUserFullName()); ?></td>
    			<td><div class="input-group input-group-sm"> <select class="" name="transita" required>
    									<option value="transita" selected></option>
    									<option value="transita">Transitou</option>
    									<option value="reprovado">Reprovou</option></div></td>
    			<td><div class="btn-group-xs" role="group" aria-label="...">
    				<label class=""><input type="checkbox" name="pago" value="Sim"> Pago</label>
				<button type="submit" class="btn btn-default pull-right"><span class="glyphicon glyphicon-plus text-success">Adicionar</span></button>
						  </div></td>
			
    		</tr>
    		<?php
    		 }
    		?>
    	   </tbody>
	</table>     
     
     </div>
     <div class="clearfix"></div>
     
     <?php
     if(Authenticator::isAdmin())
     {
	    ?>
     </form>
     <?php
     }
     ?>
     
     </div>
     </div>
     
     
     
       <div class="panel panel-default">
           <div class="panel-heading">Percurso escolar</div>
           <div class="panel-body">
               <form role="form" onsubmit="" action="mostrarArquivo.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>&amp;op=escolaridade" method="post">
                   <div class="col-xs-12">
                       <table class="table table-hover">
                           <thead>
                           <tr>
                               <th>Ano lectivo</th>
                               <th>Ano escolar</th>
                               <th></th>
                           </tr>
                           </thead>
                           <tbody>
                            <?php

                            $cid = intval($_REQUEST['cid']);

                            //Obter escolaridade
                            $result = null;
                            try
                            {
                                $result = $db->getCatechumenSchoolingRecord($cid);
                            }
                            catch(Exception $e)
                            {
                                //echo $e->getMessage();
                                //Fail silently
                            }

                            if(isset($result) && count($result)>=1)
                            {
                                foreach($result as $row)
                                {
                                    ?>
                            <tr>
                                <td><?= Utils::formatCatecheticalYear($row['ano_lectivo']) ?></td>
                                <td><?=  $row['ano_escolaridade'] ?></td>

                                <td>
                                    <div class="btn-group-xs pull-right btn-group-hover" role="group" aria-label="..."><button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarEliminarEsc" onclick="preparar_eliminacao_esc('<?= $row['ano_lectivo'] ?>')"><span class="glyphicon glyphicon-trash text-danger"> Eliminar</span></button></div>
                                </td>
                            </tr>
                            <?php
                                }
                            }

                            $result = null;
                            ?>

                            <tr class="active no-print">
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="ano_lectivo" name="ano_lectivo" placeholder="<?= Utils::formatCatecheticalYear(Utils::currentCatecheticalYear()) ?>" list="anos_lectivos" required>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-xs" role="group" aria-label="...">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" id="ano_escolar" name="ano_escolar" placeholder="1º" list="anos_escolares" required>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-xs" role="group" aria-label="...">
                                        <button type="submit" class="btn btn-default pull-right"><span class="glyphicon glyphicon-plus text-success">Adicionar</span></button>
                                    </div>
                                </td>
                            </tr>
                           </tbody>
                       </table>
                   </div>
                   <div class="clearfix"></div>
               </form>
           </div>
       </div>
    
    

    <div class="panel panel-default">
    <div class="panel-heading">Observações <div class="btn-group-xs pull-right" role="group" aria-label="...">
	  <button type="button" id="cancelar_obs" onclick="cancelar_obs()" class="btn btn-default glyphicon glyphicon-remove" style="display: none;" disabled> Cancelar</button>
	  <button type="button" id="guardar_obs" onclick="guardar_obs()" class="btn btn-primary glyphicon glyphicon-floppy-disk" style="display: none;" disabled> Guardar</button>
	  <button type="button" id="editar_obs" onclick="editar_obs()" class="btn btn-default glyphicon glyphicon-pencil"> Editar</button>
	  </div></div>
    <div class="panel-body">    
    
    <form role="form" id="form_obs" onsubmit="" action="mostrarArquivo.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>&amp;op=obs" method="post">
	    <div class="col-xs-12">
	      <textarea type="text" class="form-control" id="obs" name="obs" placeholder="Observações" style="cursor: auto;"  readonly><?php if($_SESSION['obs']){ echo('' . $_SESSION['obs'] . '');} ?></textarea>
	    </div>		    
	    <div class="clearfix"></div>
	    
	    <input type="hidden" id="obs_backup" value="<?php if($_SESSION['obs']){ echo('' . $_SESSION['obs'] . '');} ?>" readonly>
	</form>

     </div>
     </div>

       
    <div class="row" style="margin-top:20px; "></div>
    
    <div class="col-xs-6">
    	<span>Inscrito por: &nbsp;<span class="glyphicon glyphicon-user"></span> <?php echo('' . Utils::firstAndLastName($criado_por)); ?> &nbsp;&nbsp;&nbsp; <span class="glyphicon glyphicon-calendar"></span> <?php echo('' . $criado_em); ?></span>
    </div>
    <div class="col-xs-6">
        <span>Última alteração: <?php if($modificou_quem) echo('&nbsp;<span class="glyphicon glyphicon-user"></span> ' . Utils::firstAndLastName($modificou_quem) .' &nbsp;&nbsp;&nbsp; <span class="glyphicon glyphicon-calendar"></span> ' . $modificou_data); else echo('nunca');?></span>
    </div>
   
    <div class="clearfix"></div>
    
 </div>
</div>

</div>





<!-- Eliminar percurso escolar -->
<form role="form" id="form_eliminar_esc" action="mostrarArquivo.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>&amp;op=eliminar_esc" method="post">
    <input type="hidden" id="ano_lectivo_el_esc" name="ano_lectivo_el_esc" >
</form>

<?php

// Dialog to confirm deletion of schooling entry

$deleteSchoolDialog->setTitle("Confirmar eliminação");
$deleteSchoolDialog->setBodyContents(<<<HTML_CODE
               <p>Tem a certeza de que pretende eliminar esta entrada do percurso escolar?</p>
HTML_CODE
);
$deleteSchoolDialog->addButton(new Button("Não", ButtonType::SECONDARY))
    ->addButton(new Button("Sim", ButtonType::DANGER, "eliminar_escolar()"));
$deleteSchoolDialog->renderHTML();
?>


<?php
if(Authenticator::isAdmin())
{
?>
<!-- Eliminar percurso catequetico -->
<form role="form" id="form_eliminar_cat" action="mostrarArquivo.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>&amp;op=eliminar_cat" method="post">
	<input type="hidden" id="ano_lectivo_el" name="ano_lectivo_el" >
	<input type="hidden" id="turma_el" name="turma_el" >
	<input type="hidden" id="ano_catequetico_el" name="ano_catequetico_el" >
</form>

<?php

    // Dialog to confirm deletion of catechetical history entry

    $deleteCatDialog->setTitle("Confirmar eliminação");
    $deleteCatDialog->setBodyContents(<<<HTML_CODE
                <p>Tem a certeza de que pretende eliminar esta entrada do percurso catequético?</p>
HTML_CODE
    );
    $deleteCatDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                    ->addButton(new Button("Sim", ButtonType::DANGER, "eliminar_catequetico()"));
    $deleteCatDialog->renderHTML();

?>


<!-- Registar pagamento -->
<form role="form" id="form_pago" action="mostrarArquivo.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>&amp;op=pago" method="post">
	<input type="hidden" id="ano_lectivo_pago" name="ano_lectivo_pago" >
	<input type="hidden" id="turma_pago" name="turma_pago" >
	<input type="hidden" id="catecismo_pago" name="catecismo_pago" >
</form>


<?php
    // Dialog to confirm enrollment payment

    $confirmPaymentDialog->setTitle("Confirmar pagamento");
    $confirmPaymentDialog->setBodyContents(<<<HTML_CODE
                <p>Tem a certeza de que pretende registar esta inscrição como paga?</p>
HTML_CODE
    );
    $confirmPaymentDialog->addButton(new Button("Não", ButtonType::SECONDARY))
        ->addButton(new Button("Sim", ButtonType::DANGER, "pagamento()"));
    $confirmPaymentDialog->renderHTML();


} // --if(Authenticator::isAdmin())

?>


<datalist id="anos_lectivos">
    <?php
    //Print from the last 10 years up to the current year
    for($y = -10; $y < 1; $y++)
    {
        $year_start = date('Y') + $y;
        $year_end = date('Y') + $y + 1;
        echo("<option value='$year_start/$year_end'>");
    }
    ?>
</datalist>

<datalist id="anos_escolares">
<?php
for($i=1; $i <= 12; $i++)
{
?>
    <option value='<?= $i ?>º'>
<?php
}
?>
</datalist>

<!-- Listas de paroquias -->
<?php
$baptismPanel->renderParishesList();
?>



<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>

<script>
$(document).ready(function(){
    $('tr').on({
		mouseenter: function(){
			$(this)
				.find('.btn-group-hover').stop().fadeTo('fast',1)
				.find('.icon-white').addClass('icon-white-temp').removeClass('icon-white');
		},
		mouseleave: function(){
			$(this)
				.find('.btn-group-hover').stop().fadeTo('fast',0);
		}
	});

    $('.btn-group-hover').on({
		mouseenter: function(){
			$(this).removeClass('btn-group-hover')
				.find('.icon-white-temp').addClass('icon-white');
		},
		mouseleave: function(){
			$(this).addClass('btn-group-hover')
				.find('.icon-white').addClass('icon-white-temp').removeClass('icon-white');
		}
	});
})
</script>



<script>
<?php
if(Authenticator::isAdmin())
{
?>

    function preparar_eliminacao_cat(lectivo, ano, turma)
    {
        document.getElementById("ano_lectivo_el").value = lectivo;
        document.getElementById("turma_el").value = turma;
        document.getElementById("ano_catequetico_el").value = ano;

    }



    function eliminar_catequetico()
    {
        document.getElementById("form_eliminar_cat").submit();
    }


    function preparar_pago(lectivo, ano, turma)
    {
        document.getElementById("ano_lectivo_pago").value = lectivo;
        document.getElementById("turma_pago").value = turma;
        document.getElementById("catecismo_pago").value = ano;

    }


    function pagamento()
    {
        document.getElementById("form_pago").submit();
    }

<?php
}
?>


function preparar_eliminacao_esc(lectivo)
{
	document.getElementById("ano_lectivo_el_esc").value = lectivo;

}

function eliminar_escolar()
{
	document.getElementById("form_eliminar_esc").submit();
}

</script>


<script type="text/javascript">
function editar_obs()
{	
	document.getElementById("obs").readOnly = false;
	document.getElementById("guardar_obs").disabled = false;
	document.getElementById("cancelar_obs").disabled = false;
	$('#editar_obs').hide();
	$('#guardar_obs').show();
	$('#cancelar_obs').show();
}


function cancelar_obs()
{	
	document.getElementById("obs").readOnly = true;
	document.getElementById("obs").value = document.getElementById("obs_backup").value;
	document.getElementById("guardar_obs").disabled = true;
	document.getElementById("cancelar_obs").disabled = true;
	$('#editar_obs').show();
	$('#guardar_obs').hide();
	$('#cancelar_obs').hide();
}


function guardar_obs()
{	
	document.getElementById("form_obs").submit();
}
</script>


<script type="text/javascript">

    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data) 
    {
        var mywindow = window.open('', 'Arquivo', 'height=800,width=600');
        mywindow.document.write('<html><head><title>Arquivo</title><link rel="stylesheet" href="css/bootstrap.min.css"><link rel="stylesheet" href="css/custom-navbar-colors.css">');
        mywindow.document.write('<style>@media print{.no-print, .no-print *  {display: none !important; }   .btn { display: none !important; } @page { size: portrait; } body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }	.panel-heading { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; background-color: #f5f5f5 !important; } .panel-success > .panel-heading { background-color: #dff0d8 !important; } }');
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
function imprimir()
  	{
  		//Solucao antiga --> So funciona no FireFox
  		/*
  		$('#contentor').removeClass('container');
  		$('#contentor_foto').removeClass('container');
  		window.print();
  		$('#contentor').addClass('container');
  		$('#contentor_foto').addClass('container'); */
  		
  		//Nova solucao
  		PrintElem(document.getElementById('painel_arquivo'));
  	}
</script>

</body>
</html>