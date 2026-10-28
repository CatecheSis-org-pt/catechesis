<?php

require_once(__DIR__ . '/../gui/widgets/configuration_panels/UnsupportedPhpVersionPanel/UnsupportedPhpVersionPanelWidget.php');

use catechesis\gui\UnsupportedPhpVersionPanelWidget;

function check_unsupported_php_version()
{
    $panel = new UnsupportedPhpVersionPanelWidget();
    if ($panel->isUnsupported())
    {
        $_SESSION['IS_PHP_VERSION_UNSUPPORTED'] = true;
        $_SESSION['SHOW_UNSUPPORTED_PHP_WARNING'] = true;
    }
    else
    {
        $_SESSION['IS_PHP_VERSION_UNSUPPORTED'] = false;
        $_SESSION['SHOW_UNSUPPORTED_PHP_WARNING'] = false;
    }
}
