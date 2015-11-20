<?php

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;

//include_once("ColumnText.php");


class ColumnSorting extends ColumnText
{
	public function render(){
		$func_up = $this->getBtnFunction('fld_sortorder_up');
		$func_down = $this->getBtnFunction('fld_sortorder_down');
		$formobj = $this->getFormObj();		
        
        
		//$this->eventHandlers = null;
		$value = $this->text ? $this->getText() : $this->value;
        
        if ($value === null || $value ==="")
            return "";

        $style = $this->getStyle();
        $id = $this->objectName;

        if ($this->translatable == 'Y')
            $value = $this->translateString($value);
        
        if((int)$this->maxLength>0){
	        if(function_exists('mb_strlen') && function_exists('mb_substr')){
	        	if(mb_strlen($value,'UTF8') > (int)$this->maxLength){
	        		$value = mb_substr($value,0,(int)$this->maxLength,'UTF8').'...';
	        	}        	
	        }else{
	        	if(strlen($value) > (int)$this->maxLength){
	        		$value = substr($value,0,(int)$this->maxLength).'...';
	        	}         	
	        }
        }
        
        if ($value!==null)
        {
        	if($this->displayFormat)
        	{
        		$value = sprintf($this->displayFormat,$value);
        	}
        	if($this->percent=='Y')
        	{
        		$value = sprintf("%.2f",$value*100).'%';
        	}
        	
            if ($this->link)
            {
                $link = $this->getLink();
                $target = $this->getTarget();
                //$sHTML = "<a href=\"$link\" onclick=\"SetOnLoadNewView();\" $style>" . $val . "</a>";
                $sHTML = "<a id=\"$id\" href=\"$link\" $target $func $style>" . $value . "</a>";
            }
            else
            {
                $sHTML = "<span style=\"width:auto;height:auto;line-height:16px;\" $func>" . $value . "</span>";
            }
        }
        
		$sHTML = "<a $func_up  class=\"arrow_up\" href=\"javascript:;\"><img src=\"".Openbizx::$app->getImageUrl()."/spacer.gif"."\" style=\"width:12px;height:12px;\" /></a> ".
				$sHTML.
				" <a $func_down  class=\"arrow_down\" href=\"javascript:;\"><img src=\"".Openbizx::$app->getImageUrl()."/spacer.gif"."\" style=\"width:12px;height:12px;\" /></a>";
		
		return $sHTML;
	}
	
	public function getBtnFunction($event_name){
        $name = $this->objectName;
        // loop through the event handlers
        $func = "";

        if ($this->eventHandlers == null)
            return null;
        $formobj = $this->getFormObj();
        
        $eventHandler = $this->eventHandlers->get($event_name);
                
        $ehName = $eventHandler->objectName;
        $event = $eventHandler->event;
        $type = $eventHandler->functionType;
        if (!$event) return;
        if($events[$event]!=""){
           $events[$event]=array_merge(array($events[$event]),array($eventHandler->getFormedFunction()));
        }else{
           $events[$event]=$eventHandler->getFormedFunction();
        }

		foreach ($events as $event=>$function){
			if(is_array($function)){
				foreach($function as $f){
					$function_str.=$f.";";
				}
				$func .= " $event=\"$function_str\"";
			}else{
				$func .= " $event=\"$function\"";
			}
		}
        return $func;		
	}
}
?>