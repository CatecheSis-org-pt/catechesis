<?php

require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../core/catechist_belongings.php');
require_once(__DIR__ . '/../authentication/Authenticator.php');
require_once(__DIR__ . '/../core/Configurator.php');

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\Configurator;


function return_json($string)
{
    $res = array();
    $res['status_msg'] = $string;
    echo(json_encode($res));
}


// Start a secure session if none is running
Authenticator::startSecureSession();

if(!Authenticator::isAppLoggedIn())
    return_json("Nao autorizado");


$data_sessao = date('d-m-Y', strtotime('today'));
$catecismo = null;
$turma = null;

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
    return_json("Catecismo inválido");
}




if(Authenticator::isAdmin()|| group_belongs_to_catechist(Utils::computeCatecheticalYear($data_sessao), $catecismo, $turma, Authenticator::getUsername()))
{
    try
    {
        $db = new PdoDatabaseManager();
        if($db->closeVirtualCatechesisRoom($data_sessao, $catecismo, $turma))
            return_json("OK");
        else
            return_json("Falha ao encerrar sala.");
    }
    catch(Exception $e)
    {
        return_json("Falha ao encerrar sala.");
    }
}
else
    return_json("Nao autorizado");
?>