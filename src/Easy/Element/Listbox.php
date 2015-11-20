<?PHP
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
 * @version   $Id: Listbox.php 3660 2011-04-11 10:16:41Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\OptionElement;

/**
 * Listbox class is element that show ListBox with data from Selection.xml
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class Listbox extends OptionElement
{
    public $blankOption;

    /**
     * Read metadata info from metadata array and store to class variable
     *
     * @param array $xmlArr metadata array
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->blankOption = isset($xmlArr["ATTRIBUTES"]["BLANKOPTION"]) ? $xmlArr["ATTRIBUTES"]["BLANKOPTION"] : null;
    }

    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $fromList = array();
        $this->getFromList($fromList);
        $value = $this->getValue()!==null?$this->getValue():$this->getDefaultValue();
        $valueArray = explode(',', $value);
        
        $disabledStr = ($this->getEnabled() == "N") ? "DISABLED=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();

        //$sHTML = "<SELECT NAME=\"" . $this->objectName . "[]\" ID=\"" . $this->objectName ."\" $disabledStr $this->htmlAttr $style $func>";
        $sHTML = "<SELECT NAME=\"" . $this->objectName . "\" ID=\"" . $this->objectName ."\" $disabledStr $this->htmlAttr $style $func>";

        if ($this->blankOption) // ADD a blank option
        {
            $entry = explode(",",$this->blankOption);
            $text = $entry[0];
            $value = ($entry[1]!= "") ? $entry[1] : null;
            $entryList = array(array("val" => $value, "txt" => $text ));
            $fromList = array_merge($entryList, $fromList);
        }

        $defaultValue = null;
        foreach ($fromList as $option)
        {
            $test = array_search($option['val'], $valueArray);
            if ($test === false)
            {
                $selectedStr = '';
            }
            else
            {
                $selectedStr = "SELECTED";
                $defaultValue = $option['val'];                
            }
            $sHTML .= "<OPTION VALUE=\"" . $option['val'] . "\" $selectedStr>" . $option['txt'] . "</OPTION>";
        }
        if($defaultValue == null){
        	$defaultOpt = array_shift($fromList);
        	$defaultValue = $defaultOpt['val'];
        	array_unshift($fromList,$defaultOpt);
        }
     
        
        $this->setValue($defaultValue);
        $sHTML .= "</SELECT>";
        return $sHTML;
    }
}

?>