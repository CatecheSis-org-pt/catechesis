<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../../core/Configurator.php');
require_once(__DIR__ . '/../../../../core/Utils.php');
require_once(__DIR__ . '/../../../../core/UserData.php');
require_once(__DIR__ . '/../../../../core/domain/Locale.php');
require_once(__DIR__ . '/../../../../core/log_functions.php');

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Configurator;
use catechesis\UserData;
use catechesis\Utils;
use core\domain\Locale;
use uLogin;


/**
 * Panel containing the parish settings.
 * Allows to change the name and logo.
 */
class ParishSettingsPanelWidget extends AbstractSettingsPanelWidget
{
    public static $ACTION_PARAMETER = "edit_parish_details";

    public function __construct(string $id = null)
    {
        parent::__construct($id, true);

        $this->addCSSDependency("css/cropperjs-1.5.12/cropper.css");
        $this->addCSSDependency("css/quill-1.3.6/quill.snow.css");
        $this->addCSSDependency("font-awesome/fontawesome-free-5.15.1-web/css/all.min.css");
        $this->addCSSDependency("gui/widgets/configuration_panels/ParishSettingsPanel/ParishSettingsPanelWidget.css");

        $this->addJSDependency("js/cropperjs-1.5.12/cropper.js");
        $this->addJSDependency("js/quill-1.3.6/quill.min.js");
        $this->addJSDependency("gui/common/cropper.js/cropper_helper_functions.js");

        parent::setTitle("Dados da paróquia");
        //parent::setURL($_SERVER['PHP_SELF']);
        parent::useHeaderButtons();
    }


