<?php

require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Footer/SimpleFooter.php');
require_once(__DIR__ . '/core/check_maintenance_mode.php'); //Check if maintenance mode is active and redirect visitor

use catechesis\Configurator;
use catechesis\gui\WidgetManager;
use catechesis\gui\SimpleFooter;

// Instantiate a widget manager
$pageUI = new WidgetManager();

// Add widgets
$footer = new SimpleFooter(null, false);
$pageUI->addWidget($footer);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CatecheSis - <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?></title>

    <link rel="shortcut icon" href="img/favicon.png" type="image/png">
    <link rel="icon" href="img/favicon.png" type="image/png">

    <?php $pageUI->renderCSS(); ?>
    <link rel="stylesheet" href="fonts/Nexa.css">
    <link rel="stylesheet" href="fonts/Petemoss.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/index.css">
    <?php
    if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_USE_CUSTOM_PUBLIC_PAGE_IMAGE))
    {
        //Load this style sheet only when a user set a custom background
    ?>
    <link rel="stylesheet" href="css/index_custom_image.css">
    <?php
    }
    ?>

</head>
<body>

<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid" style="margin-top: 20px">
        <div class="navbar-header">
            <a class="navbar-brand" href="#">
                <a class="navbar-brand" href="<?= constant('CATECHESIS_BASE_URL'); ?>/index.php"><img src="img/CatecheSis_Logo_Navbar.svg" class="img-responsive" style="height:200%; margin-top: -7%;"></a>
            </a>
        </div>
        <div class="navbar-right" style="float: right">
            <button type="button" class="btn btn-primary navbar-btn" onclick="goto_login();">ENTRAR</button>
        </div>
    </div>
</nav>

<div class="container" id="contentor">

    <div class="row clearfix" style="margin-bottom: 100px"></div>

    <div class="center">
        <img src="img/CatecheSis_Logo_Navbar.svg" class="img-responsive" style="scale: 0.8;">
    </div>

    <div class="row clearfix" style="margin-bottom: 20px"></div>

    <div class="center">
        <div class="col-md-10">
        <h1>O CatecheSis é um sistema livre, <i>open-source</i>, para gestão de grupos de catequese concebido por catequistas e para catequistas, para atender às necessidades da sua paróquia.</h1>
        </div>
    </div>

    <div class="row clearfix" style="margin-bottom: 40px"></div>

    <div class="center cards-area">
        <div class="col-md-10">

            <!-- Online enrollments panel -->
            <div class="panel panel-default animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
                <div class="panel-body">
                    <div class="row flex-override">
                        <div class="col-md-2 vertical-align-center">
                            <img class="img-responsive" src="img/Inscricoes_Online.svg">
                        </div>
                        <div class="col-md-6 vertical-align-center">
                            <div class="valign-center">
                                <h2 class="card-title">Inscrições Online</h2>
                                <span><strong>Inscrição</strong> na catequese ou <strong>renovação</strong> de matrícula.</span><br>
                                <span><strong>Consulta</strong> do estado do pedido de incrição ou renovação.</span>
                            </div>
                        </div>
                        <div class="col-md-3 vertical-align-center">
                            <div class="valign-center">
                                <button type="button" class="btn btn-primary card-button" onclick="goto_online_enrollments();">Ir para Inscrições Online</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row clearfix"></div>

            <!-- Virtual catechesis panel -->
            <div class="panel panel-default animate__animated animate__fadeInUp" style="animation-delay: 1.0s;">
                <div class="panel-body">
                    <div class="row flex-override">
                        <div class="col-md-2 vertical-align-center">
                            <img class="img-responsive" src="img/Catequese_Virtual.svg">
                        </div>
                        <div class="col-md-6 vertical-align-center">
                            <div class="valign-center">
                                <h2 class="card-title">Catequese Virtual</h2>
                                <span>Acesso a <strong>conteúdos</strong> digitais.</span><br>
                                <span>Dinamização de catequeses à distância.</span>
                            </div>
                        </div>
                        <div class="col-md-3 vertical-align-center">
                            <div class="valign-center">
                                <button type="button" class="btn btn-primary card-button" onclick="goto_virtual_catechesis();">Ir para Catequese Virtual</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row clearfix"></div>

        </div>
    </div>

    <div class="row clearfix" style="margin-bottom: 80px"></div>
</div>

<?php
$footer->renderHTML();
?>



<?php $pageUI->renderJS(); ?>
<script src="js/index.js"></script>

<!-- Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent -->
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Este sítio utiliza cookies para melhorar a sua experiência de navegação. <br>Ao continuar está a consentir essa utilização.","dismiss":"Aceito","learnMore":"Mais info","link":null,"theme":"light-floating"};
</script>
<script type="text/javascript" src="js/cookieconsent2-1.0.10/cookieconsent.min.js"></script>
<!-- End Cookie Consent plugin -->

</body>
</html>