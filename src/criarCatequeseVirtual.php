<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . '/core/DataValidationUtils.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/domain/WeekDay.php');
require_once(__DIR__ . '/fonts/quill-fonts.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\DataValidationUtils;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\UserData;
use catechesis\Utils;
use core\domain\WeekDay;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;



// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
$pageUI->addWidget($menu);
$saveDialog = new ModalDialogWidget("confirmarGuardarCatequese");
$pageUI->addWidget($saveDialog);
$closeRoomDialog = new ModalDialogWidget("confirmarFecharSala");
$pageUI->addWidget($closeRoomDialog);


?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Criar catequese virtual</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">
  <link rel="stylesheet" href="css/quill-1.3.6/quill.snow.css" />
  <?php quill_render_css_links(); ?>
  <link rel="stylesheet" href="font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">
  <link rel="stylesheet" href="css/bootoast-1.0.1/bootoast.min.css">


    <style>
  	@media print
	{    
	    .no-print, .no-print *
	    {
		display: none !important;
	    }
	    
	    
	    a[href]:after {
		    content: none;
		  }
		  
	    /*@page {
		    size: 297mm 210mm;*/ /* landscape */
		    /* you can also specify margins here: */
		    /*margin: 35mm;*/
		    /*margin-right: 45mm;*/ /* for compatibility with both A4 and Letter */
		 /* }*/
		  
	}
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
	}
  </style>
  
  <style>
	  .btn-group-hover .btn {
	    /*border-color: white;*/
	    background: white;
	    text-shadow: 0 1px 1px white;
	    -webkit-box-shadow: inset 0 1px 0 white;
	    -moz-box-shadow: inset 0 1px 0 white;
	    box-shadow: inset 0 1px 0 white;
	}
	  .btn-group-hover {
		    opacity: 0;
	}
	
    .rowlink {

        cursor: pointer;

    }

  </style>

  <style>

      /* Customized Quill CSS */

    body > #standalone-container {
        margin: 50px auto;
        max-width: 720px;
    }
    #editor-container {
        height: 60vh;
    }


      .ql-editor .ql-video {

          display: block;
          max-width: 100%;
          width: 80%;
          height: 60vh;
          margin-left: auto;
          margin-right: auto;
      }

      @media print
      {
          .ql-hidden {
              display: none !important;
          }
      }


      .editor-box .ql-editor img {
          cursor: default !important;
      }

      .ql-embed-placeholder[data-type="ql-embed"] {
          background-color: #fff;
      }

      .quill-editor iframe {
          pointer-events: none;
      }

      .editor-container iframe
      {
          pointer-events: none;
      }

      editor-container iframe
      {
          pointer-events: none;
      }

  </style>
</head>
<body>

<?php
$menu->renderHTML();
?>



<div class="only-print" style="position: fixed; top: 0;">
	<img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
	<h3>Criar catequese virtual</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:150px; "></div>


