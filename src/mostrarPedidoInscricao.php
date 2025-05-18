<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/domain/Locale.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/core/enrollment_functions.php');
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . "/gui/widgets/Navbar/MainNavbar.php");

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use core\domain\Locale;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;


// Start a secure session if none is running
Authenticator::startSecureSession();

// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::ENROLMENTS);
$pageUI->addWidget($menu);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Pedido de inscrição</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">

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


<?php

$menu->renderHTML();

$db = new PdoDatabaseManager();

?>


<div class="container">
  <h2>Pedido de inscrição</h2>
  
  <div class="clearfix" style="margin-bottom: 20px;"></div>
  <div class="container">


  <?php

    if(!Authenticator::isAdmin())
    {
      echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
      echo("</div></body></html>");
      die();
    }

    $iid = intval(Utils::sanitizeInput($_REQUEST["iid"]));

    if(!isset($_REQUEST["iid"]) || $iid < 0)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não foi especificado nenhum pedido de inscrição para mostrar.</div>");
        die();
    }

    try
    {
        $submission = $db->getEnrollmentSubmission($iid);
    }
    catch(Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }


    if(isset($submission['cid']))
    {
      ?>
      <div class="alert alert-success"><a href="#" class="close" data-dismiss="alert">&times;</a> Este pedido de inscrição já foi processado.
          <?php if($submission['cid'] >= 0)
          {?>
              <a href="mostrarFicha.php?cid=<?php echo($submission['cid']);?>">Ver ficha</a>
              <?php
          }
          ?>
      </div>
      <?php
  }
  ?>

