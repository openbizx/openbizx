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
 * @version   $Id: DropDownList.php 3856 2011-04-21 18:10:41Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\I18n\I18n;
use Openbizx\Data\Helpers\QueryStringParam;
use Openbizx\Object\ObjectFactoryHelper;

include_once("InputElement.php");

/**
 * InputText class is element for input text
 *
 * @package openbiz.bin.easy.element
 * @author Jixian W.
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class DropDownList extends InputElement
{
	
	public $readOnly;
	public $defaultDisplayValue;
	public $cssHoverClass;
    public $selectFrom;
    public $selectFromSQL;
    public $selectedList;
    public $blankOption;
	protected $_listCache;
    protected $formPrefix = true;
	
	protected function readMetaData(&$xmlArr){
		parent::readMetaData($xmlArr);
		$this->cssClass = isset($xmlArr["ATTRIBUTES"]["CSSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSCLASS"] : "input_select_w";
		$this->cssErrorClass = isset($xmlArr["ATTRIBUTES"]["CSSERRORCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSERRORCLASS"] : $this->cssClass."_error";
		$this->cssFocusClass = isset($xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"] : $this->cssClass."_focus";
		$this->cssHoverClass = isset($xmlArr["ATTRIBUTES"]["CSSHOVERCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSHOVERCLASS"] : $this->cssClass."_hover";
		//$this->value = isset($xmlArr["ATTRIBUTES"]["DEFAULTVALUE"]) ? $xmlArr["ATTRIBUTES"]["DEFAULTVALUE"] : null;        
		$this->readOnly = isset($xmlArr["ATTRIBUTES"]["READONLY"]) ? $xmlArr["ATTRIBUTES"]["READONLY"] : "N";
        $this->selectFrom = isset($xmlArr["ATTRIBUTES"]["SELECTFROM"]) ? $xmlArr["ATTRIBUTES"]["SELECTFROM"] : null;
        $this->selectedList = isset($xmlArr["ATTRIBUTES"]["SELECTEDLIST"]) ? $xmlArr["ATTRIBUTES"]["SELECTEDLIST"] : null;
        $this->selectFromSQL = isset($xmlArr["ATTRIBUTES"]["SELECTFROMSQL"]) ? $xmlArr["ATTRIBUTES"]["SELECTFROMSQL"] : null;
    	$this->blankOption = isset($xmlArr["ATTRIBUTES"]["BLANKOPTION"]) ? $xmlArr["ATTRIBUTES"]["BLANKOPTION"] : null;
        $this->blankOption = $this->translateString($this->blankOption);
		$this->defaultValueRename = isset($xmlArr["ATTRIBUTES"]["DEFAULTVALUERENAME"]) ? $xmlArr["ATTRIBUTES"]["DEFAULTVALUERENAME"] : "N";  
		
	}
    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
    	if($this->formPrefix){
    		$formNameStr = str_replace(".","_", $this->getFormObj()->objectName)."_";
    	}        
    	if($this->value!=null){
    		$value = $this->value;
    	}else{
    		$value = $this->getText();
    	}        
    	if($value==null){
    		$value = $this->getDefaultValue();
    	}
    	if(preg_match('/\{.*?@.*?\}/si',$value)){
    		$formObj = $this->getFormObj();
    		$value = Expression::evaluateExpression($value, $formObj);
    	}
        $disabledStr = ($this->getEnabled() == "N") ? "READONLY=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();
        $optionList= $this->renderList();
        
        $htmlClass = Expression::evaluateExpression($this->cssClass, $formobj);
        $htmlClass = "CLASS='$htmlClass'";
        
        $sHTML .= "<div class=\"div_".$this->cssClass."\">";    	                
        if($this->readOnly=='Y')
        {
	        $display_input = "style=\"display:none;\"";
	        $display_span = "style=\"position:;\"";
        }
        else
        {
	        $display_span = "style=\"display:none;\"";
	        $display_input = "style=\"position:;\"";
        }
        $list = $this->getList();
        if ($this->blankOption) // ADD a blank option
        {
            $entry = explode(",",$this->blankOption);
            $text = $entry[0];
            $blkvalue = ($entry[1]!= "") ? $entry[1] : null;
            $display_value = $text;
            $entryList = array(array("val" => $blkvalue, "txt" => $text ));
            $list = array_merge($entryList, $list);
        }        
        
        $display_value=$this->getDisplayValue($value);
    	if(!$list){$list=array();}
        foreach ($list as $item){
        	if($item['val']==$value){
        		if(!preg_match("/\</si",$item['txt'])){
        			$display_value = $item['txt'];
        		}else{
        			$display_value = $item['val'];
        		}
        		break;
        	}
        }
        
        if($value==''){
            $defaultOpt = array_shift($list);
            if($display_value==''){
            	$value = $defaultOpt['val'];
        		$display_value = $defaultOpt['txt'];
            }
        	array_unshift($list,$defaultOpt);        	 
        }
                
        $this->setValue($value);
        $display_value = strip_tags($display_value);
		
		$elem_id = $formNameStr.$this->objectName;
		$elem_scroll_id = $formNameStr.$this->objectName."_scroll";
		$elem_list_id = $formNameStr.$this->objectName."_list";
		$elem_hidden_id = $formNameStr.$this->objectName."_hidden";
        
		$onchange_func = $this->getOnChangeFunction();
		$sHTML .= $optionList;
		$sHTML .= "\n<div $display_span>";
		// jquery $j('a.maxmin').click(function() {...});
		// jquery $j('a.maxmin').hover(function() {...}, function() {...});
		if (defined('OPENBIZ_JSLIB_BASE') && OPENBIZ_JSLIB_BASE == 'JQUERY') {
			$sHTML .= "\n<span ID=\"span_$elem_id\"  $this->htmlAttr $style>$display_value</span>\n";
		}
		else {
	        $sHTML .= "<span ID=\"span_$elem_id\"  $this->htmlAttr $style
						onclick=\"if($('$elem_list_id').visible()){\$('$elem_list_id').hide();\$('$elem_scroll_id').hide();$('$elem_id').className='".$this->cssClass."'}else{\$('$elem_list_id').show();\$('$elem_scroll_id').show();$('$elem_id').className='".$this->cssFocusClass."'}\"
						onmouseover=\"$('span_$elem_id').className='".$this->cssHoverClass."'\"
						onmouseout=\"$('span_$elem_id').className='".$this->cssClass."'\"
						>$display_value</span>";
		}
		$sHTML .= "</div>";
		$sHTML .= "<div $display_input>";
		if (defined('OPENBIZ_JSLIB_BASE') && OPENBIZ_JSLIB_BASE == 'JQUERY') {
			$sHTML .= "<INPUT NAME=\"$elem_id\" ID=\"$elem_id\" VALUE=\"" . $display_value . "\" $disabledStr $this->htmlAttr $style />\n";
		}
		else {
			$sHTML .= "<INPUT NAME=\"$elem_id\" ID=\"$elem_id\" VALUE=\"" . $display_value . "\" $disabledStr $this->htmlAttr $style 
						onclick=\"if($('$elem_list_id').visible()){\$('$elem_list_id').hide();\$('$elem_scroll_id').hide();$('$elem_id').className='".$this->cssClass."'}else{\$('$elem_list_id').show();\$('$elem_scroll_id').show();$('$elem_id').className='".$this->cssFocusClass."'}\"
							onmouseover=\"$('span_$elem_id').className='".$this->cssHoverClass."'\"
							onmouseout=\"$('span_$elem_id').className='".$this->cssClass."'\"
						/>";
		}
		$sHTML .= "<INPUT NAME=\"$elem_id\" ID=\"$elem_hidden_id\" VALUE=\"" . $value . "\" type=\"hidden\" $func />";	        
		$sHTML .= "</div>";	        
        	
        $sHTML .= "</div>";
        
if (defined('OPENBIZ_JSLIB_BASE') && OPENBIZ_JSLIB_BASE == 'JQUERY') {
	$sHTML .= "<script>$('#$elem_list_id').hide();
	$('#span_$elem_id, #$elem_id').click(
		function() {
			$('#$elem_list_id, #$elem_scroll_id').toggle();
		}
	);
	$('#span_$elem_id, #$elem_id').hover(
		function () {
			$(this).attr('class','$this->cssHoverClass');
		},
		function () {
			$(this).attr('class','$this->cssClass');
		}
	);
	$('#$elem_list_id li').click(
		function(){
			$('#$elem_list_id, #$elem_scroll_id').hide();
			$('#$elem_id').val($(this).attr('disp_value'));
			$('#$elem_hidden_id').val($(this).attr('real_value'));
			$('#span_$elem_id').html($(this).html());
			$('#$elem_id').attr('class','$this->cssClass');
			$onchange_func;
		}
	);
	</script>";
}
else {
	$sHTML .= "<script>$('$elem_list_id').hide();</script>";
}
        return $sHTML;
    }
    
    public function getDisplayValue($value)
    {
    	if($value===null)
    	{
    		return null;
    	}
		$list = $this->getList();
    	/*$selectFrom = $this->selectFrom;
    	$selectFrom = substr($selectFrom,0,strpos($selectFrom,','));
        
    	$list= array();        
        if (!$selectFrom) {
        	$this->getSQLFromList($list);
        }
        else
        {
        	$this->getXMLFromList($list, $selectFrom);	        	
        	if (!is_array($list) || count($list)==0){
        		$this->getDOFromList($list, $selectFrom);        		
        		if (!is_array($list) || count($list)==0){
        			$this->getSimpleFromList($list, $selectFrom);
        		}				
        	}
        }*/
            
       	if(!is_array($list)||count($list)==0){
       		return $value;
       	}
        
    	foreach ($list as $item){
        	if($item['val']==$value){
        		if(!preg_match("/\</si",$item['txt'])){
        			$display_value = $item['txt'];
        			return $display_value;
        		}else{
        			$display_value = $item['val'];
        			return $display_value;
        		}
        		break;
        	}
        }        
    	return $display_value;
    }
    
    protected function renderList(){
    	
    	if($this->formPrefix){
    		$formNameStr = str_replace(".","_", $this->getFormObj()->objectName)."_";
    	} 
    	$onchange_func = $this->getOnChangeFunction();
    	$list = $this->getList();
    	
        if ($this->blankOption) // ADD a blank option
        {
            $entry = explode(",",$this->blankOption);
            $text = $entry[0];
            $value = ($entry[1]!= "") ? $entry[1] : null;
            $entryList = array(array("val" => $value, "txt" => $text ));
            $list = array_merge($entryList, $list);
        }    	
    	
    	$value = $this->value!==null ? $this->value : $this->getText();
		$elem_id = $formNameStr.$this->objectName;
		$elem_scroll_id = $formNameStr.$this->objectName."_scroll";
		$elem_list_id = $formNameStr.$this->objectName."_list";
		$elem_hidden_id = $formNameStr.$this->objectName."_hidden";
    	$sHTML = "\n<div  class=\"dropdownlist\"  id=\"$elem_scroll_id\" style=\"display:none;\">".
    	$sHTML .= "\n<ul style=\"display:none;z-index:50\" id=\"$elem_list_id\">\n";
    	if(!$list){$list=array();}
    	foreach($list as $item){
    		$val = $item['val'];
    		$txt = $item['txt'];
    		$pic = $item['pic'];
    		if($pic){
    			if(preg_match('/\{.*\}/si',$pic)){
    				$pic = Expression::evaluateExpression($pic,null);
    			}elseif(!preg_match('/\//si',$pic)){
        			$pic = Openbizx::$app->getImageUrl()."/".$pic;
        		} 
    			$str_pic="<img src=\"$pic\" />";
    			
    		}else{
    			$str_pic = "";
    		}    		
    	    if(!preg_match("/</si",$txt)){
        		$display_value = $txt;
    	    }else{
    	    	$display_value = $val;
    	    }    
    	    if($str_pic){
    	    	$li_option_value =  $str_pic."<span>".$txt."</span>";
    	    }
    	    else{
    	    	$li_option_value = "<span>".$txt."</span>"; //$txt ;
    	    }
    	    
    	    if($val==$value)
    	    {    	    	
    	    	$option_item_style=" class='selected' ";
    	    }else{
    	    	$option_item_style=" onmouseover=\"this.className='hover'\" onmouseout=\"this.className=''\" ";
    	    }
    	    // jquery $j('a.maxmin').click( function () {...} );
			if (defined('OPENBIZ_JSLIB_BASE') && OPENBIZ_JSLIB_BASE == 'JQUERY') {
				$sHTML .= "<li $option_item_style disp_value='$display_value' real_value='$val'>$li_option_value</li>\n";
			}
			else {
				$sHTML .= "<li $option_item_style			
							onclick=\"$('$elem_list_id').hide();
									$('$elem_scroll_id').hide();
									$('$elem_id').setValue('".addslashes($display_value)."');
									$('$elem_hidden_id').setValue('".addslashes($val)."');
									$('span_$elem_id').innerHTML = this.innerHTML;
									$onchange_func ;
									$('$elem_id').className='".$this->cssClass."'
									\"	
					>$li_option_value</li>";
    		}
    		if($val == $value){
    			$this->defaultDisplayValue="".$str_pic."<span>".$txt."</span>";
    		}		
    	}
    	$sHTML .= "</ul>";
    	$sHTML .= "</div>";
    	return $sHTML;
    }
    
    protected function getList(){
		if ($this->_listCache) return $this->_listCache;
    	$list= array();
        if (!$selectFrom) {
            $selectFrom = $this->getSelectFrom();
        }
        if (!$selectFrom) {
        	return $this->getSQLFromList($list);
        }
        $this->getXMLFromList($list, $selectFrom);
        if ($list != null) {
            $this->_listCache=$list; return $list;
		}			
        $this->getDOFromList($list, $selectFrom);
        if ($list != null) {
            $this->_listCache=$list; return $list;
		}
        $this->getSimpleFromList($list, $selectFrom);
        if ($list != null) {
            $this->_listCache=$list; return $list;
		}
                
    	return $list;
    }
