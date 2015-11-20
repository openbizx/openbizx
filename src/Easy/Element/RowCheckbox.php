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
 * @version   $Id: RowCheckbox.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;
use Openbizx\Easy\Element\InputElement;

/**
 * RowCheckbox class is input element for render RowCheckbox
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class RowCheckbox extends InputElement
{
	protected  $checkStatus;
	
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->checkStatus = isset($xmlArr["ATTRIBUTES"]["CHECKSTATUS"]) ? $xmlArr["ATTRIBUTES"]["CHECKSTATUS"] : null;
    }
    /**
     * Render label
     *
     * @return string HTML text
     */
    public function renderLabel()
    {
        $formName = $this->formName;
        $name = $this->objectName.'[]';        
        $sHTML = "<INPUT TYPE=\"CHECKBOX\"  onclick=\"Openbizx.Util.checkAll(this, $('$formName').select('input[name=\'$name\']'));\"/>";
        return $sHTML;
    }

    /**
     * Render, draw the element according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $value = $this->value;
        $name = $this->objectName.'[]';
        $style = $this->getStyle();
        if($this->checkStatus)
        {
        	$formObj = $this->getFormObj();
        	$testResult = Expression::evaluateExpression($this->checkStatus, $formObj);        	
        	if($testResult)
        	{
        		$checkStatus = " checked=\"checked\" ";
        	}
        	else
        	{
        		$checkStatus = "";
        	}
        }
        else
        {
        	$checkStatus = "";
        }
        $sHTML = "<INPUT TYPE=\"CHECKBOX\" $checkStatus NAME=\"$name\" VALUE='$value' onclick=\"event.cancelBubble=true;\" $this->htmlAttr $style/>";
        return $sHTML;
    }
}

?>