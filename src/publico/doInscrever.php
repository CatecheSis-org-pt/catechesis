<?php

require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../core/DataValidationUtils.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../core/UserData.php');
require_once(__DIR__ . '/../core/enrollment_functions.php');
require_once(__DIR__ . '/../authentication/securimage/securimage.php');  //Captcha
require_once(__DIR__ . '/../core/Configurator.php');
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../core/domain/Marriage.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/../gui/widgets/Navbar/MinimalNavbar.php');
require_once(__DIR__ . '/../gui/widgets/Footer/SimpleFooter.php');

use catechesis\Configurator;
use catechesis\DataValidationUtils;
use catechesis\PdoDatabaseManager;
use catechesis\UserData;
use catechesis\Utils;
use core\domain\Marriage;
use catechesis\gui\WidgetManager;
use catechesis\gui\MinimalNavbar;
use catechesis\gui\SimpleFooter;

$db = new PdoDatabaseManager();

//Verificar se o periodo de inscricoes esta ativo
$periodo_activo = false;
try
{
    // Check if the new configuration key exists, if not, use the legacy key value
    if (Configurator::configurationExists(Configurator::KEY_ONLINE_ENROLLMENTS_NEW_OPEN))
        $periodo_activo = Configurator::getConfigurationValueOrDefault(Configurator::KEY_ONLINE_ENROLLMENTS_NEW_OPEN);
    else
        $periodo_activo = Configurator::getConfigurationValueOrDefault(Configurator::KEY_ONLINE_ENROLLMENTS_OPEN);
}
catch (Exception $e)
{
}

if(!$periodo_activo)
{
    header("Location: inscricoes.php");
    die();
}



//Funcao para criar link de retorno em caso de erro
function abortar()
{
    echo("<p><a href=\"inscrever.php?modo=regresso\">Regressar à ficha de inscrição</a></p>");
    echo("<p><a href=\"inscrever.php\">Iniciar uma nova ficha de inscrição</a></p>");

    die();
}


// Instantiate a widget manager
$pageUI = new WidgetManager("../");

// Add widgets
$navbar = new MinimalNavbar();
$pageUI->addWidget($navbar);
$footer = new SimpleFooter(null, true);
$pageUI->addWidget($footer);


?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Inscrição na catequese</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
  <link rel="icon" href="../img/favicon.png" type="image/x-icon">

  <?php $pageUI->renderCSS(); ?>

    <style>
        @media print
        {
            .no-print, .no-print *
            {
                display: none !important;
            }

            a[href]:after
            {
                content: none !important;
            }
        }
    </style>
</head>

<body>


<?php
$navbar->renderHTML();
?>


<div class="container">

<?php

	if($_REQUEST['modo']=="editar")
	{
		?>
    <div class="no-print">
        <h2 class> Editar pedido de inscrição</h2>
        <div class="row" style="margin-bottom:40px; "></div>
    </div>
    <?php
	}
	else
    {
        ?>
    <div class="">
        <h2 class> Inscrição na catequese</h2>
        <div class="row" style="margin-bottom:40px; "></div>
    </div>
    <?php
    }
?>


