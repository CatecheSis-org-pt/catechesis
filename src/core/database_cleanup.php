<?php

require_once(__DIR__ . '/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../authentication/ulogin/config/all.inc.php');
require_once(__DIR__ . '/../authentication/ulogin/main.inc.php');
require_once(__DIR__ . '/../authentication/Authenticator.php');
require_once(__DIR__ . '/PdoDatabaseManager.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;

/**
 * Cleans up the CatecheSis and ulogin logs, if the maximum number of records configured to keep is exceeded.
 * Maintains the most recent log entries.
 */
function database_cleanup()
{
    /************************************************************************************************/
    /*			CONFIGURATION					      												*/
    /*									      														*/
        $db_keep_max_records = 20000;	//Max number of records to keep in CatecheSis log
        $db_cleanup_threshold = 25000;	//Perform cleanup when the number of records exceeds this
    /*																								*/
    /*																								*/
    /************************************************************************************************/

    if (!sses_running())
        sses_start();

	//Only administrators may initiate the log cleanup;
	if(!Authenticator::isAdmin())
		return;

	$db = new PdoDatabaseManager();

	try
	{
		$logEntries = $db->getCatechesisLog();
		$currentNumberOfRecords = isset($logEntries) ? count($logEntries) : 0;

		if ($currentNumberOfRecords > $db_cleanup_threshold) // Cleanup is necessary
		{
			$oldestLSN = $db->getOldestLSNtoKeep($db_keep_max_records);

			if (isset($oldestLSN))
			{
				if ($db->deleteLogEntriesOlderThan($oldestLSN))
				{
					writeLogEntry("[Automática] Limpeza do log do CatecheSis. Eliminados registos com LSN anterior a " . $oldestLSN . ". Os " . $db_keep_max_records . " registos mais recentes foram mantidos.");

					//Fazer tambem limpeza do ulogin:

					// Limit size of log by cleaning it
					ulLog::Clean();

					// Clean up expired sessions of the default storage engine set in the configuration
					$SessionStoreClass = UL_SESSION_BACKEND;
					$SessionStore = new $SessionStoreClass();
					$SessionStore->gc();

					// Remove expired nonces
					ulPdoNonceStore::Clean();
				}
				else
				{
					writeLogEntry("Tentativa falhada de limpeza do log.");
				}
			}
		}
	}
	catch (Exception $e)
	{
		writeLogEntry("Tentativa falhada de limpeza do log.");
	}

	$db = null;
}
?>