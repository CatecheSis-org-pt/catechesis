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
    private /*bool*/ $hasNewsToShow = false;

    private $versionsWithNews = array();

    public function __construct(string $id = null)
    {
        parent::__construct($id);

        $this->setSize(ModalDialogWidget::SIZE_LARGE);
        $this->setTitle("Novidades nesta versão");
        $this->addButton(new Button("Fechar", ButtonType::PRIMARY));
        $this->setEntryAnimation(Animation::TADA);
        $this->addCSSDependency("gui/widgets/NewFeaturesDialog/NewFeatureDialogWidget.css");

        $lastSeenVersion = Authenticator::getLastSeenVersion();

        if($lastSeenVersion != constant('VERSION_STRING'))
        {
            // Dynamically build the version history from the files in help/onboarding/
            $versionHistory = $this->discoverVersionHistory();

            // Check which versions are new to the user and have news files
            foreach ($versionHistory as $version) {
                // Only consider versions newer than the last seen version
                // If lastSeenVersion is null or empty, it means the user never saw any version (first login)
                if (empty($lastSeenVersion) || version_compare($version, $lastSeenVersion, '>')) {
                    $this->versionsWithNews[] = $version;
                } else {
                    // Since versionHistory is in inverse chronological order,
                    // once we hit a version that is NOT newer than lastSeenVersion,
                    // we can stop (assuming versions are correctly ordered)
                    break;
                }
            }
        }

        $this->hasNewsToShow = !empty($this->versionsWithNews);

        if ($this->hasNewsToShow)
        {
            Authenticator::updateLastSeenVersion();
        }
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
        // Only render the widget if there are actually news to show
        if($this->hasNewsToShow)
        {
            $latestVersion = $this->versionsWithNews[0];
            $otherVersions = array_slice($this->versionsWithNews, 1);
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
                    include( CATECHESIS_ROOT_DIRECTORY . "help/onboarding/v{$latestVersion}.php");
                    ?>

                    <?php if (!empty($otherVersions)): ?>
                        <div style="margin-bottom: 60px;"></div>

                        <h2 style="text-align: center">Mas espere, há mais!</h2>
                        <p style="text-align: center">Parece que já não acedia ao CatecheSis há algum tempo...<br>Aqui estão mais algumas novidades de atualizações anteriores que poderão ter-lhe escapado:</p>

                        <div style="margin-bottom: 60px;"></div>

                        <?php
                        foreach ($otherVersions as $version) {
                            include( CATECHESIS_ROOT_DIRECTORY . "help/onboarding/v{$version}.php");
                        }
                        ?>
                    <?php endif; ?>
                </div>


                <div style="margin-bottom: 40px;"></div>
                <p><b>Veja mais alterações no <a href="changelog.html" target="_blank"><span class="fas fa-external-link-alt"></span> changelog</a>.</b></p>

            </div>
            <div class="clearfix"></div>
        </div>
        <?php
        }
    }


    /**
     * @inheritDoc
     */
    public function renderJS()
    {
        parent::renderJS();

        // Only render the widget if there are actually news to show
        if($this->hasNewsToShow)
        {
        ?>
        <script type="text/javascript">
            $(function() {
                var modal = $('#<?= $this->getID() ?>');
                modal.modal('show');

                // Animation on scroll logic
                var container = modal.find('.news-widget-container');
                var observerOptions = {
                    root: container[0],
                    threshold: 0.1
                };

                var observer = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var element = $(entry.target);
                            var animation = element.data('animation');
                            if (animation) {
                                element.addClass('animate__animated animate__faster ' + animation);
                            }
                            element.css('opacity', '1');
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);

                container.find('.news-block-left, .news-block-right').each(function() {
                    observer.observe(this);
                });
            });
        </script>
        <?php
        }
    }


    /**
     * Discovers all versions that have an onboarding news file,
     * in inverse chronological order.
     * @return array
     */
    private function discoverVersionHistory()
    {
        $versions = array();
        $directory = CATECHESIS_ROOT_DIRECTORY . "help/onboarding/";

        if (is_dir($directory)) {
            $files = glob($directory . "v*.php");
            foreach ($files as $file) {
                $filename = basename($file);
                // Extract version from v<version>.php
                if (preg_match('/^v(.+)\.php$/', $filename, $matches)) {
                    $versions[] = $matches[1];
                }
            }
        }

        // Sort versions in descending order
        usort($versions, 'version_compare');
        return array_reverse($versions);
    }


    /**
     * Checks whether a file with news (onboarding information) exists for the given version (e.g. v2.4.0)
     * @param string $version
     * @return bool
     */
    private function existsNewsForVersion(string $version)
    {
        return file_exists(CATECHESIS_ROOT_DIRECTORY . "help/onboarding/v{$version}.php");
    }
}