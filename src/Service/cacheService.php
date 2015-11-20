<?php

/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.service
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: cacheService.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Service;

use Zend\Cache\StorageFactory;

/**
 * accessService class is the plug-in service of handling cache
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 */
class cacheService
{
    public $cache = "Disbaled";
    public $cacheEngine = "File";
    protected $cacheOptions = array();
    protected $cacheEngineOptions = array();
    protected $cacheObj = null;

    /**
     * Initialize accessService with xml array metadata
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
    }

    /**
     * Destroy cache
     *
     * @return void
     */
    public function destroy()
    {
        //$this->cache = null;
        //$this->cacheEngine = null;
        //$this->cacheObj = null;
        //$this->cacheOptions = null;
        //$this->cacheEngineOptions = null;
    }

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetadata(&$xmlArr)
    {
        $this->cache = isset($xmlArr["PLUGINSERVICE"]["CACHESETTING"]["ATTRIBUTES"]["MODE"]) ? $xmlArr["PLUGINSERVICE"]["CACHESETTING"]["ATTRIBUTES"]["MODE"] : "Enabled";
        $this->cacheEngine = isset($xmlArr["PLUGINSERVICE"]["CACHEENGINE"]["ATTRIBUTES"]["TYPE"]) ? $xmlArr["PLUGINSERVICE"]["CACHEENGINE"]["ATTRIBUTES"]["TYPE"] : "FileCache";
        // process Cache settings
        if (strtoupper($this->cache) == "ENABLED") {
            $this->loadConfig($xmlArr["PLUGINSERVICE"]["CACHESETTING"]["CONFIG"], $this->cacheOptions);
        }
        switch (strtoupper($this->cacheEngine)) {
            case "FILE":
                $this->loadConfig($xmlArr["PLUGINSERVICE"]["CACHEENGINE"]["FILE"]["CONFIG"], $this->cacheEngineOptions);
                //no break there , because all other engine is inherit from FileCache
                break;

            case "SQLITE":
                $this->loadConfig($xmlArr["PLUGINSERVICE"]["CACHEENGINE"]["SQLITE"]["CONFIG"], $this->cacheEngineOptions);
                break;

            case "MEMCACHED":
                $this->loadConfig($xmlArr["PLUGINSERVICE"]["CACHEENGINE"]["MEMCACHED"]["CONFIG"], $this->cacheEngineOptions);
                break;

            case "XCACHE":
                $this->loadConfig($xmlArr["PLUGINSERVICE"]["CACHEENGINE"]["XCACHE"]["CONFIG"], $this->cacheEngineOptions);
                break;

            case "APC":
                $this->loadConfig($xmlArr["PLUGINSERVICE"]["CACHEENGINE"]["APC"]["CONFIG"], $this->cacheEngineOptions);
                break;

            case "ZENDPLATFORM":
                $this->loadConfig($xmlArr["PLUGINSERVICE"]["CACHEENGINE"]["ZENDPLATFORM"]["CONFIG"], $this->cacheEngineOptions);
                break;
        }
    }

    /**
     * Load cache configuratoon
     *
     * @param array $configs
     * @param array $options
     * @return void
     */
    private function loadConfig(&$configs, &$options)
    {
        foreach ($configs as $config) {
            $value_up = strtoupper($config["ATTRIBUTES"]["VALUE"]);
            if ($value_up == "Y") {
                $config["ATTRIBUTES"]["VALUE"] = true;
            } elseif ($value_up == "N") {
                $config["ATTRIBUTES"]["VALUE"] = false;
            }
            $options[$config["ATTRIBUTES"]["NAME"]] = $config["ATTRIBUTES"]["VALUE"];
            if ($config["ATTRIBUTES"]["NAME"] == 'cache_dir') {
                $options[$config["ATTRIBUTES"]["NAME"]] = OPENBIZ_CACHE_PATH . "/" . $config["ATTRIBUTES"]["VALUE"];
            }
        }
    }

