<?php


namespace catechesis\gui;


/**
 * An abstract class containing attributes and methods that ar common
 * for several widgets related to listing catechumens.
 */
abstract class AbstractCatechumensListingWidget extends Widget
{
    protected /*bool*/   $sacraments_shown = false;           // Defines whether the sacraments columns is shown at start
    protected /*string*/ $additional_toolbar_buttons = null;  // Any user-defined HTML buttons to add to the widget toolbar


    public function __construct(string $id = null)
    {
        parent::__construct($id);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('css/DataTables/datatables.min.css');
        $this->addCSSDependency('css/stack-glyphicons.css');
        $this->addCSSDependency('css/comprovativo_dot.css');
        $this->addCSSDependency('font-awesome/fontawesome-free-5.15.1-web/css/all.min.css');

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');
        $this->addJSDependency('js/rowlink.js');
        $this->addJSDependency('js/DataTables/datatables.min.js');

        // Static CSS styles of this widget that are common to all instances (only imported once)
        $this->addCSSDependency('gui/widgets/AbstractCatechumensListing/AbstractCatechumensListingWidget.css');

        // Static javascript code of this widget that is common to all instances (only imported once)
        $this->addJSDependency('gui/widgets/AbstractCatechumensListing/AbstractCatechumensListingWidget.js');

    }


    /**
     * Sets the list of catechumens to render in this list widget.
     * This must be set prior to calling renderHTML().
     * @param $catechumensList
     * @return $this
     */
    public function setCatechumensList($catechumensList)
    {
        $this->catechumens_list = $catechumensList;
        return $this;
    }


    /**
     * Defines if the sacraments columns should be shown in the initial state,
     * whithout the user clicking the corresponding button.
     * @param bool $
     * @return $this
     */
    public function setSacramentsShown(bool $value)
    {
        $this->sacraments_shown = $value;
        return $this;
    }


    /**
     * Adds user-defined HTML code to the widget toolbar.
     * Allows the addition of custom buttons to the same toolbar already created by this widget.
     * @param string $buttonsCode
     * @return $this
     */
    public function addButtonsToToolbar(string $buttonsCode)
    {
        $this->additional_toolbar_buttons = $buttonsCode;
        return $this;
    }



    /**
     * @inheritDoc
     */
    public function renderCSS()
    {
    }


    /**
     * @inheritDoc
     */
    public function renderJS()
    {
    }
}