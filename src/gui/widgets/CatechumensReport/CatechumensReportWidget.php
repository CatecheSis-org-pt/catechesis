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
* A widget composed by one or many accordion panels, each of which containing a list of
 * catechumens with the usual columns (name, birthdate, catechism, attributes and sacraments),
 * plus a column names "report" wihch shows a message in color green/yellow/red.
 * This is useful to report the results of an analysis or metric over a set of catechumens.
 */
class CatechumensReportWidget extends AbstractCatechumensListingWidget
{
    private /*array*/  $report_contents;                    // Stores groups of lists of catechumens to show in each of the report widget accordions


    public function __construct(string $id = null)
    {
        parent::__construct($id);
        // This widget's dependencies are inherited from AbstractCatechumensListingWidget
    }


    /**
     * Appends a new group of results to the report, which will be shown in an accordion.
     * A group of results consists of a title (to be shown in the accordion header),
     * a list of objects representing catechumens data to report in this group,
     * and (optinally) a string to be shown in case this group of results is empty.
     *
     * All catechumens lists must be set prior to calling renderHTML().
    *
    * @param string $header - Title of the accordion tab.
    * @param array $catechumensList - list of objects with fields "catequizando" and "relatorio". The first has the catechumen details, the latter has the report data.
    * @param bool $startExpanded - set if this accordion tab should be expanded at the widget startup (otherwise, it will start in collapsed state)
    * @param string|null $emptyString - string to be shown in case there are no results in this group
    * @return $this
    */
    public function addCatechumensList(string $header, array $catechumensList, bool $startExpanded = false, string $emptyString = null)
    {
        $reportEntry = array();
        $reportEntry["header"] = $header;
        $reportEntry["empty_string"] = ($emptyString?:"Sem catequizandos");
        $reportEntry["start_expanded"] = $startExpanded;
        $reportEntry["catechumens_list"] = $catechumensList;

        $this->report_contents[] = $reportEntry;

        return $this;
    }




    /**
    * Writes "name (relationship)" for the responsible of a catechumen
    * @param $candidate
    * @return string
    */
    private function formatResponsible($candidate)
	{
		$catechumen = $candidate['catequizando'];
		if($catechumen['enc_edu'] == $catechumen['pai'])
			return "Pai";
		else if($catechumen['enc_edu'] == $catechumen['mae'])
			return "Mãe";
		else
			return $catechumen['enc_edu_quem'];
	}


    /**
    * Determines the color of the report cell, based on the severity of the reported issues.
    * @param $candidate
    * @return string
    */
	private function reportColor($candidate)
	{
		if(sizeof($candidate['relatorio']['fatais']) > 0)
			return "danger";
		else if(sizeof($candidate['relatorio']['avisos']) > 0)
			return "warning";
		else if(sizeof($candidate['relatorio']['info']) > 0)
			return "info";
		else
			return "success";
	}


    /**
    * Assigns a number to the severity level of the issues reported for a catechumen.
    * @param $candidate
    * @return int
    */
	private function severyLevel($candidate)
	{
		if(sizeof($candidate['relatorio']['fatais']) > 0)
			return 3;
		else if(sizeof($candidate['relatorio']['avisos']) > 0)
			return 2;
		else if(sizeof($candidate['relatorio']['info']) > 0)
			return 1;
		else
			return 0;
	}




    //NOTE: The renderCSS() method is inherited from AbstractCatechumensListingWidget.



    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        ?>
        <!-- Catechumens report widget -->
        <div id="<?=$this->getID()?>" class="catechumens_report_widget<?= $this->getCustomClassesString()?>" style="<?=$this->getCustomInlineStyle()?>">

            <!-- Buttons to print, show/hide attributes and sacraments -->
            <div class="clearfix"></div>
            <div class="btn-group no-print">
                <?php
                if(isset($this->additional_toolbar_buttons))
                    echo($this->additional_toolbar_buttons);
                ?>
                <button type="button" onclick="window.print()" class="btn btn-default no-print"><span class="glyphicon glyphicon-print"></span> Imprimir</button>

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

