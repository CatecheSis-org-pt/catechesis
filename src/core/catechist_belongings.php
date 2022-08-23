<?php

require_once(__DIR__ . '/PdoDatabaseManager.php');
require_once(__DIR__ . '/Configurator.php');
require_once(__DIR__ . '/Utils.php');

use catechesis\PdoDatabaseManager;
use catechesis\Configurator;
use catechesis\Utils;


/**
 * Checks if a catechumen belongs to a catechist's group in the current catechetical year.
 * @param int $cid
 * @param string $username
 * @return bool
 */
function catechumen_belongs_to_catechist(int $cid, string $username)
{
	$res = false;
	$catecheticalYear = Utils::currentCatecheticalYear();
	$db = new PdoDatabaseManager();

	try
	{
		$res = $db->checkIfCatechumenBelongsToCatechist($cid, $username, $catecheticalYear);
	}
	catch(Exception $e)
	{
		return false;
	}
	$db = null;

	return $res;
}


/**
 * Checks if a catechumen teaches a particular group, in the given catechetical year.
 * @param int $catecheticalYear
 * @param int $catechism
 * @param string $group
 * @param string $username
 * @return bool
 */
function group_belongs_to_catechist(int $catecheticalYear, int $catechism, string $group, string $username)
{
	$group = Utils::sanitizeInput($group);
	
	if($catechism < 1 || $catechism > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
		return false;

	$res = false;
	$db = new PdoDatabaseManager();

	try
	{
		$groupCatechists = $db->getGroupCatechists($catecheticalYear, $catechism, $group);

		foreach($groupCatechists as $catechist)
		{
			if($catechist['username'] == $username)
			{
				$res = true;
				break;
			}
		}
	}
	catch(Exception $e)
	{
		return false;
	}
	$db = null;

	return $res;
}

?>
