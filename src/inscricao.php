<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/domain/Locale.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/core/catechist_belongings.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\UserData;
use core\domain\Locale;

// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::ENROLMENTS);
$pageUI->addWidget($menu);

$db = new PdoDatabaseManager();

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>
  	<?php
    if(isset($_REQUEST['modo']) && $_REQUEST['modo']=='editar')
        echo("Editar ficha");
    else
        echo('Inscrição na catequese');
  	?>
  </title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">
</head>
<body>


<?php
$menu->renderHTML();
?>


<div class="container">

	<?php

    $cid = null;
    $nome = "";
    $data_nasc = "";
    $local_nasc = "";
    $num_irmaos = "0";
    $morada = "";
    $cod_postal = "";
    $telefone = "";
    $telemovel = "";
    $telefone_pai = "";
    $telemovel_pai = "";
    $telefone_mae = "";
    $telemovel_mae = "";
    $telefone_enc_edu = "";
    $telemovel_enc_edu = "";
    $nif = "";
    $escuteiro = "Nao";
    $enc_edu = "Mae";
    $outro_enc_edu_quem = "";
    $nome_enc_edu = "";
    $prof_enc_edu = "";
    $pai = "";
    $prof_pai = "";
    $mae = "";
    $prof_mae = "";
    $casados = "Nao";
    $casados_como = "";
    $email = "";
    $autorizacao = "";
    $RGPD_assinado = "";
    $foto = "";
    $foto_data_base64 = "";

    if(isset($_REQUEST['modo']) && $_REQUEST['modo']=='editar')
    {
        echo("<h2>Editar ficha</h2>");

        $cid = isset($_REQUEST['cid']) ? intval(Utils::sanitizeInput($_REQUEST['cid'])) : 0;

        if(!$cid || $cid <= 0)
        {
            echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não foi especificado nenhum catequizando para editar.</div>");
            echo("</div></body></html>");
            die();
        }

        if(!Authenticator::isAdmin() && !catechumen_belongs_to_catechist($cid, Authenticator::getUsername()))
        {
            echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
            echo("</div></body></html>");
            die();
        }

        $catechumen = null;
        try
        {
            $catechumen = $db->getCatechumenById($cid);
        }
        catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            echo("</div></body></html>");
            die();
        }

        if(!$catechumen)
        {
            echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Falha ao tentar aceder à ficha do catequizando.</div>");
            echo("</div></body></html>");
            die();
        }

        $nome = Utils::sanitizeOutput($catechumen['nome']);
        $data_nasc = $catechumen['data_nasc'] ? date("d-m-Y", strtotime($catechumen['data_nasc'])) : "";
        $nif = intval($catechumen['nif']);
        if ($nif == 0) $nif = "";
        $local_nasc = Utils::sanitizeOutput($catechumen['local_nasc']);
        $num_irmaos = intval($catechumen['num_irmaos']);
        $escuteiro = Utils::sanitizeOutput($catechumen['escuteiro']);
        $autorizacao = Utils::sanitizeOutput($catechumen['autorizou_fotos']);
        $foto = Utils::sanitizeOutput($catechumen['foto']);

        $fid_pai = (isset($catechumen['pai'])) ? intval($catechumen['pai']) : null;
        $fid_mae = (isset($catechumen['mae'])) ? intval($catechumen['mae']) : null;
        $fid_ee = intval($catechumen['enc_edu']);
        $enc_edu_quem = Utils::sanitizeOutput($catechumen['enc_edu_quem']);

        if(!is_null($fid_pai))
        {
            try
            {
                $father = $db->getFamilyMember($fid_pai);
                if($father)
                {
                    $pai = Utils::sanitizeOutput($father['nome']);
                    $prof_pai = Utils::sanitizeOutput($father['prof']);
                    $telefone_pai = Utils::sanitizeOutput($father['telefone']);
                    $telemovel_pai = Utils::sanitizeOutput($father['telemovel']);
                }
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                echo("</div></body></html>");
                die();
            }
        }

        if(!is_null($fid_mae))
        {
            try
            {
                $mother = $db->getFamilyMember($fid_mae);
                if($mother)
                {
                    $mae = Utils::sanitizeOutput($mother['nome']);
                    $prof_mae = Utils::sanitizeOutput($mother['prof']);
                    $telefone_mae = Utils::sanitizeOutput($mother['telefone']);
                    $telemovel_mae = Utils::sanitizeOutput($mother['telemovel']);
                }
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                echo("</div></body></html>");
                die();
            }
        }

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
                {
                    $casados = "Não";
                }
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                echo("</div></body></html>");
                die();
            }
        }

        if($fid_ee)
        {
            try
            {
                $responsible = $db->getFamilyMember($fid_ee);
                if($responsible)
                {
                    $nome_ee = Utils::sanitizeOutput($responsible['nome']);
                    $prof_ee = Utils::sanitizeOutput($responsible['prof']);
                    $morada = Utils::sanitizeOutput($responsible['morada']);
                    $cod_postal = Utils::sanitizeOutput($responsible['cod_postal']);
                    $telefone = Utils::sanitizeOutput($responsible['telefone']);
                    $telemovel = Utils::sanitizeOutput($responsible['telemovel']);
                    $email = Utils::sanitizeOutput($responsible['email']);
                    $rgpd_val = intval($responsible['RGPD_assinado']);
                    $RGPD_assinado = (!is_null($rgpd_val) && $rgpd_val == 1);
                }
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                echo("</div></body></html>");
                die();
            }
        }

        if($fid_ee == $fid_pai && !is_null($fid_pai))
        {
            $enc_edu = "Pai";
            $nome_enc_edu = "";
            $prof_enc_edu = "";
        }
        else if($fid_ee == $fid_mae && !is_null($fid_mae))
        {
            $enc_edu = "Mae";
            $nome_enc_edu = "";
            $prof_enc_edu = "";
        }
        else
        {
            $enc_edu = "Outro";
            $outro_enc_edu_quem = $enc_edu_quem;
            $nome_enc_edu = isset($nome_ee) ? $nome_ee : "";
            $prof_enc_edu = isset($prof_ee) ? $prof_ee : "";
            $telefone_enc_edu = isset($telefone) ? $telefone : "";
            $telemovel_enc_edu = isset($telemovel) ? $telemovel : "";
        }

        if($foto != "" && file_exists(UserData::getCatechumensPhotosFolder() . '/' . $foto))
        {
            $foto_data_base64 = base64_encode(file_get_contents(UserData::getCatechumensPhotosFolder() . '/' . $foto));
        }
    }
    else
    {
        echo('<h2>Matrícula e inscrição na catequese</h2>');

        if(!Authenticator::isAdmin())
        {
            echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
            echo("</div></body></html>");
            die();
        }

        if(isset($_REQUEST['modo']) && $_REQUEST['modo']=='regresso')
        {
            $nome = isset($_SESSION['nome']) ? $_SESSION['nome'] : "";
            $data_nasc = isset($_SESSION['data_nasc']) ? $_SESSION['data_nasc'] : "";
            $local_nasc = isset($_SESSION['local_nasc']) ? $_SESSION['local_nasc'] : "";
            $num_irmaos = isset($_SESSION['num_irmaos']) ? $_SESSION['num_irmaos'] : "0";
            $morada = isset($_SESSION['morada']) ? $_SESSION['morada'] : "";
            $cod_postal = isset($_SESSION['cod_postal']) ? $_SESSION['cod_postal'] : "";
            $telefone = isset($_SESSION['telefone']) ? $_SESSION['telefone'] : "";
            $telemovel = isset($_SESSION['telemovel']) ? $_SESSION['telemovel'] : "";
            $telefone_pai = isset($_SESSION['telefone_pai']) ? $_SESSION['telefone_pai'] : "";
            $telemovel_pai = isset($_SESSION['telemovel_pai']) ? $_SESSION['telemovel_pai'] : "";
            $telefone_mae = isset($_SESSION['telefone_mae']) ? $_SESSION['telefone_mae'] : "";
            $telemovel_mae = isset($_SESSION['telemovel_mae']) ? $_SESSION['telemovel_mae'] : "";
            $telefone_enc_edu = isset($_SESSION['telefone_enc_edu']) ? $_SESSION['telefone_enc_edu'] : "";
            $telemovel_enc_edu = isset($_SESSION['telemovel_enc_edu']) ? $_SESSION['telemovel_enc_edu'] : "";
            $nif = isset($_SESSION['nif']) ? $_SESSION['nif'] : "";
            $escuteiro = isset($_SESSION['escuteiro']) ? $_SESSION['escuteiro'] : "Nao";
            $enc_edu = isset($_SESSION['enc_edu']) ? $_SESSION['enc_edu'] : "Mae";
            $outro_enc_edu_quem = isset($_SESSION['outro_enc_edu_quem']) ? $_SESSION['outro_enc_edu_quem'] : "";
            $nome_enc_edu = isset($_SESSION['nome_enc_edu']) ? $_SESSION['nome_enc_edu'] : "";
            $prof_enc_edu = isset($_SESSION['prof_enc_edu']) ? $_SESSION['prof_enc_edu'] : "";
            $pai = isset($_SESSION['pai']) ? $_SESSION['pai'] : "";
            $prof_pai = isset($_SESSION['prof_pai']) ? $_SESSION['prof_pai'] : "";
            $mae = isset($_SESSION['mae']) ? $_SESSION['mae'] : "";
            $prof_mae = isset($_SESSION['prof_mae']) ? $_SESSION['prof_mae'] : "";
            $casados = isset($_SESSION['casados']) ? $_SESSION['casados'] : "Nao";
            $casados_como = isset($_SESSION['casados_como']) ? $_SESSION['casados_como'] : "igreja";
            $email = isset($_SESSION['email']) ? $_SESSION['email'] : "";
            $autorizacao = isset($_SESSION['autorizacao']) ? $_SESSION['autorizacao'] : "";
            $RGPD_assinado = isset($_SESSION['RGPD_assinado']) ? $_SESSION['RGPD_assinado'] : "";
            $foto = isset($_SESSION['foto']) ? $_SESSION['foto'] : (isset($_SESSION['foto_cam']) ? $_SESSION['foto_cam'] : "");
            if($foto != "" && file_exists(UserData::getCatechumensPhotosFolder() . '/' . $foto))
            {
                $foto_data_base64 = base64_encode(file_get_contents(UserData::getCatechumensPhotosFolder() . '/' . $foto));
            }
        }
        else if(isset($_REQUEST['modo']) && $_REQUEST['modo']=='irmao')
        {
            $cid = isset($_REQUEST['cid']) ? intval(Utils::sanitizeInput($_REQUEST['cid'])) : 0;

            if(!$cid || $cid <= 0)
            {
                echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não foi especificado nenhum catequizando para copiar os dados do irmão.</div>");
                echo("</div></body></html>");
                die();
            }

            $catechumen = null;
            try
            {
                $catechumen = $db->getCatechumenById($cid);
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                echo("</div></body></html>");
                die();
            }

            if(!$catechumen)
            {
                echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Falha ao tentar aceder à ficha do catequizando.</div>");
                echo("</div></body></html>");
                die();
            }

            $num_irmaos = intval($catechumen['num_irmaos']);
            $autorizacao = Utils::sanitizeOutput($catechumen['autorizou_fotos']);

            $fid_pai = (isset($catechumen['pai'])) ? intval($catechumen['pai']) : null;
            $fid_mae = (isset($catechumen['mae'])) ? intval($catechumen['mae']) : null;
            $fid_ee = intval($catechumen['enc_edu']);
            $enc_edu_quem = Utils::sanitizeOutput($catechumen['enc_edu_quem']);

            if(!is_null($fid_pai))
            {
                try
                {
                    $father = $db->getFamilyMember($fid_pai);
                    if($father)
                    {
                        $pai = Utils::sanitizeOutput($father['nome']);
                        $prof_pai = Utils::sanitizeOutput($father['prof']);
                        $telefone_pai = Utils::sanitizeOutput($father['telefone']);
                        $telemovel_pai = Utils::sanitizeOutput($father['telemovel']);
                    }
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    echo("</div></body></html>");
                    die();
                }
            }

            if(!is_null($fid_mae))
            {
                try
                {
                    $mother = $db->getFamilyMember($fid_mae);
                    if($mother)
                    {
                        $mae = Utils::sanitizeOutput($mother['nome']);
                        $prof_mae = Utils::sanitizeOutput($mother['prof']);
                        $telefone_mae = Utils::sanitizeOutput($mother['telefone']);
                        $telemovel_mae = Utils::sanitizeOutput($mother['telemovel']);
                    }
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    echo("</div></body></html>");
                    die();
                }
            }

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
                    {
                        $casados = "Não";
                    }
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    echo("</div></body></html>");
                    die();
                }
            }

            if($fid_ee)
            {
                try
                {
                    $responsible = $db->getFamilyMember($fid_ee);
                    if($responsible)
                    {
                        $nome_ee = Utils::sanitizeOutput($responsible['nome']);
                        $prof_ee = Utils::sanitizeOutput($responsible['prof']);
                        $morada = Utils::sanitizeOutput($responsible['morada']);
                        $cod_postal = Utils::sanitizeOutput($responsible['cod_postal']);
                        $telefone = Utils::sanitizeOutput($responsible['telefone']);
                        $telemovel = Utils::sanitizeOutput($responsible['telemovel']);
                        $email = Utils::sanitizeOutput($responsible['email']);
                        $rgpd_val = intval($responsible['RGPD_assinado']);
                        $RGPD_assinado = (!is_null($rgpd_val) && $rgpd_val == 1);
                    }
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    echo("</div></body></html>");
                    die();
                }
            }

            if($fid_ee == $fid_pai && !is_null($fid_pai))
            {
                $enc_edu = "Pai";
                $nome_enc_edu = "";
                $prof_enc_edu = "";
            }
            else if($fid_ee == $fid_mae && !is_null($fid_mae))
            {
                $enc_edu = "Mae";
                $nome_enc_edu = "";
                $prof_enc_edu = "";
            }
            else if($fid_ee)
            {
                $enc_edu = "Outro";
                $outro_enc_edu_quem = $enc_edu_quem;
                $nome_enc_edu = isset($nome_ee) ? $nome_ee : "";
                $prof_enc_edu = isset($prof_ee) ? $prof_ee : "";
                $telefone_enc_edu = isset($telefone) ? $telefone : "";
                $telemovel_enc_edu = isset($telemovel) ? $telemovel : "";
            }
        }
    }
  	?>

  <div class="clearfix" style="margin-bottom: 20px;"></div>
  <div class="container">


