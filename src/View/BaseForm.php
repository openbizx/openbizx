<?php

/**
 * BaseForm class
 *
 * @package 
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */

namespace Openbizx\View;

use Openbizx\Object\Statefullable;
use Openbizx\Helpers\MessageHelper;
use Openbizx\Core\Expression;
use Openbizx\Object\MetaObject;
use Openbizx\Easy\FormRenderer;

include_once "FormHelper.php";

class BaseForm extends MetaObject implements Statefullable
{

    // metadata vars are public, necessary for metadata inheritance
    public $title;
    public $icon;
    public $jsClass;
    public $dataObjName;
    // FormAction handles actions from client
    public $formAction;
    // FormRenderer draws data to given format (html/xml) output
    public $formRenderer;
    // FormEventManager triggers external event observers on certain events
    public $formEventManager;

    /**
     * Name of inherited form (meta-form)
     * @var string
     */
    public $inheritFrom;
    public $panels;

    /**
     * Data Panel object
     * @var Panel
     */
    public $dataPanel;

    /**
     * Action Panel object
     * @var Panel
     */
    public $actionPanel;

    /**
     * Navigation Panel object
     * @var Panel
     */
    public $navPanel;

    /**
     * Search Panel object
     * @var Panel
     */
    public $searchPanel;
    public $height;
    public $width;
    public $templateEngine;
    public $templateFile;
    public $subForms = null;
    public $cacheLifeTime = 0;
    // basic form vars
    protected $dataObj;
    public $messageFile = null;
    protected $objectMessages;
    protected $directMethodList = array(); //list of method that can directly from browser
    protected $formHelper;

    /**
     * Initialize BizForm with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
        $this->inheritParentObj();
        $this->formHelper = new FormHelper($this);
    }

    public function allowAccess($access = null)
    {
        $result = parent::allowAccess($access);
        return $result;
    }

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetadata(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->inheritFrom = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["INHERITFROM"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["INHERITFROM"] : null;
        $this->title = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["TITLE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["TITLE"] : null;
        $this->icon = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["ICON"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["ICON"] : null;
        $this->objectDescription = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["DESCRIPTION"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["DESCRIPTION"] : null;
        $this->jsClass = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["JSCLASS"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["JSCLASS"] : null;
        $this->height = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["HEIGHT"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["HEIGHT"] : null;
        $this->width = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["WIDTH"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["WIDTH"] : null;
        $this->templateEngine = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["TEMPLATEENGINE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["TEMPLATEENGINE"] : null;
        $this->templateFile = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["TEMPLATEFILE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["TEMPLATEFILE"] : null;
        $this->formType = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["FORMTYPE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["FORMTYPE"] : null;

        $this->objectName = $this->prefixPackage($this->objectName);
        if ($this->inheritFrom == '@sourceMeta')
            $this->inheritFrom = '@' . $this->objectName;
        else
            $this->inheritFrom = $this->prefixPackage($this->inheritFrom);
        $this->dataObjName = $this->prefixPackage($xmlArr["EASYFORM"]["ATTRIBUTES"]["BIZDATAOBJ"]);

        $this->dataPanel = new Panel($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"], "", $this);
        $this->actionPanel = new Panel($xmlArr["EASYFORM"]["ACTIONPANEL"]["ELEMENT"], "", $this);
        $this->navPanel = new Panel($xmlArr["EASYFORM"]["NAVPANEL"]["ELEMENT"], "", $this);
        $this->searchPanel = new Panel($xmlArr["EASYFORM"]["SEARCHPANEL"]["ELEMENT"], "", $this);
        $this->panels = array($this->dataPanel, $this->actionPanel, $this->navPanel, $this->searchPanel);

        $this->eventName = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["EVENTNAME"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["EVENTNAME"] : null;

        $this->messageFile = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["MESSAGEFILE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["MESSAGEFILE"] : null;
        $this->objectMessages = MessageHelper::loadMessage($this->messageFile, $this->package);

        $this->cacheLifeTime = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["CACHELIFETIME"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["CACHELIFETIME"] : "0";

        // parse access
        if ($this->access) {
            $arr = explode(".", $this->access);
            $this->resource = $arr[0];
        }
        if ($this->jsClass == "jbForm" && strtoupper($this->formType) == "LIST")
            $this->jsClass = "Openbizx.TableForm";
        if ($this->jsClass == "jbForm")
            $this->jsClass = "Openbizx.Form";

        $this->translate(); // translate for multi-language support
    }

    /**
     * Inherit from parent object. Name, Package, Class cannot be inherited
     *
     * @return void
     */
    protected function inheritParentObj()
    {
        if (!$this->inheritFrom)
            return;
        $parentObj = Openbizx::getObject($this->inheritFrom);

        $this->title = $this->title ? $this->title : $parentObj->title;
        $this->icon = $this->icon ? $this->icon : $parentObj->icon;
        $this->objectDescription = $this->objectDescription ? $this->objectDescription : $parentObj->objectDescription;
        $this->jsClass = $this->jsClass ? $this->jsClass : $parentObj->jsClass;
        $this->height = $this->height ? $this->height : $parentObj->height;
        $this->width = $this->width ? $this->width : $parentObj->width;
        $this->templateEngine = $this->templateEngine ? $this->templateEngine : $parentObj->templateEngine;
        $this->templateFile = $this->templateFile ? $this->templateFile : $parentObj->templateFile;
        $this->formType = $this->formType ? $this->formType : $parentObj->formType;
        $this->range = $this->range ? $this->range : $parentObj->range;
        $this->fixSearchRule = $this->fixSearchRule ? $this->fixSearchRule : $parentObj->fixSearchRule;
        $this->defaultFixSearchRule = $this->defaultFixSearchRule ? $this->defaultFixSearchRule : $parentObj->defaultFixSearchRule;
        $this->dataObjName = $this->dataObjName ? $this->dataObjName : $parentObj->dataObjName;
        $this->eventName = $this->eventName ? $this->eventName : $parentObj->eventName;
        $this->messageFile = $this->messageFile ? $this->messageFile : $parentObj->messageFile;
        $this->objectMessages = MessageHelper::loadMessage($this->messageFile, $this->package);
        $this->cacheLifeTime = $this->cacheLifeTime ? $this->cacheLifeTime : $parentObj->cacheLifeTime;

        $this->dataPanel->merge($parentObj->dataPanel);
        $this->actionPanel->merge($parentObj->actionPanel);
        $this->navPanel->merge($parentObj->navPanel);
        $this->searchPanel->merge($parentObj->searchPanel);

        if ($this->dataPanel->current()) {
            foreach ($this->dataPanel as $elem)
                $elem->adjustFormName($this->objectName);
        }
        if ($this->actionPanel->current()) {
            foreach ($this->actionPanel as $elem)
                $elem->adjustFormName($this->objectName);
        }
        if ($this->navPanel->current()) {
            foreach ($this->navPanel as $elem)
                $elem->adjustFormName($this->objectName);
        }
        if ($this->searchPanel->current()) {
            foreach ($this->searchPanel as $elem)
                $elem->adjustFormName($this->objectName);
        }
        $this->panels = array($this->dataPanel, $this->actionPanel, $this->navPanel, $this->searchPanel);
    }

// -------------------------- Session Methods ---------------------- //

