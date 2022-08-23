<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../configuration_panels/AbstractSettingsPanel/AbstractSettingsPanelWidget.php');;
require_once(__DIR__ . '/../../../core/domain/Sacraments.php');
require_once(__DIR__ . '/../../../core/log_functions.php');
require_once(__DIR__ . '/../../../core/DataValidationUtils.php');
require_once(__DIR__ . '/../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../core/Configurator.php');
require_once(__DIR__ . '/../../../core/UserData.php');
require_once(__DIR__ . '/../../../core/Utils.php');

use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\UserData;
use core\domain\Sacraments;
use catechesis\DataValidationUtils;
use catechesis\Configurator;


class SacramentRecordPanelWidget extends AbstractSettingsPanelWidget
{
    private $ACTION_EDIT_SACRAMENT = "edit_sacrament_";
    private $ACTION_DELETE_PROOF = "del_comprovativo_";

    private /*int*/ $_sacrament = null;         // Type of sacrament in this panel
    private /*int*/ $_cid = null;               // ID of the catechumen
    private /*bool*/ $_has_sacrament = false;   // Whether this catechumen has received this sacrament
    private /*string*/ $_date = null;           // Date of the sacrament
    private /*string*/ $_parish = null;         // Parish where the sacrament was received
    private /*string*/ $_proof_file = null;     // Sacrament proof file name
    private $_deleteProofDialog = null;         // Dialog to confirm removal of sacrament proof

