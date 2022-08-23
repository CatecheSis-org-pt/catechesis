<?php


namespace catechesis\gui;

require_once(__DIR__ . '/../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../core/Configurator.php');
require_once(__DIR__ . '/../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../core/decision_support.php');


use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use RELATORIO;

/**
 * A widget showing a summary of catechumens files issues, for catechumens belongig
 * to a particular catechist.
 * Just a single sentence is shown (Ok or Not Ok) and then a button to the full report.
 */
class CatechumensProblemsReportSummaryWidget extends Widget
{
    private /*string*/ $_username;  // The username of the catechist for which this widget will display info

    public function __construct(string $id = null, string $catechistUsername = null)
    {
        parent::__construct($id);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('font-awesome/fontawesome-free-5.15.1-web/css/all.min.css');

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');

        if($catechistUsername == null)
            $this->_username = Authenticator::getUsername();    // By default, show groups where the currently logged in user lectures
        else
            $this->_username = $catechistUsername;
    }



    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        $severityLevel = RELATORIO::IGNORAR;

        $db = new PdoDatabaseManager();

        //Get the group(s) where the catechist currently lectures
        $groups = null;
        try
        {
            $groups = $db->getCatechistGroups($this->_username, Utils::currentCatecheticalYear());
        }
        catch(\Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            return;
        }

        if($groups && count($groups) > 0)
        {
            foreach($groups as $row)
            {
                list($problemas, $sem_problemas, $relatorio) = runDecisionSupportAnalysis(Authenticator::getUsername(), Authenticator::isAdmin(), $row['ano_lectivo'], $row['ano_catecismo'], $row['turma']);

                if($relatorio)
                {
                    usort($problemas, "sort_catechumens_by_severity");

                    if(!empty($problemas))
                    {
                        $groupSeverityLevel = severity_level($problemas[0]);
                        if($groupSeverityLevel > $severityLevel)
                            $severityLevel = $groupSeverityLevel;
                    }
                }
                else
                {
                    // Insufficient data for inconsistent data analysis
                    // Keep the previous value of $severityLevel
                }
            }
        }

        //Set Bootstrap class to use in the widget
        $ui_class = '';
        switch($severityLevel)
        {
            case RELATORIO::FATAL:
                $ui_class = 'danger';
                break;

            case RELATORIO::AVISO:
                $ui_class = 'warning';
                break;

            case RELATORIO::INFO:
            case RELATORIO::IGNORAR:
            default:
                $ui_class = 'success';
        }

        ?>

        <!-- Catechumens issues summary widget -->
        <div id="<?=$this->getID()?>" class="my_groups_widget<?= $this->getCustomClassesString()?>" style="<?=$this->getCustomInlineStyle()?>">
            <div class="col-sm-4">
                <div class="panel panel-<?=$ui_class?>" id="painel_catequista">
                    <div class="panel-heading text-center">Dados dos seus catequizandos</div>
                    <div class="panel-body bg-<?=$ui_class?>">
                        <div class="center-block text-center text-<?=$ui_class?>">

                            <?php
                            if($severityLevel==RELATORIO::FATAL || $severityLevel==RELATORIO::AVISO)
                            {
                            ?>
                                <h1 class="text-<?=$ui_class?>"><i class="fas fa-exclamation-triangle"></i></h1>
                                <p>Alguns dos seus catequizandos têm dados inconsistentes.</p>
                            <?php
                            }
                            else
                            {
                            ?>
                                <h1 class="text-<?=$ui_class?>"><i class="fas fa-check"></i></h1>
                                <p>Não foram detetados problemas com os seus catequizandos.</p>
                            <?php
                            }
                            ?>
                            <div style="margin-bottom: 40px"></div>
                            <a class="btn btn-<?=$ui_class?>" href="dadosInconsistentes.php" role="button">Ver relatório</a>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>

        <?php
        $db = null;
    }
}