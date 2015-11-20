<?PHP

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;
use Openbizx\Easy\Element\LabelText;

class LabelImage extends LabelText
{

	private $prefix ;

    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->prefix = isset($xmlArr["ATTRIBUTES"]["URLPREFIX"]) ? $xmlArr["ATTRIBUTES"]["URLPREFIX"] : null;
        $this->prefix =  Expression::evaluateExpression($this->prefix,$this);
    }
	
    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
    	$this->prefix = Expression::evaluateExpression($this->prefix, $formobj);
    	$func = $this->getFunction();
    	if($this->width){
    		$width_str = " width=\"".$this->width."\" ";
    	}
        if($this->height){
    		$height_str = " height=\"".$this->height."\" ";
    	}    	
    	$value = $this->getText()?$this->getText():$this->getValue();
    	if($value){
    		
    		if ($this->link)
            {
                $link = $this->getLink();
                $target = $this->getTarget();
                $sHTML = "<a href=\"$link\" $target $func $style>" . "<img src=\"".$this->prefix.$value."\"  border=\"0\" $width_str $height_str />" . "</a>";
            }
            else
            {
                $sHTML = "<img border=\"0\" src=\"".$this->prefix.$value."\" $func $width_str $height_str />";
            }
    		
        	
    	}
        return $sHTML;
    }

}

?>