<form role="form" onsubmit="return validar();" action="processarInscricao.php<?php if(isset($_REQUEST['modo']) && $_REQUEST['modo']=='editar'){ echo('?modo=editar&cid=' . $cid);}?>" method="post">

  <div class="panel panel-default" id="painel_ficha">
   <div class="panel-body">



  <div class="img-thumbnail pull-right" id="div_camara">
  	<img src="<?php if(isset($_REQUEST['modo']) && ($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto!="") echo("resources/catechumenPhoto.php?foto_name=" . $foto ); else echo("img/default-user-icon-profile.png");?>" class=""  alt="Foto do catequizando" width="240" height="240">
  </div>
  <div class="clearfix" style="margin-bottom: 10px;"></div>

  <div class="btn-group pull-right">
  	<button type="button" class="btn btn-default" id="btn_cancelar" onclick="cancela_camara()" style="display:none;"><span class="glyphicon glyphicon-remove-circle"></span> <?php if(isset($_REQUEST['modo']) && ($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto!="") echo("Repor"); else echo("Cancelar");?></button>
  	<button type="button" class="btn btn-default" id="btn_limpa" onclick="limpa_foto()" style="<?php if(isset($_REQUEST['modo']) && ($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto!="") echo(''); else echo('display:none;')?>"><span class="glyphicon glyphicon-trash"></span> Remover foto</button>
    <button type="button" class="btn btn-default" id="btn_configurar" onclick="Webcam.configure()" style="display:none;"><span class="glyphicon glyphicon-wrench"></span> Configurar</button>
  	<button type="button" class="btn btn-default" id="btn_reiniciar" onclick="reiniciar_camara()" style="display:none;"><span class="glyphicon glyphicon-repeat"></span> Reiniciar</button>
  	<button type="button" class="btn btn-default" id="btn_foto" onclick="prepara_camara()"><span class="glyphicon glyphicon-camera"></span> Tirar foto</button>
  	<button type="button" class="btn btn-default" id="btn_dispara" onclick="dispara()" style="display:none;"><span class="glyphicon glyphicon-camera"></span> Disparar!</button>
  </div>


  <div class="clearfix" style="margin-bottom: 20px;"></div>

  <!--<div class="container">-->



  <!--nome-->
    <div class="form-group">
    <div class="col-xs-6">
      <label for="nome">Nome:</label>
      <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome completo do catequizando" onchange="verifica_catequizando_inscrito()" value="<?php  if(isset($_REQUEST['modo']) && ($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar')){ echo('' . $nome . '');} else {echo('');} ?>" required>
      <div class="alert alert-danger" id="catequizando_inscrito" style="display:none;"><span class="glyphicon glyphicon-exclamation-sign"></span> Catequizando já inscrito anteriormente!</div>
    </div>
    </div>


   <!--data nascimento-->
    <div class="form-group">
     <div class="col-xs-2">
     <div class="input-append date" id="data_nasc_div" data-date="" data-date-format="dd-mm-yyyy">
      <label for="data_nasc">Nasceu a:</label>
      <!--<div class="input-group">-->
      <input class="form-control" id="data_nasc" name="data_nasc" size="16" type="text" onclick="verifica_data_nasc()" onchange="verifica_data_nasc()" placeholder="dd-mm-aaaa" value="<?php  if(isset($_REQUEST['modo']) && ($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar')){ echo('' . $data_nasc . '');} else {echo('');} ?>"  required>
      <!--<span class="input-group-addon glyphicon glyphicon-calendar" id="sizing-addon2"></span>
      </div>-->
      <span id="erro_nasc_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
     </div>
    </div>
    </div>


   <!--local nascimento-->
    <div class="form-group">
    <div class="col-xs-3">
      <label for="localidade">Em:</label>
      <input type="text" class="form-control" id="localidade" name="localidade" placeholder="Local de nascimento" list="locais_nascimento" value="<?php  if(isset($_REQUEST['modo']) && ($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar')){ echo('' . $local_nasc . '');} else {echo('');} ?>" required>
    </div>
    </div>


     <!--numero irmaos-->
    <div class="col-xs-1">
    <div id="num_irmaos_div">
      <label for="num_irmaos">Irmãos:</label>
      <input type="number" min=0 class="form-control" id="num_irmaos" name="num_irmaos" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $num_irmaos . '');} else {echo('0');} ?>">
    </div>
    </div>   
    <div class="clearfix"></div>


    <div class="row" style="margin-top:20px; "></div>

   <!--morada-->
    <div class="form-group">
    <div class="col-xs-12">
      <label for="morada">Morada:</label>
      <input type="text" class="form-control" id="morada" name="morada" placeholder="Morada do encarregado de educação" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $morada . '');} else {echo('');} ?>" required>
    </div>
    </div>




    <div class="form-group">

        <!--codigo postal-->
        <div class="col-xs-4">
            <div id="codigo_postal_div">
                <label for="codigo_postal">Código postal:</label>
                <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="xxxx-xxx Localidade" list="codigos_postais" onclick="verifica_codigo_postal()" onchange="verifica_codigo_postal()" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $cod_postal . '');} else {echo('');} ?>" required>
                <span id="erro_postal_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
            </div>
        </div>

        <?php if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED)) { ?>
        <!--NIF-->
        <div class="col-xs-2">
            <div id="nif_div">
                <label for="nif">NIF:</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="nif" name="nif" placeholder="NIF do catequizando" onclick="verifica_nif()" onchange="verifica_nif()" value="<?php  if($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $nif . '');} else {echo('');} ?>" required>
                    <span class="input-group-addon"><a href="javascript:void(0);" onclick="preencher_nif_desconhecido();" data-toggle="tooltip" data-container="body" data-placement="top" title='Preencher "000 000 000". Utilizar excecionalmente caso esteja a editar uma ficha existente e não saiba o NIF do catequizando.'><span class="fas fa-user-times"></span></a></span>
                </div>
                <span id="erro_nif_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php } ?>

    </div>



   <!--escuteiro-->
    <div class="form-group">
    <div class="col-xs-8">
    <div class="row" style="margin-top:20px; "></div>
    	<label for="e_escuteiro">É escuteiro(a):</label>
    	<label class="radio-inline"><input type="radio" name="escuteiro" value="Sim" <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar') && ($escuteiro=='Sim' || $escuteiro==1)){ echo('checked');} ?>>Sim</label>
	<label class="radio-inline"><input type="radio" name="escuteiro" value="Nao" <?php  if(($_REQUEST['modo']!='regresso' && $_REQUEST['modo']!='editar') || (($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar') && $escuteiro!='Sim' && $escuteiro!=1)){ echo('checked');} ?>>Não</label>
    </div>
    <div class="clearfix"></div>
    </div>



    <div class="row" style="margin-top:20px; "></div>

    <!--baptizado-->
    <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
    <div class="form-group">
    <div class="col-xs-8">
    <div class="row" style="margin-top:20px; "></div>
    	<label for="e_baptizado">É baptizado(a):</label>
    	<label class="radio-inline"><input type="radio" id="baptizado1" name="baptizado" value="Sim" <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['baptizado']=='Sim'){ echo('checked');} ?>>Sim</label>
	<label class="radio-inline"><input type="radio" id="baptizado2" name="baptizado" value="Nao" <?php  if($_REQUEST['modo']!='regresso' || ($_REQUEST['modo']=='regresso' && $_SESSION['baptizado']!='Sim')){ echo('checked');} ?>>Não</label>
    </div>
    <div class="clearfix"></div>
    </div>
    <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>


    <!--paroquia de baptismo-->
    <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
    <div class="form-group collapse <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['baptizado']=='Sim'){ echo('in');} ?>" id="paroquia_baptismo_collapse">
    <div class="col-xs-4">
      <label for="paroquia_baptismo"> Paróquia de baptismo: </label>
      <input type="text" class="form-control" id="paroquia_baptismo" name="paroquia_baptismo" placeholder="Paróquia de baptismo" list="paroquias" value="<?php  if($_REQUEST['modo']=='regresso'){ echo('' . $_SESSION['paroquia_baptismo'] . '');} else {echo('');} ?>">
    </div>
     <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>


    <!--data de baptismo-->
    <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
    <div class="form-group">
     <div class="col-xs-2">
     <div class="input-append date" id="data_baptismo_div" data-date="" data-date-format="dd-mm-yyyy">
      <label for="data_baptismo">Data:</label>
      <input class="form-control" id="data_baptismo" name="data_baptismo" size="16" type="text" onclick="verifica_data_baptismo()" onchange="verifica_data_baptismo()" placeholder="dd-mm-aaaa" value="<?php  if($_REQUEST['modo']=='regresso'){ echo('' . $_SESSION['data_baptismo'] . '');} else {echo('');} ?>" >
      <span id="erro_data_baptismo_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
     </div>
    </div>
    </div>
    </div>
    <div class="clearfix"></div>
    <div class="row" style="margin-bottom:20px; "></div>
    <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>




    <!--primeira comunhao-->
    <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
    <div class="form-group">
    <div class="col-xs-8">
    	<label for="fez_primeira_comunhao">Fez primeira comunhão:</label>
    	<label class="radio-inline"><input type="radio" id="comunhao1" name="comunhao" value="Sim" <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['comunhao']=='Sim'){ echo('checked');} ?>>Sim</label>
	<label class="radio-inline"><input type="radio" id="comunhao2" name="comunhao" value="Nao" <?php  if($_REQUEST['modo']!='regresso' || ($_REQUEST['modo']=='regresso' && $_SESSION['comunhao']!='Sim')){ echo('checked');} ?>>Não</label>
    </div>
    <div class="clearfix"></div>
    </div>
    <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>


    <!--paroquia primeira comunhao-->
     <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
    <div class="form-group collapse <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['comunhao']=='Sim'){ echo('in');} ?>" id="paroquia_comunhao_collapse">
    <div class="col-xs-4">
      <label for="paroquia_comunhao"> Paróquia 1ª comunhão:</label>
      <input type="text" class="form-control" id="paroquia_comunhao" name="paroquia_comunhao" placeholder="Paróquia 1ª comunhão" list="paroquias" value="<?php  if($_REQUEST['modo']=='regresso'){ echo('' . $_SESSION['paroquia_comunhao'] . '');} else {echo('');} ?>">
    </div>
    <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>

    <!--data primeira comunhao-->
    <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
    <div class="form-group">
     <div class="col-xs-2">
     <div class="input-append date" id="data_comunhao_div" data-date="" data-date-format="dd-mm-yyyy">
      <label for="data_comunhao">Data:</label>
      <input class="form-control" id="data_comunhao" name="data_comunhao" size="16" type="text" onclick="verifica_data_comunhao()" onchange="verifica_data_comunhao()" placeholder="dd-mm-aaaa" value="<?php  if($_REQUEST['modo']=='regresso'){ echo('' . $_SESSION['data_comunhao'] . '');} else {echo('');} ?>" >
      <span id="erro_data_comunhao_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
     </div>
    </div>
    </div>
    </div>
    <div class="clearfix"></div>
    <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>





     <!--encarregado educacao-->
    <div class="form-group">
    <div class="col-xs-8">
        <div class="row" style="margin-top:20px; "></div>
    	<label for="encarregado educacao">Encarregado de educação:</label>
    	<label class="radio-inline"><input type="radio" id="enc_edu1" name="enc_edu" value="Pai" onchange="mostrar_ocultar_campo_outro_enc_edu();" <?php  if((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])) && $enc_edu=='Pai'){ echo('checked');} ?>>Pai</label>
	    <label class="radio-inline"><input type="radio" id="enc_edu2" name="enc_edu" value="Mae" onchange="mostrar_ocultar_campo_outro_enc_edu();" <?php  if((!isset($_REQUEST['modo']) || !in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])) || ((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])) && $enc_edu=='Mae')){ echo('checked');} ?>>Mãe</label>
	    <label class="radio-inline"><input type="radio" id="enc_edu3" name="enc_edu" value="Outro" onchange="mostrar_ocultar_campo_outro_enc_edu();" <?php  if((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])) && $enc_edu=='Outro'){ echo('checked');} ?>>Outro</label>
    </div>
    <div class="clearfix"></div>
    </div>


    <!--outro encarregado educacao quem-->
    <div class="form-group collapse <?php  if((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])) && $enc_edu=='Outro'){ echo('in');} ?>" id="encarregado_educacao_collapse">
    <div class="col-xs-2">
      <label for="outro_enc_edu_quem"> Parentesco:</label>
      <input type="text" class="form-control" id="outro_enc_edu_quem" name="outro_enc_edu_quem" placeholder="Ex: Avó" list="parentesco" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])){ echo('' . $outro_enc_edu_quem . '');} else {echo('');} ?>">
    </div>
    <datalist id='parentesco'>
    	<option value='Avô'>
    	<option value='Avó'>
    	<option value='Irmão'>
    	<option value='Irmã'>
    </datalist>

    <!--outro encarregado educacao nome-->
    <div class="col-xs-3">
      <label for="nome_end_edu"> Nome:</label>
      <input type="text" class="form-control" id="nome_enc_edu" name="nome_enc_edu" placeholder="Nome completo do encarregado de educação" list="familiares" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])){ echo('' . $nome_enc_edu . '');} else {echo('');} ?>">
    </div>

   <!--outro encarregado educacao profissao-->
    <div class="col-xs-3">
      <label for="prof_enc_edu"> Profissão:</label>
      <input type="text" class="form-control" id="prof_enc_edu" name="prof_enc_edu" placeholder="Profissão do encarregado de educação" list="profissoes" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])){ echo('' . $prof_enc_edu . '');} else {echo('');} ?>">
    </div>

    <!--telefone outro encarregado educacao-->
    <div class="col-xs-2">
      <div id="telefone_enc_edu_div">
        <label for="telefone_enc_edu">Telefone:</label>
        <input type="tel" class="form-control" id="telefone_enc_edu" name="telefone_enc_edu" placeholder="Telefone" onclick="verifica_telefone_enc_edu()" onchange="verifica_telefone_enc_edu()" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])){ echo('' . $telefone_enc_edu . '');} else {echo('');} ?>">
        <span id="erro_telefone_enc_edu_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
      </div>
    </div>

    <!--telemovel outro encarregado educacao-->
    <div class="col-xs-2">
      <div id="telemovel_enc_edu_div">
        <label for="telemovel_enc_edu"><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>:</label>
        <input type="tel" class="form-control" id="telemovel_enc_edu" name="telemovel_enc_edu" placeholder="<?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>" onclick="verifica_telemovel_enc_edu()" onchange="verifica_telemovel_enc_edu()" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'editar', 'irmao'])){ echo('' . $telemovel_enc_edu . '');} else {echo('');} ?>">
        <span id="erro_telemovel_enc_edu_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
      </div>
    </div>
    <div class="clearfix"></div>
    </div>







    <!--pai-->
    <div class="form-group">
    <div class="col-xs-4">
      <label for="pai">Pai:</label>
      <input type="text" class="form-control" id="pai" name="pai" placeholder="Nome completo do pai" list="familiares" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $pai . '');} else {echo('');} ?>">
    </div>

    <!--profissao pai-->
    <div class="col-xs-4">
      <label for="prof_pai">Profissão:</label>
      <input type="text" class="form-control" id="prof_pai" name="prof_pai" placeholder="Profissão do pai" list="profissoes" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $prof_pai . '');} else {echo('');} ?>">
    </div>

    <!--telefone pai-->
    <div class="col-xs-2">
      <div id="telefone_pai_div">
        <label for="telefone_pai">Telefone:</label>
        <input type="tel" class="form-control" id="telefone_pai" name="telefone_pai" placeholder="Telefone do pai" onclick="verifica_telefone_pai()" onchange="verifica_telefone_pai()" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $telefone_pai . '');} else {echo('');} ?>">
        <span id="erro_telefone_pai_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
      </div>
    </div>

    <!--telemovel pai-->
    <div class="col-xs-2">
      <div id="telemovel_pai_div">
        <label for="telemovel_pai"><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>:</label>
        <input type="tel" class="form-control" id="telemovel_pai" name="telemovel_pai" placeholder="<?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?> do pai" onclick="verifica_telemovel_pai()" onchange="verifica_telemovel_pai()" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $telemovel_pai . '');} else {echo('');} ?>">
        <span id="erro_telemovel_pai_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
      </div>
    </div>
    <div class="clearfix"></div>
    </div>



    <!--mae-->
    <div class="form-group">
    <div class="col-xs-4">
      <label for="mae">Mãe:</label>
      <input type="text" class="form-control" id="mae" name="mae" placeholder="Nome completo da mãe" list="familiares" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $mae . '');} else {echo('');} ?>">
    </div>

    <!--profissao mae-->
    <div class="col-xs-4">
      <label for="prof_mae">Profissão:</label>
      <input type="text" class="form-control" id="prof_mae" name="prof_mae" placeholder="Profissão da mãe" list="profissoes" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $prof_mae . '');} else {echo('');} ?>">
    </div>

    <!--telefone mae-->
    <div class="col-xs-2">
      <div id="telefone_mae_div">
        <label for="telefone_mae">Telefone:</label>
        <input type="tel" class="form-control" id="telefone_mae" name="telefone_mae" placeholder="Telefone da mãe" onclick="verifica_telefone_mae()" onchange="verifica_telefone_mae()" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $telefone_mae . '');} else {echo('');} ?>">
        <span id="erro_telefone_mae_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
      </div>
    </div>

    <!--telemovel mae-->
    <div class="col-xs-2">
      <div id="telemovel_mae_div">
        <label for="telemovel_mae"><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>:</label>
        <input type="tel" class="form-control" id="telemovel_mae" name="telemovel_mae" placeholder="<?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?> da mãe" onclick="verifica_telemovel_mae()" onchange="verifica_telemovel_mae()" value="<?php  if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['irmao', 'regresso', 'editar'])){ echo('' . $telemovel_mae . '');} else {echo('');} ?>">
        <span id="erro_telemovel_mae_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
      </div>
    </div>
    <div class="clearfix"></div>
    </div>



    <!--casados-->
    <div class="form-group">
    <div class="col-xs-8">
    	<label for="casados">Casados:</label>
    	<label class="radio-inline"><input type="radio" id="casados1" name="casados" value="Sim" <?php  if((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'irmao', 'editar'])) && $casados=='Sim'){ echo('checked');} ?>>Sim</label>
	<label class="radio-inline"><input type="radio" id="casados2" name="casados" value="Nao" <?php  if((!isset($_REQUEST['modo']) || !in_array($_REQUEST['modo'], ['regresso', 'irmao', 'editar'])) || ((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'irmao', 'editar'])) && $casados!='Sim')){ echo('checked');} ?>>Não</label>
    </div>
    <div class="clearfix"></div>
    </div>


     <!--casados como-->
    <div class="form-group collapse <?php  if((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'irmao', 'editar'])) && $casados=='Sim'){ echo('in');} ?>" id="casados_como">
    <div class="col-xs-8">
    	<label for="casados_como">Se respondeu <i>Sim</i> indique:</label>
    	<label class="radio-inline"><input type="radio" name="casados_como" value="igreja" <?php  if((!isset($_REQUEST['modo']) || !in_array($_REQUEST['modo'], ['regresso', 'irmao', 'editar'])) || ((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'irmao', 'editar'])) && $casados_como=='igreja')){ echo('checked');} ?>>Igreja</label>
	<label class="radio-inline"><input type="radio" name="casados_como" value="civil"  <?php  if((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'irmao', 'editar'])) && $casados_como=='civil'){ echo('checked');} ?>>Civil</label>
	<label class="radio-inline"><input type="radio" name="casados_como" value="uniao de facto" <?php  if((isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['regresso', 'irmao', 'editar'])) && $casados_como=='uniao de facto'){ echo('checked');} ?> >União de facto</label>
    </div>
    <div class="clearfix"></div>
    </div>



     <!--email-->  
    <div class="form-group">
    <div class="col-xs-12">
      <label for="email">Para que seja informado de notícias e atividades da nossa catequese, indique-nos o seu e-mail. Assim poderá organizar melhor a sua vida e planear a sua agenda.<br>E-mail:</label>
      <input type="email" class="form-control" id="email" name="email" placeholder="endereco@example.com" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $email . '');} else {echo('');} ?>">
    </div>
    <div class="clearfix"></div>
    </div>



    <!--autoriza fotografias-->
    <div class="checkbox">
      <label><input id="autorizacao" name="autorizacao" type="checkbox" <?php 	if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['editar', 'regresso', 'irmao']) && ($autorizacao=='on' || $autorizacao==1 || $autorizacao=='Sim' || $autorizacao===true)){ echo("checked");}?> > Autoriza a utilização e divulgação de fotografias do educando, tiradas no âmbito das actividades catequéticas.</label>
    </div>
    <!--<div class="row" style="margin-bottom:60px; "></div>-->

   <!--Consentimento tratamento de dados (RGPD)-->
   <div class="checkbox">
       <label><input id="consentimento_rgpd" name="consentimento_rgpd" type="checkbox" <?php 	if(isset($_REQUEST['modo']) && in_array($_REQUEST['modo'], ['editar', 'regresso', 'irmao']) && ($RGPD_assinado=='on' || $RGPD_assinado==1 || $RGPD_assinado===true)){ echo("checked");}?> > Assinou e entregou o consentimento de tratamento de dados (RGPD).</label>
   </div>
   <div class="row" style="margin-bottom:60px; "></div>


    <!-- fotografia tirada com webcam -->
    <input type="hidden" id="foto_data" name="foto_data" value="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto_data_base64!="") echo($foto_data_base64);?>">
    <input type="hidden" id="original_foto_data" name="original_foto_data" value="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto_data_base64!="") echo($foto_data_base64);?>">
        <input type="hidden" id="debug_foto" name="debug_foto" value="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto!="") echo($foto);?>">
        <input type="hidden" id="debug_base64" name="debug_base64" value="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto!="") echo(base64_encode('abcd'));?>">


    </div>
  </div>



  <?php   if($_REQUEST['modo']!='editar')
          {
  ?>
    <!-- Inscricao num grupo de catequese -->
    <div class="panel panel-default">
      <div class="panel-heading">Inscrição num grupo de catequese</div>
        <div class="panel-body">

            <div class="form-group">
              <div class="col-xs-8">
              <div class="row"></div>
                    <div class="radio">
                        <label><input type="radio" id="quer_inscrever" name="quer_inscrever" value="Nao" <?php  if($_REQUEST['modo']!='regresso' || ($_REQUEST['modo']=='regresso' && $_SESSION['quer_inscrever']!='Sim')){ echo('checked');} ?>> Não inscrever agora num grupo de catequese</label>
                    </div>
                    <div class="radio">
                        <label><input type="radio" id="quer_inscrever2" name="quer_inscrever" value="Sim" <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['quer_inscrever']=='Sim'){ echo('checked');} ?>> Inscrever agora num grupo de catequese</label>
                    </div>
              </div>
              <div class="clearfix"></div>
              </div>


              <div class="jumbotron collapse <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['quer_inscrever']=='Sim'){ echo('in');} ?>" id="quer_inscrever_collapse">

                  <div class="form-group">
                    <div class="col-xs-4" style="margin-top: 3px;">
                     <label for="ano_catequetico">Ano catequético: </label>          
                      <?php
                          echo("<span>" . Utils::formatCatecheticalYear(Utils::currentCatecheticalYear()) . "</span>\n");
                      ?>
                    </div>

                      <div class="col-xs-4">
                        <label for="catecismo">Catecismo:</label>
                      <select name="catecismo">

                    <?php

                    //Obter anos de catequese
                    $result = NULL;
                    try
                    {
                        $ano_atual = Utils::currentCatecheticalYear();
                        $result = $db->getCatechisms($ano_atual);
                    }
                    catch(Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        die();
                    }

                    if (isset($result) && count($result)>=1)
                    {
                        foreach($result as $row)
                        {
                          echo("<option value='" . $row['ano_catecismo'] . "'");
                          if ($_REQUEST['modo']=='regresso' && $_SESSION['catecismo']==$row['ano_catecismo'])
                            echo(" selected");
                          echo(">");
                          echo("" . $row['ano_catecismo'] . "º" . "</option>\n");
                        }
                    }
                    $result = null;

                    ?>
                      </select>
                    </div>

                      <div class="col-xs-3">
                        <label for="turma">Grupo:</label>
                      <select name="turma">

                    <?php

                    //Obter turmas de catequese
                    $result = NULL;
                    try {
                        $ano_atual = Utils::currentCatecheticalYear();
                        $result = $db->getGroupLetters($ano_atual);
                    }
                    catch(Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        die();
                    }

                    if (isset($result) && count($result)>=1)
                    {
                        foreach($result as $row)
                        {
                          echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
                          if ($_REQUEST['modo']=='regresso' && $_SESSION['turma']==$row['turma'])
                            echo(" selected");
                          echo(">");
                          echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                        }
                    }
                    $result = null;

                    ?>
                      </select>
                    </div>


                      <div class="checkbox col-xs-1" style="margin-top: 3px;">
                        <label><input id="pago" name="pago" type="checkbox" <?php   if($_REQUEST['modo']=='regresso' && ($_SESSION['pago']=='on' || $_SESSION['pago']==1)){ echo("checked");}?> > Pago</label>
                      </div>

                     </div>

              </div>
              <div class="clearfix"></div>

        </div>
    </div>

  <?php
          }
  ?>



    <?php 	if($_REQUEST['modo']=='editar')
  			echo('<button type="button" class="btn btn-default glyphicon glyphicon-remove" onclick="window.location.assign(\'mostrarFicha.php?cid=' . $cid . '\');"> Cancelar</button>');
  	?>


    <?php 	if($_REQUEST['modo']=='editar')
  			echo("<button type=\"submit\" class=\"btn btn-primary glyphicon glyphicon-floppy-disk\"> Guardar</button>");
  		else
  			echo("<button type=\"submit\" class=\"btn btn-primary glyphicon glyphicon-pencil\"> Inscrever</button>");
  	?>


    <div style="margin-bottom: 60px;"></div>

  </form>

