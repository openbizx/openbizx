<?PHP

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\Easy\Element\OptionElement;

class ImageSelector extends OptionElement
{
    public $blankOption;


    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->blankOption = isset($xmlArr["ATTRIBUTES"]["BLANKOPTION"]) ? $xmlArr["ATTRIBUTES"]["BLANKOPTION"] : null;
        $this->cssClass = isset($xmlArr["ATTRIBUTES"]["CSSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSCLASS"] : 'image_selector';
        $this->cssErrorClass = isset($xmlArr["ATTRIBUTES"]["CSSERRORCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSERRORCLASS"] : $this->cssClass . "_error";
        $this->cssFocusClass = isset($xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"] : $this->cssClass . "_focus";
    }

   
    public function render()
    {
        $fromList = array();
        $this->getFromList($fromList);
        
        $value = $this->getValue()!='null' ? $this->getValue() : $this->getDefaultValue();
        
        $value = $value===null?$this->getDefaultValue():$value;
        
        $valueArray = explode(',', $value);
        $disabledStr = ($this->getEnabled() == "N") ? "DISABLED=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();
		
        $formobj = $this->GetFormObj();
        if($formobj->errors[$this->objectName]){
			$func .= "onclick=\"this.className='$this->cssClass'\"";
		}else{
			$func .= "onmouseover=\"this.className='$this->cssFocusClass'\" onmouseout=\"this.className='$this->cssClass'\"";
		} 
		
        $sHTML = "<input type=\"hidden\" NAME=\"" . $this->objectName . "\" ID=\"" . $this->objectName ."\" value=\"".$value."\" $disabledStr $this->htmlAttr />";
		$sHTML .= "<ul id=\"image_list_" . $this->objectName ."\" $style $func >";
        if ($this->blankOption) // ADD a blank option
        {
            $entry = explode(",",$this->blankOption);
            $text = $entry[0];
            $value = ($entry[1]!= "") ? $entry[1] : null;
            $entryList = array(array("val" => $value, "txt" => $text ));
            $fromList = array_merge($entryList, $fromList);
        }

        foreach ($fromList as $option)
        {
            $test = array_search($option['val'], $valueArray);
            if ($test === false)
            {
                $selectedStr = 'normal';
            }
            else
            {
                $selectedStr = "current";
            }
	        if($this->width){
	    		$width_str = " width=\"".$this->width."\" ";
	    	}
	        if($this->height){
	    		$height_str = " height=\"".$this->height."\" ";
	    	}          
	    	$image_url = $option['pic'];
	    	if(preg_match("/\{.*\}/si",$image_url))
	        {
	        	$formobj = $this->getFormObj();
	        	$image_url =  Expression::evaluateExpression($image_url, $formobj);
	        }else{
	        	$image_url = Openbizx::$app->getImageUrl()."/".$image_url;
	        }   
            $sHTML .= "<a title=\"" . $option['txt'] . "\" 
            				href=\"javascript:;\"
            				class=\"$selectedStr\"
            				onclick =\"$('".$this->objectName."').value='". $option['val']."';            							
            							Openbizx.ImageSelector.reset('image_list_".$this->objectName."');
            							this.className='current';
            							\"	
            			>
            			<img
            			    $width_str $height_str
            			    src=\"".$image_url."\" 
            				title=\"" . $option['txt'] . "\" 
            				 /></a>";
            
        }
        $sHTML .= "</ul>";        

        return $sHTML;
    }
}

?>
