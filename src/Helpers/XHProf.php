<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Openbizx\Helpers;

use Openbizx\Object\Object;

/**
 * Description of XHProf
 * @property-write boolean $enable Set info is XHProf enable or disable.
 * @author agus
 */
class XHProf extends Object
{
    private static $_isEnabled;
    private static $_isStarted = false;

    /**
     * Location path of XHProf.
     * @var string
     */
    private static $_path = null;

    /**
     * Get location path of XHProf.
     * @return string
     */
    public static function getPath()
    {
        return self::$_path;
    }

    /**
     * Set location path of XHProf.
     * @param string $path Location path of XHProf.
     */
    public static function setPath($path)
    {
        self::$_path = $path;
    }

    /**
     * Url of XHProf
     * @var string 
     */
    private static $_url = null;

    /**
     * Get URL of XHProf.
     * @return string
     */
    public static function getUrl()
    {
        return self::$_url;
    }

    /**
     * Set location path of XHProf.
     * @param string $url Location path of XHProf.
     */
    public static function setUrl($url)
    {
        self::$_url = $url;
    }

    /**
     * Set enable info
     * @param boolean $enable Status of enable, true or false.
     * @throws Exception
     */
    public static function setEnable($enable)
    {
        if (!self::$_isStarted) {
            self::$_isEnabled = $enable;
        } else {
            throw new Exception('XHProf is started, not can set enable property.');
        }
    }

    /**
     * Start XHProf
     */
    public static function start()
    {
        self::$_isStarted = true;
        if (self::$_isEnabled && function_exists("xhprof_enable")) {
            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        }
    }

    /**
     * Finish XHProf
     */
    public static function finish()
    {
        if (self::$_isEnabled && function_exists("xhprof_disable")) {
            $xhprof_data = xhprof_disable();
            include_once self::getPath() . "/xhprof_lib/utils/xhprof_lib.php";
            include_once self::getPath() . "/xhprof_lib/utils/xhprof_runs.php";
            $xhprof_runs = new \XHProfRuns_Default();
            $run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");
            echo "<div style=\"text-align:center\">xhprof id: <a target=\"_target\" href=\"" . self::getUrl() . "$run_id\">$run_id</a></div>";
        }
        self::$_isEnabled=false;
    }
    
    //put your code here
}