    public function __construct(string $id = null)
    {
        parent::__construct($id, false);

        parent::setTitle(""); //This will be set up later on setData()
        parent::setURL($_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']);
        parent::useHeaderButtons(true);

        // Create delete proof confirmation dialog
        $this->_deleteProofDialog = new ModalDialogWidget("confirm_sacrament_proof_delete_dialog_" . $this->getID());


        // Declare this widget's dependencies
        $this->addCSSDependency('css/jquery.fileupload.css');
        $this->addCSSDependency('css/dropzone.css');
        $this->addJSDependency('js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js');
        $this->addJSDependency('js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js');
        $this->addJSDependency('js/jquery.ui.widget.js');
        $this->addJSDependency('js/jquery.iframe-transport.js');    //The Iframe Transport is required for browsers without support for XHR file uploads
        $this->addJSDependency('js/jquery.fileupload.js');          //The basic File Upload plugin
        $this->addJSDependency('js/form-validation-utils.js');
        $this->addJSDependency('gui/widgets/SacramentRecordPanel/SacramentRecordPanelWidget.js');

        // Add dependencies used by the modal dialog
        foreach($this->_deleteProofDialog->getCSSDependencies() as $dependency)
            $this->addCSSDependency($dependency);
        foreach($this->_deleteProofDialog->getJSDependencies() as $dependency)
            $this->addJSDependency($dependency);
    }


    /**
     * Fill this panel with the necessary data for display.
     * @param int $sacrament - Type of the sacrament (one of class' Sacraments:: constants).
     * @param int $cid - ID of the catechumen.
     * @param bool $hasSacrament - Whether the catechumen has received this sacrament.
     * @param string|null $date - The date when the sacrament was received.
     * @param string|null $parish - The parish where the sacrament was received.
     * @param string|null $proofFile - Name of the file containing the sacrament proof document.
     * @return void
     */
    public function setData(int $sacrament, int $cid, bool $hasSacrament, string $date=null, string $parish=null, string $proofFile=null)
    {
        $this->_sacrament = $sacrament;
        $this->_cid = $cid;
        $this->_has_sacrament = $hasSacrament;
        $this->_date = $date;
        $this->_parish = $parish;
        $this->_proof_file = $proofFile;

        //Adjust POST action strings
        $this->ACTION_EDIT_SACRAMENT .= Sacraments::toInternalString($this->_sacrament);
        $this->ACTION_DELETE_PROOF .= Sacraments::toInternalString($this->_sacrament);

        // Set panel title according to the sacrament
        parent::setTitle(Sacraments::toExternalString($this->_sacrament));

        //Adjust the panel header color based on the value of $_has_sacrament (green/gray)
        if($this->_has_sacrament)
            $this->setPanelStyle("panel-success");
        else
            $this->setPanelStyle("panel-default");
    }


    /**
     * @inheritDoc
     */
    protected function renderBody()
    {
        ?>
        <div class="form-group">

            <!-- Sacrament date -->
            <div class="col-xs-4">
                <div class="input-append date" id="<?= $this->getID() ?>_sacrament_date_div" data-date="" data-date-format="dd-mm-yyyy">
                    <label for="<?= $this->getID() ?>_sacrament_date_input">Data:</label>
                    <input class="form-control" id="<?= $this->getID() ?>_sacrament_date_input" name="sacrament_date" size="16" type="text" placeholder="dd-mm-aaaa" style="cursor: auto;" <?php if($this->_has_sacrament){ echo('value="' . date( "d-m-Y", strtotime($this->_date)) . '"');} ?> onclick="check_sacrament_date('<?= $this->getID() ?>_sacrament_date_input', '<?= $this->getID() ?>_sacrament_date_div', '<?= $this->getID() ?>_sacrament_date_error_icon')" onchange="check_sacrament_date('<?= $this->getID() ?>_sacrament_date_input', '<?= $this->getID() ?>_sacrament_date_div', '<?= $this->getID() ?>_sacrament_date_error_icon')" readonly>
                    <span id="<?= $this->getID() ?>_sacrament_date_error_icon" class="glyphicon glyphicon-remove form-control-feedback" style="display:none;"></span>
                    <input id="<?= $this->getID() ?>_sacrament_date_backup" type="hidden" style="display: none;" <?php if($this->_has_sacrament){ echo('value="' . date( "d-m-Y", strtotime($this->_date)) . '"');} ?> readonly>
                </div>
            </div>

            <!-- Sacrament parish -->
            <div class="col-xs-8">
                <label for="<?= $this->getID() ?>_sacrament_parish"> Paróquia: </label>
                <input type="text" class="form-control" id="<?= $this->getID() ?>_sacrament_parish" name="sacrament_parish"  list='paroquias' style="cursor: auto;" <?php if($this->_has_sacrament){ echo('value="' . $this->_parish . '"');} ?> readonly>
                <input type="hidden" id="<?= $this->getID() ?>_sacrament_parish_backup" style="display: none;" <?php if($this->_has_sacrament){ echo('value="' . $this->_parish . '"');} ?> readonly>
            </div>

            <input type="hidden" name="op" style="display: none;" value="<?= $this->ACTION_EDIT_SACRAMENT ?>" readonly>
        </div>
        <div class="clearfix"></div>


        <!-- Proof document -->
        <?php
        if($this->_date!=null && $this->_date!=="")
        {
            ?>
            <div class="row" style="margin-top:20px; "></div>
            <form id="<?= $this->getID() ?>_fileupload" action="carregarComprovativo.php" method="POST" enctype="multipart/form-data">
                <div class="col-xs-12 fade dropzone" id="<?= $this->getID() ?>_drop_zone" onmouseover="$('#<?= $this->getID() ?>_drop_zone').addClass('hover'); document.getElementById('<?= $this->getID() ?>_upload_tooltip').style = 'display: block';" onmouseout="$('#<?= $this->getID() ?>_drop_zone').removeClass('hover'); document.getElementById('<?= $this->getID() ?>_upload_tooltip').style = 'display: none';">
                    <?php
                    if($this->_proof_file!=null)
                    { ?>
                        <div class="col-xs-10">
                            <a href="descarregaComprovativo.php?cid=<?= $this->_cid ?>&sacramento=<?= Sacraments::toInternalString($this->_sacrament) ?>" target="_blank"><span class="glyphicon glyphicon-file"></span> Comprovativo</a>
                            <small id="<?= $this->getID() ?>_upload_tooltip" class="no-print" style="display:none; color: #777"> <i> Arraste para aqui um comprovativo em PDF para substituir </i> </small>
                        </div>
                        <?php
                    }
                    else
                    { ?>
                        <span class="col-xs-8" style="color: #777">
                            <i>Sem comprovativo</i> <br>
                            <small id="<?= $this->getID() ?>_upload_tooltip" class="no-print" style="display:none"> <i> Arraste para aqui um comprovativo em PDF </i> </small>
                        </span>
                        <?php
                    }?>

                    <div class="btn-group btn-group-xs pull-right no-print" role="group" aria-label="...">
                        <span class="btn btn-default fileinput-button"><i class="glyphicon glyphicon-open"></i><input id="<?= $this->getID() ?>_fileupload" type="file" name="files[]"></span>
                        <?php if($this->_proof_file!=null)
                        { ?>
                            <span class="btn btn-default" data-toggle="modal" data-target="#confirm_sacrament_proof_delete_dialog_<?= $this->getID() ?>">
                                <i class="glyphicon glyphicon-trash text-danger"></i>
                            </span>
                            <?php
                        } ?>
                    </div>

                    <input type="hidden" name="cid" value="<?= $this->_cid ?>">
                    <input type="hidden" name="sacramento" value="<?= Sacraments::toInternalString($this->_sacrament) ?>">

                    <!-- The global progress bar -->
                    <div id="<?= $this->getID() ?>_progress_group" class="no-print" style="display: none">
                        <br><br>
                        <div id="<?= $this->getID() ?>_progress" class="progress no-print">
                            <div class="progress-bar progress-bar-success progress-bar-striped active"></div>
                        </div>
                    </div>

                    <!-- The container for the uploaded files -->
                    <div id="<?= $this->getID() ?>_files" class="files no-print"></div>
                </div>
            </form>
            <div class="clearfix"></div>

            <?php
            if($this->_proof_file!=null)
            {
                ?>
                <form role="form" id="<?= $this->getID()?>_form_del_sacrament_proof" onsubmit="" action="<?= $this->post_URL ?>" method="post">
                    <input type="hidden" name="cid" value="<?= $this->_cid ?>">
                    <input type="hidden" name="op" value="<?= $this->ACTION_DELETE_PROOF ?>">
                </form>
                <?php
            }
        }


        // Dialog to confirm deletion of the sacrament proof document
        $this->_deleteProofDialog->setTitle("Confirmar eliminação");
        $this->_deleteProofDialog->setBodyContents("<p>Tem a certeza de que pretende eliminar o comprovativo de " . Sacraments::toExternalString($this->_sacrament) . " deste catequizando?</p>");
        $this->_deleteProofDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                                 ->addButton(new Button("Sim", ButtonType::DANGER, "document.getElementById('" . $this->getID() . "_form_del_sacrament_proof').submit()"));
        $this->_deleteProofDialog->renderHTML();
    }


    /**
     * Renders a datalist containing all the parishes registered in the database.
     * This is used in the 'parish' form field, and helps the user completing the form.
     * @return void
     * @throws \Exception
     */
    public function renderParishesList()
    {
        $db = new PdoDatabaseManager();
        ?>
        <!-- Listas de paroquias -->
        <datalist id='paroquias'>
            <option value="<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME) ?>"> <?php //Add own parish ?>
                <?php
                try
                {
                    $result = $db->getAllDistinctParishes();

                    if(isset($result))
                    {
                        foreach($result as $paroquia_existente)
                            echo("\t<option value='" . $paroquia_existente['paroquia'] . "'>\n");
                    }
                }
                catch(\Exception $e)
                {
                    //Do nothing
                }
                ?>
        </datalist>
        <?php
        $db = null;
    }


    /**
     * @inheritDoc
     */
    protected function onEdit()
    {
        ?>
        document.getElementById("<?= $this->getID() ?>_sacrament_date_input").readOnly = false;
        document.getElementById("<?= $this->getID() ?>_sacrament_parish").readOnly = false;

        $('#<?= $this->getID() ?>_sacrament_date_input').datepicker({
            format: "dd-mm-yyyy",
            defaultViewDate: { year: <?= date("Y") ?>, month: 1, day: 1 },
            startView: 2,
            language: "pt",
            autoclose: true
        });

        $('#<?= $this->getID() ?>_sacrament_date_input').datepicker().on('show', function(ev){ });
        $('#<?= $this->getID() ?>_sacrament_date_input').datepicker().on('hide', function(ev){ });
        <?php
    }


    /**
     * @inheritDoc
     */
    protected function onSubmit()
    {
        ?>
        var date = document.getElementById('<?= $this->getID() ?>_sacrament_date_input').value;

        if(!data_valida(date) && date!=="" && date!==undefined)
        {
            alert("A data de <?= Sacraments::toExternalString($this->_sacrament) ?> que introduziu é inválida. Deve ser da forma dd-mm-aaaa.");
            return false;
        }
        else
            document.getElementById("form_settings_<?= $this->getID() ?>").submit();
        <?php
    }


    /**
     * @inheritDoc
     */
    protected function onCancel()
    {
        ?>
        document.getElementById("<?= $this->getID() ?>_sacrament_date_input").readOnly = true;
        document.getElementById("<?= $this->getID() ?>_sacrament_parish").readOnly = true;
        document.getElementById("<?= $this->getID() ?>_sacrament_date_input").value = document.getElementById("<?= $this->getID() ?>_sacrament_date_backup").value;
        document.getElementById("<?= $this->getID() ?>_sacrament_parish").value = document.getElementById("<?= $this->getID() ?>_sacrament_parish_backup").value;

        $('#<?= $this->getID() ?>_sacrament_date_input').datepicker().on('show', function(ev){ $('#<?= $this->getID() ?>_sacrament_date_input').datepicker('hide');});
        $('#<?= $this->getID() ?>_sacrament_date_input').datepicker().on('hide', function(ev){ $('#<?= $this->getID() ?>_sacrament_date_input').datepicker('hide'); });
        $('#<?= $this->getID() ?>_sacrament_date_input').datepicker('hide');
        <?php
    }


    /**
     * @inheritDoc
     */
    public function renderJS()
    {
        parent::renderJS();
        $this->_deleteProofDialog->renderJS();
        ?>
        <script>
            // Configure file uploader
            $(function () {
                'use strict';
                // Change this to the location of your server-side upload handler:
                var url = 'carregarComprovativo.php';

                $('#<?= $this->getID() ?>_fileupload').fileupload({
                    url: url,
                    dataType: 'json',
                    dropZone: $('#<?= $this->getID() ?>_drop_zone'),
                    drop: function (e, data) {
                        document.getElementById("<?= $this->getID() ?>_progress_group").style = 'display: block';
                        //alert("Upload iniciado");
                    },
                    done: function (e, data) {
                        document.getElementById("<?= $this->getID() ?>_progress_group").style = 'display: none';
                        $.each(data.result.files, function (index, file) {
                            //$('<p/>').text(file.name).appendTo('#files');
                            if (file.error != undefined && file.error != "")
                                alert("O carregamento falhou. Causa:\n" + file.error);
                        });
                        window.location = "<?php echo(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) . "?cid=" . $this->_cid); ?>";
                    },
                    fail: function (e, data) {
                        alert("O carregamento falhou. Causa:\n" + data.errorThrown + data.textStatus);
                        document.getElementById("<?= $this->getID() ?>_progress_group").style = 'display: none';
                    },
                    progressall: function (e, data) {
                        var progress = parseInt(data.loaded / data.total * 100, 10);
                        $('#<?= $this->getID() ?>_progress .progress-bar').css(
                            'width',
                            progress + '%'
                        );
                    }
                }).prop('disabled', !$.support.fileInput)
                    .parent().addClass($.support.fileInput ? undefined : 'disabled');
            });
        </script>
        <?php
    }


    /**
     * @inheritDoc
     */
    public function renderCSS()
    {
        parent::renderCSS();
        $this->_deleteProofDialog->renderCSS();
    }


    /**
     * @inheritDoc
     */
    public function handlePost()
    {
        $op_eliminar_sacramento = false;
        $db = new PdoDatabaseManager();

        //Update sacrament record
        if($_REQUEST['op'] == $this->ACTION_EDIT_SACRAMENT)
        {
            $new_sacrament_date = Utils::sanitizeInput($_POST['sacrament_date']);
            $new_parish_name = Utils::sanitizeInput($_POST['sacrament_parish']);

            if($new_sacrament_date=="" && $new_parish_name=="") //Deleted sacrament
            {
                try
                {
                    if($db->deleteSacramentRecord($this->_cid, $this->_sacrament))
                    {
                        $this->updateSacramentSessionData(false, null, null);

                        // Mark the sacrament proof for removal
                        $op_eliminar_sacramento = true;

                        catechumenArchiveLog($this->_cid, "Removido registo de " . Sacraments::toExternalString($this->_sacrament) . " do catequizando com id=" . $this->_cid . ".");

                        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Registo de " . Sacraments::toExternalString($this->_sacrament) . " removido.</div>");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar registo de " . Sacraments::toExternalString($this->_sacrament) . " do catequizando.</div>");
                    }
                }
                catch (\Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                }
            }
            else if($new_sacrament_date!="" && $new_parish_name!="") //Update sacrament record
            {
                if(!DataValidationUtils::validateDate($new_sacrament_date))
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data de " . Sacraments::toExternalString($this->_sacrament) . " que introduziu é inválida. Deve ser da forma dd-mm-aaaa.</div>");
                }
                else
                {
                    //Try to insert (if it does not exist already)
                    try
                    {
                        if($db->insertSacramentRecord($this->_cid, $this->_sacrament, $new_sacrament_date, $new_parish_name))
                        {
                            $this->updateSacramentSessionData(true, $new_sacrament_date, $new_parish_name);

                            catechumenArchiveLog($this->_cid, "Registo de " . Sacraments::toExternalString($this->_sacrament) . " do catequizando com id=" . $this->_cid . ".");

                            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> " . Sacraments::toExternalString($this->_sacrament) . " registado.</div>");
                        }
                        else
                            throw new \Exception(Sacraments::toExternalString($this->_sacrament) . " ja existe");
                    }
                    catch(\Exception $e)
                    {
                        //Try to update (if it already exists)
                        try
                        {
                            if($db->updateSacramentRecord($this->_cid, $this->_sacrament, $new_sacrament_date, $new_parish_name))
                            {
                                $this->updateSacramentSessionData(true, $new_sacrament_date, $new_parish_name);

                                catechumenArchiveLog($this->_cid, "Actualizado o registo de " . Sacraments::toExternalString($this->_sacrament) . " do catequizando com id=" . $this->_cid . ".");

                                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Registo de " . Sacraments::toExternalString($this->_sacrament) . " do catequizando actualizado.</div>");
                            }
                            else
                            {
                                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar registo de " . Sacraments::toExternalString($this->_sacrament) . " do catequizando.</div>");
                            }
                        }
                        catch (\Exception $e)
                        {
                            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                            die();
                        }
                    }
                }
            }
            else
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Deve preencher os campos data e paróquia de " . Sacraments::toExternalString($this->_sacrament) . ", ou alternativamente deixá-los em branco para eliminar o registo de " . Sacraments::toExternalString($this->_sacrament) . ".</div>");
            }

        } //--$_REQUEST['op'] == $this->ACTION_EDIT_SACRAMENT



        //Delete sacrament proof document
        if($_REQUEST['op'] == $this->ACTION_DELETE_PROOF || $op_eliminar_sacramento)
        {
            $sacr_extenso = Sacraments::toExternalString($this->_sacrament);
            $sacr_type = $this->_sacrament;
            $ficheiro = UserData::getUploadDocumentsFolder() . '/' . $this->_proof_file;

            if($op_eliminar_sacramento)
            {
                if($ficheiro != null)
                {
                    unlink($ficheiro); //Delete file
                    catechumenArchiveLog($this->_cid, "Eliminado o comprovativo de " . $sacr_extenso . " do catequizando com id=" . $this->_cid . ".");

                    //Update session data
                    $this->updateSacramentProofSessionData(null);
                }
            }
            else
            {
                try
                {
                    if($db->setSacramentProofDocument($this->_cid, $sacr_type, null))
                    {
                        if($ficheiro != null)
                            unlink($ficheiro); //Delete file

                        //Update session data
                        $this->updateSacramentProofSessionData(null);

                        catechumenArchiveLog($this->_cid, "Eliminado o comprovativo de " . $sacr_extenso . " do catequizando com id=" . $this->_cid . ".");

                        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Comprovativo de " . $sacr_extenso . " do catequizando eliminado.</div>");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar o comprovativo de " . $sacr_extenso . " do catequizando. </div>");
                    }

                }
                catch(\Exception $e)
                {
                    //echo $e->getMessage();
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar o comprovativo de " . $sacr_extenso . " do catequizando.</div>");
                    //die();
                }
            }
        } //--$_REQUEST['op'] == $this->ACTION_DELETE_PROOF

        $db = null;
    }


    /**
     * Updates this object's internal state and also the _SESSION variable to reflect
     * the changes made to the sacrament record.
     * @param bool $hasSacrament
     * @param string $sacramentDate
     * @param string $parish
     * @return void
     */
    private function updateSacramentSessionData(bool $hasSacrament, string $sacramentDate = null, string $parish = null)
    {
        switch($this->_sacrament)
        {
            case Sacraments::BAPTISM:
                $_SESSION['baptizado'] = $this->_has_sacrament = $hasSacrament;
                $_SESSION['data_baptismo'] = $this->_date = $sacramentDate;
                $_SESSION['paroquia_baptismo'] = $this->_parish = $parish;
                break;

            case Sacraments::FIRST_COMMUNION:
                $_SESSION['comunhao'] = $this->_has_sacrament = $hasSacrament;
                $_SESSION['data_comunhao'] = $this->_date = $sacramentDate;
                $_SESSION['paroquia_comunhao'] = $this->_parish = $parish;
                break;

            case Sacraments::PROFESSION_OF_FAITH:
                $_SESSION['profissaoFe'] = $this->_has_sacrament = $hasSacrament;
                $_SESSION['data_profissaoFe'] = $this->_date = $sacramentDate;
                $_SESSION['paroquia_profissaoFe'] = $this->_parish = $parish;
                break;

            case Sacraments::CHRISMATION:
                $_SESSION['crismado'] = $this->_has_sacrament = $hasSacrament;
                $_SESSION['data_crisma'] = $this->_date = $sacramentDate;
                $_SESSION['paroquia_crisma'] = $this->_parish = $parish;
                break;
        }

        //Adjust the panel header color based on the value of $_has_sacrament (green/gray)
        if($this->_has_sacrament)
            $this->setPanelStyle("panel-success");
        else
            $this->setPanelStyle("panel-default");
    }


    /**
     * Updates this object's internal state and also the _SESSION variable to reflect
     * the changes made to the sacrament proof.
     * @param string|null $sacramentProof
     * @return void
     */
    private function updateSacramentProofSessionData(string $sacramentProof = null)
    {
        switch($this->_sacrament)
        {
            case Sacraments::BAPTISM:
                $_SESSION['comprovativo_baptismo'] = $this->_proof_file = $sacramentProof;
                break;

            case Sacraments::FIRST_COMMUNION:
                $_SESSION['comprovativo_comunhao'] = $this->_proof_file = $sacramentProof;
                break;

            case Sacraments::PROFESSION_OF_FAITH:
                $_SESSION['comprovativo_profissaoFe'] = $this->_proof_file = $sacramentProof;
                break;

            case Sacraments::CHRISMATION:
                $_SESSION['comprovativo_confirmacao'] = $this->_proof_file = $sacramentProof;
                break;
        }
    }
}