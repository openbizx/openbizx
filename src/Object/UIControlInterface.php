<?php

namespace Openbizx\Object;

/**
 * UIControlInterface, all UI classes need to implement Render method
 *
 * @package   openbiz.bin
 * @author    Rocky Swen
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 */
interface UIControlInterface
{

    /**
     * Render user interface.
     */
    public function render();
}