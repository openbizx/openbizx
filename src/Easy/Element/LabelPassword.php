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

use Openbizx\Easy\Element\LabelText;

/**
 * LabelPassword class is element that show text with mask (for password)
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2009
 * @version 1.0
 * @access public
 */
class LabelPassword extends LabelText
{
    public $sortable;
    public $columnStyle;
    public $maskChar;
    public $maskLength;

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->maskChar = isset($xmlArr["ATTRIBUTES"]["MASKCHAR"]) ? $xmlArr["ATTRIBUTES"]["MASKCHAR"] : "*";
        $this->maskLength = isset($xmlArr["ATTRIBUTES"]["MASKLENGTH"]) ? $xmlArr["ATTRIBUTES"]["MASKLENGTH"] : 6;
    }

    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $sHTML = "<span $style $func> ".str_repeat($this->maskChar, $this->maskLength)." </span>";
        return $sHTML;
    }

}

?>