// below code is copied from OptionElement
    protected function getSelectFrom()
    {
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->selectFrom, $formobj);
    }

    protected function getSelectedList()
    {
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->selectedList, $formobj);
    }
    
	protected function getSelectFromSQL()
    {
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->selectFromSQL, $formobj);
    }
    
    protected function getXMLFromList(&$list, $selectFrom)
    {
        $pos0 = strpos($selectFrom, "(");
        $pos1 = strpos($selectFrom, ")");
        if ($pos0>0 && $pos1 > $pos0)
        {  // select from xml file
            $xmlFile = substr($selectFrom, 0, $pos0);
            $tag = substr($selectFrom, $pos0 + 1, $pos1 - $pos0-1);
            $tag = strtoupper($tag);
            $xmlFile = ObjectFactoryHelper::getXmlFileWithPath($xmlFile);
            if (!$xmlFile) return false;

            $xmlArr = &ObjectFactoryHelper::getXmlArray($xmlFile);
            if ($xmlArr)
            {
                $i = 0;
                if ( !isset($xmlArr["SELECTION"][$tag]) ) {
                    return false;
                }
                if(!$xmlArr["SELECTION"][$tag][0]){
                	$array = $xmlArr["SELECTION"][$tag];
                	unset($xmlArr["SELECTION"][$tag]);
                	$xmlArr["SELECTION"][$tag][0]=$array;
                }
                foreach($xmlArr["SELECTION"][$tag] as $node)
                {
                    $list[$i]['val'] = $node["ATTRIBUTES"]["VALUE"];
                    $list[$i]['pic'] = $node["ATTRIBUTES"]["PICTURE"];
                    if ($node["ATTRIBUTES"]["TEXT"])
                    {
                        $list[$i]['txt'] = $node["ATTRIBUTES"]["TEXT"];                        
                    }
                    else
                    {
                        $list[$i]['txt'] = $list[$i]['val'];
                    }
                    $i++; 
                }
                $this->translateList($list, $tag);	// supprot multi-language
            }
            return true;
        }
        return false;
    }
    
    protected function getDOFromList(&$list, $selectFrom)
    {
        $pos0 = strpos($selectFrom, "[");
        $pos1 = strpos($selectFrom, "]");
        if ($pos0 > 0 && $pos1 > $pos0)
        {  // select from bizObj
            // support BizObjName[BizFieldName] or 
            // BizObjName[BizFieldName4Text:BizFieldName4Value] or 
            // BizObjName[BizFieldName4Text:BizFieldName4Value:BizFieldName4Pic]
            $bizObjName = substr($selectFrom, 0, $pos0);
            $pos3 = strpos($selectFrom, ":");
            if($pos3 > $pos0 && $pos3 < $pos1)
            {
                $fieldName = substr($selectFrom, $pos0 + 1, $pos3 - $pos0 - 1);
                $fieldName_v = substr($selectFrom, $pos3 + 1, $pos1 - $pos3 - 1);
            }
            else
            {
                $fieldName = substr($selectFrom, $pos0 + 1, $pos1 - $pos0 - 1);
                $fieldName_v = $fieldName;
            }
            $pos4 = strpos($fieldName_v, ":");
            if($pos4){
            	$fieldName_v_mixed = $fieldName_v;
            	$fieldName_v = substr($fieldName_v_mixed,0,$pos4);
            	$fieldName_p = substr($fieldName_v_mixed, $pos4+1, strlen($fieldName_v_mixed)-$pos4-1);
            	unset($fieldName_v_mixed);
            }
            $commaPos = strpos($selectFrom, ",", $pos1);
            if ($commaPos > $pos1)
                $searchRule = trim(substr($selectFrom, $commaPos + 1));
            
            /* @var $bizObj BizDataObj */
            $bizObj = Openbizx::getObject($bizObjName);
            if (!$bizObj)
                return false;

            $recList = array();
            $oldAssoc = $bizObj->association;
            $bizObj->association = null;
            QueryStringParam::reset();
            $recList = $bizObj->directFetch($searchRule);
            $bizObj->association = $oldAssoc;

            foreach ($recList as $rec)
            {
                $list[$i]['val'] = $rec[$fieldName_v];
                $list[$i]['txt'] = $rec[$fieldName];
                $list[$i]['pic'] = $rec[$fieldName_p];
                $i++;
            }
            return true;
        }
        return false;
    }
    
    protected function getSimpleFromList(&$list, $selectFrom)
    {
        // in case of a|b|c
        if (strpos($selectFrom, "[") > 0 || strpos($selectFrom, "(") > 0)
            return;
        $recList = explode('|',$selectFrom);
        foreach ($recList as $rec)
        {
            $list[$i]['val'] = $rec;
            $list[$i]['txt'] = $rec;
            $list[$i]['pic'] = $rec;
            $i++;
        }
    }
    
    public function getSQLFromList(&$list)
    {
    	$sql = $this->getSelectFromSQL();
    	if (!$sql) return;
    	$formObj = $this->getFormObj();
    	$do = $formObj->getDataObj();
    	$db = $do->getDBConnection();
    	try {
    		$resultSet = $db->query($sql);
    		$recList = $resultSet->fetchAll();
	    	foreach ($recList as $rec)
	        {
	            $list[$i]['val'] = $rec[0];
	            $list[$i]['txt'] = isset($rec[1]) ? $rec[1] : $rec[0];
	            $i++;
	        }
    	}
    	catch (Exception $e)
        {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query Error: ".$e->getMessage());
            $this->errorMessage = "Error in SQL query: ".$sql.". ".$e->getMessage();
            throw new Openbizx\Data\Exception($this->errorMessage);
            return null;
        }
    }
    
    protected function getOnChangeFunction()
    {
        $name = $this->objectName;
        // loop through the event handlers
        $func = "";

        if ($this->eventHandlers == null)
            return null;
        $formobj = $this->getFormObj();
        foreach ($this->eventHandlers as $eventHandler)
        {
            $ehName = $eventHandler->objectName;
            $event = $eventHandler->event;
            $type = $eventHandler->functionType;
            if (!$event) continue;
            if($events[$event]!=""){
            	$events[$event]=array_merge(array($events[$event]),array($eventHandler->getFormedFunction()));
            }else{
            	$events[$event]=$eventHandler->getFormedFunction();
            }
        }
		
		$function=$events['onchange'];
		if(is_array($function)){
			foreach($function as $f){
				$function_str.=$f.";";
			}
			$func .= $function_str;
		}else{
			$func .= $function;
		}
		
        return $func;
    }    
    
	protected function translateList(&$list, $tag)
    {
    	$module = $this->getModuleName($this->selectFrom);
        if (empty($module))
            $module = $this->getModuleName($this->formName);
    	for ($i=0; $i<count($list); $i++)
    	{
    		$key = 'SELECTION_'.strtoupper($tag).'_'.$i.'_TEXT';
    		$list[$i]['txt'] = I18n::t($list[$i]['txt'], $key, $module, $this->getTransLOVPrefix());
    	}
    }
    
    protected function getTransLOVPrefix()
    {    	
    	$nameArr = explode(".",$this->selectFrom);
    	for($i=1;$i<count($nameArr)-1;$i++)
    	{
    		$prefix .= strtoupper($nameArr[$i])."_";
    	}    	
    	return $prefix;
    }   
}
?>