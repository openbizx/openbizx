<?php

namespace Openbizx;

use Openbizx\Object\ObjectFactory;
/**
 * Description of Openbizx
 *
 * @author agus
 */
class Openbizx
{
    /**
     *
     * @var \Openbizx\Application
     */
    public static $app;

    private static $_objectFactory = null;
    
    /**
     * Return the version of OpenBiz Framework
     *
     * @return string
     */
    public static function getVersion()
    {
        return '3.1';
    }

    /**
     * Get the metadata object by object name
     *
     * @param string $objectName object name
     * @param number $isNew 0 = not new, 1 new object
     * @return object
     */
    public static function getObject($objectName, $isNew = 0)
    {
        return self::objectFactory()->getObject($objectName,$isNew);
    }

    /**
     * Get the service object
     *
     * @param string $service service name
     * @return object the service object
     */
    public static function getService($service, $new = 0)
    {
        $defaultPackage = "service";
        $serviceName = $service;
        if (strpos($service, ".") === false) {
            $serviceName = $defaultPackage . "." . $service;
        }
        return Openbizx::getObject($serviceName, $new);
    }


    /**
     * Get the openbiz data object by object name.
     * It's functional same as getObject() method, just this method can return more eclipse friendly result,
     * it can support IDE's code auto completaion.
     *
     * @param string $objectName object name
     * @return BizDataObj  if the return object is a BizDataObj then return, or return null
     * @example ../../example/DataObject.php
     */
    public static function getDataObject($objectName)
    {
        $obj = Openbizx::getObject($objectName, 0);
        if (is_a($obj, 'BizDataObj')) {
            return $obj;
        }
    }


    /**
     * Get the openbiz form object by object name.
     * It's functional same as getObject() method, just this method can return more eclipse friendly result,
     * it can support IDE's code auto completaion.
     *
     * @param string $objectName object name
     * @return EasyForm  if the return object is a EasyForm then return, or return null
     * @example ../../example/FormObject.php
     */
    public static function getFormObject($objectName)
    {
        $obj = Openbizx::getObject($objectName, 0);
        if (is_a($obj, 'Openbizx\Easy\EasyForm')) {
            return $obj;
        }
    }

    /**
     * Get the openbiz view object by object name.
     * It's functional same as getObject() method, just this method can return more eclipse friendly result,
     * it can support IDE's code auto completaion.
     *
     * @param string $objectName object name
     * @return WebPage  if the return object is a WebPage then return, or return null
     * @example ../../example/ViewObject.php
     */
    public static function getWebpageObject($objectName)
    {
        return Openbizx::getObject($objectName, 0);
    }

    /**
     * Get get the ObjectFactory object
     *
     * @return ObjectFactory the ObjectFactory object
     */
    public static function objectFactory()
    {
        if (!self::$_objectFactory) {
            self::$_objectFactory = new ObjectFactory();
        }
        return self::$_objectFactory;
    }
}