<div class="container" id="contentor">

    <?php

    $db = new PdoDatabaseManager();

    //Carregar conteudo da sessao
    $data_sessao = NULL;
    $catecismo = NULL;
    $turma = NULL;


    if(isset($_POST['catecismo']))
    {
        if($_POST['catecismo']=="0")    //String "0" is considered empty
            $catecismo = -1;            //Default session for all catechisms
        else
            $catecismo = intval($_POST['catecismo']);
    }
    if(isset($_POST['turma']))
    {
        $turma = Utils::sanitizeInput($_POST['turma']);

        //Default session for all catechisms and groups
        if($catecismo == -1)
            $turma = "_";
    }
    if($_POST['data_sessao'])
    {
        $data_sessao = $_POST['data_sessao']; // sanitizeInput($_POST['data_sessao']);
    }


    $catechistGroups = $db->getCatechistGroups(Authenticator::getUsername(), Utils::currentCatecheticalYear());

    if(!isset($catecismo) || !(($catecismo >= 1 && $catecismo <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))) || $catecismo == -1))
    {
        if(isset($catechistGroups) && count($catechistGroups) >= 1)
            $catecismo = $catechistGroups[0]["ano_catecismo"]; //Default: select the group where the catechist teaches
        else
            $catecismo = 1;
    }
    if(!isset($turma))
    {
        if(isset($catechistGroups) && count($catechistGroups) >= 1)
            $turma = $catechistGroups[0]["turma"]; //Default: select the group where the catechist teaches
        else
            $turma = 'A';
    }
    if(!$data_sessao || !DataValidationUtils::validateDate($data_sessao))
    {
        if(date('D') == 'Sat')
            $data_sessao = date('d-m-Y', strtotime('today')); //Default: hoje (sabado);
        else
        {
            $defaultWeekDay = WeekDay::toString(Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_WEEK_DAY));
            $data_sessao = date('d-m-Y', strtotime('next ' . $defaultWeekDay)); //Default: next catecsis day
        }
    }


    $result = NULL;
    $conteudo_carregado = NULL;

    //Carregar conteudo da catequese
    //(se nao houver uma sessao para este catecismo especifico, procura recursivamente uma sessao generica)
    try
    {
        $conteudo_carregado = $db->getVirtualCatechesisContent($data_sessao, $catecismo, $turma, true);
    }
    catch(Exception $e)
    {
        echo("<p><strong>Erro!</strong> " . $e->getMessage() . "</p>");
    }

    //Verificar se o catequista tem permissoes para editar esta catequese
    $ano_catequetico = Utils::computeCatecheticalYear(date("d-m-Y", strtotime($data_sessao)));
    $permissaoCatequista = Authenticator::isAdmin() || group_belongs_to_catechist($ano_catequetico, $catecismo, $turma, Authenticator::getUsername());

    ?>
	
  <h2 class="no-print"> Criar catequese virtual</h2>

  <div class="well well-lg" style="position:relative; z-index:2;">
  	<form role="form" onsubmit="return validar();" action="criarCatequeseVirtual.php" method="post" id="form_catequese_virtual">
  
  <!--catecismo-->
    <div class="form-group">
    <div class="col-xs-2">
      <label for="catecismo">Catecismo:</label>
        <div class="input-group catecismo">
        <select id="catecismo" name="catecismo" onchange="muda_catecismo()">
            <?php

            //Obter catecismos
            $result = null;
            try
            {
                $result = $db->getCatechisms(Utils::currentCatecheticalYear());
            }catch(Exception $e){
                //echo $e->getMessage();
            }

            if (isset($result) && count($result)>=1)
            {
                foreach($result as $row)
                {
                    echo("<option value='" . intval($row['ano_catecismo']) . "'");
                    if ($catecismo==$row['ano_catecismo'])
                        echo(" selected");
                    echo(">");
                    echo("" . intval($row['ano_catecismo']) . "º</option>\n");
                }
            }
            ?>
            <option value="-1" <?php if($catecismo==-1) echo("selected");?>>Todos</option>
            <!--<option value="1" <?php if($catecismo==1) echo("selected");?>>Infância</option>
            <option value="7" <?php if($catecismo==7) echo("selected");?>>Adolescência</option>-->
        </select>
        </div>
    </div>
    <div class="col-xs-2">
        <label for="turma">Grupo:</label>
        <div class="input-group turma">
            <select id="turma" name="turma" onchange="this.form.submit()">
                <?php

                //Obter turmas de catequese
                $result = null;
                try
                {
                    if($catecismo != -1)
                        $result = $db->getCatechismGroups(Utils::currentCatecheticalYear(), $catecismo);
                    else
                        $result = $db->getGroupLetters(Utils::currentCatecheticalYear());

                }catch(PDOException $e){
                    //echo $e->getMessage();
                }

                if (isset($result) && count($result)>=1)
                {
                    foreach($result as $row)
                    {
                        echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
                        if ($turma==$row['turma'])
                            echo(" selected");
                        echo(">");
                        echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                    }
                }
                ?>
                <option value="_" <?php if($turma=="_") echo("selected");?>>Todas</option>
            </select>
        </div>
    </div>
   </div>
    
    
   <!--data-->
    <div class="form-group">
    <div class="col-xs-2" id="data_sessao_group">
       <label for="data_sessao">Data da sessão:</label>
       <div class="input-group date" id="data_nasc_div" data-date="" data-date-format="dd-mm-yyyy">
         <input class="form-control" id="data_sessao" name="data_sessao" size="16" type="text" onclick="verifica_data()" onchange="verifica_data(); this.form.submit();" \
                placeholder="dd-mm-aaaa" value="<?php echo($data_sessao); ?>" readonly>
         <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
     </div>
    </div>
   </div>

   <input type="hidden" id="conteudo" name="conteudo">

   <div class="clearfix"></div>
  </div>


    <?php
    if($catecismo != -1  &&  $turma!="_") //Catecismo "Todos" e turma default nao tem sala
    {
        //Verificar se ja existe uma sala de catequese virtual
        try
        {
            $salaVirtual = $db->getVirtualCatechesisRoom(date('d-m-Y', strtotime('today')), $catecismo, $turma);
        }
        catch(Exception $e)
        {
            echo("<p><strong>Erro!</strong> " . $e->getMessage() . "</p>");
        }
    }
    ?>
    <!-- Botao imprimir -->
    <div class="clearfix"></div>
    <div class="btn-group">
        <?php if($permissaoCatequista){ ?>
            <button type="button" onclick="guardar()" class="btn btn-default no-print"><span id="guardar_icon" class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
        <?php } ?>
        <button type="button" onclick="imprimir()" class="btn btn-default no-print"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
        <button type="button" onclick="previsualizar()" class="btn btn-default no-print"><span class="glyphicon glyphicon-eye-open"></span> Pré-visualizar</button>
        <?php if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL))
        {?>
            <button type="button" data-toggle="modal" data-target="#galeriaRecursos" class="btn btn-default no-print"><span class="fas fa-icons"></span> Recursos na nuvem </button>
        <?php
        }
        if($permissaoCatequista && $catecismo != -1  &&  $turma!="_")
        {?>
            <button type="button" onclick="fecharSalaVirtual()" id="botao_encerrar_sala" class="btn btn-default no-print" style="display: <?php if($salaVirtual) echo(""); else echo("none"); ?>;"><span class="text-danger glyphicon glyphicon-off"></span>&nbsp; Encerrar sala virtual</button>
            <button type="button" onclick="entrarSalaVirtual()" id="botao_criar_sala" class="btn btn-primary no-print"><span class="glyphicon glyphicon-facetime-video"></span>&nbsp; <span id="botao_criar_sala_action_text"><?php if($salaVirtual) echo("Entrar na"); else echo("Criar");?></span> sala virtual</button>
        <?php
        }?>
    </div>


    <div class="row" style="margin-bottom:20px; "></div>


    <!-- Quill editor -->
    <div id="standalone-container">
        <?php if($permissaoCatequista)
        { ?>
        <div id="toolbar-container">
            <span class="ql-formats">
              <select class="ql-font" title="Tipo de letra">
                <?php quill_render_html_font_selector(); ?>
              </select>
              <select class="ql-size" title="Tamanho">
                <option selected>Pré-definido</option>
                <option value="0.75em">Pequeno</option>
                <option value="1.5em">Médio</option>
                <option value="2.5em">Grande</option>
                <option value="4em">Enorme</option>
              </select>
            </span>
            <span class="ql-formats">
              <button class="ql-bold" title="Negrito"></button>
              <button class="ql-italic" title="Itálico"></button>
              <button class="ql-underline" title="Sublinhado"></button>
              <button class="ql-strike" title="Rasurado"></button>
            </span>
            <span class="ql-formats">
              <select class="ql-color" title="Cor do texto"></select>
              <select class="ql-background" title="Cor do fundo"></select>
            </span>
            <!--<span class="ql-formats">
              <button class="ql-script" value="sub"></button>
              <button class="ql-script" value="super"></button>
            </span>-->
            <span class="ql-formats">
              <button class="ql-header" value="1" title="Título"></button>
              <button class="ql-header" value="2" title="Subtítulo"></button>
              <button class="ql-blockquote" title="Citação"></button>
              <!--<button class="ql-code-block"></button>-->
            </span>
            <span class="ql-formats">
              <button class="ql-list" value="ordered" title="Lista numerada"></button>
              <button class="ql-list" value="bullet" title="Lista de tópicos"></button>
              <button class="ql-indent" value="-1" title="Indentar à esquerda"></button>
              <button class="ql-indent" value="+1" title="Indentar à direita"></button>
            </span>
            <span class="ql-formats">
              <!--<button class="ql-direction" value="rtl"></button>-->
              <select class="ql-align" title="Alinhamento"></select>
            </span>
            <span class="ql-formats">
              <button class="ql-link" title="Hiperligação"></button>
              <button class="ql-image" title="Imagem"></button>
              <button class="ql-video" title="Vídeo"></button>
              <!--<button class="ql-formula"></button>-->
            </span>
            <span class="ql-formats">
                <button class="ql-clean" title="Limpar formatação"></button>
                <button class="ql-shadow-multiple-block far fa-square" title="Destaque"></button>
                <button class="ql-3d-float-block fas fa-layer-group" title="Flutuante"></button>
                <button class="ql-outline-block fas fa-pen-fancy" title="Contorno"></button>
                <button class="ql-3d-block fas fa-cube" title="3D"></button>
                <button class="ql-onfireblock fas fa-fire-alt" title="Sarça ardente"></button>
                <button class="ql-neon-block fas fa-dot-circle" title="Néon"></button>
            </span>
        </div>
        <?php
        }
        ?>
        <div id="editor-container"></div>
    </div>

