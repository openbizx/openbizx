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
 * @version   $Id: Resource.php 4179 2011-05-26 07:40:53Z rockys $
 */

namespace Openbizx;

use Openbizx\I18n\I18n;
use Openbizx\Helpers\XMLParser;
//use XMLParser;

/**
 * Resource class
 *
 * @package   openbiz.bin
 * @author    Rocky Swen <rocky@phpopenbiz.org>
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 * @todo loadMessage(), 
 *       getXmlFileWithPath(), 
 *       getTplFileWithPath, 
 *       getLibFileWithPath
 */
class Resource
{

    private static $_imageUrl;
    private static $_cssUrl;
    private static $_jsUrl;
    private static $_currentTheme;
    private static $_xmlFileList;
    private static $_xmlArrayList;

    const DEFAULT_THEME = 'default';

    /**
     * Load message from file
     *
     * @param string $messageFile
     * @return mixed
     */
    public static function loadMessage($messageFile, $packageName = "")
    {
        if (isset($messageFile) && $messageFile != "") {

            // message file location order 
            // 1. OPENBIZ_APP_MESSAGE_PATH."/".$messageFile
            // 2. OPENBIZ_APP_MODULE_PATH . "/$moduleName/message/" . $messageFile;
            // 3. CORE_OPENBIZ_APP_MODULE_PATH . "/$moduleName/message/" . $messageFile;
            // OPENBIZ_APP_PATH / OPENBIZ_APP_MESSAGE_PATH : OPENBIZ_APP_PATH / messages
            if (is_file(OPENBIZ_APP_MESSAGE_PATH . "/" . $messageFile)) {
                return parse_ini_file(OPENBIZ_APP_MESSAGE_PATH . "/" . $messageFile);
            } else if (is_file(OPENBIZ_APP_MODULE_PATH . "/" . $messageFile)) {
                return parse_ini_file(OPENBIZ_APP_MODULE_PATH . "/" . $messageFile);
            } else {
                if (isset($packageName) && $packageName != "") {
                    $dirs = explode('.', $packageName);
                    $moduleName = $dirs[0];
                    $msgFile = OPENBIZ_APP_MODULE_PATH . "/$moduleName/message/" . $messageFile;
                    if (is_file($msgFile)) {
                        return parse_ini_file($msgFile);
                    } else {
                        $errmsg = self::getMessage("SYS_ERROR_INVALID_MSGFILE", array($msgFile));
                        trigger_error($errmsg, E_USER_ERROR);
                    }
                } else {
                    $errmsg = self::getMessage("SYS_ERROR_INVALID_MSGFILE", array(OPENBIZ_APP_MESSAGE_PATH . "/" . $messageFile));
                    trigger_error($errmsg, E_USER_ERROR);
                }
            }
        }
        return null;
    }

    /**
     * Get message from CONSTANT, translate and format it
     * @param string $msgId ID if constant
     * @param array $params parameter for format (use vsprintf)
     * @return string
     */
    public static function getMessage($msgId, $params = array())
    {
        $message = constant($msgId);
        if (isset($message)) {
            $message = I18n::t($message, $msgId, 'system');
            $result = vsprintf($message, $params);
        }
        return $result;
    }

    /**
     * Get image URL
     * @return string
     */
    public static function getImageUrl()
    {
        if (isset(self::$_imageUrl)) {
            return self::$_imageUrl;
        }
        $useTheme = !defined('OPENBIZ_USE_THEME') ? 0 : OPENBIZ_USE_THEME;
        $themeUrl = !defined('OPENBIZ_THEME_URL') ? "../themes" : OPENBIZ_THEME_URL;
        $themeName = Resource::getCurrentTheme();
        if ($useTheme) {
            self::$_imageUrl = "$themeUrl/$themeName/images";
        } else {
            self::$_imageUrl = "../images";
        }
        return self::$_imageUrl;
    }

    /**
     * Get CSS URL
     * @return string
     */
    public static function getCssUrl()
    {
        if (isset(self::$_cssUrl)) {
            return self::$_cssUrl;
        }
        $useTheme = !defined('OPENBIZ_USE_THEME') ? 0 : OPENBIZ_USE_THEME;
        $themeUrl = !defined('OPENBIZ_THEME_URL') ? OPENBIZ_APP_URL . "/themes" : OPENBIZ_THEME_URL;
        $themeName = Resource::getCurrentTheme();
        if ($useTheme) {
            self::$_cssUrl = "$themeUrl/$themeName/css";
        } else {
            self::$_cssUrl = OPENBIZ_APP_URL . "/css";
        }
        return self::$_cssUrl;
    }