<?php


	// Carregamento das variáveis através do metodo POST
	if ($_SERVER["REQUEST_METHOD"] == "POST")
	{
        //Dados biograficos do catequizando
        $foto_data = Utils::sanitizeInput($_POST['foto_data']);		// Foto codificada em base64
	  	$nome = Utils::sanitizeInput($_POST['nome']);
        $data_nasc = Utils::sanitizeInput($_POST['data_nasc']);
        $nif = Utils::sanitizeInput($_POST['nif']);
        $nifEnabled = Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED);
        if(!$nifEnabled) { $nif = null; }
	  	$local_nasc = Utils::sanitizeInput($_POST['localidade']);
	  	$num_irmaos = intval(Utils::sanitizeInput($_POST['num_irmaos']));

	  	//Filiacao
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
	  	$morada = Utils::sanitizeInput($_POST['morada']);
	  	$codigo_postal = Utils::sanitizeInput($_POST['codigo_postal']);
	  	$telefone = Utils::sanitizeInput($_POST['telefone']);
	  	$telemovel = Utils::sanitizeInput($_POST['telemovel']);
        $email = Utils::sanitizeInput($_POST['email']);

        //Percurso catequetico
        $ja_frequentou_catequese = Utils::sanitizeInput($_POST['ja_frequentou_catequese']);
        $ultimo_catecismo = intval(Utils::sanitizeInput($_POST['ultimo_catecismo']));
        $escuteiro = Utils::sanitizeInput($_POST['escuteiro']);
	  	$baptizado = Utils::sanitizeInput($_POST['baptizado']);
	  	$paroquia_baptismo = Utils::sanitizeInput($_POST['paroquia_baptismo']);
	  	$data_baptismo = Utils::sanitizeInput($_POST['data_baptismo']);
	  	$comunhao = Utils::sanitizeInput($_POST['comunhao']);
	  	$paroquia_comunhao = Utils::sanitizeInput($_POST['paroquia_comunhao']);
	  	$data_comunhao = Utils::sanitizeInput($_POST['data_comunhao']);

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

        //Observacoes
        $observacoes = Utils::escapeSingleQuotes(Utils::escapeDoubleQuotes(Utils::removeLineManipulators(Utils::sanitizeInput(($_POST['observacoes'])))));

        //Autorizacoes finais
	  	$autorizacao_fotos = Utils::sanitizeInput($_POST['autorizacao_fotos']);
	  	$declaracao_enc_edu = Utils::sanitizeInput($_POST['declaracao_enc_edu']);
	  	$rgpd_ee = Utils::sanitizeInput($_POST['rgpd']);
        $captchaCode = Utils::sanitizeInput($_POST['captcha_code']);
        $captchaId = Utils::sanitizeInput($_POST['captchaId']);
        $captcha_options = array();





        //Guarda valores originais para poder regressar
        $_SESSION['foto_data'] = $foto_data;
        $_SESSION['nome'] = $nome;
        $_SESSION['data_nasc'] = $data_nasc;
        $_SESSION['nif'] = $nif;
        $_SESSION['local_nasc'] = $local_nasc;
        $_SESSION['num_irmaos'] = $num_irmaos;

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
	  	$_SESSION['morada'] = $morada;
		$_SESSION['cod_postal'] = $codigo_postal;
		$_SESSION['telefone'] = $telefone;
		$_SESSION['telemovel'] = $telemovel;
		$_SESSION['email'] = $email;

        $_SESSION['ja_frequentou_catequese'] = $ja_frequentou_catequese;
        $_SESSION['ultimo_catecismo'] = $ultimo_catecismo;
        $_SESSION['escuteiro'] = $escuteiro;
		$_SESSION['baptizado'] = $baptizado;
		$_SESSION['paroquia_baptismo'] = $paroquia_baptismo;
		$_SESSION['data_baptismo'] = $data_baptismo;
		$_SESSION['comunhao'] = $comunhao;
		$_SESSION['paroquia_comunhao'] = $paroquia_comunhao;
		$_SESSION['data_comunhao'] = $data_comunhao;

        $_SESSION['autorizacao_saida'] = $autoriza_saida;
        $_SESSION['autorizacao_nome'] = $_POST['autorizacao_nome'];
        $_SESSION['autorizacao_parentesco'] = $_POST['autorizacao_parentesco'];
        $_SESSION['autorizacao_telefone'] = $_POST['autorizacao_telefone'];

        $_SESSION['observacoes'] = $observacoes;
        $_SESSION['autorizacao_fotos'] = $autorizacao_fotos;
        $_SESSION['declaracao_enc_edu'] = $declaracao_enc_edu;
        $_SESSION['rgpd'] = $rgpd_ee;





	  	//Pre-processamento de checkboxes
	  	if($casados=="Sim")
	  		$casados = true;
	  	else
	  	{
            $casados = false;
            $casados_como = null;
        }

	  	if($escuteiro=="Sim")
	  		$escuteiro = true;
	  	else
	  		$escuteiro = false;

	  	if($autorizacao_fotos=="aceito")
	  		$autorizacao_fotos=true;
	  	else
	  		$autorizacao_fotos=false;

        if($rgpd_ee=="aceito")
            $rgpd_ee=true;
        else
            $rgpd_ee=false;

	  	if($baptizado=="Sim")
	  		$baptizado=1;
	  	else
        {
            $baptizado=0;
            $data_baptismo = null;
            $paroquia_baptismo = null;
        }

	  	if($comunhao=="Sim")
	  		$comunhao=1;
	  	else
        {
            $comunhao = 0;
            $data_comunhao = null;
            $paroquia_comunhao = null;
        }

	  	if($autoriza_saida=="Sim")
            $autoriza_saida = true;
	  	else
            $autoriza_saida = false;

        if($ja_frequentou_catequese=="Sim")
            $ja_frequentou_catequese = true;
        else
        {
            $ja_frequentou_catequese = false;
            $ultimo_catecismo = null;
        }







	  	//Verificar inputs
	  	$inputs_invalidos = false;

	  	if(!DataValidationUtils::validateDate($data_nasc))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data de nascimento que introduziu é inválida. Deve ser da forma dd-mm-aaaa.</div>");
	  		$inputs_invalidos = true;	  	
	  	}

        if($nifEnabled)
        {
            if(!$nif || $nif==="" || !DataValidationUtils::validateNIF($nif))
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O número de identificação fiscal que introduziu é inválido.</div>");
                $inputs_invalidos = true;
            }
        }


	  	if(!DataValidationUtils::validateZipCode($codigo_postal, Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE)))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O código postal que introduziu é inválido. Deve ser da forma 'xxxx-yyy Localidade'.</div>");
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


	  	if($baptizado==1 && !DataValidationUtils::validateDate($data_baptismo))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data de baptismo que introduziu é inválida. Deve ser da forma dd-mm-aaaa.</div>");
	  		$inputs_invalidos = true;	  	
	  	}

	  	if($baptizado==1 && (!$paroquia_baptismo || $paroquia_baptismo==""))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Deve especificar a paróquia de baptismo.</div>");
	  		$inputs_invalidos = true;	  	
	  	}

	  	if($comunhao==1 && !DataValidationUtils::validateDate($data_comunhao))
	  	{
	  		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data de primeira comunhão que introduziu é inválida. Deve ser da forma dd-mm-aaaa.</div>");
	  		$inputs_invalidos = true;	  	
	  	}

	  	if($comunhao==1 && (!$paroquia_comunhao || $paroquia_comunhao==""))
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

	  	if($casados)
        {
            if($casados_como == "igreja")
                $casados_como = Marriage::CHURCH;
            else if($casados_como == "civil")
                $casados_como = Marriage::CIVIL;
            else if($casados_como == "uniao de facto")
                $casados_como = Marriage::DE_FACTO_UNION;
            else
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Tipo de união dos pais desconhecido.</div>");
                $inputs_invalidos = true;
            }
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

	  	if(isset($ultimo_catecismo) && ($ultimo_catecismo=="" || $ultimo_catecismo < 1 || $ultimo_catecismo > 10) )
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O valor do campo último catecismo frequentado é inválido.</div>");
            $inputs_invalidos = true;
        }

        if(!isset($declaracao_enc_edu) || $declaracao_enc_edu!="aceito")
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não aceitou a declaração de responsabilidade legal pelo educando. A aceitação deste campo é obrigatória para proceder à inscrição na catequese.</div>");
            $inputs_invalidos = true;
        }

        if(!isset($rgpd_ee) || $rgpd_ee!="aceito")
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não aceitou a declaração de tratamento de dados. A aceitação deste campo é obrigatória para proceder à inscrição na catequese.</div>");
            $inputs_invalidos = true;
        }

        //Captcha validator
        if (!isset($captchaCode) || !Securimage::checkByCaptchaId($captchaId, $captchaCode, $captcha_options))
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O código de segurança que introduziu não corresponde ao mostrado na imagem.</div>");
            $inputs_invalidos = true;
        }


	  	if($inputs_invalidos)
	  	{
	  		abortar();
	  	}



        //Registar o pedido de inscricao na BD
        if(!$inputs_invalidos)
        {
            //Decidir quem e' o encarregado de educacao
            $responsibleIndex = -1;

            if ($enc_edu == "Pai")
                $responsibleIndex = 0;
            else if ($enc_edu == "Mae")
                $responsibleIndex = 1;
            else //$enc_edu=="Outro"
                $responsibleIndex = 2;


            $eid = null;
            try
            {
                //Processar foto
                $foto_cam = UserData::saveUploadedCatechumenPhoto($foto_data);			                                // Devolve o nome do ficheiro onde a foto foi guardada (se existir)

                $eid = $db->postEnrollmentOrder($nome, $data_nasc, $local_nasc, $nif, $num_irmaos,
                    $morada, $codigo_postal,
                    $responsibleIndex, $_SERVER['REMOTE_ADDR'],
                    $escuteiro, $autorizacao_fotos, $autoriza_saida, $autorizacoes_saida_menores,
                    $foto_cam, $observacoes,
                    $outro_enc_edu_nome, $outro_enc_edu_prof,
                    $outro_enc_edu_quem,
                    $pai, $prof_pai,
                    $mae, $prof_mae,
                    $casados_como,
                    $telefone, $telemovel, $email,
                    $data_baptismo, $paroquia_baptismo,
                    $data_comunhao, $paroquia_comunhao,
                    $ultimo_catecismo);
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            }


            if(isset($eid) && $eid != -1)
            {
            ?>
                <div class="alert alert-success"><strong>Sucesso!</strong> O seu pedido de inscrição foi entregue!</div>

                <div class="row" style="margin-top: 20px"></div>

                <!-- ID do pedido -->
                <div class="col-sm-6" style="float: none; margin: 0 auto;">
                    <div class="well well-lg" style="position:relative; z-index:2; text-align: center">
                        <h4>ID do pedido: <?php echo($eid); ?></h4>
                    </div>
                <p><b>Tome nota do ID do seu pedido de inscrição.</b><br>
                    Pode utilizá-lo para consultar o estado do seu pedido.<br>
                    Se necessitar de contactar a catequese para algum
                    esclarecimento ou correção sobre a sua inscrição, indique este número.</p>
                </div>

                <div class="row" style="margin-bottom: 60px"></div>


                <?php
                $showPaymentData =  Configurator::getConfigurationValueOrDefault(Configurator::KEY_ENROLLMENT_SHOW_PAYMENT_DATA);
                $paymentEntity =  Configurator::getConfigurationValueOrDefault(Configurator::KEY_ENROLLMENT_PAYMENT_ENTITY);
                $paymentReference =  Configurator::getConfigurationValueOrDefault(Configurator::KEY_ENROLLMENT_PAYMENT_REFERENCE);
                $paymentAmount =  Configurator::getConfigurationValueOrDefault(Configurator::KEY_ENROLLMENT_PAYMENT_AMOUNT);
                $acceptDonations =  Configurator::getConfigurationValueOrDefault(Configurator::KEY_ENROLLMENT_PAYMENT_ACCEPT_BIGGER_DONATIONS);
                $paymentProof =  Configurator::getConfigurationValueOrDefault(Configurator::KEY_ENROLLMENT_PAYMENT_PROOF);

                $proofType = 'text';
                if(DataValidationUtils::validateEmail($paymentProof))
                    $proofType = "email";
                else if(DataValidationUtils::validateURL($paymentProof))
                    $proofType = "url";

                if($showPaymentData)
                {
                    ?>
                    <!-- Referencia multibanco -->
                    <div class="col-sm-8" style="float: none; margin: 0 auto; text-align: left">
                        <p>Para efetuar o seu donativo, selecione a opção <i>pagamento de serviços</i> no multibanco ou na sua
                            plataforma de <i>homebanking</i>, e insira os seguintes dados:</p>
                        <div class="well well-lg" style="position:relative; z-index:2;">
                            <table class="table table-condensed">
                                <thead>
                                <tr>
                                    <td>Entidade: &nbsp;</td>
                                    <td><?= $paymentEntity ?></td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>Referência: &nbsp;</td>
                                    <td><?= number_format($paymentReference, 0, '', ' '); ?></td>
                                </tr>
                                <tr>
                                    <td>Montante: &nbsp;</td>
                                    <td><?= number_format($paymentAmount, 2, ',', ''); ?> € <?php if($acceptDonations) echo("(ou mais, se assim o entender)"); ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <p>O comprovativo deverá ser enviado para
                            <?php
                            switch($proofType)
                            {
                                case "email":
                                    echo("o email <a href='mailto:" . $paymentProof . "'>" . $paymentProof . "</a>");
                                    break;

                                case "url":
                                    echo("o endereço <a href='" . $paymentProof . "'>" . $paymentProof . "</a>");
                                    break;

                                case "text":
                                default:
                                    echo($paymentProof);
                                    break;
                            }
                            ?>, com indicação do nome do catequizando.</p>
                    </div>

                    <div class="row" style="margin-bottom: 40px"></div>
                    <?php
                }
                ?>

                <div style="float: none; margin: 0 auto; text-align: center">
                    <button type="button" class="btn btn-default no-print glyphicon glyphicon-print" onclick="window.print();"> Imprimir</button>
                </div>

            <?php
            }
            else
            {
                //ERROR
            }

        }

	}
	else
	{
		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não foi processada nenhuma inscrição.</div>");
		//Libertar recursos
		$result = null;
		die();
	}


	if($_REQUEST['modo']!="editar")
	{
		//Sugerir inscrever um irmao
		echo("<p class=\"no-print\">Talvez queira <a href=\"inscrever.php?modo=irmao\">inscrever um irmão</a> deste catequizando.</p>");

        //Link para nova inscricao
        echo("<p class=\"no-print\"><span class= \"glyphicon glyphicon-circle-arrow-left\"></span><a href=\"inscricoes.php\">&nbsp; Voltar à página principal de inscrições</a></p>");

    }


	//Libertar recursos
	$result = null;
?>

    <div class="row" style="margin-bottom: 80px"></div>

</div>


<?php
$footer->renderHTML();
?>



<?php $pageUI->renderJS(); ?>

</body>
</html>
