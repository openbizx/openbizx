<?php


namespace Openbizx\Easy;

/**
 * TabView class is internal class mapping to the metadata of View element in HTMLTabs
 *
 * @package openbiz.bin.easy
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @since 1.2
 * @access public
 */
class TabView
{

    public $objectName;
    public $view;
    public $viewSet;
    public $caption;
    public $url;
    public $target;
    public $icon;
    public $forms; //Forms for hide or show in a BizView

    /**
     * Get forms or the form to hide or show.
     * When It has one form It hasn't the ATTRIBUTES property
     *
     * @param array $forms
     * @return array
     * */

    private function _getForms($forms)
    {
        $recArr = array();
        if (count($forms) == 0)
            return $recArr;

        foreach ($forms as $form) {
            if (!is_null($form["ATTRIBUTES"]))
                $recArr[] = $form["ATTRIBUTES"];
            else
                $recArr[] = $form;
        }
        return $recArr;
    }

    /**
     * Initialize TabView with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->objectName = $xmlArr["ATTRIBUTES"]["NAME"];
        $this->view = $xmlArr["ATTRIBUTES"]["VIEW"];
        if (isset($xmlArr["ATTRIBUTES"]["VIEWSET"])) {
            $this->viewSet = $xmlArr["ATTRIBUTES"]["VIEWSET"];
        }
        $this->caption = $this->translate($xmlArr["ATTRIBUTES"]["CAPTION"]);
        if ($xmlArr["ATTRIBUTES"]["URL"]) {
            $this->url = $xmlArr["ATTRIBUTES"]["URL"];
        }
        if (isset($xmlArr["ATTRIBUTES"]["TARGET"])) {
            $this->target = $xmlArr["ATTRIBUTES"]["TARGET"];
        }
        if (isset($xmlArr["ATTRIBUTES"]["ICON"])) {
            $this->icon = $xmlArr["ATTRIBUTES"]["ICON"];
        }


        $this->forms = NULL;
        if (isset($xmlArr["FORM"])) {
            $this->forms = $this->_getForms($xmlArr["FORM"]);
        }
        //Get form or forms to hide or show
        //$this->forms = (!is_null($xmlArr["FORM"]))?$this->getForms($xmlArr["FORM"]):null;
    }

    /**
     * Return TRUE if the current tabView has forms related to it
     *
     * @return bool
     */
    function hasForms()
    {
        return (bool) $this->forms;
    }

    protected function translate($caption)
    {
        $module = $this->getModuleName($this->objectName);
        return I18n::t($caption, $this->getTransKey(caption), $module);
    }

    protected function getTransKey($name)
    {
        $shortFormName = substr($this->objectName, intval(strrpos($this->objectName, '.')) + 1);
        return strtoupper($shortFormName . '_' . $name);
    }

}