    public function loadStatefullVars($sessionContext)
    {
        
    }

    public function saveStatefullVars($sessionContext)
    {
        
    }

// -------------------------- Attribute Methods ---------------------- //
    /**
     * Get message, and translate it
     *
     * @param string $messageId message Id
     * @param array $params
     * @return string message string
     */
    public function getMessage($messageId, $params = array())
    {
        $message = isset($this->objectMessages[$messageId]) ? $this->objectMessages[$messageId] : constant($messageId);
        //$message = I18n::getInstance()->translate($message);
        $message = I18n::t($message, $messageId, $this->getModuleName($this->objectName));
        $msg = @vsprintf($message, $params);
        if (!$msg) { //maybe in translation missing some %s can cause it returns null
            $msg = $message;
        }
        return $msg;
    }

    /**
     * Get object property
     * This method get element object if propertyName is "Elements[elementName]" format.
     *
     * @param string $propertyName
     * @return <type>
     */
    public function getProperty($propertyName)
    {
        $ret = parent::getProperty($propertyName);
        if ($ret !== null) {
            return $ret;
        }

        $pos1 = strpos($propertyName, "[");
        $pos2 = strpos($propertyName, "]");
        if ($pos1 > 0 && $pos2 > $pos1) {
            $propType = substr($propertyName, 0, $pos1);
            $elementName = substr($propertyName, $pos1 + 1, $pos2 - $pos1 - 1);
            $result = $this->getElement($elementName);
            return $result;
        }
    }