<form role="form" onsubmit="return validar();" action="processarInscricao.php?modo=aprovar" method="post">

  <div class="panel panel-default" id="painel_ficha">
   <div class="panel-body">


   <div class="panel panel-default" id="painel_catequizando">
       <div class="panel-heading">Dados biográficos do catequizando</div>
       <div class="panel-body">

          <div class="img-thumbnail" id="div_camara">
            <img src="<?php if($submission['foto']!="") echo("resources/catechumenPhoto.php?foto_name=" . $submission['foto'] ); else echo("img/default-user-icon-profile.png");?>" class=""  alt="Foto do catequizando" width="240" height="240">
          </div>

          <div class="clearfix" style="margin-bottom: 20px;"></div>



          <!--nome-->
            <div class="form-group">
            <div class="col-lg-5">
              <label for="nome">Nome do catequizando:</label>
              <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome completo do catequizando" value="<?php echo($submission['nome']);?>" readonly required>
              <div class="alert alert-danger" id="catequizando_inscrito" style="display:none;"><span class="glyphicon glyphicon-exclamation-sign"></span> Catequizando já inscrito anteriormente!</div>
            </div>
            </div>


           <!--data nascimento-->
            <div class="form-group">
             <div class="col-lg-2">
             <div class="input-append date" id="data_nasc_div" data-date="" data-date-format="dd-mm-yyyy">
              <label for="data_nasc">Nasceu a:</label>
              <!--<div class="input-group">-->
              <input class="form-control" id="data_nasc" name="data_nasc" size="16" type="text" onclick="verifica_data_nasc()" onchange="verifica_data_nasc()" placeholder="dd-mm-aaaa" value="<?php echo(date( "d-m-Y", strtotime($submission['data_nasc'])));?>" readonly  required>
              <!--<span class="input-group-addon glyphicon glyphicon-calendar" id="sizing-addon2"></span>
              </div>-->
              <span id="erro_nasc_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
             </div>
            </div>
            </div>


           <!--local nascimento-->
            <div class="form-group">
            <div class="col-lg-2">
              <label for="localidade">Em:</label>
              <input type="text" class="form-control" id="localidade" name="localidade" placeholder="Local de nascimento" value="<?php echo($submission['local_nasc']);?>" readonly required>
            </div>
            </div>

           <!--NIF-->
           <div class="col-xs-2">
               <div id="nif_div">
                   <label for="nif">NIF:</label>
                   <input type="text" class="form-control" id="nif" name="nif" placeholder="NIF do catequizando" value="<?= $submission['nif'] ?>" readonly>
               </div>
           </div>


             <!--numero irmaos-->
            <div class="col-lg-1">
            <div id="num_irmaos_div">
              <label for="num_irmaos">Irmãos:</label>
              <input type="number" min=0 class="form-control" id="num_irmaos" name="num_irmaos" value="<?php echo($submission['num_irmaos']);?>" readonly>
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
                   <label class="radio-inline"><input type="radio" id="enc_edu1" name="enc_edu" value="Pai"  onchange="atualiza_tabela_autorizacoes()" <?php  if($submission['enc_edu']==0){ echo('checked');}else{echo("disabled");} ?> readonly>Pai</label>
                   <label class="radio-inline"><input type="radio" id="enc_edu2" name="enc_edu" value="Mae"  onchange="atualiza_tabela_autorizacoes()" <?php  if($submission['enc_edu']==1){ echo('checked');}else{echo("disabled");} ?> readonly>Mãe</label>
                   <label class="radio-inline"><input type="radio" id="enc_edu3" name="enc_edu" value="Outro" onchange="atualiza_tabela_autorizacoes()" <?php  if($submission['enc_edu']==2){ echo('checked');}else{echo("disabled");} ?> readonly>Outro</label>
               </div>
               <div class="clearfix"></div>
           </div>


           <!--outro encarregado educacao quem-->
           <div class="form-group collapse <?php  if($submission['enc_edu']==2){ echo('in');} ?>" id="encarregado_educacao_collapse">
               <div class="col-lg-2">
                   <label for="outro_enc_edu_quem"> Parentesco:</label>
                   <input type="text" class="form-control" id="outro_enc_edu_quem" name="outro_enc_edu_quem" placeholder="Ex: Avó" list="parentesco" onchange="atualiza_tabela_autorizacoes()" value="<?php echo($submission['enc_edu_parentesco']);?>" readonly>
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
                   <input type="text" class="form-control" id="nome_enc_edu" name="nome_enc_edu" placeholder="Nome completo do encarregado de educação" onchange="atualiza_tabela_autorizacoes()" value="<?php echo($submission['enc_edu_nome']);?>" readonly>
               </div>

               <!--outro encarregado educacao profissao-->
               <div class="col-lg-4">
                   <label for="prof_enc_edu"> Profissão:</label>
                   <input type="text" class="form-control" id="prof_enc_edu" name="prof_enc_edu" placeholder="Profissão do encarregado de educação" value="<?php echo($submission['prof_enc_edu']);?>" readonly>
               </div>
           </div>
           <div class="clearfix"></div>







           <!--pai-->
           <div class="form-group">
               <div class="col-lg-8">
                   <label for="pai">Pai:</label>
                   <input type="text" class="form-control" id="pai" name="pai" placeholder="Nome completo do pai"  onchange="atualiza_tabela_autorizacoes()" value="<?php echo($submission['pai_nome']);?>" readonly>
               </div>

               <!--profissao pai-->
               <div class="col-lg-4">
                   <label for="prof_pai">Profissão:</label>
                   <input type="text" class="form-control" id="prof_pai" name="prof_pai" placeholder="Profissão do pai" value="<?php echo($submission['prof_pai']);?>" readonly>
               </div>
           </div>



           <!--mae-->
           <div class="form-group">
               <div class="col-lg-8">
                   <label for="mae">Mãe:</label>
                   <input type="text" class="form-control" id="mae" name="mae" placeholder="Nome completo da mãe" onchange="atualiza_tabela_autorizacoes()" value="<?php echo($submission['mae_nome']);?>" readonly>
               </div>

               <!--profissao mae-->
               <div class="col-lg-4">
                   <label for="prof_mae">Profissão:</label>
                   <input type="text" class="form-control" id="prof_mae" name="prof_mae" placeholder="Profissão da mãe" value="<?php echo($submission['prof_mae']);?>" readonly>
               </div>
               <div class="clearfix"></div>
           </div>



           <!--casados-->
           <div class="form-group">
               <div class="col-lg-8">
                   <label for="casados">Casados:</label>
                   <label class="radio-inline"><input type="radio" id="casados1" name="casados" value="Sim" <?php  if(isset($submission['casados_como']) && $submission['casados_como']!=''){ echo('checked');}else{echo("disabled");} ?> readonly>Sim</label>
                   <label class="radio-inline"><input type="radio" id="casados2" name="casados" value="Nao" <?php  if(!isset($submission['casados_como']) || $submission['casados_como']==''){ echo('checked');}else{echo("disabled");} ?> readonly>Não</label>
               </div>
               <div class="clearfix"></div>
           </div>


           <!--casados como-->
           <div class="form-group collapse <?php  if(isset($submission['casados_como']) && $submission['casados_como']!=''){ echo('in');} ?>" id="casados_como">
               <div class="col-lg-8">
                   <label for="casados_como">Se respondeu <i>Sim</i> indique:</label>
                   <label class="radio-inline"><input type="radio" name="casados_como" value="igreja" <?php  if($submission['casados_como']=='igreja'){ echo('checked');}else{echo("disabled");} ?> readonly>Igreja</label>
                   <label class="radio-inline"><input type="radio" name="casados_como" value="civil"  <?php  if($submission['casados_como']=='civil'){ echo('checked');}else{echo("disabled");} ?> readonly>Civil</label>
                   <label class="radio-inline"><input type="radio" name="casados_como" value="uniao de facto" <?php  if($submission['casados_como']=='uniao de facto'){ echo('checked');}else{echo("disabled");} ?> readonly>União de facto</label>
               </div>
               <div class="clearfix"></div>
           </div>



           <div class="row clearfix" style="margin-bottom: 40px"></div>



           <!--morada-->
            <div class="form-group">
            <div class="col-lg-12">
              <label for="morada">Morada:</label>
              <input type="text" class="form-control" id="morada" name="morada" placeholder="Morada do encarregado de educação" value="<?php  echo($submission['morada']);?>" readonly required>
            </div>
            </div>



            <!--codigo postal-->
            <div class="form-group">
                <div class="col-lg-4">
                <div id="codigo_postal_div">
                  <label for="codigo_postal">Código postal:</label>
                  <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="xxxx-xxx Localidade" onclick="verifica_codigo_postal()" onchange="verifica_codigo_postal()" value="<?php echo($submission['cod_postal']);?>" readonly required>
                  <span id="erro_postal_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                </div>
                </div>



                <!--telefone-->
                <div class="col-lg-2">
                <div id="telefone_div">
                  <label for="tel">Telefone:</label>
                  <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="Telefone do encarregado de educação" onclick="verifica_telefone()" onchange="verifica_telefone(); atualiza_tabela_autorizacoes();" value="<?php echo($submission['telefone']);?>" readonly>
                  <span id="erro_telefone_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                </div>
                </div>



                <!--telemovel-->
                <div class="col-lg-2">
                    <div id="telemovel_div">
                      <label for="telm"><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?>:</label>
                      <input type="tel" class="form-control" id="telemovel" name="telemovel" placeholder="<?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?> do encarregado de educação" onclick="verifica_telemovel()" onchange="verifica_telemovel(); atualiza_tabela_autorizacoes();" value="<?php echo($submission['telemovel']);?>" readonly>
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
                   <input type="email" class="form-control" id="email" name="email" placeholder="endereco@example.com" value="<?php echo($submission['email']);?>" readonly>
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
                   <label class="radio-inline"><input type="radio" id="ja_frequentou_catequese1" name="ja_frequentou_catequese" value="Sim" <?php  if(isset($submission['ultimo_catecismo'])){ echo('checked');}else{echo("disabled");} ?> readonly>Sim</label>
                   <label class="radio-inline"><input type="radio" id="ja_frequentou_catequese2" name="ja_frequentou_catequese" value="Nao" <?php  if(!isset($submission['ultimo_catecismo'])){ echo('checked');}else{echo("disabled");} ?> readonly>Não</label>
               </div>
               <div class="clearfix"></div>
               <div class="form-group collapse <?php  if(isset($submission['ultimo_catecismo'])){ echo('in');} ?>" id="ultimo_catecismo_collapse">
                   <div class="col-lg-3">
                       <label for="ultimo_catecismo">Último catecismo que frequentou:</label>
                       <select id="ultimo_catecismo" name="ultimo_catecismo_disabled" class="form-control" disabled>
                           <?php
                           for($i = 1; $i <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)) -1; $i++)
                           {
                           ?>
                               <option value="<?= $i ?>" <?php if($submission['ultimo_catecismo']==$i){ echo('selected'); } ?>><?= $i ?>º catecismo</option>
                           <?php
                           }
                           ?>
                       </select>
                       <input type="hidden" name="ultimo_catecismo" value="<?php echo($submission['ultimo_catecismo']);?>">
                   </div>
               </div>
           </div>

           <div class="row clearfix" style="margin-bottom: 20px"></div>


           <!--escuteiro-->
            <div class="form-group">
            <div class="col-lg-8">
            <div class="row" style="margin-top:20px; "></div>
                <label for="e_escuteiro">É escuteiro(a):</label>
                <label class="radio-inline"><input type="radio" name="escuteiro" value="Sim" <?php  if($submission['escuteiro']==1){ echo('checked');}else{echo("disabled");} ?> readonly>Sim</label>
            <label class="radio-inline"><input type="radio" name="escuteiro" value="Nao" <?php  if($submission['escuteiro']!=1){ echo('checked');}else{echo("disabled");} ?> readonly>Não</label>
            </div>
            <div class="clearfix"></div>
            </div>



            <div class="row" style="margin-top:20px; "></div>


            <!--baptizado-->
           <?php $baptizado = false;
           if((isset($submission['data_baptismo']) && $submission['data_baptismo']!="")
               || (isset($submission['paroquia_baptismo']) && $submission['paroquia_baptismo']!=""))
               $baptizado = true;
           ?>
            <div class="form-group">
            <div class="col-lg-8">
            <div class="row" style="margin-top:20px; "></div>
                <label for="e_baptizado">É baptizado(a):</label>
                <label class="radio-inline"><input type="radio" id="baptizado1" name="baptizado" value="Sim" <?php  if($baptizado){ echo('checked');}else{echo("disabled");} ?> readonly>Sim</label>
            <label class="radio-inline"><input type="radio" id="baptizado2" name="baptizado" value="Nao" <?php  if(!$baptizado){ echo('checked');}else{echo("disabled");} ?> readonly>Não</label>
            </div>
            <div class="clearfix"></div>
            </div>


            <!--paroquia de baptismo-->
            <div class="form-group collapse <?php  if($baptizado){ echo('in');} ?>" id="paroquia_baptismo_collapse">
            <div class="col-lg-4">
              <label for="paroquia_baptismo"> Paróquia de baptismo: </label>
              <input type="text" class="form-control" id="paroquia_baptismo" name="paroquia_baptismo" placeholder="Paróquia de baptismo"  value="<?php echo($submission['paroquia_baptismo']);?>" readonly>
            </div>


            <!--data de baptismo-->
            <div class="form-group">
             <div class="col-lg-2">
             <div class="input-append date" id="data_baptismo_div" data-date="" data-date-format="dd-mm-yyyy">
              <label for="data_baptismo">Data:</label>
              <input class="form-control" id="data_baptismo" name="data_baptismo" size="16" type="text" onclick="verifica_data_baptismo()" onchange="verifica_data_baptismo()" placeholder="dd-mm-aaaa" value="<?php echo(date( "d-m-Y", strtotime($submission['data_baptismo'])));?>" readonly>
              <span id="erro_data_baptismo_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
             </div>
            </div>
            </div>
            </div>
            <div class="clearfix"></div>
            <div class="row" style="margin-bottom:20px; "></div>


           <div class="row clearfix" style="margin-bottom: 20px"></div>



            <!--primeira comunhao-->
           <?php $comunhao = false;
           if((isset($submission['data_comunhao']) && $submission['data_comunhao']!="")
               || (isset($submission['paroquia_comunhao']) && $submission['paroquia_comunhao']!=""))
               $comunhao = true;
           ?>
            <div class="form-group">
            <div class="col-lg-8">
                <label for="fez_primeira_comunhao">Fez primeira comunhão:</label>
                <label class="radio-inline"><input type="radio" id="comunhao1" name="comunhao" value="Sim" <?php  if($comunhao){ echo('checked');}else{echo("disabled");} ?> readonly>Sim</label>
            <label class="radio-inline"><input type="radio" id="comunhao2" name="comunhao" value="Nao" <?php  if(!$comunhao){ echo('checked');}else{echo("disabled");} ?> readonly>Não</label>
            </div>
            <div class="clearfix"></div>
            </div>


            <!--paroquia primeira comunhao-->
            <div class="form-group collapse <?php  if($comunhao){ echo('in');} ?>" id="paroquia_comunhao_collapse">
            <div class="col-lg-4">
              <label for="paroquia_comunhao"> Paróquia 1ª comunhão:</label>
              <input type="text" class="form-control" id="paroquia_comunhao" name="paroquia_comunhao" placeholder="Paróquia 1ª comunhão"  value="<?php echo($submission['paroquia_comunhao']);?>" readonly>
            </div>

            <!--data primeira comunhao-->
            <?php if($_REQUEST['modo']=='editar'){ echo("<!--");} ?>
            <div class="form-group">
             <div class="col-lg-2">
             <div class="input-append date" id="data_comunhao_div" data-date="" data-date-format="dd-mm-yyyy">
              <label for="data_comunhao">Data:</label>
              <input class="form-control" id="data_comunhao" name="data_comunhao" size="16" type="text" onclick="verifica_data_comunhao()" onchange="verifica_data_comunhao()" placeholder="dd-mm-aaaa" value="<?php echo(date( "d-m-Y", strtotime($submission['data_comunhao'])));?>" readonly>
              <span id="erro_data_comunhao_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
             </div>
            </div>
            </div>
            </div>
            <div class="clearfix"></div>
            <?php if($_REQUEST['modo']=='editar'){ echo("-->");} ?>

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
                   <label class="radio-inline"><input type="radio" id="autorizacao_saida1" name="autorizacao_saida" value="Nao" <?php  if(!$submission['autorizou_saida_sozinho']){ echo('checked');}else{echo("disabled");} ?> readonly>Não</label>
                   <label class="radio-inline"><input type="radio" id="autorizacao_saida2" name="autorizacao_saida" value="Sim" <?php  if($submission['autorizou_saida_sozinho']){ echo('checked');}else{echo("disabled");} ?> readonly>Sim</label>
               </div>
               <div class="clearfix"></div>
               <div class="form-group collapse <?php  if(!$submission['autorizou_saida_sozinho']){echo('in');} ?>" id="autorizacao_saida_collapse">
                   <div class="col-lg-12">
                       <label for="">Quem pode vir buscar o seu educando?</label>
                       <table class="table table-hover">
                           <thead>
                           <tr>
                               <th>Nome</th>
                               <th>Parentesco</th>
                               <th><?= (Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)?"Celular":"Telemóvel" ?></th>
                           </tr>
                           </thead>
                           <tbody>
                           <tr>
                               <td><span id="autorizacao_nome_enc_edu"></span></td>
                               <td><span id="autorizacao_parentesco_enc_edu"></span></td>
                               <td><span id="autorizacao_tel_enc_edu"></span></td>
                           </tr>
                           <tr>
                               <td><input type="text" class="form-control" id="autorizacao_nome_1" name="autorizacao_nome[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][0]->nome);?>" readonly></td>
                               <td><input type="text" class="form-control" id="autorizacao_parentesco_1" name="autorizacao_parentesco[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][0]->parentesco);?>" readonly></td>
                               <td><input type="tel" class="form-control" id="autorizacao_telefone_1" name="autorizacao_telefone[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][0]->telefone);?>" readonly></td>
                           </tr>
                           <tr>
                               <td><input type="text" class="form-control" id="autorizacao_nome_1" name="autorizacao_nome[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][1]->nome);?>" readonly></td>
                               <td><input type="text" class="form-control" id="autorizacao_parentesco_1" name="autorizacao_parentesco[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][1]->parentesco);?>" readonly></td>
                               <td><input type="tel" class="form-control" id="autorizacao_telefone_1" name="autorizacao_telefone[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][1]->telefone);?>" readonly></td>
                           </tr><tr>
                               <td><input type="text" class="form-control" id="autorizacao_nome_1" name="autorizacao_nome[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][2]->nome);?>" readonly></td>
                               <td><input type="text" class="form-control" id="autorizacao_parentesco_1" name="autorizacao_parentesco[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][2]->parentesco);?>" readonly></td>
                               <td><input type="tel" class="form-control" id="autorizacao_telefone_1" name="autorizacao_telefone[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][2]->telefone);?>" readonly></td>
                           </tr><tr>
                               <td><input type="text" class="form-control" id="autorizacao_nome_1" name="autorizacao_nome[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][3]->nome);?>" readonly></td>
                               <td><input type="text" class="form-control" id="autorizacao_parentesco_1" name="autorizacao_parentesco[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][3]->parentesco);?>" readonly></td>
                               <td><input type="tel" class="form-control" id="autorizacao_telefone_1" name="autorizacao_telefone[]" placeholder="" value="<?php echo($submission['autorizacoesSaidaMenores'][3]->telefone);?>" readonly></td>
                           </tr>
                           </tbody>
                       </table>
                   </div>
               </div>
           </div>

       </div>
   </div>



   <div class="row clearfix" style="margin-bottom: 40px"></div>


   <!-- Observacoes -->
   <div class="form-group">
       <div class="col-lg-12">
           <label for="observacoes">Observações: (opcional)</label>
           <?php
           $observacoes = Utils::escapeSingleQuotes(Utils::doubleEscapeDoubleQuotes(Utils::doubleEscapeWhiteSpaces(Utils::sanitizeOutput($submission['obs']))));
           ?>
           <textarea class="form-control" id="observacoes" name="observacoes" rows="4" placeholder="Escreva aqui quaisquer observações que considere pertinentes para a catequese.&#10;Ex: o seu educando tem necessidades especiais?" style="cursor: auto;"><?php echo($observacoes);?></textarea>
       </div>
   </div>

   <div class="row clearfix" style="margin-bottom:40px; "></div>


   <!-- Autorizacoes -->
   <div class="form-group">
       <div class="col-lg-12">
           <input id="autorizacao_fotos" name="autorizacao_fotos" type="checkbox" style="cursor: auto;" value="on" <?php if($submission['autorizou_fotos']==1){ echo("checked"); }?> onclick="return false;" readonly/>
           <label for="autorizacao_fotos" style="display: contents;">Autorizo a utilização e divulgação de fotografias do meu educando, tiradas no âmbito das atividades catequéticas.</label>
       </div>
       <div class="col-lg-12">
           <input type="checkbox" id="declaracao_enc_edu" name="declaracao_enc_edu" style="cursor: auto;" value="on" onclick="return false;" required checked readonly/>
           <label for="declaracao_enc_edu" style="display: contents;">Declaro que sou titular das responsabilidades parentais ou representante legal do meu educando, ou que tenho as devidas autorizações para efetuar esta inscrição.</label>
       </div>
       <div class="col-lg-12">
           <input type="checkbox" id="rgpd" name="consentimento_rgpd" style="cursor: auto;" value="on" onclick="return false;" required checked readonly/>
           <label for="rgpd" style="display: contents;">Declaro que aceito o tratamento dos meus dados pessoais e do meu educando, de acordo com o disposto no <a href="publico/descarregarDeclaracaoRGPD.php" target="_blank" rel="noopener noreferrer">documento emitido pela <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?></a> acerca do cumprimento do Regulamento Geral de Proteção de Dados.</label>
       </div>
   </div>

   <div class="row clearfix" style="margin-bottom:30px; "></div>


    <!-- Inputs ocultos -->
    <input type="hidden" id="foto_file" name="foto_file" value="<?php echo($submission['foto']);?>">
    <input type="hidden" id="iid" name="iid" value="<?php echo($submission['iid']);?>">



   </div>
  </div>

    <div class="clearfix" style="margin-bottom: 20px;"></div>

