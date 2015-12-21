<?php

/**
 * Openbizx Framework (http://openbizx.github.io/)
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @copyright Copyright (c) 2015 Mpu Technology
 * @license   http://opensource.org/licenses/BSD-3-Clause
 * @link      http://openbizx.github.io/
 */

namespace Openbizx\Object;

/**
 * Interface for all configurator object 
 * 
 * @link https://github.com/openbizx/openbizx/blob/master/src/Object/ConfiguratorInterface.php
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 * @since 1.0
 */
interface ConfiguratorInterface
{

    /**
     * Configure object
     * @param Object $object
     * @param array $config
     */
    public function configure($object, $config);
}
