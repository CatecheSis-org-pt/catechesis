<?php
	
require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/Utils.php");
require_once(__DIR__ . "/core/UserData.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\UserData;
use core\domain\Sacraments;


// Start a secure session if none is running
Authenticator::startSecureSession();

if(!Authenticator::isAppLoggedIn())
{
    exit();
}




if($_REQUEST['cid'] && $_REQUEST['cid']!="" && $_REQUEST['sacramento'] && $_REQUEST['sacramento']!="" )
{
    $cid = intval(Utils::sanitizeInput($_REQUEST['cid']));
    $sacramento = Utils::sanitizeInput($_REQUEST['sacramento']);
    $sacramentType = null;
    $sacramentoExt = null;
    $catequizando = null;
    $ficheiro = null;

    $db = new PdoDatabaseManager();


    if(!Authenticator::isAdmin() && !catechumen_belongs_to_catechist($cid, Authenticator::getUsername()))
    {
        //Nao tem permissoes
        echo("Não tem permissões para aceder a este recurso.");
        exit();
    }

    $sacramentType = Sacraments::sacramentFromString($sacramento);
    $sacramentoExt = Sacraments::toInternalString($sacramentType);
    if(is_null($sacramentType))
    {
        Utils::error("Sacramento desconhecido.");
        exit();
    }


    //Obter nome do catequizando
    try
    {
        $catechumen = $db->getCatechumenById($cid);
        $catequizando = $catechumen['nome'];

        $sacramentRecord = $db->getCatechumenSacramentRecord($sacramentType, $cid);
        $ficheiro = $sacramentRecord['comprovativo'];
    }
    catch(Exception $e)
    {
        exit();
    }



    $nome_externo = $sacramentoExt . " ". $catequizando . ".pdf";
    header('Content-type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nome_externo . '"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0

    readfile(UserData::getUploadDocumentsFolder() . '/' . $ficheiro);
}
else
    exit();

?>