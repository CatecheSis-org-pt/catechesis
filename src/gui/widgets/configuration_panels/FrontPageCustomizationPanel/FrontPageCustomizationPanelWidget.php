<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../../core/Configurator.php');
require_once(__DIR__ . '/../../../../core/Utils.php');
require_once(__DIR__ . '/../../../../core/UserData.php');

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Configurator;
use catechesis\UserData;
use catechesis\Utils;
use uLogin;


/**
 * Panel containing the parish settings.
 * Allows to change the name and logo.
 */
class FrontPageCustomizationPanelWidget extends AbstractSettingsPanelWidget
{
    public static $ACTION_PARAMETER = "customize_front_page";

    public function __construct(string $id = null)
    {
        parent::__construct($id);

        $this->addCSSDependency("css/cropperjs-1.5.12/cropper.css");
        $this->addCSSDependency("font-awesome/fontawesome-free-5.15.1-web/css/all.min.css");
        $this->addJSDependency("js/cropperjs-1.5.12/cropper.js");
        $this->addJSDependency("gui/common/cropper.js/cropper_helper_functions.js");
        $this->addJSDependency("gui/widgets/configuration_panels/FrontPageCustomizationPanel/FrontPageCustomizationPanel.js");

        parent::setTitle("Personalizar página pública");
        //parent::setURL($_SERVER['PHP_SELF']);
        parent::useHeaderButtons();
    }


    protected function renderBody()
    {
        //Get parish data
        try
        {
            $useCustomImage = Configurator::getConfigurationValueOrDefault(Configurator::KEY_USE_CUSTOM_PUBLIC_PAGE_IMAGE);
        }
        catch(\Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }
        ?>

            <div class="form-group">

                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="radio disabled" id="<?=$this->getID()?>_div_use_default_image">
                                <label for="<?=$this->getID()?>_use_default_image" style="display: contents;">
                                    <input type="radio" class="" id="<?=$this->getID()?>_use_default_image" name="use_custom_image" value="false" style="cursor: auto;" onchange="onCheckUseCustomImage('<?=$this->getID()?>');" <?php if(!$useCustomImage) echo("checked"); ?> disabled>
                                    Utilizar o fundo padrão para a página principal
                                </label>
                            </div>
                            <div class="radio disabled" id="<?=$this->getID()?>_div_use_custom_image">
                                <label for="<?=$this->getID()?>_use_custom_image" style="display: contents;">
                                    <input type="radio" class="" id="<?=$this->getID()?>_use_custom_image" name="use_custom_image" value="true" style="cursor: auto;" onchange="onCheckUseCustomImage('<?=$this->getID()?>'); prepairCropper(cropper_<?= $this->getID() ?>, '<?= $this->getID() ?>_custom_frontpage_image', 2.3);" <?php if($useCustomImage) echo("checked"); ?> disabled>
                                    Personalizar a imagem de fundo da página principal
                                </label>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" class="form-control" id="<?=$this->getID()?>_use_custom_image_backup" value="<?php if($useCustomImage) echo("true"); else echo("false"); ?>" readonly>
                </div>

                <div id="<?=$this->getID()?>_customize_front_page_collapsible" class="collapse <?php if($useCustomImage) echo("in"); ?>">
                    <!-- Custom image -->
                    <div class="col-md-11">
                        <label for="nome">Imagem de fundo:</label>
                        <div class="row clearfix"></div>
                        <img id="<?= $this->getID()?>_custom_frontpage_image" class="img-responsive img-thumbnail" src="<?= UserData::getParishCustomFrontPageImageQueryURL() ?>">
                        <div class="row clearfix" style="margin-bottom: 10px"></div>
                        <div class="btn-group" id="<?= $this->getID()?>_image_buttons_toolbar" style="display:none;">
                            <button type="button" class="btn btn-default" id="<?= $this->getID()?>_btn_download" onclick="download_photo(cropper_<?= $this->getID() ?>, 'pagina_inicial.jpg', 'image/jpeg')"><span class="fas fa-download"></span> Transferir</button>
                            <label class="btn btn-default btn-upload" for="<?= $this->getID()?>_input_image" title="Carregar uma nova imagem">
                                <input type="file" class="sr-only" id="<?= $this->getID()?>_input_image" name="input_image" accept="image/*">
                                <span class="docs-tooltip" data-toggle="tooltip" title="" data-original-title="Carregar uma nova imagem">
                                    <span class="fa fa-upload"></span> Carregar nova imagem
                                </span>
                            </label>
                        </div>
                        <input type="hidden" id="<?= $this->getID()?>_new_custom_image" name="new_custom_image" value="">
                    </div>

                    <div class="row clearfix" style="margin-bottom:20px; "></div>
                </div>

            </div>

            <div class="row clearfix" style="margin-bottom:20px; "></div>

            <input type="hidden" name="action" value="<?= self::$ACTION_PARAMETER ?>">
    <?php
    }