</div>



<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>
<script type="text/javascript" src="webcamjs-master/webcam.js"></script>

<script language="JavaScript">    

function dispara() 
{
    Webcam.snap( function(data_uri) {
        document.getElementById('div_camara').innerHTML = '<img src="'+data_uri+'"/>';
        document.getElementById('foto_data').value = data_uri;
        document.getElementById('btn_dispara').style.display="none";                
        document.getElementById('btn_reiniciar').style.display="inline";

    } );
}

function prepara_camara()
{
    Webcam.set({
                  width: 320,
                  height: 240,
                  crop_width: 240,
                  crop_height: 240,
                  image_format: 'jpeg',
                  jpeg_quality: 90
              });
    Webcam.attach( '#div_camara' );

    document.getElementById('btn_configurar').style.display= (!Webcam.userMedia)?"inline":"none";
    document.getElementById('btn_cancelar').style.display="inline";
    document.getElementById('btn_dispara').style.display="inline";
    document.getElementById('btn_limpa').style.display="none";
    document.getElementById('btn_foto').style.display="none";
}

function cancela_camara()
{
  	document.getElementById('div_camara').innerHTML = "<img src=\"<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto!="") echo("resources/catechumenPhoto.php?foto_name=" . $foto ); else echo("img/default-user-icon-profile.png");?>\" class=\"\"  alt=\"Foto do catequizando\" width=\"240\" height=\"240\">";
  	document.getElementById('btn_configurar').style.display="none";
    document.getElementById('btn_cancelar').style.display="none";
    document.getElementById('btn_dispara').style.display="none";
    document.getElementById('btn_reiniciar').style.display="none";
    document.getElementById('btn_limpa').style.display="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $foto!="") echo('inline'); else echo('none')?>";
    document.getElementById('btn_foto').style.display="inline";

    document.getElementById('foto_data').value = document.getElementById('original_foto_data').value;
}