            <!-- Header of the report -->
            <div class="row no-print" style="margin-top:20px; "></div>
            <div class="page-header" style="position:relative; z-index:2;">
                <div class="row">
                    <div class="col-md-4 pull-left">
                        <h1 class="results_header"><small>Relatório</small></h1>
                    </div>
                    <div class="col-md-8 pull-right">
                        <div id="<?=$this->getID()?>_legenda_sacramentos" class="pull-right" style="<?php if(!$this->sacraments_shown) echo('opacity:0.0;');?>"> <span><span class="label label-success">&nbsp;</span> Nesta paróquia</span> &nbsp; <span><span class="label label-default">&nbsp;</span> Noutra paróquia</span>  <span><span class="badge-green" data-badge="">&nbsp;&nbsp;</span> Comprovativo</span></div>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>

            <!-- Results -->
            <div class="col-xs-12">
                <!--<div class="only-print" style="margin-top: -150px; position:relative; z-index:1;"></div>-->
                <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

                        <?php
                        $index = 0;
                        foreach($this->report_contents as $group)
                        {
                            $groupHeader = $group["header"];
                            $catechumensList = $group["catechumens_list"];
                            $startExpanded = $group["start_expanded"];
                        ?>
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="heading_<?= $this->getID($index); ?>">
                              <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" href="#collapse_<?= $this->getID($index); ?>" aria-expanded="<?= ($startExpanded?"true":"false") ?>" aria-controls="collapse_<?= $this->getID($index); ?>"> <!--data-parent="#accordion"-->
                                  <?= $groupHeader ?> ( <?php echo(sizeof($catechumensList)); ?> )
                                </a>
                              </h4>
                            </div>
                            <div id="collapse_<?= $this->getID($index); ?>" class="panel-collapse collapse <?= ($startExpanded?"in":"") ?>" role="tabpanel" aria-labelledby="heading_<?= $this->getID($index); ?>">
                            <?php
                              if(sizeof($catechumensList) <= 0)
                              {
                                echo("<div class=\"panel-body\">". $group["empty_string"] . "</div>");
                              }
                              else
                              {
                                echo("<div class=\"panel-body\">\n");
                                echo("</div>\n");
                                $this->renderTable($catechumensList, $index);
                              }
                            ?>
                            </div>
                        </div>
                        <?php
                            $index++;
                        }
                        ?>

