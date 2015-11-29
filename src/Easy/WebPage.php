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
 * @version   $Id: WebPage.php 3614 2011-04-07 05:34:25Z jixian2003 $
 */

namespace Openbizx\Easy;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\Object\MetaIterator;
use Openbizx\Object\Statefullable;
use Openbizx\Object\MetaObject;
use Openbizx\I18n\I18n;
use Openbizx\Helpers\MessageHelper;
/**
 * WebPage class is the class that contains list of forms.
 * View is same as html page.
 *
 * @package openbiz.bin.easy
 * @author rocky swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class WebPage extends MetaObject implements Statefullable
{

    public $title;
    public $keywords;
    public $templateEngine;
    public $templateFile;
    public $viewSet;
    public $tab;
    public $formRefs;
    public $widgets;
    public $isPopup = false;
    public $height;
    public $width;
    public $consoleOutput = true;
    public $messageFile = null;        // message file path
    protected $objectMessages;
    public $cacheLifeTime = 0;
    public $lastRenderedForm;

    public $formRefLibs;

    private $_app;

    /**
     * Initialize WebPage with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    public function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
    }

    /**
     * Read Metadata from xml array
     * 
     * @param array $xmlArr
     * @return void
     */
    protected function readMetadata(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->objectName = $this->prefixPackage($this->objectName);
        $this->title = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["TITLE"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["TITLE"] : null;
        $this->keywords = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["KEYWORDS"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["KEYWORDS"] : null;
        $this->templateEngine = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["TEMPLATEENGINE"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["TEMPLATEENGINE"] : null;
        $this->templateFile = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["TEMPLATEFILE"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["TEMPLATEFILE"] : null;
        //echo __METHOD__. '-' . __LINE__ . ' templateFile : ' . $this->templateFile . '<br />';
        $this->viewSet = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["VIEWSET"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["VIEWSET"] : null;
        $this->tab = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["TAB"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["TAB"] : null;

        $this->formRefs = new MetaIterator($xmlArr["WEBPAGE"]["FORMREFERENCES"]["REFERENCE"], "Openbizx\Easy\FormReference", $this);
        
        if ($xmlArr["WEBPAGE"]["FORMREFERENCELIBS"]) {
            $this->formRefLibs = new MetaIterator($xmlArr["WEBPAGE"]["FORMREFERENCELIBS"]["REFERENCE"], "Openbizx\Easy\FormReference", $this);
        }
        if ($xmlArr["WEBPAGE"]["WIDGETS"]) {
            $this->widgets = new MetaIterator($xmlArr["WEBPAGE"]["WIDGETS"]["REFERENCE"], "Openbizx\Easy\FormReference", $this);
        }
        $this->messageFile = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["MESSAGEFILE"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["MESSAGEFILE"] : null;
        $this->objectMessages = MessageHelper::loadMessage($this->messageFile, $this->package);
        $this->cacheLifeTime = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["CACHELIFETIME"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["CACHELIFETIME"] : "0";

        $this->readTile($xmlArr); // TODO: is this needed as title supports expression?

        $this->translate(); // translate for multi-language support
        if (empty($this->title))
            $this->title = $this->objectDescription;
    }

    protected function readTile(&$xmlArr)
    {
        if (isset($xmlArr["WEBPAGE"]["TILE"])) {
            $this->formRefs = array();
            if (isset($xmlArr["WEBPAGE"]["TILE"]["ATTRIBUTES"])) {
                $tileName = $xmlArr["WEBPAGE"]["TILE"]["ATTRIBUTES"]["NAME"];
                $this->tiles[$tileName] = new MetaIterator($xmlArr["WEBPAGE"]["TILE"]["REFERENCE"], "Openbizx\Easy\FormReference", $this);
            } else {
                foreach ($xmlArr["WEBPAGE"]["TILE"] as $child) {
                    $tileName = $child["ATTRIBUTES"]["NAME"];
                    $this->tiles[$tileName] = new MetaIterator($child["REFERENCE"], "Openbizx\Easy\FormReference", $this);
                }
            }
            //echo "<pre>"; print_r($this->tiles); echo "</pre>";
            $tmp = array();
            $this->formRefs = new MetaIterator($tmp, "", $this);
            foreach ($this->tiles as $tile) {
                foreach ($tile as $ref)
                    $this->formRefs->set($ref->objectName, $ref);
            }
        }
    }

    /**
     * Check the Form is in the lib
     *
     * @param string $formName form name     
     * @return bool inside or not
     */
    public function isInFormRefLibs($formName)
    {
        if ($this->formRefLibs) {
            $this->formRefLibs->rewind();
            while ($this->formRefLibs->valid()) {
                $reference = $this->formRefLibs->current();
                if ($reference->objectName == $formName) {
                    return true;
                }
                $this->formRefLibs->next();
            }
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get message, and translate it
     *
     * @param <type> $msgId message Id
     * @param array $params
     * @return string message string
     */
    protected function getMessage($msgId, $params = array())
    {
        $message = isset($this->objectMessages[$msgId]) ? $this->objectMessages[$msgId] : constant($msgId);
        //$message = I18n::getInstance()->translate($message);
        $message = I18n::t($message, $msgId, $this->getModuleName($this->objectName));
        return vsprintf($message, $params);
    }

    /**
     * Get/Retrieve Session data of this object
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function loadStatefullVars($sessionContext)
    {
        $sessionContext->loadObjVar($this->objectName, "LastRenderedForm", $this->lastRenderedForm);
    }

    /**
     * Save Session data of this object
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function saveStatefullVars($sessionContext)
    {
        $sessionContext->saveObjVar($this->objectName, "LastRenderedForm", $this->lastRenderedForm);
    }

    /**
     * Get view set name
     *
     * @return mixed viewset name or null
     */
    public function getViewSet()
    {
        return $this->viewSet;
    }

    /**
     * Set the Render output to console (as calling print ...) or to a string buffer
     *
     * @param boolean $consoleOutput
     * @return void
     */
    public function setConsoleOutput($consoleOutput)
    {
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * Proses rule
     *
     * @return void
     */
    public function processRule()
    {

    }

    /**
     * Set parameters
     *
     * @return void
     */
    public function setParameters()
    {

    }

    /**
     * Render this view.
     *
     * @return mixed either print html content, or return html content
     * @example ../../../example/ViewObject.php     
     */
    public function render()
    {
        if (!$this->allowAccess()) {
            $accessDenyView = Openbizx::getObject(OPENBIZ_ACCESS_DENIED_VIEW);
            return $accessDenyView->render();
        }
        $this->initAllForms();
        // check the "fld_..." arg in url and put it in the search rule
        $this->processRequest();
        return $this->_render();
    }

    /**
     * Render this view. This function is called by Render() or ReRender()
     *
     * @return mixed either print html content or return html content if called by Render(), or void if called by ReRender()
     */
    protected function _render()
    {
        if ($this->cacheLifeTime > 0) {
            $pageUrl = $this->curPageURL();
            $cache_id = md5($pageUrl);
            //try to process cache service.
            $cacheSvc = Openbizx::getService(CACHE_SERVICE, 1);
            $cacheSvc->init($this->objectName, $this->cacheLifeTime);
            $this->consoleOutput = false;
            $output = ViewRenderer::render($this);
            Openbizx::$app->getLog()->log(LOG_DEBUG, "VIEW", "Set cache. url = " . $pageUrl);
            $cacheSvc->save($output, $cache_id);
            print $output;
        } else {
            $this->setClientScripts();
            ViewRenderer::render($this);
        }
        return;
    }

    /**
     * Get current page URL
     * NOTE:
     * This method on next version maybe removed.
     * New method is {@link getCurrentPageUrl}
     * 
     * @return string page URL
     */
    public function curPageURL()
    {
        return $this->getCurrentPageUrl();
    }

    /**
     * Get current page URL
     *
     * @return string page URL
     */
    public function getCurrentPageUrl()
    {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

    /**
     * Set default client javascript and css that included in the html content
     *
     * @return void
     */
    protected function setClientScripts()
    {
        Openbizx::$app->getClientProxy()->includeBaseClientScripts();
    }

    /**
     * Initialize all form objects.
     *
     * @return void
     */
    protected function initAllForms()
    {
        foreach ($this->formRefs as $formRef) {
            $formRef->setViewName($this->objectName);
            $formName = $formRef->objectName;
            $formObj = Openbizx::getObject($formName);
            if ($formRef->subForms && method_exists($formObj, "SetSubForms")) {
                $formObj->setSubForms($formRef->subForms);
            }
        }
    }

    /**
     * Process request
     *
     * @return void
     */
    protected function processRequest()
    {
        // if url has form=...
        $paramForm = isset($_REQUEST['form']) ? $_REQUEST['form'] : null;
        
        // check url arg as fld:name=val
        $getKeys = array_keys($_REQUEST);
        $pageid = $_GET["pageid"];

        $paramFields = null;
        foreach ($getKeys as $key) {
            if (substr($key, 0, 4) == "fld:") {
                $fieldName = substr($key, 4);
                $fieldValue = $_REQUEST[$key];
                $paramFields[$fieldName] = $fieldValue;
            }
        }

        if (!$paramFields && !$pageid) {
            return;
        }

        // get the form object
        if (!$paramForm) { // get the first form name if no form is given
            foreach ($this->formRefs as $formRef) {
                $paramForm = $formRef->objectName;
                break;
            }
        }
        if (!$paramForm) {
            return;
        }
        $paramForm = $this->prefixPackage($paramForm);
        $formObj = Openbizx::getObject($paramForm);
        $formObj->setRequestParams($paramFields);
        if ($pageid) {
            $formObj->setCurrentPage($pageid);
        }
    }

    /**
     * Get output attributs
     * 
     * @return array
     * @todo need to raname to getOutputAttributs() or getAttributes
     */
    public function outputAttrs()
    {
        $out['name'] = $this->objectName;
        $out['module'] = $this->getModuleName($this->objectName);
        $out['description'] = $this->objectDescription;
        $out["keywords"] = $this->keywords;
        if ($this->title) {
            $title = Expression::evaluateExpression($this->title, $this);
        } else {
            $title = $this->objectDescription;
        }
        $out['title'] = $title;
        return $out;
    }

    protected function translate()
    {
        $module = $this->getModuleName($this->objectName);
        $trans_string = I18n::t($this->title, $this->getTransKey('Title'), $module, $this->getTransPrefix());
        if ($trans_string) {
            $this->title = $trans_string;
        }
        $trans_string = I18n::t($this->objectDescription, $this->getTransKey('Description'), $module, $this->getTransPrefix());
        if ($trans_string) {
            $this->objectDescription = $trans_string;
        }
    }

    protected function getTransPrefix()
    {
        $nameArr = explode(".", $this->objectName);
        for ($i = 1; $i < count($nameArr) - 1; $i++) {
            $prefix .= strtoupper($nameArr[$i]) . "_";
        }
        return $prefix;
    }

    protected function getTransKey($name)
    {
        $shortFormName = substr($this->objectName, intval(strrpos($this->objectName, '.')) + 1);
        return strtoupper($shortFormName . '_' . $name);
    }

}

