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
 * @version   $Id: PageSelector.php 2711 2010-11-30 18:00:24Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\DropDownList;

/**
 * InputText class is element for input text
 *
 * @package openbiz.bin.easy.element
 * @author Jixian W.
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class PageSelector extends DropDownList
{

	//protected $formPrefix = true;
    public function getList(){
    	$formobj=$this->getFormObj();
    	$list=array();
    	for($i=1;$i<=$formobj->totalPages;$i++){
    		array_push($list,array("val"=>$i,"txt"=>$i));
    	}
    	return $list;
    }

}

?>