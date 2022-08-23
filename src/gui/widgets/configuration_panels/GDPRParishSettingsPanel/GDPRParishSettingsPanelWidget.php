<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../../core/Configurator.php');
require_once(__DIR__ . '/../../../../core/Utils.php');
require_once(__DIR__ . '/../../../../core/UserData.php');
require_once(__DIR__ . '/../../../../core/log_functions.php');

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Configurator;
use catechesis\UserData;
use catechesis\Utils;
use uLogin;


/**
 * Panel containing the parish settings related to the General Data Protection Regulation.
 * Allows to change the responsible for data processing and such.
 */
class GDPRParishSettingsPanelWidget extends AbstractSettingsPanelWidget
{
    public static $ACTION_PARAMETER = "edit_parish_gdpr_details";

    public function __construct(string $id = null)
    {
        parent::__construct($id, true);

        $this->addCSSDependency("font-awesome/fontawesome-free-5.15.1-web/css/all.min.css");

        parent::setTitle("Proteção de dados (RGPD)");
        //parent::setURL($_SERVER['PHP_SELF']);
        parent::useHeaderButtons();
    }


    protected function renderBody()
    {
        $dataProcessingName = null;
        $dataProcessingAddress = null;
        $dataProcessingEmail = null;
        $dpoName = null;
        $dpoAddress = null;
        $dpoEmail = null;

        //Get parish GDPR data
        try
        {
            $dataProcessingName = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_RESPONSIBLE_NAME);
            $dataProcessingAddress = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_RESPONSBILE_ADDRESS);
            $dataProcessingEmail = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_RESPONSIBLE_EMAIL);
            $dpoName = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_DPO_NAME);
            $dpoAddress = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_DPO_ADDRESS);
            $dpoEmail = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_DPO_EMAIL);
        }
        catch(\Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        }

        ?>

        <div class="form-group">
            <div class="col-md-6">
                <span>O órgão <b>responsável pelo tratamento dos dados</b> é </span>
                <input type="text" class="form-control" id="<?=$this->getID()?>_data_responsible_name" name="data_responsible_name" placeholder="a Catequese paroquial de ..." style="cursor: auto; display: inline;" value="<?= $dataProcessingName ?>" required readonly>
                <input type="hidden" id="<?=$this->getID()?>_data_responsible_name_backup" value="<?= $dataProcessingName ?>" required readonly>
            </div>
            <div class="row clearfix"></div>
            <div class="col-md-8">
                <span>com domicílio em</span>
                <input type="text" class="form-control" id="<?=$this->getID()?>_data_responsible_address" name="data_responsible_address" placeholder="morada" style="cursor: auto; display: inline;" value="<?= $dataProcessingAddress ?>" required readonly>
                <input type="hidden" id="<?=$this->getID()?>_data_responsible_address_backup" value="<?= $dataProcessingAddress ?>" required readonly>
            </div>
            <div class="col-md-4">
                <span>com o e-mail</span>
                <input type="email" class="form-control" id="<?=$this->getID()?>_data_responsible_email" name="data_responsible_email" placeholder="e-mail" style="cursor: auto; display: inline;" value="<?= $dataProcessingEmail ?>" required readonly>
                <input type="hidden" id="<?=$this->getID()?>_data_responsible_email_backup" value="<?= $dataProcessingEmail ?>" required readonly>
            </div>
        </div>

        <div class="clearfix" style="margin-bottom: 40px"></div>

        <div class="form-group">
            <div class="col-md-6">
                <span>O <b>Encarregado de Proteção de Dados (DPO)</b> é </span>
                <input type="text" class="form-control" id="<?=$this->getID()?>_dpo_name" name="dpo_name" placeholder="o pároco da Paróquia de ..." style="cursor: auto; display: inline;" value="<?= $dpoName ?>" required readonly>
                <input type="hidden" id="<?=$this->getID()?>_dpo_name_backup" value="<?= $dpoName ?>" required readonly>
            </div>
            <div class="row clearfix"></div>
            <div class="col-md-8">
                <span>com domicílio profissional em</span>
                <input type="text" class="form-control" id="<?=$this->getID()?>_dpo_address" name="dpo_address" placeholder="morada" style="cursor: auto; display: inline;" value="<?= $dpoAddress ?>" required readonly>
                <input type="hidden" id="<?=$this->getID()?>_dpo_address_backup" value="<?= $dpoAddress ?>" required readonly>
            </div>
            <div class="col-md-4">
                <span>com o e-mail</span>
                <input type="email" class="form-control" id="<?=$this->getID()?>_dpo_email" name="dpo_email" placeholder="e-mail" style="cursor: auto; display: inline;" value="<?= $dpoEmail ?>" required readonly>
                <input type="hidden" id="<?=$this->getID()?>_dpo_email_backup" value="<?= $dpoEmail ?>" required readonly>
            </div>
        </div>

        <div class="row clearfix" style="margin-bottom:40px; "></div>
        <div class="col-md-12">
            <p id="<?=$this->getID()?>_info_text_help_block" class="help-block">Estes dados são utilizados para gerar automaticamente a declaração de consentimento de tratamento de dados pessoais, apresentada aos encarregados de educação que efetuarem uma inscrição/renovação de matrícula online.</p>
            <button class="btn btn-default" type="button" onclick="window.open('<?= $this->getPathPrefix() ?>publico/descarregarDeclaracaoRGPD.php', '_blank');"><i class="fas fa-file-pdf">&nbsp;</i> Pré-visualizar documento gerado</button>
        </div>
        <div class="row clearfix" style="margin-bottom:20px; "></div>

        <input type="hidden" name="action" value="<?= self::$ACTION_PARAMETER ?>">
    <?php
    }


    /**
     * @inherit
     */
    protected function onEdit()
    {?>
        document.getElementById("<?=$this->getID()?>_data_responsible_name").readOnly = false;
        document.getElementById("<?=$this->getID()?>_data_responsible_address").readOnly = false;
        document.getElementById("<?=$this->getID()?>_data_responsible_email").readOnly = false;
        document.getElementById("<?=$this->getID()?>_dpo_name").readOnly = false;
        document.getElementById("<?=$this->getID()?>_dpo_address").readOnly = false;
        document.getElementById("<?=$this->getID()?>_dpo_email").readOnly = false;
       <?php
    }


    /**
     * @inherit
     */
    protected function onCancel()
    {
        ?>
        document.getElementById("<?=$this->getID()?>_data_responsible_name").readOnly = true;
        document.getElementById("<?=$this->getID()?>_data_responsible_address").readOnly = true;
        document.getElementById("<?=$this->getID()?>_data_responsible_email").readOnly = true;
        document.getElementById("<?=$this->getID()?>_dpo_name").readOnly = true;
        document.getElementById("<?=$this->getID()?>_dpo_address").readOnly = true;
        document.getElementById("<?=$this->getID()?>_dpo_email").readOnly = true;

        document.getElementById("<?=$this->getID()?>_data_responsible_name").value = document.getElementById("<?=$this->getID()?>_data_responsible_name_backup").value;
        document.getElementById("<?=$this->getID()?>_data_responsible_address").value = document.getElementById("<?=$this->getID()?>_data_responsible_address_backup").value;
        document.getElementById("<?=$this->getID()?>_data_responsible_email").value = document.getElementById("<?=$this->getID()?>_data_responsible_email_backup").value;
        document.getElementById("<?=$this->getID()?>_dpo_name").value = document.getElementById("<?=$this->getID()?>_dpo_name_backup").value;
        document.getElementById("<?=$this->getID()?>_dpo_address").value = document.getElementById("<?=$this->getID()?>_dpo_address_backup").value;
        document.getElementById("<?=$this->getID()?>_dpo_email").value = document.getElementById("<?=$this->getID()?>_dpo_email_backup").value;
        <?php
    }


    /**
     * @inherit
     */
    protected function onSubmit()
    {
    }


    /**
     * @inherit
     */
    public function renderJS()
    {
        parent::renderJS();
    }

    /**
     * @inheritDoc
     */
    public function handlePost()
    {
        if($this->requires_admin_privileges && !Authenticator::isAdmin())
            return; //Do not render this widget if the user is not admin and it requires admin priviledges

        $action = Utils::sanitizeInput($_POST['action']);

        //Edit parish data
        if($action == self::$ACTION_PARAMETER)
        {
            $editDataProcessingName = Utils::sanitizeInput($_POST['data_responsible_name']);
            $editDataProcessingAddress = Utils::sanitizeInput($_POST['data_responsible_address']);
            $editDataProcessingEmail = Utils::sanitizeInput($_POST['data_responsible_email']);
            $editDpoName = Utils::sanitizeInput($_POST['dpo_name']);
            $editDpoAddress = Utils::sanitizeInput($_POST['dpo_address']);
            $editDpoEmail = Utils::sanitizeInput($_POST['dpo_email']);

            try
            {
                Configurator::setConfigurationValue(Configurator::KEY_GDPR_RESPONSIBLE_NAME, $editDataProcessingName);
                Configurator::setConfigurationValue(Configurator::KEY_GDPR_RESPONSBILE_ADDRESS, $editDataProcessingAddress);
                Configurator::setConfigurationValue(Configurator::KEY_GDPR_RESPONSIBLE_EMAIL, $editDataProcessingEmail);
                Configurator::setConfigurationValue(Configurator::KEY_GDPR_DPO_NAME, $editDpoName);
                Configurator::setConfigurationValue(Configurator::KEY_GDPR_DPO_ADDRESS, $editDpoAddress);
                Configurator::setConfigurationValue(Configurator::KEY_GDPR_DPO_EMAIL, $editDpoEmail);

                writeLogEntry("Modificou os detalhes referentes aos responsáveis pelo tratamento de dados (RGPD) da paróquia.");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Os detalhes referentes aos responsáveis pelo tratamento de dados (RGPD) da paróquia foram actualizados.</div>");
            }
            catch(\Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                //die();
            }
        }
    }
}