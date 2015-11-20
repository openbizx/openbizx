<?php

namespace Openbizx\Easy;

use Openbizx\I18n\I18n;
/**
 * FormReference class is the class that contain form reference.
 * this is part of WebPage
 *
 * @package openbiz.bin.easy
 * @author rocky swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class FormReference
{

    public $objectName;
    public $subForms;
    public $objectDescription;
    private $_parentForm;
    public $display = true;
    protected $viewName;

    /**
     * Contructor, store form info from array to variable of class
     *
     * @param array $xmlArr array of form information
     */
    public function __construct($xmlArr)
    {
        foreach ($xmlArr["ATTRIBUTES"] as $name => $value) {
            $name = 'm_' . ucfirst(strtolower($name));
            $this->$name = $value;
        }
        $this->objectName = $xmlArr["ATTRIBUTES"]["NAME"];
        $this->subForms = $xmlArr["ATTRIBUTES"]["SUBFORMS"];
        $this->objectDescription = $xmlArr["ATTRIBUTES"]["DESCRIPTION"];
    }

    /**
     * Set parent form
     * 
     * @param string $formName form name
     * @@return void
     */
    public function setParentForm($formName)
    {
        $this->_parentForm = $formName;
    }

    public function setViewName($viewName)
    {
        $this->viewName = $viewName;
        $this->translate();
    }

    protected function translate()
    {
        $module = substr($this->viewName, 0, intval(strpos($this->viewName, '.')));
        //echo $this->getTransKey('Description');
        $this->objectDescription = I18n::t($this->objectDescription, $this->getTransKey('Description'), $module);
    }

    protected function getTransKey($name)
    {
        $shortViewName = substr($this->viewName, intval(strrpos($this->viewName, '.') + 1));
        return strtoupper($shortViewName . '_' . $this->objectName . '_' . $name);
    }

}
