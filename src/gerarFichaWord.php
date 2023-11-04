<?php

/** Error reporting */
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . "/core/domain/Locale.php");
//require_once(__DIR__ . "/core/document_generators/libraries/vendor/autoload.php");

require_once(__DIR__ . '/core/document_generators/libraries/PHPWord-develop/src/PhpOffice/PhpWord/Settings.php');
require_once(__DIR__ . '/core/document_generators/libraries/PHPWord-develop/src/PhpOffice/PhpWord/PhpWord.php');
require_once(__DIR__ . '/core/document_generators/libraries/ZendFramework-2.4.11/library/Zend/Stdlib/StringUtils.php');
require_once(__DIR__ . '/core/document_generators/libraries/PHPWord-develop/src/PhpOffice/PhpWord/Shared/ZipArchive.php');
require_once(__DIR__ . '/core/document_generators/libraries/PHPWord-develop/src/PhpOffice/PhpWord/TemplateProcessor.php');


use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Utils;
use catechesis\UserData;
use catechesis\Configurator;
use PhpOffice\PhpWord\Settings;
use core\domain\Locale;


Settings::loadConfig();


$db = new PdoDatabaseManager();

$cid = intval(Utils::sanitizeInput($_REQUEST['cid']));



if($cid && $cid>0)
{

    if(!Authenticator::isAdmin() && !catechumen_belongs_to_catechist($cid, Authenticator::getUsername()))
    {
        Utils::error("Não tem permissões para aceder aos dados deste catequizando.");
    }



    $result = NULL;

    $nome = NULL;
    $data_nasc = NULL;
    $local_nasc = NULL;
    $num_irmaos = NULL;
    $escuteiro = NULL;
    $autorizacao_fotos = NULL;
    $fid_pai = NULL;
    $fid_mae = NULL;
    $fid_ee = NULL;
    $enc_edu_quem = NULL;
    $foto = NULL;

    $nome_pai = NULL;
    $prof_pai = NULL;

    $nome_mae = NULL;
    $prof_mae = NULL;

    $casados = "Não";
    $casados_como = NULL;

    $nome_ee = NULL;
    $prof_ee = NULL;
    $morada = NULL;
    $codigo_postal = NULL;
    $telefone = NULL;
    $telemovel = NULL;
    $email = NULL;

    $criado_por = NULL;
    $criado_em = NULL;
    $lastLSN_ficha = NULL;



    //Obter dados do catequizando
    $catechumen = null;
    try
    {
        $catechumen = $db->getCatechumenById($cid);

        $nome = Utils::sanitizeOutput($catechumen['nome']);
        $data_nasc = Utils::sanitizeOutput($catechumen['data_nasc']);
        $local_nasc = Utils::sanitizeOutput($catechumen['local_nasc']);
        $num_irmaos = intval($catechumen['num_irmaos']);
        $escuteiro = Utils::sanitizeOutput($catechumen['escuteiro']);
        $autorizacao_fotos = Utils::sanitizeOutput($catechumen['autorizou_fotos']);
        $fid_pai = (isset($catechumen['pai']))? intval($catechumen['pai']) : null;
        $fid_mae = (isset($catechumen['mae']))? intval($catechumen['mae']) : null;
        $fid_ee = intval($catechumen['enc_edu']);
        $enc_edu_quem = Utils::sanitizeOutput($catechumen['enc_edu_quem']);
        $foto = Utils::sanitizeOutput($catechumen['foto']);
        $criado_por = Utils::sanitizeOutput($catechumen['criado_por_nome']);
        $criado_em = Utils::sanitizeOutput($catechumen['criado_em']);
        $lastLSN_ficha = intval($catechumen['lastLSN_ficha']);
    }
    catch(Exception $e)
    {
        Utils::error($e->getMessage());
    }



    if (isset($catechumen))
    {
        //Obter dados do pai
        if(!is_null($fid_pai))
        {
            try
            {
                $father = $db->getFamilyMember($fid_pai);
                $nome_pai = Utils::sanitizeOutput($father['nome']);
                $prof_pai = Utils::sanitizeOutput($father['prof']);
            }
            catch(Exception $e)
            {
                Utils::error($e->getMessage());
            }
        }




        //Obter dados da mae
        if(!is_null($fid_mae))
        {
            try
            {
                $mother = $db->getFamilyMember($fid_mae);
                $nome_mae = Utils::sanitizeOutput($mother['nome']);
                $prof_mae = Utils::sanitizeOutput($mother['prof']);
            }
            catch(Exception $e)
            {
                Utils::error($e->getMessage());
            }
        }




        //Verificar se os pais sao casados
        if(!is_null($fid_mae) && !is_null($fid_pai))
        {
            try
            {
                $marriage = $db->getMarriageInformation($fid_mae, $fid_pai);

                if($marriage)
                {
                    $casados = "Sim";
                    $casados_como = Utils::sanitizeOutput($marriage['como']);
                }
                else
                    $casados = "Não";
            }
            catch(Exception $e)
            {
                Utils::error($e->getMessage());
            }
        }




        //Obter dados do encarregado educacao
        try
        {
            $responsible = $db->getFamilyMember($fid_ee);

            $nome_ee = Utils::sanitizeOutput($responsible['nome']);
            $prof_ee = Utils::sanitizeOutput($responsible['prof']);
            $morada = Utils::sanitizeOutput($responsible['morada']);
            $codigo_postal = Utils::sanitizeOutput($responsible['cod_postal']);
            $telefone = Utils::sanitizeOutput($responsible['telefone']);
            $telemovel = Utils::sanitizeOutput($responsible['telemovel']);
            $email = Utils::sanitizeOutput($responsible['email']);
            $rgpd_ee = intval($responsible['RGPD_assinado']);

            //converter para bool
            $rgpd_ee = (!is_null($rgpd_ee) && $rgpd_ee == 1);
        }
        catch(Exception $e)
        {
            Utils::error($e->getMessage());
        }




        //Obter ultima modificacao do log
        $modificou_quem = NULL;
        $modificou_data = NULL;
        try
        {
            $fileLog = $db->getLogEntry($lastLSN_ficha);

            $modificou_quem = Utils::sanitizeOutput($fileLog['nome_modificacao']);
            $modificou_data = Utils::sanitizeOutput($fileLog['data']);
        }
        catch(Exception $e)
        {
            Utils::error($e->getMessage());
        }



        //Libertar recursos
        $result = NULL;


        //Passar dados para esta sessao

        $_SESSION['nome'] = $nome;
        $_SESSION['data_nasc_row'] = $data_nasc;
        $_SESSION['data_nasc'] = $data_nasc = date( "d-m-Y", strtotime($data_nasc));
        $_SESSION['local_nasc'] = $local_nasc;
        $_SESSION['num_irmaos'] = $num_irmaos;
        $_SESSION['escuteiro'] = $escuteiro;
        $_SESSION['autorizacao'] = $autorizacao_fotos;
        $_SESSION['fid_pai'] = $fid_pai;
        $_SESSION['fid_mae'] = $fid_mae;
        $_SESSION['fid_ee'] = $fid_ee;
        $_SESSION['enc_edu_quem'] = $_SESSION['outro_enc_edu_quem'] = $enc_edu_quem;
        $_SESSION['foto'] = $foto;

        $_SESSION['nome_pai'] = $_SESSION['pai'] = $nome_pai;
        $_SESSION['prof_pai'] = $prof_pai;

        $_SESSION['nome_mae'] = $_SESSION['mae'] = $nome_mae;
        $_SESSION['prof_mae'] = $prof_mae;

        $_SESSION['casados'] = $casados;
        $_SESSION['casados_como'] = $casados_como;

        $_SESSION['nome_ee'] = $nome_ee;
        $_SESSION['prof_ee'] = $_SESSION['prof_enc_edu'] = $prof_ee;
        $_SESSION['morada'] = $morada;
        $_SESSION['codigo_postal'] = $_SESSION['cod_postal'] = $codigo_postal;
        $_SESSION['telefone'] = $telefone;
        $_SESSION['telemovel'] = $telemovel;
        $_SESSION['email'] = $email;

        $_SESSION['cid'] = $cid;

        //Para compatibilidade com o codigo inscricao.php:
        if($fid_ee==$fid_pai)
        {
            $_SESSION['enc_edu'] = "Pai";
            $_SESSION['nome_enc_edu'] = "";
            $_SESSION['prof_enc_edu'] = "";
        }
        else if($fid_ee==$fid_mae)
        {
            $_SESSION['enc_edu'] = "Mãe";
            $_SESSION['nome_enc_edu'] = "";
            $_SESSION['prof_enc_edu'] = "";
        }
        else
        {
            $_SESSION['enc_edu'] = "Outro";
            $_SESSION['nome_enc_edu'] = $nome_ee;
            $_SESSION['prof_enc_edu'] = $prof_ee;
        }
    }
    else
    {
        Utils::error("Falha ao tentar aceder à ficha do catequizando.");
    }
}
else
{
    Utils::error("Não foi especificado nenhum catequizando para consultar.");
}




