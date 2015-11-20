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
 * @version   $Id: CheckListbox.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy\Element;

/**
 * Listbox class is element that show ListBox with data from Selection.xml
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class CheckListbox extends OptionElement
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
        $style = $this->getStyle();
        $func = $this->getFunction();
        $valueList = array(); $valueArray = array();
        $this->getFromList($valueList, $this->getSelectedList());
        foreach ($valueList as $vl) {
            $valueArray[] = $vl['val'];
        }

        $sHTML = "<div name=\"" . $this->objectName . "\" ID=\"" . $this->objectName ."\" $this->htmlAttr $style>";

        foreach ($fromList as $option)
        {
            $test = array_search($option['val'], $valueArray);
            if ($test === false)
            {
                $selectedStr = '';
            }
            else
            {
                $selectedStr = "CHECKED";
            }
            $sHTML .= "<input type=\"checkbox\" name=\"".$this->objectName."[]\" VALUE=\"" . $option['val'] . "\" $selectedStr></input>" . $option['txt'] . "<br/>";
        }
        $sHTML .= "</div>";
        return $sHTML;
    }
}

?>