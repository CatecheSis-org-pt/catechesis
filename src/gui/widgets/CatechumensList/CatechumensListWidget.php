<?php


namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractCatechumensListing/AbstractCatechumensListingWidget.php');
require_once(__DIR__ . '/../../../core/Configurator.php');
require_once(__DIR__ . '/../../../core/Utils.php');
require_once(__DIR__ . '/../../../core/UserData.php');
require_once(__DIR__ . '/../../../core/absence_statistics.php');

use catechesis\Configurator;
use catechesis\UserData;
use catechesis\Utils;


/**
 * A widget consisting in a list of catechumens, with several columns: name,
 * birthdate, current catechism, attributes and sacraments.
 * Attributes and sacraments are hidden by default, but can be shown by clicking
 * on a button in a toolbar.
 * Also includes an export function to PDF and Excel.
 */
class CatechumensListWidget extends AbstractCatechumensListingWidget
{
    private /*PDO result array*/ $catechumens_list;         // Stores the list of catechumens to show in the list widget
    private /*string*/ $entities_name = "resultado";        // Name to use in the results header to refer to the entities in the list (e.g. "results" or "catechumens")
    private /*bool*/ $is_selector = false;                  // Whether the widget works as a simple list or as a selector (with selectable 3D cards)
    private /*string*/ $selector_column_name = "Selecionar";  // Name for the column that holds the switch buttons
    private /*string*/ $selector_field_name = "presenca[]";    // Name for the field containing the checkbox in the widget
    private /*array*/ $selector_selected_cids = [];          // List of cids that are initially selected
    private /*string*/ $selector_on_text = "&nbsp;&nbsp;&nbsp;&nbsp;";      // String displayed in the "on" state of the switch
    private /*string*/ $selector_off_text = "&nbsp;&nbsp;&nbsp;&nbsp;";        // String displayed in the "off" state of the switch
    private /*bool*/ $show_attendance = false;               // Whether to show the attendance column in the list view
    private /*int*/ $attendance_catechetical_year = null;     // Catechetical year to show attendance for
    private /*bool*/ $show_attributes_feature = true;        // Whether to show the "Mostrar atributos" button and corresponding columns
    private /*bool*/ $show_sacraments_feature = true;         // Whether to show the "Mostrar sacramentos" button and corresponding columns
    private /*bool*/ $show_catechism_feature = true;          // Whether to show the "Catecismo" column

    public function __construct(string $id = null)
    {
        parent::__construct($id);
        // This widget's dependencies are inherited from AbstractCatechumensListingWidget

        // Additional dependencies for the selector mode (switches)
        $this->addCSSDependency('css/bootstrap-switch.css');
        $this->addJSDependency('js/bootstrap-switch.js');

        // Static CSS styles of this widget that are common to all instances (only imported once)
        $this->addCSSDependency('gui/widgets/CatechumensList/CatechumensListWidget.css');
    }


    /**
     * Sets whether the widget works as a simple list or as a selector (with selectable 3D cards).
     * @param bool $isSelector
     * @return $this
     */
    public function setupSelector(string $categoryName, string $positiveClass, string $negativeClass, string $fieldName)
    {
        $this->is_selector = true;
        $this->selector_column_name = $categoryName;
        $this->selector_on_text = $positiveClass;
        $this->selector_off_text = $negativeClass;
        $this->selector_field_name = $fieldName;
        return $this;
    }


    /**
     * Sets the list of cids that are initially selected when is_selector is true.
     * @param array $selectedCids
     * @return $this
     */
    public function setSelectorSelectedCids(array $selectedCids)
    {
        $this->selector_selected_cids = $selectedCids;
        return $this;
    }



    /**
     * Sets whether to show the attendance column in the list view.
     * @param bool $show
     * @param int|null $year If null, the current catechetical year is used.
     * @return $this
     */
    public function showAttendance(bool $show=true, int $year = null)
    {
        $this->show_attendance = $show;
        $this->attendance_catechetical_year = $year ?? intval(Utils::currentCatecheticalYear());
        return $this;
    }


    /**
     * Sets whether to show the "Mostrar atributos" button and corresponding columns.
     * @param bool $show
     * @return $this
     */
    public function showAttributes(bool $show = true)
    {
        $this->show_attributes_feature = $show;
        return $this;
    }


    /**
     * Sets whether to show the "Mostrar sacramentos" button and corresponding columns.
     * @param bool $show
     * @return $this
     */
    public function showSacraments(bool $show = true)
    {
        $this->show_sacraments_feature = $show;
        return $this;
    }


