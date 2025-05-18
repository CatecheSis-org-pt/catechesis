<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/enrollment_functions.php');
require_once(__DIR__ . '/core/log_functions.php');
require_once(__DIR__ . "/core/Utils.php");
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/DataValidationUtils.php");
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . "/core/domain/Marriage.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . "/gui/widgets/Navbar/MainNavbar.php");

use catechesis\DataValidationUtils;
use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\UserData;
use catechesis\utils;
use core\domain\Marriage;
use core\domain\Sacraments;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;

// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::ENROLMENTS, true);
$pageUI->addWidget($menu);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>
      <?php
      if($_REQUEST['modo']=='editar')
          echo("Editar ficha");
      else
          echo('Inscrição na catequese');
      ?>
  </title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">

  <?php
  $pageUI->renderJS(); // Render the widgets' JS code
  ?>
</head>

<body>

<?php
$menu->renderHTML();
?>

<div class="container">
  <?php

    $db = new PdoDatabaseManager();

	if($_REQUEST['modo']=="editar")
	{
		echo("<h2>Actualização de dados</h2>");

        if(!Authenticator::isAdmin() && !catechumen_belongs_to_catechist($_SESSION['cid'], Authenticator::getUsername()))
        {
            echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
            echo("</div></body></html>");
            die();
        }
	}
	else
    {
        echo("<h2>Matrícula e inscrição na catequese</h2>");

        if(!Authenticator::isAdmin())
        {
            echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
            echo("</div></body></html>");
            die();
        }
    }



	//Funcao para criar link de retorno em caso de erro
	function abortar()
	{
		if($_REQUEST['modo']=="editar")
		{
			echo("<p><a href=\"inscricao.php?modo=editar\">Regressar à edição da ficha</a></p>");
			echo("<p><a href=\"mostrarFicha.php?cid=" . $_SESSION['cid'] . "\">Cancelar edição</a></p>");		
		
		}
		else
		{
			echo("<p><a href=\"inscricao.php?modo=regresso\">Regressar à ficha de inscrição</a></p>");
			echo("<p><a href=\"inscricao.php\">Iniciar uma nova ficha de inscrição</a></p>");
		}
		
		die();
	}



	
	
	// Carregamento das variáveis através do metodo POST
	if ($_SERVER["REQUEST_METHOD"] == "POST")
	{
	  	$nome = Utils::sanitizeInput($_POST['nome']);
        $data_nasc = Utils::sanitizeInput($_POST['data_nasc']);
        $nif = Utils::sanitizeInput($_POST['nif']);
	  	$local_nasc = Utils::sanitizeInput($_POST['localidade']);
	  	$num_irmaos = Utils::sanitizeInput($_POST['num_irmaos']);
	  	$morada = Utils::sanitizeInput($_POST['morada']);
	  	$codigo_postal = Utils::sanitizeInput($_POST['codigo_postal']);
	  	$telefone = Utils::sanitizeInput($_POST['telefone']);
	  	$telemovel = Utils::sanitizeInput($_POST['telemovel']);
	  	$escuteiro = Utils::sanitizeInput($_POST['escuteiro']);
	  	$baptizado = Utils::sanitizeInput($_POST['baptizado']);
	  	$paroquia_baptismo = Utils::sanitizeInput($_POST['paroquia_baptismo']);
	  	$data_baptismo = Utils::sanitizeInput($_POST['data_baptismo']);
	  	$comunhao = Utils::sanitizeInput($_POST['comunhao']);
	  	$paroquia_comunhao = Utils::sanitizeInput($_POST['paroquia_comunhao']);
	  	$data_comunhao = Utils::sanitizeInput($_POST['data_comunhao']);
	  	$enc_edu = Utils::sanitizeInput($_POST['enc_edu']);
	  	$outro_enc_edu_quem = Utils::sanitizeInput($_POST['outro_enc_edu_quem']);
	  	$outro_enc_edu_nome = Utils::sanitizeInput($_POST['nome_enc_edu']);
	  	$outro_enc_edu_prof = Utils::sanitizeInput($_POST['prof_enc_edu']);
	  	$pai = Utils::sanitizeInput($_POST['pai']);
	  	$prof_pai = Utils::sanitizeInput($_POST['prof_pai']);
	  	$mae = Utils::sanitizeInput($_POST['mae']);
	  	$prof_mae = Utils::sanitizeInput($_POST['prof_mae']);
	  	$casados = Utils::sanitizeInput($_POST['casados']);
	  	$casados_como = Utils::sanitizeInput($_POST['casados_como']);
	  	$email = Utils::sanitizeInput($_POST['email']);
	  	$rgpd_ee = Utils::sanitizeInput($_POST['consentimento_rgpd']);

        $quer_inscrever = Utils::sanitizeInput($_POST['quer_inscrever']);
        $catecismo = Utils::sanitizeInput($_POST['catecismo']);
        $turma = Utils::sanitizeInput($_POST['turma']);
        $pago = Utils::sanitizeInput($_POST['pago']);

        $foto_data = null;
        $foto_cam = null;
        $autorizacao_fotos = null;
        $autoriza_saida = null;
        $autorizacoes_saida_menores = null;
        $observacoes = null;
        if($_REQUEST['modo']=="aprovar")
        {
            $foto_cam = Utils::sanitizeInput($_POST['foto_file']);         // Caminho do ficheiro da foto, ja registada
            $autorizacao_fotos = Utils::sanitizeInput($_POST['autorizacao_fotos']);
            $observacoes = Utils::escapeSingleQuotes(Utils::escapeDoubleQuotes(Utils::removeLineManipulators(Utils::sanitizeInput(($_POST['observacoes'])))));

            //Autorizacao saida menores
            $autoriza_saida = Utils::sanitizeInput($_POST['autorizacao_saida']);
            $autorizacoes_saida_menores = array();
            $table_lines = count($_POST['autorizacao_nome']);
            for($i = 0; $i < $table_lines; $i = $i + 1)
            {
                $familiar = new stdClass();
                $familiar->nome = Utils::sanitizeInput($_POST['autorizacao_nome'][$i]);
                $familiar->parentesco = Utils::sanitizeInput($_POST['autorizacao_parentesco'][$i]);
                $familiar->telefone = Utils::sanitizeInput($_POST['autorizacao_telefone'][$i]);
                array_push($autorizacoes_saida_menores, $familiar);
            }

            $iid = intval(Utils::sanitizeInput($_POST['iid']));
        }
        else
        {
            try
            {
                $foto_data = Utils::sanitizeInput($_POST['foto_data']);                             // Foto codificada em base64
                $foto_cam = UserData::saveUploadedCatechumenPhoto($foto_data);                      // Devolve o nome do ficheiro onde a foto foi guardada (se existir)
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong>" . $e->getMessage() . "</div>");
            }
            $autorizacao_fotos = Utils::sanitizeInput($_POST['autorizacao']);
        }


	  	
	  	
	  		
	  	//Guarda valores originais para poder regressar
	  	$_SESSION['num_irmaos'] = $num_irmaos;
	  	$_SESSION['morada'] = $morada;
		$_SESSION['cod_postal'] = $codigo_postal;
		$_SESSION['telefone'] = $telefone;
		$_SESSION['telemovel'] = $telemovel;
		$_SESSION['enc_edu'] = $enc_edu;
		$_SESSION['outro_enc_edu_quem'] = $outro_enc_edu_quem;
		$_SESSION['nome_enc_edu'] = $outro_enc_edu_nome;
		$_SESSION['prof_enc_edu'] = $outro_enc_edu_prof;
		$_SESSION['pai'] = $pai;
		$_SESSION['prof_pai'] = $prof_pai;
		$_SESSION['mae'] = $mae;
		$_SESSION['prof_mae'] = $prof_mae;
		$_SESSION['casados'] = $casados;
		$_SESSION['casados_como'] = $casados_como;
		$_SESSION['email'] = $email;
		
		$_SESSION['nome'] = $nome;
        $_SESSION['data_nasc'] = $data_nasc;
        $_SESSION['nif'] = $nif;
		$_SESSION['local_nasc'] = $local_nasc;
		$_SESSION['escuteiro'] = $escuteiro;
		$_SESSION['baptizado'] = $baptizado;
		$_SESSION['paroquia_baptismo'] = $paroquia_baptismo;
		$_SESSION['data_baptismo'] = $data_baptismo;
		$_SESSION['comunhao'] = $comunhao;
		$_SESSION['paroquia_comunhao'] = $paroquia_comunhao;
		$_SESSION['data_comunhao'] = $data_comunhao;
		
		$_SESSION['foto_cam'] = $foto_cam;

		$_SESSION['quer_inscrever'] = $quer_inscrever;
		$_SESSION['catecismo'] = $catecismo;
		$_SESSION['turma'] = $turma;
		$_SESSION['pago'] = $pago;
 		
		
	  	
	  	//Pre-processamento de checkboxes (converter para bool)
        $casados = ($casados=="Sim");
        $escuteiro = ($escuteiro=="Sim");
        $autorizacao_fotos = ($autorizacao_fotos=="on");
        $autoriza_saida = ($autoriza_saida=="Sim");
        $rgpd_ee = ($rgpd_ee=="on");
        $baptizado = ($baptizado=="Sim");
        $comunhao = ($comunhao=="Sim");
        $quer_inscrever = ($quer_inscrever=="Sim");
        $pago = ($pago=="on");
	  		

	  		
	  		
	  		
	  	//Verificar inputs
	  	$inputs_invalidos = false;
	  	
	  	if(!DataValidationUtils::validateDate($data_nasc))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data de nascimento que introduziu é inválida. Deve ser da forma dd-mm-aaaa.</div>");
	  		var_dump($data_nasc);
	  		$inputs_invalidos = true;
	  	}

        if($nif != "" && !DataValidationUtils::validateNIF($nif))
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O número de identificação fiscal que introduziu é inválido.</div>");
            var_dump($nif);
            $inputs_invalidos = true;
        }
	  	
	  		  	
	  	if(!DataValidationUtils::validateZipCode($codigo_postal, Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O código postal que introduziu é inválido. Deve ser da forma 'xxxx-yyy Localidade'.</div>");
	  		var_dump($codigo_postal);
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	
	  	if($telefone!="" && !DataValidationUtils::validatePhoneNumber($telefone, Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O número de telefone que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.</div>");
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	if($telemovel!="" && !DataValidationUtils::validatePhoneNumber($telemovel, Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O número de telemóvel que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.</div>");
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	
	  	if($baptizado && !DataValidationUtils::validateDate($data_baptismo))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data de baptismo que introduziu é inválida. Deve ser da forma dd-mm-aaaa.</div>");
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	if($baptizado && (!$paroquia_baptismo || $paroquia_baptismo==""))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Deve especificar a paróquia de baptismo.</div>");
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	if($comunhao && !DataValidationUtils::validateDate($data_comunhao))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data de primeira comunhão que introduziu é inválida. Deve ser da forma dd-mm-aaaa.</div>");
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	if($comunhao && (!$paroquia_comunhao || $paroquia_comunhao==""))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Deve especificar a paróquia onde realizou a primeira comunhão.</div>");
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	
	  	if($enc_edu=="Pai" && (!$pai || $pai=="" || !$prof_pai || $prof_pai==""))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Deve especificar o nome e profissão do pai, porque é o encarregado de educação.</div>");
	  		$inputs_invalidos = true;
	  	}
	  	
	  	if($enc_edu=="Mae" && (!$mae || $mae=="" || !$prof_mae || $prof_mae==""))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Deve especificar o nome e profissão da mãe, porque é o encarregado de educação.</div>");
	  		$inputs_invalidos = true;
	  	}
	  	
	  	
	  	if($enc_edu=="Outro" && (!$outro_enc_edu_quem || $outro_enc_edu_quem=="" || !$outro_enc_edu_nome || $outro_enc_edu_nome=="" || !$outro_enc_edu_prof || $outro_enc_edu_prof==""))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Deve especificar o grau de parentesco, nome e profissão do encarregado de educação.</div>");
	  		$inputs_invalidos = true;
	  	}
	  	
	  	if($email && $email!="" && !DataValidationUtils::validateEmail($email))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O e-mail que introduziu é inválido.</div>");
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	if($foto_cam && $foto_cam!=''  && !file_exists(UserData::getCatechumensPhotosFolder() . '/' . $foto_cam))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não foi possível guardar a fotografia no servidor.</div>");
	  		$inputs_invalidos = true;
	  	}

	  	if($quer_inscrever && ($catecismo=="" || $turma==""))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não foi especificado o catecismo e/ou grupo para a inscrição.</div>");
	  		$inputs_invalidos = true;	  	
	  	}
	  	
	  	if($inputs_invalidos)
	  	{
	  		abortar();
	  	}
	  		
	  	
	  	

	  	
	  	
	  	$result = NULL;
	  	$fid_pai = NULL;
	  	$fid_pai_old = NULL;
	  	$fid_mae = NULL;
	  	$fid_mae_old = NULL;
	  	$fid_ee = NULL;	
	  	$fid_ee_old = NULL;
	  	$quem_ee_old = NULL;
	  	$foto_antiga = NULL;


	  	if($_REQUEST['modo']!="editar")
	  	{
		  	//Verificar se catequizando ja existe
			try
            {
                $result = $db->getCatechumensByNameAndBirthdate($nome, $data_nasc);

				if (isset($result) && count($result)>=1)
				{
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Catequizando já inscrito anteriormente!</div>");
                    abortar();
				}
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}
		else
		{
			//Modo edicao - obter dados do catequizando
			try
            {
                $request_cid = intval($_SESSION['cid']);
                $result = $db->getCatechumenById($request_cid);

				if(isset($result))
                {
                    $fid_pai_old = $fid_pai = $result['pai'];
                    $fid_mae_old = $fid_mae = $result['mae'];
                    $fid_ee_old = $result['enc_edu'];
                    $quem_ee_old = $result['enc_edu_quem'];
                    $foto_antiga = $result['foto'];
                }
				else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao obter ficha do catequizando para actualização.</div>");
                    abortar();
                }
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}



		//Obter o pai, se existir
		$result = NULL;
		if(($_REQUEST['modo']!="editar" && $pai && $pai!="")    ||   ($_REQUEST['modo']=="editar" && $pai && $pai!="" && !$fid_pai))
		{
			try
            {
                $result = $db->getFamilyMembersByName($pai);
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
			
			if(empty($result)) //Ainda nao existe o pai (registar)
			{
				try
                {
                    $fid_pai = $db->createFamilyMember($pai, $prof_pai);
				}
                catch (Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    abortar();
                }
			}	
			else //Pai ja existe (actualizar dados)
			{
			    if(count($result) > 1)
                    echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Aviso!</strong> Existe mais do que um encarregado de educação com o nome $pai na base de dados. A base de dados poderá estar inconsistente. As fichas poderão ter sido atualizadas erradamente.</div>");

			    $fid_pai = $result[0]["fid"]; 	//Obter id do pai
                $prof_pai_old = $result[0]["prof"];	//Obter profissao do pai
		
				echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Aviso!</strong> O pai já existia na base de dados. Isto é normal se estiver a registar um irmão de um catequizando ou a actualizar uma ficha.</div>");
				
			
				//Dados existentes desactualizados (actualizar)
				if($prof_pai_old!=$prof_pai)
				{
					try
					{
						if($db->updateFamilyMemberJob($fid_pai, $prof_pai))
						{
                            catechumenFileLog($_SESSION['cid'], "Actualizados dados do pai do catequizando com id=" . $_SESSION['cid'] . ". As fichas de todos os seus filhos / educandos foram também actualizadas.", true); //Pode afectar fichas de irmaos
                            echo("<div class=\"alert alert-info\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Info!</strong> Os dados do pai foram automaticamente actualizados nas fichas de todos os filhos.</div>");
						}
						else
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar dados do pai.</div>");
                            abortar();
                        }
					}
                    catch (Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        abortar();
                    }
				}
				//else Dados existente actualizados
			}
		}
		else if($_REQUEST['modo']=="editar" && ($pai || $pai!="") && $fid_pai)	//Modo edicao - modificou dados do pai e este ja existia
		{
			try
            {
				if($db->updateFamilyMemberName($fid_pai, $pai) && $db->updateFamilyMemberJob($fid_pai, $prof_pai))
				{
				    catechumenFileLog($_SESSION['cid'], "Actualizados dados do pai do catequizando com id=" . $_SESSION['cid'] . ". As fichas de todos os seus filhos / educandos foram também actualizadas.", true); //Pode afectar fichas de irmaos
				    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Os dados do pai foram automaticamente actualizados nas fichas de todos os filhos.</div>");
				}
				else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha interna ao tentar aceder à base de dados. Impossível actualizar dados do pai.</div>");
                    abortar();
				}	    
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}
		else if($_REQUEST['modo']=="editar" && (!$pai || $pai=="") && $fid_pai_old)	//Modo edicao - apagou pai
		{
			$fid_pai = NULL;
			catechumenFileLog($_SESSION['cid'], "Removidos dados do pai do catequizando com id=" . $_SESSION['cid'] . ".");
		}
		
		

		
		
		//Obter a mae, se existir
		$result = NULL;
		if(($_REQUEST['modo']!="editar" && $mae && $mae!="")   ||   ($_REQUEST['modo']=="editar" && $mae && $mae!="" && !$fid_mae))
		{
			try
            {
                $result = $db->getFamilyMembersByName($mae);
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
			
			if(!isset($result) || empty($result)) //Ainda nao existe a mae (registar)
			{
				try
                {
                    $fid_mae = $db->createFamilyMember($mae, $prof_mae);
				}
                catch (Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    abortar();
                }
			}	
			else //Mae ja existe (actualizar dados)
			{
                if(count($result) > 1)
                    echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Aviso!</strong> Existe mais do que um encarregado de educação com o nome $mae na base de dados. A base de dados poderá estar inconsistente. As fichas poderão ter sido atualizadas erradamente.</div>");

                $fid_mae = $result[0]["fid"]; 	    //Obter id da mae
                $prof_mae_old = $result[0]["prof"];	//Obter profissao da mae
		
				echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Aviso!</strong> A mãe já existia na base de dados. Isto é normal se estiver a registar um irmão de um catequizando ou a actualizar uma ficha.</div>");
				
			
				//Dados existentes desactualizados (actualizar)
				if($prof_mae_old!=$prof_mae)
				{
					try
					{
						if($db->updateFamilyMemberJob($fid_mae, $prof_mae))
						{
                            catechumenFileLog($_SESSION['cid'], "Actualizados dados da mãe do catequizando com id=" . $_SESSION['cid'] . ". As fichas de todos os seus filhos / educandos foram também actualizadas.", true); //Pode afectar fichas de irmaos
                            echo("<div class=\"alert alert-info\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Info!</strong> Os dados da mãe foram automaticamente actualizados nas fichas de todos os filhos.</div>");
						}else
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar dados da mãe.</div>");
                            abortar();
                        }
					}
                    catch (Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        abortar();
                    }
				}
				//else Dados existente actualizados
			}
		}
		else if($_REQUEST['modo']=="editar" && ($mae || $mae!="") && $fid_mae)	//Modo edicao - modificou dados da mae e esta ja existia
		{
			try
            {
				if($db->updateFamilyMemberName($fid_mae, $mae) && $db->updateFamilyMemberJob($fid_mae, $prof_mae))
				{
				    catechumenFileLog($_SESSION['cid'], "Actualizados dados da mãe do catequizando com id=" . $_SESSION['cid'] . ". As fichas de todos os seus filhos / educandos foram também actualizadas.", true); //Pode afectar fichas de irmaos
				    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Os dados da mãe foram automaticamente actualizados nas fichas de todos os filhos.</div>");
				}
				else
                {
					echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha interna ao tentar aceder à base de dados. Impossível actualizar dados da mãe.</div>");
				    abortar();
				}	    
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}
		else if($_REQUEST['modo']=="editar" && (!$mae || $mae=="") && $fid_mae_old)	//Modo edicao - apagou mae
		{
			$fid_mae = NULL;
			catechumenFileLog($_SESSION['cid'], "Removidos dados da mãe do catequizando com id=" . $_SESSION['cid'] . ".");
		}
		
		
		
		
		
		
		//Marriage

        $existingMarriageOldParents = null;
        $existingMarriageNewParents = null;
        try
        {
            // Check existing information in the database
            if(!empty($fid_pai_old) && !empty($fid_mae_old))
                $existingMarriageOldParents = $db->getMarriageInformation($fid_pai_old, $fid_mae_old);
            if(!empty($fid_pai) && !empty($fid_mae))
                $existingMarriageNewParents = $db->getMarriageInformation($fid_pai, $fid_mae);
        }
        catch (Exception $e)
        {
        }

        // Decide which action should be made with respect to this marriage
        $marriageAction = "";
        if($casados && $fid_pai && $fid_mae && !$existingMarriageNewParents)
            $marriageAction = "add";        // Register the marriage
        else if(!$casados && $fid_pai_old && $fid_mae_old && $existingMarriageOldParents)
            $marriageAction = "remove";     // Remove the marriage from the database
        else if($casados && $fid_pai && $fid_mae && $existingMarriageNewParents && $existingMarriageNewParents["como"]!=$casados_como)
            $marriageAction = "change";     // Update the marriage type in the database


        // Apply the necessary actions
        if($marriageAction == "remove" || $marriageAction == "change")
        {
            //Remove marriage :(
            try
            {
                if ($db->deleteMarriage($fid_pai_old, $fid_mae_old))
                {
                    if ($marriageAction == "remove")
                    {
                        catechumenFileLog($_SESSION['cid'], "Removido registo de casamento dos pais do catequizando com id=" . $_SESSION['cid'] . " (familiares com ID " . $fid_pai_old . " e " . $fid_mae_old . "). As fichas de todos os seus filhos / educandos foram também actualizadas.", true); //Pode afectar fichas de irmaos
                        echo("<div class=\"alert alert-info\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Info!</strong> Registo de casamento eliminado.</div>");
                    }
                }
                else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar registo de casamento.</div>");
                    abortar();
                }
            }
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
        }
        if($marriageAction == "add" || $marriageAction == "change")
        {
            // Register marriage
            try
            {
                $unionType = Utils::marriageTypeFromString($casados_como);

                if ($db->addMarriageInformation($fid_pai, $fid_mae, $unionType))
                {
                    if($marriageAction == "add")
                    {
                        catechumenFileLog($_SESSION['cid'], "Adicionado registo de casamento dos familiares com ID " . $fid_mae . " e " . $fid_pai . ". As fichas de todos os seus filhos / educandos foram também actualizadas.", true); //Pode afectar fichas de irmaos
                        //Note: $_SESSION['cid'] is not defined when we are making or approving a new enrollment, so this log message will not be linked to the catechumen file.
                    }
                    else
                    {
                        catechumenFileLog($_SESSION['cid'], "Modificado o tipo de registo de casamento dos familiares com ID " . $fid_mae . " e " . $fid_pai . ". As fichas de todos os seus filhos / educandos foram também actualizadas.", true); //Pode afectar fichas de irmaos
                        echo("<div class=\"alert alert-info\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Info!</strong> Modificado o tipo de registo de casamento.</div>");
                    }
                }
                else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao registar casamento.</div>");
                    abortar();
                }
            }
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
        }



		//Decidir quem e' o encarregado de educacao
		$nome_ee = NULL;
		$prof_ee = NULL;

		if($enc_edu=="Pai")
		{
			$nome_ee = $pai;
			$prof_ee = $prof_pai;
		}
		else if($enc_edu=="Mae")
		{
			$nome_ee = $mae;
			$prof_ee = $prof_mae;
		}
		else //$enc_edu=="Outro"
		{
			$nome_ee = $outro_enc_edu_nome;
			$prof_ee = $outro_enc_edu_prof;
		}
		
		
		
		
		
		//Registar ou actualizar encarregado de educacao
		$result = NULL;
		try
        {
            $result = $db->getFamilyMembersByName($nome_ee);
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            abortar();
        }
		
		if(!isset($result) || empty($result)) //Ainda nao existe o familiar (registar)
		{
			try
            {
                $fid_ee = $db->createFamilyMember($nome_ee, $prof_ee, $morada, $codigo_postal,
                                            ($telefone && $telefone!=0)?$telefone:null,
                                          ($telemovel && $telemovel!="" && $telemovel!=0)?$telemovel:null,
                                             ($email && $email!="")?$email:null,
                                                    $rgpd_ee);
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}	
		else //Familiar ja existe (actualizar dados)
		{
            if(count($result) > 1)
                echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Aviso!</strong> Existe mais do que um encarregado de educação com o nome $nome_ee na base de dados. A base de dados poderá estar inconsistente. As fichas poderão ter sido atualizadas erradamente.</div>");

            $fid_ee = $result[0]["fid"];
            $nome_outro_ee_old = $result[0]["nome"];
            $prof_outro_old = $result[0]["prof"];
            $morada_old = $result[0]["morada"];
            $cod_postal_old = $result[0]["cod_postal"];
            $telefone_old = $result[0]["telefone"];
            $telemovel_old = $result[0]["telemovel"];
            $email_old = $result[0]["email"];
            $rgpd_ee_old = $result[0]['RGPD_assinado'];
            $rgpd_ee_old = ($rgpd_ee_old==1); //Convert to bool

			if($fid_ee!=$fid_pai && $fid_ee!=$fid_mae)
				echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Aviso!</strong> O encarregado de educação já existia na base de dados. Isto é normal se estiver a registar um irmão de um catequizando ou a actualizar uma ficha.</div>");


			//Dados existentes desactualizados (actualizar)
			if(	//($nome_outro_ee_old!=$nome_ee) ||                 //Quando o nome muda, o enc edu e' eliminado e um novo e' criado...
                ($prof_outro_old!=$prof_ee) ||
				($morada_old!=$morada) ||
				($cod_postal_old!=$codigo_postal) ||
				($telefone_old!=$telefone) ||
				($telemovel_old!=$telemovel) ||
				($email_old!=$email) ||
                ($rgpd_ee_old!=$rgpd_ee))
			{
				try
				{
					if($db->updateFamilyMemberAllFields($fid_ee, $nome_ee, $prof_ee, $morada, $codigo_postal,
                        $telefone, $telemovel, $email, $rgpd_ee))
					{
						if($fid_ee!=$fid_pai && $fid_ee!=$fid_mae)
						{
					    		echo("<div class=\"alert alert-info\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Info!</strong> Os dados do encarregado de educação foram automaticamente actualizados nas fichas de todos os filhos e educandos.</div>");
					    	
					    		if($_REQUEST['modo']=="editar")
					    			catechumenFileLog($_SESSION['cid'], "Actualizados dados do encarregado de educação do catequizando com id=" . $_SESSION['cid'] . ". As fichas de todos os seus filhos / educandos foram também actualizadas.", true); //Pode afectar fichas de irmaos
                        }
					}
					else
                    {
						echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar dados do encarregado de educação.</div>");
						abortar();
                     }
				}
                catch (Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    abortar();
                }
			}
			//else Dados existente actualizados
		}
	

		
		
		
		
		//Regista ou actualiza o catequizando
		try
        {
			if($_REQUEST['modo']=="editar")
            {
                //Atualizar ficha
                if($db->updateCatechumen($_SESSION['cid'], $nome, $data_nasc, $local_nasc, $nif, $fid_pai, $fid_mae, $fid_ee,
                                        $outro_enc_edu_quem, $foto_cam, $num_irmaos, $escuteiro, $autorizacao_fotos))
                {
                    $cid = $_SESSION['cid'];

                    catechumenFileLog($_SESSION['cid'], "Actualizada ficha do catequizando com id=" . $_SESSION['cid'] . ".");
                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Ficha de $nome actualizada!</div>");
                }
                else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar ficha do catequizando.</div>");
                    abortar();
                }
            }
			else if($_REQUEST['modo']=="aprovar")
            {
                //Aprovar inscricao online
                $cid = $db->createCatechumen($nome, $data_nasc, $local_nasc, $nif, $fid_pai, $fid_mae, $fid_ee, $outro_enc_edu_quem,
                                                $foto_cam, $num_irmaos, $escuteiro, $autorizacao_fotos,
                                                $autoriza_saida,$observacoes, Authenticator::getUsername());

                catechumenFileLog($cid, "Inscrito catequizando " . $nome . ".");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> $nome matriculado(a)!</div>");

            }
			else
            {
                //Nova inscricao
                $cid = $db->createCatechumen($nome, $data_nasc, $local_nasc, $nif, $fid_pai, $fid_mae, $fid_ee, $outro_enc_edu_quem,
                                                $foto_cam, $num_irmaos, $escuteiro, $autorizacao_fotos,
                                                false,"", Authenticator::getUsername());

                catechumenFileLog($cid, "Inscrito catequizando " . $nome . ".");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> $nome matriculado(a)!</div>");
            }


            //Se havia foto e agora nao ha OU Se a foto mudou
            if(((!$foto_cam || $foto_cam=="") && $foto_antiga && $foto_antiga!="") || ($foto_cam && $foto_antiga && $foto_cam!=$foto_antiga))
            {
                //Eliminar foto antiga
                unlink(UserData::getCatechumensPhotosFolder() . '/' . $foto_antiga);
            }
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            abortar();
        }
		
		

		
		//Modo edicao - apagou pai
		if($_REQUEST['modo']=="editar" && (!$pai || $pai=="") && $fid_pai_old)	
		{
			//Verificar se ainda ha filhos deste pai. Caso contrario, apagar o pai da BD
			try
            {
                $result = $db->getFamilyMemberChildren($fid_pai_old);
				    
                if(empty($result))
                {
                    if($db->deleteFamilyMember($fid_pai_old))
                    {
                        echo("<div class=\"alert alert-info\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Info!</strong> O pai foi eliminado da base de dados, pois não existem outros filhos seus.</div>");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha interna ao tentar aceder à base de dados. Impossível actualizar dados do pai.</div>");
                        abortar();
                    }
				}
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}

		
		
		//Modo edicao - apagou mae
		if($_REQUEST['modo']=="editar" && (!$mae || $mae=="") && $fid_mae_old)	
		{
			//Verificar se ainda ha filhos desta mae. Caso contrario, apagar a mae da BD
			try
            {
                $result = $db->getFamilyMemberChildren($fid_mae_old);

                if(empty($result))
                {
                    if($db->deleteFamilyMember($fid_mae_old))
                    {
                        echo("<div class=\"alert alert-info\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Info!</strong> A mãe foi eliminada da base de dados, pois não existem outros filhos seus.</div>");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha interna ao tentar aceder à base de dados. Impossível actualizar dados da mãe.</div>");
                        abortar();
                    }
                }
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}

		
		//Pode ser que o encarregado de educacao antigo ja nao seja nem pai, nem mae, nem enc_edu de ninguem. Nesse caso apagar.
		if($_REQUEST['modo']=="editar" && $fid_ee!=$fid_ee_old)
		{
			try
            {
                $result = $db->getFamilyMemberChildren($fid_ee_old);

                if(empty($result)) //Queremos saber se nao ha outros catequizandos
                {
                    if($db->deleteFamilyMember($fid_ee_old))
                    {
                        echo("<div class=\"alert alert-info\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Info!</strong> O antigo encarregado de educação (" . $quem_ee_old . ") foi eliminado da base de dados, pois não existem outros filhos ou dependentes seus.</div>");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha interna ao tentar aceder à base de dados. Impossível eliminar antigo encarregado de educação.</div>");
                        abortar();
                    }
                }
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}


				
		//Registar baptismo, se tiver
		if($_REQUEST['modo']!="editar" && $baptizado && $data_baptismo && $paroquia_baptismo)
		{
			try
            {
				if($db->insertSacramentRecord($cid, Sacraments::BAPTISM, $data_baptismo, $paroquia_baptismo))
				{
				    catechumenArchiveLog($cid, "Registo de baptismo do catequizando com id=" . $cid . ".");
				    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Baptismo de $nome registado!</div>");
				}
				else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao registar baptismo do catequizando.</div>");
                    abortar();
                }
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}
		
		
		
		//Registar primeira comunhao, se tiver
		if($_REQUEST['modo']!="editar" && $comunhao && $data_comunhao && $paroquia_comunhao)
		{
			try
            {
				if($db->insertSacramentRecord($cid, Sacraments::FIRST_COMMUNION, $data_comunhao, $paroquia_comunhao))
				{
				    catechumenArchiveLog($cid, "Registo de primeira comunhão do catequizando com id=" . $cid . ".");
				    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Primeira comunhão de $nome registada!</div>");
				}
				else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao registar primeira comunhão do catequizando.</div>");
                    abortar();
                }
						    
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}



		//Registar autorizacao de saida de menores
        foreach($autorizacoes_saida_menores as $familiar)
        {
            $name = $familiar->nome;
            $relationship = $familiar->parentesco;
            $phone = $familiar->telefone;


            if(!$name || $name == "")
            {
                //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> É necessário definir o nome do familiar do catequizando.</div>");
                $inputs_invalidos = true;
            }
            if($phone!="" && !DataValidationUtils::validatePhoneNumber($phone, Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE))) //Podemos admitir familiares sem telefone
            {
                //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O número de telemóvel que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.</div>");
                $inputs_invalidos = true;
            }
            /*if(!$relationship || $relationship == "") //Podemos admitir nao preencher o parentesco (ex: para colaboradores Regaco Materno)
            {
                //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> É necessário definir um parentesco para o familiar do catequizando.</div>");
                $inputs_invalidos = true;
            }*/

            if($inputs_invalidos) //Ignore empty or invalid table rows
                continue;


            //Adiciona a' lista de familiares que podem vir buscar o catequizando
            addAuthorizationToList($cid, $name, $relationship, $phone);
        }


		//Inscricao num grupo de catequese
        if($_REQUEST['modo']!="editar" && $quer_inscrever)
        {

            $ins_ano_catequetico = Utils::currentCatecheticalYear();
            $ins_pago = $pago;
            $ins_catecismo = intval($catecismo);
            $ins_turma = $turma;

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
                    if($db->enrollCatechumenInGroup($cid, $ins_ano_catequetico, $ins_catecismo, $ins_turma, true, $ins_pago, Authenticator::getUsername()))
                    {
                            catechumenArchiveLog($cid, "Catequizando com id=" . $cid . " inscrito no " . $ins_catecismo . "º" . $ins_turma . ", no ano catequético de " . Utils::formatCatecheticalYear($ins_ano_catequetico) . ".");
                            if($ins_pago)
                                catechumenArchiveLog($cid, "Pagamento do catequizando com id=" . $cid . " referente ao catecismo " . $ins_catecismo . "º" . $ins_turma . " do ano catequético de " . Utils::formatCatecheticalYear($ins_ano_catequetico) . ".");
                            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Catequizando inscrito no " . $ins_catecismo . "º catecismo!</div>");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao inscrever o catequizando no grupo de catequese. Tente novamente a inscrição a partir do Arquivo.</div>");

;
                    }

                }
                catch (Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    abortar();
                }
            }
        }


        if($_REQUEST['modo']=="aprovar")
        {
            //Marcar pedido de inscricao como processado
            setEnrollmentOrderAsProcessed($iid, $cid);
        }
	
	}
	else
	{
		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não foi processada nenhuma inscrição.</div>");
		//Libertar recursos
		$db = null;
		$result = null;
		die();
	}
	
	
	
	
	
	
	if($_REQUEST['modo']!="editar" && $_REQUEST['modo']!="aprovar")
	{
		//Link para nova inscricao
		echo("<p><a href=\"inscricao.php\">Inscrever um novo catequizando</a></p>");

		//Sugerir inscrever um irmao
		echo("<p>Talvez queira <a href=\"inscricao.php?modo=irmao\">inscrever um irmão</a> deste catequizando.</p>");
	}
	
	//Link para a ficha
	echo("<p><a href=\"mostrarFicha.php?cid=" . $cid . "\">Mostrar ficha do catequizando</a></p>");
	
	//Link para o arquivo
	echo("<p><a href=\"mostrarArquivo.php?cid=" . $cid . "\">Mostrar/actualizar arquivo do catequizando</a></p>");
	

	

	//Libertar recursos
	$db = null;
	$result = null;

?>
  </div>
</body>
</html>