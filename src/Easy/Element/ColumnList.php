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
 * @version   $Id: ColumnList.php 3347 2011-02-27 18:46:26Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;

//include_once("OptionElement.php");

/**
 * ColumnList class is element that show description from "Selection.xml" on column/table view
 *
 * @package openbiz.bin.easy.element
 * @author Agus Suhartono, Rocky Swen (original ListBox and ColumnText author)
 * @copyright Copyright (c) 2009
 * @access public
 */
class ColumnList extends OptionElement
{
    public $sortable;
    public $columnStyle;

    /**
     * Read metadata info from metadata array and store to class variable
     *
     * @param array $xmlArr metadata array
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->sortable = isset($xmlArr["ATTRIBUTES"]["SORTABLE"]) ? $xmlArr["ATTRIBUTES"]["SORTABLE"] : null;
        $this->link = isset($xmlArr["ATTRIBUTES"]["LINK"]) ? $xmlArr["ATTRIBUTES"]["LINK"] : null;
        $this->columnStyle = $this->style;
    }

    /**
     * set the sort flag of the element
     *
     * @param integer $flag 1 or 0
     * @return void
     */
    public function setSortFlag($flag=null)
    {
        $this->sortFlag = $flag;
    }

    /**
     * Get link that evaluated by Expression::evaluateExpression
     *
     * @return string link
     */
    protected function getLink()
    {
        if ($this->link == null)
            return null;
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->link, $formobj);
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
     * Draw/Render the element to show description
     *
     * @return string HTML text
     */
    public function render()
    {
        $fromList   = array();
        $this->getFromList($fromList);
        $value_arr  = explode(',', $this->value);
        $style      = $this->getStyle();
        $func       = $this->getFunction();

        $selectedStr = '';

        $selectedStr = $this->value;

        foreach ($fromList as $opt)
        {
            $test = array_search($opt['val'], $value_arr);
            if (!($test === false))
            {
                $selectedStr = $opt['txt'] ;
                break;
            }
        }

        if ($this->link)
        {
            $link = $this->getLink();
            $sHTML = "<a href=\"$link\" $func $style>" . $selectedStr . "</a>";
        }
        else
            $sHTML = "<span $func $style>" . $selectedStr . "</span>";

        return $sHTML;
    }
}

?>
