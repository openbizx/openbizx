<?php

namespace Openbizx\Object;

/**
 * Parameter class
 *
 * @package   openbiz.bin
 * @author    Rocky Swen
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 * 
 * @todo Remove or empower class.
 */
class Parameter
{

    public $objectName;
    public $value;
    public $required;
    public $inOut;

    public function __construct(&$xmlArr)
    {
        $this->objectName = isset($xmlArr["ATTRIBUTES"]["NAME"]) ? $xmlArr["ATTRIBUTES"]["NAME"] : null;
        $this->value = isset($xmlArr["ATTRIBUTES"]["VALUE"]) ? $xmlArr["ATTRIBUTES"]["VALUE"] : null;
        $this->required = isset($xmlArr["ATTRIBUTES"]["REQUIRED"]) ? $xmlArr["ATTRIBUTES"]["REQUIRED"] : null;
        $this->inOut = isset($xmlArr["ATTRIBUTES"]["INOUT"]) ? $xmlArr["ATTRIBUTES"]["INOUT"] : null;
    }

    /**
     * Get property
     *
     * @param string $propertyName property name
     * @return mixed
     */
    public function getProperty($propertyName)
    {
        if ($propertyName == "Value") {
            return $this->value;
        }
        return null;
    }

}