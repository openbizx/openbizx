<?php 
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.easy.element
 * @copyright Copyright &copy; 2005-2010, Jixian
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: LabelBool.php 3640 2011-04-11 03:18:17Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\Easy\Element\LabelText;

//include_once("LabelText.php");

class LabelBool extends LabelText{
    public $trueImg;
    public $falseImg;
    public $trueValue;
    public $falseValue;

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->trueImg=isset($xmlArr["ATTRIBUTES"]["TRUEIMG"])?$xmlArr["ATTRIBUTES"]["TRUEIMG"]:"flag_y.gif";
        $this->falseImg=isset($xmlArr["ATTRIBUTES"]["FALSEIMG"])?$xmlArr["ATTRIBUTES"]["FALSEIMG"]:"flag_n.gif";
        $this->trueValue=isset($xmlArr["ATTRIBUTES"]["TRUEVALUE"])?$xmlArr["ATTRIBUTES"]["TRUEVALUE"]:true;
        $this->falseValue=isset($xmlArr["ATTRIBUTES"]["FLASEVALUE"])?$xmlArr["ATTRIBUTES"]["FLASEVALUE"]:false;
    }
    /**
     * Render element, according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
    	if(!$this->trueImg)
    	{
    		$this->trueImg = "flag_y.gif";
    	}
    	if(!$this->falseImg)
    	{
    		$this->falseImg = "flag_n.gif";
    	}
        $val=$this->value;
        $style = $this->getStyle();
        $text = $this->getText();
        $id = $this->objectName;
        $func = $this->getFunction();
        if($val==='1' || $val==='true' || strtoupper($val) == 'Y' || $val>0 || $val==$this->trueValue)
    	{
        	$image_url  = $this->trueImg;            
        }
        else
        {
        	$image_url  = $this->falseImg;            
        }   
        if(preg_match("/\{.*\}/si",$image_url))
        {
        	$formobj = $this->getFormObj();
        	$image_url =  Expression::evaluateExpression($image_url, $formobj);
        }else{
        	$image_url = Openbizx::$app->getImageUrl()."/".$image_url;
        }                   
    	if ($this->link)
        {
            $link = $this->getLink();
            $target = $this->getTarget();
            $sHTML = "<a alt=\"".$text."\" title=\"".$text."\" id=\"$id\" href=\"$link\" $target $func $style><img src='$image_url' /></a>";
        }else{
        	$sHTML = "<span id=\"$id\"  ><img style=\"padding-top:2px;\" alt=\"".$text."\" title=\"".$text."\" src='$image_url' /></span>";
        }
        return $sHTML;
    }    	
}

?>