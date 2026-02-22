<?php

require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/domain/Locale.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/log_functions.php'); //Para poder escrever no log
require_once(__DIR__ . '/core/enrollment_functions.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/DataValidationUtils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');

use catechesis\DatabaseAccessMode;
use catechesis\DataValidationUtils;
use catechesis\Authenticator;
use catechesis\Configurator;
use core\domain\Locale;
use catechesis\PdoDatabaseManager;
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
$deleteFamilyMemberDialog = new ModalDialogWidget("confirmarEliminarFamiliar");
$pageUI->addWidget($deleteFamilyMemberDialog);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Detalhes do catequizando</title>
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

        @page {
            size: portrait;
        }

        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
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

        .progress {
            position: relative;
        }
        .progress:before {
            display: block;
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 0;
            border-bottom: 2rem solid #eeeeee;
        }
        .progress-bar {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1;
            border-bottom: 2rem solid #337ab7;
        }
        .progress-bar-success {
            border-bottom-color: #67c600;
        }
        .progress-bar-info {
            border-bottom-color: #5bc0de;
        }
        .progress-bar-warning {
            border-bottom-color: #f0a839;
        }
        .progress-bar-danger {
            border-bottom-color: #ee2f31;
        }
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
		$obs = NULL;
		$foto = NULL;
        $autorizacao_fotos = NULL;
        $autorizacao_saida_sozinho = NULL;

        $fid_ee = NULL;
        $enc_edu_quem = NULL;
        $nome_ee = NULL;
        $telefone_ee = NULL;
        $telemovel_ee = NULL;

		$criado_por = NULL;
		$criado_em = NULL;
        $lastLSN_autorizacoes = NULL;
		$modificou_quem = NULL;
		$modificou_data = NULL;




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
            $autorizacao_saida_sozinho = intval($catechumen['autorizou_saida_sozinho']);
            $fid_pai = (isset($catechumen['pai']))? intval($catechumen['pai']) : null;
            $fid_mae = (isset($catechumen['mae']))? intval($catechumen['mae']) : null;
            $fid_ee = intval($catechumen['enc_edu']);
            $enc_edu_quem = Utils::sanitizeOutput($catechumen['enc_edu_quem']);
            $foto = Utils::sanitizeOutput($catechumen['foto']);
            $criado_por = Utils::sanitizeOutput($catechumen['criado_por_nome']);
            $criado_em = Utils::sanitizeOutput($catechumen['criado_em']);
            $lastLSN_autorizacoes = intval($catechumen['lastLSN_autorizacoes']);
        }
        catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }



        if (isset($catechumen))
        {
            ///Obter dados do encarregado educacao
            try
            {
                $responsible = $db->getFamilyMember($fid_ee);

                $nome_ee = Utils::sanitizeOutput($responsible['nome']);
                $prof_ee = Utils::sanitizeOutput($responsible['prof']);
                $morada = Utils::sanitizeOutput($responsible['morada']);
                $codigo_postal = Utils::sanitizeOutput($responsible['cod_postal']);
                $telefone_ee = Utils::sanitizeOutput($responsible['telefone']);
                $telemovel_ee = Utils::sanitizeOutput($responsible['telemovel']);
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
                $fileLog = $db->getLogEntry($lastLSN_autorizacoes);

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
            $_SESSION['data_nasc'] = $data_nasc;
            $_SESSION['local_nasc'] = $local_nasc;
            $_SESSION['num_irmaos'] = $num_irmaos;
            $_SESSION['escuteiro'] = $escuteiro;
            $_SESSION['obs'] = $obs;
            $_SESSION['foto'] = $foto;
            $_SESSION['fid_ee'] = $fid_ee;
            $_SESSION['enc_edu_quem'] = $_SESSION['outro_enc_edu_quem'] = $enc_edu_quem;
            $_SESSION['nome_ee'] = $nome_ee;
            $_SESSION['prof_ee'] = $_SESSION['prof_enc_edu'] = $prof_ee;
            $_SESSION['morada'] = $morada;
            $_SESSION['codigo_postal'] = $_SESSION['cod_postal'] = $codigo_postal;
            $_SESSION['telefone'] = $telefone_ee;
            $_SESSION['telemovel'] = $telemovel_ee;
            $_SESSION['email'] = $email;
            $_SESSION['RGPD_assinado'] = $rgpd_ee;
            $_SESSION['autorizacao'] = $autorizacao_fotos;
            $_SESSION['autorizacao_saida_sozinho'] = $autorizacao_saida_sozinho;

            $_SESSION['cid'] = $cid;

            //Para compatibilidade com o codigo inscricao.php:
            if($fid_ee==$fid_pai)
            {
                $enc_edu_quem = "pai";
                $_SESSION['enc_edu'] = "Pai";
                $_SESSION['nome_enc_edu'] = "";
                $_SESSION['prof_enc_edu'] = "";
            }
            else if($fid_ee==$fid_mae)
            {
                $enc_edu_quem = "mae";
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


            //Apply user change requests (if any)

            //Adicionar familiar
            if ($_REQUEST['op'] == "adiciona_familiar")
            {
                $parentesco = Utils::sanitizeInput($_POST['parentesco']);
                $nome_familiar = Utils::sanitizeInput($_POST['nome_familiar']);
                $telemovel_familiar = Utils::sanitizeInput($_POST['telemovel']);
                $fid_familiar = NULL;


                //Verificar inputs
                $inputs_invalidos = false;

                if(!$parentesco || $parentesco == "")
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> É necessário definir um parentesco para o familiar do catequizando.</div>");
                    $inputs_invalidos = true;
                }
                if(!$nome_familiar || $nome_familiar == "")
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> É necessário definir o nome do familiar do catequizando.</div>");
                    $inputs_invalidos = true;
                }
                if($telemovel_familiar=="" || !DataValidationUtils::validatePhoneNumber($telemovel_familiar, Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)))
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O número de telemóvel que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.</div>");
                    $inputs_invalidos = true;
                }

                if($inputs_invalidos)
                {
                    die();
                }


                // Tries to create a new family member and add it to the authorizations list of the catechumen
                $fid_familiar = addAuthorizationToList($cid, $nome_familiar, $parentesco, $telemovel_familiar);

                if(!isset($fid_familiar))
                    die();

            } //--if ($_REQUEST['op'] == "adiciona_familiar")


            //Remover familiar
            if ($_REQUEST['op'] == "eliminar_fam")
            {

                $fid_familiar = intval($_POST['fid_familiar']);

                $db->beginTransaction(DatabaseAccessMode::DEFAULT_DELETE);


                //Remover familiar da lista de autorizacoes do catequizando
                try
                {
                    if (!$db->removeFamilyMemberFromCatechumenAuthorizationList($cid, $fid_familiar))
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar autorizações do catequizando.</div>");
                        $db->rollBack();
                        die();
                    }
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    $db->rollBack();
                    die();
                }



                //Remover familiar da base de dados
                //NOTA: Nao procuramos se o familiar existe noutras fichas, porque so' com o primeiro e ultimo nome pode haver nomes repetidos...
                //Para esta funcionalidade, assumimos que o familiar e' sempre para acrescentar/remover da BD.
                try
                {
                    if ($db->deleteFamilyMember($fid_familiar))
                    {
                        $db->commit();
                        catechumenAuthorizationsLog($cid, "Removido familiar com fid=" . $fid_familiar . " da lista de autorizações do catequizando com cid=" . $cid);
                        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Familiar removido da lista de autorizações deste catequizando.</div>");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar autorizações do catequizando.</div>");
                        $db->rollBack();
                        die();
                    }
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    $db->rollBack();
                    die();
                }
            }
            //--if ($_REQUEST['op'] == "eliminar_fam")



            //Alterar autorizacao do catequizando para sair sozinho
            if ($_REQUEST['op'] == "sair-sozinho")
            {
                $pode_sair = Utils::sanitizeInput($_POST['checkbox-sai-sozinho']);

                if($pode_sair == "on")
                    $pode_sair = true;
                else
                    $pode_sair = false;


                //Atualizar autorizacao do catequizando
                try
                {
                    if($db->setCatechumenAuthorizationToGoOutAlone($cid, $pode_sair))
                    {
                        if($pode_sair)
                        {
                            catechumenAuthorizationsLog($cid, "O catequizando com cid=" . $cid . " pode sair sozinho.");
                            $_SESSION['autorizacao_saida_sozinho'] = $autorizacao_saida_sozinho = 1;
                        }
                        else
                        {
                            catechumenAuthorizationsLog($cid, "O catequizando com cid=" . $cid . " não pode sair sozinho.");
                            $_SESSION['autorizacao_saida_sozinho'] = $autorizacao_saida_sozinho = 0;
                        }

                        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Autorização de saída do catequizando modificada.</div>");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar autorizações do catequizando.</div>");
                        die();
                    }
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                }
            }//--if ($_REQUEST['op'] == "sair-sozinho")


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

