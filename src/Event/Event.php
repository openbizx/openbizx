<?php

/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id$
 */

namespace Openbizx\Event;

use Openbizx\Event\EventInterface;

/**
 * 
 */
class Event implements EventInterface
{

    public $event_key, $target, $params;

    public function __construct($event_key, $target, $params)
    {
        $this->event_key = $event_key;
        $this->target = $target;
        $this->params = $params;
    }

    public function getName()
    {
        return $this->event_key;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getParams()
    {
        return $this->params;
    }

}
