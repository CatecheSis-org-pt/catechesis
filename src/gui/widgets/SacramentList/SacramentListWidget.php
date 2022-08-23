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
 * A widget to show a list of catechumens that received a particular sacrament.
 * It contains the columns name, birthdate, sacrament date and parish.
 * Also includes an export function to PDF and Excel.
 */
class SacramentListWidget extends AbstractCatechumensListingWidget
{
    private /*PDO result array*/ $catechumens_list;             // Stores the list of catechumens to show in the list widget
    private /*string*/ $entities_name_singular = "resultado";   // Name to use in the results header to refer to the entities in the list (e.g. "results" or "baptisms")
    private /*string*/ $entities_name_plural = "resultados";    // Plural form of the previous string

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
     * to better adjust to the context (e.g. "results", "baptisms" or "first comunions").
     * Two arguments can be passed, corresponding to the singular and plural form.
     * If the latter is ommitted, the plural form is assumed to follow the regular form (where an 's' is added
     * to the singular form)
     * Default value is "resultado".
     * @param string $name
     * @return $this
     */
    public function setEntitiesName(string $singular, string $plural = null)
    {
        $this->entities_name_singular = $singular;

        if(isset($plural))
            $this->entities_name_plural = $plural;
        else
            $this->entities_name_plural = $this->entities_name_singular . "s";

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
        <div id="<?=$this->getID()?>" class="sacrament_list_widget<?= $this->getCustomClassesString()?>" style="<?=$this->getCustomInlineStyle()?>">

            <!-- Botoes imprimir, transferir (Excel e PDF) -->
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
            </div>

            <form target="_blank" action="transferirListagemSacramento.php" method="post" id="<?=$this->getID()?>_transferir_form" name="<?=$this->getID()?>_transferir_form">
                <input type="hidden" name="file_type" id="<?=$this->getID()?>_transferir_tipo" value="xls">
                <input type="hidden" name="entity_name_singular" id="<?=$this->getID()?>_entity_name_singular" value="<?= $this->entities_name_singular?>">
                <input type="hidden" name="entity_name_plural" id="<?=$this->getID()?>_entity_name_plural" value="<?= $this->entities_name_plural?>">
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
                        <h1 class="results_header"><small><span id="<?=$this->getID()?>_numero_resultados"></span><?php if(count($this->catechumens_list)==0) echo("Sem"); else echo(count($this->catechumens_list));?> <?php if(count($this->catechumens_list)>1) echo($this->entities_name_plural); else echO($this->entities_name_singular); ?></small></h1>
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
                                    <h3>Listagem de <?= $this->entities_name_plural ?></h3>
                                    <div class="row" style="margin-bottom:50px; "></div>
                                </div>
                                Nome</th>
                            <th>Data nascimento</th>
                            <th>Data de <?= $this->entities_name_singular ?></th>
                            <th>Paróquia</th>
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
                            $sacramentInOurParish = Utils::sacramentParish($row['paroquia']) == 1; //true if the sacrament took place in our parish, false otherwise
                            ?>

                            <tr>
                                <td data-container="body" data-toggle="popover" data-placement="top" data-content="<img src='<?php
                            if($foto && $foto!="")
                                echo("resources/catechumenPhoto.php?foto_name=$foto");
                            else
                                echo("img/default-user-icon-profile.png");
                            ?>' style='height:133px;'>">
                                    <a href="mostrarArquivo.php?cid=<?=$row['cid']?>" target="_blank"></a><?= Utils::sanitizeOutput($row['nome']) ?>
                                </td>

                                <td data-order="<?=strtotime($row['data_nasc'])?>"><span data-container="body" data-toggle="popover" data-placement="top" data-content="<?= date_diff(date_create($row['data_nasc']), date_create('today'))->y ?> anos"><?=date( "d-m-Y", strtotime($row['data_nasc']))?></span></td>

                                <td data-order="<?=strtotime($row['data_sacramento'])?>"><span><?=date( "d-m-Y", strtotime($row['data_sacramento']))?></span></td>

                                <td><span <?php if($sacramentInOurParish) echo('class="badge badge-primary"');?>><?= Utils::sanitizeOutput($row['paroquia']) ?></span></td>
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
                $(document).ready( function () {
                    var table_<?= $this->getID(); ?> = $('#<?=$this->getID()?>_resultados').DataTable({
                        paging: false,
                        info: false,
                        language: {
                            url: 'js/DataTables/Portuguese.json'
                        },
                        "aaSorting": [], //Do not sort anything at start, to keep the provided order (only when the user clicks on a column),
                        "columnDefs": [
                            { "width": "40%", "targets": 0 },
                            { "width": "15%", "targets": 1 },
                            { "width": "15%", "targets": 2 },
                            { "width": "30%", "targets": 3 },
                        ]
                    });


                //Redraw columns with new widths
                redraw_datatable('#<?=$this->getID()?>_resultados', table_<?= $this->getID(); ?>);

                //Redraw columns every time the browser window is resized
                window.addEventListener('resize', function(){
                    redraw_datatable('#<?=$this->getID()?>_resultados', table_<?= $this->getID(); ?>);
                    });

                });
            </script>
    <?php

        // NOTE: The remaining JS code for this widget is static (only needs to be imported once per page, even if there are multiple
        // instances of this widget), so it is abstracted away in the file AbstractCatechumensListingWidget.js
    }
}