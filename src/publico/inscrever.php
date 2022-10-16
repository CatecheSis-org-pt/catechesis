<?php

require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../core/Utils.php');
require_once(__DIR__ . '/../core/Configurator.php');
require_once(__DIR__ . '/../authentication/securimage/securimage.php');
require_once(__DIR__ . '/../gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/../gui/widgets/Navbar/MinimalNavbar.php');
require_once(__DIR__ . '/../gui/widgets/Footer/SimpleFooter.php');
require_once(__DIR__ . '/../core/check_maintenance_mode.php'); //Check if maintenance mode is active and redirect visitor


use catechesis\Configurator;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MinimalNavbar;
use catechesis\gui\SimpleFooter;


//Verificar se o periodo de inscricoes esta ativo
$periodo_activo = false;
try
{
    $periodo_activo = Configurator::getConfigurationValueOrDefault(Configurator::KEY_ONLINE_ENROLLMENTS_OPEN);
}
catch (Exception $e)
{
}

if(!$periodo_activo)
{
    header("Location: inscricoes.php");
    die();
}


// Generate captcha ID
$captchaId = Securimage::getCaptchaId(true);


// Instantiate a widget manager
$pageUI = new WidgetManager("../");

// Add widgets
$navbar = new MinimalNavbar();
$pageUI->addWidget($navbar);
$footer = new SimpleFooter(null, true);
$pageUI->addWidget($footer);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>
  	<?php
        if($_REQUEST['modo']=='editar')
  			echo("Editar ficha");
  		else
  			echo('Inscrição na catequese');
  	?>
  </title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
  <link rel="icon" href="../img/favicon.png" type="image/x-icon">

  <?php $pageUI->renderCSS(); ?>
  <link rel="stylesheet" href="../css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">


    <style>
        @media print
        {
            .no-print, .no-print *
            {
                display: none !important;
            }
        }
    </style>
</head>
<body>


<?php $navbar->renderHTML(); ?>


