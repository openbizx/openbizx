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
 * Default configurator used by ConfiguratorManager
 *
 * @link https://github.com/openbizx/openbizx/blob/master/src/Object/DefaultConfigurator.php
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 * @since 1.0
 */
class DefaultConfigurator implements ConfiguratorInterface
{

    /**
     * Configur object
     * @param type $object
     * @param type $config
     * @return type
     */
    public function configure($object, $config = [])
    {
        foreach ($config as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }

}
