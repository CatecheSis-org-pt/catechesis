<?php

require_once(__DIR__ . '/../core/Configurator.php');
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
    $periodo_activo = Configurator::getConfigurationValueOrDefault(Configurator::KEY_ONLINE_ENROLLMENTS_OPEN);
}
catch (Exception $e)
{
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
    <title>Inscrição na catequese</title>
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
        <h2 class> Inscrição na catequese</h2>
        <h4>Ano catequético de <?= Utils::formatCatecheticalYear(Utils::currentCatecheticalYear());?></h4>
        <div class="row" style="margin-bottom:20px; "></div>
    </div>

    <?php
    if($periodo_activo)
    {
    ?>
        <p>Bem-vindo à plataforma de inscrições da catequese da <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?>!<br>
        Selecione a opção que melhor se ajusta ao seu caso:</p>


        <div class="row" style="margin-top: 40px"></div>

        <div class="col-md-6">
            <div class="well well-lg" style="position:relative; z-index:2;">
                <p>É a primeira vez que estou a inscrever o meu educando na catequese da <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?>.</p>
                <div class="row" style="margin-top: 20px"></div>
                <div style="float: none; margin: 0 auto; text-align: center">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='inscrever.php'"><span class="glyphicon glyphicon-pencil"></span> Inscrever</button>
                </div>
            </div>
        </div>

        <div class="col-md-6" >
            <div class="well well-lg" style="position:relative; z-index:2;">
                <p>O meu educando já frequentou a catequese na <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?>. Pretendo renovar a matrícula.</p>
                <div class="row" style="margin-top: 20px"></div>
                <div style="float: none; margin: 0 auto; text-align: center">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='renovarMatricula.php'"><span class="glyphicon glyphicon-repeat"></span> Renovar matrícula</button>
                </div>
            </div>
        </div>

    <?php
    }
    else
    {
    ?>

    <p>Bem-vindo à plataforma de inscrições da catequese da <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?>!<br>
        De momento, as inscrições estão fechadas. Se desejar efetuar uma inscrição ou renovação de matrícula, contacte a catequese.</p>

    <div class="row" style="margin-top: 40px"></div>
    <?php
    }
    ?>

    <div class="row clearfix"></div>

    <div class="col-md-6" style="float: none; margin: 0 auto;">
        <div class="well well-lg" style="position:relative; z-index:2;">
            <p>Pretendo consultar o estado do meu pedido de inscrição ou renovação de matrícula, ou saber em que
                catecismo/grupo foi colocado o meu educando.</p>
            <div class="row" style="margin-top: 20px"></div>
            <div style="float: none; margin: 0 auto; text-align: center">
                <button type="button" class="btn btn-primary" onclick="window.location.href='consultarPedido.php'"><span class="glyphicon glyphicon-search"></span> Consultar estado do pedido</button>
            </div>
        </div>
    </div>

    <div class="row clearfix" style="margin-bottom: 40px"></div>

</div>


<?php
$footer->renderHTML();
?>



<?php $pageUI->renderJS(); ?>

<!-- Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent -->
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Este sítio utiliza cookies para melhorar a sua experiência de navegação. <br>Ao continuar está a consentir essa utilização.","dismiss":"Aceito","learnMore":"Mais info","link":null,"theme":"light-floating"};
</script>

<script type="text/javascript" src="../js/cookieconsent2-1.0.10/cookieconsent.min.js"></script>
<!-- End Cookie Consent plugin -->

</body>
</html>