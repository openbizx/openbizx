<?php
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.easy
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: ViewRenderer.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\Helpers\TemplateHelper;

/**
 * ViewRenderer class is view helper for rendering form
 *
 * @package openbiz.bin.easy
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2011
 * @access public
 */
class ViewRenderer
{

    /**
     * Render view object
     *
     * @param WebPage $webpage
     * @return string result of rendering process
     */
    static public function render($webpage)
    {
        $tplEngine = $webpage->templateEngine;
        $tplAttributes = ViewRenderer::buildTemplateAttributes($webpage);

        if (defined("OPENBIZ_PAGE_MINIFY") && OPENBIZ_PAGE_MINIFY == 1) {
            ob_start();
        }

        if ($tplEngine == "Smarty" || $tplEngine == null) {
            ViewRenderer::renderSmarty($webpage, $tplAttributes);
        } else {
            ViewRenderer::renderPHP($webpage, $tplAttributes);
        }

        if (defined("OPENBIZ_PAGE_MINIFY") && OPENBIZ_PAGE_MINIFY == 1) {
            $html = ob_get_contents();
            ob_end_clean();
            $html = self::MinifyOutput($html);
            echo $html;
        }
        return $html;
    }

    /**
     * 
     * Minify the HTML code and rewrite the code for 
     * including CSS and JS files to make it redirect to /bin/min/index.php?f
     * @param string $html
     * @return string $html
     */
    static public function MinifyOutput($html)
    {

        $minifyURL = OPENBIZ_APP_URL . "/bin/min/index.php";
        $headEnd = "</head>";

        //fetch js requests
        preg_match_all("/\<script.*?src\s?\=\s?[\"|\'](.*?\.js)[\"|\']/i", $html, $matches);
        $jsListStr = implode(array_unique($matches[1]), ',');
        $jsURL = $minifyURL . '?f=' . $jsListStr;
        $jsCode = "<script type=\"text/javascript\" src=\"$jsURL\"></script>";

        //remove old js include
        $html = preg_replace("/\<script.*?src\s?\=\s?[\"|\'].*?\.js[\"|\'].*?\<\/script\>/i", "", $html);

        //add new js include
        $html = str_replace($headEnd, $jsCode . "\n" . $headEnd, $html);

        preg_match_all("/\<link.*?href\s?\=\s?\"(.*?\.css)\"/i", $html, $matches);
        $cssListStr = implode(array_unique($matches[1]), ',');
        $cssURL = $minifyURL . '?f=' . $cssListStr;
        $cssCode = "<link rel=\"stylesheet\" href=\"$cssURL\" type=\"text/css\">";

        $html = preg_replace("/\<link.*?href\s?\=\s?\"(.*?\.css)\".*?\>/i", "", $html);
        $html = str_replace($headEnd, $cssCode . "\n" . $headEnd, $html);

        require_once OPENBIZ_APP_PATH . '/bin/min/lib/Minify/HTML.php';
        $html = Minify_HTML::minify($html);
        return $html;
    }