    /**
     * Get object instance of {@link BizDataObj} defined in it's metadata file
     *
     * @return BizDataObj
     */
    public function getDataObj()
    {
        if (!$this->dataObj) {
            if ($this->dataObjName) {
                $this->dataObj = Openbizx::getObject($this->dataObjName);
            }
            if ($this->dataObj) {
                $this->dataObj->bizFormName = $this->objectName;
            } else {
                //Openbizx::$app->getClientProxy()->showErrorMessage("Cannot get DataObj of ".$this->dataObjName.", please check your metadata file.");
                return null;
            }
        }
        return $this->dataObj;
    }

    /**
     * Set data object {@link BizDataObj} with specified instant from parameter
     *
     * @param BizDataObj $dataObj
     * @return void
     */
    final public function setDataObj($dataObj)
    {
        $this->dataObj = $dataObj;
    }

    /**
     * Get view object
     *
     * @global BizSystem $g_BizSystem
     * @return WebPage
     */
    public function getWebpageObject()
    {
        $viewName = Openbizx::$app->getCurrentViewName();
        if (!$viewName) {
            return null;
        }
        $viewObj = Openbizx::getObject($viewName);
        return $viewObj;
    }

    /**
     * Get sub form of this form
     *
     * @return EasyForm
     */
    public function getSubForms()
    {
        // ask view to give its subforms if not set yet
        return $this->subForms;
    }

    /**
     * Get an element object
     *
     * @param string $elementName - name of the control
     * @return Element
     */
    public function getElement($elementName)
    {
        if ($this->dataPanel->get($elementName)) {
            return $this->dataPanel->get($elementName);
        }
        if ($this->actionPanel->get($elementName)) {
            return $this->actionPanel->get($elementName);
        }
        if ($this->navPanel->get($elementName)) {
            return $this->navPanel->get($elementName);
        }
        if ($this->searchPanel->get($elementName)) {
            return $this->searchPanel->get($elementName);
        }
        if ($this->wizardPanel) {
            if ($this->wizardPanel->get($elementName))
                return $this->wizardPanel->get($elementName);
        }
    }

    /**
     * Get error elements
     *
     * @param array $fields
     * @return array
     */
    public function getErrorElements($fields)
    {
        $errElements = array();
        foreach ($fields as $field => $error) {
            $element = $this->dataPanel->getByField($field);
            $errElements[$element->objectName] = $error;
        }
        return $errElements;
    }

    public function setRecordId($val)
    {
        $this->recordId = $val;
    }

    public function setFormInputs($inputArr = null)
    {
        if (!$inputArr) {
            $inputArr = $this->formInputs;
        }
        if (!is_array($inputArr)) {
            $inputArr = array();
        }
        foreach ($this->dataPanel as $element) {
            if (isset($inputArr[$element->objectName])) {
                $element->setValue($inputArr[$element->objectName]);
            }
        }

        foreach ($this->searchPanel as $element) {
            if (isset($inputArr[$element->objectName])) {
                $element->setValue($inputArr[$element->objectName]);
            }
        }
        return $inputArr;
    }

// -------------------------- Render Methods ---------------------- //
    /**
     * Render this form (return html content),
     * called by WebPage's render method (called when form is loaded).
     * Query is issued before returning the html content.
     *
     * @return string - HTML text of this form's read mode
     * @example ../../../example/FormObject.php
     */
    public function render()
    {
        if (!$this->allowAccess())
            return "";
        //$this->setClientScripts();

        if ($this->cacheLifeTime > 0 && $this->subForms == null) {
            $cache_id = md5($this->objectName);
            //try to process cache service.
            $cacheSvc = Openbizx::getService(CACHE_SERVICE, 1);
            $cacheSvc->init($this->objectName, $this->cacheLifeTime);
            if ($cacheSvc->test($cache_id)) {
                Openbizx::$app->getLog()->log(LOG_DEBUG, "FORM", "Cache Hit. form name = " . $this->objectName);
                $output = $cacheSvc->load($cache_id);
            } else {
                Openbizx::$app->getLog()->log(LOG_DEBUG, "FORM", "Set cache. form name = " . $this->objectName);
                $output = FormRenderer::render($this);
                $cacheSvc->save($output, $cache_id);
            }
            return $output;
        }

        //Moved the renderHTML function infront of declaring subforms
        $output = FormRenderer::render($this);

        // lazy subform loading - prepare the subforms' dataobjs, since the subform relates to parent form by dataobj association
        $this->prepareSubFormsDataObj();

        return $output;
    }