</div>


<?php if($permissaoCatequista)
{
    // Dialog to confirm overwrite save

    $saveDialog->setTitle("Confirmar submissão");

    $saveDialog->setBodyContents(<<<HTML_CODE
                <p>Neste momento, os seguintes catequistas estão a visualizar esta página:<br></p>
                <p id="catequistas_ativos"></p>
                <p></p>
                <p>Se clicar em 'Sim' irá guardar as suas alterações à sessão de catequese, mas irá substituir as alterações que estes catequistas tenham eventualmente realizado. <br>É preferível contactá-los antes de continuar.</p>
                <p>Tem a certeza de que pretende guardar as suas alterações sobre esta sessão de catequese?</p>
HTML_CODE
    );

    $saveDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                ->addButton(new Button("Sim", ButtonType::DANGER, "perform_guardar()"));

    $saveDialog->renderHTML();



    // Dialog to confirm close virtual catechesis room

    $closeRoomDialog->setTitle("Confirmar encerramento da sala");

    $closeRoomDialog->setBodyContents(<<<HTML_CODE
                <p>A sala de catequese virtual encontra-se aberta, podendo estar a decorrer uma sessão de catequese.<br>
                Se encerrar esta sala a sessão de catequese terminará para todos os participantes.</p>
                <p>Tem a certeza de que pretende fechar esta sala de catequese?</p>
HTML_CODE
    );

    $closeRoomDialog->addButton(new Button("Cancelar", ButtonType::SECONDARY))
                    ->addButton(new Button("Encerrar", ButtonType::DANGER, "perform_encerrar_sala()"));

    $closeRoomDialog->renderHTML();

}
?>


