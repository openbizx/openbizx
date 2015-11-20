<?php

/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.easy.element
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: InputElement.php 3536 2011-03-28 19:13:05Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\Easy\Element\Element;

/**
 * InputElement class is based element for all input element
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class InputElement extends Element
{

    public $fieldName;
    public $label;
    public $objectDescription;
    public $defaultValue = "";
    public $defaultValueRename = "Y";
    public $required = "N";
    public $enabled = "Y";      // support expression
    public $text;
    public $hint;
    public $link;

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->fieldName = isset($xmlArr["ATTRIBUTES"]["FIELDNAME"]) ? $xmlArr["ATTRIBUTES"]["FIELDNAME"] : null;
        $this->label = isset($xmlArr["ATTRIBUTES"]["LABEL"]) ? $xmlArr["ATTRIBUTES"]["LABEL"] : null;
        $this->objectDescription = isset($xmlArr["ATTRIBUTES"]["DESCRIPTION"]) ? $xmlArr["ATTRIBUTES"]["DESCRIPTION"] : null;
        $this->defaultValue = isset($xmlArr["ATTRIBUTES"]["DEFAULTVALUE"]) ? $xmlArr["ATTRIBUTES"]["DEFAULTVALUE"] : null;
        $this->defaultValueRename = isset($xmlArr["ATTRIBUTES"]["DEFAULTVALUERENAME"]) ? $xmlArr["ATTRIBUTES"]["DEFAULTVALUERENAME"] : "Y";
        $this->required = isset($xmlArr["ATTRIBUTES"]["REQUIRED"]) ? $xmlArr["ATTRIBUTES"]["REQUIRED"] : null;
        $this->enabled = isset($xmlArr["ATTRIBUTES"]["ENABLED"]) ? $xmlArr["ATTRIBUTES"]["ENABLED"] : null;
        $this->text = isset($xmlArr["ATTRIBUTES"]["TEXT"]) ? $xmlArr["ATTRIBUTES"]["TEXT"] : null;

        $this->hint = isset($xmlArr["ATTRIBUTES"]["HINT"]) ? $xmlArr["ATTRIBUTES"]["HINT"] : null;

        // if no class name, add default class name. i.e. NewRecord => ObjName.NewRecord
        // tmp_remark
        //$this->valuePicker = $this->prefixPackage($this->valuePicker);
    }

    /**
     * Get enable status
     *
     * @return string
     */
    protected function getEnabled()
    {
        $formObj = $this->getFormObj();
        return Expression::evaluateExpression($this->enabled, $formObj);
    }

    protected function getRequired()
    {
        $formObj = $this->getFormObj();
        return Expression::evaluateExpression($this->required, $formObj);
    }

    public function getValue()
    {
        $value = parent::getValue();
        if ($value == $this->hint) {
            $this->value = null;
            return null;
        }
        return $value;
    }

    /**
     * Render label, just return label value
     *
     * @return string
     */
    public function renderLabel()
    {
        $sHTML = $this->translateString($this->label);
        return $sHTML;
    }

    /**
     * Render, draw the element according to the mode
     * just return element value
     *
     * @return string HTML text
     */
    public function render()
    {
        return $this->value;
    }

    /**
     * Add sort-cut key scripts
     *
     * @return string
     */
    protected function addSCKeyScript()
    {
        $keyMap = $this->getSCKeyFuncMap();
        if (count($keyMap) == 0) {
            return "";
        }
        Openbizx::$app->getClientProxy()->appendScripts("shortcut", "shortcut.js");
        $str = "<script>\n";
        $formObj = $this->getFormObj();

        if (!$formObj->removeall_sck) {
            $str .= " shortcut.removeall(); \n";
            $formObj->removeall_sck = true;
        }

        foreach ($keyMap as $key => $func) {
            $str .= " shortcut.remove(\"$key\"); \n";
        }

        $str .= " shortcut.add(\"$key\",function() { $func }); \n";
        $str .= "</script>\n";
        return $str;
    }

}

?>
