<?php

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;
use Openbizx\Easy\Element\ColumnBar;

//include_once ('ColumnBar.php');

class LabelBar extends ColumnBar
{

    public function render()
    {
        $value = $this->text ? $this->getText() : $this->value;
        if ($this->color) {
            $formObj = $this->getFormObj();
            $color = Expression::evaluateExpression($this->color, $formObj);
            if (!$color) {
                $color = '33b5fb';
            }
            $bgcolor_str = "background-color: #" . $color . ";";
        } else {
            $bgcolor_str = "background-color: #33b5fb;";
        }

        if ($this->displayFormat) {
            $value = sprintf($this->displayFormat, $value);
        }
        if ($this->percent == 'Y') {
            $value = sprintf("%.2f", $value * 100) . '%';
        }
        $style = $this->getStyle();
        $id = $this->objectName;
        $func = $this->getFunction();
        $height = $this->height;
        $width = $this->width - 80;
        $max_value = Expression::evaluateExpression($this->maxValue, $this->getFormObj());

        $width_rate = ($value / $max_value);

        if ($width_rate > 1) {
            $width_rate = 1;
        }
        $width_bar = (int) ($width * $width_rate);

        if (!preg_match("/MSIE 6/si", $_SERVER['HTTP_USER_AGENT'])) {
            $bar_overlay = "<span class=\"bar_data_bg\" style=\"" . $bgcolor_str . "height:" . $height . "px;width:" . $width_bar . "px;\"></span>";
            $bar = "<span class=\"bar_data\" style=\"" . $bgcolor_str . "height:" . $height . "px;width:" . $width_bar . "px;\"></span>";
        } else {
            $bar = "<span class=\"bar_data\" style=\"" . $bgcolor_str . "height:" . $height . "px;width:" . $width_bar . "px;opacity: 0.4;filter: alpha(opacity=40);\"></span>";
        }

        $sHTML = "
    	<span id=\"$id\" $func $style >
    		
    		<span class=\"bar_bg\" style=\"height:" . $height . "px;width:" . $width . "px;\">    			
    		$bar_overlay
    		$bar	 
    		</span>
    		
    		<span class=\"value\" style=\"text-align:left;text-indent:10px;\">$value" . $this->displayUnit . "</span>
    	</span>
    	";
        return $sHTML;
    }

}