$document = new \PhpOffice\PhpWord\TemplateProcessor(__DIR__ . '/core/document_generators/TemplateFicha.docx');

$document->setValue('parish_name', Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME));
$document->setValue('ano_catequetico', Utils::formatCatecheticalYear(Utils::currentCatecheticalYear()));

$document->setValue('nome', $_SESSION['nome']);
$document->setValue('data_nasc', $_SESSION['data_nasc']);
$document->setValue('local_nasc', $_SESSION['local_nasc']);
$document->setValue('idade', date_diff(date_create($_SESSION['data_nasc_row']), date_create('today'))->y);
$document->setValue('irmaos', $_SESSION['num_irmaos']);
$document->setValue('morada', $_SESSION['morada']);
$document->setValue('cod_postal', $_SESSION['codigo_postal']);

if($_SESSION['escuteiro']==1)
    $document->setValue('escuteiro', 'Sim');
else
    $document->setValue('escuteiro', 'Não');

if($_SESSION['telefone'] && $_SESSION['telefone']!="" && $_SESSION['telefone']!=0)
     $document->setValue('telefone', $_SESSION['telefone']);
else
    $document->setValue('telefone', "         ");

if($_SESSION['telemovel'] && $_SESSION['telemovel']!="")
     $document->setValue('telemóvel', $_SESSION['telemovel']);