    /**
     * Get JavaScript(JS) URL
     * @return string
     */
    public static function getJsUrl()
    {
        if (isset(self::$_jsUrl)) {
            return self::$_jsUrl;
        }
        self::$_jsUrl = !defined('OPENBIZ_JS_URL') ? OPENBIZ_APP_URL . "/js" : OPENBIZ_JS_URL;
        return self::$_jsUrl;
    }

    /**
     * Get smarty template
     * @return Smarty smarty object
     */
    public static function getSmartyTemplate()
    {
        /*
          if(extension_loaded('ionCube Loader')){
          include_once(SMARTY_DIR . "Smarty.class.php");
          }else{
          include_once(SMARTY_DIR . "Smarty.class.src.php");
          }
         *
         */
        $smarty = new \Smarty;

        $useTheme = !defined('OPENBIZ_USE_THEME') ? 0 : OPENBIZ_USE_THEME;
        if ($useTheme) {
            $theme = Resource::getCurrentTheme();
            $themePath = $theme;    // Openbizx::$app->getConfiguration()->GetThemePath($theme);
            if (is_dir(OPENBIZ_THEME_PATH . "/" . $themePath . "/template")) {
                $templateRoot = OPENBIZ_THEME_PATH . "/" . $themePath . "/template";
            } else {
                $templateRoot = OPENBIZ_THEME_PATH . "/" . $themePath . "/templates";
            }

            $smarty->template_dir = $templateRoot;
            $smarty->compile_dir = defined('OPENBIZ_SMARTY_CPL_PATH') ? OPENBIZ_SMARTY_CPL_PATH . "/" . $themePath : $templateRoot . "/cpl";
            $smarty->config_dir = $templateRoot . "/cfg";
            if (!file_exists($smarty->compile_dir)) {
                @mkdir($smarty->compile_dir, 0777);
            }
            // load the config file which has the images and css url defined
            $smarty->config_load('tpl.conf');
        } else {
            if (defined('SMARTY_TPL_PATH')) {
                $smarty->template_dir = SMARTY_TPL_PATH;
            }
            if (defined('OPENBIZ_SMARTY_CPL_PATH')) {
                $smarty->compile_dir = OPENBIZ_SMARTY_CPL_PATH . "/" . $themePath;
            }
            if (defined('SMARTY_CFG_PATH')) {
                $smarty->config_dir = SMARTY_CFG_PATH;
            }
        }
        if (!is_dir($smarty->compile_dir)) {
            mkdir($smarty->compile_dir, 0777);
        }
        // load the config file which has the images and css url defined
        $smarty->assign('app_url', OPENBIZ_APP_URL);
        $smarty->assign('app_index', OPENBIZ_APP_INDEX_URL);
        $smarty->assign('js_url', OPENBIZ_JS_URL);
        $smarty->assign('css_url', OPENBIZ_THEME_URL . "/" . $theme . "/css");
        $smarty->assign('resource_url', OPENBIZ_RESOURCE_URL);
        $smarty->assign('resource_php', OPENBIZ_RESOURCE_PHP);
        $smarty->assign('theme_js_url', OPENBIZ_THEME_URL . "/" . $theme . "/js");
        $smarty->assign('theme_url', OPENBIZ_THEME_URL . "/" . $theme);
        $smarty->assign('image_url', OPENBIZ_THEME_URL . "/" . $theme . "/images");
        $smarty->assign('lang', strtolower(I18n::getCurrentLangCode()));
        $smarty->assign('lang_name', I18n::getCurrentLangCode());

        return $smarty;
    }

    /**
     * Get \Zend Template
     * @return \Zend_View zend view template object
     */
    public static function getZendTemplate()
    {
        $view = new \Zend_View();
        if (defined('SMARTY_TPL_PATH')) {
            $view->setScriptPath(SMARTY_TPL_PATH);
        }

        $theme = Resource::getCurrentTheme();

        // load the config file which has the images and css url defined
        $view->app_url = OPENBIZ_APP_URL;
        $view->app_index = OPENBIZ_APP_INDEX_URL;
        $view->js_url = OPENBIZ_JS_URL;
        $view->css_url = OPENBIZ_THEME_URL . "/" . $theme . "/css";
        $view->resource_url = OPENBIZ_RESOURCE_URL;
        $view->theme_js_url = OPENBIZ_THEME_URL . "/" . $theme . "/js";
        $view->theme_url = OPENBIZ_THEME_URL . "/" . $theme;
        $view->image_url = OPENBIZ_THEME_URL . "/" . $theme . "/images";
        $view->lang = strtolower(I18n::getCurrentLangCode());

        return $view;
    }

