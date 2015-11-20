<?php

namespace Openbizx\Validation;

/**
 * Openbizx\Validation\Exception
 *
 * @package   openbiz.bin
 * @author    Rocky Swen
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 */
class Exception extends \Exception
{

    public $errors;   // key, errormessage pairs

    public function __construct($errors)
    {
        $this->errors = $errors;
        $message = "";
        foreach ($errors as $key => $err) {
            $message .= "$key = $err, ";
        }
        $this->message = $message;
    }

}