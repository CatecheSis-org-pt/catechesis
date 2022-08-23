<?php

require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . "/gui/widgets/Navbar/MainNavbar.php");
require_once(__DIR__ . "/gui/widgets/Navbar/MinimalNavbar.php");

use catechesis\Authenticator;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MinimalNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;

// Start a secure session if none is running
Authenticator::startSecureSession();

// Create the widgets manager
$pageUI = new WidgetManager();

// Check if the user is authenticated, to decide which menu to render
$menu = null;
if(Authenticator::isAppLoggedIn())
{
    // Complete menu for authenticated users
    $menu = new MainNavbar(null, MENU_OPTION::NONE);
    $pageUI->addWidget($menu);
}
else
{
    // Minimal menu for unauthenticated users
    $menu = new MinimalNavbar(null);
    $pageUI->addWidget($menu);
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>CatecheSis - Recurso não encontrado</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  
  
  <style>
  	@media print
	{    
	    .no-print, .no-print *
	    {
		display: none !important;
	    }
	    
	    
	    a[href]:after {
		    content: none;
		  }
		  
	    /*@page {
		    size: 297mm 210mm;*/ /* landscape */
		    /* you can also specify margins here: */
		    /*margin: 35mm;*/
		    /*margin-right: 45mm;*/ /* for compatibility with both A4 and Letter */
		 /* }*/
		  
	}
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
	}


    #error_container {
        background-image: url("img/Erro404.jpg");
        background-color: #017188;
        /*background-position: center;*/
        background-size: cover;
        /*width: 100%;
        //height: auto;*/
        background-repeat: no-repeat;

        position: absolute;
        width:100%;
        height: 100%;
        display:flex;
        flex-direction: column;
    }


    .navbar
    {
        /* Adjust navbar to seamless integrate with 404 art */

        margin-bottom: 0;  /* Remove white space below the navbar */
        border-color: rgb(0, 143, 207);
    }

  </style>

</head>
<body>

<?php
$menu->renderHTML();

if(Authenticator::isAppLoggedIn())
{
    //Compensate for the margin-bottom introduced by the MainNavbar, so that there is no white space between the nav bar and the art.
    ?>
    <div style="margin-top: -100px"></div>
    <?php
}
?>

<div class="container no-print" id="error_container" >
</div>

<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
</body>
</html>