<?php

/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   Openbizx.Event
 * @copyright Copyright (c) 2005-2011, Openbizx LLC
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id$
 */

namespace Openbizx\Event;

use Openbizx\Event\EventManagerInterface;
use Openbizx\Event\Event;

/**
 * EventManager is the class that trigger events
 *
 * @package   openbiz.bin
 * @author    Rocky Swen <rocky@phpopenbiz.org>
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @access    public
 */
class EventManager implements EventManagerInterface
{

    protected $eventObsevers;

    public function trigger($event_key, $target, $params)
    {
        $event = new Event($event_key, $target, $params);
        $matchedObservers = $this->getMatchObservers($event_key);
        foreach ($matchedObservers as $observer) {
            $observer->observe($event);
        }
    }

    public function attach($event_key, $observer, $priority = null)
    {
        $this->eventObsevers[$event_key][] = $observer;
    }

    protected function getMatchObservers($event_key)
    {
        if (isset($this->eventObsevers[$event_key])) {
            return $this->eventObsevers[$event_key];
        } else {
            return array();
        }
    }

}
