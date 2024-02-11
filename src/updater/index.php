<?php
require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/../authentication/Authenticator.php');
require_once(__DIR__ . '/../core/DataValidationUtils.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/Checker.php');
require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/../core/UpdateChecker.php');
require_once(__DIR__ . '/../core/log_functions.php');

/* TODO:
- [x] Apagar pasta e .tar no final;
- [x] Verificar permissoes de admin;
- [x] Fazer backup dos ficheiros de config antes de atualizar;
- [x] Atualizar variavel de sessao que guarda versao mais recente (para nao aparecer novamente o popup de atualizacao disponivel);
- [x] Inserir registo no log do CatecheSis a dizer que o utilizador atual atualizou o CatecheSis;
- [x] Enviar pais no pedido;
- [x] Adicionar menu "Verificar existencia de atualizacoes";
- [ ] Script para correr atualizacao na command line; https://devlateral.com/guides/php/how-to-run-a-php-script-in-cli-mode-only
- [ ] Criar package especial para deploy em sistemas existentes;
*/

use catechesis\DataValidationUtils;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\UpdateChecker;
use MirazMac\Requirements\Checker;
use catechesis\PdoDatabaseManager;
use catechesis\Configurator;
use catechesis\Authenticator;


if(!Authenticator::isAdmin())
{
    echo("<html><body>");
    echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
    echo("</div></body></html>");
    die();
}

// Instantiate a widget manager
$pageUI = new WidgetManager("../");

// Add dependencies
$pageUI->addCSSDependency('css/bootstrap.min.css');
$pageUI->addCSSDependency('css/custom-navbar-colors.css'); //FIXME Remove this when migrating to Bootstrap 5
$pageUI->addCSSDependency('font-awesome/fontawesome-free-5.15.1-web/css/all.min.css');
$pageUI->addCSSDependency('fonts/Nexa.css');
$pageUI->addJSDependency('js/jquery.min.js');
$pageUI->addJSDependency('js/bootstrap.min.js');


// Control whether the previous/next buttons should be shown
$has_previous = true;
$has_next = true;

// Status variables
$checker = new Checker;
$update_package_file = __DIR__ . '/update_package.tar.gz';
$update_package_file_uncompressed = __DIR__ . '/update_package.tar';
$update_package_folder = __DIR__ . '/update_patch';
$error_donwload_package = false;
$error_extract_package = false;
$https_pass = true;
$reqs_satisfied = false;
$error_missing_recipe = false;
$error_update_database = false;
$error_update_files = false;
$error_remove_obsolete_files = false;
$error_updating_configuration_files = false;


// Process form steps
$current_step = 0;
if($_REQUEST['setup_step'])
{
    $current_step = intval($_REQUEST['setup_step']);
}
else if($_SESSION['setup_step'])
{
    $current_step = $_SESSION['setup_step'];
}


$updateChecker = null;