    /**
     * Get Xml file with path
     *
     * Search the object metedata file as objname+.xml in metedata directories
     * name convension: demo.BOEvent points to metadata/demo/BOEvent.xml
     * new in 2.2.3, demo.BOEvent can point to modules/demo/BOEvent.xml
     *
     * @param string $xmlObj xml object name
     * @return string xml config file path
     * */
    public static function getXmlFileWithPath($xmlObj)
    {
        if (isset(self::$_xmlFileList[$xmlObj])) {
            return self::$_xmlFileList[$xmlObj];
        }
        $xmlFile = $xmlObj;
        if (strpos($xmlObj, ".xml") > 0) {  // remove .xml suffix if any
            $xmlFile = substr($xmlObj, 0, strlen($xmlObj) - 4);
        }

        // replace "." with "/"
        $xmlFile = str_replace(".", "/", $xmlFile);
        // check the leading char '@'
        $checkExtModule = true;
        if (strpos($xmlFile, '@') === 0) {
            $xmlFile = substr($xmlFile, 1);
            $checkExtModule = false;
        }
        $xmlFile .= ".xml";
        $xmlFile = "/" . $xmlFile;

        // find device path first
        if (defined('OPENBIZ_CLIENT_DEVICE')) {
            $path = dirname($xmlFile);
            if (strpos($path, 'view') > 0 || strpos($path, 'form') > 0 || strpos($path, 'widget') > 0) {
                $fname = basename($xmlFile);
                $xmlFileList[] = OPENBIZ_APP_MODULE_PATH . "/$path/" . OPENBIZ_CLIENT_DEVICE . "/$fname";
            }
        }

        // search in modules directory first
        $xmlFileList[] = OPENBIZ_APP_MODULE_PATH . $xmlFile;
        $xmlFileList[] = OPENBIZ_APP_PATH . $xmlFile;
        $xmlFileList[] = OPENBIZ_META . $xmlFile;
        if ($checkExtModule && defined('MODULE_EX_PATH')) {
            array_unshift($xmlFileList, MODULE_EX_PATH . $xmlFile);
        }

        foreach ($xmlFileList as $xmlFileItem) {
            if (file_exists($xmlFileItem)) {
                self::$_xmlFileList[$xmlObj] = $xmlFileItem;
                return $xmlFileItem;
            }
        }
        self::$_xmlFileList[$xmlObj] = null;
        return null;
    }

    /**
     * Get openbiz template file path by searching modules/package, /templates
     *
     * @param string $className
     * @return string php library file path
     * */
    public static function getTplFileWithPath($templateFile, $packageName)
    {
        //for not changing a lot things, the best injection point is added theme support here.
        $theme = Resource::getCurrentTheme();
        $themePath = $theme;    // Openbizx::$app->getConfiguration()->GetThemePath($theme);
        if ($themePath) {
            $templateRoot = OPENBIZ_THEME_PATH . "/" . $themePath . "/template";
        } else {
            $templateRoot = SMARTY_TPL_PATH;
        }

        $names = explode(".", $packageName);
        if (count($names) > 0) {
            $moduleName = $names[0];
        }
        $packagePath = str_replace('.', '/', $packageName);
        // check the leading char '@'
        $checkExtModule = true;
        if (strpos($packagePath, '@') === 0) {
            $packagePath = substr($packagePath, 1);
            $checkExtModule = false;
        }

        $searchTpls = array(
            OPENBIZ_APP_MODULE_PATH . "/$packagePath/template/$templateFile",
            dirname(OPENBIZ_APP_MODULE_PATH . "/$packagePath") . "/template/$templateFile",
            OPENBIZ_APP_MODULE_PATH . "/$moduleName/template/$templateFile",
            //OPENBIZ_APP_MODULE_PATH."/common/template/$templateFile",
            $templateRoot . "/$templateFile"
        );
        if ($checkExtModule && defined('MODULE_EX_PATH')) {
            array_unshift($searchTpls, MODULE_EX_PATH . "/$packagePath/template/$templateFile");
        }

        // device
        if (defined('OPENBIZ_CLIENT_DEVICE')) {
            array_unshift($searchTpls, OPENBIZ_APP_MODULE_PATH . "/$moduleName/template/" . OPENBIZ_CLIENT_DEVICE . "/$templateFile");
        }

        foreach ($searchTpls as $tplFile) {
            if (file_exists($tplFile)) {
                return $tplFile;
            }
        }
        $errmsg = Resource::getMessage("UNABLE_TO_LOCATE_TEMPLATE_FILE", array($templateFile));
        trigger_error($errmsg, E_USER_ERROR);
        return null;
    }

