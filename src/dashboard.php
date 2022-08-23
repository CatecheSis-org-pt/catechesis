<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/log_functions.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . "/gui/widgets/Navbar/MainNavbar.php");
require_once(__DIR__ . "/gui/widgets/MyGroups/MyGroupsWidget.php");
require_once(__DIR__ . "/gui/widgets/CatechumensProblemsReportSummary/CatechumensProblemsReportSummaryWidget.php");
require_once(__DIR__ . "/gui/widgets/QuickAccess/QuickAccessWidget.php");

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\MyGroupsWidget;
use catechesis\gui\CatechumensProblemsReportSummaryWidget;
use catechesis\gui\QuickAccessWidget;

$db = new PdoDatabaseManager(); // Catechesis database object


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::HOME);
$pageUI->addWidget($menu);

$catechistGroupsPanel = new MyGroupsWidget();
$pageUI->addWidget($catechistGroupsPanel);

$problemsReport = new CatechumensProblemsReportSummaryWidget();
$pageUI->addWidget($problemsReport);

$quickAccess = new QuickAccessWidget();
$pageUI->addWidget($quickAccess);


?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Início</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/dashboard.css">

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
	}
	
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
	}
  </style>
</head>
<body>

<?php

$menu->renderHTML();

?>


<div class="container" id="contentor">

    <div style="margin-bottom: 60px;"></div>

    <div class="greeting-block">
	    <h2> <?= Utils::greeting( Utils::firstName(Authenticator::getUserFullName()) ); ?></h2>
    </div>

    <div style="margin-bottom: 60px;"></div>

    <div class="row">
  	    <?php
        //Render catechist groups panel
        $catechistGroupsPanel->addCustomClass("col-sm-8");
        $catechistGroupsPanel->renderHTML();

        //Render problems mini report
        $problemsReport->renderHTML();
        ?>

        <div class="clearfix" style="margin-bottom: 40px"></div>

        <?php
        //Render quick access panel
        $quickAccess->renderHTML();
        ?>

        <div class="clearfix"></div>

    </div>

    <div class="clearfix"></div>


  
</div>

<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/rowlink.js"></script>

</body>
</html>