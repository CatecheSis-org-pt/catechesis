<?php
require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/DataValidationUtils.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\DataValidationUtils;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;


// Start a secure session if none is running
Authenticator::startSecureSession();

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    //Check if user is authenticated
    if(!Authenticator::isAppLoggedIn())
    {
        echo("<strong>Erro!</strong> Não foi possível guardar a catequese porque a sua sessão no CatecheSis expirou. Por favor volte a autenticar-se.");
        die();
    }

    $data = NULL;
    $catecismo = NULL;
    $turma = NULL;
    $conteudo = NULL;

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
    if ($_POST['conteudo'])
        $conteudo = $_POST['conteudo']; //Utils::sanitizeInput($_POST['conteudo']);


    if (!$data || !DataValidationUtils::validateDate($data))
    {
        echo("<strong>Erro!</strong> A data é inválida.");
        die();
    } else if (!(($catecismo >= 1 && $catecismo <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))) || $catecismo == -1)) {
        echo("<strong>Erro!</strong> O catecismo é inválido.");
        die();
    }

    //Validar se o utilizador tem permissoes para escrever neste catecismo
    if(!Authenticator::isAdmin() && !group_belongs_to_catechist(Utils::computeCatecheticalYear($data), $catecismo, $turma, Authenticator::getUsername()))
    {
        echo("<strong>Erro!</strong> Não tem permissões para modificar este catecismo/grupo.");
        die();
    }


    //Guardar/atualizar conteudo da sessao
    try
    {
        $db = new PdoDatabaseManager();
        if($db->postVirtualCatechesisContent($conteudo, Authenticator::getUsername(), $data, $catecismo, $turma))
            echo("OK");
        else
        {
            echo("<strong>Erro!</strong> Falha ao guardar o conteudo da sessão.");
            die();
        }
    }
    catch(Exception $e)
    {
        echo("<strong>Erro!</strong> " . $e->getMessage() );
        die();
    }
}
else
{
    header("Location: " . constant('CATECHESIS_BASE_URL') . "/index.php"); // Redirect browser
    exit();
}
?>