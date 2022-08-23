<?php

require_once(__DIR__ . '/../core/UserData.php');

use catechesis\UserData;


header('Content-type: image/jpg');

// Try to serve the requested image
$bytesRead = readfile(UserData::getParishPublicImageFile(true));

//If the image could not be loaded, serve the default one
if(!$bytesRead)
    readfile("img/index.jpg");

?>