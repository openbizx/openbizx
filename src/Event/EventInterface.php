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
 * @copyright Copyright (c) 2005-2011, Openbiz LLC
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id$
 */

namespace Openbizx\Event;

/**
 *
 * @author agus
 */
interface EventInterface
{

    public function getName();

    public function getTarget();

    public function getParams();
}
