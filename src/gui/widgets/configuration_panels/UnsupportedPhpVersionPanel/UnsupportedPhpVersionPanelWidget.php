<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');

use catechesis\Authenticator;

/**
 * A configuration panel that displays a warning if the server is running an unsupported PHP version.
 */
class UnsupportedPhpVersionPanelWidget extends AbstractSettingsPanelWidget
{
    private static $EOL_DATES = [
        '5.6' => '2018-12-31',
        '7.0' => '2019-01-10',
        '7.1' => '2019-12-01',
        '7.2' => '2020-11-30',
        '7.3' => '2021-12-06',
        '7.4' => '2022-11-28',
        '8.0' => '2023-11-26',
        '8.1' => '2025-12-31',
        '8.2' => '2026-12-31',
        '8.3' => '2027-12-31',
        '8.4' => '2028-12-31',
        '8.5' => '2029-12-31',
    ];

    private /*string*/ $testedVersion;

    public function __construct(?string $id = null, bool $requires_admin_privileges = true, ?string $testedVersion = null)
    {
        parent::__construct($id, $requires_admin_privileges);

        $this->testedVersion = $testedVersion;

        $this->addCSSDependency('font-awesome/fontawesome-free-5.15.1-web/css/all.min.css');

        parent::setTitle("<b><i class=\"fas fa-exclamation-triangle\"></i> ALERTA!</b> Versão do PHP não suportada");
        parent::setPanelStyle("panel-danger");
        parent::useHeaderButtons(false);
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
     * Checks if the given PHP version (or current server version) is unsupported / reached End of Life (EOL).
     * @param string|null $version
     * @param string|null $currentDate (format Y-m-d)
     * @return bool
     */
    public function isUnsupported(?string $version = null, ?string $currentDate = null): bool
    {
        $version = $version ?? $this->getPhpVersion();
        $currentDate = $currentDate ?? date('Y-m-d');

        if (preg_match('/^(\d+)\.(\d+)/', $version, $matches)) {
            $major = (int)$matches[1];
            $minor = (int)$matches[2];
            $majorMinor = $major . '.' . $minor;

            // Anything below PHP 5.6 is unsupported
            if ($major < 5 || ($major == 5 && $minor < 6)) {
                return true;
            }

            if (isset(self::$EOL_DATES[$majorMinor])) {
                return $currentDate > self::$EOL_DATES[$majorMinor];
            }

            // For newer unlisted versions, assume they are supported
            return false;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function renderCSS()
    {
        if (!$this->isUnsupported()) {
            return;
        }

        parent::renderCSS();
        ?>
        <style type="text/css">
            #<?= $this->getID() ?>_panel_body {
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
        if (!$this->isUnsupported()) {
            return;
        }

        parent::renderHTML();
    }

    /**
     * @inheritDoc
     */
    protected function renderBody()
    {
        $currentVersion = $this->getPhpVersion();
        ?>
        <div class="col-md-12">
            <p>O servidor está a executar o <strong>PHP <?= htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8') ?></strong>, uma versão que já atingiu o fim de vida (<em>End of Life</em>) e não recebe atualizações de segurança.</p>
            <p>A utilização de uma versão descontinuada do PHP pode comprometer a segurança, estabilidade e desempenho da aplicação. Recomenda-se a atualização imediata do PHP no servidor para uma versão suportada.</p>
            <p><i class="fas fa-exclamation-triangle"></i> <strong>Futuras versões do CatecheSis deixarão de funcionar neste servidor.</strong> <i class="fas fa-exclamation-triangle"></i></p>
        </div>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function handlePost()
    {
        // Informational panel, no POST processing required
    }
}
