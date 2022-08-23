<?php

require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../core/DataValidationUtils.php');
require_once(__DIR__ . '/../core/enrollment_functions.php');
require_once(__DIR__ . '/../authentication/securimage/securimage.php'); //Captcha
require_once(__DIR__ . '/../core/Configurator.php');
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/../gui/widgets/Navbar/MinimalNavbar.php');
require_once(__DIR__ . '/../gui/widgets/Footer/SimpleFooter.php');


use catechesis\Configurator;
use catechesis\DataValidationUtils;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MinimalNavbar;
use catechesis\gui\SimpleFooter;

$db = new PdoDatabaseManager();

$periodo_activo = false;
try
{
    //Verificar se o periodo de inscricoes esta ativo
    $periodo_activo = Configurator::getConfigurationValueOrDefault(Configurator::KEY_ONLINE_ENROLLMENTS_OPEN);
}
catch(Exception $e)
{
}

if(!$periodo_activo)
{
    header("Location: inscricoes.php");
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
    <meta charset="UTF-8">
    <title>Renovação de matrícula</title>

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

<div class="container" id="contentor">

    <div class="">
        <h2 class> Renovação de matrícula</h2>
        <div class="row" style="margin-bottom:40px; "></div>
    </div>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $enc_edu_nome = Utils::sanitizeInput($_POST['enc_edu_nome']);
    $enc_edu_email = Utils::sanitizeInput($_POST['enc_edu_email']);
    $enc_edu_tel = Utils::sanitizeInput($_POST['enc_edu_tel']);
    $catequizando_nome = Utils::sanitizeInput($_POST['catequizando_nome']);
    $catequizando_catecismo = intval(Utils::sanitizeInput($_POST['catequizando_catecismo']));
    $observacoes = Utils::escapeSingleQuotes(Utils::escapeDoubleQuotes(Utils::removeLineManipulators(Utils::sanitizeInput(($_POST['observacoes'])))));
    $declaracao_enc_edu = Utils::sanitizeInput($_POST['declaracao_enc_edu']);
    $rgpd = Utils::sanitizeInput($_POST['rgpd']);
    $captchaCode = Utils::sanitizeInput($_POST['captcha_code']);
	$captchaId = Utils::sanitizeInput($_POST['captchaId']);
	$captcha_options = array();

    //Verificar inputs
    $inputs_invalidos = false;

    if(!isset($enc_edu_nome) || $enc_edu_nome=="")
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não preencheu o nome do encarregado de educação. Este campo é obrigatório.</div>");
        $inputs_invalidos = true;
    }
    if(!isset($catequizando_nome) || $catequizando_nome=="")
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não preencheu o nome do catequizando. Este campo é obrigatório.</div>");
        $inputs_invalidos = true;
    }
    if(!isset($catequizando_catecismo) || $catequizando_catecismo=="" || $catequizando_catecismo < 1 || $catequizando_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O campo último catecismo frequentado tem um valor inválido.</div>");
        $inputs_invalidos = true;
    }

    if(!isset($declaracao_enc_edu) || $declaracao_enc_edu!="aceito")
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não aceitou a declaração de responsabilidade legal pelo educando. A aceitação deste campo é obrigatória para proceder à inscrição na catequese.</div>");
        $inputs_invalidos = true;
    }
    if(!isset($rgpd) || $rgpd!="aceito")
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não aceitou a declaração de tratamento de dados. A aceitação deste campo é obrigatória para proceder à inscrição na catequese.</div>");
        $inputs_invalidos = true;
    }

    if(!isset($enc_edu_tel) || $enc_edu_tel=="" || !DataValidationUtils::validatePhoneNumber($enc_edu_tel))
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O número de telefone que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.</div>");
        $inputs_invalidos = true;
    }

    if(isset($enc_edu_email) && $enc_edu_email!="" && !DataValidationUtils::validateEmail($enc_edu_email))
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O e-mail que introduziu é inválido.</div>");
        $inputs_invalidos = true;
    }
    else if(!isset($enc_edu_email) || $enc_edu_email=="")
        $enc_edu_email = null;

    //Captcha validator
    if (!isset($captchaCode) || !Securimage::checkByCaptchaId($captchaId, $captchaCode, $captcha_options))
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O código de segurança que introduziu não corresponde ao mostrado na imagem.</div>");
        echo("<p>Por favor <a href='javascript:history.go(-1)'>volte a tentar</a></p>");
        $inputs_invalidos = true;
    }


    if(!$inputs_invalidos)
    {
        try
        {
            // Register enrollment
            $rid = $db->postRenewalOrder($enc_edu_nome, $enc_edu_tel, $catequizando_nome, $catequizando_catecismo, $_SERVER['REMOTE_ADDR'], $enc_edu_email, $observacoes);

        ?>

            <div class="alert alert-success"><strong>Sucesso!</strong> O seu pedido de renovação de matrícula foi entregue!</div>

            <div class="row" style="margin-top: 20px"></div>

            <!-- ID do pedido -->
            <div class="col-sm-6" style="float: none; margin: 0 auto;">
                <div class="well well-lg" style="position:relative; z-index:2; text-align: center">
                    <h4>ID do pedido: <?php echo($rid); ?></h4>
                </div>
            <p><b>Tome nota do ID do seu pedido de renovação.</b><br>
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


            <div class="row" style="margin-top: 40px"></div>
            <p class="no-print"><span class="glyphicon glyphicon-circle-arrow-left"></span> <a href="inscricoes.php">&nbsp; Voltar à página principal de inscrições</a></p>

            <div class="row" style="margin-bottom: 80px"></div>

<?php
        }
        catch(Exception $e)
        {
            ?><div class="alert alert-danger"><strong>ERRO!</strong> <?= $e->getMessage() ?>></div><?php
        }
    }
}
else
{
    // No submission found
    ?>
    <div class="alert alert-danger"><strong>ERRO!</strong> Não foram submetidos quaisquer dados.</div>
    <?php
}

?>

</div>


<?php
$footer->renderHTML();
?>


<?php $pageUI->renderJS(); ?>

</body>
</html>