<?php if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL))
{?>
<!-- Galeria de recursos de catequese virtual -->
<div class="modal fade bd-example-modal-lg" id="galeriaRecursos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="galeriaRecursosTitulo">Recursos na nuvem</h4>
            </div>
            <div class="modal-body">
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe class="embed-responsive-item" src="<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL); ?>"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="window.open('<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_NEXTCLOUD_BASE_URL); ?>', '_blank');"><span class="glyphicon glyphicon-new-window"></span>&nbsp; Ir para a nuvem</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<?php
}
?>

<?php
$pageUI->renderJS(); // Render the widgets' JS code
quill_render_js_scripts();
?>
<script src="js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>
<script src="js/rowlink.js"></script>
<script src="js/quill-1.3.6/quill.min.js"></script>
<script src="js/quill-image-resize-module/image-resize.min.js"></script>
<script src="js/bootoast-1.0.1/bootoast.js"></script>

<script>

    function muda_catecismo()
    {
        //Se a turma atualmente selecionada (ex: B) nao existir no novo catecismo selecionado, o conteudo fica em branco.
        //Redefinimos a turma entao como A, por precaucao.
        document.getElementById("turma").value = "A";
        document.getElementById("form_catequese_virtual").submit();
    }


    var Delta = Quill.import('delta');

    <?php quill_render_js_fonts(); ?>

    var quill = new Quill('#editor-container', {
        modules: {
            toolbar: <?php if ($permissaoCatequista) echo("'#toolbar-container'"); else echo("false"); ?>,
            imageResize: {modules: [ 'Resize', 'DisplaySize']}
        },
        placeholder: 'Compôr a sessão de catequese...',
        <?php if(!$permissaoCatequista) echo("readOnly: true,\n"); ?>
        theme: 'snow'
    });

    <?php if($permissaoCatequista)
    {
        quill_render_font_effects_js("quill");
    }?>

    function htmlDecode(input) {
        var doc = new DOMParser().parseFromString(input, "text/html");
        return doc.documentElement.textContent;
    }

    <?php

    if($conteudo_carregado)
    {
        echo("quill.setContents(new Delta(JSON.parse(htmlDecode('" . $conteudo_carregado . "'))));");
    }
    ?>

    <?php
    if($permissaoCatequista)
    {
    ?>

    var global = this;
    var outros_utilizadores_a_editar = [];
    const REFRESH_RATE_CATEQUESE_VIRTUAL = 30000;               //Refrescar utilizadores ativos na catequese virtual a cada 30s

    function keep_alive()
    {
        var dataSessao = document.getElementById("data_sessao").value;
        var catecismo = document.getElementById("catecismo").value;
        var turma = document.getElementById("turma").value;

        $.post("lockCatequeseVirtual.php", {data: dataSessao, catecismo: catecismo, turma: turma}, function(data, status)
        {
            var obj = $.parseJSON(data);
            //alert("Data: " + obj.nomes_utilizadores_ativos + "\nStatus: " + status);
            global.outros_utilizadores_a_editar = obj.active_users_names;
        });
    }
    keep_alive();                                                                             //Correr a funcao keep_alive() ao abrir a pagina
    var intervalID = setInterval(function(){keep_alive();}, REFRESH_RATE_CATEQUESE_VIRTUAL);  //Correr a funcao keep_alive() periodicamente



        <?php
        if($catecismo != -1  &&  $turma!="_")
        {
        ?>

        const REFRESH_RATE_SALA_VIRTUAL = 30000;               //Refrescar estado da sala virtual a cada 30s
        var initial_room_url = '<?= $salaVirtual['url'] ?>';

        function check_virtual_room()
        {
            var dataSessao = '<?= date('d-m-Y', strtotime('today')) ?>';
            var catecismo = '<?= $catecismo ?>';
            var turma = '<?= $turma ?>';

            $.post("virtual/salaVirtualStatus.php", {dataSessao: dataSessao, catecismo: catecismo, turma: turma}, function(data, status)
            {
                var obj = $.parseJSON(data);

                if(obj.room_status === "open")
                {
                    open_room();
                }
                else
                {
                    closed_room();
                }
            });
        }

        var intervalID2 = setInterval(function(){check_virtual_room();}, REFRESH_RATE_SALA_VIRTUAL);  //Correr a funcao check_virtual_room() periodicamente


        //Quando o utilizador muda de tab no browser
        $(window).blur(function()
        {
            clearInterval(intervalID2); //Parar o polling
        });

        //Quando o utilizador volta a esta tab no browser
        $(window).focus(function()
        {
            check_virtual_room();
            intervalID2 = setInterval(function(){check_virtual_room();}, REFRESH_RATE_SALA_VIRTUAL);  //Reativar o polling
        });





        function closed_room()
        {
            document.getElementById("botao_criar_sala_action_text").innerText = "Criar";
            document.getElementById("botao_encerrar_sala").style.display = "none";
        }

        function open_room()
        {
            document.getElementById("botao_criar_sala_action_text").innerText = "Entrar na";
            document.getElementById("botao_encerrar_sala").style.display = "block";
        }


        function entrarSalaVirtual()
        {
            window.open("<?php echo(constant('CATECHESIS_BASE_URL'));?>/virtual/salaVirtual.php?catecismo=<?php echo($catecismo);?>&turma=<?php echo($turma);?>", '_blank');

        }

        function fecharSalaVirtual()
        {
            $("#confirmarFecharSala").modal('show');
        }


        function perform_encerrar_sala()
        {
            var dataSessao = '<?= date('d-m-Y', strtotime('today')) ?>';
            var catecismo = '<?= $catecismo ?>';
            var turma = '<?= $turma ?>';

            $.post("virtual/encerrarSala.php", {dataSessao: dataSessao, catecismo: catecismo, turma: turma}, function(data, status)
            {
                var obj = $.parseJSON(data);
                if(obj.status_msg !== "OK")
                {
                    alert("Falha ao encerrar sala virtual. Erro: " + obj.status_msg);
                }
                check_virtual_room();
            });
        }

        <?php
        }
        ?>



    //Monitorizar se houve alteracoes
    var changed = false;

    quill.on('text-change', function(delta) {
        global.changed = true;
    });

    window.onbeforeunload = function() {
        if (changed)
        {
            return "Tem alterações não guardadas nesta sessão de catequese. Se continuar, estas alterações serão perdidas.<br>Tem a certeza de que pretende continuar?";
        }
    };



    function guardar()
    {
        keep_alive();

        if(global.outros_utilizadores_a_editar.length <= 0) //Apenas o utilizador atual esta a editar esta pagina
            perform_guardar();
        else                                         //Outros utilizadores estao a editar a pagina
        {
            var msg = "<b>";
            for (i = 0; i < global.outros_utilizadores_a_editar.length; ++i) {
                msg += global.outros_utilizadores_a_editar[i] + "<br>";
            }
            document.getElementById("catequistas_ativos").innerHTML = msg + "</b>";

            $('#confirmarGuardarCatequese').modal('show');
        }
    }

    function perform_guardar()
    {
        document.getElementById("guardar_icon").className = "fa fa-spinner fa-spin";

        var delta = quill.getContents();
        var deltaTxt = JSON.stringify(delta);
        document.getElementById("conteudo").value = deltaTxt;
        var catecismo = document.getElementById("catecismo").value;
        var turma = document.getElementById("turma").value;
        var dataSessao = document.getElementById("data_sessao").value; //"2020-03-22"

        var formData = new FormData();
        formData.append("data",dataSessao);
        formData.append("catecismo",catecismo);
        formData.append("turma",turma);
        formData.append("conteudo",deltaTxt);

        var xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4)
            {
                var response = xhr.response;

                if (response.includes("OK")) {
                    global.changed = false;

                    bootoast.toast({
                        message: 'Catequese virtual guardada com sucesso!',
                        type: 'success',
                        position: 'top-center',
                        icon: null,
                        timeout: 3,
                        animationDuration: 300,
                        dismissible: true
                    });
                } else {
                    //alert("Data: " + data + "\nStatus: " + status); //DEBUG
                    bootoast.toast({
                        message: response,
                        type: 'danger',
                        position: 'top-center',
                        icon: null,
                        timeout: 5,
                        animationDuration: 300,
                        dismissible: true
                    });
                }
                document.getElementById("guardar_icon").className = "glyphicon glyphicon-floppy-disk";
            }
        }

        xhr.open("POST", "guardarCatequeseVirtual.php");
        xhr.responseType = "text";
        xhr.send(formData);
    }

    <?php
    }
    ?>
