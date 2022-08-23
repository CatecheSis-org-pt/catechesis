<?php

require_once(__DIR__ . '/../core/enrollment_functions.php');
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../core/Configurator.php');
require_once(__DIR__ . '/../authentication/securimage/securimage.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/../gui/widgets/Navbar/MinimalNavbar.php');
require_once(__DIR__ . '/../gui/widgets/Footer/SimpleFooter.php');
require_once(__DIR__ . '/../core/check_maintenance_mode.php'); //Check if maintenance mode is active and redirect visitor


use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
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
    <title>Estado do pedido de inscrição na catequese</title>
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
        <h2 class> Estado do pedido de inscrição na catequese</h2>
        <h4>Ano catequético de <?= Utils::formatCatecheticalYear(Utils::currentCatecheticalYear());?></h4>
        <div class="row" style="margin-bottom:20px; "></div>
    </div>

    <?php
    if($_SERVER["REQUEST_METHOD"] == "POST")
    {
        $db = new PdoDatabaseManager();

        // Input parameters
        $id = intval(Utils::sanitizeInput($_POST['id_pedido']));
        $surname = Utils::sanitizeInput($_POST['apelido']);
        $requestType = Utils::sanitizeInput($_POST['tipo_pedido']);
        $captchaCode = Utils::sanitizeInput($_POST['captcha_code']);
        $captchaId = Utils::sanitizeInput($_POST['captchaId']);
        $captcha_options = array();


        //Check input parameters
        $inputs_invalidos = false;

        if(!isset($id) || $id < 0)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não preencheu o ID do pedido. Este campo é obrigatório.</div>");
            $inputs_invalidos = true;
        }
        if(!isset($surname) || $surname=="")
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não preencheu o apelido do catequizando. Este campo é obrigatório.</div>");
            $inputs_invalidos = true;
        }
        if(!isset($requestType) || ($requestType!="inscricao" && $requestType!="renovacao"))
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Tipo de pedido não especificado ou não reconhecido.</div>");
            $inputs_invalidos = true;
        }

        //Captcha validator
        if (!isset($captchaCode) || !Securimage::checkByCaptchaId($captchaId, $captchaCode, $captcha_options))
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O código de segurança que introduziu não corresponde ao mostrado na imagem.</div>");
            echo("<p>Por favor <a href='javascript:history.go(-1)'>volte a tentar</a></p>");
            $inputs_invalidos = true;
        }


        // Output parameters
        $catechumenName = "";
        $requestStatusDone = false;
        $requestStatusMsg = "";
        $enrollmentDetails = null;
        $errorOccurred = false;

        if(!$inputs_invalidos)
        {
            try
            {
                if ($requestType == "inscricao")
                {
                    $submission = $db->GetEnrollmentSubmission($id);

                    if (!isset($submission) || ($surname != Utils::lastName($submission['nome'])))
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Pedido de inscrição não encontrado.</div>");
                        echo("<p>Por favor <a href='javascript:history.go(-1)'>volte a tentar</a></p>");
                        $errorOccurred = true;
                    }
                    else
                    {
                        $catechumenName = $submission['nome'];

                        if (isset($submission['cid']))
                        {
                            $catecheticalYear = Utils::currentCatecheticalYear();
                            $catechismAndGroup = $db->getCatechumenCurrentCatechesisGroup($submission['cid'], $catecheticalYear);

                            $requestStatusDone = true;
                            $requestStatusMsg = "Aceite";

                            if (isset($catechismAndGroup) && isset($catechismAndGroup['ano_catecismo']) && isset($catechismAndGroup['turma']))
                                $enrollmentDetails = "Inscrito no " . $catechismAndGroup['ano_catecismo'] . "º" . $catechismAndGroup['turma'];
                            else
                                $enrollmentDetails = " Informação do catecismo/grupo não disponível. Contacte a catequese.";
                        }
                        else
                        {
                            $requestStatusDone = false;
                            $requestStatusMsg = "Pedido submetido";
                            $enrollmentDetails = "A aguardar processamento";
                        }
                    }
                }
                else if ($requestType == "renovacao")
                {
                    $submission = $db->GetRenewalSubmission($id);

                    if (!isset($submission) || ($surname != Utils::lastName($submission['catequizando_nome'])))
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Pedido de renovação de matrícula não encontrado.</div>");
                        echo("<p>Por favor <a href='javascript:history.go(-1)'>volte a tentar</a></p>");
                        $errorOccurred = true;
                    }
                    else
                    {
                        $catechumenName = $submission['catequizando_nome'];

                        if (isset($submission['processado']) && $submission['processado'] == 1)
                        {
                            $requestStatusDone = true;
                            $requestStatusMsg = "Aceite";

                            if (isset($submission['ano_lectivo_inscricao']) && isset($submission['ano_catecismo_inscricao']) && isset($submission['turma_inscricao']))
                            {
                                $enrollmentDetails = "Inscrito no " . $submission['ano_catecismo_inscricao'] . "º" . $submission['turma_inscricao'] . ".";
                                $enrollmentDetails .= " <i>Suscetível de alterações.</i> 
                                                <span class=\"glyphicon glyphicon-question-sign\" 
                                                data-toggle=\"popover\" data-placement=\"bottom\"
                                                data-content=\"O catecismo/grupo mostrado aqui foi definido quando o pedido foi processado, mas poderá estar sujeito a alterações que não se refletirão aqui. 
                                                                Confirme com a catequese no início do ano catequético.\"></span>";
                            }
                            else
                            {
                                $enrollmentDetails = " Informação do catecismo/grupo não disponível. Contacte a catequese.";
                            }
                        }
                        else
                        {
                            $requestStatusDone = false;
                            $requestStatusMsg = "Pedido submetido";
                            $enrollmentDetails = "A aguardar processamento";
                        }
                    }
                }
            }
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Ocorreu um erro desconhecido ao processar o pedido.</div>");
                echo("<p>Por favor <a href='javascript:history.go(-1)'>volte a tentar</a></p>");
                $errorOccurred = true;
            }

            //Draw results table
            if(!$errorOccurred)
            {
            ?>
                <div class="row" style="margin-bottom:40px; "></div>

                <div class="col-md-12 table-responsive" style="float: none; margin: 0 auto;">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Nome do catequizando</th>
                            <th>Estado do pedido</th>
                            <th>Detalhes</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><?= $catechumenName ?></td>
                            <td><span class="label label-<?= $requestStatusDone?"success":"default" ?>"><?= $requestStatusMsg ?></span></td>
                            <td><?= $enrollmentDetails ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            <?php
            }
            else
            {

            }
        }
    }
    else
    {
    ?>
    <div class="row" style="margin-bottom: 20px"></div>

    <div class="col-md-6" style="float: none; margin: 0 auto;">
        <div class="well well-lg" style="position:relative; z-index:2;">

            <p>Por favor insira os dados do seu pedido:</p>
            <div class="row" style="margin-top: 30px"></div>

            <form role="form" action="consultarPedido.php" method="post">

                <!-- Dados do pedido -->
                <div class="form-group">
                    <div class="col-sm-12">
                        <label for="id_pedido">ID do pedido:</label>
                        <input type="number" class="form-control" id="id_pedido" name="id_pedido" required>
                    </div>
                    <div class="col-lg-12">
                        <label for="apelido">Apelido do catequizando:</label>
                        <input type="text" class="form-control" id="apelido" name="apelido" placeholder="Último nome do catequizando" required>
                    </div>
                    <div class="row" style="margin-bottom: 10px"></div>
                    <div class="col-lg-12">
                        <label for="tipo_pedido">Tipo do pedido:</label>
                        <label class="radio-inline"><input type="radio" id="tipo_pedido_inscricao" name="tipo_pedido" value="inscricao" checked>Nova inscrição</label>
                        <label class="radio-inline"><input type="radio" id="tipo_pedido_renovacao" name="tipo_pedido" value="renovacao">Renovação de matrícula</label>
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
                    <div class="col-lg-4">
                        <input type="hidden" id="captchaId" name="captchaId" value="<?php echo $captchaId ?>" />
                        <input type="text" class="form-control" id="captcha_code" name="captcha_code" size="10" maxlength="6" placeholder="xxxxxx" style="cursor: auto;" required/>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 20px"></div>

                <div style="float: none; margin: 0 auto; text-align: center">
                    <button type="submit" class="btn btn-primary" ><span class="glyphicon glyphicon-search"></span> Consultar</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    }
    ?>

    <div class="row" style="margin-top: 40px"></div>
    <p class="no-print"><span class="glyphicon glyphicon-circle-arrow-left"></span> <a href="inscricoes.php">&nbsp; Voltar à página principal de inscrições</a></p>


    <div class="row clearfix" style="margin-bottom: 80px"></div>
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


<script>
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

<?php
//if($_SERVER["REQUEST_METHOD"] == "POST"  &&  $requestType == "renovacao")
//{?>
<script>
    $(function () {
        $('[data-toggle="popover"]').popover({ trigger: "hover",
            html: true,
            /*content: function () {
              return '<img src="'+$(this).data('img') + '" />';
            },*/
            container: 'body',
            delay: { "show": 500, "hide": 100 }
        });
    })
</script>
<?php
//}
?>

</body>
</html>