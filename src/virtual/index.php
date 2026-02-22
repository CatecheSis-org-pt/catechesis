<?php
require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../core/Configurator.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../core/DataValidationUtils.php');
require_once(__DIR__ . "/../core/PdoDatabaseManager.php");
require_once(__DIR__ . '/../core/domain/Locale.php');
require_once(__DIR__ . '/../authentication/Authenticator.php');
require_once(__DIR__ . '/../fonts/quill-fonts.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/../gui/widgets/Footer/SimpleFooter.php');
require_once(__DIR__ . '/../core/check_maintenance_mode.php'); //Check if maintenance mode is active and redirect visitor


use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\DataValidationUtils;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use core\domain\Locale;
use catechesis\gui\WidgetManager;
use catechesis\gui\SimpleFooter;

// Start a secure session if none is running
Authenticator::startSecureSession();


// Instantiate a widget manager
$pageUI = new WidgetManager("../");

// Add widgets
$footer = new SimpleFooter(null, false);
$pageUI->addWidget($footer);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Catequese Virtual</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/png">
    <link rel="icon" href="../img/favicon.png" type="image/png">
    <?php $pageUI->renderCSS(); ?>
    <link rel="stylesheet" href="../css/quill-1.3.6/quill.snow.css" />
    <?php quill_render_css_links("../"); ?>
    <link rel="stylesheet" href="../css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">
    <link rel="stylesheet" href="../fonts/Nexa.css">
    <link rel="stylesheet" href="../fonts/Petemoss.css">
    <link rel="stylesheet" href="virtual.css">

    <style>

        <?php

        $db = new PdoDatabaseManager();

        //// CONSTANTES ////////////////////////////////////////////////////////////////////////////////////////
        $HOJE = date('d-m-Y', strtotime('today'));
        $UMA_SEMANA_ATRAS = date('d-m-Y', strtotime('-1 week'));
        ////////////////////////////////////////////////////////////////////////////////////////////////////////

        $catecismo = NULL;
        $turma = NULL;
        $conteudo_carregado = NULL;
        $data_sessao = NULL;
        $result = NULL;


        if($_GET['catecismo'])
        {
            $catecismo = intval($_GET['catecismo']);

                if($catecismo == -1)    //Default session for all catechisms
                    $catecismo = 1;
                else if($catecismo < 1 || $catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
                    $catecismo = NULL;
        }

        if($_GET['turma'])
        {
            $turma = Utils::sanitizeInput($_GET['turma']);

            //Default session for all catechisms and groups
            if(intval($_GET['catecismo']) == -1)
                $turma = "_";
        }
        else
            $turma = ''; //Default (compatibility with old sessions not designed por a particular group)

        if($_GET['data_sessao'])
        {
            $data_sessao = $_GET['data_sessao']; // sanitizeInput($_POST['data_sessao']);
        }


        if(!$data_sessao || !DataValidationUtils::validateDate($data_sessao))
        {
            //Datas possiveis na ultima semana
            $last_week_dates = array();
            try
            {
                $last_week_dates = $db->getVirtualCatechesisSessionDates($catecismo, $turma, true, 0, $UMA_SEMANA_ATRAS, $HOJE);
            }
            catch(Exception $e)
            {
                //Do nothing
            }

            if(empty($last_week_dates))
                $data_sessao = $HOJE;               //Fallback: hoje
            else
                $data_sessao = $last_week_dates[0]; //Sessao mais recente nos ultimos oito dias
        }
        else
        {
            $data_sessao_dt = new DateTime($data_sessao);
            $hoje_dt = new DateTime($HOJE);

            //A data nao pode ser no futuro, a nao ser que seja um catequista autenticado
            if(!Authenticator::isAppLoggedIn() && ($data_sessao_dt > $hoje_dt))
            {
                $data_sessao = $HOJE;
                $data_sessao_dt = new DateTime($data_sessao);
            }
        }


        $on_landing_page = ($catecismo == NULL); //When no catechism is selected, we are on the landing page


        if($on_landing_page) //Cor de fundo apenas na pagina principal
        {
        ?>
            body
            {
                background-color: #008fcf;
            }

            .hamburger.is-closed .hamb-top,
            .hamburger.is-closed .hamb-middle,
            .hamburger.is-closed .hamb-bottom
            {
                background-color: white; //#1a1a1a;
            }
        <?php
        }
        ?>

        body > #standalone-container {
            margin: 50px auto;
            max-width: 720px;
        }
        #editor-container {
            height: 100vh;
        }

        .ql-container.ql-snow {
            border: 0px solid #ccc;
        }

        .ql-editor .ql-video {

            display: block;
            max-width: 100%;
            width: 80%;
            height: 60vh;
            margin-left: auto;
            margin-right: auto;
        }


        .ql-editor {
            overflow-y: visible;
        }
    </style>