    /**
     * Initialize cache
     *
     * @param string $objName
     * @param number $lifeTime
     * @return boolean|\Zend_Cache
     */
    public function init($objName = "", $lifeTime = 0)
    {
        if (strtoupper($this->cache) == "ENABLED") {
            if (strtoupper($this->cacheEngine) == "FILE" && $objName != "") {
                $objfolder = str_replace(".", "/", $objName) . "/";
                $objfolder = str_replace(array(':', ' '), '_', $objfolder);
                if (!strpos($this->cacheEngineOptions['cache_dir'], $objfolder)) {
                    $this->cacheEngineOptions['cache_dir'].=$objfolder;
                }
            }

            if (!file_exists($this->cacheEngineOptions['cache_dir'])) {
                $this->_makeDirectory($this->cacheEngineOptions['cache_dir'], 0777);
            }

            $this->cacheOptions['automatic_serialization'] = true;

            if ((int) $lifeTime > 0) {
                $this->cacheOptions['lifetime'] = (int) $lifeTime;
            }
            
            //require_once 'Zend/Cache.php';
            $this->cacheObj = \Zend_Cache::factory(
                            'Core', $this->cacheEngine, $this->cacheOptions, $this->cacheEngineOptions
            );
            return $this->cacheObj;
        } else {
            return false;
        }
    }

    /**
     * Save cache
     *
     * @param mixed $data
     * @param string $id
     * @return boolean true if no problem
     */
    public function save($data, $id)
    {
        if ($this->cacheObj && strtoupper($this->cache) == "ENABLED") {
            return $this->cacheObj->save($data, $id);
        } else {
            return false;
        }
    }

    /**
     * Load cache
     *
     * @param string $id cache id
     * @return mixed cached datas (or false)
     */
    public function load($id)
    {
        if ($this->cacheObj && strtoupper($this->cache) == "ENABLED") {
            return $this->cacheObj->load($id);
        } else {
            return false;
        }
    }

    /**
     * Test cache
     *
     * @param string $id cache id
     * @return boolean true is a cache is available, false else
     */
    public function test($id)
    {
        if ($this->cacheObj && strtoupper($this->cache) == "ENABLED") {
            return $this->cacheObj->test($id);
        } else {
            return false;
        }
    }

    /**
     * Remove a cache
     *
     * @param string $id cache id to remove
     * @return boolean true if ok
     */
    public function remove($id)
    {
        if ($this->cacheObj && strtoupper($this->cache) == "ENABLED") {
            return $this->cacheObj->remove($id);
        } else {
            return false;
        }
    }

    /**
     * get a list of all caches
     *
     * @return array ids
     */
    public function getIds()
    {
        if ($this->cacheObj && strtoupper($this->cache) == "ENABLED") {
            return $this->cacheObj->getIds();
        } else {
            return false;
        }
    }

    /**
     * clean all cache
     *
     * @return boolean true if ok
     */
    public function cleanAll()
    {
        if ($this->cacheObj && strtoupper($this->cache) == "ENABLED") {
            return $this->cacheObj->clean(\Zend_Cache::CLEANING_MODE_ALL);
        } else {
            return false;
        }
    }

    /**
     * clean Expired
     *
     * @return boolean true if ok
     */
    public function cleanExpired()
    {
        if ($this->cacheObj && strtoupper($this->cache) == "ENABLED") {
            return $this->cacheObj->clean(\Zend_Cache::CLEANING_MODE_OLD);
        } else {
            return false;
        }
    }

    /**
     * Make directory recursively
     *
     * @param string $pathName The directory path.
     * @param int $mode<p>
     * The mode is 0777 by default, which means the widest possible
     * access. For more information on modes, read the details
     * on the chmod page.
     * @return bool Returns true on success or false on failure.
     * @todo need move to utility class or helper?
     */
    private function _makeDirectory($pathName, $mode)
    {
        is_dir(dirname($pathName)) || $this->_makeDirectory(dirname($pathName), $mode);
        return is_dir($pathName) || @mkdir($pathName, $mode);
    }

}
