<?php
require_once(__DIR__ . '/../core/DataValidationUtils.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/Checker.php');
require_once(__DIR__ . '/utils.php');

use catechesis\DataValidationUtils;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use MirazMac\Requirements\Checker;

// Start or resume a PHP session
session_start();


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
$https_pass = true;
$reqs_satisfied = false;
$catechesis_domain = '';
$catechesis_root = '';
$data_dir = '';
$dir_already_exists = false;
$dir_copy_failed = false;
$main_config_creation_failed = false;
$shadow_config_creation_failed = false;
$main_config_file = '';
$db_connection_failed = false;
$db_tables_creation_failed = false;
$db_host = '';
$db_name = '';
$db_user = '';
$db_pass = '';
$admin_empty_fields = false;
$admin_invalid_username = false;
$admin_invalid_password = false;
$admin_password_mismatch = false;
$admin_account_creation_failed = false;
$admin_account_creation_error = '';
$name = '';
$username = '';
$parishSettings = null;
$gdprSettings = null;

// Process form steps
$current_step = 0;
if($_POST['setup_step'])
{
    $current_step = intval($_POST['setup_step']);
}
else if($_SESSION['setup_step'])
{
    $current_step = $_SESSION['setup_step'];
}

switch($current_step)
{
    case -1: //Restart
        $current_step = 0;
        unset($_SESSION['username']);
        unset($_SESSION['admin']);
        session_unset();
        session_destroy();
        session_start();
    case 0:
    case 1:
        $_SESSION['catechesis_base_url'] = '';
        $_SESSION['shadow_config_file'] = '';
        break;

    case 2:
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

    case 3:
        $catechesis_domain = $_SERVER['HTTP_HOST'];
        $catechesis_root = dirname(__DIR__) . "/";
        $_SESSION['catechesis_base_url'] = str_replace("/setup/", "", Utils::getBaseUrl());

        //Populate directoris list
        $dir_list = [];
        echo('<datalist id="dir_list">');
        $scan = scandir(posix_getpwuid(posix_getuid())['dir']);
        foreach($scan as $file)
        {
            if (!is_dir("$file"))
            {
                $dir_list[] = '/' . $file;
                echo('<option value="/' . $file . '">');
            }
        }
        echo('</datalist>');


        if($_POST['data_dir'])
        {
            $data_dir = Utils::sanitizeInput($_POST['data_dir']);

            //Create directory if it does not exist already
            if(!file_exists($data_dir) && !is_dir($data_dir))
            {
                mkdir($data_dir, 0755, true);

                //Copy directory contents
                if(!SetupUtils\xcopy(__DIR__ . "/catechesis_data", $data_dir))
                {
                    $dir_copy_failed = true;
                }
                else
                {
                    //Write config files
                    $main_config_file = __DIR__ . "/../core/config/catechesis_config.inc.php";
                    $_SESSION['shadow_config_file'] = $data_dir . "/config/catechesis_config.shadow.php";

                    if(!SetupUtils\xcopy(__DIR__ . "/../core/config/catechesis_config.inc.template.php", $main_config_file))
                    {
                        $main_config_creation_failed = true;
                        break;
                    }
                    if(!SetupUtils\xcopy(__DIR__ . "/catechesis_data/config/catechesis_config.shadow.template.php", $_SESSION['shadow_config_file']))
                    {
                        $shadow_config_creation_failed = true;
                        break;
                    }

                    $main_settings = array();
                    $main_settings['<CATECHESIS_DOMAIN>'] = $catechesis_domain;
                    $main_settings['<CATECHESIS_BASE_URL>'] = $_SESSION['catechesis_base_url'];
                    $main_settings['<CATECHESIS_ROOT_DIRECTORY>'] = $catechesis_root;
                    $main_settings['<CATECHESIS_DATA_DIRECTORY>'] = $data_dir;
                    SetupUtils\replace_strings_in_file($main_config_file, $main_settings);

                    $shadow_settings = array();
                    $shadow_settings['<CATECHESIS_UL_SITE_KEY>'] = Utils::secureRandomString(64);
                    SetupUtils\replace_strings_in_file($_SESSION['shadow_config_file'], $shadow_settings);

                }
            }
            else
                $dir_already_exists = true;

            if(!$dir_already_exists && !$dir_copy_failed)
                $current_step++;
        }
        break;


    case 4:
        if($_POST['bd_host'])
        {
            $db_host = Utils::sanitizeInput($_POST['bd_host']);
            $db_name = Utils::sanitizeInput($_POST['bd_name']);
            $db_user = Utils::sanitizeInput($_POST['bd_user']);
            $db_pass = Utils::sanitizeInput($_POST['bd_pass']);

            if(!SetupUtils\test_db_connection($db_host, $db_name, $db_user, $db_pass))
            {
                $db_connection_failed = true;
                break;
            }

            //Fill configuration values
            $shadow_settings = array();
            $shadow_settings['<CATECHESIS_HOST>'] = $db_host;
            $shadow_settings['<CATECHESIS_DB>'] = $db_name;
            $shadow_settings['<DB_ROOT_USER>'] = $db_user;
            $shadow_settings['<DB_ROOT_PASSWORD>'] = $db_pass;
            SetupUtils\replace_strings_in_file($_SESSION['shadow_config_file'], $shadow_settings);

            //Populate database
            if(!SetupUtils\run_sql_script($db_host, $db_name, $db_user, $db_pass, __DIR__ . "/db/catechesis_database.sql")
                || !SetupUtils\run_sql_script($db_host, $db_name, $db_user, $db_pass, __DIR__ . "/db/ulogin_database.sql")
                || !SetupUtils\run_sql_script($db_host, $db_name, $db_user, $db_pass, __DIR__ . "/db/script_collation.sql"))
                {
                    $db_tables_creation_failed = true;
                    break;
                }

            $current_step++;
        }
        break;


    case 5:
        if($_POST['admin_name'] && $_POST['admin_username'] && $_POST['admin_password_1'] && $_POST['admin_password_2'])
        {
            $name = Utils::sanitizeInput($_POST['admin_name']);
            $username = Utils::sanitizeInput($_POST['admin_username']);
            $password_user = Utils::sanitizeInput($_POST['admin_password_1']);
            $password_user_2 = Utils::sanitizeInput($_POST['admin_password_2']);
            $isAdmin = true;
            $isCatechist = false;
            $isCatechistActive = false;
            $tel = NULL;
            $email = NULL;


            if(empty($name) || empty($username) || empty($password_user))
            {
                $admin_empty_fields = true;
                break;
            }
            else if(!DataValidationUtils::validateUsername($username))
            {
                $admin_invalid_username = true;
                break;
            }
            else if(!DataValidationUtils::validatePassword($password_user))
            {
                $admin_invalid_password = true;
                break;
            }
            else if($password_user != $password_user_2)
            {
                $admin_password_mismatch = true;
                break;
            }
            else
            {
                try
                {
                    $_SESSION['username'] = $username;
                    $_SESSION['nome_utilizador'] = $name;
                    $_SESSION['admin'] = 1;

                    $old_session_id = session_id(); //Backup the session id (because it will be overwritten by the following import)
                    $old_session_data = $_SESSION;

                    require_once(__DIR__ . '/../core/PdoDatabaseManager.php');
                    $db = new catechesis\PdoDatabaseManager();
                    if(!$db->createUserAccount($username, $name, $password_user, $isAdmin, $isCatechist, $isCatechistActive, $tel, $email))
                    {
                        $admin_account_creation_failed = true;
                        unset($_SESSION['username']);
                        unset($_SESSION['nome_utilizador']);
                        unset($_SESSION['admin']);
                        break;
                    }

                    $current_step++;

                    //Prepair step 6
                    SetupUtils\require_once_keep_session(__DIR__ . '/../gui/widgets/configuration_panels/ParishSettingsPanel/ParishSettingsPanelWidget.php',
                        $old_session_id, $old_session_data); //Import and restore the previous session id
                    $parishSettings = new catechesis\gui\ParishSettingsPanelWidget();
                    $pageUI->addWidget($parishSettings);

                    $_SESSION['username'] = $username;
                    $_SESSION['nome_utilizador'] = $name;
                    $_SESSION['admin'] = 1;
                }
                catch (Exception $e)
                {
                    $admin_account_creation_failed = true;
                    $admin_account_creation_error = $e->getMessage();
                    break;
                }
            }
        }
        break;


    case 6:
        SetupUtils\require_once_keep_session(__DIR__ . '/../gui/widgets/configuration_panels/ParishSettingsPanel/ParishSettingsPanelWidget.php');
        SetupUtils\require_once_keep_session(__DIR__ . '/../gui/widgets/configuration_panels/GDPRParishSettingsPanel/GDPRParishSettingsPanelWidget.php');

        $parishSettings = new catechesis\gui\ParishSettingsPanelWidget();
        $pageUI->addWidget($parishSettings);

        break;

    case 7:
        SetupUtils\require_once_keep_session(__DIR__ . '/../gui/widgets/configuration_panels/GDPRParishSettingsPanel/GDPRParishSettingsPanelWidget.php');

        $gdprSettings = new catechesis\gui\GDPRParishSettingsPanelWidget();
        $pageUI->addWidget($gdprSettings);

        break;

    case 8:
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

    <title>CatecheSis | Assistente de instalação</title>

    <link rel="shortcut icon" href="../img/favicon.png" type="image/png">
    <link rel="icon" href="../img/favicon.png" type="image/png">

    <?php $pageUI->renderCSS(); ?>
    <link rel="stylesheet" href="../font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">
    <link rel="stylesheet" href="../fonts/Nexa.css">
    <link rel="stylesheet" href="../fonts/Petemoss.css">
    <link rel="stylesheet" href="../css/animate.min.css">
    <link rel="stylesheet" href="css/setup.css">
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
                                    "Termos e condições",
                                    "Verificação de requisitos",
                                    "Opções de instalação",
                                    "Base de dados",
                                    "Criar conta de administrador",
                                    "Dados da paróquia",
                                    "RGPD",
                                    "Limpeza"];

                        $idx = 0;
                        foreach($STEPS as $item)
                        {
                            if($idx==$current_step && $current_step < (count($STEPS)-1))
                            {
                            ?>
                                <li><b><i class="fas fa-caret-right"></i> <?= $item ?></b></li>
                            <?php
                            }
                            else if($idx<$current_step  ||  $current_step == (count($STEPS)-1)) //When we reach the last step ("finalized"), mark it as already checked
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

                            <h1>Assistente de instalação</h1>
                            <h2>Bem-vindo ao assistente de instalaçãdo do CatecheSis!</h2>

                            <p>Este assistente vai orientá-lo passo a passo na configuração do CatecheSis para a sua paróquia.</p>

                            <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                        </form>
                    <?php
                        break;

                        case 1:
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

                        case 2:
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

                        case 3:
                            ?>
                        <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                            <h1>Opções de Instalação</h1>

                            <h4>Diretoria do programa</h4>
                            <p>O CatecheSis será instalado na diretoria <code><?= $catechesis_root ?></code> .<br>
                                Para mudar a diretoria da instalação, por favor mova manualmente os ficheiros para a pasta pretendida no servidor e volte a executar este assistente.
                            </p>

                            <div style="margin-bottom: 40px;"></div>
                            <h4>Diretoria de dados</h4>
                            <p>Selecione a diretoria onde serão guardados os dados gerados pelo CatecheSis.<br>
                                Esta diretoria não deve ser acessível através de um browser.</p>

                            <div class="form-group">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="data_dir" name="data_dir" placeholder="/home/catechesis-data" list="dir_list" value="<?=$data_dir?>" required>
                                </div>
                                <div class="col-md-4">
                                    <span class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="A diretoria será criada. É nesta diretoria que serão guardados os dados produzidos durante a utilização do CatecheSis, tais como fotografias e documentos carregados. Esta é a diretoria relevante para efeitos de backup."></span>
                                </div>
                            </div>
                            <?php
                            if($dir_already_exists)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> A diretoria <code><?= $data_dir ?></code> já existe no servidor.<br>
                                    Para evitar perdas de dados, nenhuma alteração foi feita. Se pretende mesmo utilizar esta diretoria, por favor elimine-a primeiro.</div>
                                <?php
                            }

                            if($dir_copy_failed)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Não foi possível criar a diretoria <?= $data_dir ?>.<br>
                                    Por favor verifqiue se o caminho existe e se o utilizador Apache tem permissões de escrita.</div>
                                <?php
                            }

                            if($main_config_creation_failed)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Não foi possível criar o ficheiro de configuração principal do CatecheSis em <code><?= $main_config_file ?></code>.<br>
                                    Por favor verifqiue se o utilizador Apache tem permissões de escrita.</div>
                                <?php
                            }

                            if($shadow_config_creation_failed)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Não foi possível criar o ficheiro de configuração "shadow" do CatecheSis em <code><?= $_SESSION['shadow_config_file'] ?></code>.<br>
                                    Por favor verifqiue se o utilizador Apache tem permissões de escrita.</div>
                                <?php
                            }
                            ?>

                            <div class="clearfix" style="margin-bottom: 40px;"></div>
                            <h4>Configurações detetadas automaticamente</h4>
                            <table>
                                <tr>
                                    <td>Domínio:</td>
                                    <td><code><?= $catechesis_domain ?></code></td>
                                </tr>
                                <tr>
                                    <td>URL base do CatecheSis:</td>
                                    <td><code><?= $_SESSION['catechesis_base_url'] ?></code></td>
                                </tr>
                            </table>

                            <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                        </form>
                            <?php
                            break;

                        case 4:
                            ?>
                        <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                            <h1>Base de Dados</h1>

                            <p>Por favor preencha os dados de acesso ao servidor de base de dados:</p>
                            <div class="form-group">
                                <div class="col-md-8">
                                    <label for="bd_host">Servidor:</label>
                                    <input type="text" class="form-control" id="bd_host" name="bd_host" placeholder="localhost" value="<?=$db_host?:'localhost'?>" required>
                                </div>
                                <div class="col-md-4">
                                    <span style="margin-top: 40px" class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Endereço do servidor de base de dados."></span>
                                </div>

                                <div class="clearfix"></div>

                                <div class="col-md-8">
                                    <label for="bd_name">Nome da base de dados:</label>
                                    <input type="text" class="form-control" id="bd_name" name="bd_name" placeholder="catechesis" value="<?=$db_name?>" required>
                                </div>
                                <div class="col-md-4">
                                    <span style="margin-top: 40px"  class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Esta base de dados tem de ser previamente criada por si (vazia), no seu servidor de base de dados."></span>
                                </div>

                                <div class="clearfix"></div>

                                <div class="col-md-8">
                                    <label for="bd_user">Utilizador:</label>
                                    <input type="text" class="form-control" id="bd_user" name="bd_user" placeholder="" value="<?=$db_user?>" required>
                                </div>
                                <div class="col-md-4">
                                    <span style="margin-top: 40px"  class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Nome de utilizador da base de dados. Necessita de permissões para criar tabelas."></span>
                                </div>

                                <div class="clearfix"></div>

                                <div class="col-md-8">
                                    <label for="bd_pass">Palavra-passe:</label>
                                    <input type="password" class="form-control" id="bd_pass" name="bd_pass" placeholder="" value="<?=$db_pass?>" required>
                                </div>
                                <div class="col-md-4">
                                    <span style="margin-top: 40px"  class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Palavra-passe do utilizador da base de dados indicado acima."></span>
                                </div>

                                <div class="clearfix"></div>
                            </div>
                            <?php

                            if($db_connection_failed)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Não foi possível estabelecer uma ligação à base de dados.<br>
                                Verifique se o endereço, o nome da base de dados e as credenciais estão corretos.</div>
                                <?php
                            }
                            if($db_tables_creation_failed)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Não foi possível criar as tabelas na base de dados.<br>
                                Verifique se o utilizador da base de dados tem permissões para criar tabelas.</div>
                                <?php
                            }
                            ?>
                            <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                        </form>
                        <?php
                            break;

                        case 5:
                            ?>
                        <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                            <h1>Criar Conta de Administrador</h1>

                            <p>Vamos criar uma conta de administrador.</p>

                            <p>Utilize esta conta para aceder ao CatecheSis após terminar este assistente de instalação.<br>
                                Poderá posteriormente criar contas para os restantes utilizadores/catequistas.</p>

                            <!--nome-->
                            <div class="form-group">
                                <div class="col-xs-6">
                                    <label for="admin_name">Nome:</label>
                                    <input type="text" class="form-control" id="admin_name" name="admin_name" placeholder="Nome completo" value="<?=$name?>" required>
                                </div>
                            </div>

                            <!--username-->
                            <div class="form-group">
                                <div class="col-xs-8">
                                    <label for="admin_username">Nome de utilizador:</label>
                                    <input type="text" class="form-control" id="admin_username" name="admin_username" placeholder="Nome a usar no sistema" value="<?=$username?>" required>
                                </div>
                            </div>
                            <div class="clearfix"></div>

                            <!--password1-->
                            <div class="form-group">
                                <div class="col-xs-8">
                                    <label for="admin_password_1">Palavra-passe:</label>
                                    <input type="password" class="form-control" id="admin_password_1" name="admin_password_1" required>
                                </div>
                            </div>
                            <div class="clearfix"></div>

                            <!--password2-->
                            <div class="form-group">
                                <div class="col-xs-8">
                                    <label for="admin_password_2">Confirmar palavra-passe:</label>
                                    <input type="password" class="form-control" id="admin_password_2" name="admin_password_2" required>
                                </div>
                            </div>
                            <div class="clearfix"></div>

                            <?php
                            if($admin_empty_fields)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Por favor preencha todos os campos.</div>
                                <?php
                            }
                            if($admin_invalid_username)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> O nome de utilizador que introduziu á inválido.</div>
                                <?php
                            }
                            if($admin_invalid_password)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> A palavra-passe que introduziu é inválida.<br>
                                Deve conter pelo menos 10 caracteres, incluindo letras e digitos.</div>
                                <?php
                            }
                            if($admin_password_mismatch)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> As palavras-passe que introduziu não coincidem.</div>
                                <?php
                            }
                            if($admin_account_creation_failed)
                            {
                                ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Ocorreu um erro na base de dados ao tentar criar o utilizador.<br>
                                <br><?= $admin_account_creation_error ?></div>
                                <?php
                            }
                            ?>
                            <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                        </form>
                        <?php
                            break;

                        case 6:
                            ?>
                            <h1>Dados da Paróquia</h1>
                            <h2>Por favor preencha os seguintes dados acerca da sua paróquia.</h2>

                            <p>Faça clique sobre o botão <i><span class="fas fa-pen"></span>Editar</i> para começar.<br>
                            Depois de preencher todos os campos, clique no botão <i><span class="fas fa-save"></span> Guardar</i>.</p>

                            <p>Estes dados podem ser alterados posteriormente a qualquer momento, na página de configurações do CatecheSis.</p>
                            <?php
                            $parishSettings->handlePost();
                            $parishSettings->renderHTML();
                            ?>
                            <p>Quando estiver satisfeito(a) com as suas edições, clique no botão <i>Seguinte</i>.</p>

                            <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step + 1 ?>">
                            </form>
                        <?php
                            break;

                        case 7:
                            ?>
                            <h1>Proteção de dados.</h1>
                            <h2>Por favor preencha os seguintes dados acerca dos responsáveis pelo tratamento de dados na sua paróquia.</h2>

                            <p>Faça clique sobre o botão <i><span class="fas fa-pen"></span>Editar</i> para começar.<br>
                                Depois de preencher todos os campos, clique no botão <i><span class="fas fa-save"></span> Guardar</i>.</p>

                            <p>Estes dados podem ser alterados posteriormente a qualquer momento, na página de configurações do CatecheSis.</p>
                            <?php
                            $gdprSettings->handlePost();
                            $gdprSettings->renderHTML();
                            ?>

                            <p>Quando estiver satisfeito(a) com as suas edições, clique no botão <i>Seguinte</i>.</p>

                            <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step + 1 ?>">
                            </form>
                        <?php
                            break;

                        case 8:
                            $has_previous = true;
                            $has_next = false;

                            // Delete the setup folder
                            try
                            {
                                $cleanup_success = SetupUtils\delete_dir(__DIR__);
                            }
                            catch (Exception $e)
                            {
                                $cleanup_success = false;
                            }
                            ?>
                            <h1>Instalação concluída!</h1>
                            <h2>Concluiu com sucesso a instalação do CatecheSis na sua paróquia!</h2>

                            <?php
                            if(!$cleanup_success)
                            {
                            ?>
                                <div class="alert alert-danger"><strong>ERRO!</strong> Não foi possível eliminar a diretoria <code><?= __DIR__ ?></code>.<br>
                                    A presença desta diretoria num sistema em produção representa um RISCO DE SEGURANÇA grave, permitindo que terceiros acedam aos seus dados.<br>
                                    Por favor, elimine manualmente a diretoria <code><?= __DIR__ ?></code> do seu servidor.</div>
                            <?php
                            }
                            ?>
                            <p>Pode fechar este assistente e aceder à página principal do CatecheSis em <a href="<?=$_SESSION['catechesis_base_url']?>"><?=$_SESSION['catechesis_base_url']?></a></p>

                            <form class="form-horizontal" id="form-wizard" role="form" action="index.php" method="post">
                                <input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">
                            </form>
                            <?php
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
        case 6:
            //Inject setup_step field into Parish Settings panel form
        ?>
            $(document).ready(function(){
                $("#form_settings_<?= $parishSettings->getID() ?>").append('<input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">');
            })
        <?php
            break;

        case 7:
            //Inject setup_step field into RGPD Settings panel form
        ?>
            $(document).ready(function(){
                $("#form_settings_<?= $gdprSettings->getID() ?>").append('<input type="hidden" id="setup_step_input" name="setup_step" value="<?= $current_step ?>">');
            })
        <?php
            break;
    }
    ?>
</script>

</body>
</html>