switch($current_step)
{
    case -1: //Restart
        $current_step = 0;
    case 0:
    case 1:

        // Update environment variables
        //include(__DIR__ . '/../core/version_info.php');     // Force refresh version info
        //include(__DIR__ . '/../core/UpdateChecker.php');    // Force load updated version info
        require_once(__DIR__ . '/../core/check_for_updates.php');
        //check_for_updates();
        $updateChecker = new UpdateChecker();
        $_SESSION['IS_UPDATE_AVAILABLE'] = $updateChecker->isUpdateAvailable();
        $_SESSION['LATEST_AVAILABLE_VERSION'] = $updateChecker->getLatestVersion();
        $_SESSION['CURRENT_VERSION'] = $updateChecker->getCurrentVersion();
        $_SESSION['UPDATE_CHANGELOG_URL'] = $updateChecker->getChangelogUrl();
        break;

    case 3:

        //Delete previous packages if exist
        if(file_exists($update_package_file))
            unlink($update_package_file);
        if (file_exists($update_package_file_uncompressed))
            unlink($update_package_file_uncompressed);
        if(file_exists($update_package_folder))
            SetupUtils\delete_dir($update_package_folder);

        //Download the update package
        $updateChecker = new UpdateChecker();
        $success = file_put_contents($update_package_file, file_get_contents($updateChecker->getDownloadUrl()));

        if ($success)
        {
            try
            {
                // Decompress from gz
                $p = new PharData($update_package_file);
                $p->decompress(); // creates .tar file

                mkdir($update_package_folder, 0777, true);

                // Unarchive from the tar
                $phar = new PharData($update_package_file_uncompressed);
                $phar->extractTo($update_package_folder, null, true);

                //Immediately update the updater itself, so that the actions that follow (license, requirements, etc) can be apropriately set for this update
                $recipe_file = $update_package_folder . '/update_recipe.php';
                //$recipe_file = __DIR__ . '/update_recipe.php'; //DEBUG
                if(file_exists($recipe_file))
                {
                    require_once($recipe_file);
                    update_updater();
                    update_licenses();
                }
                else
                {
                    $error_extract_package = true;
                }

            }
            catch(Exception $e)
            {
                $error_extract_package = true;
            }
        }
        else
        {
            $error_donwload_package = true;
        }
        $current_step++;
        break;


    case 5:
        // Define requirements
        $checker->requirePhpVersion('>=7.4')
            ->requirePhpExtensions(['pdo_mysql', 'gd', 'xml', 'XMLWriter', /*'xsl',*/ 'zip', 'DOM', 'MBString'])
            ->requireClasses(['PDO', 'finfo', 'stdClass'])
            //->requireApacheModules(['mod_rewrite'])
            ->requireFunctions(['random_bytes'])
            ->requireFile(__DIR__ . "/../core/config/catechesis_config.inc.template.php", Checker::CHECK_FILE_EXISTS)
            ->requireDirectory(__DIR__ . "/../", Checker::CHECK_IS_READABLE)
        ;

        // Runs the check and returns parsed requirements as an array
        // Contains parsed requirements with state of the current values
        // and their comparison result
        $output = $checker->check();

        // Should be called after running check() to see if requirements has met or not
        $reqs_satisfied = $checker->isSatisfied();

        // Check HTTPS
        if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')
        {
            $reqs_satisfied = false;
            $https_pass = false;
        }
        break;


    case 8:
        //Update database
        $recipe_file = $update_package_folder . '/update_recipe.php';
        //$recipe_file = __DIR__ . '/update_recipe.php'; //DEBUG
        if(file_exists($recipe_file))
        {
            require_once($recipe_file);
            $error_update_database = !update_database();
        }
        else
        {
            $error_missing_recipe = true;
        }

        break;

    case 10:
        //Update files
        $recipe_file = $update_package_folder . '/update_recipe.php';
        //$recipe_file = __DIR__ . '/update_recipe.php'; //DEBUG
        if(file_exists($recipe_file))
        {
            // Update files
            require_once($recipe_file);
            $error_update_files = !update_files();
            $error_remove_obsolete_files = !delete_obolete_files();
            $error_updating_configuration_files = !update_configuration_files();

            // Write log entry
            writeLogEntry("Atualizou o CatecheSis para a versão " . $_SESSION['LATEST_AVAILABLE_VERSION']);
        }
        else
        {
            $error_missing_recipe = true;
        }
        break;

}


//Store the current step in the session
$_SESSION['setup_step'] = $current_step;

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CatecheSis | Assistente de atualização</title>

    <link rel="shortcut icon" href="../img/favicon.png" type="image/png">
    <link rel="icon" href="../img/favicon.png" type="image/png">

    <?php $pageUI->renderCSS(); ?>
    <link rel="stylesheet" href="../font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">
    <link rel="stylesheet" href="../fonts/Nexa.css">
    <link rel="stylesheet" href="../fonts/Petemoss.css">
    <link rel="stylesheet" href="../css/animate.min.css">
    <link rel="stylesheet" href="css/updater.css">
