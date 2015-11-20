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
 * @version   $Id: Hidden.php 2912 2010-12-17 18:30:31Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\Element;
/**
 * Button class is hidden element
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class Hidden extends Element
{
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
    }

    /**
     * Draw the element according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        if($this->value!=null){
    		$value = $this->value;
    	}else{
    		$value = $this->getText();
    	} 
        
    	$sHTML = "<INPUT TYPE=HIDDEN NAME='$this->objectName' ID=\"" . $this->objectName ."\" VALUE='$value' $this->htmlAttr />";
        return $sHTML;
    }

}

?>
