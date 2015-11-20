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
 * @version   $Id: RawData.php 3437 2011-03-08 16:40:19Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;
use Openbizx\Easy\Element\Element;

/**
 * RawData class is element for render raw data
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class RawData extends Element
{

    public $fieldName;
    public $label;
    public $text;
    public $link;
    public $unSerialize;

    /**
     * Read metadata info from metadata array and store to class variable
     *
     * @param array $xmlArr metadata array
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->fieldName = isset($xmlArr["ATTRIBUTES"]["FIELDNAME"]) ? $xmlArr["ATTRIBUTES"]["FIELDNAME"] : null;
        $this->label = isset($xmlArr["ATTRIBUTES"]["LABEL"]) ? $xmlArr["ATTRIBUTES"]["LABEL"] : null;
        $this->text = isset($xmlArr["ATTRIBUTES"]["TEXT"]) ? $xmlArr["ATTRIBUTES"]["TEXT"] : null;
        $this->link = isset($xmlArr["ATTRIBUTES"]["LINK"]) ? $xmlArr["ATTRIBUTES"]["LINK"] : null;
        $this->unSerialize = isset($xmlArr["ATTRIBUTES"]["UNSERIALIZE"]) ? $xmlArr["ATTRIBUTES"]["UNSERIALIZE"] : null;
    }

    /**
     * Get link of element
     *
     * @return string
     */
    protected function getLink()
    {
        if ($this->link == null) {
            return null;
        }
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->link, $formobj);
    }

    /**
     * Render label, just return elemen label
     *
     * @return string HTML text
     */
    public function renderLabel()
    {
        return $this->label;
    }

    /**
     * Get text of element
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
     * Render, draw the element according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $value = $this->text ? $this->getText() : $this->value;
        if ($value === null || $value == "")
            return $value;

        if ($this->unSerialize == "Y") {
            $value = unserialize($value);
        }

        if ($this->translatable == 'Y') {
            if (is_array($value)) {
                foreach ($value as $key => $value) {
                    $value[$key] = $value = $this->translateString($value);
                }
            } else {
                $value = $this->translateString($value);
            }
        }
        return $value;
    }

}

?>