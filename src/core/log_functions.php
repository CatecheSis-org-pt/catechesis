<?php

require_once(__DIR__ . '/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../authentication/Authenticator.php');
require_once(__DIR__ . '/PdoDatabaseManager.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;


/**
 * Writes a log entry in the CatecheSis actions logs.
 * @param $accao
 * @return false
 */
function writeLogEntry($accao)
{
	try
	{
		$db = new PdoDatabaseManager();
		$lsn = $db->addLogEntry(Authenticator::getUsername(), $accao);
		$db = null;

		return $lsn;
	}
	catch(Exception $e)
	{
		return false;
	}
}


/**
 * Adds an entry log to the CatecheSis actions log and updates the last modification information of a catechumen's file
 * to point to this new log entry.
 * If the parameter $affectsSiblings is true, the files of the catechumens that share the same responsible/father/mother
 * of the provided catechumen are also updated to point to the same log entry. This is useful for actions that may affect
 * multiple catechumens (for example, changing an address or phone number).
 * @param $cid
 * @param $accao
 * @param false $affectsSiblings
 * @return false
 */
function catechumenFileLog($cid, $accao, $affectsSiblings=false)
{
	$lsn = writeLogEntry($accao);
	
	if($lsn && $cid && intval($cid)>0)
	{
		$db = new PdoDatabaseManager();

		try
		{
			if($db->updateCatechumenFileLog($cid, $lsn))
			{
				if($affectsSiblings)
				{
					//Get all catechumens affected by this change
					$siblings = $db->getCatechumenSiblings($cid);

					//Mark all catechumen files as changed
					foreach($siblings as $catechumen)
					{
						$db->updateCatechumenFileLog($catechumen['cid'], $lsn);
					}
				}
				$db = null;

				return $lsn;
			}
			else
			{
				$db = null;
				return false;
			}
		}
		catch(Exception $e)
		{
			$db = null;
			return false;
		}
	}
	else
		return false;
}


/**
 * Adds an entry log to the CatecheSis actions log and updates the last modification information of a catechumen's archive
 * to point to this new log entry.
 * @param $cid
 * @param $accao
 * @return false
 */
function catechumenArchiveLog($cid, $accao)
{
	$lsn = writeLogEntry($accao);

	if($lsn && $cid>0)
	{
		$db = new PdoDatabaseManager();

		try
		{
			if ($db->updateCatechumenArchiveLog($cid, $lsn))
			{
				$db = null;
				return $lsn;
			}
			else
			{
				$db = null;
				return false;
			}
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}


/**
 * Adds an entry log to the CatecheSis actions log and updates the last modification information of a catechumen's authorizations
 * to point to this new log entry.
 * @param $cid
 * @param $accao
 * @return false
 */
function catechumenAuthorizationsLog($cid, $accao)
{
	$lsn = writeLogEntry($accao);

	if($lsn && $cid>0)
	{
		$db = new PdoDatabaseManager();

		try
		{
			if ($db->updateCatechumenAuthorizationsLog($cid, $lsn))
			{
				$db = null;
				return $lsn;
			}
			else
			{
				$db = null;
				return false;
			}
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}

?>
