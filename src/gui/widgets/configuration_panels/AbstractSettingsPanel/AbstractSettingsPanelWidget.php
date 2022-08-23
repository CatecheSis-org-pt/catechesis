<?php

namespace catechesis\gui;


use catechesis\Authenticator;

/**
 * A generic UI panel to with controls to adjust several
 * CatecheSis settings.
 */
abstract class AbstractSettingsPanelWidget extends Widget
{
    protected /*string*/ $panel_title;                      // Title shown on the panel heading
    protected /*string*/ $post_URL;                         // URL where to POST when saving settings
    protected /*bool*/   $use_header_buttons = true;        // Whether to add 'Edit', 'Save' and 'Cancel' buttons to the panel heading
    protected /*string*/ $panel_style = "panel-default";    // Bootstrap style to apply to this panel (e.g. 'panel-default', 'panel-success', ...)
    protected /*bool*/   $requires_admin_privileges = true; // Whether this widget can only be shown to admin users

    public function __construct(string $id = null, bool $requires_admin_privileges=true)
    {
        parent::__construct($id);

        $this->post_URL = $_SERVER['PHP_SELF'];             //By default, POST to the page that is rendering this widget
        $this->requires_admin_privileges = $requires_admin_privileges;

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('font-awesome/fontawesome-free-5.15.1-web/css/all.min.css');

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');
    }


    /**
     * Retunrs the panel's title.
     * @return mixed
     */
    public function getTitle()
    {
        return $this->panel_title;
    }

    /**
     * Method that handles the parameters passed to the page
     * in a POST invocation, to set the configuration parameters
     * appropriately.
     * Must be implemented in a subclass.
     * @return void
     */
    public abstract function handlePost();


    /**
     * Renders the HTML contents of the panel body.
     * Must be implemented in a subclass.
     */
    protected abstract function renderBody();



    /**
     * Javascript function run when the user clicks on the 'Edit' button on the
     * panel heading. Should enable text fields, buttons and such.
     * Override this function in a subclass to define the actual Javascript code.
     * @return void
     */
    protected function onEdit()
    {
        //Empty function body
    }


    /**
     * Javascript function run when the user clicks on the 'Cancel' button on the
     * panel heading. Should return the edited fields to their original values.
     * Override this function in a subclass to define the actual Javascript code.
     * @return void
     */
    protected function onCancel()
    {
        //Empty function body
    }


    /**
     * Javascript function run when the user clicks on the 'Save' button on the
     * panel heading. Should validate the user input and return true only in case
     * it is valid.
     * Override this function in a subclass to define the actual Javascript code.
     * @return void
     */
    protected function onSubmit()
    {
        //Empty function body
    }


    /**
     * Sets the title shown on the panel header.
     * @param string $title
     * @return $this
     */
    protected function setTitle(string $title)
    {
        $this->panel_title = $title;
        return $this;
    }

    /**
     * Sets the URL where the settings form is POSTed.
     * @param string $URL
     * @return $this
     */
    protected function setURL(string $URL)
    {
        $this->post_URL = $URL;
        return $this;
    }


    /**
     * Sets whether the panel heading buttons 'Edit', 'Save' and 'Cancel'
     * should be rendered.
     * @param bool $enable
     * @return $this
     */
    protected function useHeaderButtons(bool $enable = true)
    {
        $this->use_header_buttons = $enable;
        return $this;
    }

    /**
     * Set one of the Bootstrap CSS classes for this panel (e.g. 'panel-default', 'panel-success', ...).
     * @param string $style
     * @return void
     */
    protected function setPanelStyle(string $style)
    {
        $this->panel_style = $style;
    }

    public function requiresAdminPriviledges()
    {
        return $this->requires_admin_privileges;
    }

    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        if($this->requires_admin_privileges && !Authenticator::isAdmin())
            return; //Do not render this widget if the user is not admin and it requires admin priviledges
        ?>
        <div id="<?=$this->getID()?>" class="settings_panel_widget<?= $this->getCustomClassesString()?>" style="<?=$this->getCustomInlineStyle()?>">
            <form role="form" id="form_settings_<?= $this->getID() ?>" name="form_admin" onsubmit="return on_submit_<?= $this->getID() ?>()" action="<?= $this->post_URL ?>" method="post">
                <div class="panel <?= $this->panel_style ?>" id="<?=$this->getID()?>_panel">
                    <div class="panel-heading" id="<?=$this->getID()?>_panel_heading"><?= $this->panel_title ?>
                        <?php
                        if($this->use_header_buttons)
                        {?>
                        <div class="btn-group-xs pull-right" role="group" aria-label="...">
                            <button type="button" id="<?=$this->getID()?>_cancelar_bt" onclick="on_cancel_<?= $this->getID() ?>()" class="btn btn-default glyphicon glyphicon-remove" style="display: none;"> Cancelar</button>
                            <button type="submit" id="<?=$this->getID()?>_guardar_bt" class="btn btn-primary glyphicon glyphicon-floppy-disk" style="display: none;"> Guardar</button>
                            <button type="button" id="<?=$this->getID()?>_editar_bt" onclick="on_edit_<?= $this->getID() ?>()" class="btn btn-default glyphicon glyphicon-pencil"> Editar</button>
                        </div>
                        <?php
                        }
                        ?>
                    </div>
                    <div class="panel-body" id="<?=$this->getID()?>_panel_body">
                        <?= $this->renderBody() ?>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }


    /**
     * @inheritDoc
     */
    public function renderJS()
    {
        if($this->requires_admin_privileges && !Authenticator::isAdmin())
            return; //Do not render this widget if the user is not admin and it requires admin priviledges

        ?>
        <script type="text/javascript">
        <?php
        if($this->use_header_buttons)
        {?>
            function on_edit_<?= $this->getID() ?>()
            {
                <?= $this->onEdit(); ?>

                document.getElementById("<?=$this->getID()?>_guardar_bt").disabled = false;
                document.getElementById("<?=$this->getID()?>_cancelar_bt").disabled = false;
                $('#<?=$this->getID()?>_editar_bt').hide();
                $('#<?=$this->getID()?>_guardar_bt').show();
                $('#<?=$this->getID()?>_cancelar_bt').show();
            }

            function on_cancel_<?= $this->getID() ?>()
            {
                <?= $this->onCancel(); ?>
                document.getElementById("<?=$this->getID()?>_guardar_bt").disabled = true;
                document.getElementById("<?=$this->getID()?>_cancelar_bt").disabled = true;
                $('#<?=$this->getID()?>_editar_bt').show();
                $('#<?=$this->getID()?>_guardar_bt').hide();
                $('#<?=$this->getID()?>_cancelar_bt').hide();
            }
        <?php
        }?>

            function on_submit_<?= $this->getID() ?>()
            {
                <?= $this->onSubmit(); ?>
            }
        </script>
        <?php
    }
}