</script>



<script>
// List of dates with virtual catechesis sessions, to highlight
var highlighted_dates = [
    <?php
    try
    {
        $datas_sessoes = $db->getVirtualCatechesisSessionDates($catecismo, $turma, true);
        for ($i = 0; $i < count($datas_sessoes); $i = $i + 1)
        {
            if ($i > 0)
                echo(", ");
            echo("'$datas_sessoes[$i]'");
        }
    }
    catch(Exception $e)
    {
        //Do nothing
    }
    ?>
];

$(function(){
   $('#data_sessao_group .input-group.date').datepicker({
       format: "dd-mm-yyyy",
       language: "pt",
       todayBtn: true,
       /*daysOfWeekDisabled: "0,1,2,3,4,5",
       daysOfWeekHighlighted: "6",*/
       todayHighlight: true,
       autoclose: true,
       beforeShowDay: function(date) {
           dateFormat = ("0" + date.getDate()).slice(-2) + '-' + ("0" + (date.getMonth()+1)).slice(-2) + '-' + date.getFullYear(); //We have to sum 1 to the month to make it right xD
           if (highlighted_dates.indexOf(dateFormat) >= 0) {
               return {classes: 'highlighted', tooltip: 'Catequese virtual disponível'};
           }
       }
    });
});
</script>


