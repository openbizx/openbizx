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
 * @version   $Id: LabelList.php 543 2009-10-03 08:50:00Z mr_a_ton$
 */

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;
use Openbizx\Easy\Element\Element;

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
class HTMLPreview extends Element
{
    public $fieldName;
    public $label;
    public $displayFormat;
    public $text;
    public $link;    
    public $target;
    public $maxLength;
    public $percent;
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
        $this->text = isset($xmlArr["ATTRIBUTES"]["TEXT"]) ? $xmlArr["ATTRIBUTES"]["TEXT"] : null;
        $this->link = isset($xmlArr["ATTRIBUTES"]["LINK"]) ? $xmlArr["ATTRIBUTES"]["LINK"] : null;
        $this->target = isset($xmlArr["ATTRIBUTES"]["TARGET"]) ? $xmlArr["ATTRIBUTES"]["TARGET"] : null;
        $this->maxLength = isset($xmlArr["ATTRIBUTES"]["MAXLENGHT"]) ? $xmlArr["ATTRIBUTES"]["MAXLENGHT"] : null;
        $this->maxLength = isset($xmlArr["ATTRIBUTES"]["MAXLENGTH"]) ? $xmlArr["ATTRIBUTES"]["MAXLENGTH"] : null;
        $this->percent = isset($xmlArr["ATTRIBUTES"]["PERCENT"]) ? $xmlArr["ATTRIBUTES"]["PERCENT"] : "N";
        $this->displayFormat = isset($xmlArr["ATTRIBUTES"]["DISPLAYFORMAT"]) ? $xmlArr["ATTRIBUTES"]["DISPLAYFORMAT"] : null;
    }

    /**
     * Get target of link
     * <a target='...'>...</a>
     *
     * @return string
     */
    protected function getTarget()
    {
        if ($this->target == null)
            return null;

        return "target='" . $this->target ."'";
        ;
    }

    /**
     * Get link of LabelText
     *
     * @return string
     */
    protected function getLink()
    {
        if ($this->link == null)
            return null;
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->link, $formobj);
    }

    /**
     * Get text of label
     *
     * @return string
     */
    protected function getText()
    {
        if ($this->text == null)
            return null;   
        $formObj = $this->getFormObj();
        return Expression::evaluateExpression($this->text, $formObj);
    }
    
    /**
     * Render label
     *
     * @return string HTML text
     */
    public function renderLabel()
    {
        return $this->label;
    }

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
        $value_org = strip_tags($value);
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
        if($this->height)
        {
        	$height = $this->height.'px';
        }
        if ($value!=null)
        {
        	$header = "
        	<head><link href=\"".OPENBIZ_JS_URL."/ckeditor/contents.css\" rel=\"stylesheet\" type=\"text/css\"></head>
        	";
           $sHTML = "
           			<script>".$id."_data=".json_encode($header.$value)."</script>
           			<iframe border=\"0\" frameborder=\"0\" allowtransparency=\"true\"
           				tabIndex=\"-1\" style=\"width:100%;height:$height;background: none repeat scroll 0 0 transparent;border: 0 none;border-collapse: collapse;\"
           				src=\"javascript:setTimeout(%20function()%7Bdocument.open()%3Bdocument.write(%20window.parent%5B%20%22".$id."_data%22%20%5D%20)%3Bdocument.close()%3Bwindow.parent%5B%20%22".$id."_data%22%20%5D%20%3D%20null%3B%7D%2C%20200%20)\">
           			</iframe>
           			";
            
        }

        return $sHTML;
    }

}

?>