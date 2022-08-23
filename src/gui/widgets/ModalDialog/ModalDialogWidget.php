<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../Widget.php');
require_once(__DIR__ . "/../../common/Animation.php");
require_once(__DIR__ . "/../../common/Button.php");

class ModalDialogWidget extends Widget
{
    const SIZE_MEDIUM = "modal-vertical-centered";
    const SIZE_LARGE = "modal-lg";

    private $title = "";
    private $body = "";
    private $buttons = array();
    private $size = self::SIZE_MEDIUM;
    private $entryAnimation = Animation::RUBBER;
    private $exitAnimation = Animation::FADE_OUT_UP;


    public function __construct(string $id = null)
    {
        parent::__construct($id);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('css/animate.min.css');

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');

        // Static CSS styles of this widget that are common to all instances (only imported once)
        $this->addCSSDependency('gui/widgets/ModalDialog/ModalDialogWidget.css');

        // Static javascript code of this widget that is common to all instances (only imported once)
        $this->addJSDependency('gui/widgets/ModalDialog/ModalDialogWidget.js');
    }


    /**
     * Set the title of the modal dialog.
     * Expects a string with plain text, possibly with HTML formatting code.
     * @param string $contents
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }


    /**
     * Set the main content of the modal dialog.
     * Expects a string with HTML code.
     * @param string $contents
     * @return $this
     */
    public function setBodyContents(string $contents)
    {
        $this->body = $contents;
        return $this;
    }


    /**
     * Renders the body of the modal dialog.
     * @return void
     */
    protected function renderBodyContents()
    {
        echo($this->body);
    }


    /**
     * Adds a button to the modal dialog footer.
     * @param Button $button
     */
    public function addButton(Button $button)
    {
        $this->buttons[] = $button;
        return $this;
    }


    /**
     * Sets the modal tipology/size.
     * Use one of the constants SIZE_* in this class as argument.
     * @param string $size
     */
    public function setSize(string $size)
    {
        $this->size = $size;
    }


    /**
     * Set the modal window entry animation.
     * Use one of the constants of the abstract class ModalAnimation as argument.
     * @param string $animation
     * @return $this
     */
    public function setEntryAnimation(string $animation)
    {
        $this->entryAnimation = $animation;
        return $this;
    }


    /**
     * Set the modal window exit animation.
     * Use one of the constants of the abstract class ModalAnimation as argument.
     * @param string $animation
     * @return $this
     */
    public function setExitAnimation(string $animation)
    {
        $this->exitAnimation = $animation;
        return $this;
    }





    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        ?>
        <div id="<?=$this->getID()?>" class="modal fade <?= $this->getCustomClassesString()?>" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
            <div class="modal-dialog <?= $this->size ?>">
                <div class="modal-content">
                    <div class="modal-header" id="<?=$this->getID()?>_header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="<?=$this->getID()?>_title"><?= $this->title ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="panel-body" id="<?=$this->getID()?>_body">
                            <?php $this->renderBodyContents(); ?>
                        </div>
                    </div>
                    <div class="modal-footer" id="<?=$this->getID()?>_footer">
                        <?php
                        foreach($this->buttons as $button)
                        {
                            $button->renderHTML();
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * @inheritDoc
     */
    public function renderJS()
    {
        ?>
        <script type="text/javascript">
        <?php
        if($this->entryAnimation != Animation::NONE)
        {
        ?>
            $('#<?=$this->getID()?>').on('show.bs.modal', function (e) {
                $('#<?=$this->getID()?> .modal-dialog').attr('class', 'modal-dialog <?= $this->size ?> <?= "animated animate__animated " . $this->entryAnimation ?>');
            })
        <?php
        }
        if($this->exitAnimation != Animation::NONE)
        {
            ?>
            $('#<?=$this->getID()?>').on('hide.bs.modal', function (e) {
                $('#<?=$this->getID()?> .modal-dialog').attr('class', 'modal-dialog <?= $this->size ?> <?= "animated animate__animated " . $this->exitAnimation ?>');
            })
        <?php
        }
        ?>
        </script>
        <?php
    }
}