    /**
     * Sets whether to show the "Catecismo" column.
     * @param bool $show
     * @return $this
     */
    public function showCatechism(bool $show = true)
    {
        $this->show_catechism_feature = $show;
        return $this;
    }


    /**
     * Sets the list of catechumens to render in this list widget.
     * This must be set prior to calling renderHTML().
     * @param $catechumensList
     * @return $this
     */
    public function setCatechumensList($catechumensList)
    {
        $this->catechumens_list = $catechumensList;
        return $this;
    }



    /**
     * Customize the name of the entities listed in this widget, that is shown in the results header,
     * to better adjust to the context (e.g. "results", "catechumens" or "scouts").
     * The argument should be passed in singular form (an 's' is automatically added for plural forms).
     * Default value is "resultado".
     * @param string $name
     * @return $this
     */
    public function setEntitiesName(string $name)
    {
        $this->entities_name = $name;
        return $this;
    }



    //NOTE: The renderCSS() method is inherited from AbstractCatechumensListingWidget.


    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        ?>

        <!-- Catechumens list widget -->
        <div id="<?=$this->getID()?>" class="catechumens_list_widget<?= $this->getCustomClassesString()?>" style="<?=$this->getCustomInlineStyle()?>">

            <!-- Tabs -->
            <ul class="nav nav-pills" style="float: right; position: relative; z-index: 10;">
                <li role="presentation" class="active" data-toggle="tooltip" data-placement="top" title="Vista de lista"><a href="#<?=$this->getID()?>_tabList" aria-controls="<?=$this->getID()?>_tabList" role="tab" data-toggle="tab"><i class="fas fa-th-list"></i></a></li>
                <li role="presentation" class="" data-toggle="tooltip" data-placement="top" title='Vista de cartões "Quem é quem"'><a href="#<?=$this->getID()?>_tabGrid" aria-controls="<?=$this->getID()?>_tabGrid" role="tab" data-toggle="tab"><i class="fas fa-id-card-alt"></i></a></li>
            </ul>

            <div class="tab-content" style="padding-top:60px;">
                <!-- List view -->
                <div role="tabpanel" class="tab-pane active" id="<?=$this->getID()?>_tabList">
                <?php
                $this->renderListViewHTML();
                ?>
                </div>

                <!-- Grid view -->
                <div role="tabpanel" class="tab-pane catechumens-grid-panel" id="<?=$this->getID()?>_tabGrid">
                    <?php
                    $this->renderGridViewHTML();
                    ?>
                </div>
            </div>