function reiniciar_camara()
{
    document.getElementById('foto_data').value = document.getElementById('original_foto_data').value;
    document.getElementById('btn_reiniciar').style.display="none";
    Webcam.reset();
    prepara_camara();
}

function limpa_foto()
{
  document.getElementById('foto_data').value = '';
  document.getElementById('div_camara').innerHTML = "<img src=\"img/default-user-icon-profile.png\" class=\"\"  alt=\"Foto do catequizando\" width=\"240\" height=\"240\">";
  document.getElementById('btn_configurar').style.display="none";
  document.getElementById('btn_cancelar').style.display="inline";
  document.getElementById('btn_dispara').style.display="none";
  document.getElementById('btn_reiniciar').style.display="none";
  document.getElementById('btn_limpa').style.display="none";
  document.getElementById('btn_foto').style.display="inline";
}

</script>




<script>
function validar()
{

	var cod_postal = document.getElementById('codigo_postal').value;
    var data_nasc = document.getElementById('data_nasc').value;
    var nifElem = document.getElementById('nif');
    var nif = (nifElem)? nifElem.value : "";
    var NIF_ENABLED = <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED)?'true':'false' ?>;
	var telefone_pai = document.getElementById('telefone_pai').value;
    var telemovel_pai = document.getElementById('telemovel_pai').value;
    var telefone_mae = document.getElementById('telefone_mae').value;
    var telemovel_mae = document.getElementById('telemovel_mae').value;
    var telefone_enc_edu = document.getElementById('telefone_enc_edu').value;
    var telemovel_enc_edu = document.getElementById('telemovel_enc_edu').value;
    var pai = document.getElementById('pai').value;
    var prof_pai = document.getElementById('prof_pai').value;
    var mae = document.getElementById('mae').value;
    var enc_edu_pai = document.getElementsByName('enc_edu')[0].checked;
    var enc_edu_mae = document.getElementsByName('enc_edu')[1].checked;
    var enc_edu_outro = document.getElementsByName('enc_edu')[2].checked;
    var enc_edu_parentesco = document.getElementById('outro_enc_edu_quem').value;
    var enc_edu_nome = document.getElementById('nome_enc_edu').value;
    var enc_edu_prof = document.getElementById('prof_enc_edu').value;
    var prof_mae = document.getElementById('prof_mae').value;

    <?php if($_REQUEST['modo']!='editar') :?>
    var baptizado = document.getElementsByName('baptizado')[0].checked;
    var paroquia_baptismo = document.getElementById('paroquia_baptismo').value;
    var data_baptismo = document.getElementById('data_baptismo').value;
    var comunhao = document.getElementsByName('comunhao')[0].checked;
    var paroquia_comunhao = document.getElementById('paroquia_comunhao').value;
    var data_comunhao = document.getElementById('data_comunhao').value;



    if(!verifica_catequizando_inscrito())
    {
        alert("O catequizando já foi inscrito anteriormente!");
    return false;
	}
	<?php endif ?>

	if(!data_valida(data_nasc))
    {
        alert("A data de nascimento que introduziu é inválida. Deve ser da forma dd-mm-aaaa.");
        return false;
    }

    if(NIF_ENABLED)
    {
        if(nif==="" || nif===undefined)
        {
            alert("Deve introduzir o NIF do catequizando.");
            return false;
        }
        if(!nif_valido(nif))
        {
            alert("O número de identificação fiscal que introduziu é inválido.");
            return false;
        }
    }


	if(!codigo_postal_valido(cod_postal, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
	{
		alert("O código postal que introduziu é inválido. Deve ser da forma 'xxxx-yyy Localidade'.");
		return false;
	}



    if(telefone_pai!="" && telefone_pai!=undefined && !telefone_valido(telefone_pai, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
    {
        alert("O número de telefone do pai que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
        return false;
    }
    if(telemovel_pai!="" && telemovel_pai!=undefined && !telefone_valido(telemovel_pai, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
    {
        alert("O número de telemóvel do pai que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
        return false;
    }
    if(telefone_mae!="" && telefone_mae!=undefined && !telefone_valido(telefone_mae, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
    {
        alert("O número de telefone da mãe que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
        return false;
    }
    if(telemovel_mae!="" && telemovel_mae!=undefined && !telefone_valido(telemovel_mae, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
    {
        alert("O número de telemóvel da mãe que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
        return false;
    }

    if(enc_edu_pai && (telefone_pai=="" || telefone_pai==undefined) && (telemovel_pai=="" || telemovel_pai==undefined))
    {
        alert("Deve introduzir pelo menos um número de telefone ou telemóvel para o encarregado de educação.");
        return false;
    }
    if(enc_edu_mae && (telefone_mae=="" || telefone_mae==undefined) && (telemovel_mae=="" || telemovel_mae==undefined))
    {
        alert("Deve introduzir pelo menos um número de telefone ou telemóvel para o encarregado de educação.");
        return false;
    }

    if(telefone_enc_edu!="" && telefone_enc_edu!=undefined && !telefone_valido(telefone_enc_edu, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
    {
        alert("O número de telefone do encarregado de educação que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
        return false;
    }
    if(telemovel_enc_edu!="" && telemovel_enc_edu!=undefined && !telefone_valido(telemovel_enc_edu, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
    {
        alert("O número de telemóvel do encarregado de educação que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
        return false;
    }

    if(enc_edu_outro && (telefone_enc_edu=="" || telefone_enc_edu==undefined) && (telemovel_enc_edu=="" || telemovel_enc_edu==undefined))
    {
        alert("Deve introduzir pelo menos um número de telefone ou telemóvel para o encarregado de educação.");
        return false;
    }


    <?php if($_REQUEST['modo']!='editar') :?>
        if( baptizado && (paroquia_baptismo=="" || paroquia_baptismo==undefined))
        {
        	alert("Deve especificar a paróquia de baptismo.");
		return false; 
        }

        if( baptizado && (data_baptismo=="" || data_baptismo==undefined))
        {
        	alert("Deve especificar a data de baptismo.");
		return false; 
        }

        if( baptizado && !data_valida(data_baptismo))
        {
        	alert("A data de baptismo que introduziu é inválida. Deve ser da forma dd-mm-aaaa.");
		return false; 
        }

        if( comunhao  && (paroquia_comunhao=="" || paroquia_comunhao==undefined))
        {
        	alert("Deve especificar a paróquia onde realizou a primeira comunhão.");
		return false; 
        }

        if( comunhao  && (data_comunhao=="" || data_comunhao==undefined))
        {
        	alert("Deve especificar a data em que realizou a primeira comunhão.");
		return false; 
        }

        if( comunhao && !data_valida(data_comunhao))
        {
        	alert("A data da primeira comunhão que introduziu é inválida. Deve ser da forma dd-mm-aaaa.");
		return false; 
        }
    <?php endif ?>


    if( (enc_edu_pai && (pai=="" || pai==undefined)) || (enc_edu_mae && (mae=="" || mae==undefined)) )
	{
		alert("Deve especificar o nome e profissão do encarregado de educação.");
		return false; 
	}


	if( enc_edu_outro && ((enc_edu_parentesco=="" || enc_edu_parentesco==undefined) || (enc_edu_nome=="" || enc_edu_nome==undefined) || (enc_edu_prof=="" || enc_edu_prof==undefined)) )
	{
		alert("Deve especificar o grau de parentesco, nome e profissão do encarregado de educação.");
		return false; 
	}


    if( (pai!="" && pai!=undefined) && (prof_pai=="" | prof_pai==undefined) )
    {
    alert("Deve especificar a profissão do pai.");
    return false;
    }

    if( (prof_pai!="" && prof_pai!=undefined) && (pai=="" | pai==undefined) )
    {
    alert("Deve especificar o nome do pai, além da profissão.");
    return false;
    }

    if( (mae!= "" && mae!=undefined) && (prof_mae== "" | prof_mae==undefined) )
    {
    alert("Deve especificar a profissão da mãe.");
    return false;
    }

     if( (prof_mae!= "" && prof_mae!=undefined) && (mae== "" | mae==undefined) )
    {
    alert("Deve especificar o nome da mãe, além da profissão.");
    return false;
    }

    return true;

}



function telefone_valido(num, locale)
{
    var phoneno = '';

    if(locale==="PT")
        phoneno = /^(\+\d{1,}[-\s]{0,1})?\d{9}$/;
    else if(locale==="BR")
        phoneno = /^(\+\d{1,}[-\s]{0,1})?\s*\(?(\d{2}|\d{0})\)?[-. ]?(\d{5}|\d{4})[-. ]?(\d{4})[-. ]?\s*$/;

	return num.match(phoneno);
}


function codigo_postal_valido(codigo, locale)
{
    var pattern="";
    if(locale==="PT")
	    pattern = /^[0-9]{4}\-[0-9]{3}\s\S+/;
    else if(locale==="BR")
        pattern = /^[0-9]{5}\-[0-9]{3}\s\S+/;

	return (pattern.test(codigo));

}


function data_valida(data)
{
	var pattern = /^[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}$/;

	return (pattern.test(data));
}

function preencher_nif_desconhecido()
{
    var nifElem = document.getElementById('nif');
    if(nifElem) {
        nifElem.value = '000000000';
        verifica_nif();
    }
}

function nif_valido(nif)
{
    // Remove espaços e caracteres não numéricos
    nif = (nif || "").toString().replace(/\D/g, '');

    // Aceita 000000000 como mecanismo de escape quando não se sabe o NIF
    if (nif === "000000000") {
        return true;
    }

    // Verifica se o NIF tem 9 dígitos
    if (nif.length !== 9) {
        return false;
    }

    // O primeiro dígito não pode ser 0
    if (nif[0] === '0') {
        return false;
    }

    // Converte o NIF em um array de dígitos
    const digitos = nif.split('').map(Number);

    // Calcula o dígito de controlo (PT): sum(d1*9 + d2*8 + ... + d8*2)
    const soma = digitos.slice(0, 8).reduce((acc, curr, index) => acc + curr * (9 - index), 0);

    let check = 11 - (soma % 11);
    if (check >= 10) check = 0;

    // Verifica se o dígito de controlo está correto
    return check === digitos[8];
}
</script>




<script>
$(function(){
   $('#data_nasc').datepicker({
       format: "dd-mm-yyyy",
       defaultViewDate: { year: 2010, month: 1, day: 1 },
       startView: 2,
       language: "pt",
       autoclose: true,
       orientation: "bottom auto",
       container: '#data_nasc_div'
    });
});

$(function(){
   $('#data_baptismo').datepicker({
       format: "dd-mm-yyyy",
       defaultViewDate: { year: 2010, month: 1, day: 1 },
       startView: 2,
       language: "pt",
       autoclose: true,
       orientation: "bottom auto",
       container: '#data_baptismo_div'
    });
});

$(function(){
   $('#data_comunhao').datepicker({
       format: "dd-mm-yyyy",
       defaultViewDate: { year: 2010, month: 1, day: 1 },
       startView: 2,
       language: "pt",
       autoclose: true,
       orientation: "bottom auto",
       container: '#data_comunhao_div'
    });
});
</script>



<script>
function verifica_codigo_postal()
{
	var cod = document.getElementById('codigo_postal').value;

	if(!codigo_postal_valido(cod, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && cod!="" && cod!=undefined)
	{ 
		$('#codigo_postal_div').addClass('has-error');
		$('#codigo_postal_div').addClass('has-feedback');
		$('#erro_postal_icon').show(); 
		return false;
	} else {
	 	$('#codigo_postal_div').removeClass('has-error');
		$('#codigo_postal_div').removeClass('has-feedback');
		$('#erro_postal_icon').hide();   
		return true;
	}
}


function verifica_data_nasc()
{
	var data_nasc = document.getElementById('data_nasc').value;

	if(!data_valida(data_nasc) && data_nasc!="" && data_nasc!=undefined)
	{ 
		$('#data_nasc_div').addClass('has-error');
		$('#data_nasc_div').addClass('has-feedback');
		$('#erro_nasc_icon').show(); 
		return false;
	} else {
	 	$('#data_nasc_div').removeClass('has-error');
		$('#data_nasc_div').removeClass('has-feedback');
		$('#erro_nasc_icon').hide();  
		return true;
	}
}

function verifica_nif()
{
    var nif = document.getElementById('nif').value;

    if(!nif_valido(nif) && nif!="" && nif!=undefined)
    {
        $('#nif_div').addClass('has-error');
        $('#nif_div').addClass('has-feedback');
        $('#erro_nif_icon').show();
        return false;
    } else {
        $('#nif_div').removeClass('has-error');
        $('#nif_div').removeClass('has-feedback');
        $('#erro_nif_icon').hide();
        return true;
    }
}

function verifica_data_baptismo()
{
	var data_bap = document.getElementById('data_baptismo').value;

	if(!data_valida(data_bap) && data_bap!="" && data_bap!=undefined)
	{ 
		$('#data_baptismo_div').addClass('has-error');
		$('#data_baptismo_div').addClass('has-feedback');
		$('#erro_data_baptismo_icon').show();  
		return false;
	} else {
	 	$('#data_baptismo_div').removeClass('has-error');
		$('#data_baptismo_div').removeClass('has-feedback');
		$('#erro_data_baptismo_icon').hide();  
		return true;
	}
}

function verifica_data_comunhao()
{
	var data_com = document.getElementById('data_comunhao').value;

	if(!data_valida(data_com) && data_com!="" && data_com!=undefined)
	{ 
		$('#data_comunhao_div').addClass('has-error');
		$('#data_comunhao_div').addClass('has-feedback');
		$('#erro_data_comunhao_icon').show();  
		return false;
	} else {
	 	$('#data_comunhao_div').removeClass('has-error');
		$('#data_comunhao_div').removeClass('has-feedback');
		$('#erro_data_comunhao_icon').hide();  
		return true;
	}
}

function verifica_telefone_pai()
{
	var telefone = document.getElementById('telefone_pai').value;

	if(!telefone_valido(telefone, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && telefone!="" && telefone!=undefined)
	{ 
		$('#telefone_pai_div').addClass('has-error');
		$('#telefone_pai_div').addClass('has-feedback');
		$('#erro_telefone_pai_icon').show(); 
		return false;
	} else {
	 	$('#telefone_pai_div').removeClass('has-error');
		$('#telefone_pai_div').removeClass('has-feedback');
		$('#erro_telefone_pai_icon').hide();  
		return true;
	}
}

function verifica_telemovel_pai()
{
	var telemovel = document.getElementById('telemovel_pai').value;

	if(!telefone_valido(telemovel, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && telemovel!="" && telemovel!=undefined)
	{ 
		$('#telemovel_pai_div').addClass('has-error');
		$('#telemovel_pai_div').addClass('has-feedback');
		$('#erro_telemovel_pai_icon').show();  
		return false;
	} else {
	 	$('#telemovel_pai_div').removeClass('has-error');
		$('#telemovel_pai_div').removeClass('has-feedback');
		$('#erro_telemovel_pai_icon').hide();  
		return true;
	}
}

function verifica_telefone_mae()
{
	var telefone = document.getElementById('telefone_mae').value;

	if(!telefone_valido(telefone, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && telefone!="" && telefone!=undefined)
	{ 
		$('#telefone_mae_div').addClass('has-error');
		$('#telefone_mae_div').addClass('has-feedback');
		$('#erro_telefone_mae_icon').show(); 
		return false;
	} else {
	 	$('#telefone_mae_div').removeClass('has-error');
		$('#telefone_mae_div').removeClass('has-feedback');
		$('#erro_telefone_mae_icon').hide();  
		return true;
	}
}

function verifica_telemovel_mae()
{
	var telemovel = document.getElementById('telemovel_mae').value;

	if(!telefone_valido(telemovel, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && telemovel!="" && telemovel!=undefined)
	{ 
		$('#telemovel_mae_div').addClass('has-error');
		$('#telemovel_mae_div').addClass('has-feedback');
		$('#erro_telemovel_mae_icon').show();  
		return false;
	} else {
	 	$('#telemovel_mae_div').removeClass('has-error');
		$('#telemovel_mae_div').removeClass('has-feedback');
		$('#erro_telemovel_mae_icon').hide();  
		return true;
	}
}

function verifica_telefone_enc_edu()
{
	var telefone = document.getElementById('telefone_enc_edu').value;

	if(!telefone_valido(telefone, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && telefone!="" && telefone!=undefined)
	{ 
		$('#telefone_enc_edu_div').addClass('has-error');
		$('#telefone_enc_edu_div').addClass('has-feedback');
		$('#erro_telefone_enc_edu_icon').show(); 
		return false;
	} else {
	 	$('#telefone_enc_edu_div').removeClass('has-error');
		$('#telefone_enc_edu_div').removeClass('has-feedback');
		$('#erro_telefone_enc_edu_icon').hide();  
		return true;
	}
}

function verifica_telemovel_enc_edu()
{
	var telemovel = document.getElementById('telemovel_enc_edu').value;

	if(!telefone_valido(telemovel, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && telemovel!="" && telemovel!=undefined)
	{ 
		$('#telemovel_enc_edu_div').addClass('has-error');
		$('#telemovel_enc_edu_div').addClass('has-feedback');
		$('#erro_telemovel_enc_edu_icon').show();  
		return false;
	} else {
	 	$('#telemovel_enc_edu_div').removeClass('has-error');
		$('#telemovel_enc_edu_div').removeClass('has-feedback');
		$('#erro_telemovel_enc_edu_icon').hide();  
		return true;
	}
}
</script>

<script>
$(document).ready(function()
{
    //Disable toggle behaviour
    $("#encarregado_educacao_collapse").collapse({ 'toggle': false });
    $("#ultimo_catecismo_collapse").collapse({ 'toggle': false });
    $("#paroquia_baptismo_collapse").collapse({ 'toggle': false });
    $("#paroquia_comunhao_collapse").collapse({ 'toggle': false });
    $("#casados_como").collapse({ 'toggle': false });
    $("#autorizacao_saida_collapse").collapse({ 'toggle': false });
    $("#quer_inscrever_collapse").collapse({ 'toggle': false });

    $("#baptizado1").click(function(){
        $("#paroquia_baptismo_collapse").collapse('show');
    });
    $("#baptizado2").click(function(){
        $("#paroquia_baptismo_collapse").collapse('hide');
    });
    $("#comunhao1").click(function(){
        $("#paroquia_comunhao_collapse").collapse('show');
    });
    $("#comunhao2").click(function(){
        $("#paroquia_comunhao_collapse").collapse('hide');
    });
    $("#casados1").click(function(){
        $("#casados_como").collapse('show');
    });
    $("#casados2").click(function(){
        $("#casados_como").collapse('hide');
    });
    $("#quer_inscrever").click(function(){
        $("#quer_inscrever_collapse").collapse('hide');
    });
    $("#quer_inscrever2").click(function(){
        $("#quer_inscrever_collapse").collapse('show');
    });

    $('[data-toggle="tooltip"]').tooltip({ container: 'body' });
});


function mostrar_ocultar_campo_outro_enc_edu()
{
    if(document.getElementById('enc_edu3').checked) //Outro
    {
        $("#encarregado_educacao_collapse").collapse('show');
    }
    else //Pai ou mae
    {
        $("#encarregado_educacao_collapse").collapse('hide');
    }
}
</script>



<!-- Carregar listas de nomes, moradas, profissoes, paroquias... --> 
<?php

	//Criar lista com nomes dos familiares existentes
    echo("<datalist id='familiares'>\n");
	try
    {
		$result = $db->getAllDistinctFamilyMemberNames();

        foreach($result as $familiar_existente)
            echo("\t<option value=\"" . $familiar_existente['nome'] . "\">\n");
	}
	catch(Exception $e)
    {
        //Simply fail silently, since this is not very important
        //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        //die();
    }
    echo("</datalist>\n");



    //Criar lista com nomes das profissoes
    echo("<datalist id='profissoes'>\n");
	try
    {
        $result = $db->getAllDistinctJobs();

        foreach ($result as $profissao_existente)
            echo("\t<option value=\"" . $profissao_existente['prof'] . "\">\n");
    }
    catch(Exception $e)
    {
        //Simply fail silently, since this is not very important
        //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        //die();
    }
    echo("</datalist>\n");


    //Criar lista com codigos postais
    echo("<datalist id='codigos_postais'>\n");
	try
    {
		$result = $db->getAllDistinctZipCodes();

        foreach ($result as $postal_existente)
            echo("\t<option value=\"" . $postal_existente['cod_postal'] . "\">\n");
	}
	catch(Exception $e)
    {
        //Simply fail silently, since this is not very important
        //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        //die();
    }
    echo("</datalist>\n");


    //Criar lista com locais de nascimento
    echo("<datalist id='locais_nascimento'>\n");
    try
    {
		$result = $db->getAllDistinctBirthPlaces();

        foreach($result as $local_existente)
            echo("\t<option value=\"" . $local_existente['local_nasc'] . "\">\n");
	}
    catch(Exception $e)
    {
        //Simply fail silently, since this is not very important
        //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        //die();
    }
    echo("</datalist>\n");


    //Criar lista com paroquias
    echo("<datalist id='paroquias'>\n");
    echo("<option value=\"" .  Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME) . "\">\n"); //Add own parish
    try
    {
		$result = $db->getAllDistinctParishes();

		if(isset($result))
		{
		    foreach($result as $paroquia_existente)
		    	echo("\t<option value=\"" . $paroquia_existente['paroquia'] . "\">\n");
		}
	}
    catch(Exception $e)
    {
        //Simply fail silently, since this is not very important
        //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        //die();
    }
    echo("</datalist>\n");



	if($_REQUEST['modo']!='editar')
	{
        //Criar array com nomes de catequizandos e script para verificar inscricoes repetidas
		try
        {
            $result = $db->getAllDistinctCatechumenNames();

            echo("<script>\n");
            echo("function verifica_catequizando_inscrito(){\n");
            echo("\tvar catequizandos = [ \n");

            foreach($result as $catequizando_existente)
                echo("\t\t\"" . $catequizando_existente['nome'] . "\",\n");

            echo("\t\t];\n");

            echo("\n\tvar nome_escrito = document.getElementById('nome').value;\n");
            echo("\tvar mensagem = document.getElementById('catequizando_inscrito');\n");
            echo("\tvar found = $.inArray(nome_escrito, catequizandos) > -1;\n");
            echo("\tif(found)\n\t{ $('#catequizando_inscrito').show(); \n\treturn false;\n\t} else {\n\t $('#catequizando_inscrito').hide(); \n\treturn true;\n\t}\n");

            echo("}\n</script>\n");
		}
        catch(Exception $e)
        {
            //Simply fail silently, since this is not very important
            //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            //die();
        }
	}
	else
	{
		 echo("<script>\n");
		 echo("function verifica_catequizando_inscrito(){\n");
		 echo("\treturn true;\n");			   
		 echo("}\n</script>\n");
	}
?>
</body>
</html>