</head>
<body>

<div class="container" id="contentor">

    <div class="limiter">
        <div class="container-login">
            <div class="wrap-login">

                <div id="left-text" class="col-md-4">
                    <div class="row" style="margin-bottom: 80px;"></div>
                    <img src="../img/CatecheSis_Logo_Navbar.svg" class="img-responsive" style="scale: 0.8;">
                    <div class="row" style="margin-bottom: 40px"></div>
                    <ul>
                        <?php
                        $STEPS = ["Início",
                                    "Descarregar atualização",
                                    "",                                     // invisible step
                                    "Extrair pacote de atualização",
                                    "Termos e condições",
                                    "Verificação de requisitos",
                                    "Atualizar base de dados",
                                    "",
                                    "Atualizar ficheiros",
                                    "",
                                    "Fim"];

                        $idx = 0;
                        foreach($STEPS as $item)
                        {
                            if($idx==$current_step && $current_step < (count($STEPS)-1))
                            {
                            ?>
                                <li><b><i class="fas fa-caret-right"></i> <?= $item ?></b></li>
                            <?php
                            }
                            else if(($idx<$current_step  ||  $current_step == (count($STEPS)-1)) && $item!='') //When we reach the last step ("finalized"), mark it as already checked
                            {
                                ?>
                                <li><i class="fas fa-check"></i> <?= $item ?></li>
                                <?php
                            }
                            else
                            {
                                ?>
                                <li><?= $item ?></li>
                                <?php
                            }
                            $idx++;
                        }
                        ?>
                        <div style="margin-bottom: 20px;"></div>
                        <div class="progress" style="width: 50%">
                            <div id="global-progress-bar" class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="<?= 100*$current_step/(count($STEPS)-1) ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= 100*$current_step/(count($STEPS)-1) ?>%;"></div>
                        </div>
                    </ul>
                    <div style="margin-bottom: 80px;"></div>
                </div>

                <div id="right-form" class="col-md-8">
                    <div class="row" style="margin-bottom: 80px;"></div>

                    <?php
                    switch($current_step)
                    {
                        case 0:
                            $has_previous = false;
                            $current_step++;
                            ?>
                        <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">

                            <h1>Atualização</h1>
                            <h2>Bem-vindo ao assistente de atualização do CatecheSis!</h2>

                            <?php
                            if($updateChecker->isUpdateAvailable())
                            {
                            ?>
                            <div style="overflow: hidden;">
                                <div class="container col-xs-12">
                                    <p>Está disponível uma nova versão do CatecheSis!</p>
                                    <div class="col-xs-2">
                                        <img src="../img/CatecheSis_Logo_About.svg" class="img-responsive">
                                    </div>
                                    <div class="col-xs-10">
                                        <div class="col-xs-12">
                                            <div style="margin-bottom: 10px;"></div>
                                            <table>
                                                <thead>
                                                <th></th>
                                                <th></th>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td style="padding-right: 20px;">Versão instalada:</td>
                                                    <td><?= $updateChecker->getCurrentVersion()  ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding-right: 10px">Versão disponível:</td>
                                                    <td><?= $updateChecker->getLatestVersion() ?></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div style="margin-bottom: 40px;"></div>
                                    <p><a href="<?= $_SESSION['UPDATE_CHANGELOG_URL'] ?>" target="_blank">Saiba mais</a> acerca das novidades incluídas atualização.</p>
                                </div>
                                <div class="clearfix"></div>
                            </div>

                                <div class="clearfix" style="margin-bottom: 20px"></div>

                                <div class="alert alert-warning"><strong>AVISO!</strong> O CatecheSis poderá ficar indisponível para todos os utilizadores durante alguns minutos, enquanto decorre a atualização.</div>
                                <div class="alert alert-info"><strong>RECOMENDAÇÃO!</strong> Por favor faça uma cópia de segurança da base de dados antes de avançar.</div>

                                <div class="clearfix" style="margin-bottom: 20px"></div>

                                <p>Para começar a atualização, clique em Seguinte.</p>

                            <?php
                            }
                            else
                            {
                                $has_previous = false;
                                $has_next = false;
                            ?>
                                <p>Já está a utilizar a versão mais recente do CatecheSis.</p>
                            <?php
                            }
                            ?>

                            <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                        </form>
                    <?php
                        break;

                        case 1:
                            $has_previous = false;
                            $has_next = false;
                            $current_step++;
                            ?>
                        <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                            <h1>Descarregar o pacote de atualização</h1>

                            <div style="margin-bottom: 20px;"></div>
                            <p>O CatecheSis está agora a transferir o pacote de atualização. Por favor aguarde...</p>
                            <div class="progress" style="width: 80%">
                                <div id="global-progress-bar" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                            </div>

                            <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                        </form>
                        <?php
                            break;

                        case 4:
                            $current_step++;
                            ?>
                        <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                            <h1>Termos e condições</h1>
                            <h2>A utilização do CatecheSis está sujeita aos seguintes termos e condições.</h2>

                            <div style="height: 40vh;  overflow: scroll">
                                <label>Resumo da licença <a href="../licenses/CatecheSis/LICENSE" target="_blank">AGPL-3.0</a></label>
                                <pre><?php include(__DIR__ . '/../licenses/CatecheSis/LICENSE_SHORT_PT'); ?></pre>

                                <label>Termos e condições</label>
                                <pre><?php include(__DIR__ . '/../licenses/CatecheSis/TERMS_AND_CONDITIONS_PT'); ?></pre>
                            </div>

                            <div style="margin-bottom: 40px"></div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <input type="checkbox" id="terms_and_conditions" name="terms_and_conditions" required>
                                    <label for="terms_and_conditions">Li e aceito os termos e condições</label>
                                </div>
                            </div>

                            <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                        </form>
                            <?php
                            break;


                        case 5:
                    ?>
                        <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                            <h1>Verificação de requisitos</h1>

                            <p>O servidor deve cumprir alguns requisitos para executar o CatecheSis.</p>

                            <?php
                            if ($reqs_satisfied)
                            {
                                echo "<div class=\"alert alert-success\"><strong>Boa!</strong> Parece que o seu servidor cumpre todos os requisitos!</p></div>";
                                $current_step++;
                            }
                            else
                            {
                                echo "<div class=\"alert alert-danger\"><strong>Ups!</strong> Alguns requisitos não estão a ser cumpridos pelo seu servidor. :( <br>Confira abaixo a lista completa:<br><br>";
                                echo "<p>" . join('<br>', $checker->getErrors()) . "</p>";
                                if(!$https_pass)
                                    echo "<p>O servidor não está a utilizar HTTPS. Para garantir a segurança dos dados pessoais, o uso de HTTPS é obrigatório no CatecheSis.</p>";
                                echo "</div>";
                                $has_next = false;
                            }
                            ?>
                            <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                        </form>
                        <?php
                            break;

                        case 6:
                            $has_previous = false;
                            $has_next = false;
                            $current_step++;
                            ?>
                            <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                <h1>Atualizar base de dados</h1>

                                <div style="margin-bottom: 20px;"></div>
                                <p>A atualizar a base de dados. Por favor aguarde...</p>
                                <div class="progress" style="width: 80%">
                                    <div id="global-progress-bar" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                                </div>

                                <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                            </form>
                            <?php
                            break;


                        case 8:
                            $has_previous = false;
                            $has_next = false;

                            if($error_missing_recipe)
                            {
                                $has_previous = true;
                                ?>
                            <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                <h1>Erro</h1>

                                <div style="margin-bottom: 20px;"></div>

                                <div class="alert alert-danger"><strong>ERRO!</strong> O pacote de atualização está corrompido.<br>
                                    Por favor tente novamente. Se o problema persistir, reporte em <a href="https://catechesis.org.pt/contactos.php">https://catechesis.org.pt/contactos.php</a>
                                </div>

                                <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                            </form>
                            <?php
                            }
                            else if($error_update_database)
                            {
                                $has_previous = true;
                            ?>
                            <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                <h1>Erro</h1>

                                <div style="margin-bottom: 20px;"></div>

                                <div class="alert alert-danger"><strong>ERRO!</strong> Ocorreu um erro ao atualizar a base de dados.<br>
                                    Por favor tente novamente. Se o problema persistir, reporte em <a href="https://catechesis.org.pt/contactos.php">https://catechesis.org.pt/contactos.php</a>
                                </div>

                                <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                            </form>
                            <?php
                            }
                            else
                            {
                                $current_step++;
                                ?>
                            <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                <h1>Atualizar ficheiros</h1>

                                <div style="margin-bottom: 20px;"></div>
                                <p>A atualizar ficheiros. Por favor aguarde...</p>
                                <div class="progress" style="width: 80%">
                                    <div id="global-progress-bar" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                                </div>

                                <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                            </form>
                            <?php
                            }
                            break;


                        case 10:
                            $has_previous = false;
                            $has_next = false;
                            if($error_missing_recipe)
                            {
                                $has_previous = true;
                            ?>
                                <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                    <h1>Erro</h1>

                                    <div style="margin-bottom: 20px;"></div>

                                    <div class="alert alert-danger"><strong>ERRO!</strong> O pacote de atualização está corrompido.<br>
                                        Por favor tente novamente. Se o problema persistir, reporte em <a href="https://catechesis.org.pt/contactos.php">https://catechesis.org.pt/contactos.php</a>
                                    </div>

                                    <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                                </form>
                                <?php
                            }
                            else if($error_update_files)
                            {
                                $has_previous = true;
                                ?>
                                <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                    <h1>Erro</h1>

                                    <div style="margin-bottom: 20px;"></div>

                                    <div class="alert alert-danger"><strong>ERRO!</strong> Ocorreu um erro ao atualizar os ficheiros do programa.<br>
                                        Por favor tente novamente. Se o problema persistir, reporte em <a href="https://catechesis.org.pt/contactos.php">https://catechesis.org.pt/contactos.php</a>
                                    </div>

                                    <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                                </form>
                                <?php
                            }
                            else if($error_remove_obsolete_files)
                            {
                                $has_previous = true;
                                ?>
                                <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                    <h1>Erro</h1>

                                    <div style="margin-bottom: 20px;"></div>

                                    <div class="alert alert-danger"><strong>ERRO!</strong> Ocorreu um erro ao eliminar ficheiros obsoletos do programa.<br>
                                    Por favor tente novamente. Se o problema persistir, reporte em <a href="https://catechesis.org.pt/contactos.php">https://catechesis.org.pt/contactos.php</a>
                                    </div>

                                    <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                                </form>
                            <?php
                            }
                            else if($error_updating_configuration_files)
                            {
                                $has_previous = true;
                                ?>
                                <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                    <h1>Erro</h1>

                                    <div style="margin-bottom: 20px;"></div>

                                    <div class="alert alert-danger"><strong>ERRO!</strong> Ocorreu um erro ao atualizar os ficheiros de configuração do CatecheSis.<br>
                                        Por favor tente novamente. Se o problema persistir, reporte em <a href="https://catechesis.org.pt/contactos.php">https://catechesis.org.pt/contactos.php</a>
                                    </div>

                                    <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                                </form>
                                <?php
                            }
                            else
                            {

                            $has_previous = true;
                            $has_next = false;

                            $cleanup_success = true;

                            // Delete the update patch folder and files
                            try
                            {
                                if(file_exists($update_package_file))
                                    unlink($update_package_file);
                                if (file_exists($update_package_file_uncompressed))
                                    unlink($update_package_file_uncompressed);
                                if(file_exists($update_package_folder))
                                    $cleanup_success = SetupUtils\delete_dir($update_package_folder);
                            }
                            catch (Exception $e)
                            {
                                $cleanup_success = false;
                            }
                            ?>
                            <h1>Atualização concluída!</h1>
                            <h2>Concluiu com sucesso a atualização do CatecheSis!</h2>

                            <?php
                            if(!$cleanup_success)
                            {
                            ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Não foi possível eliminar o ficheiro <code><?= $update_package_file ?></code> e/ou a diretoria <code><?= $update_package_folder ?></code>.<br>
                                    Por favor, elimine manualmente este ficheiro e/ou a diretoria <code><?= $update_package_folder ?></code> do seu servidor.</div>
                            <?php
                            }
                            ?>

                                <p>As atualizações do CatecheSis são incrementais, pelo que poderão existir mais atualizações para aplicar.</p>

                                <div class="clearfix" style="margin-bottom: 20px"></div>

                                <div class="form-group">
                                    <div class="col-md-4 center-block">
                                        <button id="restart_button" type="submit" class="btn btn-primary" onclick="restart()"><i class="fas fa-undo"></i> <strong>Verificar a existência de atualizações</strong></button>
                                    </div>
                                </div>

                            <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                            </form>
                            <?php
                            }
                            break;
                    }
                    ?>


                        <div class="wizard_buttons">
                            <div class="wizard_buttons_inner">
                                <?php
                                if($has_previous)
                                {?>
                                <button id="prev_button" type="submit" class="btn btn-default" onclick="restart()"><i class="fas fa-undo"></i> <strong>Recomeçar</strong></button>
                                <?php
                                }
                                if($has_next)
                                {?>
                                <button id="next_button" type="submit" class="btn btn-primary" onclick="onNext()"><strong>Seguinte</strong> <i class="fas fa-angle-right"></i></button>
                                <?php
                                }
                                ?>
                            </div>
                        </div>

                    <div class="row" style="margin-bottom: 80px;"></div>
                </div>

            </div>
        </div>
    </div>
    
