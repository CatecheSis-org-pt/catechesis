<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../../core/Configurator.php');
require_once(__DIR__ . '/../../../../core/Utils.php');
require_once(__DIR__ . '/../../../../core/log_functions.php');

use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\Utils;

/**
 * Panel to enable/disable optional fields in forms and records.
 */
class OptionalFieldsConfigurationPanelWidget extends AbstractSettingsPanelWidget
{
    public static $ACTION_PARAMETER = "edit_optional_fields";

    public function __construct(string $id = null)
    {
        parent::__construct($id, true);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap-switch.css');
        $this->addJSDependency('js/bootstrap-switch.js');

        parent::setTitle("Campos opcionais das fichas");
        parent::useHeaderButtons(false); // Simple immediate-save switches
    }

    protected function renderBody()
    {
        $nifEnabled = true;

        try
        {
            $nifEnabled = Configurator::getConfigurationValueOrDefault(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED);
        }
        catch(\Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        }
        ?>
        <div class="col-md-12">
            <p>Ative ou desative os campos opcionais nas fichas dos catequizandos e dos formulários de inscrição.</p>
            <p class="help-block">Alguns campos poderão não fazer falta ou não fazer sentido na sua paróquia. Ative apenas os campos de que necessita, para cumprir com o Regulamento Geral de Proteção de Dados .</p>

            <div class="row">
                <div class="col-md-6">
                    <div>
                        <input type="checkbox" class="<?= $this->getID() ?>_checkbox-nif" id="<?= $this->getID() ?>_optional_field_nif_switch" name="optional_field_nif_switch" <?php if($nifEnabled) echo("checked"); ?>>
                        <span><b> Número de Identificação Fiscal (NIF)</b></span>
                        <span class="fas fa-question-circle" data-toggle="popover" data-placement="top" data-content='Quando ativo, o campo NIF é mostrado e obrigatório nas inscrições e na ficha do catequizando. Quando inativo, o campo é ocultado e não é obrigatório.'></span>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="action" value="<?= self::$ACTION_PARAMETER ?>">
        <?php
    }

    public function renderJS()
    {
        parent::renderJS();
        ?>
        <script>
            $(function () {
                $("[class='<?= $this->getID() ?>_checkbox-nif']").bootstrapSwitch({size: 'small', onText: 'On', offText: 'Off'});
            });

            $('input[class="<?= $this->getID() ?>_checkbox-nif"]').on('switchChange.bootstrapSwitch', function(event, state) {
                $('#form_settings_<?= $this->getID() ?>').submit();
            });

            $(function () {
                $('[data-toggle="popover"]').popover({ trigger: 'hover', html: true, delay: { 'show': 500, 'hide': 100 } });
            });
        </script>
        <?php
    }

    public function handlePost()
    {
        if($this->requires_admin_privileges && !Authenticator::isAdmin())
            return;

        $action = Utils::sanitizeInput($_POST['action']);
        if($action == self::$ACTION_PARAMETER)
        {
            $nifSwitch = Utils::sanitizeInput($_POST['optional_field_nif_switch']);
            $nifEnabled = ($nifSwitch == 'on');
            try
            {
                Configurator::setConfigurationValue(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED, $nifEnabled);
                writeLogEntry(($nifEnabled?"Ativou":"Desativou") . " o campo opcional NIF.");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> As alterações foram guardadas.</div>");
            }
            catch (\Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            }
        }
    }
}