?>


    <div class="no-print">

        <div class="btn-group" role="group" aria-label="...">
            <button type="button" class="btn btn-default glyphicon glyphicon-print" onclick="imprimir()"> Imprimir</button>
        </div>

        <div class="row" style="margin-top:20px; "></div>

        <ul class="nav nav-tabs">
            <li role="presentation"><a href="mostrarFicha.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Ficha</a></li>
            <li role="presentation"><a href="mostrarArquivo.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Arquivo</a></li>
            <li role="presentation" class="active"><a href="mostrarAutorizacoes.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>">Autorizações</a></li>
        </ul>

    </div>



    <div class="panel panel-default" id="painel_autorizacoes">
        <div class="panel-body">

            <div class="container" id="contentor_foto">
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
                            <input class="form-control" id="nome" name="nome" placeholder="Nome do catequizando" size="16" type="text" style="cursor: auto;"
                                <?php if($_SESSION['nome']){ echo("value='" . $_SESSION['nome'] . "'");}?> readonly>
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
                            <input type="text" class="form-control" id="localidade" name="localidade" placeholder="Local de nascimento" style="cursor: auto;"
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
                <div class="panel-heading">Autorização de saída de menor</div>
                <div class="panel-body">
                    <div class="col-xs-12">
                        <div class="progress" style="width: 95%; margin: 0px auto; float:left">
                        <?php
                        if($_SESSION['autorizacao_saida_sozinho']==1)
                        {?>
                            <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:100%">
                                <span class="h5">O catequizando pode sair sozinho.</span>
                            </div>
                        </div>
                        <div style="float:left; position:relative; top:-2px; left:0px;" class="no-print">
                            <form role="form" id="form_sai-sozinho" name="form_sai-sozinho" onsubmit="" action="mostrarAutorizacoes.php?cid=<?php echo($cid); ?>&op=sair-sozinho" method="post">
                                <input type="checkbox" class="checkbox-sai-sozinho" name="checkbox-sai-sozinho" checked>
                            </form>
                        </div>
                        <?php
                        }
                        else
                        { ?>
                            <div class="progress-bar progress-bar-warning progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:100%">
                                <span class="h5">O catequizando não pode sair sozinho.</span>
                            </div>
                        </div>
                        <div style="float:left; position:relative; top:-2px; left:0px;" class="no-print">
                            <form role="form" id="form_sai-sozinho" name="form_sai-sozinho" onsubmit="" action="mostrarAutorizacoes.php?cid=<?php echo($cid); ?>&op=sair-sozinho" method="post">
                                <input type="checkbox" class="checkbox-sai-sozinho" name="checkbox-sai-sozinho">
                            </form>
                        </div>
                        <?php
                        }
                        ?>
                </div>

                <div class="row" style="margin-top:50px; "></div>
                <div class="col-xs-12">
                    <h5><b>Quem pode vir buscar o catequizando:</b></h5>
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Parentesco</th>
                            <th><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?></th>
                            <th></th>
                        </tr>
                        </thead>

                        <tr>
                            <td><?php echo($nome_ee); ?></td>
                            <td>Encarregado de educação (<?php echo($enc_edu_quem); ?>)</td>
                            <td><?php if(!is_null($telemovel_ee) && $telemovel_ee != 0 && $telemovel_ee != "") echo($telemovel_ee); else echo($telefone_ee); ?></td>
                            <td></td>
                        </tr>
                        <?php

                        $result = NULL;

                        try
                        {
                            $result = $db->getCatechumenAuthorizationList($cid);
                        }
                        catch(Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                            die();
                        }


                        if (isset($result))
                        {
                            foreach ($result as $row)
                            {
                                echo("<tr>\n");
                                echo("<td>" . $row['nome'] . "</td>\n");
                                echo("<td>" . $row['parentesco'] . "</td>\n");

                                $tel = '';
                                if(!is_null($row['telemovel']) && $row['telemovel'] != ""  && $row['telemovel'] != 0)
                                    $tel = $row['telemovel'];
                                else if(!is_null($row['telefone']) && $row['telefone'] != ""  && $row['telefone'] != 0)
                                    $tel = $row['telefone'];

                                echo("<td>" . $tel . "</td>\n");

                                echo("<td><div class=\"btn-group-xs pull-right btn-group-hover\" role=\"group\" aria-label=\"...\"><button type=\"button\" class=\"btn btn-default\" data-toggle=\"modal\" data-target=\"#confirmarEliminarFamiliar\" onclick=\"preparar_eliminacao_autorizacao_familiar(" . $row['fid']  . ")\"><span class=\"glyphicon glyphicon-trash text-danger\"> Eliminar</span></button></div></td>\n"); //Botao eliminar

                                echo("</tr>\n");
                            }
                        }
                        ?>

                        <tr class="active no-print">

                            <form role="form" onsubmit="return valida_dados_familiar()" action="mostrarAutorizacoes.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>&amp;op=adiciona_familiar" method="post">

                            <td><div class="btn-group-xs" role="group" aria-label="...">
                                    <div class="input-group input-group-sm"><input type="text" class="form-control" id="nome_familiar" name="nome_familiar" placeholder="Nome" list="nomes_familiares" required></div></div></td>
                            <td><div class="input-group input-group-sm"><input type="text" class="form-control" id="parentesco" name="parentesco" placeholder="Avó" list="parentescos_familiares" required></div></td>
                            <td><div class="btn-group-xs" role="group" aria-label="...">
                                    <div class="input-group input-group-sm"><input type="text" class="form-control" id="telemovel" name="telemovel" placeholder="9xxxxxxxx" list="" required></div></div></td>
                            <td><div class="btn-group-xs" role="group" aria-label="...">
                                    <button type="submit" class="btn btn-default pull-right"><span class="glyphicon glyphicon-plus text-success">Adicionar</span></button>
                                </div></td>

                            </form>

                        </tr>

                        <tbody>
                        </tbody>
                    </table>
                </div>
                <div class="clearfix"></div>

            </div>
        </div>


            <div class="panel panel-default">
                <div class="panel-heading">Autorização de recolha de imagens</div>
                <div class="panel-body">
                    <?php
                    //Autorizacao photos
                    if($_SESSION['autorizacao']==1)
                        echo('<span class="text-success"><span class="glyphicon glyphicon-ok"></span> Autoriza a utilização e divulgação de fotografias do educando, tiradas no âmbito das actividades catequéticas.</span>');
                    else
                        echo('<span class="text-danger"><span class="fas fa-ban"></span> NÃO autoriza a utilização e divulgação de fotografias do educando, tiradas no âmbito das actividades catequéticas.</span>');
                    echo('<div class="clearfix"></div>');
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

            <div class="clearfix"></div>

