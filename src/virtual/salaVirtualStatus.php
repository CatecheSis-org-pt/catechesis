<?php

require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../core/Configurator.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../authentication/Authenticator.php');
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;


// Start a secure session if none is running
Authenticator::startSecureSession();



$data_sessao = null;
$catecismo = null;
$turma = null;
$turmas_validas = array();
$parametrosInvalidos = false;


if($_REQUEST['dataSessao'])
{
    $dataS = Utils::sanitizeInput($_REQUEST['dataSessao']);
    $data_sessao = date('d-m-Y', strtotime($dataS));
    //$data_sessao = date('d-m-Y', strtotime('today'));
}
if($_REQUEST['catecismo'])
{
    $catecismo = intval($_REQUEST['catecismo']);
}
if($_REQUEST['turma'])
{
    $turma = Utils::sanitizeInput($_REQUEST['turma']);
}

if(!isset($catecismo) || $catecismo < 1 || $catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
{
    $parametrosInvalidos = true;
}



if(!$parametrosInvalidos)
{
    //Get videocall status
    $sala = null;
    try
    {
        $db = new PdoDatabaseManager();
        $sala = $db->getVirtualCatechesisRoom($data_sessao, $catecismo, $turma);
        $db = null;
    }
    catch (Exception $e)
    {
        $parametrosInvalidos = true;
    }

    if (!$parametrosInvalidos && $sala)
    {
        $res = array();
        $res["room_status"] = ($sala['aberta'] == 1) ? "open" : "closed";
        $res["URL"] = $sala['url'];
        $res['last_modified_user'] = $sala['ultima_modificacao_user'];
        $res['last_modified_user_name'] = $sala['ultima_modificacao_nome'];
        $res['last_modified_timestamp'] = $sala['ultima_modificacao_timestamp'];

        echo(json_encode($res));
    }
    else
    {
        $res = array();
        $res["room_status"] = "closed";

        echo(json_encode($res));
    }
}
else
{
    //ERROR
    $res = array();
    $res["room_status"] = "closed";

    echo(json_encode($res));
}

?>