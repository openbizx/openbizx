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
 * @version   $Id: ColumnText.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy\Element;

/**
 * ColumnText class is element for ColumnText,
 * show text on data list
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class ColumnText extends LabelText
{
    public $sortable;
    public $columnStyle;    

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->sortable = isset($xmlArr["ATTRIBUTES"]["SORTABLE"]) ? $xmlArr["ATTRIBUTES"]["SORTABLE"] : null;        
        $this->columnStyle = $this->style;
    }

    /**
     * Set the sort flag of the element
     *
     * @param integer $flag 1 or 0
     * @return void
     */
    public function setSortFlag($flag=null)
    {
        $this->sortFlag = $flag;
    }

    /**
     * Render label,
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
    
    public function matchRemoteMethod($method)
    {
        return ($this->sortable == "Y" && $method == "sortrecord");
    }
}
?>