<?php

require_once(__DIR__ . '/../core/Configurator.php');
require_once(__DIR__ . '/../authentication/securimage/securimage.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/../gui/widgets/Navbar/MinimalNavbar.php');
require_once(__DIR__ . '/../gui/widgets/Footer/SimpleFooter.php');
require_once(__DIR__ . '/../core/check_maintenance_mode.php'); //Check if maintenance mode is active and redirect visitor


use catechesis\Configurator;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MinimalNavbar;
use catechesis\gui\SimpleFooter;



//Verificar se o periodo de inscricoes esta ativo
$periodo_activo = false;
try
{
    // Check if the new configuration key exists, if not, use the legacy key value
    if (Configurator::configurationExists(Configurator::KEY_ONLINE_ENROLLMENTS_RENEWAL_OPEN))
        $periodo_activo = Configurator::getConfigurationValueOrDefault(Configurator::KEY_ONLINE_ENROLLMENTS_RENEWAL_OPEN);
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

// Generate captcha ID
$captchaId = Securimage::getCaptchaId(true);


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
        }
    </style>
</head>
<body>

<?php
$navbar->renderHTML();
?>


<div class="container" id="contentor">

    <div class="no-print">
        <h2 class> Renovação de matrícula</h2>
        <h4>Ano catequético de <?= Utils::formatCatecheticalYear(Utils::currentCatecheticalYear());?></h4>
        <div class="row" style="margin-bottom:40px; "></div>
    </div>


    <form role="form" action="doRenovarMatricula.php" method="post" id="form_renovar_matricula" onsubmit="return validar();">
        <div class="panel panel-default collapse in" id="painel_renovacao">
            <div class="panel-heading">Preencher todos os campos</div>
            <div class="panel-body">

                <!--Catequizando-->
                <div class="form-group">
                    <div class="col-lg-9">
                        <label for="catequizando_nome">Nome do catequizando:</label>
                        <input type="text" class="form-control" id="catequizando_nome" name="catequizando_nome" placeholder="Nome completo do catequizando" style="cursor: auto;" value="" required>
                    </div>
                    <div class="col-lg-3">
                        <label for="catequizando_catecismo">Último catecismo que <u>frequentou</u>:</label>
                        <select id="catequizando_catecismo" name="catequizando_catecismo" class="form-control" required>
                            <option value=""></option>
                            <?php
                            for($i = 1; $i <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)) - 1; $i++)
                            {
                            ?>
                                <option value="<?= $i ?>"><?= $i ?>º catecismo</option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row clearfix" style="margin-bottom:40px; "></div>


                <!--Encarregado de educacao-->
                <div class="form-group">
                    <div class="col-lg-9">
                        <label for="enc_edu_nome">Encarregado de educação:</label>
                        <input type="text" class="form-control" id="enc_edu_nome" name="enc_edu_nome" placeholder="Nome do encarregado de educação" style="cursor: auto;" value="" required>
                    </div>
                    <div class="col-lg-6">
                        <label for="enc_edu_email">E-mail:</label>
                        <input type="email" class="form-control" id="enc_edu_email" name="enc_edu_email" placeholder="ex: endereco@example.com" style="cursor: auto;" value="">
                    </div>
                    <div class="col-lg-3">
                        <label for="enc_edu_tel">Telefone:</label>
                        <input type="tel" class="form-control" id="enc_edu_tel" name="enc_edu_tel" placeholder="" style="cursor: auto;" value="" required>
                    </div>
                </div>

                <div class="row clearfix" style="margin-bottom:40px; "></div>

                <!-- Observacoes -->
                <div class="form-group">
                    <div class="col-lg-12">
                        <label for="observacoes">Observações: (opcional)</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="4" placeholder="Escreva aqui quaisquer observações que considere pertinentes para a catequese.&#10;Ex:&#10;- o seu educando tem necessidades especiais?&#10;- pretende atualizar algum outro campo não apresentado aqui (ex: morada)?" style="cursor: auto;" value=""></textarea>
                    </div>
                </div>

                <div class="row clearfix" style="margin-bottom:40px; "></div>


                <?php
                $enrollmentCustomText = Configurator::getConfigurationValueOrDefault(Configurator::KEY_ENROLLMENT_CUSTOM_TEXT);
                if(isset($enrollmentCustomText) && $enrollmentCustomText != '')
                {
                ?>
                <!-- Informacoes -->
                <div class="form-group">
                    <div class="col-lg-12">
                        <label for="donativo">Informações:</label>
                        <div class="well">
                            <?= $enrollmentCustomText ?>
                        </div>
                    </div>
                </div>
                <?php
                }
                ?>

                <div class="row clearfix" style="margin-bottom:40px; "></div>

                <!-- Autorizacoes -->
                <div class="form-group">
                    <div class="col-lg-12">
                        <input type="checkbox" id="declaracao_enc_edu" name="declaracao_enc_edu" style="cursor: auto;" value="aceito" required/>
                        <label for="declaracao_enc_edu" style="display: contents;">Declaro que sou titular das responsabilidades parentais ou representante legal do meu educando, ou que tenho as devidas autorizações para efetuar esta inscrição.</label>
                    </div>
                    <div class="col-lg-12">
                        <input type="checkbox" id="rgpd" name="rgpd" style="cursor: auto;" value="aceito" required/>
                        <label for="rgpd" style="display: contents;">Declaro que aceito o tratamento dos meus dados pessoais e do meu educando, de acordo com o disposto no <a href="descarregarDeclaracaoRGPD.php" target="_blank" rel="noopener noreferrer">documento emitido pela <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?></a> acerca do cumprimento do Regulamento Geral de Proteção de Dados.</label>
                    </div>
                </div>

                <div class="row clearfix" style="margin-bottom:30px; "></div>

                <!-- Captcha -->
                <div class="form-group">

                    <div class="col-lg-12">
                        <label for="captcha_code">Introduza o código que vê nesta imagem na caixa abaixo:</label>
                        <img class="img-responsive" id="captcha" src="../authentication/securimage/generate_captcha.php?display=1&amp;captchaId=<?= $captchaId ?>" alt="CAPTCHA Image" />
                        <a href="#" onclick="refreshCaptcha(); return false;" data-toggle="tooltip" data-placement="top" title="A imagem está difícil de ler? Peça outra!"><span class="glyphicon glyphicon-refresh"></span> Carregar outra imagem </a>
                    </div>
                    <div class="row clearfix" style="margin-bottom:10px; "></div>
                    <div class="col-lg-2">
                        <input type="hidden" id="captchaId" name="captchaId" value="<?php echo $captchaId ?>" />
                        <input type="text" class="form-control" id="captcha_code" name="captcha_code" size="10" maxlength="6" placeholder="xxxxxx" style="cursor: auto;" required/>
                    </div>
                </div>

                <div class="row clearfix" style="margin-bottom:20px; "></div>

            </div>
        </div>

        <button type="submit" class="btn btn-primary glyphicon glyphicon-save"> Submeter</button>
    </form>

    <div class="row" style="margin-top: 40px"></div>
    <p class="no-print"><span class="glyphicon glyphicon-circle-arrow-left"></span> <a href="inscricoes.php">&nbsp; Voltar à página principal de inscrições</a></p>


    <div class="row" style="margin-bottom:80px; "></div>
</div>


<?php
$footer->renderHTML();
?>



<?php $pageUI->renderJS(); ?>
<script src="../js/form-validation-utils.js"></script>
<script>
    function validar()
    {
        var telefone = document.getElementById('enc_edu_tel').value;
        var email = document.getElementById('enc_edu_email').value;

        if(telefone!=="" && telefone!==undefined && !telefone_valido(telefone, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
        {
            alert("O número de telefone que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
            return false;
        }
        if(email!=="" && email!==undefined && !email_valido(email))
        {
            alert("O endereço de e-mail que introduziu é inválido.");
            return false;
        }

        return true;
    }


    function refreshCaptcha()
    {
        $.ajax({ url: '../authentication/securimage/generate_captcha.php?refresh=1',
            dataType: 'json',
        }).done(function(data) {
            var src = '../authentication/securimage/generate_captcha.php?display=1&captchaId=' + data.captchaId + '&rand=' + Math.random();
            $('#captcha').attr('src', src); // replace image with new captcha
            $('#captchaId').attr('value', data.captchaId); // update hidden form field
        });
    }

</script>

<script src="../js/tooltips.js"></script>


<!-- Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent -->
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Este sítio utiliza cookies para melhorar a sua experiência de navegação. <br>Ao continuar está a consentir essa utilização.","dismiss":"Aceito","learnMore":"Mais info","link":null,"theme":"light-floating"};
</script>

<script type="text/javascript" src="../js/cookieconsent2-1.0.10/cookieconsent.min.js"></script>
<!-- End Cookie Consent plugin -->

</body>
</html>
