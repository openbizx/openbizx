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
 * @version   $Id: Textarea.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\OptionElement;

/**
 * Textarea class is element for render html Textarea
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class Textarea extends OptionElement
{
	public $blankOption;
	
	public function readMetaData(&$xmlArr){
		parent::readMetaData($xmlArr);
		$this->cssClass = isset($xmlArr["ATTRIBUTES"]["CSSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSCLASS"] : "input_textarea";
		$this->cssErrorClass = isset($xmlArr["ATTRIBUTES"]["CSSERRORCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSERRORCLASS"] : "input_textarea_error";
		$this->cssFocusClass = isset($xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"] : "input_textarea_focus";
		$this->blankOption = isset($xmlArr["ATTRIBUTES"]["BLANKOPTION"]) ? $xmlArr["ATTRIBUTES"]["BLANKOPTION"] : null;
	}
   /**
    * Render, draw the element according to the mode
    *
    * @return string HTML text
    */
    public function render()
    {

        
        $disabledStr = ($this->getEnabled() == "N") ? "DISABLED=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction(); 
    	if($formobj->errors[$this->objectName]){
			$func .= "onchange=\"this.className='$this->cssClass'\"";
		}else{
			$func .= "onfocus=\"this.className='$this->cssFocusClass'\" onblur=\"this.className='$this->cssClass'\"";
		}        
        $sHTML .= "<TEXTAREA NAME=\"" . $this->objectName . "\" ID=\"" . $this->objectName ."\" $disabledStr $this->htmlAttr $style $func>".$this->value."</TEXTAREA>";        
    	
        if($this->selectFrom){
        	$fromList = array();
	        $this->getFromList($fromList);
	        $valueArray = explode(',', $this->value);
	        $sHTML .= "<UL ID=\"" . $this->objectName ."_suggestion\" class=\"input_textarea_suggestion\" >";
	        if ($this->blankOption) // ADD a blank option
	        {
	            $entry = explode(",",$this->blankOption);
	            $text = $entry[0];
	            $value = ($entry[1]!= "") ? $entry[1] : null;
	            $entryList = array("val" => $value, "txt" => $text );
	            $sHTML .= "<LI><H3>".$entryList['txt']."</H3></LI>";
	        }
	        
	        foreach ($fromList as $option)
	        {            
	            $sHTML .= "<LI><A href=\"javascript:;\" onclick=\"$('".$this->objectName."').value+='".$option['val']."'\" >".$option['txt']."</A></LI>";        	
	        }
	        $sHTML .= "</UL>";
        }
        return $sHTML;
    }

    
}

?>