<div class="container">
    <h2>
    <?php
        if($_REQUEST['modo']=='editar')
            echo("Editar ficha");
        else
            echo('Inscrição na catequese');
    ?>
    </h2>
    <h4>Ano catequético de <?= Utils::formatCatecheticalYear(Utils::currentCatecheticalYear());?></h4>

    <div class="clearfix" style="margin-bottom: 20px;"></div>
    <div class="container">

        <form role="form" onsubmit="return validar();" action="doInscrever.php<?php if($_REQUEST['modo']=='editar'){ echo('?modo=editar');}?>" method="post">

          <div class="panel panel-default" id="painel_ficha">
           <div class="panel-body">

           <div class="panel panel-default" id="painel_catequizando">
               <div class="panel-heading">Dados biográficos do catequizando</div>
               <div class="panel-body">

                  <div class="img-thumbnail" id="div_camara">
                    <img src="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $_SESSION['foto_data']!="") echo('data:image/jpeg;charset=utf-8;base64,' .  $_SESSION['foto_data']); else echo("../img/default-user-icon-profile.png");?>" class=""  alt="Foto do catequizando" width="240" height="240">
                  </div>
                  <div class="clearfix" style="margin-bottom: 10px;"></div>

                  <div class="btn-group">
                    <button type="button" class="btn btn-default" id="btn_cancelar" onclick="cancela_camara()" style="display:none;"><span class="glyphicon glyphicon-remove-circle"></span> <?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $_SESSION['foto_data']!="") echo("Repor"); else echo("Cancelar");?></button>
                    <button type="button" class="btn btn-default" id="btn_limpa" onclick="limpa_foto()" style="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $_SESSION['foto_data']!="") echo(''); else echo('display:none;')?>"><span class="glyphicon glyphicon-trash"></span> Remover foto</button>
                    <button type="button" class="btn btn-default" id="btn_configurar" onclick="Webcam.configure()" style="display:none;"><span class="glyphicon glyphicon-wrench"></span> Configurar</button>
                    <button type="button" class="btn btn-default" id="btn_reiniciar" onclick="reiniciar_camara()" style="display:none;"><span class="glyphicon glyphicon-repeat"></span> Reiniciar</button>
                    <button type="button" class="btn btn-default" id="btn_foto" onclick="prepara_camara()"><span class="glyphicon glyphicon-camera"></span> Tirar foto</button>
                    <button type="button" class="btn btn-default" id="btn_dispara" onclick="dispara()" style="display:none;"><span class="glyphicon glyphicon-camera"></span> Disparar!</button>
                  </div>


                  <div class="clearfix" style="margin-bottom: 20px;"></div>



                  <!--nome-->
                    <div class="form-group">
                    <div class="col-lg-6">
                      <label for="nome">Nome do catequizando:</label>
                      <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome completo do catequizando" value="<?php  if($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['nome'] . '');} else {echo('');} ?>" required>
                      <div class="alert alert-danger" id="catequizando_inscrito" style="display:none;"><span class="glyphicon glyphicon-exclamation-sign"></span> Catequizando já inscrito anteriormente!</div>
                    </div>
                    </div>


                   <!--data nascimento-->
                    <div class="form-group">
                     <div class="col-lg-2">
                     <div class="input-append date" id="data_nasc_div" data-date="" data-date-format="dd-mm-yyyy">
                      <label for="data_nasc">Nasceu a:</label>
                      <!--<div class="input-group">-->
                      <input class="form-control" id="data_nasc" name="data_nasc" size="16" type="text" onclick="verifica_data_nasc()" onchange="verifica_data_nasc()" placeholder="dd-mm-aaaa" value="<?php  if($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['data_nasc'] . '');} else {echo('');} ?>"  required>
                      <!--<span class="input-group-addon glyphicon glyphicon-calendar" id="sizing-addon2"></span>
                      </div>-->
                      <span id="erro_nasc_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                     </div>
                    </div>
                    </div>


                   <!--local nascimento-->
                    <div class="form-group">
                    <div class="col-lg-3">
                      <label for="localidade">Em:</label>
                      <input type="text" class="form-control" id="localidade" name="localidade" placeholder="Local de nascimento" value="<?php  if($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['local_nasc'] . '');} else {echo('');} ?>" required>
                    </div>
                    </div>


                     <!--numero irmaos-->
                    <div class="col-lg-1">
                    <div id="num_irmaos_div">
                      <label for="num_irmaos">Irmãos:</label>
                      <input type="number" min=0 class="form-control" id="num_irmaos" name="num_irmaos" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['num_irmaos'] . '');} else {echo('0');} ?>">
                    </div>
                    </div>
                    <div class="clearfix"></div>

               </div>
           </div>


           <div class="row clearfix" style="margin-bottom: 40px"></div>


           <div class="panel panel-default" id="painel_filiacao">
               <div class="panel-heading">Filiação</div>
               <div class="panel-body">

                   <!--encarregado educacao-->
                   <div class="form-group">
                       <div class="col-lg-12">
                           <div class="row" style="margin-top:20px; "></div>
                           <label for="encarregado educacao">Encarregado de educação:</label>
                           <label class="radio-inline"><input type="radio" id="enc_edu1" name="enc_edu" value="Pai"  onchange="mostrar_ocultar_campo_outro_enc_edu(); atualiza_tabela_autorizacoes();" <?php  if(($_REQUEST['modo']!='regresso' && $_REQUEST['modo']!='editar') || (($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar') && $_SESSION['enc_edu']=='Pai')){ echo('checked');} ?>>Pai</label>
                           <label class="radio-inline"><input type="radio" id="enc_edu2" name="enc_edu" value="Mae"  onchange="mostrar_ocultar_campo_outro_enc_edu(); atualiza_tabela_autorizacoes();" <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar') && $_SESSION['enc_edu']=='Mae'){ echo('checked');} ?>>Mãe</label>
                           <label class="radio-inline"><input type="radio" id="enc_edu3" name="enc_edu" value="Outro" onchange="mostrar_ocultar_campo_outro_enc_edu(); atualiza_tabela_autorizacoes();" <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar') && $_SESSION['enc_edu']=='Outro'){ echo('checked');} ?>>Outro</label>
                       </div>
                       <div class="clearfix"></div>
                   </div>


                   <!--outro encarregado educacao quem-->
                   <div class="form-group collapse <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar') && $_SESSION['enc_edu']=='Outro'){ echo('in');} ?>" id="encarregado_educacao_collapse">
                       <div class="col-lg-2">
                           <label for="outro_enc_edu_quem"> Parentesco:</label>
                           <input type="text" class="form-control" id="outro_enc_edu_quem" name="outro_enc_edu_quem" placeholder="Ex: Avó" list="parentesco" onchange="atualiza_tabela_autorizacoes()" value="<?php  if($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['outro_enc_edu_quem'] . '');} else {echo('');} ?>">
                       </div>
                       <datalist id='parentesco'>
                           <option value='Avô'>
                           <option value='Avó'>
                           <option value='Irmão'>
                           <option value='Irmã'>
                           <option value='Tutor'>
                       </datalist>

                       <!--outro encarregado educacao nome-->
                       <div class="col-lg-6">
                           <label for="nome_end_edu"> Nome:</label>
                           <input type="text" class="form-control" id="nome_enc_edu" name="nome_enc_edu" placeholder="Nome completo do encarregado de educação" onchange="atualiza_tabela_autorizacoes()" value="<?php  if($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['nome_enc_edu'] . '');} else {echo('');} ?>">
                       </div>

                       <!--outro encarregado educacao profissao-->
                       <div class="col-lg-4">
                           <label for="prof_enc_edu"> Profissão:</label>
                           <input type="text" class="form-control" id="prof_enc_edu" name="prof_enc_edu" placeholder="Profissão do encarregado de educação" value="<?php  if($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['prof_enc_edu'] . '');} else {echo('');} ?>">
                       </div>
                   </div>
                   <div class="clearfix"></div>







                   <!--pai-->
                   <div class="form-group">
                       <div class="col-lg-8">
                           <label for="pai">Pai:</label>
                           <input type="text" class="form-control" id="pai" name="pai" placeholder="Nome completo do pai"  onchange="atualiza_tabela_autorizacoes()" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['pai'] . '');} else {echo('');} ?>">
                       </div>

                       <!--profissao pai-->
                       <div class="col-lg-4">
                           <label for="prof_pai">Profissão:</label>
                           <input type="text" class="form-control" id="prof_pai" name="prof_pai" placeholder="Profissão do pai" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['prof_pai'] . '');} else {echo('');} ?>">
                       </div>
                   </div>



                   <!--mae-->
                   <div class="form-group">
                       <div class="col-lg-8">
                           <label for="mae">Mãe:</label>
                           <input type="text" class="form-control" id="mae" name="mae" placeholder="Nome completo da mãe" onchange="atualiza_tabela_autorizacoes()" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['mae'] . '');} else {echo('');} ?>">
                       </div>

                       <!--profissao mae-->
                       <div class="col-lg-4">
                           <label for="prof_mae">Profissão:</label>
                           <input type="text" class="form-control" id="prof_mae" name="prof_mae" placeholder="Profissão da mãe" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['prof_mae'] . '');} else {echo('');} ?>">
                       </div>
                       <div class="clearfix"></div>
                   </div>



                   <!--casados-->
                   <div class="form-group">
                       <div class="col-lg-8">
                           <label for="casados">Casados:</label>
                           <label class="radio-inline"><input type="radio" id="casados1" name="casados" value="Sim" <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='editar') && $_SESSION['casados']=='Sim'){ echo('checked');} ?>>Sim</label>
                           <label class="radio-inline"><input type="radio" id="casados2" name="casados" value="Nao" <?php  if(($_REQUEST['modo']!='regresso' && $_REQUEST['modo']!='irmao' && $_REQUEST['modo']!='editar') || (($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='editar') && $_SESSION['casados']!='Sim')){ echo('checked');} ?>>Não</label>
                       </div>
                       <div class="clearfix"></div>
                   </div>


                   <!--casados como-->
                   <div class="form-group collapse <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='editar') && $_SESSION['casados']=='Sim'){ echo('in');} ?>" id="casados_como">
                       <div class="col-lg-8">
                           <label for="casados_como">Se respondeu <i>Sim</i> indique:</label>
                           <label class="radio-inline"><input type="radio" name="casados_como" value="igreja" <?php  if(($_REQUEST['modo']!='regresso' && $_REQUEST['modo']!='irmao' &&  $_REQUEST['modo']=='editar') || ($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='editar') && $_SESSION['casados_como']=='igreja'){ echo('checked');} ?>>Igreja</label>
                           <label class="radio-inline"><input type="radio" name="casados_como" value="civil"  <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='editar') && $_SESSION['casados_como']=='civil'){ echo('checked');} ?>>Civil</label>
                           <label class="radio-inline"><input type="radio" name="casados_como" value="uniao de facto" <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='editar') && $_SESSION['casados_como']=='uniao de facto'){ echo('checked');} ?> >União de facto</label>
                       </div>
                       <div class="clearfix"></div>
                   </div>



                   <div class="row clearfix" style="margin-bottom: 40px"></div>



                   <!--morada-->
                    <div class="form-group">
                    <div class="col-lg-12">
                      <label for="morada">Morada:</label>
                      <input type="text" class="form-control" id="morada" name="morada" placeholder="Morada do encarregado de educação" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['morada'] . '');} else {echo('');} ?>" required>
                    </div>
                    </div>



                    <!--codigo postal-->
                    <div class="form-group">
                        <div class="col-lg-4">
                        <div id="codigo_postal_div">
                          <label for="codigo_postal">Código postal:</label>
                          <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="xxxx-xxx Localidade" onclick="verifica_codigo_postal()" onchange="verifica_codigo_postal()" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['cod_postal'] . '');} else {echo('');} ?>" required>
                          <span id="erro_postal_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                        </div>
                        </div>



                        <!--telefone-->
                        <div class="col-lg-2">
                        <div id="telefone_div">
                          <label for="tel">Telefone:</label>
                          <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="Telefone do encarregado de educação" onclick="verifica_telefone()" onchange="verifica_telefone(); atualiza_tabela_autorizacoes();" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['telefone'] . '');} else {echo('');} ?>">
                          <span id="erro_telefone_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                        </div>
                        </div>



                        <!--telemovel-->
                        <div class="col-lg-2">
                            <div id="telemovel_div">
                              <label for="telm">Telemóvel:</label>
                              <input type="tel" class="form-control" id="telemovel" name="telemovel" placeholder="Telemóvel do encarregado de educação" onclick="verifica_telemovel()" onchange="verifica_telemovel(); atualiza_tabela_autorizacoes();" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['telemovel'] . '');} else {echo('');} ?>">
                              <span id="erro_telemovel_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </div>


                       <div class="row clearfix" style="margin-bottom: 20px"></div>


                   <!--email-->
                   <div class="form-group">
                       <div class="col-lg-12">
                           <label for="email">E-mail:</label>
                           <input type="email" class="form-control" id="email" name="email" placeholder="endereco@example.com" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['email'] . '');} else {echo('');} ?>">
                           <span>Para que seja informado de notícias e actividades da nossa catequese, indique-nos o seu e-mail. Assim poderá organizar melhor a sua vida e planear a sua agenda.</span>
                       </div>
                       <div class="clearfix"></div>
                   </div>

               </div>
           </div>

          <div class="row clearfix" style="margin-bottom: 40px"></div>



           <div class="panel panel-default" id="painel_percurso_catequetico">
               <div class="panel-heading">Percurso catequético</div>
               <div class="panel-body">

                   <!--ja frequentou a catequese-->
                   <div class="form-group">
                       <div class="col-lg-12">
                           <div class="row" style="margin-top:20px; "></div>
                           <label for="ja_frequentou_catequese">Já frequentou a catequese (noutra paróquia):</label>
                           <label class="radio-inline"><input type="radio" id="ja_frequentou_catequese1" name="ja_frequentou_catequese" value="Sim" <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['ja_frequentou_catequese']=='Sim'){ echo('checked');} ?>>Sim</label>
                           <label class="radio-inline"><input type="radio" id="ja_frequentou_catequese2" name="ja_frequentou_catequese" value="Nao" <?php  if($_REQUEST['modo']!='regresso' || ($_REQUEST['modo']=='regresso' && $_SESSION['ja_frequentou_catequese']!='Sim')){ echo('checked');} ?>>Não</label>
                       </div>
                       <div class="clearfix"></div>
                       <div class="form-group collapse <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['ja_frequentou_catequese']=='Sim'){ echo('in');} ?>" id="ultimo_catecismo_collapse">
                           <div class="col-lg-3">
                               <label for="ultimo_catecismo">Último catecismo que <u>frequentou</u>:</label>
                               <select id="ultimo_catecismo" name="ultimo_catecismo" class="form-control" >
                                   <?php
                                   for($i = 1; $i <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)) - 1; $i++)
                                   {
                                       ?>
                                       <option value="<?= $i ?>" <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['ultimo_catecismo']==1){ echo('selected');} ?>><?= $i ?>º catecismo</option>
                                       <?php
                                   }
                                   ?>
                               </select>
                           </div>
                           <div class="row" style="margin-bottom:20px; "></div>
                       </div>
                   </div>


                   <div class="row" style="margin-bottom:20px; "></div>


                    <!--baptizado-->
                    <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
                    <div class="form-group">
                        <div class="col-lg-8">
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
                        <div class="col-lg-4">
                          <label for="paroquia_baptismo"> Paróquia de baptismo: </label>
                          <input type="text" class="form-control" id="paroquia_baptismo" name="paroquia_baptismo" placeholder="Paróquia de baptismo"  value="<?php  if($_REQUEST['modo']=='regresso'){ echo('' . $_SESSION['paroquia_baptismo'] . '');} else {echo('');} ?>">
                        </div>
                         <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>

                        <!--data de baptismo-->
                        <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
                        <div class="form-group">
                            <div class="col-lg-2">
                                <div class="input-append date" id="data_baptismo_div" data-date="" data-date-format="dd-mm-yyyy">
                                    <label for="data_baptismo">Data:</label>
                                    <input class="form-control" id="data_baptismo" name="data_baptismo" size="16" type="text" onclick="verifica_data_baptismo()" onchange="verifica_data_baptismo()" placeholder="dd-mm-aaaa" value="<?php  if($_REQUEST['modo']=='regresso'){ echo('' . $_SESSION['data_baptismo'] . '');} else {echo('');} ?>" >
                                    <span id="erro_data_baptismo_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-bottom:20px; "></div>
                    </div>
                    <div class="clearfix"></div>
                    <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>


                   <div class="row" style="margin-bottom:20px; "></div>


                    <!--primeira comunhao-->
                    <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
                    <div class="form-group">
                        <div class="col-lg-8">
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
                        <div class="col-lg-4">
                          <label for="paroquia_comunhao"> Paróquia 1ª comunhão:</label>
                          <input type="text" class="form-control" id="paroquia_comunhao" name="paroquia_comunhao" placeholder="Paróquia 1ª comunhão"  value="<?php  if($_REQUEST['modo']=='regresso'){ echo('' . $_SESSION['paroquia_comunhao'] . '');} else {echo('');} ?>">
                        </div>
                        <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>

                        <!--data primeira comunhao-->
                        <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
                        <div class="form-group">
                            <div class="col-lg-2">
                                <div class="input-append date" id="data_comunhao_div" data-date="" data-date-format="dd-mm-yyyy">
                                    <label for="data_comunhao">Data:</label>
                                    <input class="form-control" id="data_comunhao" name="data_comunhao" size="16" type="text" onclick="verifica_data_comunhao()" onchange="verifica_data_comunhao()" placeholder="dd-mm-aaaa" value="<?php  if($_REQUEST['modo']=='regresso'){ echo('' . $_SESSION['data_comunhao'] . '');} else {echo('');} ?>" >
                                    <span id="erro_data_comunhao_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row clearfix" style="margin-bottom: 40px"></div>
                    </div>
                    <div class="clearfix"></div>
                    <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>

                   <div class="row" style="margin-bottom:20px; "></div>

                   <!--escuteiro-->
                   <div class="form-group">
                       <div class="col-lg-8">
                           <label for="e_escuteiro">É escuteiro(a):</label>
                           <label class="radio-inline"><input type="radio" name="escuteiro" value="Sim" <?php  if(($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar') && ($_SESSION['escuteiro']=='Sim' || $_SESSION['escuteiro']==1)){ echo('checked');} ?>>Sim</label>
                           <label class="radio-inline"><input type="radio" name="escuteiro" value="Nao" <?php  if(($_REQUEST['modo']!='regresso' && $_REQUEST['modo']!='editar') || (($_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar') && $_SESSION['escuteiro']!='Sim' && $_SESSION['escuteiro']!=1)){ echo('checked');} ?>>Não</label>
                       </div>
                       <div class="clearfix"></div>
                   </div>

               </div>
           </div>


           <div class="row clearfix" style="margin-bottom: 40px"></div>



           <div class="panel panel-default" id="painel_autorizacoes_menores">
               <div class="panel-heading">Autorização de saída de menores</div>
               <div class="panel-body">

                   <!-- Autorizacao saida menores -->
                   <div class="form-group">
                       <div class="col-lg-12">
                           <div class="row" style="margin-top:20px; "></div>
                           <label for="autorizacao_saida">O seu educando pode sair da igreja sozinho?:</label>
                           <label class="radio-inline"><input type="radio" id="autorizacao_saida1" name="autorizacao_saida" value="Nao" <?php  if($_REQUEST['modo']!='regresso' || ($_REQUEST['modo']=='regresso' && $_SESSION['autorizacao_saida']!='Sim')){ echo('checked');} ?>>Não</label>
                           <label class="radio-inline"><input type="radio" id="autorizacao_saida2" name="autorizacao_saida" value="Sim" <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['autorizacao_saida']=='Sim'){ echo('checked');} ?>>Sim</label>
                       </div>
                       <div class="clearfix"></div>
                       <div class="form-group collapse <?php  if($_REQUEST['modo']=='regresso' && $_SESSION['autorizacao_saida']=='Sim'){}else{ echo('in');} ?>" id="autorizacao_saida_collapse">
                           <div class="col-lg-12">
                               <label for="ultimo_catecismo">Quem pode vir buscar o seu educando?</label>
                               <table class="table table-hover">
                                   <thead>
                                   <tr>
                                       <th>Nome</th>
                                       <th>Parentesco</th>
                                       <th>Telemóvel</th>
                                   </tr>
                                   </thead>
                                   <tbody>
                                   <tr>
                                       <td><span id="autorizacao_nome_enc_edu"></span></td>
                                       <td><span id="autorizacao_parentesco_enc_edu"></span></td>
                                       <td><span id="autorizacao_tel_enc_edu"></span></td>
                                   </tr>
                                   <tr>
                                       <td><input type="text" class="form-control" id="autorizacao_nome_1" name="autorizacao_nome[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_nome'][0] . '');} else {echo('');} ?>"></td>
                                       <td><input type="text" class="form-control" id="autorizacao_parentesco_1" name="autorizacao_parentesco[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_parentesco'][0] . '');} else {echo('');} ?>"></td>
                                       <td><input type="tel" class="form-control" id="autorizacao_telefone_1" name="autorizacao_telefone[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_telefone'][0] . '');} else {echo('');} ?>"></td>
                                   </tr>
                                   <tr>
                                       <td><input type="text" class="form-control" id="autorizacao_nome_1" name="autorizacao_nome[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_nome'][1] . '');} else {echo('');} ?>"></td>
                                       <td><input type="text" class="form-control" id="autorizacao_parentesco_1" name="autorizacao_parentesco[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_parentesco'][1] . '');} else {echo('');} ?>"></td>
                                       <td><input type="tel" class="form-control" id="autorizacao_telefone_1" name="autorizacao_telefone[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_telefone'][1] . '');} else {echo('');} ?>"></td>
                                   </tr><tr>
                                       <td><input type="text" class="form-control" id="autorizacao_nome_1" name="autorizacao_nome[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_nome'][2] . '');} else {echo('');} ?>"></td>
                                       <td><input type="text" class="form-control" id="autorizacao_parentesco_1" name="autorizacao_parentesco[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_parentesco'][2] . '');} else {echo('');} ?>"></td>
                                       <td><input type="tel" class="form-control" id="autorizacao_telefone_1" name="autorizacao_telefone[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_telefone'][2] . '');} else {echo('');} ?>"></td>
                                   </tr><tr>
                                       <td><input type="text" class="form-control" id="autorizacao_nome_1" name="autorizacao_nome[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_nome'][3] . '');} else {echo('');} ?>"></td>
                                       <td><input type="text" class="form-control" id="autorizacao_parentesco_1" name="autorizacao_parentesco[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_parentesco'][3] . '');} else {echo('');} ?>"></td>
                                       <td><input type="tel" class="form-control" id="autorizacao_telefone_1" name="autorizacao_telefone[]" placeholder="" value="<?php  if($_REQUEST['modo']=='irmao' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='editar'){ echo('' . $_SESSION['autorizacao_telefone'][3] . '');} else {echo('');} ?>"></td>
                                   </tr>
                                   </tbody>
                               </table>
                           </div>
                       </div>
                   </div>

               </div>
           </div>



           <div class="row clearfix" style="margin-bottom: 40px"></div>


           <div class="panel panel-default" id="painel_outros">
               <div class="panel-heading">Outros assuntos</div>
               <div class="panel-body">

                   <!-- Observacoes -->
                   <div class="form-group">
                       <div class="col-lg-12">
                           <label for="observacoes">Observações: (opcional)</label>
                           <textarea class="form-control" id="observacoes" name="observacoes" rows="4" placeholder="Escreva aqui quaisquer observações que considere pertinentes para a catequese.&#10;Ex: o seu educando tem necessidades especiais?" style="cursor: auto;" value=""></textarea>
                       </div>
                   </div>

                   <div class="row clearfix" style="margin-bottom:40px; "></div>


                   <?php
                   $enrollmentCustomText = Configurator::getConfigurationValueOrDefault(Configurator::KEY_ENROLLMENT_CUSTOM_TEXT);
                   if(isset($enrollmentCustomText) && $enrollmentCustomText != '')
                   {
                       ?>
                       <!-- Informacoes -->
                       <div class="form-group">
                           <div class="col-lg-12">
                               <label for="donativo">Informações:</label>
                               <div class="well">
                                   <?= $enrollmentCustomText ?>
                               </div>
                           </div>
                       </div>
                       <?php
                   }
                   ?>

               </div>
           </div>

           <div class="row clearfix" style="margin-bottom:40px; "></div>


           <!-- Autorizacoes -->
           <div class="form-group">
               <div class="col-lg-12">
                   <input id="autorizacao_fotos" name="autorizacao_fotos" type="checkbox" style="cursor: auto;" value="aceito" <?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso' || $_REQUEST['modo']=='irmao') && ($_SESSION['autorizacao_fotos']=='aceito' || $_SESSION['autorizacao_fotos']=='on' || $_SESSION['autorizacao_fotos']==1)){ echo("checked"); }?>/>
                   <label for="autorizacao_fotos" style="display: contents;">Autorizo a utilização e divulgação de fotografias do meu educando, tiradas no âmbito das atividades catequéticas.</label>
               </div>
               <div class="col-lg-12">
                   <input type="checkbox" id="declaracao_enc_edu" name="declaracao_enc_edu" style="cursor: auto;" value="aceito" required <?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && ($_SESSION['declaracao_enc_edu']=='aceito' || $_SESSION['declaracao_enc_edu']=='on' || $_SESSION['declaracao_enc_edu']==1)){ echo("checked"); }?>/>
                   <label for="declaracao_enc_edu" style="display: contents;">Declaro que sou titular das responsabilidades parentais ou representante legal do meu educando, ou que tenho as devidas autorizações para efetuar esta inscrição.</label>
               </div>
               <div class="col-lg-12">
                   <input type="checkbox" id="rgpd" name="rgpd" style="cursor: auto;" value="aceito" required <?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && ($_SESSION['rgpd']=='aceito' || $_SESSION['rgpd']=='on' || $_SESSION['rgpd']==1)){ echo("checked"); }?>/>
                   <label for="rgpd" style="display: contents;">Declaro que aceito o tratamento dos meus dados pessoais e do meu educando, de acordo com o disposto no <a href="descarregarDeclaracaoRGPD.php" target="_blank" rel="noopener noreferrer">documento emitido pela <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?></a> acerca do cumprimento do Regulamento Geral de Proteção de Dados.</label>
               </div>
           </div>

           <div class="row clearfix" style="margin-bottom:30px; "></div>


           <!-- Captcha -->
           <div class="form-group">

               <div class="col-lg-12">
                   <label for="captcha_code">Introduza o código que vê nesta imagem na caixa abaixo:</label>
                   <img class="img-responsive" id="captcha" src="../authentication/securimage/generate_captcha.php?display=1&amp;captchaId=<?= $captchaId ?>" alt="CAPTCHA Image" />
                   <a href="#" onclick="refreshCaptcha(); return false;" data-toggle="tooltip" data-placement="top" title="A imagem está difícil de ler? Peça outra!"><span class="glyphicon glyphicon-refresh"></span> Carregar outra imagem </a>
               </div>
               <div class="row clearfix" style="margin-bottom:10px; "></div>
               <div class="col-lg-2">
                   <input type="hidden" id="captchaId" name="captchaId" value="<?php echo $captchaId ?>" />
                   <input type="text" class="form-control" id="captcha_code" name="captcha_code" size="10" maxlength="6" placeholder="xxxxxx" style="cursor: auto;" required/>
               </div>
           </div>


           <div class="row clearfix" style="margin-bottom:20px; "></div>

            <!-- fotografia tirada com webcam -->
            <input type="hidden" id="foto_data" name="foto_data" value="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $_SESSION['foto_data']!="") echo($_SESSION['foto_data']);?>">
            <input type="hidden" id="original_foto_data" name="original_foto_data" value="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $_SESSION['foto_data']!="") echo($_SESSION['foto_data']);?>">

            </div>
          </div>

            <?php 	if($_REQUEST['modo']=='editar')
                    echo('<button type="button" class="btn btn-default glyphicon glyphicon-remove" onclick="window.location.assign(\'../mostrarFicha.php?cid=' . $_SESSION['cid'] . '\');"> Cancelar</button>');
            ?>

            <?php 	if($_REQUEST['modo']=='editar')
                    echo("<button type=\"submit\" class=\"btn btn-primary glyphicon glyphicon-floppy-disk\"> Guardar</button>");
                else
                    echo("<button type=\"submit\" class=\"btn btn-primary glyphicon glyphicon-pencil\"> Inscrever</button>");
            ?>

            <div class="row" style="margin-top: 40px"></div>
            <p class="no-print"><span class="glyphicon glyphicon-circle-arrow-left"></span> <a href="inscricoes.php">&nbsp; Voltar à página principal de inscrições</a></p>

            <div style="margin-bottom: 60px;"></div>

          </form>

        <div class="row" style="margin-bottom:80px; "></div>
    </div>
</div>

<?php
$footer->renderHTML();
?>



<?php $pageUI->renderJS(); ?>
<script src="../js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="../js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>
<script type="text/javascript" src="../webcamjs-master/webcam.js"></script>
<script src="../js/form-validation-utils.js"></script>

<script>

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
  	document.getElementById('div_camara').innerHTML = "<img src=\"<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $_SESSION['foto_data']!="") echo('data:image/jpeg;charset=utf-8;base64,' . $_SESSION['foto_data']); else echo("img/default-user-icon-profile.png");?>\" class=\"\"  alt=\"Foto do catequizando\" width=\"240\" height=\"240\">";
  	document.getElementById('btn_configurar').style.display="none";
    document.getElementById('btn_cancelar').style.display="none";
    document.getElementById('btn_dispara').style.display="none";
    document.getElementById('btn_reiniciar').style.display="none";
    document.getElementById('btn_limpa').style.display="<?php if(($_REQUEST['modo']=='editar' || $_REQUEST['modo']=='regresso') && $_SESSION['foto_data']!="") echo('inline'); else echo('none')?>";
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
  document.getElementById('div_camara').innerHTML = "<img src=\"../img/default-user-icon-profile.png\" class=\"\"  alt=\"Foto do catequizando\" width=\"240\" height=\"240\">";
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
	var telefone = document.getElementById('telefone').value;
    var telemovel = document.getElementById('telemovel').value;
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
   	<?php endif ?>
	
	if(!data_valida(data_nasc))
        {
        	alert("A data de nascimento que introduziu é inválida. Deve ser da forma dd-mm-aaaa.");
        	return false;
        }
        
        
	if(!codigo_postal_valido(cod_postal))
	{
		alert("O código postal que introduziu é inválido. Deve ser da forma 'xxxx-yyy Localidade'.");
		return false;
	}
                
        
        
        if((telefone==="" || telefone===undefined) && (telemovel==="" || telemovel===undefined) )
        {
		alert("Deve introduzir pelo menos um número de telefone ou telemóvel.");
		return false; 
        }
        else if(telefone!=="" && telefone!==undefined && !telefone_valido(telefone))
        {
        	alert("O número de telefone que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
		return false; 
        }
        else if(telemovel!=="" && telemovel!==undefined && !telefone_valido(telemovel))
        {
        	alert("O número de telemóvel que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
		return false; 
        }
        
        
        <?php if($_REQUEST['modo']!='editar') :?>
        if( baptizado && (paroquia_baptismo==="" || paroquia_baptismo===undefined))
        {
        	alert("Deve especificar a paróquia de baptismo.");
		return false; 
        }
        
        if( baptizado && (data_baptismo==="" || data_baptismo===undefined))
        {
        	alert("Deve especificar a data de baptismo.");
		return false; 
        }
        
        if( baptizado && !data_valida(data_baptismo))
        {
        	alert("A data de baptismo que introduziu é inválida. Deve ser da forma dd-mm-aaaa.");
		return false; 
        }
        
        if( comunhao  && (paroquia_comunhao==="" || paroquia_comunhao===undefined))
        {
        	alert("Deve especificar a paróquia onde realizou a primeira comunhão.");
		return false; 
        }
        
        if( comunhao  && (data_comunhao==="" || data_comunhao===undefined))
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
        
        
        if( (enc_edu_pai && (pai==="" || pai===undefined)) || (enc_edu_mae && (mae==="" || mae===undefined)) )
	{
		alert("Deve especificar o nome e profissão do encarregado de educação.");
		return false; 
	}
        
        
	if( enc_edu_outro && ((enc_edu_parentesco==="" || enc_edu_parentesco===undefined) || (enc_edu_nome==="" || enc_edu_nome===undefined) || (enc_edu_prof==="" || enc_edu_prof===undefined)) )
	{
		alert("Deve especificar o grau de parentesco, nome e profissão do encarregado de educação.");
		return false; 
	}
        
        
        if( (pai!=="" && pai!==undefined) && (prof_pai==="" || prof_pai===undefined) )
        {
		alert("Deve especificar a profissão do pai.");
		return false; 
        }
        
        if( (prof_pai!=="" && prof_pai!==undefined) && (pai==="" || pai===undefined) )
        {
		alert("Deve especificar o nome do pai, além da profissão.");
		return false; 
        }
        
        if( (mae!== "" && mae!==undefined) && (prof_mae=== "" || prof_mae===undefined) )
        {
		alert("Deve especificar a profissão da mãe.");
		return false; 
        }
        
         if( (prof_mae!== "" && prof_mae!==undefined) && (mae=== "" || mae===undefined) )
        {
		alert("Deve especificar o nome da mãe, além da profissão.");
		return false; 
        }
        
        return true;
        
}
</script>




<script>
$(function(){
   $('#data_nasc').datepicker({
       format: "dd-mm-yyyy",
       defaultViewDate: { year: 2010, month: 1, day: 1 },
       startView: 2,
       language: "pt",
       autoclose: true
    });
});

$(function(){
   $('#data_baptismo').datepicker({
       format: "dd-mm-yyyy",
       defaultViewDate: { year: 2010, month: 1, day: 1 },
       startView: 2,
       language: "pt",
       autoclose: true
    });
});

$(function(){
   $('#data_comunhao').datepicker({
       format: "dd-mm-yyyy",
       defaultViewDate: { year: 2010, month: 1, day: 1 },
       startView: 2,
       language: "pt",
       autoclose: true
    });
});
</script>



<script>
function verifica_codigo_postal()
{
	var cod = document.getElementById('codigo_postal').value;
	
	if(!codigo_postal_valido(cod) && cod!="" && cod!=undefined)
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

function verifica_telefone()
{
	var telefone = document.getElementById('telefone').value;
	
	if(!telefone_valido(telefone) && telefone!="" && telefone!=undefined)
	{ 
		$('#telefone_div').addClass('has-error');
		$('#telefone_div').addClass('has-feedback');
		$('#erro_telefone_icon').show(); 
		return false;
	} else {
	 	$('#telefone_div').removeClass('has-error');
		$('#telefone_div').removeClass('has-feedback');
		$('#erro_telefone_icon').hide();  
		return true;
	}
}

function verifica_telemovel()
{
	var telemovel = document.getElementById('telemovel').value;
	
	if(!telefone_valido(telemovel) && telemovel!="" && telemovel!=undefined)
	{ 
		$('#telemovel_div').addClass('has-error');
		$('#telemovel_div').addClass('has-feedback');
		$('#erro_telemovel_icon').show();  
		return false;
	} else {
	 	$('#telemovel_div').removeClass('has-error');
		$('#telemovel_div').removeClass('has-feedback');
		$('#erro_telemovel_icon').hide();  
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


    $("#ja_frequentou_catequese1").click(function(){
        $("#ultimo_catecismo_collapse").collapse('show');
    });
    $("#ja_frequentou_catequese2").click(function(){
        $("#ultimo_catecismo_collapse").collapse('hide');
    });
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
    $("#autorizacao_saida1").click(function(){
        $("#autorizacao_saida_collapse").collapse('show');
    });
    $("#autorizacao_saida2").click(function(){
        $("#autorizacao_saida_collapse").collapse('hide');
    });
    $("#quer_inscrever").click(function(){
        $("#quer_inscrever_collapse").collapse('hide');
    });
    $("#quer_inscrever2").click(function(){
        $("#quer_inscrever_collapse").collapse('show');
    });
});
</script>



<script>

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

    function atualiza_tabela_autorizacoes()
    {
        var enc_edu_nome = "";
        var enc_edu_parentesco = "";
        var telemovel = document.getElementById('telemovel').value;
        var telefone = document.getElementById('telefone').value;
        var enc_edu_tel = 0;

        if(document.getElementById('enc_edu1').checked) //Pai
        {
            enc_edu_nome = document.getElementById('pai').value;
            enc_edu_parentesco = "Pai";
        }
        else if(document.getElementById('enc_edu2').checked) //Mae
        {
            enc_edu_nome = document.getElementById('mae').value;
            enc_edu_parentesco = "Mãe";
        }
        else if(document.getElementById('enc_edu3').checked) //Outro
        {
            enc_edu_nome = document.getElementById('nome_enc_edu').value;
            enc_edu_parentesco = document.getElementById('outro_enc_edu_quem').value;
        }

        if(telemovel!==undefined && telemovel!=="")
            enc_edu_tel = telemovel;
        else
            enc_edu_tel = telefone;

        var autorizacao_nome_enc_edu = document.getElementById('autorizacao_nome_enc_edu');
        var autorizacao_parentesco_enc_edu = document.getElementById('autorizacao_parentesco_enc_edu');
        var autorizacao_tel_enc_edu = document.getElementById('autorizacao_tel_enc_edu');

        autorizacao_nome_enc_edu.innerText = enc_edu_nome;
        autorizacao_parentesco_enc_edu.innerText = enc_edu_parentesco + " (Encarregado de educação)";
        autorizacao_tel_enc_edu.innerText = enc_edu_tel;
    }
</script>

<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>

<script>
    function refreshCaptcha()
    {
        $.ajax({ url: '../authentication/securimage/generate_captcha.php?refresh=1',
            dataType: 'json',
        }).done(function(data) {
            var src = '../authentication/securimage/generate_captcha.php?display=1&captchaId=' + data.captchaId + '&rand=' + Math.random();
            $('#captcha').attr('src', src); // replace image with new captcha
            $('#captchaId').attr('value', data.captchaId); // update hidden form field
        });
    }
</script>
    
<!-- Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent -->
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Este sítio utiliza cookies para melhorar a sua experiência de navegação. <br>Ao continuar está a consentir essa utilização.","dismiss":"Aceito","learnMore":"Mais info","link":null,"theme":"light-floating"};
</script>

<script type="text/javascript" src="../js/cookieconsent2-1.0.10/cookieconsent.min.js"></script>
<!-- End Cookie Consent plugin -->

</body>
</html>