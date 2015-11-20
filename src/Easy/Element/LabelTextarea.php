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
 * @version   $Id: LabelList.php 543 2009-10-03 08:50:00Z mr_a_ton$
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Easy\Element\LabelText;


/**
 * LebelText - class LabelText is element that view value who binds
 * with a BizField
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2009
 * @version 1.0
 * @access public
 */
class LabelTextarea extends LabelText
{
    /**
     * Render, draw the element according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
       $value = $this->text ? $this->getText() : $this->value;
        
        if ($value == null || $value =="")
            return "";

        $style = $this->getStyle();
        $id = $this->objectName;
        $func = $this->getFunction();

        if ($this->translatable == 'Y')
            $value = $this->translateString($value);
        
        if((int)$this->maxLength>0){
	        if(function_exists('mb_strlen') && function_exists('mb_substr')){
	        	if(mb_strlen($value,'UTF8') > (int)$this->maxLength){
	        		$value = mb_substr($value,0,(int)$this->maxLength,'UTF8').'...';
	        	}        	
	        }else{
	        	if(strlen($value) > (int)$this->maxLength){
	        		$value = substr($value,0,(int)$this->maxLength).'...';
	        	}         	
	        }
        }
        /*
         * it is important converting not just nl2br
         *  
         */
               
        //$value = htmlentities($value);
        
        $value = htmlentities($value, ENT_QUOTES, "UTF-8");
        $value = str_replace("\n\n","\n<img src=\"".Openbizx::$app->getImageUrl()."/spacer.gif\" style=\"display:block;height:10px;\">",$value);
        $value = nl2br($value);
        
        if ($value!=null)
        {
        	if($this->displayFormat)
        	{
        		$value = sprintf($this->displayFormat,$value);
        	}
        	if($this->percent=='Y')
        	{
        		$value = sprintf("%.2f",$value*100).'%';
        	}
        	
            if ($this->link)
            {
                $link = $this->getLink();
                $target = $this->getTarget();
                //$sHTML = "<a href=\"$link\" onclick=\"SetOnLoadNewView();\" $style>" . $val . "</a>";
                $sHTML = "<a id=\"$id\" href=\"$link\" $target $func $style>" . $value . "</a>";
            }
            else
            {
                $sHTML = "<span $style $func>" . $value . "</span>";
            }
        }

        return $sHTML;    }
    


}