    /**
     * @inherit
     */
    protected function onEdit()
    {?>
        document.getElementById("<?= $this->getID()?>_image_buttons_toolbar").style.display = 'inline';
        document.getElementById("<?=$this->getID()?>_use_default_image").disabled = false;
        document.getElementById("<?=$this->getID()?>_div_use_default_image").classList.remove("disabled");
        document.getElementById("<?=$this->getID()?>_use_custom_image").disabled = false;
        document.getElementById("<?=$this->getID()?>_div_use_custom_image").classList.remove("disabled");

        cropper_<?= $this->getID() ?> = restartCropper(cropper_<?= $this->getID() ?>, "<?= $this->getID() ?>_custom_frontpage_image", 2.3);
       <?php
    }


    /**
     * @inherit
     */
    protected function onCancel()
    {
        ?>
        cropper_<?= $this->getID() ?>.destroy();
        document.getElementById('<?= $this->getID()?>_custom_frontpage_image').src = '<?= UserData::getParishCustomFrontPageImageQueryURL() ?>';
        document.getElementById('<?= $this->getID()?>_new_custom_image').value = null;
        document.getElementById("<?= $this->getID()?>_image_buttons_toolbar").style.display = 'none';
        document.getElementById("<?=$this->getID()?>_use_default_image").disabled = true;
        document.getElementById("<?=$this->getID()?>_div_use_default_image").classList.add("disabled");
        document.getElementById("<?=$this->getID()?>_use_custom_image").disabled = true;
        if(document.getElementById("<?=$this->getID()?>_use_custom_image_backup").value == "true")
            document.getElementById("<?=$this->getID()?>_use_custom_image").checked = true;
        else
            document.getElementById("<?=$this->getID()?>_use_default_image").checked = true;
        document.getElementById("<?=$this->getID()?>_div_use_custom_image").classList.add("disabled");
        onCheckUseCustomImage('<?=$this->getID()?>');
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
                                                fillColor: '#fff',
                                                imageSmoothingEnabled: false,
                                                imageSmoothingQuality: 'high'}).toDataURL('image/jpeg');
        document.getElementById('<?= $this->getID()?>_custom_frontpage_image').src = imageData;
        document.getElementById('<?= $this->getID()?>_new_custom_image').value = imageData;
        cropper_<?= $this->getID() ?>.destroy();
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

            //Setup image file loader
            document.getElementById("<?= $this->getID()?>_input_image").onchange = function (evt) {
                var tgt = evt.target || window.event.srcElement,
                    files = tgt.files;

                // FileReader support
                if (FileReader && files && files.length) {
                    var fr = new FileReader();
                    fr.onload = function () {
                        document.getElementById("<?= $this->getID()?>_custom_frontpage_image").src = fr.result;
                        cropper_<?= $this->getID() ?> = restartCropper(cropper_<?= $this->getID() ?>, "<?= $this->getID()?>_custom_frontpage_image", 2.3);
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

            /**
             * Waits 500ms (for the Bootstrap collapsible to show) and then
             * resets the cropper.
             */
            function prepairCropper(cropperInstance, imageId, aspectRatio=null)
            {
                sleep(500).then(() => {
                    cropper_<?= $this->getID() ?> = restartCropper(cropperInstance, imageId, aspectRatio);
                });
            }
        </script>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function handlePost()
    {
        $action = Utils::sanitizeInput($_POST['action']);

        //Edit public front page data
        if($action == self::$ACTION_PARAMETER)
        {
            $editUseCustomImage = Utils::sanitizeInput($_POST['use_custom_image']) == "true";
            $editCustomImage = Utils::sanitizeInput($_POST['new_custom_image']);

            try
            {
                UserData::saveUploadedParishPublicPicture($editCustomImage);
                Configurator::setConfigurationValue(Configurator::KEY_USE_CUSTOM_PUBLIC_PAGE_IMAGE, $editUseCustomImage);

                writeLogEntry("Personalizou a página principal pública.");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Página principal atualizada.</div>");
            }
            catch(\Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                //die();
            }
        }
    }
}