    protected function renderBody()
    {
        $parishName = null;
        $parishPlace = null;
        $parishDiocese = null;
        $parishCustomTableFooter = null;
        $localization_code = null;

        //Get parish data
        try
        {
            $parishName = Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME);
            $parishPlace = Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_PLACE);
            $parishDiocese = Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_DIOCESE);
            $parishCustomTableFooter = Utils::sanitizeOutput(Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER));
            $localization_code = Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE);
        }
        catch(\Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        }

        ?>

            <div class="form-group">

                <!-- Parish logo -->
                <div class="col-md-6">
                    <label for="nome">Logotipo:</label>
                    <div class="row clearfix"></div>
                    <img id="<?= $this->getID()?>_parish_logo" class="img-responsive img-thumbnail" src="<?= $this->getPathPrefix() .  UserData::getParishLogoQueryURL() ?>">
                    <div class="row clearfix" style="margin-bottom: 10px"></div>
                    <div class="btn-group" id="<?= $this->getID()?>_image_buttons_toolbar" style="display:none;">
                        <button type="button" class="btn btn-default" id="<?= $this->getID()?>_btn_download" onclick="download_photo(cropper_<?= $this->getID() ?>, 'logotipo_paroquia.png', 'image/png')"><span class="fas fa-download"></span> Transferir</button>
                        <label class="btn btn-default btn-upload" for="<?= $this->getID()?>_input_image" title="Carregar uma nova imagem">
                            <input type="file" class="sr-only" id="<?= $this->getID()?>_input_image" name="input_image" accept="image/*">
                            <span class="docs-tooltip" data-toggle="tooltip" title="" data-original-title="Carregar uma nova imagem">
                                <span class="fa fa-upload"></span> Carregar nova imagem
                            </span>
                        </label>
                    </div>
                    <input type="hidden" id="<?= $this->getID()?>_new_parish_logo" name="new_parish_logo" value="">
                </div>

                <div class="row clearfix" style="margin-bottom:20px; "></div>

                <!--Parish name-->
                <div class="col-md-6">
                  <label for="nome">Nome:</label>
                  <input type="text" class="form-control" id="<?=$this->getID()?>_name" name="name" placeholder="Paróquia de ..." style="cursor: auto;" value="<?= $parishName ?>" required readonly>
                  <input type="hidden" class="form-control" id="<?=$this->getID()?>_name_backup" value="<?= $parishName ?>" readonly>
                </div>

                <!--Parish place -->
                <div class="col-md-6">
                  <label for="place">Localidade:</label>
                  <input type="text" class="form-control" id="<?=$this->getID()?>_place" name="place" placeholder="Freguesia/local" style="cursor: auto;" value="<?= $parishPlace ?>" readonly>
                  <input type="hidden" class="form-control" id="<?=$this->getID()?>_place_backup" value="<?= $parishPlace ?>" readonly>
                </div>

                <div class="row clearfix" style="margin-bottom:20px; "></div>

                <!--Parish diocese -->
                <div class="col-md-6">
                    <label for="place">Diocese:</label>
                    <input type="text" class="form-control" id="<?=$this->getID()?>_diocese" name="diocese" placeholder="Diocese de ..." style="cursor: auto;" value="<?= $parishDiocese ?>" readonly>
                    <input type="hidden" class="form-control" id="<?=$this->getID()?>_diocese_backup" value="<?= $parishDiocese ?>" readonly>
                </div>

                <!--Country (localization) -->
                <div class="col-md-2">
                    <label for="place">País: <span class="fas fa-question-circle" data-toggle="popover" data-placement="top" data-content='Utilizado para configurações regionais, tais como número de dígitos do código postal e telefone em formulários.'></span></label>
                    <select id="<?=$this->getID()?>_locale" name="locale" class="form-control" disabled>
                        <option value="<?= Locale::PORTUGAL ?>" <?php if($localization_code == Locale::PORTUGAL) echo("selected"); ?>>Portugal</option>
                        <option value="<?= Locale::BRASIL ?>" <?php if($localization_code == Locale::BRASIL) echo("selected"); ?>>Brasil</option>
                    </select>
                    <input type="hidden" class="form-control" id="<?=$this->getID()?>_locale_backup" value="<?= $localization_code ?>" readonly>
                </div>
            </div>

            <div class="row clearfix" style="margin-bottom:20px; "></div>

            <!-- Parish custom footer-->
            <div class="col-md-12">
              <label for="footer">Rodapé:</label>
                <!-- Quill editor -->
                <div id="<?=$this->getID()?>_standalone-container">
                    <div id="<?=$this->getID()?>_toolbar_container" style="pointer-events: none">
                        <span class="ql-formats">
                          <button class="ql-bold" title="Negrito"></button>
                          <button class="ql-italic" title="Itálico"></button>
                          <button class="ql-underline" title="Sublinhado"></button>
                        </span>
                    </div>
                    <div id="<?=$this->getID()?>_editor_container"></div>
                </div>
                <input type="hidden" class="form-control" id="<?=$this->getID()?>_footer" name="footer" value="<?= $parishCustomTableFooter ?>" readonly>
                <input type="hidden" class="form-control" id="<?=$this->getID()?>_footer_backup" value="<?= $parishCustomTableFooter ?>" readonly>
            </div>

            <input type="hidden" name="action" value="<?= self::$ACTION_PARAMETER ?>">
    <?php
    }


    /**
     * @inherit
     */
    protected function onEdit()
    {?>
        document.getElementById("<?= $this->getID()?>_image_buttons_toolbar").style.display = 'inline';
        document.getElementById("<?=$this->getID()?>_name").readOnly = false;
        document.getElementById("<?=$this->getID()?>_place").readOnly = false;
        document.getElementById("<?=$this->getID()?>_diocese").readOnly = false;
        document.getElementById("<?=$this->getID()?>_locale").disabled = false;
        <?=$this->getID()?>_quill.root.innerHTML = document.getElementById("<?=$this->getID()?>_footer").value;
        <?=$this->getID()?>_quill.enable();
        document.getElementById("<?=$this->getID()?>_toolbar_container").style = 'pointer-events: initial'; //Enable Quill toolbar
        cropper_<?= $this->getID() ?> = restartCropper(cropper_<?= $this->getID() ?>, '<?= $this->getID() ?>_parish_logo', null);
       <?php
    }


    /**
     * @inherit
     */
    protected function onCancel()
    {
        ?>
        cropper_<?= $this->getID() ?>.destroy();
        document.getElementById("<?=$this->getID()?>_parish_logo").src = "<?= $this->getPathPrefix() . UserData::getParishLogoQueryURL() ?>";
        document.getElementById('<?= $this->getID()?>_new_parish_logo').value = null;
        document.getElementById("<?= $this->getID()?>_image_buttons_toolbar").style.display = 'none';
        document.getElementById("<?=$this->getID()?>_name").readOnly = true;
        document.getElementById("<?=$this->getID()?>_place").readOnly = true;
        document.getElementById("<?=$this->getID()?>_diocese").readOnly = true;
        document.getElementById("<?=$this->getID()?>_locale").disabled = true;
        <?=$this->getID()?>_quill.disable();
        document.getElementById("<?=$this->getID()?>_toolbar_container").style = 'pointer-events: none'; //Disable Quill toolbar
        document.getElementById("<?=$this->getID()?>_name").value = document.getElementById("<?=$this->getID()?>_name_backup").value;
        document.getElementById("<?=$this->getID()?>_place").value = document.getElementById("<?=$this->getID()?>_place_backup").value;
        document.getElementById("<?=$this->getID()?>_diocese").value = document.getElementById("<?=$this->getID()?>_diocese_backup").value;
        document.getElementById("<?=$this->getID()?>_locale").value = document.getElementById("<?=$this->getID()?>_locale_backup").value;
        document.getElementById("<?=$this->getID()?>_footer").value = document.getElementById("<?=$this->getID()?>_footer_backup").value;
        <?=$this->getID()?>_quill.root.innerHTML = document.getElementById("<?=$this->getID()?>_footer_backup").value;
        <?php
    }


    /**
     * @inherit
     */
    protected function onSubmit()
    {?>
        imageData = cropper_<?= $this->getID() ?>.getCroppedCanvas({  minWidth: 256,
                                                minHeight: 256,
                                                maxWidth: 4096,
                                                maxHeight: 4096,
                                                //fillColor: '#fff',
                                                imageSmoothingEnabled: false,
                                                imageSmoothingQuality: 'high'}).toDataURL(/*'image/png'*/);
        document.getElementById('<?= $this->getID()?>_parish_logo').src = imageData;
        document.getElementById('<?= $this->getID()?>_new_parish_logo').value = imageData;
        cropper_<?= $this->getID() ?>.destroy();

        if(<?=$this->getID()?>_quill.getText().trim().length > 0)
            document.getElementById("<?=$this->getID()?>_footer").value = <?=$this->getID()?>_quill.root.innerHTML;
        else
            document.getElementById("<?=$this->getID()?>_footer").value = ''; //Avoid the "<p><br></p>" that Quill adds to empty contents
        <?php
    }


    /**
     * @inherit
     */
    public function renderJS()
    {
        parent::renderJS();
        ?>
        <script type="text/javascript">

            cropper_<?= $this->getID() ?> = null; //Just declare this global variable so that it can be used in onEdit() and onCancel() functions

            function download_photo()
            {
                imageData = cropper.getCroppedCanvas({  minWidth: 256,
                                                        minHeight: 256,
                                                        maxWidth: 4096,
                                                        maxHeight: 4096,
                                                        //fillColor: '#fff',
                                                        imageSmoothingEnabled: false,
                                                        imageSmoothingQuality: 'high'}).toDataURL(/*'image/png'*/);
                var link = document.createElement("a");
                link.download = "logotipo_paroquia.png";
                link.href = imageData;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                delete link;
            }

            //Setup image loader from file
            document.getElementById("<?= $this->getID()?>_input_image").onchange = function (evt) {
                var tgt = evt.target || window.event.srcElement,
                    files = tgt.files;

                // FileReader support
                if (FileReader && files && files.length) {
                    var fr = new FileReader();
                    fr.onload = function () {
                        document.getElementById("<?= $this->getID()?>_parish_logo").src = fr.result;
                        cropper_<?= $this->getID() ?> = restartCropper(cropper_<?= $this->getID() ?>, "<?= $this->getID()?>_parish_logo", null);
                    }
                    fr.readAsDataURL(files[0]);
                }

                // Not supported
                else {
                    // fallback -- perhaps submit the input to an iframe and temporarily store
                    // them on the server until the user's session ends.
                    alert("O seu navegador não suporta a edição de imagens. Por favor utilize outre navegador para carregar o logotipo da paróquia.");
                }
            }

            //Setup Quill HTML editor
            var <?=$this->getID()?>_quill = new Quill('#<?=$this->getID()?>_editor_container', {
                modules: {
                    toolbar: '#<?=$this->getID()?>_toolbar_container',
                    clipboard: {
                        matchVisual: false //Avoids Quill writing "<p><br></p>" when the contents is empty
                    }
                },
                placeholder: 'Rodapé a apresentar nas páginas impressas.',
                readOnly: true,
                theme: 'snow'
            });

            <?=$this->getID()?>_quill.root.innerHTML = document.getElementById("<?=$this->getID()?>_footer").value;
            if(document.getElementById("<?=$this->getID()?>_footer").value !== '')
            {
                <?=$this->getID()?>_quill.root.dataset.placeholder = ''; //Remove placeholder
            }

            $(function () {
                $('[data-toggle="popover"]').popover({ trigger: 'hover',
                    html: true,
                    delay: { 'show': 500, 'hide': 100 }
                });
            })
        </script>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function handlePost()
    {
        if($this->requires_admin_privileges && !Authenticator::isAdmin())
            return; //Do not render this widget if the user is not admin and it requires admin priviledges

        $action = Utils::sanitizeInput($_POST['action']);

        //Edit parish data
        if($action == self::$ACTION_PARAMETER)
        {
            $editParishLogo = Utils::sanitizeInput($_POST['new_parish_logo']);
            $editParishName = Utils::sanitizeInput($_POST['name']);
            $editParishPlace = Utils::sanitizeInput($_POST['place']);
            $editParishDiocese = Utils::sanitizeInput($_POST['diocese']);
            $editLocale = Utils::sanitizeInput($_POST['locale']);
            $editParishCustomFooter = Utils::sanitizeKeepFormattingTags($_POST['footer']); //Will be sanitized on output

            if($editLocale != Locale::PORTUGAL && $editLocale != Locale::BRASIL)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O país que introduziu não é suportado. </div>");
            }

            try
            {
                UserData::saveUploadedParishLogo($editParishLogo);
                Configurator::setConfigurationValue(Configurator::KEY_PARISH_NAME, $editParishName);
                Configurator::setConfigurationValue(Configurator::KEY_PARISH_PLACE, $editParishPlace);
                Configurator::setConfigurationValue(Configurator::KEY_PARISH_DIOCESE, $editParishDiocese);
                Configurator::setConfigurationValue(Configurator::KEY_LOCALIZATION_CODE, $editLocale);
                Configurator::setConfigurationValue(Configurator::KEY_PARISH_CUSTOM_TABLE_FOOTER, $editParishCustomFooter);

                // Auto-disable country-specific optional fields when applicable
                if($editLocale == Locale::BRASIL)
                {
                    try
                    {
                        Configurator::setConfigurationValue(Configurator::KEY_OPTIONAL_FIELD_NIF_ENABLED, false);
                        writeLogEntry("Desativou o campo opcional NIF.");
                    }
                    catch(\Exception $ignored)
                    {
                        /* fail silently to not block saving other settings */
                    }
                }

                writeLogEntry("Modificou os dados da paróquia.");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Os dados da paróquia foram actualizados.</div>");
            }
            catch(\Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                //die();
            }
        }
    }
}