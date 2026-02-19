<?php


// Functions to compute several statistics, to show on the UI or support decisions (e.g. decision support)

require_once(__DIR__ . '/PdoDatabaseManager.php');
require_once(__DIR__ . '/DataValidationUtils.php');
require_once(__DIR__ . '/Utils.php');
require_once(__DIR__ . '/Configurator.php');

use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\DataValidationUtils;
use catechesis\Utils;



/**
 * Computes the attendance percentage of a catechumen in the current catechetical year.
 * Percentage is (sessions attended / total sessions of their group in the current year) * 100.
 * Returns 0 if the catechumen is not enrolled or there are no sessions.
 * @param int $cid
 * @return float
 */
function getCurrentYearAttendancePercentage(int $cid)
{
    try
    {
        if(!class_exists('\\catechesis\\PdoDatabaseManager'))
            require_once(__DIR__ . '/PdoDatabaseManager.php');

        $db = new PdoDatabaseManager();
        $currentYear = intval(self::currentCatecheticalYear());

        $groupInfo = $db->getCatechumenCurrentCatechesisGroup($cid, $currentYear);
        if(!$groupInfo)
            return 0.0;

        $catechism = intval($groupInfo['ano_catecismo']);
        $group = $groupInfo['turma'];

        $sessions = $db->getCatechesisSessions($currentYear, $catechism, $group);
        $totalSessions = is_array($sessions) ? count($sessions) : 0;
        if($totalSessions == 0)
            return 100.0;

        $attendance = $db->getCatechumenAttendanceForGroup($currentYear, $catechism, $group, $cid);
        $attended = 0;
        if(is_array($attendance))
        {
            foreach($attendance as $row)
            {
                if(isset($row['presenca']) && intval($row['presenca']) === 1)
                    $attended++;
            }
        }

        return round(($attended / $totalSessions) * 100.0, 2);
    }
    catch (Exception $e)
    {
        return 0.0;
    }
}

?>