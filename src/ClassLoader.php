<?php

/**
 * Openbizx Framework
 *
 * This file contain BizApplication class, the C from MVC of phpOpenBiz framework,
 * and execute it. So bootstrap script simply include this file. For sample of
 * bootstrap script please see controller.php under baseapp/bin
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
 * @version   $Id: BizApplication.php 4882 2012-11-30 07:48:46Z hellojixian@gmail.com $
 */

namespace Openbizx;

/**
 * BizApplication is the class that dispatches client requests to proper objects
 *
 * @package   openbiz.bin
 * @author    Rocky Swen <rocky@phpopenbiz.org> and Openbizx Dev Team
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @access    public
 */
class ClassLoader
{

    private static $_classNameCache = array();

    protected function __construct()
    {
        //  $coreClassMap = include(__DIR__ . DIRECTORY_SEPARATOR  . 'autoload_classmap.php' )  ;
        //  print_r($coreClassMap);
        // exit;
        //self::registerClassMap($coreClassMap);
    }

    /**
     * Class autoloading
     *    - not check $_classNameCache, because autoload called only class not yet load
     *    - not need package
     * @param type $className
     * @return boolean
     */
    public static function autoload($className)
    {        
        $filePath = self::getAutoloadLibFileWithPath($className);

        //if ($className==='Zend_Cache') {
        //    echo $className . '-' . $filePath . '<br />';
        //}
        
        //var_dump( $filePath);
        if ($filePath) {
            include_once($filePath); // auto_load
            //if ($className==='Openbizx\Data\NodeRecord') {
            //    echo (class_exists($className)? 'ada':'tidal-ada');
            //}
            self::$_classNameCache[$className] = 1; // 
            return true;
        }
        return false;
    }

    /**
     * Get openbiz library php file path for autoload, remove metadata package searching
     *
     * @param string $className
     * @return string php library file path
     * */
    public static function getAutoloadLibFileWithPath($className)
    {
        if (!$className) {
            return;
        }
        
        // use class map first
        if (isset(self::$classMap[$className])) {
            return self::$classMap[$className];
        }

        // search it in cache first
        $cacheKey = $className . "_path";
        if (extension_loaded('apc') && ($filePath = apc_fetch($cacheKey)) != null) {
            return $filePath;
        }

        if (strpos($className, 'Zend_') === 0) {
            $filePath = self::getZendFileWithPath($className);
        } else {
            $filePath = self::getCoreLibFilePath($className);
        }
        // cache it to save file search
        if ($filePath && extension_loaded('apc')) {
            apc_store($cacheKey, $filePath);
        }
        /* if (!file_exists($filePath)) {
          trigger_error("Cannot find the library file of $className", E_USER_ERROR);
          } */
        return $filePath;
    }

    public static function getZendFileWithPath($className)
    {
        // autodiscover the path from the class name
        $classFile = ZEND_FRWK_HOME . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        return $classFile;
    }

    /**
     * Load class of MetaObject
     * 
     * @param type $className
     * @param type $packageName
     * @return boolean
     */
    public static function loadMetadataClass($className, $packageName = '')
    {
        if (class_exists($className, false)) {
            return true;
        }
        if (isset(self::$_classNameCache[$packageName . $className])) {
            return true;
        }
        if (strpos($className, 'Zend_') === 0) {
            return true;
        }
        $filePath = self::getLibFileWithPath($className, $packageName);
        if ($filePath) {
            include_once($filePath);
            self::$_classNameCache[$packageName . $className] = 1;
            return true;
        }
        return false;
    }

