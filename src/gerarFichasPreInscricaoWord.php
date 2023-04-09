<?php

/** Error reporting */
//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . "/core/domain/Locale.php");
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
use core\domain\Sacraments;
use PhpOffice\PhpWord\Settings;
use core\domain\Locale;


Settings::loadConfig();
Settings::setPdfRendererPath(__DIR__ . '/core/document_generators/libraries/PDF/dompdf-0.6.1/');
Settings::setPdfRendererName('DomPDF');
	

$db = new PdoDatabaseManager();

$document = new \PhpOffice\PhpWord\TemplateProcessor(__DIR__ . '/core/document_generators/FichaRenovaçãoIterable.docx');


$cids = $_REQUEST['cids'];
$catecismo = intval(Utils::sanitizeInput($_REQUEST['catecismo']));
$turma = Utils::sanitizeInput($_REQUEST['turma']);

$outcome = $document->cloneBlock('CLONEME', sizeof($cids));


$i = 1;
foreach($cids as $cid)
{
    $cid = intval(Utils::sanitizeInput($cid));

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

        $enc_edu = null;
        $nome_enc_edu = null;
        $prof_enc_edu = null;

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

        $baptizado = false;
        $comunhao = false;


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






            //Obter baptismo
            try
            {
                $baptism = $db->getCatechumenSacramentRecord(Sacraments::BAPTISM, $cid);

                if($baptism)
                {
                    $baptizado = true;
                    $data_baptismo = Utils::sanitizeOutput($baptism['data']);
                    $paroquia_baptismo = Utils::sanitizeOutput($baptism['paroquia']);
                }
            }
            catch(Exception $e)
            {
                Utils::error($e->getMessage());
            }




            //Obter primeira comunhao
            try
            {
                $communion = $db->getCatechumenSacramentRecord(Sacraments::FIRST_COMMUNION, $cid);

                if($communion)
                {
                    $comunhao = true;
                    $data_comunhao = Utils::sanitizeOutput($communion['data']);
                    $paroquia_comunhao = Utils::sanitizeOutput($communion['paroquia']);
                }
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



            //Ajustes

            if($fid_ee==$fid_pai)
            {
                $enc_edu = "Pai";
                $nome_enc_edu = "";
                $prof_enc_edu = "";
            }
            else if($fid_ee==$fid_mae)
            {
                $enc_edu = "Mãe";
                $nome_enc_edu = "";
                $prof_enc_edu = "";
            }
            else
            {
                $enc_edu = "Outro";
                $nome_enc_edu = $nome_ee;
                $prof_enc_edu = $prof_ee;
            }


            // Preencher ficha

            $grupo_anterior = $catecismo . 'º ' . $turma;

            $document->setValue('parish_name', Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME));
            $document->setValue('ano_catequetico', Utils::formatCatecheticalYear(Utils::currentCatecheticalYear() +10001));

            $document->setValue('grupo_anterior_' . $i, $grupo_anterior);
            $document->setValue('nome_' . $i, $nome);
            $document->setValue('data_nasc_' . $i, date( "d-m-Y", strtotime($data_nasc)));
            $document->setValue('local_nasc_' . $i, $local_nasc);
            $document->setValue('idade_' . $i, date_diff(date_create($data_nasc), date_create('today'))->y);
            $document->setValue('irmaos_' . $i, $num_irmaos);
            $document->setValue('morada_' . $i, $morada);
            $document->setValue('cod_postal_' . $i, $codigo_postal);

            if($escuteiro==1)
                $document->setValue('escuteiro_' . $i, 'Sim');
            else
                $document->setValue('escuteiro_' . $i, 'Não');

            if($baptizado)
                $document->setValue('batismo_' . $i, 'Sim');
            else
                $document->setValue('batismo_' . $i, 'Não');

            if($comunhao)
                $document->setValue('comunhao_' . $i, 'Sim');
            else
                $document->setValue('comunhao_' . $i, 'Não');

            if($telefone && $telefone!="" && $telefone!=0)
                 $document->setValue('telefone_' . $i, $telefone);
            else
                $document->setValue('telefone_' . $i, "_________");

            if($telemovel && $telemovel!="")
                 $document->setValue('telemóvel_' . $i, $telemovel);
            else
                $document->setValue('telemóvel_' . $i, '_________');

            if($email && $email!="")
                $document->setValue('email_' . $i, $email);
            else
                $document->setValue('email_' . $i, '_______________________________________');

            if($nome_pai && $nome_pai!="")
            {
                 $document->setValue('nome_pai_' . $i, str_pad($nome_pai, 50));
                 $document->setValue('prof_pai_' . $i, $prof_pai);
            }
            else
            {
                $document->setValue('nome_pai_' . $i, str_pad('', 50));
                $document->setValue('prof_pai_' . $i, '');
            }

            if($nome_mae && $nome_mae!="")
            {

                 $document->setValue('nome_mae_' . $i, str_pad($nome_mae, 50));
                 $document->setValue('prof_mae_' . $i, $prof_mae);
            }
            else
            {
                $document->setValue('nome_mae_' . $i, str_pad('', 50));
                $document->setValue('prof_mae_' . $i, '');
            }

            if($fid_ee!=$fid_pai && $fid_ee!=$fid_mae)
            {
                $document->setValue('enc_edu_quem_' . $i, $enc_edu_quem);
                $document->setValue('enc_edu_opt_' . $i, "Nome: " . $nome_enc_edu . " Profissão: " . $prof_enc_edu);
            }
            else
            {
                $document->setValue('enc_edu_quem_' . $i, $enc_edu);
                $document->setValue('enc_edu_opt_' . $i, '');
            }

            if($casados=="Sim")
            {

                if($casados_como=="uniao de facto")
                    $casados_como = "união de facto";

                $document->setValue('casados_' . $i, "Sim, " . $casados_como . "");
            }
            else
                $document->setValue('casados_' . $i, "Não");


            if($autorizacao_fotos==1)
                $document->setValue('autorizou_foto_' . $i, 'X');
            else
                $document->setValue('autorizou_foto_' . $i, '');


            // Custom parish footer
            $document->setValue('parish_footer', Utils::convertHtmlMarkupToWord(Configurator::getConfigurationValueOrDefault( Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER)));

            //Set parish logo
            $TEMPLATE_LOGO_WIDTH = 308;     // Logo size (in pixels). This must match the image used in the .docx template file.
            $TEMPLATE_LOGO_HEIGHT = 220;
            $tempLogoFile = UserData::getTempFolder() . "/" . Utils::secureRandomString() . '.png';
            Utils::resizeLetterbox(UserData::getParishLogoFile(), $tempLogoFile,  $TEMPLATE_LOGO_WIDTH,  $TEMPLATE_LOGO_HEIGHT);
            $document->save_image('image1.png', $tempLogoFile,$document);
        }
        else
        {
            Utils::error("Falha ao tentar aceder à ficha do catequizando.");
        }

    }
    else
    {
        Utils::error("ID do catequizando invalido.");
    }

    $i = $i + 1;
}




//Libertar recursos
$result = NULL;



$filename = "Pre-Inscricoes";
$document->saveAs(UserData::getTempFolder() . '/' . $filename . '.docx');



// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-word');
$fsize = filesize(UserData::getTempFolder() . '/' . $filename . '.docx');
header("Content-Length: ".$fsize);
header('Content-Disposition: attachment; filename="' . $filename . '.docx"');
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
readfile(UserData::getTempFolder() . '/' . $filename . '.docx');

unlink(UserData::getTempFolder() . '/' . $filename . '.docx'); //Delete temporary file
unlink($tempLogoFile);

?>