    /**
     * Rerender this form (form is rendered already) .
     *
     * @param boolean $redrawForm - whether render this form again or not, optional default true
     * @param boolean $hasRecordChange - if record change, need to render subforms, optional default true
     * @return string - HTML text of this form's read mode
     */
    public function rerender($redrawForm = true, $hasRecordChange = true)
    {
        if ($redrawForm) {
            Openbizx::$app->getClientProxy()->redrawForm($this->objectName, FormRenderer::render($this));
        }

        if ($hasRecordChange) {
            $this->rerenderSubForms();
        }
    }

    /**
     * Rerender sub forms who has dependecy on this form.
     * This method is called when parent form's change affect the sub forms
     *
     * @return void
     */
    protected function rerenderSubForms()
    {
        if (!$this->subForms) {
            return;
        }
        $this->prepareSubFormsDataObj();
        foreach ($this->subForms as $subForm) {
            $formObj = Openbizx::getObject($subForm);
            $formObj->rerender();
        }
        return;
    }

    protected function prepareSubFormsDataObj()
    {
        if ($this->subForms && $this->getDataObj()) {
            foreach ($this->subForms as $subForm) {
                $formObj = Openbizx::getObject($subForm);
                $dataObj = $this->getDataObj()->getRefObject($formObj->dataObjName);
                if ($dataObj) {
                    $formObj->setDataObj($dataObj);
                }
            }
        }
    }

    /**
     * Get output attributs as array
     *
     * @return array array of attributs
     * @todo rename to getOutputAttribute or getAttribute (2.5?)
     */
    public function outputAttrs()
    {
        $output['name'] = $this->objectName;
        $output['title'] = Expression::evaluateExpression($this->title, $this);
        $output['icon'] = $this->icon;
        return $output;
    }

// -------------------------- Misc Methods ---------------------- //

    public function setRequestParams($paramFields)
    {
        
    }

    /**
     * Validate request from client (browser)
     * 
     * @param string $methodName called from the client
     * @return boolean
     */
    public function validateRequest($methodName)
    {
        $methodName = strtolower($methodName);
        foreach ($this->directMethodList as $value) {
            if ($methodName == $value)
                return true;
        }
    }

    /**
     * Get activeRecord
     *
     * @param mixed $recId
     * @return array - record array
     */
    public function getActiveRecord($recId = null)
    {
        if ($this->activeRecord != null) {
            if ($this->activeRecord['Id'] != null) {
                return $this->activeRecord;
            }
        }

        if ($recId == null || $recId == '')
            $recId = Openbizx::$app->getClientProxy()->getFormInputs('_selectedId');
        if ($recId == null || $recId == '')
            return null;
        $this->recordId = $recId;

        // TODO: may consider cache the current record in session or pass the record from client
        if ($this->getDataObj()) {
            $this->getDataObj()->setActiveRecordId($this->recordId);
            $rec = $this->getDataObj()->getActiveRecord();

            // update the record row
            $this->dataPanel->setRecordArr($rec);

            $this->activeRecord = $rec;
        }
        return $rec;
    }

    /**
     * Set active record
     *
     * @param array $record
     * @return void
     */
    protected function setActiveRecord($record)
    {
        // update the record row
        $this->dataPanel->setRecordArr($record);

        foreach ($record as $key => $value) {
            //if($key=='extend')continue;
            $this->activeRecord[$key] = $record[$key];
        }
    }

    /**
     * Switch to other form
     *
     * @param string $formName to-be-swtiched form name. if empty, then switch to default form
     * @param string $id id value of the target form
     * @return void
     * @access remote
     */
    public function switchForm($formName = null, $id = null)
    {
        $this->formHelper->switchForm($formName, $id);
    }

    public function loadDialog($formName = null, $id = null)
    {
        $this->formHelper->loadDialog($formName, $id);
    }

// -------------------------- Tranlation Methods ---------------------- //

    protected function translate()
    {
        $module = $this->getModuleName($this->objectName);
        if (!empty($this->title)) {
            $trans_string = I18n::t($this->title, $this->getTransKey('Title'), $module, $this->getTransPrefix());
            if ($trans_string) {
                $this->title = $trans_string;
            }
        }
        if (!empty($this->icon)) {
            $trans_string = I18n::t($this->icon, $this->getTransKey('Icon'), $module, $this->getTransPrefix());
            if ($trans_string) {
                $this->icon = $trans_string;
            }
        }
        if (!empty($this->objectDescription)) {
            $trans_string = I18n::t($this->objectDescription, $this->getTransKey('Description'), $module, $this->getTransPrefix());
            if ($trans_string) {
                $this->objectDescription = $trans_string;
            }
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

?>