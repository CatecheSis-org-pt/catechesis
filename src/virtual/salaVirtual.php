<?php

require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../authentication/Authenticator.php');
require_once(__DIR__ . '/../core/Configurator.php');
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/../gui/widgets/ModalDialog/ModalDialogWidget.php');

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;


// Start a secure session if none is running
Authenticator::startSecureSession();

$db = new PdoDatabaseManager();


// Create the widgets manager
$pageUI = new WidgetManager("../");

// Instantiate the widgets used in this page and register them in the manager
$confirmCloseRoomDialog = new ModalDialogWidget("confirmarFecharSala");
$pageUI->addWidget($confirmCloseRoomDialog);
$closedRoomDialog = new ModalDialogWidget("salaFechada");
$pageUI->addWidget($closedRoomDialog);
$changeRoomDialog = new ModalDialogWidget("mudancaSala");
$pageUI->addWidget($changeRoomDialog);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Sala virtual</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
    <link rel="icon" href="../img/favicon.png" type="image/x-icon">
    <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
    <link rel="stylesheet" href="../css/custom-navbar-colors.css">

    <style>
        @media print
        {
            .no-print, .no-print *
            {
                display: none !important;
            }
        }
    </style>

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        .box {
            display: flex;
            flex-flow: column;
            height: 100%;
        }

        .box .header {
            flex: 0 1 auto;
            /* The above is shorthand for:
            flex-grow: 0,
            flex-shrink: 1,
            flex-basis: auto
            */
        }

        .box .content {
            flex: 1 1 auto;
            overflow: hidden;
        }

        .box .footer {
            flex: 0 1 40px;
        }

        .navbar{
            margin-bottom: 0px;
        }

    </style>
</head>
<body>

<?php

$catecismo = null;
$turma = null;
$turmas_validas = array();
$parametrosInvalidos = false;


if($_REQUEST['catecismo'])
{
    $catecismo = intval($_REQUEST['catecismo']);
}
if($_REQUEST['turma'])
{
    $turma = Utils::sanitizeInput($_REQUEST['turma']);
}

