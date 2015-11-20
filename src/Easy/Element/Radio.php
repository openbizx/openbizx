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
 * @version   $Id: Radio.php 3671 2011-04-12 06:30:49Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;
use Openbizx\Easy\Element\OptionElement;

/**
 * Radio class is element that show RadioBox with data from Selection.xml
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class Radio extends OptionElement
{
   
    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $fromList = array();
        $this->getFromList($fromList);
        $disabledStr = ($this->getEnabled() == "N") ? "DISABLED=\"true\"" : "";
        if(!$this->style){
        	$this->style.="margin-right:8px;";
        }
        $style = $this->getStyle();
        $func = $this->getFunction();

        $value = $this->getValue()!='null' ? $this->getValue() : $this->getDefaultValue();
        
        $value = $value===null?$this->getDefaultValue():$value;
        
        if($this->width){
        	
        	$width = (int)$this->width."px;";
        }else{
        	$width = "auto;";
        }
        foreach ($fromList as $option) {        	
            $checkedStr = ($option['val'] == $value) ? "CHECKED" : "";            
            if($option['pic'])
            {
            	$option_display = "<img src=\"".Expression::evaluateExpression($option['pic'],$this->getFormObj())."\" />";
            }
            else 
            {
            	$option_display = $option['txt'];
            }
            $sHTML .= "<label style=\"text-align:left;width:$width\" class=\"radio_option\"><INPUT TYPE=RADIO NAME='".$this->objectName."' ID=\"" . $this->objectName ."_".$option['val']."\" VALUE=\"" . $option['val'] . "\" $checkedStr $disabledStr $style $this->htmlAttr $func />" . $option_display . "</label>";
        }
        
        return $sHTML;
    }
    
    public function getStyle()
    {
    	 
		$formobj = $this->getFormObj();    	
        $htmlClass = Expression::evaluateExpression($this->cssClass, $formobj);
        $htmlClass = "CLASS='$htmlClass'";
        if(!$htmlClass){
        	$htmlClass = null;
        }
        $style ='';
         
        if ($this->height && $this->height>=0)
            $style .= "height:".$this->height."px;";
        if ($this->style)
            $style .= $this->style;
        if (!isset($style) && !$htmlClass)
            return null;
        if (isset($style))
        {
            
            $style = Expression::evaluateExpression($style, $formobj);
            $style = "STYLE='$style'";
        }
        if($formobj->errors[$this->objectName])
        {
      	    $htmlClass = "CLASS='".$this->cssErrorClass."'";
        }
        if ($htmlClass)
            $style = $htmlClass." ".$style;
        return $style;
    	
    }
}