                        <table>
                            <tfoot class="only-print">
                                <tr>
                                    <td colspan="4"><?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER); ?></td>
                                </tr>
                            </tfoot>
                        </table>

                </div>
            </div>
        <?php
    }


    /**
    * Renders a report table, given a list of results (catechumens) to show.
    * @param $candidates - list of objects with fields "catequizando" and "relatorio". The first has the catechumen details, the latter has the report data.
    * @param int $index - index of this results table inside the widget (to assign it a unique ID)
     */
    private function renderTable($candidates, int $index)
    {
        if(count($candidates) > 0)
        {?>
            <table class="table table-hover" id="<?=$this->getID($index)?>_resultados" style="width:100%; margin-bottom: 0px !important;">
                <thead>
                <tr>
                    <th style="background-color: transparent;">
                        <div class="only-print" style="opacity:0.0;">
                            <img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
                            <h3>Relatório</h3>
                            <div class="row" style="margin-bottom:50px; "></div>
                        </div>
                        Nome</th>
                    <th class="<?=$this->getID()?>_col_atributos" data-field="<?=$this->getID()?>_col_atributos" style="text-align: right; max-width:50px; opacity:0">Atributos</th> <!-- Coluna de simbolos/icones vários -->
                    <th>Catecismo (<?php echo('' . intval(Utils::currentCatecheticalYear() / 10000) . '/' . intval(Utils::currentCatecheticalYear() % 10000)); ?>)</th>
                    <th class="<?=$this->getID()?>_col_sacramentos" data-field="<?=$this->getID()?>_col_sacramentos" <?php if(!$this->sacraments_shown) echo('style="max-width:0px; opacity:0"'); ?>>Sacramentos</th>
                    <th class="col_contactos only-print" data-searchable="false">Contactos</th>
                    <th>Relatório</th>
                </tr>
                </thead>
                <tbody data-link="row" class="rowlink">
                <?php
                foreach($candidates as $candidato)
                {
                    $row = $candidato['catequizando'];

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
                    $enc_edu = $this->formatResponsible($candidato);
                    $telefone = intval($row['telefone']);
                    $telemovel = intval($row['telemovel']);
                    $email = Utils::sanitizeOutput($row['email']);

                    //Numerical catechism order for DataTables
                    $catechismOrder = $row['ano_catecismo']?($row['ano_catecismo']*100 + Utils::toNumber(Utils::sanitizeOutput($row['turma']))):0;

                    //Numerical report column order for DataTables
                    $reportOrder = $this->severyLevel($candidato);

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


                    <td data-order="<?= $catechismOrder ?>" style="text-align:right; padding-right: 5%;"><?=($row['ano_catecismo']?($row['ano_catecismo'] . "º" . Utils::sanitizeOutput($row['turma'])):"-")?></td>

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

                    <td class="col_contactos only-print" data-searchable="false">
                    <?php
                    echo("(" . $enc_edu . ")<br>\n");
                    if($telemovel != 0)
                        echo("Telemóvel: " . $telemovel . "<br>\n");
                    if ($telefone != 0)
                        echo("Telefone: " . $telefone . "<br>\n");
                    if(isset($email) && $email!="")
                        echo("E-mail: " . $email . "<br>\n");
                    ?>
                    </td>


                    <td data-order="<?= $reportOrder ?>" class="<?= $this->reportColor($candidato)?>">
                    <?php
                    if(sizeof($candidato['relatorio']['fatais']) > 0 || sizeof($candidato['relatorio']['avisos']) > 0 || sizeof($candidato['relatorio']['info']) > 0)
                    {
                        //echo("<ul>\n");
                        foreach($candidato['relatorio']['fatais'] as $problema)
                        {
                            echo("<span class='glyphicon glyphicon-remove-sign text-danger'>&nbsp;</span>" . $problema . "<br>\n");
                        }
                        foreach($candidato['relatorio']['avisos'] as $problema)
                        {
                            echo("<span class='glyphicon glyphicon-exclamation-sign text-warning'>&nbsp;</span>" . $problema . "<br>\n");
                        }
                        foreach($candidato['relatorio']['info'] as $problema)
                        {
                            echo("<span class='glyphicon glyphicon-info-sign text-info'>&nbsp;</span>" . $problema . "<br>\n");
                        }
                        //echo("</ul>\n");
                    }
                    else
                    {
                        echo("<span><span class='glyphicon glyphicon-ok'>&nbsp;</span>Não foram detetados problemas.</span>\n");
                    }
                    ?>
                    </td>

                    </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
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
                    <?php
                    $index = 0;
                    foreach($this->report_contents as $group)
                    {
                        if($group['catechumens_list'])
                        {
                        ?>
                            var table_<?= $this->getID($index); ?> = $('#<?= $this->getID($index); ?>_resultados').DataTable({
                                paging: false,
                                info: false,
                                language: {
                                    url: 'js/DataTables/Portuguese.json'
                                },
                                "dom": '<"wrapper"<"col-sm-12" f> lipt>', //Customizations to make the table fill the accordion wihtout padding
                                "aaSorting": [], //Do not sort anything at start, to keep the provided order (only when the user clicks on a column),
                                "columnDefs": [
                                    { "width": "30%", "targets": 0 },
                                    { "width": "15%", "targets": 1 },
                                    { "width": "5%", "targets": 2 },
                                    { "width": "5%", "targets": 3 },
                                    { "width": "5%", "targets": 4 },
                                    { "width": "30%", "targets": 5 }
                                ]
                            });


                            //Redraw columns with new widths
                            redraw_datatable('#<?= $this->getID($index); ?>_resultados', table_<?= $this->getID($index); ?>);

                            //Redraw columns with new widths every time a hidden accordion is shown
                            $('#collapse_<?= $this->getID($index); ?>').on('shown.bs.collapse', function () {
                                   redraw_datatable('#<?= $this->getID($index); ?>_resultados', table_<?= $this->getID($index); ?>);
                            });

                            //Redraw columns every time the browser window is resized
                            window.addEventListener('resize', function(){
                                redraw_datatable('#<?= $this->getID($index); ?>_resultados', table_<?= $this->getID($index); ?>);
                            });

                    <?php
                        }

                        $index++;
                    }
                    ?>
                } );
            </script>
    <?php

        // NOTE: The remaining JS code for this widget is static (only needs to be imported once per page, even if there are multiple
        // instances of this widget), so it is abstracted away in the file AbstractCatechumensListingWidget.js
    }
}