<?php

namespace catechesis\gui;

/**
 * Abstracts the classes of GUI buttons.
 */
abstract class ButtonType
{
    const PRIMARY = "btn-primary";
    const SECONDARY = "btn-default";
    const DANGER = "btn-danger";
    const WARNING = "btn-warning";
    const INFO = "btn-info";
    const SUCCESS = "btn-success";
}


/**
 * Encapsulates the data associated with a GUI button.
 */
class Button
{
    private $text;     // Button face text
    private $type;     // Button action severity type (to define its color)
    private $onClick;  // HTML onClick action (javascript code)


    /**
     * @param string $text - Button face text
     * @param string $type - Button action severity type (to define its color). One of the constants of class ButtonType.
     * @param string|null $onClick - HTML onClick action (javascript code)
     */
    public function __construct(string $text, string $type = ButtonType::SECONDARY, string $onClick = null)
    {
        $this->text = $text;
        $this->type = $type;
        $this->onClick = $onClick;
    }


    public function getText()
    {
        return $this->text;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOnClickAction()
    {
        return $this->onClick;
    }


    /**
     * Renders the HTML button.
     */
    public function renderHTML()
    {
    ?>
        <button type="button" class="btn <?= $this->getType() ?>" data-dismiss="modal" onclick="<?= $this->getOnClickAction() ?>"><?= $this->getText() ?></button>
    <?php
    }
}