    /**
     * Get core path of class
     *
     * @param string $className class name
     * @return string full file name of class
     */
    public static function getCoreLibFilePath($className)
    {
        // if class not yet collect on class map, scan core path.                 
        $classFile = $className . '.php';
        // TODO: search the file under bin/, bin/data, bin/ui. bin/service, bin/easy, bin/Easy/element.
        // guess class type and folder
        $lowClassName = strtolower($className);
        if (strrpos($lowClassName, 'service') > 0) {
            $corePaths = array('service/');
        } else if (strrpos($lowClassName, 'form') > 0 || strrpos($lowClassName, 'form') === 0) {
            $corePaths = array('easy/');
        } else if (strrpos($lowClassName, 'view') > 0 || strrpos($lowClassName, 'view') === 0) {
            $corePaths = array('easy/');
        } else if (strrpos($lowClassName, 'dataobj') > 0) {
            $corePaths = array('data/');
        } else {
            $corePaths = array('easy/element/', '', 'data/', 'easy/', 'service/');
        }
        //$corePaths = array('', 'data/', 'easy/', 'easy/element/', 'ui/', 'service/');
        foreach ($corePaths as $path) {
            $_classFile = OPENBIZ_BIN . $path . $classFile;
            //echo "file_exists($_classFile)\n";
            if (file_exists($_classFile)) {
                return $_classFile;
            }
        }
        return null;
    }

    /**
     * Get openbiz library php file path by searching modules/package, /bin/package and /bin
     *
     * @param string $className
     * @return string php library file path
     * */
    public static function getLibFileWithPath($className, $packageName = "")
    {
        if (!$className) {
            return;
        }

        if (isset(self::$classMap[$packageName . $className])) {
            return self::$classMap[$packageName . $className];
        }

        // search it in cache first
        $cacheKey = $className . "_path";
        if (extension_loaded('apc') ) {
            $filePath = apc_fetch($cacheKey);
            if ( $filePath !== null ) {
                return $filePath;
            }
        }
        
        $filePath = self::_findClassFileOnCache($className);
        if ( $filePath !== null ) {
            return $filePath;
        }
        

        if (strpos($className, ".") > 0) {
            $className = str_replace(".", "/", $className);
        }

        $filePath = null;
        $classFile = $className . ".php";
        $classFile_0 = $className . ".php";
        // convert package name to path, add it to classfile
        $classFileIsFound = false;
        if ($packageName) {
            $path = str_replace(".", "/", $packageName);
            // check the leading char '@'
            $checkExtModule = true;
            if (strpos($path, '@') === 0) {
                $path = substr($path, 1);
                $checkExtModule = false;
            }

            // search in apphome/modules directory first, search in apphome/bin directory then
            $classFiles[0] = Openbizx::$app->getModulePath() . "/" . $path . "/" . $classFile;
            $classFiles[1] = OPENBIZ_APP_PATH . "/bin/" . $path . "/" . $classFile;
            if ($checkExtModule && defined('MODULE_EX_PATH')) {
                array_unshift($classFiles, MODULE_EX_PATH . "/" . $path . "/" . $classFile);
            }
            foreach ($classFiles as $classFile) {
                if (file_exists($classFile)) {
                    $filePath = $classFile;
                    $classFileIsFound = true;
                    break;
                }
            }
        }

        if (!$classFileIsFound) {
            $filePath = self::getCoreLibFilePath($className);
        }
        // cache it to save file search
        if ($filePath && extension_loaded('apc')) {
            apc_store($cacheKey, $filePath);
        }
        /* if (!file_exists($filePath)) {
          trigger_error("Cannot find the library file of $className", E_USER_ERROR);
          } */
        return $filePath;
    }

    /**
     * Find class file location from cache dictionary.
     * @param type $className
     * @return string|null full path filename if found or null if not found 
     */ 
    private static function _findClassFileOnCache($className) {
        // search it in cache first
        $cacheKey = $className . "_path";
        if (extension_loaded('apc') ) {
            $filePath = apc_fetch($cacheKey);
            return $filePath;            
        }
        return null;     
    }

    public static function registerClassMap($classMap)
    {
        self::$classMap = array_merge(self::$classMap, $classMap);
    }

    /**
     * class map for openbiz core class
     * @author agus suhartono
     * @var array
     */
    public static $classMap = array();

}