if(!isset($catecismo) || $catecismo < 1 || $catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
{
    $parametrosInvalidos = true;
}

//Verificar que a turma existe
$turmas_existentes = $db->getCatechismGroups(Utils::currentCatecheticalYear(), $catecismo);

if(isset($turmas_existentes) && count($turmas_existentes)>=1)
{
    foreach ($turmas_existentes as $turmaE)
    {
        array_push($turmas_validas, $turmaE['turma']);
    }

    if (!isset($turma) || !in_array($turma, $turmas_validas))
    {
        $parametrosInvalidos = true;
    }
}
else
    $parametrosInvalidos = true;


//Obter URL da videochamada

$data_sessao = date('d-m-Y', strtotime('today'));
$url = NULL;
$roomPassword = NULL;

$sala = null;
try
{
    $sala = $db->getVirtualCatechesisRoom($data_sessao, $catecismo, $turma);
}
catch(Exception $e)
{
    $parametrosInvalidos = true;
}

if ($sala)
{
    $url = $sala['url'];
    $roomPassword = $sala['passwordSala'];
}
else if(Authenticator::isAppLoggedIn())
{
    $url = Utils::secureRandomString(4);
    $roomPassword = Utils::secureRandomString(4);

    try
    {
        if (!$db->postVirtualCatechesisRoom($data_sessao, $catecismo, $turma, $url, $roomPassword, Authenticator::getUsername()))
            $parametrosInvalidos = true;
    }
    catch(Exception $e)
    {
        $parametrosInvalidos = true;
    }
}
else
{
    //No room
    $parametrosInvalidos = true;
}

if($parametrosInvalidos)
{
    ?>


    <!-- Cabecalho -->
    <nav class="header navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="#" data-toggle="modal" data-target="#sobre">CatecheSis</a>
                <span class="navbar-text">Sala de catequese virtual </span>
            </div>
        </div>
    </nav>

    <div class="container" id="contentor">

        <div style="margin-top: 40px;"></div>
        <div class="alert alert-danger"><strong>ERRO!</strong> Sala não encontrada.</div>

    </div>

<?php
}
else
{
?>

<div class="box">

    <!-- Cabecalho -->
    <nav class="header navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="#" data-toggle="modal" data-target="#sobre"><img src="../img/CatecheSis_Logo_Navbar.svg" class="img-responsive" style="height:200%; margin-top: -7%;"></a>
                <span class="navbar-text">Sala de catequese virtual &nbsp; .:&nbsp; <?= $catecismo ?>º<?= $turma ?> &nbsp;:. </span>
            </div>
            <div>
                <?php if(Authenticator::isAppLoggedIn()){ ?>
                    <ul class="nav navbar-nav navbar-right">
                        <form class="navbar-form navbar-left">
                            <button type="button" onclick="fecharSalaVirtual()" id="botao_encerrar_sala" class="btn btn-danger no-print"><span class="glyphicon glyphicon-off"></span>&nbsp; Encerrar sala virtual</button>
                        </form>
                        <li><a><span class="glyphicon glyphicon-user"></span> <?= Utils::firstAndLastName(strip_tags(Authenticator::getUserFullName())); ?></a></li>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </nav>


    <!-- Jitsi meet -->
    <div id="meet" class="content">
    </div>

</div>



<?php

// Dialog to inform that the virtual catechesis room is closed

$closedRoomDialog->setTitle("Sala fechada");
$closedRoomDialog->setBodyContents(<<<HTML_CODE
                <p>Esta sala de catequese virtual foi encerrada.<br>Pode fechar este separador.</p>
HTML_CODE
);
$closedRoomDialog->addButton(new Button("OK", ButtonType::SECONDARY));
$closedRoomDialog->renderHTML();



// Dialog to inform that a new virtual catechesis room was created

$changeRoomDialog->setTitle("Mudança de sala");
$changeRoomDialog->setBodyContents(<<<HTML_CODE
                <p><b><span id="catequista_mudou_sala"></span></b> criou uma nova sala de catequese virtual e está a pedir
                para mudar para essa sala.</p>
                <div class="text-center">
                    <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="force_reload()"><span class="glyphicon glyphicon-new-window"></span>&nbsp; Ir para a nova sala</button>
                </div>
HTML_CODE
);
$changeRoomDialog->renderHTML();


if(Authenticator::isAppLoggedIn())
{
    // Dialog to confirm close virtual catechesis room

    $confirmCloseRoomDialog->setTitle("Confirmar encerramento da sala");
    $confirmCloseRoomDialog->setBodyContents(<<<HTML_CODE
                <p>Se encerrar esta sala a sessão de catequese <b>terminará para todos os participantes.</b><br>
                Caso deseje apenas abandonar a sala, permitindo que outro catequista continue a sessão de catequese,
                    clique no botão <img class="" src="img/hang.png"> em baixo, ao centro, ou feche simplesmente este separador.</p>
                <p>Tem a certeza de que pretende encerrar esta sala de catequese?</p>
HTML_CODE
        );
    $confirmCloseRoomDialog->addButton(new Button("Cancelar", ButtonType::SECONDARY))
                            ->addButton(new Button("Encerrar", ButtonType::DANGER, "perform_encerrar_sala()"));
    $confirmCloseRoomDialog->renderHTML();

}
?>


<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src='https://meet.jit.si/external_api.js'></script>

<script>

    //Configuration options:
    //https://github.com/jitsi/jitsi-meet/blob/master/config.js
    //https://github.com/jitsi/jitsi-meet/blob/master/interface_config.js

    const domain = 'meet.jit.si';
    const options = {
        roomName: '<?= constant("CATECHESIS_DOMAIN") ?>/<?= $url ?>',
        width: "100%",
        height: "100%",
        parentNode: document.querySelector('#meet'),
        configOverwrite: {
            disableDeepLinking: true,   //To hide app install prompt in mobile devices and proceed with browser
            defaultLanguage: 'ptBR' },
        interfaceConfigOverwrite: {
            LANG_DETECTION: false,
            TOOLBAR_BUTTONS: [
                'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                'fodeviceselection', 'hangup', 'profile', 'chat',
                'sharedvideo', 'settings', 'raisehand',
                'videoquality', 'filmstrip', 'stats', 'shortcuts',
                'tileview', 'videobackgroundblur', 'help',
                <?php if(Authenticator::isAppLoggedIn())
                { //Opcoes adicionais so para catequistas autenticados
                    ?>
                , 'invite', 'security', 'mute-everyone'
                <?php
                }
                ?>
            ]
        },
        userInfo: {
            email: '',
            displayName: '<?php if(Authenticator::isAppLoggedIn()) echo(Utils::firstAndLastName($_SESSION["nome_utilizador"]) . " (catequista)") ?>'
        }
    };
    const api = new JitsiMeetExternalAPI(domain, options);

    <?php
    if(Authenticator::isAppLoggedIn())
    {
    ?>
    api.addEventListener('participantRoleChanged', function (event)
    {
        if(event.role === 'moderator')
        {
            api.executeCommand('password', '<?= $roomPassword ?>');     //Define a password
            api.executeCommand('toggleLobby', true);                    //Participantes tem de pedir autorizacao para entrar (excepto se tiverem a password)
            api.executeCommand('subject', 'Catequese virtual .: <?= $catecismo ?>º<?= $turma ?> :.');
        }
        else
        {
            setTimeout(() => {
                // why timeout: I got some trouble calling event listeners without setting a timeout :)

                // when local user is trying to enter in a locked room
                api.addEventListener('passwordRequired', () => {
                    api.executeCommand('password', '<?= $roomPassword ?>');
                });

                // when local user has joined the video conference
                api.addEventListener('videoConferenceJoined', (response) => {
                    setTimeout(function(){ api.executeCommand('password', '<?= $roomPassword ?>');}, 300);
                });

            }, 10);
        }
    });


    setTimeout(() => {
    // why timeout: I got some trouble calling event listeners without setting a timeout :)

        // when local user is trying to enter in a locked room
        api.addEventListener('passwordRequired', () => {
            api.executeCommand('password', '<?= $roomPassword ?>');
        });

        // when local user has joined the video conference
        api.addEventListener('videoConferenceJoined', (response) => {
            api.executeCommand('password', '<?= $roomPassword ?>');
        });

    }, 10);



    function fecharSalaVirtual()
    {
        $("#confirmarFecharSala").modal('show');
    }


    function perform_encerrar_sala()
    {
        var dataSessao = '<?= date('d-m-Y', strtotime('today')) ?>';
        var catecismo = '<?= $catecismo ?>';
        var turma = '<?= $turma ?>';

        $.post("encerrarSala.php", {dataSessao: dataSessao, catecismo: catecismo, turma: turma}, function(data, status)
        {
            var obj = $.parseJSON(data);
            if(obj.status_msg !== "OK")
            {
                alert("Falha ao encerrar sala virtual. Erro: " + obj.status_msg);
            }

            // Hang up the meeting call
            api.executeCommand('hangup');

            // Hide the close room button
            var botao_encerrar_sala = document.getElementById("botao_encerrar_sala");
            if(botao_encerrar_sala)
            {
                botao_encerrar_sala.style = "display: none;";
            }
        });
    }

    <?php
    }
    ?>
</script>


<script>
    var global = this;
    var initial_room_url = '<?= $url ?>';
    const REFRESH_RATE_SALA_VIRTUAL = 30000;               //Refrescar estado da sala virtual a cada 30s

    function keep_alive()
    {
        var dataSessao = '<?= $data_sessao ?>';
        var catecismo = '<?= $catecismo ?>';
        var turma = '<?= $turma ?>';

        $.post("salaVirtualStatus.php", {dataSessao: dataSessao, catecismo: catecismo, turma: turma}, function(data, status)
        {
            var obj = $.parseJSON(data);

            if(obj.room_status !== "open")
            {
                //Close room
                closed_room();
            }
            else if(global.initial_room_url !== obj.URL)
            {
                //Move to another room
                document.getElementById("catequista_mudou_sala").innerHTML = obj.last_modified_user_name;
                $('#mudancaSala').modal('show');
            }
        });
    }

    keep_alive();                                                                           //Correr a funcao keep_alive() ao abrir a pagina
    var intervalID = setInterval(function(){keep_alive();}, REFRESH_RATE_SALA_VIRTUAL);     //Correr a funcao keep_alive() periodicamente



    function closed_room()
    {
        api.executeCommand('hangup');
        $('#salaFechada').modal('show');

        // Hide the close room button
        var botao_encerrar_sala = document.getElementById("botao_encerrar_sala");
        if(botao_encerrar_sala)
        {
            botao_encerrar_sala.style = "display: none;";
        }
    }

    function changed_room()
    {
        $('#mudancaSala').modal('show');
    }

    function force_reload()
    {
        location.reload();
        return false;
    }
</script>

<?php
}
?>
</body>
</html>