else
    $document->setValue('telemóvel', '         ');

if($_SESSION['email'] && $_SESSION['email']!="")
     $document->setValue('email', $_SESSION['email']);
else
    $document->setValue('email', '');

if($_SESSION['nome_pai'] && $_SESSION['nome_pai']!="")
{
     $document->setValue('nome_pai', str_pad($_SESSION['nome_pai'], 50));
     $document->setValue('prof_pai', $_SESSION['prof_pai']);
}
else
{
    $document->setValue('nome_pai', str_pad('', 50));
    $document->setValue('prof_pai', '');
}

if($_SESSION['nome_mae'] && $_SESSION['nome_mae']!="")
{

     $document->setValue('nome_mae', str_pad($_SESSION['nome_mae'], 50));
     $document->setValue('prof_mae', $_SESSION['prof_mae']);
}
else
{
    $document->setValue('nome_mae', str_pad('', 50));
    $document->setValue('prof_mae', '');
}

if($_SESSION['fid_ee']!=$_SESSION['fid_pai'] && $_SESSION['fid_ee']!=$_SESSION['fid_mae'])
{
    $document->setValue('enc_edu_quem', $_SESSION['enc_edu_quem']);
    $document->setValue('enc_edu_opt', "Nome: " . $_SESSION['nome_enc_edu'] . " Profissão: " . $_SESSION['prof_enc_edu']);
}
else
{
    $document->setValue('enc_edu_quem', $_SESSION['enc_edu']);
    $document->setValue('enc_edu_opt', '');
}

if($_SESSION['casados']=="Sim")
{
    $casados_como = $_SESSION['casados_como'];
    if($casados_como=="uniao de facto")
        $casados_como = "união de facto";

    $document->setValue('casados', "Sim, " . $casados_como . "");
}
else
    $document->setValue('casados', "Não");


if($_SESSION['autorizacao']==1)
    $document->setValue('autorizou_foto', 'X');
else
    $document->setValue('autorizou_foto', '');

// Custom parish footer
$document->setValue('parish_footer', Utils::convertHtmlMarkupToWord(Configurator::getConfigurationValueOrDefault( Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER)));


//Set parish logo
$TEMPLATE_LOGO_WIDTH = 308;     // Logo size (in pixels). This must match the image used in the .docx template file.
$TEMPLATE_LOGO_HEIGHT = 220;
$tempLogoFile = UserData::getTempFolder() . "/" . Utils::secureRandomString() . '.png';
Utils::resizeLetterbox(UserData::getParishLogoFile(), $tempLogoFile,  $TEMPLATE_LOGO_WIDTH,  $TEMPLATE_LOGO_HEIGHT);
$document->save_image('image1.png', $tempLogoFile,$document);


if($foto!=NULL && $foto!="")
{
    $image_path = UserData::getCatechumensPhotosFolder() . '/' . $foto;
    $document->save_image('image2.jpeg',$image_path,$document);
}

$document->saveAs(UserData::getTempFolder() . '/' . $nome . '.docx');


// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-word');
$fsize = filesize(UserData::getTempFolder() . '/' . $nome . '.docx');
header("Content-Length: ".$fsize);
header('Content-Disposition: attachment; filename="' . $nome . '.docx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

ob_clean();
flush();
readfile(UserData::getTempFolder() . '/' . $nome . '.docx');

unlink(UserData::getTempFolder() . '/' . $nome . '.docx'); //Apagar ficheiro temporario
unlink($tempLogoFile);
?>