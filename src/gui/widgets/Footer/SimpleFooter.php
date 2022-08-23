<?php

namespace catechesis\gui;
require_once(__DIR__ . '/../../../core/Configurator.php');

use catechesis\Configurator;


/**
 * Renders a simple footer with the parish name.
 * Suitable to be used on public pages.
 */
class SimpleFooter extends Widget
{
    private /*bool*/ $_with_background = false;     // Defined whether the footer should have a blue background color (true) or be transparent (false)

    public function __construct(string $id = null, bool $withBackground=false)
    {
        parent::__construct($id);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('css/custom-navbar-colors.css'); //FIXME Remove this when migrating to Bootstrap 5
        $this->addCSSDependency('fonts/Nexa.css');

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');

        $this->_with_background = $withBackground;
    }

    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        ?>
        <div id="<?= $this->getID()?>_simple_footer" class="simple_footer">
            <p><a href="<?= constant('CATECHESIS_BASE_URL'); ?>/index.php"><strong>CatecheSis</strong></a> - <?= Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME); ?></p>
        </div>
        <?php
    }

    public function renderCSS()
    {
        ?>
        <style>
            .simple_footer
            {
                font-family: Nexa, sans-serif;
                font-size: medium;
                position:fixed;
                width: 100%;
                bottom : 0px;
                height : auto;
                margin-top : 40px;
                padding-top: 10px;
                vertical-align: middle;
                color: white;
                text-align: center;
                <?php
                if($this->_with_background)
                {
                ?>
                background-color: #008fcf;
                <?php
                }
                ?>
            }

            .simple_footer a
            {
                color: white;
            }
        </style>
        <?php
    }
}