    /**
     * Gather all template variables needed. Should play well with Smarty or \Zend templates
     *
     * @param WebPage $viewObj
     * @return array associative array holding all needed VIEW based template variables
     */
    static public function buildTemplateAttributes($viewObj)
    {
        // Assocative Array to hold all Template Values
        // Fill with default viewobj attributes
        //$tplAttributes = $viewObj->outputAttrs();
        //Not sure what this is doing...
        $newClntObjs = '';

        //Fill other direct view variables
        $tplAttributes["module"] = $viewObj->getModuleName($viewObj->objectName);
        $tplAttributes["description"] = $viewObj->objectDescription;
        $tplAttributes["keywords"] = $viewObj->keywords;
        if (isset($viewObj->tiles)) {
            foreach ($viewObj->tiles as $tname => $tile) {
                foreach ($tile as $formRef) {
                    if ($formRef->display == false)
                        continue;
                    $tiles[$tname][$formRef->objectName] = Openbizx::getObject($formRef->objectName)->render();
                    $tiletabs[$tname][$formRef->objectName] = $formRef->objectDescription;
                }
            }
        } else {
            foreach ($viewObj->formRefs as $formRef) {
                if ($formRef->display == false)
                    continue;
                $forms[$formRef->objectName] = Openbizx::getObject($formRef->objectName)->render();
                $formtabs[$formRef->objectName] = $formRef->objectDescription;
            }
        }

        if (count($viewObj->widgets)) {
            foreach ($viewObj->widgets as $formRef) {
                if ($formRef->display == false)
                    continue;
                $widgets[$formRef->objectName] = Openbizx::getObject($formRef->objectName)->render();
            }
        }

        //Fill Loop related data
        $tplAttributes["forms"] = $forms;
        $tplAttributes["widgets"] = $widgets;
        $tplAttributes["formtabs"] = $formtabs;
        $tplAttributes["tiles"] = $tiles;
        $tplAttributes["tiletabs"] = $tiletabs;

        // add clientProxy scripts
        $includedScripts = Openbizx::$app->getClientProxy()->getAppendedScripts();
        $tplAttributes["style_sheets"] = Openbizx::$app->getClientProxy()->getAppendedStyles();
        if ($viewObj->isPopup && $bReRender == false) {
            $moveToCenter = "moveToCenter(self, " . $viewObj->width . ", " . $viewObj->height . ");";
            $tplAttributes["scripts"] = $includedScripts . "\n<script>\n" . $newClntObjs . $moveToCenter . "</script>\n";
        } else
            $tplAttributes["scripts"] = $includedScripts . "\n<script>\n" . $newClntObjs . "</script>\n";

        if ($viewObj->title)
            $tplAttributes["title"] = Expression::evaluateExpression($viewObj->title, $viewObj);
        else
            $tplAttributes["title"] = $viewObj->objectDescription;

        if (OPENBIZ_DEFAULT_SYSTEM_NAME) {
            $tplAttributes["title"] = $tplAttributes["title"] . ' - ' . OPENBIZ_DEFAULT_SYSTEM_NAME;
        }
        return $tplAttributes;
    }

    /**
     * Render smarty template for view object
     *
     * @param WebPage $webpage
     * @param string $tplFile
     * @return string result of rendering process
     */
    static protected function renderSmarty($webpage, $tplAttributes = Array())
    {
        $smarty = TemplateHelper::getSmartyTemplate();

        $viewOutput = $webpage->outputAttrs();
        foreach ($viewOutput as $k => $v) {
            $smarty->assign($k, $v);
        }
        // render the formobj attributes
        $smarty->assign("view", $viewOutput);

        //Translate Array of template variables to \Zend template object
        foreach ($tplAttributes as $key => $value) {
            $smarty->assign($key, $value);
        }
        
        //echo __METHOD__ . __LINE__. ' - ' . $webpage->templateFile . '<br />';
        //echo __METHOD__ . __LINE__. ' - ' . TemplateHelper::getTplFileWithPath($webpage->templateFile, $webpage->package) . '<br />';

        //if ($viewObj->consoleOutput) {
            $smarty->display(TemplateHelper::getTplFileWithPath($webpage->templateFile, $webpage->package));
        //} else {
        //    return $smarty->fetch(TemplateHelper::getTplFileWithPath($viewObj->templateFile, $viewObj->package));
        //}
    }

    /**
     * Render PHP template for view object
     *
     * @param EasyForm $formObj
     * @param string $tplFile
     * @return string result of rendering process
     */
    static protected function renderPHP($viewObj, $tplAttributes = Array())
    {
        $view = TemplateHelper::getZendTemplate();
        $tplFile = TemplateHelper::getTplFileWithPath($viewObj->templateFile, $viewObj->package);
        $view->addScriptPath(dirname($tplFile));

        //Translate Array of template variables to \Zend template object
        foreach ($tplAttributes as $key => $value) {
            if ($value == NULL) {
                $view->$key = '';
            } else {
                $view->$key = $value;
            }
        }
        if ($viewObj->consoleOutput)
            echo $view->render($viewObj->templateFile);
        else
            return $view->render($viewObj->templateFile);
    }

    /**
     * Set headers of view
     * 
     * @param WebPage $viewObj
     * @return void
     */
    static protected function setHeaders($viewObj)
    {
        // get the cache attribute
        // if cache = browser, set the cache control in headers
        header('Pragma:', true);
        header('Cache-Control: max-age=3600', true);
        $offset = 60 * 60 * 24 * - 1;
        $ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
        header($ExpStr, true);
    }

}

?>