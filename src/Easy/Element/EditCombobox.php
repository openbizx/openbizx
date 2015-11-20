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
 * @version   $Id: EditCombobox.php 2871 2010-12-16 08:21:22Z rockys $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;

/**
 * EditCombobox class is element for EditCombobox
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class EditCombobox extends OptionElement
{

    public $blankOption;
    protected $widthInput = "128px";
    protected $onchange = "";

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->blankOption = isset($xmlArr["ATTRIBUTES"]["BLANKOPTION"]) ? $xmlArr["ATTRIBUTES"]["BLANKOPTION"] : null;
    }

    /**
     * Get style of element
     *
     * @return string style of Element
     */
    protected function getStyle()
    {
        $htmlClass = $this->cssClass ? "class='" . $this->cssClass . "' " : "class='editcombobox'";
        /*
          $width = $this->width ? $this->width : 146;
          $this->widthInput = ($width-18).'px';
          $this->width = $width.'px';
          $style = "position: absolute; width: $this->width; z-index: 1; clip: rect(auto, auto, auto, $this->widthInput);";
         */
        if ($this->style)
            $style .= $this->style;
        if (!isset($style) && !$htmlClass)
            return null;
        if (isset($style)) {
            $formObj = $this->getFormObj();
            $style = Expression::evaluateExpression($style, $formObj);
            $style = "style='$style'";
        }
        if ($htmlClass) {
            $style = $htmlClass . " " . $style;
        }
        return $style;
    }

    /**
     * Render element, according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $fromList = array();
        $this->getFromList($fromList);
        $valueArr = explode(',', $this->value);
        $disabledStr = ($this->getEnabled() == "N") ? "disabled=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();
        $selName = $this->objectName . "_sel";
        //$onchange = "onchange=\"$('$inputName').value=this.value;".$this->onchange."\"";
        $onChange = "onchange=\"$('$this->objectName').value=this.value; $('$this->objectName').triggerEvent('change');\" $func";

        $sHTML = "<div $style>\n";
        $sHTML .= "<select name=\"" . $selName . "\" id=\"" . $selName . "\" $disabledStr $this->htmlAttr $onChange>\n";

        if ($this->blankOption) { // ADD a blank option
            $entry = explode(",", $this->blankOption);
            $text = $entry[0];
            $value = ($entry[1] != "") ? $entry[1] : null;
            $entryList = array(array("val" => $value, "txt" => $text));
            $fromList = array_merge($entryList, $fromList);
        }

        foreach ($fromList as $opt) {
            $test = array_search($opt['val'], $valueArr);
            if ($test === false) {
                $selectedStr = '';
            } else {
                $selectedStr = "selected";
                $selVal = $opt['val'];
            }
            $sHTML .= "<option value=\"" . $opt['val'] . "\" $selectedStr>" . $opt['txt'] . "</option>\n";
        }

        if (!$selVal)
            $selVal = $this->value ? $this->value : $this->getDefaultValue();

        $sHTML .= "</select>\n";
        $sHTML .= "<div><input id=\"$this->objectName\" name=\"$this->objectName\" type=\"text\" value=\"$selVal\" $func/></div>\n";
        $sHTML .= "</div>\n";

        return $sHTML;
    }

}

?>