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
 * @version   $Id: HTMLButton.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\InputElement;

/**
 * Button class is element for Button
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class HTMLButton extends InputElement
{
    /**
     * Render / draw the element according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $disabledStr = ($this->getEnabled() == "N") ? "DISABLED=\"true\"" : "";
        $func = $this->getFunction();
        $style = $this->getStyle();
        $sHTML .= "<INPUT TYPE=BUTTON NAME='$this->objectName' ID=\"" . $this->objectName ."\" VALUE='$this->text' $disabledStr $this->htmlAttr $func $style />";
        return $sHTML;
    }
}

?>