<?php
if(!isset($submission['cid']))
{
    ?>
    <!-- Inscricao num grupo de catequese -->
    <div class="panel panel-default">
        <div class="panel-heading">Inscrição num grupo de catequese</div>
        <div class="panel-body">

            <div class="form-group">
                <div class="col-xs-8">
                    <div class="row"></div>
                    <label class="radio"><input type="radio" id="quer_inscrever" name="quer_inscrever" value="Nao" checked> Não inscrever agora num grupo de catequese</label>
                    <label class="radio"><input type="radio" id="quer_inscrever2" name="quer_inscrever" value="Sim">Inscrever agora num grupo de catequese</label>
                </div>
                <div class="clearfix"></div>
            </div>


            <div class="jumbotron collapse" id="quer_inscrever_collapse">

                <div class="form-group">
                    <div class="col-xs-4" style="margin-top: 3px;">
                        <label for="ano_catequetico">Ano catequético: </label>
                        <span><?= Utils::formatCatecheticalYear(Utils::currentCatecheticalYear())?></span>
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


                            $catecismo_recomendado = computeRecommendedCatechism($submission['data_nasc'], $submission['ultimo_catecismo']);

                            if (isset($result) && count($result)>=1)
                            {
                                foreach($result as $row)
                                {
                                    echo("<option value='" . $row['ano_catecismo'] . "'");
                                    if ($row['ano_catecismo'] == $catecismo_recomendado)
                                        echo(" selected");
                                    echo(">");
                                    echo("" . $row['ano_catecismo'] . "º" . "</option>\n");
                                }
                            }

                            $result = null;

                            ?>
                        </select>
                        <br>
                        <span><small><i><b>Recomendado: </b><?php echo($catecismo_recomendado);?>º</i>&nbsp;</small>
                            <?php
                            $razoes = "";
                            if(isset($submission['ultimo_catecismo']))
                                $razoes = $razoes . "Ultimo catecismo frequentado: " . $submission['ultimo_catecismo'] . "º<br>";
                            $razoes = $razoes . "Idade do catequizando: " . date_diff(date_create($submission['data_nasc']), date_create('today'))->y . " anos";
                            ?>
                        <span class="glyphicon glyphicon-question-sign" data-container="body" data-toggle="popover" data-placement="top" data-content="<?php echo($razoes);?>"></span></span>
                    </div>

                    <div class="col-xs-3">
                        <label for="turma">Grupo:</label>
                        <select name="turma">

                            <?php

                            //Obter turmas de catequese
                            $result = NULL;
                            try
                            {
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
                                    echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'>");
                                    echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                                }
                            }

                            $result = null;
                            ?>
                        </select>
                    </div>


                    <div class="checkbox col-xs-1" style="margin-top: 3px;">
                        <label><input id="pago" name="pago" type="checkbox"> Pago</label>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>

        </div>
    </div>




     <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span> Aprovar inscrição</button>
