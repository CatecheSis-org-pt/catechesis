<?php


namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractCatechumensListing/AbstractCatechumensListingWidget.php');
require_once(__DIR__ . '/../../../core/Configurator.php');
require_once(__DIR__ . '/../../../core/Utils.php');
require_once(__DIR__ . '/../../../core/UserData.php');

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

    public function __construct(string $id = null)
    {
        parent::__construct($id);
        // This widget's dependencies are inherited from AbstractCatechumensListingWidget
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

            <!-- Botoes imprimir, transferir (Excel e PDF), mostrar/ocultar atributos e sacramentos -->
            <div class="clearfix"></div>
            <div class="btn-group no-print">
                <?php
                if(isset($this->additional_toolbar_buttons))
                    echo($this->additional_toolbar_buttons);
                ?>
                <button type="button" onclick="window.print()" class="btn btn-default no-print"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
                <div class="btn-group">
                    <button type="button" onclick="" class="btn btn-default dropdown-toggle no-print" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-export"></span> Exportar <span class="caret"></span></button>
                    <ul class="dropdown-menu">
                        <li><a href="#" onclick="download_results('<?=$this->getID()?>', 'xls');"><img src="img/excel_icon.png" style="width: 10%; height: 10%;"/> Como Microsoft Excel 97-2003 (.xls) <span style="margin-right: 20px;"></span></a></li>
                        <li><a href="#" onclick="download_results('<?=$this->getID()?>', 'pdf');"><img src="img/pdf_icon.png" style="width: 10%; height: 10%;"/> Como PDF (.pdf) <span style="margin-right: 20px;"></span></a></li>
                    </ul>
                </div>
                <button type="button" onclick="show_hide_catechumen_attributes('<?=$this->getID()?>')" class="btn btn-default no-print" id="<?=$this->getID()?>_botao_atributos"><span class="glyphicon glyphicon-eye-open"></span> Mostrar atributos</button>

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
            </div>

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


            <!-- Cabecalho com Num Resultados -->
            <div class="row no-print" style="margin-top:20px; "></div>
            <div class="page-header" style="position:relative; z-index:2;">
                <div class="row">
                    <div class="col-md-4 pull-left">
                        <h1 class="results_header"><small><span id="<?=$this->getID()?>_numero_resultados"></span><?php if(count($this->catechumens_list)==0) echo("Sem"); else echo(count($this->catechumens_list));?> <?= $this->entities_name ?><?php if(count($this->catechumens_list)!=1) echo("s"); ?></small></h1>
                    </div>
                    <div class="col-md-8 pull-right">
                        <div id="<?=$this->getID()?>_legenda_sacramentos" class="pull-right" style="<?php if(!$this->sacraments_shown) echo('opacity:0.0;');?>"> <span><span class="label label-success">&nbsp;</span> Nesta paróquia</span> &nbsp; <span><span class="label label-default">&nbsp;</span> Noutra paróquia</span>  <span><span class="badge-green" data-badge="">&nbsp;&nbsp;</span> Comprovativo</span></div>
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
                    <table class="table table-hover" id="<?=$this->getID()?>_resultados">
                        <thead>
                        <tr>
                            <th style="background-color: transparent;">
                                <div class="only-print" style="opacity:0.0;">
                                    <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
                                    <h3>Pesquisa de catequizandos</h3>
                                    <div class="row" style="margin-bottom:50px; "></div>
                                </div>
                                Nome</th>
                            <th class="<?=$this->getID()?>_col_atributos" data-field="<?=$this->getID()?>_col_atributos" style="text-align: right; max-width:50px; opacity:0">Atributos</th> <!-- Coluna de simbolos/icones vários -->
                            <th>Data nascimento</th>
                            <th>Catecismo (<?php echo('' . intval(Utils::currentCatecheticalYear() / 10000) . '/' . intval(Utils::currentCatecheticalYear() % 10000)); ?>)</th>
                            <th class="<?=$this->getID()?>_col_sacramentos" data-field="<?=$this->getID()?>_col_sacramentos" <?php if(!$this->sacraments_shown) echo('style="max-width:0px; opacity:0"'); ?>>Sacramentos</th>
                        </tr>
                        </thead>
                        <tfoot class="only-print">
                        <tr>
                            <td colspan="4"><?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER); ?></td>
                        </tr>
                        </tfoot>
                        <tbody data-link="row" class="rowlink">
                        <?php
                        foreach($this->catechumens_list as $row)
                        {
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


                            ?>
                            <tr>
                                <td data-container="body" data-toggle="popover" data-placement="top" data-content="<img src='<?php
                            if($foto && $foto!="")
                                echo("resources/catechumenPhoto.php?foto_name=$foto");
                            else
                                echo("img/default-user-icon-profile.png");
                            ?>' style='height:133px;'>">
                                    <a href="mostrarFicha.php?cid=<?=$row['cid']?>" target="_blank"></a><?= Utils::sanitizeOutput($row['nome']) ?>
                                </td>

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


                            <td data-order="<?=strtotime($row['data_nasc'])?>"><span data-container="body" data-toggle="popover" data-placement="top" data-content="<?= date_diff(date_create($row['data_nasc']), date_create('today'))->y ?> anos"><?=date( "d-m-Y", strtotime($row['data_nasc']))?></span></td>

                            <td data-order="<?= $catechismOrder ?>" style="text-align:right; padding-right: 10%;"><?=($row['ano_catecismo']?($row['ano_catecismo'] . "º" . Utils::sanitizeOutput($row['turma'])):"-")?></td>

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
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

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
            // Initialize variables defined in the common JS code for this widget instance
            set_attributes_visibility('<?=$this->getID();?>', false);
            set_sacraments_visibility('<?=$this->getID();?>', <?php if($this->sacraments_shown) echo('true'); else echo('false'); ?>);

            $(document).ready( function () {
                var table_<?= $this->getID(); ?> = $('#<?=$this->getID()?>_resultados').DataTable({
                    paging: false,
                    info: false,
                    language: {
                        url: 'js/DataTables/Portuguese.json'
                    },
                    "aaSorting": [], //Do not sort anything at start, to keep the provided order (only when the user clicks on a column),
                    "columnDefs": [
                        { "width": "30%", "targets": 0 },
                        { "width": "40%", "targets": 1 },
                        { "width": "10%", "targets": 2 },
                        { "width": "10%", "targets": 3 },
                        { "width": "10%", "targets": 4 }
                    ]
                });

                //Redraw columns with new widths
                redraw_datatable('#<?=$this->getID()?>_resultados', table_<?= $this->getID(); ?>);

                //Redraw columns every time the browser window is resized
                window.addEventListener('resize', function(){
                    redraw_datatable('#<?=$this->getID()?>_resultados', table_<?= $this->getID(); ?>);
                });
            } );
        </script>
        <?php

        // NOTE: The remaining JS code for this widget is static (only needs to be imported once per page, even if there are multiple
        // instances of this widget), so it is abstracted away in the file AbstractCatechumensListingWidget.js
    }
}