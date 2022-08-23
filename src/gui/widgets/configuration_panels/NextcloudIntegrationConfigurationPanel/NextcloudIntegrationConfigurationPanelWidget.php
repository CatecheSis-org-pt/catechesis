<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../../core/Configurator.php');
require_once(__DIR__ . '/../../../../core/Utils.php');

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\DataValidationUtils;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use uLogin;


/**
 * A panel providing settings to adjust the integration between CatecheSis and Nextcloud.
 */
class NextcloudIntegrationConfigurationPanelWidget extends AbstractSettingsPanelWidget
{
    public static $ACTION_PARAMETER = "edit_nextcloud_integration";

    public function __construct(string $id = null)
    {
        parent::__construct($id, true);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap-switch.css');
        $this->addJSDependency('js/bootstrap-switch.js');

        parent::setTitle("Integração com Nextcloud");
        //parent::setURL($_SERVER['PHP_SELF']);
        parent::useHeaderButtons(true);
    }


    protected function renderBody()
    {
        //Check if the online enrollment period is open
        $nextcloudBaseURL = null;
        $nextcloudVirtualCatechesisResourcesURL = null;
        try
        {
            $nextcloudBaseURL = Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_NEXTCLOUD_BASE_URL);
            $nextcloudVirtualCatechesisResourcesURL = Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL);
        }
        catch(\Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        }
        ?>

        <div class="col-md-12">
            <label for="<?=$this->getID()?>_nextcloud_base_url">URL base da Nextcloud: <span class="fas fa-question-circle" data-toggle="popover" data-placement="top" data-content='Endereço da página principal da instância Nextcloud da sua paróquia.'></span></label>
            <input type="text" class="form-control" id="<?=$this->getID()?>_nextcloud_base_url" name="nextcloud_base_url" placeholder="https://" style="cursor: auto;" value="<?= $nextcloudBaseURL ?>" readonly>
            <input type="hidden" class="form-control" id="<?=$this->getID()?>_nextcloud_base_url_backup" value="<?= $nextcloudBaseURL ?>" readonly>
        </div>
        <div class="row clearfix" style="margin-bottom: 20px"></div>
        <div class="col-md-12">
            <label for="<?=$this->getID()?>_nextcloud_virtual_catechesis_resources_url">URL da pasta de recursos para a catequese virtual: <span class="fas fa-question-circle" data-toggle="popover" data-placement="top" data-content='Endereço da pasta especial "Recursos para a catequese virtual" partilhada na instância Nextcloud da sua paróquia.'></span></label>
            <input type="text" class="form-control" id="<?=$this->getID()?>_nextcloud_virtual_catechesis_resources_url" name="nextcloud_virtual_catechesis_resources_url" placeholder="https://" style="cursor: auto;" value="<?= $nextcloudVirtualCatechesisResourcesURL ?>" readonly>
            <input type="hidden" class="form-control" id="<?=$this->getID()?>_nextcloud_virtual_catechesis_resources_url_backup" value="<?= $nextcloudVirtualCatechesisResourcesURL ?>" readonly>
        </div>

        <input type="hidden" name="action" value="<?= self::$ACTION_PARAMETER ?>">

        <?php
    }



    /**
     * @inherit
     */
    protected function onEdit()
    {?>
        document.getElementById("<?=$this->getID()?>_nextcloud_base_url").readOnly = false;
        document.getElementById("<?=$this->getID()?>_nextcloud_virtual_catechesis_resources_url").readOnly = false;
        <?php
    }


    /**
     * @inherit
     */
    protected function onCancel()
    {
        ?>
        document.getElementById("<?=$this->getID()?>_nextcloud_base_url").readOnly = true;
        document.getElementById("<?=$this->getID()?>_nextcloud_virtual_catechesis_resources_url").readOnly = true;
        document.getElementById("<?=$this->getID()?>_nextcloud_base_url").value = document.getElementById("<?=$this->getID()?>_nextcloud_base_url_backup").value;
        document.getElementById("<?=$this->getID()?>_nextcloud_virtual_catechesis_resources_url").value = document.getElementById("<?=$this->getID()?>_nextcloud_virtual_catechesis_resources_url_backup").value;
        <?php
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

        //Activar/desactivar
        if($_POST['action'] == self::$ACTION_PARAMETER && Authenticator::isAdmin())
        {

            $nextcloudBaseURL = Utils::sanitizeInput($_POST['nextcloud_base_url']);
            $nextcloudVirtualCatechesisResourcesURL = Utils::sanitizeInput($_POST['nextcloud_virtual_catechesis_resources_url']);

            if($nextcloudBaseURL!='' && !DataValidationUtils::validateURL($nextcloudBaseURL))
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A URL de base da Nextcloud que introduziu não é válida.</div>");
            }
            else if($nextcloudVirtualCatechesisResourcesURL!='' && !DataValidationUtils::validateURL($nextcloudVirtualCatechesisResourcesURL))
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A URL que introduziu para os recursos de catequese virtual não é válida.</div>");
            }
            else
            {
                try
                {
                    Configurator::setConfigurationValue(Configurator::KEY_CATECHESIS_NEXTCLOUD_BASE_URL, $nextcloudBaseURL);
                    Configurator::setConfigurationValue(Configurator::KEY_CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL, $nextcloudVirtualCatechesisResourcesURL);

                    writeLogEntry("Modificou as URLs de integração com a Nextcloud.");
                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Modificou as URLs de integração com a Nextcloud.</div>");
                }
                catch (\Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                }
            }
        }
    }
}