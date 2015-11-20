<?php 

namespace Openbizx\Easy\Element;

class ColumnStyle extends RawData{

    public function setSortFlag($flag=null)
    {
        $this->sortFlag = $flag;
    }
	
    public function renderLabel()
    {
        return null;
    }	
}

?>