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

use Openbizx\Openbizx;
//include_once("OptionElement.php");

/**
 * Listbox class is element that show ListBox with data from Selection.xml
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class ColumnListbox extends OptionElement
{
    public $blankOption;

	public function getItemValue($id)
	{
		$valueArr = $this->value;
		return $valueArr[$id];		
	}
	
	public function setValue($value)
	{
		Openbizx::$app->getSessionContext()->loadObjVar($this->getFormObj()->objectName, $this->objectName, $this->value);
		$valueArr = $_POST[$this->objectName];
		if(is_array($valueArr))
		{
			foreach($valueArr as $key=>$value)
			{
				$this->value[$key] = $value;
			}
		}
		Openbizx::$app->getSessionContext()->saveObjVar($this->getFormObj()->objectName, $this->objectName, $this->value);
	}    
    
    /**
     * When render table, it return the table header; when render array, it return the display name
     *
     * @return string HTML text
     */
   	public function renderLabel()
    {
        if ($this->sortable == "Y")
        {
            $rule = $this->objectName;

            $function = $this->formName . ".SortRecord($rule,$this->sortFlag)";
            if($this->sortFlag == "ASC" || $this->sortFlag == "DESC"){
            	$class=" class=\"current\" ";
            }else{
            	$class=" class=\"normal\" ";
            }
            if ($this->sortFlag == "ASC")
            	$span_class = " class=\"sort_up\" ";
            else if ($this->sortFlag == "DESC")
                $span_class = " class=\"sort_down\" ";
            $sHTML = "<a href=javascript:Openbizx.CallFunction('" . $function . "') $class ><span $span_class >" . $this->label ."</span>";            
            $sHTML .= "</a>";
        }
        else
        {
            $sHTML = $this->label;
        }
        return $sHTML;
    }
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
    	$rec = $this->getFormObj()->getActiveRecord();
		$recId = $rec["Id"];    	
    	
        $fromList = array();
        $this->getFromList($fromList);
        $value = $this->getItemValue($recId)!==null?$this->getItemValue($recId):$this->getDefaultValue();
        $valueArray = explode(',', $value);
        
        $disabledStr = ($this->getEnabled() == "N") ? "DISABLED=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();

        //$sHTML = "<SELECT NAME=\"" . $this->objectName . "[]\" ID=\"" . $this->objectName ."\" $disabledStr $this->htmlAttr $style $func>";
        $sHTML = "<SELECT NAME=\"" . $this->objectName . "[$recId]\" ID=\"" . $this->objectName ."\" $disabledStr $this->htmlAttr $style $func>";

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