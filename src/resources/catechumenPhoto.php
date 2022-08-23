<?php

require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../authentication/Authenticator.php');
require_once(__DIR__ . '/../core/UserData.php');

use catechesis\Authenticator;
use catechesis\Utils;
use catechesis\UserData;


// Start a secure session if none is running
Authenticator::startSecureSession();



if($_REQUEST['foto_name'] && $_REQUEST['foto_name']!="")
{
    header('Content-type: image/jpeg');

    $filename = Utils::sanitizeInput($_REQUEST['foto_name']);

    // If the requested resource is NOT free access and the user is not authenticated, then deny access
    if(!Authenticator::isAppLoggedIn())
    {
        // serve the default user profile picture
        readfile("img/default-user-icon-profile.png");
        exit();
    }

    // Try to serve the requested image
	$bytesRead = readfile(UserData::getCatechumensPhotosFolder() . '/' . $filename);

    //If the image could not be loaded, serve the default one
    if(!$bytesRead)
        readfile("img/default-user-icon-profile.png");
}
else
{
    header('Content-type: image/png');
    readfile("img/default-user-icon-profile.png");
}

?>