<script>
function data_valida(data)
{
	var pattern = /^[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}$/;
	
	return (pattern.test(data));

}


function verifica_data()
{
	var data_nasc = document.getElementById('data_sessao').value;
	
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


function validar()
{
	var data_nasc = document.getElementById('data_sessao').value;
	var nome = document.getElementById('nome').value;
	
	if(!data_valida(data_nasc) && data_nasc!=="" && data_nasc!==undefined)
        {
        	alert("A data de nascimento que introduziu é inválida. Deve ser da forma dd-mm-aaaa.");
        	return false;
        }
        
        if((data_nasc==="" || data_nasc===undefined) && (nome==="" || nome===undefined))
        {
        	$('#pesquisaLonga').modal('show');
        	return false;
        }
        
        return true;
}


</script>



<script type="text/javascript">

    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'Catequese virtual', 'height=800,width=600');
        mywindow.document.write('<html><head><title>Catequese virtual</title><link rel="stylesheet" href="css/bootstrap.min.css"/>');
        mywindow.document.write('<link rel="stylesheet" href="css/quill-1.3.6/quill.snow.css" />');
        mywindow.document.write('<style>@media print{.no-print, .no-print *  {display: none !important; }   .btn { display: none !important; } .ql-hidden { display: none !important; } } ');
        mywindow.document.write('@media screen { .only-print, .only-print * { display: none !important;	} } textarea { resize: vertical; }</style>');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10

        mywindow.addEventListener('load', function () {
            mywindow.print();
            mywindow.close();
        });

        return true;
    }


    function imprimir()
    {
        PrintElem(document.getElementById('editor-container'));
    }


    function previsualizar()
    {
        window.open("<?php echo(constant('CATECHESIS_BASE_URL'));?>/virtual/index.php?catecismo=<?php echo($catecismo);?>&turma=<?php echo($turma);?>&data_sessao=<?php echo($data_sessao);?>", '_blank');
    }

</script>



<script>
$(function () {
  $('[data-toggle="popover"]').popover({ trigger: "hover", 
                                          html: true,
                                          /*content: function () {
                                            return '<img src="'+$(this).data('img') + '" />';
                                          },*/
                                          delay: { "show": 500, "hide": 100 }
                                        });
})
</script>


</body>
</html>