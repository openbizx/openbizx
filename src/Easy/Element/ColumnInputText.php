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
 * @version   $Id: InputText.php 3313 2011-02-24 04:32:17Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;

/**
 * InputText class is element for input text
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class ColumnInputText extends InputElement
{
	protected function readMetaData(&$xmlArr){
		parent::readMetaData($xmlArr);
		$this->cssClass = isset($xmlArr["ATTRIBUTES"]["CSSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSCLASS"] : "column_input_text";
		$this->cssErrorClass = isset($xmlArr["ATTRIBUTES"]["CSSERRORCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSERRORCLASS"] : $this->cssClass."_error";
		$this->cssFocusClass = isset($xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"] : $this->cssClass."_focus";
	}
	
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
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
    	$rec = $this->getFormObj()->getActiveRecord();
		$recId = $rec["Id"];
		
    	if($this->value!=null){
    		$value = $this->getItemValue($recId);
    	}else{
    		$value = $this->getText();
    	} 
    	
    	if($value==""){
    		$value = $this->getDefaultValue();
    	}
        $disabledStr = ($this->getEnabled() == "N") ? "READONLY=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();
        
        $formobj = $this->GetFormObj();
    	if($formobj->errors[$this->objectName]){
			$func .= "onchange=\"this.className='$this->cssClass'\"";
		}else{
			$func .= "onfocus=\"this.className='$this->cssFocusClass'\" onblur=\"this.className='$this->cssClass'\"";
		}        
        
		
        $sHTML = "<INPUT NAME=\"" . $this->objectName . "[".$recId."]\" ID=\"" . $this->objectName ."\" VALUE=\"" . $value . "\" $disabledStr $this->htmlAttr $style $func />";
        if($this->hint){
        	$sHTML.="<script>        	
        	\$j('#" . $this->objectName . "').tbHinter({
				text: '".$this->hint."'
			});
        	</script>";
        }
        return $sHTML;
    }

}

?>
