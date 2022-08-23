<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../../../core/Configurator.php');
use catechesis\Configurator;

/**
 * Renders a minimal navbar, with just the CatecheSis logo and no menu options.
 * Suitable to be used on public pages.
 */
class MinimalNavbar extends Widget
{
    public function __construct(string $id = null)
    {
        parent::__construct($id);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('css/custom-navbar-colors.css'); //FIXME Remove this when migrating to Bootstrap 5

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');
    }

    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        ?>
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <div class="navbar-header">
                    <a class="navbar-brand" href="<?= constant('CATECHESIS_BASE_URL'); ?>/index.php"><img src="<?= $this->getPathPrefix() ?>img/CatecheSis_Logo_Navbar.svg" class="img-responsive" style="height:200%; margin-top: -7%;"></a>
                </div>
            </div>
        </nav>
        <?php
    }
}