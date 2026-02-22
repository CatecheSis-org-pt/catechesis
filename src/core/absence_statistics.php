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
 * Computes the attendance percentage of a catechumen in a given catechetical year and group.
 * Percentage is (sessions attended / total sessions of their group in the year) * 100.
 * Returns 100.0 if there are no sessions, and 0.0 if the catechumen is not enrolled or an error occurs.
 * @param int $cid
 * @param int $catecheticalYear
 * @param int|null $catechism If null, it will be looked up in the database.
 * @param string|null $group If null, it will be looked up in the database.
 * @return array ['percentage' => float, 'attended' => int, 'total' => int]
 */
function getCatechumenAttendanceStats(int $cid, int $catecheticalYear, int $catechism = null, string $group = null)
{
    try
    {
        $db = new PdoDatabaseManager();

        if ($catechism === null || $group === null) {
            $groupInfo = $db->getCatechumenCurrentCatechesisGroup($cid, $catecheticalYear);
            if (!$groupInfo) {
                return ['percentage' => 0.0, 'attended' => 0, 'total' => 0];
            }
            $catechism = intval($groupInfo['ano_catecismo']);
            $group = $groupInfo['turma'];
        }

        $attendance = $db->getCatechumenAttendanceForGroup($catecheticalYear, $catechism, $group, $cid);
        $attended = 0;
        $totalSessions = 0;
        if(is_array($attendance))
        {
            foreach($attendance as $row)
            {
                if(isset($row['presenca']) && $row['presenca'] !== null)
                {
                    $totalSessions++;
                    if(intval($row['presenca']) === 1)
                        $attended++;
                }
            }
        }

        if($totalSessions == 0)
            return ['percentage' => 100.0, 'attended' => 0, 'total' => 0];

        return [
            'percentage' => round(($attended / $totalSessions) * 100.0, 1),
            'attended' => $attended,
            'total' => $totalSessions
        ];
    }
    catch (Exception $e)
    {
        return ['percentage' => 0.0, 'attended' => 0, 'total' => 0];
    }
}


/**
 * Computes the attendance percentage of a catechumen in the current catechetical year.
 * Percentage is (sessions attended / total sessions of their group in the current year) * 100.
 * Returns 0 if the catechumen is not enrolled or there are no sessions.
 * @param int $cid
 * @return float
 */
function getCurrentYearCatechumenAttendancePercentage(int $cid)
{
    $stats = getCatechumenAttendanceStats($cid, intval(Utils::currentCatecheticalYear()));
    return $stats['percentage'];
}

?>