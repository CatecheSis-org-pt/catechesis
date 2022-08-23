<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/PdoDatabaseManager.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . '/core/enrollment_functions.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/log_functions.php');
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');
require_once(__DIR__ . '/gui/widgets/configuration_panels/OnlineEnrollmentsActivationPanel/OnlineEnrollmentsActivationPanelWidget.php');

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\OnlineEnrollmentsActivationPanelWidget;
use catechesis\PdoDatabaseManager;
use catechesis\UserData;
use catechesis\Utils;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::ENROLMENTS);
$pageUI->addWidget($menu);
$deleteEnrollmentDialog = new ModalDialogWidget("confirmarEliminarPedidoInscricao");
$pageUI->addWidget($deleteEnrollmentDialog);
$deleteRenewalDialog = new ModalDialogWidget("confirmarEliminarPedidoRenovacao");
$pageUI->addWidget($deleteRenewalDialog);
$confirmEnrollmentDialog = new ModalDialogWidget("confirmarProcessarPedidoInscricao");
$pageUI->addWidget($confirmEnrollmentDialog);
$orderDetailsDialog = new ModalDialogWidget("detalhesPedido");
$pageUI->addWidget($orderDetailsDialog);


?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Processar pedidos de inscrição</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
    <link rel="stylesheet" href="css/custom-navbar-colors.css">
    <link rel="stylesheet" type="text/css" href="css/DataTables/datatables.min.css"/>
    <link rel="stylesheet" href="css/btn-group-hover.css">

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
        .rowlink {

            cursor: pointer;

        }
    </style>
</head>
<body>

    <?php

    $menu->renderHTML();

    $db = new PdoDatabaseManager();


    // Definir ano catequetico de referencia para esta pagina
    $ano_catequetico = Utils::currentCatecheticalYear();
    if(isset($_POST['sel_ano_catequetico']))
        $ano_catequetico = intval($_POST['sel_ano_catequetico']);

    ?>

<div class="only-print" style="position: fixed; top: 0;">
    <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
    <h3>Processar pedidos de inscrição online</h3>
    <div class="row" style="margin-bottom:20px; "></div>
</div>
<div class="row only-print" style="margin-bottom:170px; "></div>

<div class="container" id="contentor">

    <div class="no-print">
        <h2> Processar pedidos de inscrição online</h2>
    </div>
    <div class="row" style="margin-top:40px; "></div>

    <?php

    if(!Authenticator::isAdmin())
    {
        echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
        echo("</div></body></html>");
        die();
    }

    ?>


    <form role="form" action="processarInscricoesOnline.php" method="post" id="form_ano">
        <div class="form-group">
            <div class="col-xs-4">
                <label for="nome">Ano catequético:</label>
                <select name="sel_ano_catequetico" onchange="this.form.submit()">
                    <?php

                    //Get catechetical years
                    $result = null;
                    try
                    {
                        $result = $db->getCatecheticalYears();
                    }
                    catch(Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        die();
                    }

                    foreach($result as $row)
                    {
                        echo("<option value='" . $row['ano_lectivo'] . "'");
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['sel_ano_catequetico']==$row['ano_lectivo'])
                            echo(" selected");
                        echo(">");
                        echo("" . Utils::formatCatecheticalYear($row['ano_lectivo']) . "</option>\n");
                    }

                    $result = null;
                    ?>
                </select>
            </div>
        </div>
    </form>


    <?php
    $selectedTab = "Inscricoes";

    //Eliminar pedido de inscricao online
    if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="eliminarPedidoInscricao" )
    {
        $iid = intval(Utils::sanitizeInput($_POST['iid_el']));

        if(deleteEnrollmentOrder($iid))
            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Pedido de inscrição eliminado!</div>");
        else
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar pedido de inscrição.</div>");

        $selectedTab = "Inscricoes";
    }

    //Marcar pedido de inscricao como processado
    if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="processarPedidoInscricao" )
    {
        $iid = intval(Utils::sanitizeInput($_POST['iid_proc']));

        if(setEnrollmentOrderAsProcessed($iid, null))
            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Pedido de inscrição marcado como processado!</div>");
        else
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao marcar pedido de inscrição como processado.</div>");

        $selectedTab = "Inscricoes";
    }


    //Eliminar pedido de renovacao online
    if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST['op']=="eliminarPedidoRenovacao" )
    {
        $rid = intval(Utils::sanitizeInput($_POST['rid_el']));

        if(deleteRenewalOrder($rid))
            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Pedido de renovação de matrícula eliminado!</div>");
        else
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar pedido de renovação de matricula.</div>");

        $selectedTab = "Renovacoes";
    }
