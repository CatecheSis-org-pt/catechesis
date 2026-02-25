<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../Widget.php');
require_once(__DIR__ . '/../ModalDialog/ModalDialogWidget.php');
require_once(__DIR__ . "/../../common/Animation.php");
require_once(__DIR__ . "/../../common/Button.php");
require_once(__DIR__ . "/../../../core/version_info.php");
require_once(__DIR__ . "/../../../core/Utils.php");
require_once(__DIR__ . "/../../../authentication/Authenticator.php");

use catechesis\Authenticator;
use Dompdf\Exception;
use catechesis\Utils;

class NewFeaturesDialogWidget extends ModalDialogWidget
{
    public function __construct(string $id = null)
    {
        parent::__construct($id);

        $this->setSize(ModalDialogWidget::SIZE_LARGE);
        $this->setTitle("Novidades nesta versão");
        $this->addButton(new Button("Fechar", ButtonType::PRIMARY));
        $this->setEntryAnimation(Animation::TADA);
        $this->addCSSDependency("gui/widgets/NewFeaturesDialog/NewFeatureDialogWidget.css");
    }


    /**
     * Setting the body of this modal dialog is unsupported.
     * @param string $contents
     * @return $this
     */
    public function setBodyContents(string $contents)
    {
        throw new Exception("NewFeaturesDialogWidget: The body of a NewFeaturesDialogWidget cannot be set.");
    }

    /**
     * Renders the body of the about dialog.
     * @return void
     */
    protected function renderBodyContents()
    {
        ?>
        <div style="overflow: hidden;">
            <div class="container col-xs-12 news-widget-container">

                <h1> <?= Utils::firstName(Authenticator::getUserFullName()) ?>,<br>temos <span class="animated animate__animated animate__tada">novidades</span>! 🎉</h1>

                <div style="margin-bottom: 60px;"></div>

                <div class="news-content">

                    <p style="text-align: center"><b>O CatecheSis foi atualizado!<br>Faça scroll para ver as principais novidades!</b></p>
                    <div class="center-container">
                        <img class="center-image" src="img/double-scroll-down.gif" style="scale: 50%"/>
                    </div>

                    <?php
                    include( CATECHESIS_ROOT_DIRECTORY . "help/onboarding/v2.4.0.php");
                    ?>
                </div>


                <div style="margin-bottom: 40px;"></div>
                <p><b>Veja mais alterações no <a href="changelog.html" target="_blank"><span class="fas fa-external-link-alt"></span> changelog</a>.</b></p>

            </div>
            <div class="clearfix"></div>
        </div>
        <?php
    }


    /**
     * @inheritDoc
     */
    public function renderJS()
    {
        parent::renderJS();
        ?>
        <script type="text/javascript">
            $(function() {
                $('#<?= $this->getID() ?>').modal('show');
            });
        </script>
        <?php
    }
}