<?php
    }
?>
    
    <div style="margin-bottom: 60px;"></div>
    
  </form>

  <div class="row" style="margin-bottom:80px; "></div>
</div>




<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>

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
        
        
	if(!codigo_postal_valido(cod_postal, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
	{
		alert("O código postal que introduziu é inválido. Deve ser da forma 'xxxx-yyy Localidade'.");
		return false;
	}
                
        
        
        if((telefone==="" || telefone===undefined) && (telemovel==="" || telemovel===undefined) )
        {
		alert("Deve introduzir pelo menos um número de telefone ou telemóvel.");
		return false; 
        }
        else if(telefone!=="" && telefone!==undefined && !telefone_valido(telefone, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
        {
        	alert("O número de telefone que introduziu é inválido. Deve conter 9 dígitos ou iniciar-se com '+xxx ' seguido de 9 digitos.");
		return false; 
        }
        else if(telemovel!=="" && telemovel!==undefined && !telefone_valido(telemovel, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>'))
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
	
	if(!telefone_valido(telefone, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && telefone!="" && telefone!=undefined)
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
	
	if(!telefone_valido(telemovel, '<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) ?>') && telemovel!="" && telemovel!=undefined)
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
$(document).ready(function(){

    $("#quer_inscrever").click(function(){
        $("#quer_inscrever_collapse").collapse('hide');
    });
    $("#quer_inscrever2").click(function(){
        $("#quer_inscrever_collapse").collapse('show');
    });
});
</script>



<script>
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

    $(function () {
        $('[data-toggle="popover"]').popover({ trigger: "hover",
            html: true,
            /*content: function () {
              return '<img src="'+$(this).data('img') + '" />';
            },*/
            delay: { "show": 500, "hide": 100 }
        });
    })

    atualiza_tabela_autorizacoes();
</script>

    
</body>
</html>