</div>


<?php $pageUI->renderJS(); ?>
<script src="../js/tooltips.js"></script>

<!-- Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent -->
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Este sítio utiliza cookies para melhorar a sua experiência de navegação. <br>Ao continuar está a consentir essa utilização.","dismiss":"Aceito","learnMore":"Mais info","link":null,"theme":"light-floating"};
</script>
<script type="text/javascript" src="../js/cookieconsent2-1.0.10/cookieconsent.min.js"></script>
<!-- End Cookie Consent plugin -->

<script>
    function onNext()
    {
        var form = document.getElementById("form-wizard");
        for (const el of form.querySelectorAll("[required]"))
        {
            if (!el.reportValidity())
            {
                return;
            }
        }

        if (form.checkValidity())
        {
            var next_button = document.getElementById("next_button");
            next_button.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>&nbsp; Seguinte';
            next_button.disabled = true;
            var progress_bar = document.getElementById("global-progress-bar");
            progress_bar.classList.add('active'); //Start progress bar animation
            form.submit();
        }
    }
    
    function restart()
    {
        var form = document.getElementById("form-wizard");
        var current_step_field = document.getElementById("setup_step_input");
        current_step_field.value = -1;
        form.submit();
    }

    <?php
        switch($current_step)
        {
            case 2:
            case 7:
            case 9:
                // Refresh page automatically to actually start the download/update
                $current_step++;
                ?>

            window.onload = function ()
            {
                'use strict';
                var millisecondsBeforeRefresh = 0;
                window.setTimeout(function () {
                    //document.location.reload();
                    window.location = window.location.href;
                }, millisecondsBeforeRefresh);
            };
                    <?php
                        break;
        }
                    ?>
</script>

        }
    ?>
</script>

<?php
//Store the current step in the session
$_SESSION['setup_step'] = $current_step;
?>

</body>
</html>