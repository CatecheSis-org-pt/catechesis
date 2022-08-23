<?php
/**
 * Checks if maintenance mode is enabled and, in such case, redirects the visitor to the error 500 page,
 * stating that CatecheSis is temporarily unavailable.
 */

require_once(__DIR__ . '/Configurator.php');
use catechesis\Configurator;


try
{
    $maintenanceMode = Configurator::getConfigurationValueOrDefault(Configurator::KEY_MAINTENANCE_MODE);
    if($maintenanceMode)
    {
        require_once(__DIR__ . '/../erro500.php');
        die();
    }
}
catch(Exception $e)
{
    require_once(__DIR__ . '/../erro500.php');
    die();
}
