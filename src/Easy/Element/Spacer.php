<?php

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\LabelText;

class Spacer extends LabelText
{

    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->cssClass = isset($xmlArr["ATTRIBUTES"]["CSSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSCLASS"] : "element_spacer";
        $this->cssErrorClass = isset($xmlArr["ATTRIBUTES"]["CSSERRORCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSERRORCLASS"] : $this->cssClass . "_error";
        $this->cssFocusClass = isset($xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSFOCUSCLASS"] : $this->cssClass . "_focus";
    }

    public function render()
    {
        $style = $this->getStyle();
        $id = $this->objectName;
        $sHTML = "<span id=\"$id\" $style $func></span>";
        return $sHTML;
    }

}