<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../../core/Configurator.php');
require_once(__DIR__ . '/../../../../core/Utils.php');

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use uLogin;


/**
 * A panel providing a switch that allows to open or close the catechumens evalutation period
 * for catechists.
 */
class CatechumensEvaluationActivationPanelWidget extends AbstractSettingsPanelWidget
{
    public static $ACTION_PARAMETER = "edit_evaluation_period_status";

    public function __construct(string $id = null)
    {
        parent::__construct($id, true);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap-switch.css');
        $this->addJSDependency('js/bootstrap-switch.js');

        parent::setTitle("Aproveitamento dos catequizandos");
        //parent::setURL($_SERVER['PHP_SELF']);
        parent::useHeaderButtons(false);
    }


    protected function renderBody()
    {
        //Check if the evaluation period is open
        $periodo_activo = false;
        try
        {
            $periodo_activo = Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHUMENS_EVALUATION);
        }
        catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        }
        ?>
        <div class="col-md-12">
            <p>Utilize o interruptor abaixo para activar/desactivar a possibilidade de alterar o aproveitamento dos catequizandos, para todos os catequistas.</p>
            <div class="row">
                <div class="col-md-6">
                    <div>
                        <input type="checkbox" class="<?= $this->getID() ?>_checkbox-admin" id="<?= $this->getID() ?>_evaluation_period_switch" name="evaluation_period_switch" <?php if($periodo_activo) echo("checked"); ?>>
                        <span><b> Período de avaliação <?php if($periodo_activo) echo("aberto"); else echo("fechado"); ?></b></span>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="action" value="<?= self::$ACTION_PARAMETER ?>">

        <?php
    }




    /**
     * @inherit
     */
    public function renderJS()
    {
        parent::renderJS();
        ?>
        <script>
            $(function () {
                $("[class='<?= $this->getID() ?>_checkbox-admin']").bootstrapSwitch({size: 'small',
                    onText: 'On',
                    offText: 'Off'
                });
            });

            $('input[class="<?= $this->getID() ?>_checkbox-admin"]').on('switchChange.bootstrapSwitch', function(event, state) {

                $('#form_settings_<?= $this->getID() ?>').submit();
            });
        </script>
        <?php
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
            $setting = Utils::sanitizeInput($_POST['evaluation_period_switch']);

            if($setting=="on")
            {
                try
                {
                    Configurator::setConfigurationValue(Configurator::KEY_CATECHUMENS_EVALUATION, true);
                    writeLogEntry("Abriu o período de avaliação do aproveitamento dos catequizandos.");
                    $periodo_activo = true;
                }
                catch (Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                }
            }
            else
            {
                try
                {
                    Configurator::setConfigurationValue(Configurator::KEY_CATECHUMENS_EVALUATION, false);
                    writeLogEntry("Fechou o período de avaliação do aproveitamento dos catequizandos.");
                    $periodo_activo = false;
                }
                catch (Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                }
            }
        }
    }
}