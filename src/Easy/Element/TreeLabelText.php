<?php 

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\TreeListbox;

class TreeLabelText extends TreeListbox
{
    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $fromList = array();
        $this->getFromList($fromList);
        $valueArray = explode(',', $this->value);

        $sHTML = "";
        if ($this->blankOption) // ADD a blank option
        {
            $entry = explode(",",$this->blankOption);
            $text = $entry[0];
            $value = ($entry[1]!= "") ? $entry[1] : null;
            $entryList = array(array("val" => $value, "txt" => $text ));
            $fromList = array_merge($entryList, $fromList);
        }

        $i=1;
        $fromListCount=count($fromList);
        foreach ($fromList as $option)
        {   
        	//if($i<=($fromListCount-1)){     	
	            $test = array_search($option['val'], $valueArray);
	            //$sHTML .= "<OPTION VALUE=\"" . $option['val'] . "\" $selectedStr>" . $option['txt'] . "</OPTION>";
	            $sHTML .= "<span>" . $option['txt'] . "</span><br />";            
	            $i++;
        	//}
        }
        
        return $sHTML;
    }     
}
?>