?>

    <!-- Tabs -->
    <ul class="nav nav-tabs">
        <li role="presentation" class="<?php if($selectedTab=="Inscricoes") echo('active');?>"><a href="#tabInscricoes" aria-controls="tabInscricoes" role="tab" data-toggle="tab">Novas inscrições</a></li>
        <li role="presentation" class="<?php if($selectedTab=="Renovacoes") echo('active');?>"><a href="#tabRenovacoes" aria-controls="tabRenovacoes" role="tab" data-toggle="tab">Renovações de matrícula</a></li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        <!-- Novas inscricoes -->
        <div role="tabpanel" class="tab-pane <?php if($selectedTab=="Inscricoes") echo('active');?>" id="tabInscricoes">
            <?php

            $pedidosInscricaoPendentes = array();
            $pedidosInscricaoProcessados = array();

            try
            {
                $pedidosInscricao = $db->getEnrollmentSubmissions($ano_catequetico);

                foreach ($pedidosInscricao as $pedido)
                {
                    if (isset($pedido['cid']) && $pedido['cid'] != "")
                        array_push($pedidosInscricaoProcessados, $pedido);
                    else
                        array_push($pedidosInscricaoPendentes, $pedido);
                }

                //Libertar recursos
                $pedidosInscricao = null;
            }
            catch(Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            }
            ?>

            <div class="row clearfix" style="margin-bottom: 20px;"></div>

            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingOne">
                        <div class="panel-title" style="font-size: 14px">
                            <a role="button" data-toggle="collapse" href="#collapseOneInscricoes" aria-expanded="<?php if(count($pedidosInscricaoPendentes)>0) echo('true'); else echo('false'); ?>" aria-controls="collapseOneInscricoes">
                                Pendentes (<?php echo(count($pedidosInscricaoPendentes)); ?>)
                            </a>
                        </div>
                    </div>
                    <div id="collapseOneInscricoes" class="panel-collapse collapse <?php if(count($pedidosInscricaoPendentes)>0) echo('in'); ?>" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            <div class="col-xs-12">
                                <?php if(count($pedidosInscricaoPendentes) > 0)
                                {?>
                                    <table class="table table-hover" id="tabela-inscricoes-pendentes">
                                        <thead>
                                        <tr>
                                            <th>ID do pedido</th>
                                            <th>Nome do catequizando</th>
                                            <th>Catecismo recomendado <span class="glyphicon glyphicon-question-sign" data-container="body" data-toggle="popover" data-placement="top" data-content="Com base na idade do catequizando ou no último catecismo frequentado, caso tenha frequentado a catequese noutra paróquia."></span></th>
                                            <th>Observações</th>
                                            <th>Ações</th> <!-- Accoes -->
                                        </tr>
                                        </thead>
                                        <tbody data-link="row" class="rowlink">
                                        <?php

                                        foreach($pedidosInscricaoPendentes as $pedido)
                                        {
                                            $iid = intval($pedido['iid']);
                                            $catequizando_nome = Utils::sanitizeOutput($pedido['nome']);
                                            $data_nascimento = Utils::sanitizeOutput($pedido['data_nasc']);
                                            $ultimo_catecismo = $pedido['ultimo_catecismo'];
                                            $observacoes = Utils::escapeSingleQuotes(Utils::doubleEscapeDoubleQuotes(Utils::doubleEscapeWhiteSpaces(Utils::sanitizeOutput($pedido['obs']))));


                                            echo("<tr class='default'>\n");

                                            echo("\t<td>" . $iid . "</td>\n");

                                            echo("\t<td><a href=\"mostrarPedidoInscricao.php?iid=" . $iid . "\" target=\"_blank\" rel=\"noopener noreferrer\" \"></a>" . $catequizando_nome . "</td>");

                                            echo("\t<td>" . computeRecommendedCatechism($data_nascimento, $ultimo_catecismo) . "º</td>\n");

                                            echo("\t<td>");
                                            if(isset($observacoes) && $observacoes != "")
                                            {
                                                echo("<span class='glyphicon glyphicon-comment' data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" title=\"Observações\" data-content=\"" . $observacoes . "\"></span>");
                                            }
                                            echo("</td>\n");

                                            echo("<td class='rowlink-skip'>");
                                            ?>
                                            <div class="btn-group btn-group-hover" role="group" aria-label="...">
                                                <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="glyphicon glyphicon-wrench"></span> Acções <span class="caret"></span></button>
                                                <ul class="dropdown-menu" role="menu">
                                                    <li><a data-toggle="modal" data-target="#confirmarProcessarPedidoInscricao" onclick="preparar_processar_pedido_inscricao(<?php echo($iid);?>)" style="cursor: pointer;"><span class="glyphicon glyphicon-ok text-success"></span>&nbsp;Marcar como processado</a></li>
                                                    <li><a data-toggle="modal" data-target="#confirmarEliminarPedidoInscricao" onclick="preparar_eliminacao_pedido_inscricao(<?php echo($iid);?>)" style="cursor: pointer;"><span class="glyphicon glyphicon-trash text-danger"></span>&nbsp;Eliminar</a></li>
                                                </ul>
                                            </div>
                                        <?php
                                            echo('</td>');

                                            echo("</tr>\n");
                                        }

                                        ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingTwo">
                        <div class="panel-title" style="font-size: 14px">
                            <a class="collapsed" role="button" data-toggle="collapse" href="#collapseTwoInscricoes" aria-expanded="false" aria-controls="collapseTwoInscricoes">
                                Processados (<?php echo(count($pedidosInscricaoProcessados)); ?>)
                            </a>
                        </div>
                    </div>
                    <div id="collapseTwoInscricoes" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                        <div class="panel-body">
                            <div class="col-xs-12">
                                <?php if(count($pedidosInscricaoProcessados) > 0)
                                {?>
                                    <table class="table table-hover" id="tabela-inscricoes-processadas">
                                        <thead>
                                        <tr>
                                            <th>ID do pedido</th>
                                            <th>Nome do catequizando</th>
                                            <th>Catecismo inscrito</th>
                                            <th>Observações</th>
                                            <th>Ações</th> <!-- Accoes -->
                                        </tr>
                                        </thead>
                                        <tbody data-link="row" class="rowlink">
                                        <?php

                                        foreach($pedidosInscricaoProcessados as $pedido)
                                        {
                                            $iid = intval($pedido['iid']);
                                            $catequizando_nome = Utils::sanitizeOutput($pedido['nome']);
                                            $data_nascimento = Utils::sanitizeOutput($pedido['data_nasc']);
                                            $ultimo_catecismo = $pedido['ultimo_catecismo'];
                                            $observacoes = Utils::escapeSingleQuotes(Utils::doubleEscapeDoubleQuotes(Utils::doubleEscapeWhiteSpaces(Utils::sanitizeOutput($pedido['obs']))));
                                            $catecismo_inscrito = "";
                                            if(isset($pedido['cid']) && $pedido['cid']!="")
                                            {
                                                try
                                                {
                                                    $catecismo_grupo = $db->getCatechumenCurrentCatechesisGroup(intval($pedido['cid']), $ano_catequetico);
                                                    if (isset($catecismo_grupo))
                                                        $catecismo_inscrito = $catecismo_grupo['ano_catecismo'] . "º" . $catecismo_grupo['turma'];
                                                }
                                                catch(Exception $e)
                                                {
                                                }
                                            }

                                            echo("<tr class='default'>\n");

                                            echo("\t<td>" . $iid . "</td>\n");

                                            echo("\t<td><a href=\"mostrarPedidoInscricao.php?iid=" . $iid . "\" target=\"_blank\" rel=\"noopener noreferrer\" \"></a>" . $catequizando_nome . "</td>");


                                            echo("\t<td>" . $catecismo_inscrito . "</td>\n");

                                            echo("\t<td>");
                                            if(isset($observacoes) && $observacoes != "")
                                            {
                                                echo("<span class='glyphicon glyphicon-comment' data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" title=\"Observações\" data-content=\"" . $observacoes . "\"></span>");
                                            }
                                            echo("</td>\n");

                                            echo("<td class='rowlink-skip'>");
                                            echo('<div class="btn-group-xs btn-group-hover pull-right" role="group" aria-label="...">');
                                            echo('<button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarEliminarPedidoInscricao" onclick="preparar_eliminacao_pedido_inscricao(' . $iid . ')"><span class="glyphicon glyphicon-trash text-danger"> Eliminar</span></button></div>'); //Botao eliminar
                                            echo('</td>');

                                            echo("</tr>\n");
                                        }

                                        ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Renovacoes de matricula -->
        <div role="tabpanel" class="tab-pane <?php if($selectedTab=="Renovacoes") echo('active');?>" id="tabRenovacoes">

            <?php

            $pedidosRenovacaoPendentes = array();
            $pedidosRenovacaoProcessados = array();

            try
            {
                $pedidosRenovacao = $db->getRenewalSubmissions($ano_catequetico);

                foreach($pedidosRenovacao as $pedido)
                {
                    if($pedido['processado'] == 1)
                        array_push($pedidosRenovacaoProcessados, $pedido);
                    else
                        array_push($pedidosRenovacaoPendentes, $pedido);
                }

                //Libertar recursos
                $pedidosRenovacao = null;
            }
            catch(Exception $e)
            {
                echo("<p><strong>Erro!</strong> " . $e->getMessage() . "</p>");
            }

            ?>

            <div class="row clearfix" style="margin-bottom: 20px;"></div>

            <div class="alert alert-info" role="alert">
                <span><b><span class="fas fa-info-circle"></b> Para processar os pedidos de renovação de matrículas vá para a página de <a href="renovacaoMatriculas.php">renovação de matrículas</a>.</span>
            </div>

            <div class="row clearfix" style="margin-bottom: 20px;"></div>

            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingOne">
                        <div class="panel-title" style="font-size: 14px">
                            <a role="button" data-toggle="collapse" href="#collapseOneRenovacoes" aria-expanded="<?php if(count($pedidosRenovacaoPendentes)>0) echo('true'); else echo('false'); ?>" aria-controls="collapseOneRenovacoes">
                                Pendentes (<?php echo(count($pedidosRenovacaoPendentes)); ?>)
                            </a>
                        </div>
                    </div>
                    <div id="collapseOneRenovacoes" class="panel-collapse collapse <?php if(count($pedidosRenovacaoPendentes)>0) echo('in'); ?>" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            <div class="col-xs-12">
                                <?php if(count($pedidosRenovacaoPendentes) > 0)
                                {?>
                                    <table class="table table-hover" id="tabela-renovacoes-pendentes">
                                        <thead>
                                        <tr>
                                            <th>ID do pedido</th>
                                            <th>Nome do catequizando</th>
                                            <th>Último catecismo frequentado</th>
                                            <th>Observações</th>
                                            <th>Ações</th> <!-- Accoes -->
                                        </tr>
                                        </thead>
                                        <tbody data-link="row" class="rowlink">
                                        <?php

                                        foreach($pedidosRenovacaoPendentes as $pedido)
                                        {
                                            $rid = intval($pedido['rid']);
                                            $enc_edu_nome = Utils::sanitizeOutput($pedido['enc_edu_nome']);
                                            $enc_edu_tel = Utils::sanitizeOutput($pedido['enc_edu_tel']);
                                            $enc_edu_email = Utils::sanitizeOutput($pedido['enc_edu_email']);
                                            $catequizando_nome = Utils::sanitizeOutput($pedido['catequizando_nome']);
                                            $ultimo_catecismo = intval($pedido['ultimo_catecismo']);
                                            $observacoes = Utils::escapeSingleQuotes(Utils::doubleEscapeDoubleQuotes(Utils::doubleEscapeWhiteSpaces(Utils::sanitizeOutput($pedido['observacoes']))));


                                            echo("<tr class='default'>\n");

                                            echo("\t<td>" . $rid . "</td>\n");

                                            echo("\t<td><a href='' data-toggle=\"modal\" data-target=\"#detalhesPedido\" onclick=\"preparar_detalhes("
                                                . $rid . ",'" . $enc_edu_nome . "','" . $enc_edu_tel . "','" . $enc_edu_email
                                                . "','" . $catequizando_nome . "'," . $ultimo_catecismo . ",'" . $observacoes . "')\"></a>");
                                            echo($catequizando_nome . "</td>");

                                            echo("\t<td>" . $ultimo_catecismo . "º</td>\n");

                                            echo("\t<td>");
                                            if(isset($observacoes) && $observacoes != "")
                                            {
                                                echo("<span class='glyphicon glyphicon-comment' data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" title=\"Observações\" data-content=\"" . $observacoes . "\"></span>");
                                            }
                                            echo("</td>\n");

                                            echo("<td class='rowlink-skip'>");
                                            echo('<div class="btn-group-xs btn-group-hover pull-right" role="group" aria-label="..."><button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarEliminarPedidoRenovacao" onclick="preparar_eliminacao_pedido_renovacao(' . $rid . ')"><span class="glyphicon glyphicon-trash text-danger"> Eliminar</span></button></div>'); //Botao eliminar
                                            echo('</td>');

                                            echo("</tr>\n");
                                        }

                                        ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingTwo">
                        <div class="panel-title" style="font-size: 14px">
                            <a class="collapsed" role="button" data-toggle="collapse" href="#collapseTwoRenovacoes" aria-expanded="false" aria-controls="collapseTwoRenovacoes">
                                Processados (<?php echo(count($pedidosRenovacaoProcessados)); ?>)
                            </a>
                        </div>
                    </div>
                    <div id="collapseTwoRenovacoes" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                        <div class="panel-body">
                            <div class="col-xs-12">
                                <?php if(count($pedidosRenovacaoProcessados) > 0)
                                {?>
                                    <table class="table table-hover" id="tabela-renovacoes-processadas">
                                        <thead>
                                        <tr>
                                            <th>ID do pedido</th>
                                            <th>Nome do catequizando</th>
                                            <th>Último catecismo frequentado</th>
                                            <th>Observações</th>
                                            <th>Ações</th> <!-- Accoes -->
                                        </tr>
                                        </thead>
                                        <tbody data-link="row" class="rowlink">
                                        <?php

                                        foreach($pedidosRenovacaoProcessados as $pedido)
                                        {
                                            $rid = intval($pedido['rid']);
                                            $enc_edu_nome = Utils::sanitizeOutput($pedido['enc_edu_nome']);
                                            $enc_edu_tel = Utils::sanitizeOutput($pedido['enc_edu_tel']);
                                            $enc_edu_email = Utils::sanitizeOutput($pedido['enc_edu_email']);
                                            $catequizando_nome = Utils::sanitizeOutput($pedido['catequizando_nome']);
                                            $ultimo_catecismo = intval($pedido['ultimo_catecismo']);
                                            $observacoes = Utils::escapeSingleQuotes(Utils::doubleEscapeDoubleQuotes(Utils::doubleEscapeWhiteSpaces(Utils::sanitizeOutput($pedido['observacoes']))));


                                            echo("<tr class='default'>\n");

                                            echo("\t<td>" . $pedido['rid'] . "</td>\n");

                                            echo("\t<td><a href='' data-toggle=\"modal\" data-target=\"#detalhesPedido\" onclick=\"preparar_detalhes("
                                                . $rid . ",'" . $enc_edu_nome . "','" . $enc_edu_tel . "','" . $enc_edu_email
                                                . "','" . $catequizando_nome . "'," . $ultimo_catecismo . ",'" . $observacoes . "')\"></a>");
                                            echo($catequizando_nome . "</td>");

                                            echo("\t<td>" . $ultimo_catecismo . "º</td>\n");

                                            echo("\t<td>");
                                            if(isset($observacoes) && $observacoes != "")
                                            {
                                                echo("<span class='glyphicon glyphicon-comment' data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" title=\"Observações\" data-content=\"" . $observacoes . "\"></span>");
                                            }
                                            echo("</td>\n");

                                            echo("<td class='rowlink-skip'>");
                                            echo('<div class="btn-group-xs btn-group-hover pull-right" role="group" aria-label="..."><button type="button" class="btn btn-default" data-toggle="modal" data-target="#confirmarEliminarPedidoRenovacao" onclick="preparar_eliminacao_pedido_renovacao(' . $rid . ')"><span class="glyphicon glyphicon-trash text-danger"> Eliminar</span></button></div>'); //Botao eliminar
                                            echo('</td>');

                                            echo("</tr>\n");
                                        }

                                        ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>




<!-- Forms e Dialogos -->

<form id="form_eliminar_pedido_inscricao" role="form" action="processarInscricoesOnline.php?op=eliminarPedidoInscricao" method="post">
    <input type="hidden" id="iid_el" name="iid_el">
</form>

<form id="form_processar_pedido_inscricao" role="form" action="processarInscricoesOnline.php?op=processarPedidoInscricao" method="post">
    <input type="hidden" id="iid_proc" name="iid_proc">
</form>

<form id="form_eliminar_pedido_renovacao" role="form" action="processarInscricoesOnline.php?op=eliminarPedidoRenovacao" method="post">
    <input type="hidden" id="rid_el" name="rid_el">
</form>



<?php

// Dialog to confirm deletion of enrollment order

$deleteEnrollmentDialog->setTitle("Confirmar eliminação");
$deleteEnrollmentDialog->setBodyContents(<<<HTML_CODE
                <p>Se existir um problema com este pedido de inscrição, é preferível contactar o requerente primeiro.
                    Se eliminar este pedido de inscrição perderá os contactos e todos os dados deste requerente.<br>
                    <b>Esta ação é irreversível.</b></p>
                <p>Tem a certeza de que pretende eliminar este pedido de inscrição?</p>
HTML_CODE
);
$deleteEnrollmentDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                       ->addButton(new Button("Sim", ButtonType::DANGER, "eliminar_pedido_inscricao()"));
$deleteEnrollmentDialog->renderHTML();



// Dialog to confirm deletion of renweal order

$deleteRenewalDialog->setTitle("Confirmar eliminação");
$deleteRenewalDialog->setBodyContents(<<<HTML_CODE
                <p>Se existir um problema com este pedido de renovação de matrícula, é preferível contactar o requerente primeiro.
                    Se eliminar este pedido de renovação de matrícula perderá os contactos deste requerente.<br>
                    <b>Esta ação é irreversível.</b></p>
                <p>Tem a certeza de que pretende eliminar este pedido de renovação de matrícula?</p>
HTML_CODE
);
$deleteRenewalDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                    ->addButton(new Button("Sim", ButtonType::DANGER, "eliminar_pedido_renovacao()"));
$deleteRenewalDialog->renderHTML();



// Dialog to mark an enrollment order as processed

$confirmEnrollmentDialog->setTitle("Confirmar alteração de estado");
$confirmEnrollmentDialog->setBodyContents(<<<HTML_CODE
                <p>Se marcar aqui manualmente o pedido como processado, <b>não está a realizar nenhuma inscrição</b>.<br>
                O procedimento habitual para processar um pedido de inscrição é clicar sobre a linha correspondente na tabela,
                que abrirá a respetiva ficha. Depois poderá confirmar os dados inseridos pelo requerente e aprovar a inscrição.
                Quando a inscrição for aprovada, o pedido ficará automaticamente marcado como processado.
                </p>
                <p>Tem a certeza de que pretende marcar este pedido como processado?</p>
HTML_CODE
);
$confirmEnrollmentDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                        ->addButton(new Button("Sim", ButtonType::DANGER, "processar_pedido_inscricao()"));
$confirmEnrollmentDialog->renderHTML();



// Dialog to show renewal order details

$orderDetailsDialog->setTitle("Detalhes do pedido");
$orderDetailsDialog->setBodyContents(<<<HTML_CODE
                <p><span><b>ID: </b></span><span id="dialogo_id"></span></p>
                <div style="margin-top: 20px"></div>
                <p><span><b>Nome do catequizando: </b></span><span id="dialogo_catequizando"></span></p>
                <p><span><b>Último catecismo frequentado: </b></span><span id="dialogo_catecismo"></span></p>
                <div style="margin-top: 20px"></div>
                <p><span><b>Encarregado de educação: </b></span><span id="dialogo_enc_edu"></span></p>
                <p><span><b>Telefone: </b></span><span id="dialogo_tel"></span></p>
                <p><span><b>Email: </b></span><span id="dialogo_email"></span></p>
                <div style="margin-top: 20px"></div>
                <p><span><b>Observações: </b><br></span><span id="dialogo_observações"></span></p>
HTML_CODE
);
$orderDetailsDialog->addButton(new Button("Fechar", ButtonType::SECONDARY));
$orderDetailsDialog->renderHTML();
?>



<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/rowlink.js"></script>
<script src="js/btn-group-hover.js"></script>
<script type="text/javascript" src="js/DataTables/datatables.min.js"></script>

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

    $(document).ready( function () {
        $('#tabela-inscricoes-pendentes').DataTable({
            paging: false,
            info: false,
            language: {
                url: 'js/DataTables/Portuguese.json'
            }
        });
    });

    $(document).ready( function () {
        $('#tabela-inscricoes-processadas').DataTable({
            paging: false,
            info: false,
            language: {
                url: 'js/DataTables/Portuguese.json'
            }
        });
    } );

    $(document).ready( function () {
        $('#tabela-renovacoes-pendentes').DataTable({
            paging: false,
            info: false,
            language: {
                url: 'js/DataTables/Portuguese.json'
            }
        });
    });

    $(document).ready( function () {
        $('#tabela-renovacoes-processadas').DataTable({
            paging: false,
            info: false,
            language: {
                url: 'js/DataTables/Portuguese.json'
            }
        });
    } );
</script>

<script>

    function preparar_eliminacao_pedido_inscricao(iid)
    {
        document.getElementById("iid_el").value = iid;
    }


    function eliminar_pedido_inscricao()
    {
        document.getElementById("form_eliminar_pedido_inscricao").submit();
    }


    function preparar_eliminacao_pedido_renovacao(rid)
    {
        document.getElementById("rid_el").value = rid;
    }


    function eliminar_pedido_renovacao()
    {
        document.getElementById("form_eliminar_pedido_renovacao").submit();
    }


    function preparar_processar_pedido_inscricao(iid)
    {
        document.getElementById("iid_proc").value = iid;
    }

    function processar_pedido_inscricao()
    {
        document.getElementById("form_processar_pedido_inscricao").submit();
    }

    function preparar_detalhes(rid, enc_edu_nome, enc_edu_tel, enc_edu_email, catequizando_nome, ultimo_catecismo, observacoes)
    {
        document.getElementById("dialogo_id").innerText = rid;
        document.getElementById("dialogo_enc_edu").innerText = enc_edu_nome;
        document.getElementById("dialogo_tel").innerHTML = "<a href=\"tel:" + enc_edu_tel + "\">" + enc_edu_tel + "</a>";
        document.getElementById("dialogo_email").innerHTML = "<a href=\"mailto:" + enc_edu_email + "\">" + enc_edu_email + "</a>";
        document.getElementById("dialogo_catequizando").innerText = catequizando_nome;
        document.getElementById("dialogo_catecismo").innerText = ultimo_catecismo + "º";
        document.getElementById("dialogo_observações").innerHTML = observacoes;
    }
</script>

</body>
</html>