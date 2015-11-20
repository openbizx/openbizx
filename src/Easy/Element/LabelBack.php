<?php

namespace Openbizx\Easy\Element;

use Openbizx\Easy\Element\LabelText;

class LabelBack extends LabelText
{
    protected function getLink()
    {
        return "javascript:history.go(-1);";
    }

}
