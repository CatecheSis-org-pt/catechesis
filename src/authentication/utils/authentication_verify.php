<?php

require_once(__DIR__ . '/../ulogin/config/all.inc.php');
require_once(__DIR__ . '/../ulogin/main.inc.php');
require_once(__DIR__ . '/../../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../Authenticator.php');
require_once(__DIR__ . '/../../core/Utils.php');

use catechesis\Authenticator;
use catechesis\Utils;

// Start a secure session if none is running
Authenticator::startSecureSession();

// Check if the user is authenticated, and redirect him to front page otherwise
if(!Authenticator::isAppLoggedIn())
	Authenticator::denyAccess(Utils::getCurrentPageURL());

?>
