<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../../core/Configurator.php');
require_once(__DIR__ . '/../../../../core/Utils.php');
require_once(__DIR__ . '/../../../../core/domain/WeekDay.php');


use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\DataValidationUtils;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use core\domain\WeekDay;
use uLogin;


/**
 * A panel providing settings to adjust operational details of the catechesis in this parish.
 */
class CatechesisSettingsPanelWidget extends AbstractSettingsPanelWidget
{
    public static $ACTION_PARAMETER = "edit_catechesis_operational_settings";

    public function __construct(string $id = null)
    {
        parent::__construct($id, true);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap-switch.css');
        $this->addJSDependency('js/bootstrap-switch.js');

        parent::setTitle("Catequese (operacionalização)");
        //parent::setURL($_SERVER['PHP_SELF']);
        parent::useHeaderButtons(true);
    }


    protected function renderBody()
    {
        $numCatechisms = 10;
        $weekDay = null;
        try
        {
            $numCatechisms = Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS);
            $weekDay = Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_WEEK_DAY);
        }
        catch(\Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        }
        ?>

        <div class="col-md-4">
            <label for="<?=$this->getID()?>_week_day">Dia em que a catequese é ministrada:</label>
            <select id="<?=$this->getID()?>_week_day" name="week_day" class="form-control" disabled>
                <option value="<?= WeekDay::SUNDAY ?>" <?php if($weekDay == WeekDay::SUNDAY) echo("selected"); ?>>Domingo</option>
                <option value="<?= WeekDay::MONDAY ?>" <?php if($weekDay == WeekDay::MONDAY) echo("selected"); ?>>Segunda-feira</option>
                <option value="<?= WeekDay::TUESDAY ?>" <?php if($weekDay == WeekDay::TUESDAY) echo("selected"); ?>>Terça-feira</option>
                <option value="<?= WeekDay::WEDNESDAY ?>" <?php if($weekDay == WeekDay::WEDNESDAY) echo("selected"); ?>>Quarta-feira</option>
                <option value="<?= WeekDay::THURSDAY ?>" <?php if($weekDay == WeekDay::THURSDAY) echo("selected"); ?>>Quinta-feira</option>
                <option value="<?= WeekDay::FRIDAY ?>" <?php if($weekDay == WeekDay::FRIDAY) echo("selected"); ?>>Sexta-feira</option>
                <option value="<?= WeekDay::SATURDAY ?>" <?php if($weekDay == WeekDay::SATURDAY) echo("selected"); ?>>Sábado</option>
            </select>
            <input type="hidden" class="form-control" id="<?=$this->getID()?>_week_day_backup" value="<?= $weekDay ?>" readonly>
        </div>

        <div class="row clearfix" style="margin-bottom: 20px"></div>

        <div class="col-md-4">
            <label for="<?=$this->getID()?>_num_catechisms">Número de anos do percurso catequético (catecismos):</label>
            <input type="number" min="1" max="20" class="form-control" id="<?=$this->getID()?>_num_catechisms" name="num_catechisms" value="<?= $numCatechisms ?>" readonly>
            <input type="hidden" class="form-control" id="<?=$this->getID()?>_num_catechisms_backup" value="<?= $numCatechisms ?>" readonly>
        </div>

        <input type="hidden" name="action" value="<?= self::$ACTION_PARAMETER ?>">

        <?php
    }



    /**
     * @inherit
     */
    protected function onEdit()
    {?>
        document.getElementById("<?=$this->getID()?>_week_day").disabled = false;
        document.getElementById("<?=$this->getID()?>_num_catechisms").readOnly = false;

        <?php
    }


    /**
     * @inherit
     */
    protected function onCancel()
    {
        ?>
        document.getElementById("<?=$this->getID()?>_week_day").disabled = true;
        document.getElementById("<?=$this->getID()?>_num_catechisms").readOnly = true;
        document.getElementById("<?=$this->getID()?>_week_day").value = document.getElementById("<?=$this->getID()?>_week_day_backup").value;
        document.getElementById("<?=$this->getID()?>_num_catechisms").value = document.getElementById("<?=$this->getID()?>_num_catechisms_backup").value;
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

        if($_POST['action'] == self::$ACTION_PARAMETER && Authenticator::isAdmin())
        {
            $numCatechisms = intval($_POST['num_catechisms']);
            $weekDay = intval($_POST['week_day']);

            if($numCatechisms < 0)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O número de catecismos que introduziu não é válido. </div>");
            }
            else if($weekDay < 0 || $weekDay > 6)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O dia de semana que introduziu não é válido. </div>");
            }
            else
            {
                try
                {
                    Configurator::setConfigurationValue(Configurator::KEY_NUM_CATECHISMS, $numCatechisms);
                    Configurator::setConfigurationValue(Configurator::KEY_CATECHESIS_WEEK_DAY, $weekDay);

                    writeLogEntry("Modificou o número de anos do percurso catequético (catecismos) e/ou o dia da semana em que é ministrada a catequese.");
                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Modificou o número de anos do percurso catequético (catecismos) e/ou o dia da semana em que é ministrada a catequese.</div>");
                }
                catch (\Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                }
            }
        }
    }
}
