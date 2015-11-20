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
 * @version   $Id: RichText.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Easy\Element\InputElement;

/**
 * RichText class is input element for render RichText editor
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class RichText extends InputElement
{

    /**
     * Render, draw the element according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        Openbizx::$app->getClientProxy()->includeRTEScripts();
        
        $elementName = $this->objectName;
        $elementNameAndContainer = $elementName."_container";
        $value = $this->getValue();
        $style = $this->getStyle();
        $width = $this->width ? $this->width : 600;
        $height = $this->height ? $this->height : 300;
        //$func = "onclick=\"editRichText('$elementName', $width, $height);\"";
        if(!strlen($value)>0) // fix suggested by smarques
            $value="&nbsp;";
        $sHTML = "<DIV id='$elementNameAndContainer' $style $func>".$value."</DIV>\n";
        $sHTML .= "<input type='hidden' id='hdn$elementName' name='$elementName' value=\"".$value."\" />"."\n";
        //$sHTML .= "<textarea rows=2 cols=20 id='hdn$elementName' name='$elementName'>".$value."</textarea>\n";
        $sHTML .= "<script>editRichText('$elementName', $width, $height);</script>";
        return $sHTML;
    }

}

?>