    /**
     * Get Xml Array.
     * If xml file has been compiled (has .cmp), load the cmp file as array;
     * otherwise, compile the .xml to .cmp first new 2.2.3, .cmp files
     * will be created in app/cache/metadata_cmp directory. replace '/' with '_'
     * for example, /module/demo/BOEvent.xml has cmp file as _module_demo_BOEvent.xml
     *
     * @param string $xmlFile
     * @return array
     **/
    public static function &getXmlArray($xmlFile)
    {
        if (isset(self::$_xmlArrayList[$xmlFile])) {
            return self::$_xmlArrayList[$xmlFile];
        }
        
        $objXmlFileName = $xmlFile;
        //echo "getXmlArray($xmlFile)\n";
        //$objCmpFileName = dirname($objXmlFileName) . "/__cmp/" . basename($objXmlFileName, "xml") . ".cmp";
        //$_crc32 = sprintf('%08X', crc32(dirname($objXmlFileName)));
        $_crc32 = strtoupper(md5(dirname($objXmlFileName)));
        $objCmpFileName = OPENBIZ_CACHE_METADATA_PATH . '/' . $_crc32 . '_'
                . basename($objXmlFileName, "xml") . "cmp";

        $xmlArr = null;
        //$cacheKey = substr($objXmlFileName, strlen(META_PATH)+1);
        $cacheKey = $objXmlFileName;
        $findInCache = false;
        if (file_exists($objCmpFileName) && (filemtime($objCmpFileName) > filemtime($objXmlFileName))) {
            // search in cache first
            if (!$xmlArr && extension_loaded('apc')) {
                if (($xmlArr = apc_fetch($cacheKey)) != null) {
                    $findInCache = true;
                }
            }
            if (!$xmlArr) {
                $content_array = file($objCmpFileName);
                $xmlArr = unserialize(implode("", $content_array));
            }
        } else {
            $parser = new XMLParser($objXmlFileName, 'file', 1);
            $xmlArr = $parser->getTree();
            //echo var_dump($xmlArr);
            // simple validate the xml array
            $root_keys = array_keys($xmlArr);
            $root_key = $root_keys[0];
            if (!$root_key || $root_key == "") {
                trigger_error("Metadata file parsing error for file $objXmlFileName. Please double check your metadata xml file again.", E_USER_ERROR);
            }
            $xmlArrStr = serialize($xmlArr);
            if (!file_exists(dirname($objCmpFileName))) {
                mkdir(dirname($objCmpFileName));
            }
            $cmp_file = fopen($objCmpFileName, 'w') or die("can't open cmp file to write");
            fwrite($cmp_file, $xmlArrStr) or die("can't write to the cmp file");
            fclose($cmp_file);
        }
        // save to cache to avoid file processing overhead
        if (!$findInCache && extension_loaded('apc')) {
            apc_store($cacheKey, $xmlArr);
        }
        self::$_xmlArrayList[$xmlFile] = $xmlArr;
        return $xmlArr;
    }

    // theme selection priority: url, session, userpref, system(constant)
    public static function getCurrentTheme()
    {
        if (Resource::$_currentTheme != null) {
            return Resource::$_currentTheme;
        }
        $currentTheme = "";
        if (isset($_GET['theme'])) {
            $currentTheme = $_GET['theme'];
        }
        if ($currentTheme == "") {
            $currentTheme = Openbizx::$app->getSessionContext()->getVar("THEME");
        }
        if ($currentTheme == "") {
            $currentTheme = Openbizx::$app->getUserPreference("theme");
        }
        if ($currentTheme == "" && defined('OPENBIZ_THEME_NAME')) {
            $currentTheme = OPENBIZ_THEME_NAME;
        }
        if ($currentTheme == "") {
            $currentTheme = Resource::DEFAULT_THEME;
        }

        // TODO: user pereference has language setting

        Openbizx::$app->getSessionContext()->setVar("THEME", $currentTheme);
        Resource::$_currentTheme = $currentTheme;

        return $currentTheme;
    }


    /**
     * Get list of default template location
     * @return array
     */
    public static function getDefaultTemplateLocations()
    {
        return array(
            OPENBIZ_APP_MODULE_PATH . "/$packagePath/template/$templateFile",
            dirname(OPENBIZ_APP_MODULE_PATH . "/$packagePath") . "/template/$templateFile",
            OPENBIZ_APP_MODULE_PATH . "/$moduleName/template/$templateFile",
            //OPENBIZ_APP_MODULE_PATH."/common/template/$templateFile",
            $templateRoot . "/$templateFile"
        );
    }


}