</head>
<body>


<div id="wrapper">
    <div class="overlay"></div>

    <!-- Sidebar -->
    <nav class="navbar navbar-inverse navbar-fixed-top" id="sidebar-wrapper" role="navigation">
        <ul class="nav sidebar-nav">
            <li class="sidebar-brand">
                <a href="index.php">
                    Catequese Virtual
                </a>
            </li>
            <li>
                <a href="index.php">Início</a>
            </li>
            <?php

            $ano_catequetico = Utils::currentCatecheticalYear();

            for($catechism=1; $catechism <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)); $catechism = $catechism + 1)
            {
                $groups = $db->getCatechismGroups($ano_catequetico, $catechism);

                if (isset($groups) && count($groups)>=1)
                {
                    ?>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?=$catechism?>º catecismo <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                    <li class="dropdown-header">Grupo</li>
                    <?php
                    foreach($groups as $group)
                    {
                    ?>
                        <li style="margin-left: 10px;"><a href="index.php?catecismo=<?=$catechism?>&turma=<?=$group['turma']?>"><?=$catechism?>º<?=$group['turma']?></a></li>
                    <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
                }
            }
            ?>
        </ul>
    </nav>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <button type="button" class="hamburger is-closed" data-toggle="offcanvas">
            <span class="hamb-top"></span>
            <span class="hamb-middle"></span>
            <span class="hamb-bottom"></span>
        </button>
        <div class="container">

            <?php

            //Carregar conteudo da sessao
            if($catecismo)
            {
                try
                {
                    //Carregar conteudo da catequese
                    //(se nao houver uma sessao para este catecismo especifico, procura recursivamente uma sessao generica)
                    $conteudo_carregado = $db->getVirtualCatechesisContent($data_sessao, $catecismo, $turma, true);
                }
                catch(Exception $e)
                {
                    //Do nothing
                }

                ?>

                <h2><?= $catecismo ?>º Catecismo <?php if(isset($turma) && $turma !== "" && $turma !== "_") echo(" - Grupo $turma");?></h2>
                <h4><?php
                    echo(Locale::getPortugueseDate($data_sessao));
                    ?></h4>

                <div>
                    <form id="form_sessao_catequese" method="get" action="index.php">
                        <input type="hidden" name="catecismo" id="catecismo_input" value="<?= $catecismo ?>">
                        <input type="hidden" name="turma" id="turma_input" value="<?= $turma ?>">
                        <input type="hidden" name="data_sessao" id="data_sessao_input">
                    </form>
                    <button class="btn btn-default btn" id="button" data-date-format="dd-mm-yyyy" data-date="<?php echo($data_sessao);?>"><span class="glyphicon glyphicon-calendar"></span> Selecionar outra data</button>
                </div>

                <div class="row" style="margin-bottom:20px; "></div>

                <?php
                if($conteudo_carregado)
                {
                    ?>
                    <!-- Quill editor -->
                    <div id="standalone-container">
                        <div id="editor-container"></div>
                    </div>
                    <?php
                }
                else
                {
                    ?>
                    <p>Ainda não foram publicados conteúdos para esta semana.</p>

                    <?php
                }
            }
            else {
                ?>

                <div class="row">
                    <div class="col-lg-12">

                        <div style="text-align: center; color: white; font-family: Nexa, sans-serif;">

                            <h1 id="virtual_catechesis_title">Catequese Virtual</h1>

                            <div class="row" style="margin-bottom: 40px"></div>

                            <div id="block-title">
                                <h3>Novos desafios exigem novas respostas!</h3>
                                <h1>Bem-vindo à <span class="catequese-virtual-destaque">Catequese Virtual!</span></h1>
                            </div>

                            <div class="row" style="margin-bottom: 80px"></div>

                            <h3>Aqui poderás encontrar tudo sobre a tua catequese:</h3>
                            <div class="row" style="margin-bottom: 20px"></div>

                            <div class="row">
                                <div class="col-md-3 col-md-offset-3">
                                    <img class="center-image" width="56" height="56" src="img/file-signature-solid.svg">
                                    <h4>Temas abordados<br> na sessão</h4>
                                </div>
                                <div class="col-md-3">
                                    <img class="center-image" width="56" height="56" src="img/video-solid.svg">
                                    <h4>Vídeos</h4>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="row">
                                <div class="col-md-3 col-md-offset-3">
                                    <img class="center-image" width="56" height="56" src="img/info-solid.svg">
                                    <h4>Informações</h4>
                                </div>
                                <div class="col-md-3">
                                    <img class="center-image" width="56" height="56" src="img/praying-hands-solid.svg">
                                    <h4>Orações</h4>
                                </div>
                            </div>

                            <div class="row clearfix" style="margin-bottom: 20px"></div>

                            <h4 class="catequese-virtual-destaque">E mais...</h4>
                        </div>
                    </div>

                </div>
                <?php
            }
            ?>
        </div>
        <!-- /#container -->

    </div>
    <!-- /#page-content-wrapper -->
</div>
<!-- /#wrapper -->


<?php
if($on_landing_page)
{
    ?>
    <div style="margin-bottom: 80px;"></div>
    <?php
    //Show footer only on landing page
    $footer->renderHTML();
}
?>



<?php $pageUI->renderJS(); ?>
<script src="../js/quill-1.3.6/quill.min.js"></script>
<script src="../js/quill-image-resize-module/image-resize.min.js"></script>
<script src="../js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="../js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>

<script>
    $(document).ready(function () {
        var trigger = $('.hamburger'),
            overlay = $('.overlay'),
            isClosed = false;

        trigger.click(function () {
            hamburger_cross();
        });

        function hamburger_cross() {

            if (isClosed == true) {
                overlay.hide();
                trigger.removeClass('is-open');
                trigger.addClass('is-closed');
                isClosed = false;
            } else {
                overlay.show();
                trigger.removeClass('is-closed');
                trigger.addClass('is-open');
                isClosed = true;
            }
        }

        $('[data-toggle="offcanvas"]').click(function () {
            $('#wrapper').toggleClass('toggled');
        });
    });
</script>



<script>
    //import Delta from 'quill-delta';
    var Delta = Quill.import('delta');

    <?php quill_render_js_fonts(); ?>

    var quill = new Quill('#editor-container', {
        modules: {
            "toolbar": false
        },
        readOnly: true,
        theme: 'snow'
    });


    function htmlDecode(input) {
        var doc = new DOMParser().parseFromString(input, "text/html");
        return doc.documentElement.textContent;
    }

    <?php

    if($conteudo_carregado)
    {
        echo("quill.setContents(new Delta(JSON.parse(htmlDecode('" . $conteudo_carregado . "'))));");
    }
    ?>
</script>

<script>
    // List of dates with virtual catechesis sessions, to highlight
    var highlighted_dates = [
        <?php
        try
        {
            $datas_sessoes = $db->getVirtualCatechesisSessionDates($catecismo, $turma, true, 0, null, $HOJE);
            for ($i = 0; $i < count($datas_sessoes); $i = $i + 1)
            {
                if ($i > 0)
                    echo(", ");
                echo("'$datas_sessoes[$i]'");
            }
        }
        catch(Exception $e)
        {
            //Do nothing
        }
        ?>
    ];

    $('#button').datepicker({
        format: "dd-mm-yyyy",
        language: "pt",
        todayBtn: true,
        /*daysOfWeekDisabled: "0,1,2,3,4,5",
        daysOfWeekHighlighted: "6",*/
        endDate: "<?php echo($HOJE); ?>",
        todayHighlight: true,
        autoclose: true,
        beforeShowDay: function(date) {
            dateFormat = ("0" + date.getDate()).slice(-2) + '-' + ("0" + (date.getMonth()+1)).slice(-2) + '-' + date.getFullYear(); //We have to sum 1 to the month to make it right xD
            if (highlighted_dates.indexOf(dateFormat) >= 0) {
                return {classes: 'highlighted', tooltip: 'Catequese virtual disponível'};
            }
            else
                return false; //Disable date
        }
    })
        .on('changeDate', function(ev){
            $('#button').datepicker('hide');
            var date_string = ev.date.getDate().toString().padStart(2, '0') + "-" + (ev.date.getMonth()+1).toString().padStart(2, '0') + "-" + ev.date.getFullYear().toString();
            document.getElementById("data_sessao_input").value = date_string;
            document.getElementById("form_sessao_catequese").submit();
        });
</script>


<!-- Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent -->
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Este sítio utiliza cookies para melhorar a sua experiência de navegação. <br>Ao continuar está a consentir essa utilização.","dismiss":"Aceito","learnMore":"Mais info","link":null,"theme":"light-floating"};
</script>

<script type="text/javascript" src="../js/cookieconsent2-1.0.10/cookieconsent.min.js"></script>
<!-- End Cookie Consent plugin -->

</body>
</html>