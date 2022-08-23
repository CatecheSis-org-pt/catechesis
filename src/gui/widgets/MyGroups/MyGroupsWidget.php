<?php


namespace catechesis\gui;

require_once(__DIR__ . '/../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../core/Configurator.php');
require_once(__DIR__ . '/../../../core/PdoDatabaseManager.php');


use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;


/**
 * A widget consisting in a panel that shows the groups where a catechist currently lectures.
 */
class MyGroupsWidget extends Widget
{
    private /*string*/ $_username;  // The username of the catechist for which this widget will display info

    public function __construct(string $id = null, string $catechistUsername = null)
    {
        parent::__construct($id);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('gui/widgets/MyGroups/MyGroupsWidget.css');

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');
        $this->addJSDependency('gui/widgets/MyGroups/MyGroupsWidget.js');

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
        $db = new PdoDatabaseManager(); // Catechesis database object

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
        ?>

        <!-- My catechesis groups widget -->
        <div id="<?=$this->getID()?>" class="my_groups_widget<?= $this->getCustomClassesString()?>" style="<?=$this->getCustomInlineStyle()?>">

            <div class="panel panel-default" id="<?=$this->getID()?>_catechist_groups_panel">
                <div class="panel-heading text-center">Os seus grupos de catequese</div>
                <div class="panel-body">

        <?php
        if($groups && count($groups) > 0)
        {
            ?>
                    <div class="row">
            <?php
            foreach($groups as $row)
            {
                ?>
                        <div class="col-sm-4">
                            <?php //Form to easily redirect the user to the group listing on clicking the card ?>
                            <form role="form" action="pesquisarAno.php" method="post" id="<?= $this->getID() ?>_form_<?= $row['ano_lectivo'] ?>_<?= $row['ano_catecismo'] ?>_<?= $row['turma'] ?>">
                                <input type="hidden" name="ano_catequetico" value="<?= $row['ano_lectivo'] ?>">
                                <input type="hidden" name="catecismo" value="<?= $row['ano_catecismo'] ?>">
                                <input type="hidden" name="turma" value="<?= $row['turma'] ?>">
                            </form>
                            <div class="panel panel-default catechesis-group-card" onclick="goto_group('<?= $this->getID() ?>_form_<?= $row['ano_lectivo'] ?>_<?= $row['ano_catecismo'] ?>_<?= $row['turma'] ?>')">
                                <div class="panel-body">
                                    <label for="ano_catequetico">Ano: </label>
                                    <span><?= Utils::formatCatecheticalYear($row['ano_lectivo']) ?></span>
                                    <div class="row clearfix"></div>
                                    <label for="catecismo">Grupo: </label>
                                    <span><?= $row['ano_catecismo'] ?>º<?= Utils::sanitizeOutput($row['turma']) ?></span>
                                    <?php
                                    // Load this group's catechumens birthdays
                                    try
                                    {
                                        $birthdays = $db->getTodaysGroupBirthdays(Utils::currentCatecheticalYear(), $row['ano_catecismo'], Utils::sanitizeOutput($row['turma']));

                                        if(count($birthdays) >= 1)
                                            echo("<div style='margin-bottom: 10px;'></div>");   // A little spacing between the gourp and birthdays section

                                        foreach($birthdays as $row2)
                                            echo("<span><i class=\"fas fa-birthday-cake\"></i> <a href=\"mostrarFicha.php?cid=" . $row2['cid'] . "\" target=\"_blank\" class='birthday_link'>" . Utils::sanitizeOutput($row2['nome']) . "</a> faz hoje " . date_diff(date_create($row2['data_nasc']), date_create('today'))->y . " anos!</span><br>");
                                    }
                                    catch(\Exception $e)
                                    {
                                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao carregar aniversários.</div>");
                                        return;
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                <?php
            }
            ?>
                    </div>
                    <div class="row clearfix" style="margin-bottom: 20px"></div>
                    <p>Ir para <a href="meusCatequizandos.php">os meus catequizandos</a>.</p>
        <?php
        }
        else
        {
            ?>
                    <div class="center-block text-center">
                        <h1><i class="fas fa-users-slash"></i></h1>
                        <p>Não tem grupos de catequese neste ano catequético.</p>
                    </div>
            <?php
        }
        ?>
                </div>
            </div>

        </div>

        <?php
        $groups = null;
        $db = null;
    }
}