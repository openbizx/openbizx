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
 * Manage configurator that configure object
 *
 * @link https://github.com/openbizx/openbizx/blob/master/src/Object/ConfiguratorManager.php
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 * @since 1.0
 */
class ConfiguratorManager
{

    private static $_configurator = null;

    /**
     * Set default configurator that configure object.
     * 
     * @param ConfiguratorInterface $configurator
     */
    public static function setConfigurator($configurator)
    {
        self::$_configurator = configurator;
    }
    
    /**
     * Get configurator
     * If configurator not yet defined, then return DefaultConfigurator
     * @return type
     */
    public static function getConfigurator()
    {
        if (self::$_configurator === null) {
            self::$_configurator = new DefaultConfigurator;
        }  
        return self::$_configurator;
    }

    /**
     * Configures an object with the initial property values.
     * @param Object $object the object to be configured
     * @param array $config = [] the property initial values given in terms of name-value pairs.
     * @return Object object that configure
     */
    public static function configure($object, $config = [])
    {
        return self::getConfigurator()->configure($object, $config);
    }

}
