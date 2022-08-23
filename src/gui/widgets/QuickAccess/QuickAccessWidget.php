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
class QuickAccessWidget extends Widget
{
    public function __construct(string $id = null)
    {
        parent::__construct($id);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('font-awesome/fontawesome-free-5.15.1-web/css/all.min.css');

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');
    }



    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        ?>

        <!-- Catechumens issues summary widget -->
        <div id="<?=$this->getID()?>" class="my_groups_widget<?= $this->getCustomClassesString()?>" style="<?=$this->getCustomInlineStyle()?>">
            <div class="col-sm-2"></div>
            <div class="col-sm-8">
                <div class="panel panel-default" id="<?=$this->getID()?>_quick_access_panel">
                    <div class="panel-heading text-center">Acessos rápidos</div>
                    <div class="panel-body">
                        <div class="col-sm-12" style="display: flex; justify-content: center;">
                            <div class="center-block">
                                <a class="btn btn-default" href="meusCatequizandos.php" role="button">
                                    <div class="text-center" style="font-size: xx-large; height: 95px;">
                                        <div style="margin-bottom: 55px"></div>
                                        <i class="fas fa-users"></i>
                                    </div>
                                    Os meus catequizandos
                                </a>
                                <a class="btn btn-default" href="criarCatequeseVirtual.php" role="button">
                                    <img src="img/Catequese_Virtual.svg" class="img-responsive" style="height: 150px">
                                    Catequese Virtual
                                </a>
                                <?php
                                if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_NEXTCLOUD_BASE_URL))
                                {
                                ?>
                                <a class="btn btn-default" href="<?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_NEXTCLOUD_BASE_URL); ?>" target="_blank" role="button">
                                    <img src="img/Logo_Cloud.svg" class="img-responsive" style="height: 150px; width: 150px;">
                                    Catequese Cloud
                                </a>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <div class="col-sm-2"></div>
        </div>

        <?php
    }
}