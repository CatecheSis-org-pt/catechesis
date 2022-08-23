<?php

require_once(__DIR__ . '/../core/UserData.php');

use catechesis\UserData;


header('Content-type: image/png');

// Try to serve the requested image
$bytesRead = readfile(UserData::getParishLogoFile(true));

//If the image could not be loaded, serve the default one
if(!$bytesRead)
    readfile("img/image_placeholder.png");

?>