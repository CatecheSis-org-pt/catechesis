<?php
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/Fortune.php');
require_once(__DIR__ . '/core/DataValidationUtils.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Footer/SimpleFooter.php');
require_once(__DIR__ . '/core/check_maintenance_mode.php'); //Check if maintenance mode is active and redirect visitor

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\Fortune;
use catechesis\DataValidationUtils;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\SimpleFooter;



// Start a secure session if none is running
Authenticator::startSecureSession();


///////////// Authentication code //////////////////

$ulogin = new uLogin('catechesis\Authenticator::appLogin', 'catechesis\Authenticator::appLoginFail');

$action = Utils::sanitizeInput($_POST['action']);
$wrongPassword = false;

// Process logout request
if ($_REQUEST['logout'] && Utils::sanitizeInput($_REQUEST['logout']) =="true")
{
    Authenticator::appLogout($ulogin);
    exit();
}

// Process login request
if(!Authenticator::isAppLoggedIn())
{
    if ($action=='login')
    {
        // Here we verify the nonce, so that only users can try to log in
        // to whom we've actually shown a login page. The first parameter
        // of Nonce::Verify needs to correspond to the parameter that we
        // used to create the nonce, but otherwise it can be anything
        // as long as they match.
        if (isset($_POST['nonce']) && ulNonce::Verify('fidedigno', Utils::sanitizeInput($_POST['nonce'])))
        {
            // We store it in the session if the user wants to be remembered. This is because
            // some auth backends redirect the user and we will need it after the user
            // arrives back.
            if (isset($_POST['autologin']))
                $_SESSION['appRememberMeRequested'] = true;
            else
                unset($_SESSION['appRememberMeRequested']);

            $ulogin->Authenticate(Utils::sanitizeInput($_POST['username']), Utils::sanitizeInput($_POST['password']));
            if($ulogin->IsAuthSuccess())
            {
                // The authentication itself is handled by tha callbacks that we registered with the ulogin object
                // Here we perform additional actions after login

                // Redirect browser
                if ($_REQUEST['redirect'] && DataValidationUtils::checkInnerURL(Utils::sanitizeInput($_REQUEST['redirect'])))
                {
                    // to a custom URL, if any defined in the parameter 'redirect'
                    header("Location: " . Utils::sanitizeInput($_REQUEST['redirect']));
                }
                else
                {
                    // to the dashboard (default)
                    header("Location: " . constant('CATECHESIS_BASE_URL') . "/dashboard.php");
                }

                exit();
            }
            else
            {
                $wrongPassword = true;
            }
        }
        else
        {
            Authenticator::denyAccess();
        }
    }
    /* else if ($action=='autologin'){	// We were requested to use the remember-me function for logging in.
		// Note, there is no username or password for autologin ('remember me')
		$ulogin->Autologin();
		if (!$ulogin->IsAuthSuccess())
			denyAccess();
		//else
			//Utils::error("autologin ok");
	}*/
}
else
{
    // Already logged in, redirect to dashboard
    header("Location: " . constant('CATECHESIS_BASE_URL') . "/dashboard.php");
}
///////////////////////




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

    <title>CatecheSis | Entrar</title>

    <link rel="shortcut icon" href="img/favicon.png" type="image/png">
    <link rel="icon" href="img/favicon.png" type="image/png">

    <?php $pageUI->renderCSS(); ?>
    <link rel="stylesheet" href="font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">
    <link rel="stylesheet" href="fonts/Nexa.css">
    <link rel="stylesheet" href="fonts/Petemoss.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="container" id="contentor">

    <div class="limiter">
        <div class="container-login">
            <div class="wrap-login <?php if($wrongPassword) echo("animate__animated animate__headShake"); ?>">

                <div id="left-text" class="col-md-6">
                    <div class="row" style="margin-bottom: 80px;"></div>
                    <img src="img/CatecheSis_Logo_Navbar.svg" class="img-responsive" style="scale: 0.8;">
                    <div class="row" style="margin-bottom: 40px"></div>
                    <?php

                    //Instantiate a Fortune object and get a random quote
                    $fortunes = new Fortune();

                    $fortune = null;
                    if($_POST['fortune'])
                    {
                        $fortuneIndex = intval($_POST['fortune']);
                        try
                        {
                            $fortune = $fortunes->getFortune($fortuneIndex);
                        }
                        catch(Exception $e)
                        {
                            $fortune = $fortunes->getRandom();
                        }
                    }
                    else
                        $fortune = $fortunes->getRandom();
                    ?>
                    <p id="biblic_citation">"<?= $fortune['citation'] ?>"</p>
                    <p id="biblic_reference">- <?= $fortune['reference'] ?> -</p>
                </div>

                <div id="right-form" class="col-md-6">
                    <div class="row" style="margin-bottom: 80px;"></div>
                    <h1>Entrar</h1>
                    <h2>Bem-vindo ao CatecheSis!</h2>

                    <form class="form-horizontal" role="form" action="login.php" method="post" onsubmit="onSubmit()">
                        <div class="input_box">
                            <input class="input_control" type="text" id="username_field" name="username" placeholder="Nome de utilizador" value="<?= Utils::sanitizeInput($_POST['username']) ?>" required>
                            <span class="focus-input"></span>
                        </div>

                        <div class="input_box">
                            <input class="input_control" type="password" name="password" placeholder="Palavra-passe" required>
                            <span class="focus-input"></span>
                            <a class="forgot_help_text" data-toggle="tooltip" data-placement="top" title="Contacte um administrador ou a coordenação da catequese">Esqueceu-se da palavra-passe?</a>
                        </div>

                        <input type="hidden" name="action" value="login">
                        <?php
                        if($_REQUEST['redirect'] && DataValidationUtils::checkInnerURL(Utils::sanitizeInput($_REQUEST['redirect'])))
                        {
                        ?>
                        <input type="hidden" name="redirect" value="<?= Utils::sanitizeInput($_REQUEST['redirect']) ?>">
                        <?php
                        }
                        ?>
                        <input type="hidden" name="fortune" value="<?= $fortune['index'] ?>">
                        <input type="hidden" id="nonce" name="nonce" value="<?= Authenticator::createNonce();?>">

                        <?php
                        if($wrongPassword)
                        {
                        ?>
                            <div class="alert alert-warning animate__animated animate__fadeIn" role="alert">
                                Por favor verifique se o <strong>nome de utilizador</strong> e a <strong>palavra-passe</strong> estão corretos.
                            </div>
                        <?php
                        }
                        ?>

                        <button id="login_button" type="submit" class="btn btn-primary"><strong>Iniciar sessão</strong></button>
                    </form>

                    <div class="row" style="margin-bottom: 80px;"></div>
                </div>

            </div>
        </div>
    </div>

</div>

<?php
$footer->renderHTML();
?>



<?php $pageUI->renderJS(); ?>
<script src="js/tooltips.js"></script>

<!-- Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent -->
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Este sítio utiliza cookies para melhorar a sua experiência de navegação. <br>Ao continuar está a consentir essa utilização.","dismiss":"Aceito","learnMore":"Mais info","link":null,"theme":"light-floating"};
</script>
<script type="text/javascript" src="js/cookieconsent2-1.0.10/cookieconsent.min.js"></script>
<!-- End Cookie Consent plugin -->

<script>
    document.getElementById("username_field").focus();

    function onSubmit()
    {
        var login_button = document.getElementById("login_button");
        login_button.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>&nbsp; Iniciar sessão';
        login_button.disabled = true;
    }
</script>

<?php
// DEBUG Ulogin
//ulLog::ShowDebugConsole();
?>

</body>
</html>