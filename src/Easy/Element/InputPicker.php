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
 * @version   $Id: InputPicker.php 3984 2011-04-28 02:54:31Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\InputText;

/**
 * InputPicker class is element for input picker
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class InputPicker extends InputText
{

    public $valuePicker;
    public $pickerMap;
    public $updateForm;

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    public function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        // if no class name, add default class name. i.e. NewRecord => ObjName.NewRecord
        $this->valuePicker = isset($xmlArr["ATTRIBUTES"]["VALUEPICKER"]) ? $xmlArr["ATTRIBUTES"]["VALUEPICKER"] : null;
        $this->valuePicker = $this->prefixPackage($this->valuePicker);
        $this->pickerMap = isset($xmlArr["ATTRIBUTES"]["PICKERMAP"]) ? $xmlArr["ATTRIBUTES"]["PICKERMAP"] : null;
        $this->updateForm = isset($xmlArr["ATTRIBUTES"]["UPDATEFORM"]) ? $xmlArr["ATTRIBUTES"]["UPDATEFORM"] : "N";
    }

    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $this->defaultValueRename = "N";
        $this->enabled = 'N';
        $sHTML = parent::render();

        // sample picker call CallFunction('easy.f_AttendeeListChild.LoadPicker(view,form,elem)','Prop_Window');
        if ($this->valuePicker != null) {
            $function = $this->formName . ".LoadPicker($this->valuePicker,$this->objectName)";
            $sHTML .= " <input type=button onClick=\"Openbizx.CallFunction('$function');\" value=\"...\" style='width:20px;' />";
        }

        return $sHTML;
    }

    public function getEvents()
    {
        $events = parent::getEvents();
        $events['onclick'] .= "Openbizx.CallFunction('" . $this->formName . ".LoadPicker($this->valuePicker,$this->objectName)')";
        return $events;
    }

    public function matchRemoteMethod($method)
    {
        return ($method == "loadpicker");
    }

}