</div>




<!-- Eliminar familiar que pode vir buscar a crianca -->
<form role="form" id="form_eliminar_familiar"
      action="mostrarAutorizacoes.php?cid=<?php echo('' . $_SESSION['cid'] . ''); ?>&amp;op=eliminar_fam" method="post">
    <input type="hidden" id="fid_familiar" name="fid_familiar">
</form>


<?php
    // Dialog to confirm removal of family member

    $deleteFamilyMemberDialog->setTitle("Confirmar eliminação");
    $deleteFamilyMemberDialog->setBodyContents(<<<HTML_CODE
                        <p>Tem a certeza de que pretende eliminar esta entrada da lista de pessoas que podem vir buscar o catequizando?</p>
HTML_CODE
        );
    $deleteFamilyMemberDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                             ->addButton(new Button("Sim", ButtonType::DANGER, "eliminar_familiar()"));
    $deleteFamilyMemberDialog->renderHTML();
?>


<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/bootstrap-switch.js"></script>

<script>

function valida_dados_familiar()
{
    var telemovel = document.getElementById('telemovel').value;
    if(!telefone_valido(telemovel, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
    {
        alert("O número de telemóvel que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
        return false;
    }
    return true;
}

function telefone_valido(num, locale)
{
    var phoneno = '';

    if(locale==="PT")
        phoneno = /^(\+\d{1,}[-\s]{0,1})?\d{9}$/;
    else if(locale==="BR")
        phoneno = /^(\+\d{1,}[-\s]{0,1})?\s*\(?(\d{2}|\d{0})\)?[-. ]?(\d{5}|\d{4})[-. ]?(\d{4})[-. ]?\s*$/;

    return num.match(phoneno);
}

function preparar_eliminacao_autorizacao_familiar(fid)
{
    document.getElementById("fid_familiar").value = fid;
}


function eliminar_familiar()
{
    document.getElementById("form_eliminar_familiar").submit();
}


$(function () {
    $("[class='checkbox-sai-sozinho']").bootstrapSwitch({size: 'mini',
        onText: '&nbsp;',
        offText: '&nbsp;',
        onColor: 'success',
        offColor: 'warning',
        labelText: '<span class="glyphicon glyphicon-pencil"/>'
    });
});

$('input[class="checkbox-sai-sozinho"]').on('switchChange.bootstrapSwitch', function(event, state)
{
    $('#form_sai-sozinho').submit();
});


</script>



<script type="text/javascript">

function PrintElem(elem)
{
    Popup($(elem).html());
}

function Popup(data)
{
    var mywindow = window.open('', 'Autorizações', 'height=800,width=600');
    mywindow.document.write('<html><head><title>Autorizações</title><link rel="stylesheet" href="css/bootstrap.min.css"><link rel="stylesheet" href="css/custom-navbar-colors.css">');
    mywindow.document.write('<style>@media print{.no-print, .no-print *  {display: none !important; }   .btn { display: none !important; } @page { size: portrait; } body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } ' +
        '.progress {\n' +
        '    position: relative;\n' +
        '}\n' +
        '.progress:before {\n' +
        '    display: block;\n' +
        '    content: \'\';\n' +
        '    position: absolute;\n' +
        '    top: 0;\n' +
        '    right: 0;\n' +
        '    bottom: 0;\n' +
        '    left: 0;\n' +
        '    z-index: 0;\n' +
        '    border-bottom: 2rem solid #eeeeee;\n' +
        '}\n' +
        '.progress-bar {\n' +
        '    position: absolute;\n' +
        '    top: 0;\n' +
        '    bottom: 0;\n' +
        '    left: 0;\n' +
        '    z-index: 1;\n' +
        '    border-bottom: 2rem solid #337ab7;\n' +
        '}\n' +
        '.progress-bar-success {\n' +
        '    border-bottom-color: #67c600;\n' +
        '}\n' +
        '.progress-bar-info {\n' +
        '    border-bottom-color: #5bc0de;\n' +
        '}\n' +
        '.progress-bar-warning {\n' +
        '    border-bottom-color: #f0a839;\n' +
        '}\n' +
        '.progress-bar-danger {\n' +
        '    border-bottom-color: #ee2f31;\n' +
        '}	}');
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

function imprimir()
{
    PrintElem(document.getElementById('painel_autorizacoes'));
}

</script>

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

</body>
</html>