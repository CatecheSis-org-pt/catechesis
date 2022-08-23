<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/DataValidationUtils.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");

use catechesis\DataValidationUtils;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;


/* =================================== CONFIG PARAMETERS ======================================================*/
    $KEEP_ALIVE_THRESHOLD = 30;     //Users are considered active until 30 seconds after the last timestamp
/* ============================================================================================================*/

//Funcao para criar link de retorno em caso de erro





$data = NULL;
$catecismo = NULL;
$turma = NULL;

if ($_SERVER["REQUEST_METHOD"] == "POST")
{

    if ($_POST['data'])
        $data = Utils::sanitizeInput($_POST['data']);
    if ($_POST['catecismo'])
    {
        if($_POST['catecismo']=="0")    //String "0" is considered empty
            $catecismo = -1;            //Default session for all catechisms
        else
            $catecismo = intval($_POST['catecismo']);
    }
    if ($_POST['turma'])
    {
        $turma = Utils::sanitizeInput($_POST['turma']);
        if($turma == "_")
            $turma = null;             //Default session for all groups
    }


    if (!$data || !DataValidationUtils::validateDate($data))
    {
        echo("<strong>Erro!</strong> A data é inválida.");
        die();
    } else if (!(($catecismo >= 1 && $catecismo <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))) || $catecismo == -1)) {
        echo("<strong>Erro!</strong> O catecismo é inválido.");
        die();
    }


    // Check if user has permissions to write in this catechism/group
    if(!Authenticator::isAdmin() && !group_belongs_to_catechist(Utils::computeCatecheticalYear($data), $catecismo, $turma, Authenticator::getUsername()))
    {
        echo("<strong>Erro!</strong> Não tem permissões para modificar este catecismo/grupo.");
        die();
    }


    // Lock session
    try
    {
        $db = new PdoDatabaseManager();

        //Insert session lock
        if(!$db->insertLockInVirtualCatechesis(Authenticator::getUsername(), $data, $catecismo, $turma))
        {
            echo("<p><strong>Erro!</strong> Falha ao atualizar o lock da sessão. [1]</p>");
        }

        //Return list of users watching the same session
        echo(json_encode($db->getListOfVirtualCatechesisObservers($data, $KEEP_ALIVE_THRESHOLD, $catecismo, $turma, Authenticator::getUsername())));
    }
    catch (Exception $e)
    {
        //echo $e->getMessage();
        echo("<strong>Erro!</strong> " . $e->getMessage());
        die();
    }
}
?>