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

namespace Openbizx\Helpers;

use Openbizx\Openbizx;
use Openbizx\I18n\I18n;

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
class TemplateHelper
{

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
            $theme = Openbizx::$app->getCurrentTheme();
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

        $theme = Openbizx::$app->getCurrentTheme();

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
     * Get openbiz template file path by searching modules/package, /templates
     *
     * @param string $className
     * @return string php library file path
     * */
    public static function getTplFileWithPath($templateFile, $packageName)
    {
        //for not changing a lot things, the best injection point is added theme support here.
        $theme = Openbizx::$app->getCurrentTheme();
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
            Openbizx::$app->getModulePath() . "/$packagePath/template/$templateFile",
            dirname(Openbizx::$app->getModulePath() . "/$packagePath") . "/template/$templateFile",
            Openbizx::$app->getModulePath() . "/$moduleName/template/$templateFile",
            //Openbizx::$app->getModulePath()."/common/template/$templateFile",
            $templateRoot . "/$templateFile"
        );
        if ($checkExtModule && defined('MODULE_EX_PATH')) {
            array_unshift($searchTpls, MODULE_EX_PATH . "/$packagePath/template/$templateFile");
        }

        // device
        if (defined('OPENBIZ_CLIENT_DEVICE')) {
            array_unshift($searchTpls, Openbizx::$app->getModulePath() . "/$moduleName/template/" . OPENBIZ_CLIENT_DEVICE . "/$templateFile");
        }

        foreach ($searchTpls as $tplFile) {
            if (file_exists($tplFile)) {
                return $tplFile;
            }
        }
        $errmsg = MessageHelper::getMessage("UNABLE_TO_LOCATE_TEMPLATE_FILE", array($templateFile));
        trigger_error($errmsg, E_USER_ERROR);
        return null;
    }




    /**
     * Get list of default template location
     * @return array
     */
    private static function getDefaultTemplateLocations()
    {
        return array(
            Openbizx::$app->getModulePath() . "/$packagePath/template/$templateFile",
            dirname(Openbizx::$app->getModulePath() . "/$packagePath") . "/template/$templateFile",
            Openbizx::$app->getModulePath() . "/$moduleName/template/$templateFile",
            //Openbizx::$app->getModulePath()."/common/template/$templateFile",
            $templateRoot . "/$templateFile"
        );
    }


}
