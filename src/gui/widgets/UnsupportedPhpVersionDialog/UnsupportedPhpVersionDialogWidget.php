<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../Widget.php');
require_once(__DIR__ . '/../ModalDialog/ModalDialogWidget.php');
require_once(__DIR__ . "/../../common/Animation.php");
require_once(__DIR__ . "/../../common/Button.php");
require_once(__DIR__ . "/../../../authentication/Authenticator.php");
require_once(__DIR__ . "/../configuration_panels/UnsupportedPhpVersionPanel/UnsupportedPhpVersionPanelWidget.php");

use catechesis\Authenticator;
use Dompdf\Exception;

class UnsupportedPhpVersionDialogWidget extends ModalDialogWidget
{
    private /*bool*/ $shouldShow = false;
    private /*string|null*/ $testedVersion = null;

    public function __construct(?string $id = null, ?string $testedVersion = null, bool $forceShow = false)
    {
        parent::__construct($id);

        $this->testedVersion = $testedVersion;

        $this->setSize(ModalDialogWidget::SIZE_MEDIUM);
        $this->setTitle("<b><i class=\"fas fa-exclamation-triangle text-danger\"></i> ALERTA!</b> Versão do PHP não suportada");
        $this->addButton(new Button("Fechar", ButtonType::DANGER));
        $this->setEntryAnimation(Animation::TADA);
        $this->addCustomClass("unsupported-php-version-dialog");
        $this->addCSSDependency('gui/widgets/UnsupportedPhpVersionDialog/UnsupportedPhpVersionDialogWidget.css');

        $panel = new UnsupportedPhpVersionPanelWidget(null, false, $testedVersion);
        $isUnsupported = $panel->isUnsupported();

        if ($forceShow || (!empty($_SESSION['SHOW_UNSUPPORTED_PHP_WARNING']) && Authenticator::isAdmin() && $isUnsupported))
        {
            $this->shouldShow = true;
            $_SESSION['SHOW_UNSUPPORTED_PHP_WARNING'] = false;
        }
    }

    /**
     * Setting the body of this modal dialog is unsupported.
     * @param string $contents
     * @return $this
     */
    public function setBodyContents(string $contents)
    {
        throw new Exception("UnsupportedPhpVersionDialogWidget: The body of a UnsupportedPhpVersionDialogWidget cannot be set.");
    }

    /**
     * Returns whether this dialog is configured to be shown.
     * @return bool
     */
    public function shouldShow(): bool
    {
        return $this->shouldShow;
    }

    /**
     * Returns the PHP version being evaluated.
     * @return string
     */
    public function getPhpVersion(): string
    {
        return $this->testedVersion !== null ? $this->testedVersion : PHP_VERSION;
    }

    /**
     * Checks if the evaluated PHP version is unsupported.
     * @param string|null $version
     * @param string|null $currentDate
     * @return bool
     */
    public function isUnsupported(?string $version = null, ?string $currentDate = null): bool
    {
        $panel = new UnsupportedPhpVersionPanelWidget(null, false, $version ?? $this->getPhpVersion());
        return $panel->isUnsupported(null, $currentDate);
    }

    /**
     * @inheritDoc
     */
    public function renderCSS()
    {
        if (!$this->isUnsupported())
        {
            return;
        }

        parent::renderCSS();
        ?>
        <style type="text/css">
            #<?= $this->getID() ?> .modal-content,
            #<?= $this->getID() ?>_header,
            #<?= $this->getID() ?>_body,
            #<?= $this->getID() ?>_footer {
                background-color: #f2dede;
            }
        </style>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        if (!$this->isUnsupported())
        {
            return;
        }

        parent::renderHTML();
    }

    /**
     * @inheritDoc
     */
    public function renderJS()
    {
        parent::renderJS();

        if ($this->shouldShow)
        {
            ?>
            <script type="text/javascript">
                $(function() {
                    var modal = $('#<?= $this->getID() ?>');
                    modal.modal('show');
                });
            </script>
            <?php
        }
    }

    /**
     * Renders the body of the unsupported PHP version dialog.
     * @return void
     */
    protected function renderBodyContents()
    {
        $currentVersion = $this->getPhpVersion();
        ?>
        <div style="overflow: hidden;">
            <div class="container col-xs-12">
                <p>O servidor está a executar o <strong>PHP <?= htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8') ?></strong>, uma versão que já atingiu o fim de vida (<em>End of Life</em>) e não recebe atualizações de segurança.</p>
                <p>A utilização de uma versão descontinuada do PHP pode comprometer a segurança, estabilidade e desempenho da aplicação. Recomenda-se a atualização imediata do PHP no servidor para uma versão suportada.</p>
                <p><i class="fas fa-exclamation-triangle text-danger"></i> <strong>Futuras versões do CatecheSis deixarão de funcionar neste servidor.</strong> <i class="fas fa-exclamation-triangle text-danger"></i></p>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php
    }
}