        </div>
        <?php
    }

    public function renderGridViewHTML()
    {
        $cardsPerPage = 12;
        $totalCards = count($this->catechumens_list);
        $totalPages = ceil($totalCards / $cardsPerPage);
        ?>
        <?php if($this->is_selector): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-hand-pointer"></i> Clique nos cartões para os levantar ou baixar. Marque catequizandos levantando os respetivos cartões, e desmarque baixando os cartões.
        </div>
        <?php endif; ?>
        <div class="panel panel-default catechumens-ground-plane" id="<?=$this->getID()?>_catechist_groups_panel">
            <div class="panel-body">
                <?php
                $cardCounter = 0;
                $rowCounter = 0;
                $pageCounter = 0;
                foreach($this->catechumens_list as $row)
                {
                    $foto = Utils::sanitizeOutput($row['foto']);
                    $cid = intval($row['cid']);

                    if($cardCounter % $cardsPerPage == 0)
                    {
                        $pageCounter++;
                        echo('<div class="catechumens-grid-page" data-page="' . $pageCounter . '" ' . ($pageCounter > 1 ? 'style="display:none;"' : '') . '>');
                    }

                    if($cardCounter % 4 == 0)
                    {
                        echo('<div class="row clearfix" style="margin-bottom: 40px;');
                        if(($rowCounter+1)%2==0)
                            echo(' margin-left: 100px;');
                        echo('">');
                        $rowCounter++;
                    }
                    $cardCounter++;

                ?>

                <div class="col-sm-3">
                    <?php $isSelected = in_array($cid, $this->selector_selected_cids); ?>
                    <div class="catechumen-card <?= ($this->is_selector?'catechumen-card-selectable':'') ?> <?= ($isSelected?'selected':'') ?>" data-cid="<?= $cid ?>" onclick="<?= ($this->is_selector?'toggle_catechumen_selection(this)':'window.open(\'mostrarFicha.php?cid=' . $cid . '\');') ?>" >
                        <div class="catechumen-card-inner">
                            <div class="catechumen-card-front">
                                <img src="<?php
                                if($foto && $foto!="")
                                    echo("resources/catechumenPhoto.php?foto_name=$foto");
                                else
                                    echo("img/default-user-icon-profile.png");
                                ?>">
                                <h3><?= Utils::firstAndLastName(Utils::sanitizeOutput($row['nome'])) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                    if($cardCounter % 4 == 0 || $cardCounter == $totalCards)
                        echo("</div>");
                    
                    if($cardCounter % $cardsPerPage == 0 || $cardCounter == $totalCards)
                        echo("</div>");
                }
                ?>
            </div>
        </div>
        <?php if($totalPages > 1): ?>
        <div class="row text-center no-print">
            <ul class="pagination pagination-sm catechumens-grid-pagination" id="<?= $this->getID() ?>_pagination">
                <li class="disabled"><a href="#" onclick="change_grid_page('<?= $this->getID() ?>', 'prev'); return false;">&laquo;</a></li>
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="<?= ($i == 1 ? 'active' : '') ?>"><a href="#" onclick="change_grid_page('<?= $this->getID() ?>', <?= $i ?>); return false;"><?= $i ?></a></li>
                <?php endfor; ?>
                <li><a href="#" onclick="change_grid_page('<?= $this->getID() ?>', 'next'); return false;">&raquo;</a></li>
            </ul>
        </div>
        <?php endif; ?>
    <?php
    }

    /**
     * @inheritDoc
     */
    public function renderListViewHTML()
    {
        ?>

        <!-- Botoes imprimir, transferir (Excel e PDF), mostrar/ocultar atributos e sacramentos -->
        <div class="clearfix"></div>
        <div class="btn-group no-print">
            <?php
            if(isset($this->additional_toolbar_buttons))
                echo($this->additional_toolbar_buttons);
            ?>
            <?php if(!$this->is_selector): ?>
            <button type="button" onclick="window.print()" class="btn btn-default no-print"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
            <div class="btn-group">
                <button type="button" onclick="" class="btn btn-default dropdown-toggle no-print" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-export"></span> Exportar <span class="caret"></span></button>
                <ul class="dropdown-menu">
                    <li><a href="#" onclick="download_results('<?=$this->getID()?>', 'xls');"><img src="img/excel_icon.png" style="width: 10%; height: 10%;"/> Como Microsoft Excel 97-2003 (.xls) <span style="margin-right: 20px;"></span></a></li>
                    <li><a href="#" onclick="download_results('<?=$this->getID()?>', 'pdf');"><img src="img/pdf_icon.png" style="width: 10%; height: 10%;"/> Como PDF (.pdf) <span style="margin-right: 20px;"></span></a></li>
                </ul>
            </div>
            <?php endif; ?>
            <?php if($this->show_attributes_feature): ?>
                <button type="button" onclick="show_hide_catechumen_attributes('<?=$this->getID()?>')" class="btn btn-default no-print" id="<?=$this->getID()?>_botao_atributos"><span class="glyphicon glyphicon-eye-open"></span> Mostrar atributos</button>
            <?php endif; ?>

            <?php if($this->show_sacraments_feature): ?>
                <?php
                if($this->sacraments_shown)
                {?>
                    <button type="button" onclick="show_hide_catechumen_sacraments('<?=$this->getID()?>')" class="btn btn-default no-print" id="<?=$this->getID()?>_botao_sacramentos"><span class="glyphicon glyphicon-eye-close"></span> Ocultar sacramentos</button>
                <?php
                }
                else
                {?>
                    <button type="button" onclick="show_hide_catechumen_sacraments('<?=$this->getID()?>')" class="btn btn-default no-print" id="<?=$this->getID()?>_botao_sacramentos"><span class="glyphicon glyphicon-eye-open"></span> Mostrar sacramentos</button>
                <?php
                } ?>
            <?php endif; ?>
        </div>

        <?php if(!$this->is_selector): ?>
        <form target="_blank" action="transferirResultadosPesquisa.php" method="post" id="<?=$this->getID()?>_transferir_form" name="<?=$this->getID()?>_transferir_form">
            <input type="hidden" name="file_type" id="<?=$this->getID()?>_transferir_tipo" value="xls">
            <input type="hidden" name="entity_name" id="<?=$this->getID()?>_entity_name" value="<?= $this->entities_name ?>">
            <?php
            //Generate list of cid as required by the download script
            foreach($this->catechumens_list as $catechumen)
            {?>
                <input type="hidden" name="catechumens_list[]" value="<?= $catechumen['cid'] ?>">
            <?php
            }?>
        </form>
        <?php endif; ?>


        <!-- Cabecalho com Num Resultados -->
        <div class="row no-print" style="margin-top:20px; "></div>
        <div class="page-header" style="position:relative; z-index:2;">
            <div class="row">
                <div class="col-md-4 pull-left">
                    <h1 class="results_header"><small><span id="<?=$this->getID()?>_numero_resultados"></span><?php if(count($this->catechumens_list)==0) echo("Sem"); else echo(count($this->catechumens_list));?> <?= $this->entities_name ?><?php if(count($this->catechumens_list)!=1) echo("s"); ?></small></h1>
                </div>
                <div class="col-md-8 pull-right">
                    <div id="<?=$this->getID()?>_legenda_sacramentos" class="pull-right" style="<?php if(!$this->sacraments_shown || !$this->show_sacraments_feature) echo('opacity:0.0;');?>"> <span><span class="label label-success">&nbsp;</span> Nesta paróquia</span> &nbsp; <span><span class="label label-default">&nbsp;</span> Noutra paróquia</span>  <span><span class="badge-green" data-badge="">&nbsp;&nbsp;</span> Comprovativo</span></div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>


        <?php
        if(count($this->catechumens_list) > 0)
        {?>
            <!-- Resultados -->
            <div class="col-xs-12">
                <div class="only-print" style="margin-top: -150px; position:relative; z-index:1;"></div>

                <?php if($this->is_selector && count($this->catechumens_list) >= 1): ?>
                    <div class="no-print">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="<?=$this->getID()?>_checkbox_geral" class="selector-switch-geral" checked>
                                    <span style="margin-left: 10px; vertical-align: middle;">Todos</span>
                                </th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="row" style="margin-top:20px; "></div>
                <?php endif; ?>

                <table class="table table-hover" id="<?=$this->getID()?>_resultados">
                    <thead>
                    <tr>
                        <th style="background-color: transparent;">
                            <div class="only-print" style="opacity:0.0;">
                                <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
                                <h3>Pesquisa de catequizandos</h3>
                                <div class="row" style="margin-bottom:50px; "></div>
                            </div>
                            <?php if($this->is_selector): ?>
                                <?= $this->selector_column_name ?>
                            <?php else: ?>
                                Nome
                            <?php endif; ?>
                        </th>
                        <?php if($this->is_selector): ?>
                            <th>Nome</th>
                        <?php endif; ?>
                            <?php if($this->show_attributes_feature): ?>
                                <th class="<?=$this->getID()?>_col_atributos" data-field="<?=$this->getID()?>_col_atributos" style="text-align: right; max-width:50px; opacity:0">Atributos</th> <!-- Coluna de simbolos/icones vários -->
                                <?php if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED)) { ?>
                                    <th class="<?=$this->getID()?>_col_atributos" data-field="<?=$this->getID()?>_col_atributos" style="opacity:0">NIF</th>
                                <?php } ?>
                            <?php endif; ?>
                            <th>Data nascimento</th>
                        <?php if($this->show_catechism_feature): ?>
                            <th style="text-align:right;">Catecismo (<?= Utils::formatCatecheticalYear(Utils::currentCatecheticalYear()) ?>)</th>
                        <?php endif; ?>
                        <?php if($this->show_attendance): ?>
                            <th>Presenças</th>
                        <?php endif; ?>
                        <?php if($this->show_sacraments_feature): ?>
                            <th class="<?=$this->getID()?>_col_sacramentos" data-field="<?=$this->getID()?>_col_sacramentos" <?php if(!$this->sacraments_shown) echo('style="max-width:0px; opacity:0"'); ?>>Sacramentos</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tfoot class="only-print">
                    <tr>
                        <td colspan="<?= ($this->is_selector ? 2 : 1) + ($this->show_attributes_feature ? (Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED) ? 2 : 1) : 0) + 1 + ($this->show_catechism_feature ? 1 : 0) + ($this->show_attendance ? 1 : 0) + ($this->show_sacraments_feature ? 1 : 0) ?>"><?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER); ?></td>
                    </tr>
                    </tfoot>
                    <tbody data-link="row" class="rowlink">
                    <?php
                    foreach($this->catechumens_list as $row)
                    {
                        $cid = intval($row['cid']);
                        $foto = Utils::sanitizeOutput($row['foto']);
                        $paroquia_batismo = Utils::sanitizeOutput($row['paroquia_batismo']);
                        $comprovativo_batismo = Utils::sanitizeOutput($row['comprovativo_batismo']);
                        $paroquia_comunhao = Utils::sanitizeOutput($row['paroquia_comunhao']);
                        $comprovativo_comunhao = Utils::sanitizeOutput($row['comprovativo_comunhao']);
                        $paroquia_crisma = Utils::sanitizeOutput($row['paroquia_crisma']);
                        $comprovativo_crisma = Utils::sanitizeOutput($row['comprovativo_crisma']);
                        $escuteiro = (intval($row['escuteiro']) == 1);
                        $observacoes = Utils::sanitizeOutput($row['obs']);
                        $autorizou_fotos = (intval($row['autorizou_fotos']) == 1);
                        $autorizou_saida = (intval($row['autorizou_saida_sozinho']) == 1);

                        //Numerical catechism order for DataTables
                        $catechismOrder = $row['ano_catecismo']?($row['ano_catecismo']*100 + Utils::toNumber(Utils::sanitizeOutput($row['turma']))):0;

                        $isSelected = in_array($cid, $this->selector_selected_cids);
                        $rowClass = "";
                        if($this->is_selector)
                            $rowClass = $isSelected ? "success" : "danger";


                        $popoverAttributes = "data-container='body' data-toggle='popover' data-placement='top' data-content=\"<img src='";
                        if($foto && $foto!="")
                            $popoverAttributes .= "resources/catechumenPhoto.php?foto_name=$foto";
                        else
                            $popoverAttributes .= "img/default-user-icon-profile.png";
                        $popoverAttributes .= "' style='height:133px;'>\"";


                        ?>
                        <tr id="<?=$this->getID()?>_row_<?=$cid?>" data-cid="<?=$cid?>" class="<?= $rowClass ?>">
                            <td <?= (!$this->is_selector ? $popoverAttributes : "") ?>>
                                <?php if($this->is_selector): ?>
                                    <input type="checkbox" class="selector-switch" name="<?= $this->selector_field_name ?>" value="<?= $cid ?>" <?= $isSelected ? "checked" : "" ?>>
                                <?php else: ?>
                                    <a href="mostrarFicha.php?cid=<?=$cid?>" target="_blank"></a><?= Utils::sanitizeOutput($row['nome']) ?>
                                <?php endif; ?>
                            </td>

                        <?php if($this->is_selector): ?>
                            <td <?= $popoverAttributes ?>>
                                <a href="mostrarFicha.php?cid=<?=$cid?>" target="_blank"></a><?= Utils::sanitizeOutput($row['nome']) ?>
                            </td>
                        <?php endif; ?>

                        <?php if($this->show_attributes_feature): ?>
                            <?php
                            // Atributos
                            ?>
                            <td class="<?=$this->getID()?>_col_atributos" data-field="<?=$this->getID()?>_col_atributos" style="max-width:50px; opacity:0">

                            <?php if(isset($observacoes) && $observacoes!="")
                            {?>
                                <span class='glyphicon glyphicon-comment' data-placement='top' data-toggle='popover' title='Observações' data-content='<?= $observacoes ?>' style='float:right'></span>
                            <?php
                            }
                            else
                            {?>
                                <span class='glyphicon glyphicon-comment' style='float:right; opacity: 0.0;'></span>
                            <?php
                            }

                            if($escuteiro)
                            {?>
                                <span class='fas fa-campground' style='float:right; margin-inline: 5px;' data-placement='top' data-toggle='popover' data-content='Escuteiro'>&nbsp;</span>
                            <?php
                            }
                            else
                            {?>
                                <span class='fas fa-campground' style='float:right; margin-inline: 5px; opacity: 0.0;'>&nbsp;</span>
                            <?php
                            }

                            if($autorizou_saida==0)
                            {?>
                                <span class='icon-stack' style='float:right' data-toggle="popover" data-placement="top" data-content="O catequizando NÂO está autorizado a sair sozinho.">
                                <i class='fas fa-door-open icon-stack-base'></i>
                                <i class='fas fa-ban ban-overlay'></i>
                            </span>
                                <?php
                            }
                            else
                            { ?>
                                <span class='fas fa-door-open' style='float:right; margin-inline: 5px; opacity: 1.0;' data-placement='top' data-toggle='popover' data-content='O catequizando pode sair sozinho.'>&nbsp;</span>
                                <?php
                            }

                            if($autorizou_fotos==0)
                            {?>
                                <span class='icon-stack' style='float:right' data-toggle="popover" data-placement="top" data-content="NÃO autoriza a utilização e divulgação de fotografias do educando.">
                                    <i class='fas fa-camera icon-stack-base'></i>
                                    <i class='fas fa-ban ban-overlay'></i>
                                </span>
                            <?php
                            }
                            else
                            { ?>
                                <span class='icon-stack' style='float:right; opacity: 0.0;'> </span>
                            <?php
                            }
                            ?>

                            </td>
                            <?php //--Atributos ?>


                            <?php if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED)) { ?>
                                    <td class="<?=$this->getID()?>_col_atributos" data-field="<?=$this->getID()?>_col_atributos" style="opacity:0;">
                                        <?= Utils::sanitizeOutput($row['nif']) ?>
                                    </td>
                                <?php } ?>
                        <?php endif; ?>

                            <td data-order="<?=strtotime($row['data_nasc'])?>"><span data-container="body" data-toggle="popover" data-placement="top" data-content="<?= date_diff(date_create($row['data_nasc']), date_create('today'))->y ?> anos"><?=date( "d-m-Y", strtotime($row['data_nasc']))?></span></td>

                        <?php if($this->show_catechism_feature): ?>
                            <td data-order="<?= $catechismOrder ?>" style="text-align:right; padding-right: 40px;"><?=($row['ano_catecismo']?($row['ano_catecismo'] . "º" . Utils::sanitizeOutput($row['turma'])):"-")?></td>
                        <?php endif; ?>

                        <?php if($this->show_attendance): ?>
                            <?php
                            $stats = getCatechumenAttendanceStats($cid, $this->attendance_catechetical_year, $row['ano_catecismo'] ? intval($row['ano_catecismo']) : null, $row['turma']);
                            $percentage = $stats['percentage'];
                            $attended = $stats['attended'];
                            $totalSessions = $stats['total'];
                            ?>
                            <td style="width: 20%; min-width: 150px; vertical-align: middle;">
                                <div class="progress" style="margin-bottom: 0; height: 15px; position: relative; width: 60%; float: left; margin-right: 5px;">
                                    <div class="progress-bar <?= ($percentage < 50.0) ? 'progress-bar-danger' : '' ?>" role="progressbar" aria-valuenow="<?= $attended ?>" aria-valuemin="0" aria-valuemax="<?= $totalSessions ?>" style="width: <?= $percentage ?>%;">
                                    </div>
                                </div>
                                <span style="font-size: 11px; font-weight: bold;"><?= $attended ?> / <?= $totalSessions ?></span>
                            </td>
                        <?php endif; ?>

                        <?php if($this->show_sacraments_feature): ?>
                            <td class="<?=$this->getID()?>_col_sacramentos" <?php if(!$this->sacraments_shown) echo('style="max-width:0px; opacity:0"');?> >

                            <?php
                            switch(Utils::sacramentParish($paroquia_batismo))
                            {
                                case 1:
                                    echo("<span class=\"label label-success " . ((isset($comprovativo_batismo) && $comprovativo_batismo!=null)?"badge-green\" data-badge=\"\"":"\"") . " data-toggle=\"tooltip\" data-placement=\"top\" title=\"Batismo\">B</span>");
                                    break;
                                case 2:
                                    echo("<span class=\"label label-default " . ((isset($comprovativo_batismo) && $comprovativo_batismo!=null)?"badge-green\" data-badge=\"\"":"\"") . " data-toggle=\"tooltip\" data-placement=\"top\" title=\"Batismo\">B</span>");
                                    break;
                            }
                            switch(Utils::sacramentParish($paroquia_comunhao))
                            {
                                case 1:
                                    echo("<span class=\"label label-success " . ((isset($comprovativo_comunhao) && $comprovativo_comunhao!=null)?"badge-green\" data-badge=\"\"":"\"") . " data-toggle=\"tooltip\" data-placement=\"top\" title=\"Eucaristia (Primeira Comunhão)\">E</span>");
                                    break;
                                case 2:
                                    echo("<span class=\"label label-default " . ((isset($comprovativo_comunhao) && $comprovativo_comunhao!=null)?"badge-green\" data-badge=\"\"":"\"") . " data-toggle=\"tooltip\" data-placement=\"top\" title=\"Eucaristia (Primeira Comunhão)\">E</span>");
                                    break;
                            }
                            switch(Utils::sacramentParish($paroquia_crisma))
                            {
                                case 1:
                                    echo("<span class=\"label label-success " . ((isset($comprovativo_crisma) && $comprovativo_crisma!=null)?"badge-green\" data-badge=\"\"":"\"") . " data-toggle=\"tooltip\" data-placement=\"top\" title=\"Confirmação (Crisma)\">C</span>");
                                    break;
                                case 2:
                                    echo("<span class=\"label label-default " . ((isset($comprovativo_crisma) && $comprovativo_crisma!=null)?"badge-green\" data-badge=\"\"":"\"") . " data-toggle=\"tooltip\" data-placement=\"top\" title=\"Confirmação (Crisma)\">C</span>");
                                    break;
                            }
                            ?>
                            </td>
                        <?php endif; ?>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>

        <?php
        }
    }



    /**
     * @inheritDoc
     */
    public function renderJS()
    {
        ?>
        <script type="text/javascript">
            function toggle_catechumen_selection(element)
            {
                $(element).toggleClass('selected');
                <?php if($this->is_selector): ?>
                // Synchronize with list view switch
                var cid = $(element).data('cid');
                var state = $(element).hasClass('selected');
                var switchElement = $('#<?=$this->getID()?>_row_' + cid + ' .selector-switch');
                if (switchElement.bootstrapSwitch('state') !== state) {
                    switchElement.bootstrapSwitch('state', state, true);
                    update_row_class(cid, state);
                }
                <?php endif; ?>
            }

            <?php if($this->is_selector): ?>
            function update_row_class(cid, state) {
                var row = $('#<?=$this->getID()?>_row_' + cid);
                if (state) {
                    row.removeClass('danger').addClass('success');
                } else {
                    row.removeClass('success').addClass('danger');
                }
            }
            <?php endif; ?>

            function change_grid_page(widgetId, target) {
                var pagination = $('#' + widgetId + '_pagination');
                var currentPage = pagination.find('li.active').find('a').text();
                var totalPages = pagination.find('li').length - 2;

                var targetPage = target;
                if (target === 'prev') {
                    targetPage = parseInt(currentPage) - 1;
                } else if (target === 'next') {
                    targetPage = parseInt(currentPage) + 1;
                }

                if (targetPage < 1 || targetPage > totalPages) {
                    return;
                }

                // Update active page in pagination
                pagination.find('li').removeClass('active');
                pagination.find('li').eq(targetPage).addClass('active');

                // Update disabled state for prev/next
                pagination.find('li:first-child').toggleClass('disabled', targetPage === 1);
                pagination.find('li:last-child').toggleClass('disabled', targetPage === totalPages);

                // Show target page and hide others
                var widgetPanel = $('#' + widgetId + '_catechist_groups_panel');
                widgetPanel.find('.catechumens-grid-page').hide();
                widgetPanel.find('.catechumens-grid-page[data-page="' + targetPage + '"]').show();
            }

            $(function () {
                $('[data-toggle="popover"]').popover({ trigger: "hover",
                    html: true,
                    delay: { "show": 500, "hide": 100 }
                });
            });

            // Initialize variables defined in the common JS code for this widget instance
            <?php if($this->show_attributes_feature): ?>
                set_attributes_visibility('<?=$this->getID();?>', false);
            <?php endif; ?>
            <?php if($this->show_sacraments_feature): ?>
                set_sacraments_visibility('<?=$this->getID();?>', <?php if($this->sacraments_shown) echo('true'); else echo('false'); ?>);
            <?php endif; ?>

            $(document).ready( function () {
                var table_<?= $this->getID(); ?> = $('#<?=$this->getID()?>_resultados').DataTable({
                    paging: false,
                    info: false,
                    language: {
                        url: 'js/DataTables/Portuguese.json'
                    },
                    "aaSorting": [], //Do not sort anything at start, to keep the provided order (only when the user clicks on a column),
                    "columnDefs": [
                        <?php
                        $colIdx = 0;
                        if($this->is_selector): ?>
                            { "width": "5%", "targets": <?= $colIdx++ ?> }, // Switch
                            { "width": "35%", "targets": <?= $colIdx++ ?> }, // Name
                            <?php if($this->show_attributes_feature): ?>
                                { "width": "10%", "targets": <?= $colIdx++ ?> }, // Attributes
                                <?php if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED)): ?>
                                    { "width": "10%", "targets": <?= $colIdx++ ?> }, // NIF
                                <?php endif; ?>
                            <?php endif; ?>
                            { "width": "10%", "targets": <?= $colIdx++ ?> }, // Birthdate
                            <?php if($this->show_catechism_feature): ?>
                                { "width": "10%", "targets": <?= $colIdx++ ?> }, // Catechism
                            <?php endif; ?>
                            <?php if($this->show_attendance): ?>
                                { "width": "10%", "targets": <?= $colIdx++ ?> }, // Attendance
                            <?php endif; ?>
                            <?php if($this->show_sacraments_feature): ?>
                                { "width": "10%", "targets": <?= $colIdx++ ?> }, // Sacraments
                            <?php endif; ?>

                        <?php else: ?>
                            { "width": "40%", "targets": <?= $colIdx++ ?> }, // Name
                            <?php if($this->show_attributes_feature): ?>
                                { "width": "10%", "targets": <?= $colIdx++ ?> }, // Attributes
                                <?php if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED)): ?>
                                    { "width": "10%", "targets": <?= $colIdx++ ?> }, // NIF
                                <?php endif; ?>
                            <?php endif; ?>
                            { "width": "10%", "targets": <?= $colIdx++ ?> }, // Birthdate
                            <?php if($this->show_catechism_feature): ?>
                                { "width": "10%", "targets": <?= $colIdx++ ?> }, // Catechism
                            <?php endif; ?>
                            <?php if($this->show_attendance): ?>
                                { "width": "10%", "targets": <?= $colIdx++ ?> }, // Attendance
                            <?php endif; ?>
                            <?php if($this->show_sacraments_feature): ?>
                                { "width": "10%", "targets": <?= $colIdx++ ?> }, // Sacraments
                            <?php endif; ?>
                        <?php endif; ?>
                    ]
                });

                //Redraw columns with new widths
                redraw_datatable('#<?=$this->getID()?>_resultados', table_<?= $this->getID(); ?>);

                //Redraw columns every time the browser window is resized
                window.addEventListener('resize', function(){
                    redraw_datatable('#<?=$this->getID()?>_resultados', table_<?= $this->getID(); ?>);
                });

                <?php if($this->is_selector): ?>
                // Initialize bootstrap-switch for the selector switches
                $("#<?=$this->getID()?> .selector-switch").bootstrapSwitch({
                    size: 'mini',
                    onText: '<?= $this->selector_on_text ?>',
                    offText: '<?= $this->selector_off_text ?>',
                    onColor: 'success',
                    offColor: 'danger'
                });

                $("#<?=$this->getID()?> .selector-switch-geral").bootstrapSwitch({
                    size: 'mini',
                    onText: '<?= $this->selector_on_text ?>',
                    offText: '<?= $this->selector_off_text ?>',
                    onColor: 'success',
                    offColor: 'danger'
                });

                // Handle switch change events (synchronize with Grid view)
                $('#<?=$this->getID()?> .selector-switch').on('switchChange.bootstrapSwitch', function(event, state) {
                    var cid = $(this).closest('tr').data('cid');
                    update_row_class(cid, state);
                    
                    // Synchronize with Grid view card
                    var card = $('#<?=$this->getID()?> .catechumen-card[data-cid="' + cid + '"]');
                    if (state) {
                        card.addClass('selected');
                    } else {
                        card.removeClass('selected');
                    }
                });

                // Handle global switch change
                $('#<?=$this->getID()?> .selector-switch-geral').on('switchChange.bootstrapSwitch', function(event, state) {
                    $('#<?=$this->getID()?> .selector-switch').bootstrapSwitch('state', state);
                });

                // Initialize initial row colors
                $('#<?=$this->getID()?> .selector-switch').each(function() {
                    var cid = $(this).closest('tr').data('cid');
                    update_row_class(cid, $(this).bootstrapSwitch('state'));
                });
                <?php endif; ?>
            } );

            $('#<?=$this->getID()?>_tabList').parent().tooltip();
            $('#<?=$this->getID()?>_tabGrid').parent().tooltip();

        </script>
        <?php

        // NOTE: The remaining JS code for this widget is static (only needs to be imported once per page, even if there are multiple
        // instances of this widget), so it is abstracted away in the file AbstractCatechumensListingWidget.js
    }
}