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
 * @version   $Id: EasyForm.php 4203 2011-06-01 07:33:23Z rockys $
 */

namespace Openbizx\Easy;

use Openbizx\Openbizx;
use Openbizx\Core\ErrorHandler;
use Openbizx\Core\Expression;
use Openbizx\I18n\I18n;
use Openbizx\Object\MetaObject;
use Openbizx\Object\Statefullable;
use Openbizx\Helpers\MessageHelper;
use Openbizx\Data\DataRecord;

use Openbizx\Easy\Panel;
use Openbizx\Easy\FormRenderer;
use Openbizx\Validation\Exception as ValidationException;

/**
 * EasyForm class - contains form object metadata functions
 *
 * @package openbiz.bin.easy
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class EasyForm extends MetaObject implements Statefullable
{

    public $DATAFORMAT = 'RECORD';
    // metadata vars are public, necessary for metadata inheritance
    public $title;
    public $icon;
    public $objectDescription;
    public $jsClass;
    public $dataObjName;
    public $height;
    public $width;
    public $defaultForm;
    public $canUpdateRecord;
    public $directMethodList = null; //list of method that can directly from browser
    public $panels;

    public $removeall_sck = false;
    
    /**
     * used by sub-class
     * @var type 
     */
    public $searchRuleBindValues;

    public $clearSearchRule;

    /**
     * Name of inherited form (meta-form)
     *
     * @var string
     */
    public $inheritFrom;

    /**
     * Data Panel object
     *
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
    public $templateEngine;
    public $templateFile;
    public $formType;
    public $subForms = null;
    public $eventName;
    public $range = 10;
    public $cacheLifeTime = 0;
    public $formParams;
    // parent form is the form that trigger the popup. "this" form is a popup form
    public $parentFormName;
    // the form that drives navigation - the 1st form deplayed in the view
    public $defaultFormName = null;
    public $errors;   // errors array (error_element, error_message)
    public $notices;  // list of notice messages
    // basic form vars
    protected $dataObj;
    protected $recordId = null;
    public $activeRecord = null;
    public $formInputs = null;
    public $searchRule = null;
    public $fixSearchRule = null; // FixSearchRule is the search rule always applying on the search
    public $sortRule = null;
    protected $defaultFixSearchRule = null;
    protected $referer = "";
    public $messageFile = null;
    protected $hasError = false;
    protected $validateErrors = array();
    protected $queryParams = array();
    // vars for grid(list)
    protected $currentPage = 1;
    protected $startItem = 1;
    public $totalPages = 1;
    protected $totalRecords = 0;
    protected $recordSet = null;
    protected $isRefreshData = false;
    protected $resource = "";
    protected $objectMessages;
    protected $invokingElement = null;
    public $autoRefresh = 0;
    public $referenceFormName; //switch from which form
    protected $recordAllowAccess = true;

    public $searchPanelValues;

    public $staticOutput = false;

    /**
     * Initialize BizForm with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
        //echo $_GET['referer'];
        $this->inheritParentObj();
    }

    public function allowAccess($access = null)
    {
        if (!$this->recordAllowAccess) {
            /**
             * if the record is now allowed to access, then deny form render
             * instead of display an empty form
             */
            return false;
        }
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
        $this->defaultForm = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["DEFAULTFORM"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["DEFAULTFORM"] : null;
        $this->templateEngine = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["TEMPLATEENGINE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["TEMPLATEENGINE"] : null;
        $this->templateFile = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["TEMPLATEFILE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["TEMPLATEFILE"] : null;
        $this->formType = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["FORMTYPE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["FORMTYPE"] : null;
        $this->range = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["PAGESIZE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["PAGESIZE"] : $this->range;
        $this->fixSearchRule = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["SEARCHRULE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["SEARCHRULE"] : null;
        $this->sortRule = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["SORTRULE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["SORTRULE"] : null;
        $this->defaultFixSearchRule = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["SEARCHRULE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["SEARCHRULE"] : null;

        $this->objectName = $this->prefixPackage($this->objectName);
        if ($this->inheritFrom == '@sourceMeta') {
            $this->inheritFrom = '@' . $this->objectName;
        } else
            $this->inheritFrom = $this->prefixPackage($this->inheritFrom);
        $this->dataObjName = $this->prefixPackage($xmlArr["EASYFORM"]["ATTRIBUTES"]["BIZDATAOBJ"]);

        if (isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["DIRECTMETHOD"])) {
            $this->directMethodList = explode(",", strtolower(str_replace(" ", "", $xmlArr["EASYFORM"]["ATTRIBUTES"]["DIRECTMETHOD"])));
        }
        $this->dataPanel = new Panel($xmlArr["EASYFORM"]["DATAPANEL"]["ELEMENT"], "", $this);
        $this->actionPanel = new Panel($xmlArr["EASYFORM"]["ACTIONPANEL"]["ELEMENT"], "", $this);
        $this->navPanel = new Panel($xmlArr["EASYFORM"]["NAVPANEL"]["ELEMENT"], "", $this);
        $this->searchPanel = new Panel($xmlArr["EASYFORM"]["SEARCHPANEL"]["ELEMENT"], "", $this);
        $this->panels = array($this->dataPanel, $this->actionPanel, $this->navPanel, $this->searchPanel);

        $this->formType = strtoupper($this->formType);

        $this->eventName = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["EVENTNAME"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["EVENTNAME"] : null;

        $this->messageFile = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["MESSAGEFILE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["MESSAGEFILE"] : null;
        $this->objectMessages = MessageHelper::loadMessage($this->messageFile, $this->package);

        $this->cacheLifeTime = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["CACHELIFETIME"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["CACHELIFETIME"] : "0";

        $this->currentPage = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["STARTPAGE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["STARTPAGE"] : 1;
        $this->startItem = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["STARTITEM"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["STARTITEM"] : 1;

        $this->autoRefresh = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["AUTOREFRESH"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["AUTOREFRESH"] : 0;
        $this->stateless = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["AUTOREFRESH"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["STATELESS"] : 0;

        // parse access
        if ($this->access) {
            $arr = explode(".", $this->access);
            $this->resource = $arr[0];
        }
        if ($this->jsClass == "jbForm" && strtoupper($this->formType) == "LIST") {
            $this->jsClass = "Openbizx.TableForm";
        }
        if ($this->jsClass == "jbForm") {
            $this->jsClass = "Openbizx.Form";
        }

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
        $this->defaultForm = $this->defaultForm ? $this->defaultForm : $parentObj->defaultForm;
        $this->templateEngine = $this->templateEngine ? $this->templateEngine : $parentObj->templateEngine;
        $this->templateFile = $this->templateFile ? $this->templateFile : $parentObj->templateFile;
        $this->formType = $this->formType ? $this->formType : $parentObj->formType;
        $this->range = $this->range ? $this->range : $parentObj->range;
        $this->fixSearchRule = $this->fixSearchRule ? $this->fixSearchRule : $parentObj->fixSearchRule;
        $this->defaultFixSearchRule = $this->defaultFixSearchRule ? $this->defaultFixSearchRule : $parentObj->defaultFixSearchRule;
        $this->dataObjName = $this->dataObjName ? $this->dataObjName : $parentObj->dataObjName;
        $this->directMethodList = $this->directMethodList ? $this->directMethodList : $parentObj->directMethodList;
        $this->eventName = $this->eventName ? $this->eventName : $parentObj->eventName;
        $this->messageFile = $this->messageFile ? $this->messageFile : $parentObj->messageFile;
        $this->objectMessages = MessageHelper::loadMessage($this->messageFile, $this->package);
        $this->cacheLifeTime = $this->cacheLifeTime ? $this->cacheLifeTime : $parentObj->cacheLifeTime;
        $this->currentPage = $this->currentPage ? $this->currentPage : $parentObj->currentPage;
        $this->startItem = $this->startItem ? $this->startItem : $parentObj->startItem;

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

    public function canDisplayForm()
    {

        if ($this->getDataObj()->dataPermControl == 'Y') {
            switch (strtolower($this->formType)) {
                default:
                case 'list':
                    return true;
                    break;
                case 'detail':
                    $permCode = 1;
                    break;

                case 'edit':
                    $permCode = 2;
                    break;
            }
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            $result = $svcObj->checkDataPerm($this->fetchData(), $permCode, $this->getDataObj());
            if ($result == false) {
                return false;
            }
        }
        return true;
    }

    public function canDeleteRecord($rec)
    {

        if ($this->getDataObj()->dataPermControl == 'Y') {
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            $result = $svcObj->checkDataPerm($rec, 3, $this->getDataObj());
            if ($result == false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get/Retrieve Session data of this object
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function loadStatefullVars($sessionContext)
    {
        $sessionContext->loadObjVar($this->objectName, "RecordId", $this->recordId);
        $sessionContext->loadObjVar($this->objectName, "FixSearchRule", $this->fixSearchRule);
        $sessionContext->loadObjVar($this->objectName, "SearchRule", $this->searchRule);
        $sessionContext->loadObjVar($this->objectName, "QueryParams", $this->queryParams);
        $sessionContext->loadObjVar($this->objectName, "SubForms", $this->subForms);
        $sessionContext->loadObjVar($this->objectName, "ParentFormName", $this->parentFormName);
        $sessionContext->loadObjVar($this->objectName, "DefaultFormName", $this->defaultFormName);
        $sessionContext->loadObjVar($this->objectName, "CurrentPage", $this->currentPage);
        $sessionContext->loadObjVar($this->objectName, "PageSize", $this->range);
        $sessionContext->loadObjVar($this->objectName, "ReferenceFormName", $this->referenceFormName);
        $sessionContext->loadObjVar($this->objectName, "SearchPanelValues", $this->searchPanelValues);
    }

    /**
     * Save object variable to session context
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function saveStatefullVars($sessionContext)
    {
        $sessionContext->saveObjVar($this->objectName, "RecordId", $this->recordId);
        $sessionContext->saveObjVar($this->objectName, "FixSearchRule", $this->fixSearchRule);
        $sessionContext->saveObjVar($this->objectName, "SearchRule", $this->searchRule);
        $sessionContext->saveObjVar($this->objectName, "QueryParams", $this->queryParams);
        $sessionContext->saveObjVar($this->objectName, "SubForms", $this->subForms);
        $sessionContext->saveObjVar($this->objectName, "ParentFormName", $this->parentFormName);
        $sessionContext->saveObjVar($this->objectName, "DefaultFormName", $this->defaultFormName);
        $sessionContext->saveObjVar($this->objectName, "CurrentPage", $this->currentPage);
        $sessionContext->saveObjVar($this->objectName, "PageSize", $this->range);
        $sessionContext->saveObjVar($this->objectName, "ReferenceFormName", $this->referenceFormName);
        $sessionContext->saveObjVar($this->objectName, "SearchPanelValues", $this->searchPanelValues);
    }

    /**
     * Invoke the action passed from browser
     *
     * @return mixed the function result, or false on error.
     */
    public function invoke()
    {
        $argList = func_get_args();
        $param1 = array_shift($argList);
        // first one is element:eventhandler
        list ($elementName, $eventHandlerName) = explode(":", $param1);
        $element = $this->getElement($elementName);
        $eventHandler = $element->eventHandlers->get($eventHandlerName);
        $this->invokingElement = array($element, $eventHandler);
        // find the matching function
        list($funcName, $funcParams) = $eventHandler->parseFunction($eventHandler->origFunction);
        // call the function with rest parameters
        return call_user_func_array(array($this, $funcName), $argList);
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

        if ($methodName == "selectrecord" || $methodName == "invoke" || $methodName = "sortrecord")
            return true;
        // element, eventhandler
        list($element, $eventHandler) = $this->getInvokingElement();
        if ($element && $eventHandler) {
            if (stripos($eventHandler->origFunction, $methodName) === 0)
                return true;
        }
        // scan elements to match method
        foreach ($this->panels as $panel) {
            foreach ($panel as $elem)
                if ($elem->matchRemoteMethod($methodName))
                    return true;
        }

        if (is_array($this->directMethodList)) {
            foreach ($this->directMethodList as $value) {
                if ($methodName == $value)
                    return true;
            }
        }

        return false;
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
        if ($ret !== null)
            return $ret;

        $pos1 = strpos($propertyName, "[");
        $pos2 = strpos($propertyName, "]");
        if ($pos1 > 0 && $pos2 > $pos1) {
            $propType = substr($propertyName, 0, $pos1);
            $elementName = substr($propertyName, $pos1 + 1, $pos2 - $pos1 - 1);
            switch (strtolower($propType)) {
                case 'param':
                case 'params':
                    $result = $this->formParams[$elementName];
                    break;
                default:

                    $result = $this->getElement($elementName);
                    break;
            }
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
                //DebugLine::show(__METHOD__.__LINE__);
                $this->dataObj = Openbizx::getObject($this->dataObjName);
            }
            if ($this->dataObj) {
                //DebugLine::show(__METHOD__.__LINE__);
                $this->dataObj->bizFormName = $this->objectName;
            } else {
                //DebugLine::show(__METHOD__.__LINE__);
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
        $output['hasSubform'] = $this->subForms ? 1 : 0;
        $output['currentPage'] = $this->currentPage;
        $output['currentRecordId'] = $this->recordId;
        $output['totalPages'] = $this->totalPages;
        $output['totalRecords'] = $this->totalRecords;
        $output['description'] = str_replace('\n', "<br />", Expression::evaluateExpression($this->objectDescription, $this));
        $output['elementSets'] = $this->getElementSet();
        $output['tabSets'] = $this->getTabSet();
        $output['ActionElementSets'] = $this->getElementSet($this->actionPanel);
        if ($output['icon']) {
            if (preg_match("/{.*}/si", $output['icon'])) {
                $output['icon'] = Expression::evaluateExpression($output['icon'], null);
            } else {
                $output['icon'] = OPENBIZ_THEME_URL . "/" .Openbizx::$app->getCurrentTheme() . "/images/" . $output['icon'];
            }
        }
        return $output;
    }

    /**
     * Handle the error from {@link BizDataObj::getErrorMessage} method,
     * report the error as an alert window and log.
     *
     * @param int $errCode
     * @return void
     */
    public function processDataObjError($errCode = 0)
    {
        $errorMsg = $this->getDataObj()->getErrorMessage();
        Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "DataObj error = " . $errorMsg);
        Openbizx::$app->getClientProxy()->showErrorMessage($errorMsg);
    }

    /**
     * Process error of form object
     *
     * @param array $errors
     * @return string - HTML text of this form's read mode
     */
    public function processFormObjError($errors)
    {
        $this->errors = $errors;
        $this->hasError = true;
        return $this->rerender();
    }

    /**
     * Handle the exception from DataObj method,
     *  report the error as an alert window
     *
     * @param int $errCode
     * @return string
     */
    public function processDataException($e)
    {
        $errorMsg = $e->getMessage();
        Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "DataObj error = " . $errorMsg);
        //Openbizx::$app->getClientProxy()->showClientAlert($errorMsg);   //showErrorMessage($errorMsg);
        //Openbizx::$app->getClientProxy()->showErrorMessage($errorMsg);	
        $e->no_exit = true;
        ErrorHandler::exceptionHandler($e);
    }

    /**
     * Set the sub forms of this form. This form is parent of other forms
     *
     * @param string $subForms - sub controls string with format: ctrl1;ctrl2...
     * @return void
     */
    final public function setSubForms($subForms)
    {
        // sub controls string with format: ctrl1;ctrl2...
        if (!$subForms || strlen($subForms) < 1) {
            $this->subForms = null;
            return;
        }
        $subFormArr = explode(";", $subForms);
        unset($this->subForms);
        foreach ($subFormArr as $subForm) {
            $this->subForms[] = $this->prefixPackage($subForm);
        }
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
        if (!$viewName)
            return null;
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
        if (isset($this->wizardPanel)) {
            if ($this->wizardPanel->get($elementName)) {
                return $this->wizardPanel->get($elementName);
            }
        }
    }

    public function getElementSet($panel = null)
    {
        if (!$panel) {
            $panel = $this->dataPanel;
        }
        $setArr = array();
        $panel->rewind();
        while ($panel->valid()) {
            $elem = $panel->current();
            $panel->next();
            if ($elem->elementSet && $elem->canDisplayed()) {
                //is it in array
                if (in_array($elem->elementSet, $setArr)) {
                    continue;
                } else {
                    array_push($setArr, $elem->elementSet);
                }
            }
        }
        return $setArr;
    }

    public function getTabSet($panel = null)
    {
        if (!$panel) {
            $panel = $this->dataPanel;
        }
        $setArr = array();
        $tabSetArr = array();
        $panel->rewind();
        while ($panel->valid()) {
            $elem = $panel->current();
            $panel->next();
            if ($elem->tabSet && $elem->canDisplayed()) {
                //is it in array
                if (in_array($elem->tabSet, $setArr)) {
                    continue;
                } else {
                    $setArr[$elem->tabSetCode] = $elem->tabSet;
                }
            }
        }
        foreach ($setArr as $tabsetCode => $tabset) {
            $elemSetArr = array();
            $panel->rewind();
            while ($panel->valid()) {
                $elem = $panel->current();
                $panel->next();
                if ($elem->elementSet && $elem->canDisplayed()) {
                    //is it in array
                    if ($elem->tabSetCode != $tabsetCode ||
                            in_array($elem->elementSet, $elemSetArr)) {
                        continue;
                    } else {
                        array_push($elemSetArr, $elem->elementSet);
                    }
                }
            }
            $tabSetArr[$tabsetCode]['SetName'] = $tabset;
            $tabSetArr[$tabsetCode]['Elems'] = $elemSetArr;
        }
        return $tabSetArr;
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

    /**
     * Popup a selection EasyForm in a dynamically generated WebPage
     *
     * @param string $viewName
     * @param string $formName
     * @param string $elementName
     * @return void
     * @access remote
     */
    public function loadPicker($formName, $elementName = "")
    {
        // set the ParentFormName and ParentCtrlName of the popup form
        /* @var $pickerForm EasyForm */
        $pickerForm = Openbizx::getObject($formName);

        if ($elementName != "") {
            // set the picker map as well
            $element = $this->getElement($elementName);
            $pickerMap = $element->pickerMap;
        }
        $currentRecord = $this->readInputRecord();
        $pickerForm->setParentForm($this->objectName);
        $pickerForm->setParentFormData($this->objectName, $elementName, $pickerMap);
        $pickerForm->parentFormRecord = $currentRecord;
        Openbizx::$app->getClientProxy()->redrawForm("DIALOG", $pickerForm->render());
    }

    public function loadDialog($formName, $id = null, $transId = false)
    {
        $paramFields = array();
        if ($id != null)
            $paramFields["Id"] = $id;
        if ($transId != false)
            $paramFields["Id"] = $this->recordId;
        $this->_showForm($formName, "Dialog", $paramFields);
    }

    public function setParentForm($parentFormName)
    {
        $this->parentFormName = $parentFormName;
    }

    /**
     * Call/Invoke service method, this EasyForm name is passed to the method
     *
     * @param string $class
     * @param string $method
     * @param string $param
     * @return mixed - return value of the service method
     */
    public function callService($class, $method, $param = null)
    {
        $service = Openbizx::getService($class);
        if ($param) {
            return $service->$method($param);
        } else {
            return $service->$method($this->objectName);
        }
    }

    /**
     * Set request parameters
     *
     * @param array $paramFields
     * @return void
     */
    public function setRequestParams($paramFields)
    {
        if ($paramFields) {
            $this->fixSearchRule = null; // reset fixsearchrule to clean the previous one in session
            foreach ($paramFields as $fieldName => $val) {
                $element = $this->dataPanel->getByField($fieldName);
                if ($element->allowURLParam == 'Y') {
                    if (!$this->getDataObj()) {
                        return;
                    }
                    if ($this->getDataObj()->getField($fieldName)) {
                        if ($this->getDataObj()->getField($fieldName)->checkValueType($val)) {
                            //echo __METHOD__.__LINE__.'<br />';
                            $this->setFixSearchRule("[$fieldName]='$val'");
                        }
                    }
                }
            }
        }
    }

    public function setCurrentPage($pageid)
    {
        $this->currentPage = $pageid;
    }

    /**
     * Close the popup window
     *
     * @return void
     */
    public function close()
    {
        Openbizx::$app->getClientProxy()->closePopup();
    }

    /**
     * Render parent form
     *
     * @return void
     */
    public function renderParent()
    {
        /* @var $parentForm EasyForm */
        $parentForm = Openbizx::getObject($this->parentFormName);
        $parentForm->rerender();
    }

    /**
     * Set the dependent search rule of the bizform, this search rule will apply on its BizDataObj.
     * The dependent search rule (session var) will always be with bizform until it get set to other value
     *
     * @param string $rule - search rule has format "[fieldName1] opr1 Value1 AND/OR [fieldName2] opr2 Value2"
     * @param boolean $cleanActualRule
     * @return void
     */
    public function setFixSearchRule($rule = null, $cleanActualRule = true)
    {
        if ($cleanActualRule)
            $this->fixSearchRule = $this->defaultFixSearchRule;

        if ($this->fixSearchRule && $rule) {
            if (strpos($this->fixSearchRule, $rule) === false) {
                $this->fixSearchRule = $this->fixSearchRule . " AND " . $rule;
            }
        }
        if (!$this->fixSearchRule && $rule) {
            $this->fixSearchRule = $rule;
        }
    }

    /**
     * Fetch record set
     *
     * @return array array of record
     */
    public function fetchDataSet()
    {
        $dataObj = $this->getDataObj();

        if (!$dataObj) {
            return null;
        }

        if ($this->isRefreshData) {
            $dataObj->resetRules();
        } else {
            $dataObj->clearSearchRule();
        }

        if ($this->fixSearchRule) {
            if ($this->searchRule) {
                $searchRule = $this->searchRule . " AND " . $this->fixSearchRule;
            } else {
                $searchRule = $this->fixSearchRule;
            }
        } else {
            $searchRule = $this->searchRule;
        }

        $dataObj->setQueryParameters($this->queryParams);
        $dataObj->setSearchRule($searchRule);
        if ($this->startItem > 1) {
            $dataObj->setLimit($this->range, $this->startItem);
        } else {
            $dataObj->setLimit($this->range, ($this->currentPage - 1) * $this->range);
        }
        if ($this->sortRule && $this->sortRule != $this->getDataObj()->sortRule) {
            $dataObj->setSortRule($this->sortRule);
        }
        $resultRecords = $dataObj->fetch();
        $this->totalRecords = $dataObj->count();
        if ($this->range && $this->range > 0) {
            $this->totalPages = ceil($this->totalRecords / $this->range);
        }
        $selectedIndex = 0;

        //if current page is large than total pages ,then reset current page to last page
        if ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
            $dataObj->setLimit($this->range, ($this->currentPage - 1) * $this->range);
            $resultRecords = $dataObj->fetch();
        }

        $this->getDataObj()->setActiveRecord($resultRecords[$selectedIndex]);

        if (!$this->recordId) {
            $this->recordId = $resultRecords[0]["Id"];
        } else {
            $foundRecordId = false;
            foreach ($resultRecords as $record) {
                if ($this->recordId == $record['Id']) {
                    $foundRecordId = true;
                }
            }
            if ($foundRecordId == false) {
                $this->recordId = $result[0]['Id'];
            }
        }

        return $resultRecords;
    }

    /**
     * Fetch single record
     *
     * @return array one record array
     */
    public function fetchData()
    {
        // if has valid active record, return it, otherwise do a query
        if ($this->activeRecord != null)
            return $this->activeRecord;

        $dataObj = $this->getDataObj();
        if ($dataObj == null)
            return;

        if (strtoupper($this->formType) == "NEW")
            return $this->getNewRecord();

        if (!$this->fixSearchRule && !$this->searchRule) {
            //if its a default sub form,even no search rule, but can still fetch a default record
            if (!is_array($this->getDataObj()->association)) {
                //only if its a default sub form and without any association then return emply array
                return array();
            }
        } else {
            if ($this->isRefreshData)
                $dataObj->resetRules();
            else
                $dataObj->clearSearchRule();

            if ($this->fixSearchRule) {
                if ($this->searchRule) {
                    $searchRule = $this->searchRule . " AND " . $this->fixSearchRule;
                } else {
                    $searchRule = $this->fixSearchRule;
                }
            }

            $dataObj->setSearchRule($searchRule);
            $dataObj->setLimit(1);
        }
        $resultRecords = $dataObj->fetch();
        if (!count($resultRecords)) {
            $this->recordAllowAccess = false;
        }

        $this->recordId = $resultRecords[0]['Id'];
        $this->setActiveRecord($resultRecords[0]);

        if ($this->getDataObj()) {
            $this->canUpdateRecord = (int) $this->getDataObj()->canUpdateRecord();
        }
        return $resultRecords[0];
    }

    /**
     * Goto page specified by $page parameter, and ReRender
     * If page not specified, goto page 1
     *
     * @param number $page
     */
    public function gotoPage($page = 1)
    {
        $tgtPage = intval($page);
        if ($tgtPage == 0)
            $tgtPage = 1;
        $this->currentPage = $tgtPage;
        $this->rerender();
    }

    public function gotoSelectedPage($elemName)
    {
        $page = Openbizx::$app->getClientProxy()->getFormInputs(str_replace(".", "_", $this->objectName) . '_' . $elemName);
        $this->gotoPage($page);
    }

    public function setPageSize($elemName)
    {
        $pagesize = Openbizx::$app->getClientProxy()->getFormInputs(str_replace(".", "_", $this->objectName) . '_' . $elemName);
        $this->range = $pagesize;
        $this->UpdateForm();
    }

    /**
     * Sort Record, for list form
     *
     * @param string $sortCol column name to sort
     * @param string $order 'dec' (decending) or 'asc' (ascending)
     * @access remote
     * @return void
     */
    public function sortRecord($sortCol, $order = 'ASC')
    {
        $element = $this->getElement($sortCol);
        // turn off the OnSort flag of the old onsort field
        $element->setSortFlag(null);
        // turn on the OnSort flag of the new onsort field
        if ($order == "ASC")
            $order = "DESC";
        else
            $order = "ASC";
        $element->setSortFlag($order);

        // change the sort rule and issue the query
        $this->getDataObj()->setSortRule("[" . $element->fieldName . "] " . $order);

        // move to 1st page
        $this->currentPage = 1;
        $this->sortRule = "";

        $this->rerender();
    }

    /**
     * Run Search
     *
     * @return void
     */
    public function runSearch()
    {
        static $isSearchHelperLoaded = false;

        if (!$isSearchHelperLoaded) {
            include_once(OPENBIZ_BIN . "/Easy/SearchHelper.php");
            $isSearchHelperLoaded = true;
        }
        $searchRule = "";
        $this->queryParams = array();
        foreach ($this->searchPanel as $element) {
            $searchStr = '';
            if (method_exists($element, "getSearchRule")) {
                $searchStr = $element->getSearchRule();
            } else {
                if (!$element->fieldName)
                    continue;

                $value = Openbizx::$app->getClientProxy()->getFormInputs($element->objectName);
                if ($element->fuzzySearch == "Y") {
                    $value = "*$value*";
                }
                if ($value != '') {
                    //$searchStr = inputValToRule($element->fieldName, $value, $this);
                    $this->queryParams[$element->fieldName] = $value;
                }
            }
            if ($searchStr) {
                if ($searchRule == "")
                    $searchRule .= $searchStr;
                else
                    $searchRule .= " AND " . $searchStr;
            }
        }
        $this->searchRule = $searchRule;

        $this->isRefreshData = true;

        $this->currentPage = 1;

        Openbizx::$app->getLog()->log(LOG_DEBUG, "FORMOBJ", $this->objectName . "::runSearch(), SearchRule=" . $this->searchRule);

        $recArr = $this->readInputRecord();

        $this->searchPanelValues = $recArr;

        $this->runEventLog();
        $this->rerender();
    }

    /**
     * Reset search
     * 
     * @return void
     */
    public function resetSearch()
    {
        $this->searchRule = "";
        $this->isRefreshData = true;
        $this->currentPage = 1;
        $this->runEventLog();
        $this->rerender();
    }

    public function setSearchRule($searchRule, $queryParams = null)
    {
        $this->searchRule = $searchRule;
        $this->queryParams = $queryParams;
        $this->isRefreshData = true;
        $this->currentPage = 1;
    }

    /**
     * New record, be default, just redirect to the new record page
     *
     * @return void
     */
    public function newRecord()
    {
        $this->processPostAction();
    }

    /**
     * Copy record to new record     *
     *
     * @param mixed $id id of record that want to copy,
     * it parameter not passed, id is '_selectedId'
     * @return void
     */
    public function copyRecord($id = null)
    {
        if ($id == null || $id == '')
            $id = Openbizx::$app->getClientProxy()->getFormInputs('_selectedId');

        if (!$id) {
            Openbizx::$app->getClientProxy()->showClientAlert($this->getMessage("PLEASE_EDIT_A_RECORD"));
            return;
        }
        $this->getActiveRecord($id);
        $this->processPostAction();
    }

    /**
     * Edit Record
     * NOTE: append fld:Id=$id to the redirect page url
     *
     * @param mixed $id
     * @return void
     */
    public function editRecord($id = null)
    {
        if ($id == null || $id == '')
            $id = Openbizx::$app->getClientProxy()->getFormInputs('_selectedId');

        if (!isset($id)) {
            Openbizx::$app->getClientProxy()->showClientAlert($this->getMessage("PLEASE_EDIT_A_RECORD"));
            return;
        }

        // update the active record with new update record
        $this->getActiveRecord($id);

        $this->processPostAction();
    }

    /**
     * Show form
     *
     * @param string $formName
     * @param string $target target type: Popup or other
     * @param array $paramFields
     * @return void
     */
    protected function _showForm($formName, $target, $paramFields)
    {

        $formName_org = $formName;
        if (!$this->defaultFormName)
            $this->defaultFormName = $this->objectName;
        if ($formName == null) {
            if ($this->referenceFormName == null) {
                $formName = $this->defaultFormName;
            } else {
                if ($formName = $this->referenceFormName) {
                    //this judgement is for anti endless loop between swtich forms
                    $formObj = Openbizx::getObject($this->referenceFormName);
                    if ($formObj->referenceFormName == $this->objectName) {
                        $formName = $this->defaultFormName;
                    } else {
                        $formName = $this->referenceFormName;
                    }
                }
            }
        }
        //if($this->getWebpageObject()->isInFormRefLibs($formName))
        {
            // get the form object
            /* @var $formObj EasyForm */
            $formObj = Openbizx::getObject($formName);
            $formObj->defaultFormName = $this->defaultFormName;
            if ($formName_org) {
                //RefenerenceForm records where the from switch from
                if ($this->formType != 'EDIT' &&
                        $this->formType != 'NEW' &&
                        $this->formType != 'COPY') {
                    $formObj->referenceFormName = $this->objectName;
                }
            }

            //if has more than Id field as params then $clearFixSearchRule is false, means join all where rules
            $paramTemp = $paramFields;
            unset($paramTemp['Id']);
            if (count($paramTemp)) {
                $clearFixSearchRule = false;
            } else {
                $clearFixSearchRule = true;
            }
            foreach ($paramFields as $fieldName => $val) {
                $formObj->formParams[$fieldName] = $val;
                $formObj->setFixSearchRule("[$fieldName]='$val'", $clearFixSearchRule);
                if ($fieldName == "Id") {
                    $formObj->setRecordId($val);
                }
            }

            if (!$formObj->canDisplayForm()) {
                $formObj->errorMessage = $this->getMessage("FORM_OPERATION_NOT_PERMITTED", $formObj->objectName);

                if (strtoupper($this->formType) == "LIST") {
                    Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "DataObj error = " . $errorMsg);
                    Openbizx::$app->getClientProxy()->showClientAlert($formObj->errorMessage);
                } else {
                    $this->processFormObjError(array($formObj->errorMessage));
                }

                return false;
            }

            switch ($target) {
                case "Popup":
                    $formObj->setParentForm($this->objectName);
                    echo $formObj->render();
                    break;
                case "Dialog":
                    $formObj->setParentForm($this->objectName);
                    Openbizx::$app->getClientProxy()->redrawForm("DIALOG", $formObj->render());
                    break;
                default:
                    Openbizx::$app->getClientProxy()->redrawForm($this->objectName, $formObj->render());
            }
        }
    }

    /**
     * Delete Record
     * NOTE: use redirectpage attr of eventhandler to redirect or redirect to previous page by default
     *
     * @param string $id
     * @return void
     */
    public function deleteRecord($id = null)
    {
        if ($id == null || $id == '')
            $id = Openbizx::$app->getClientProxy()->getFormInputs('_selectedId');

        $selIds = Openbizx::$app->getClientProxy()->getFormInputs('row_selections', false);
        if ($selIds == null)
            $selIds[] = $id;
        foreach ($selIds as $id) {
            $dataRec = $this->getDataObj()->fetchById($id);
            $this->getDataObj()->setActiveRecord($dataRec);

            if (!$this->canDeleteRecord($dataRec)) {
                $this->errorMessage = $this->getMessage("FORM_OPERATION_NOT_PERMITTED", $this->objectName);
                if (strtoupper($this->formType) == "LIST") {
                    Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "DataObj error = " . $errorMsg);
                    Openbizx::$app->getClientProxy()->showClientAlert($this->errorMessage);
                } else {
                    $this->processFormObjError(array($this->errorMessage));
                }
                return;
            }

            // take care of exception
            try {
                $dataRec->delete();
            } catch (Openbizx\Data\Exception $e) {
                // call $this->processDataException($e);
                $this->processDataException($e);
                return;
            }
        }
        if (strtoupper($this->formType) == "LIST")
            $this->rerender();

        $this->runEventLog();
        $this->processPostAction();
    }

    /**
     * Remove the record out of the associate relationship
     *
     * @return void
     */
    public function removeRecord()
    {
        if ($id == null || $id == '')
            $id = Openbizx::$app->getClientProxy()->getFormInputs('_selectedId');

        $selIds = Openbizx::$app->getClientProxy()->getFormInputs('row_selections', false);
        if ($selIds == null)
            $selIds[] = $id;
        foreach ($selIds as $id) {

            $rec = $this->getDataObj()->fetchById($id);
            $ok = $this->getDataObj()->removeRecord($rec, $bPrtObjUpdated);
            if (!$ok)
                return $this->processDataObjError($ok);
        }

        $this->runEventLog();
        $this->rerender();
        if ($this->parentFormName) {
            $this->renderParent();
        }
    }

    /**
     * Select Record
     *
     * @param string $recId
     * @access remote
     * @return void
     */
    public function selectRecord($recId)
    {
        if ($recId == null || $recId == '')
            $recId = Openbizx::$app->getClientProxy()->getFormInputs('_selectedId');
        $this->recordId = $recId;
        if ($this->getDataObj()) {
            $this->getDataObj()->setActiveRecordId($this->recordId);
        }
        $this->rerender(false); // not redraw the this form, but draw the subforms
        //$this->rerender(); 
    }

    /**
     * Get element Id
     *
     * @return mixed
     */
    public function getElementID()
    {
        $id = $this->dataPanel->getByField('Id')->getValue();
        if ($id) {
            return (int) $id;
        } else {
            return (int) $this->recordId;
        }
    }

    /**
     * Save input and redirect page to a new view
     * use redirectpage attr of eventhandler to redirect or redirect to previous page by default
     * NOTE: For Edit/New form type
     * 
     * @return void
     */
    public function saveRecord()
    {
        if (strtoupper($this->formType) == "NEW") {
            $this->insertRecord();
        } else {
            $this->updateRecord();
        }
    }

    /**
     * Update record
     *
     * @return mixed
     */
    public function updateRecord()
    {
        $currentRec = $this->fetchData();
        $recArr = $this->readInputRecord();
        $this->setActiveRecord($recArr);
        if (count($recArr) != 0) {

            try {
                $this->ValidateForm();
            } catch (Openbizx\Validation\Exception $e) {
                $this->processFormObjError($e->errors);
                return;
            }

            if ($this->_doUpdate($recArr, $currentRec) == false)
                return;

            $this->commitFormElements(); // commit change in FormElement
        }

        // in case of popup form, close it, then rerender the parent form
        if ($this->parentFormName) {
            $this->close();

            $this->renderParent();
        }

        $this->processPostAction();
    }

    public function updateFieldValueAdd($id, $fld_name, $value, $min, $max)
    {
        if ($value >= $max) {
            $value = $min;
        } else {
            $value++;
        }
        return $this->updateFieldValue($id, $fld_name, $value);
    }

    public function updateFieldValueXor($id, $fld_name, $value)
    {
        if ($value > 0) {
            $value_xor = 0;
        } else {
            $value_xor = 1;
        }
        return $this->updateFieldValue($id, $fld_name, $value_xor);
    }

    /**
     * Update record
     *
     * @return mixed
     */
    public function updateFieldValue($Id, $fld_name, $value)
    {

        $element = $this->dataPanel->get($fld_name);
        $fieldname = $element->fieldName;
        $currentRec = $this->getActiveRecord($Id);
        $recArr = $this->getActiveRecord($Id);
        $recArr[$fieldname] = $value;
        if ($this->_doUpdate($recArr, $currentRec) == false)
            return;
        $this->UpdateForm();
    }

    /**
     * Do update record
     *
     * @param array $inputRecord
     * @param array $currentRecord
     * @return void
     */
    protected function _doUpdate($inputRecord, $currentRecord)
    {
        $dataRec = new DataRecord($currentRecord, $this->getDataObj());

        foreach ($inputRecord as $k => $v) {
            $dataRec[$k] = $v; // or $dataRec->$k = $v;
        }

        try {
            $dataRec->save();
        } catch (Openbizx\Validation\Exception $e) {
            $errElements = $this->getErrorElements($e->errors);
            if (count($e->errors) == count($errElements)) {
                $this->processFormObjError($errElements);
            } else {
                $errmsg = implode("<br />", $e->errors);
                Openbizx::$app->getClientProxy()->showErrorMessage($errmsg);
            }
            return false;
        } catch (Openbizx\Data\Exception $e) {
            $this->processDataException($e);
            return false;
        }
        $this->activeRecord = null;
        $this->getActiveRecord($dataRec["Id"]);

        $this->runEventLog();
        return true;
    }

    protected function commitFormElements()
    {
        foreach ($this->dataPanel as $element) {
            if (is_a($element, "FormElement")) {
                $element->setValue('');
            }
        }
    }

    /**
     * Insert new record
     *
     * @return mixed
     */
    public function insertRecord()
    {
        $recArr = $this->readInputRecord();
        $this->setActiveRecord($recArr);
        if (count($recArr) == 0)
            return;

        try {
            $this->ValidateForm();
        } catch (Openbizx\Validation\Exception $e) {
            $this->processFormObjError($e->errors);
            return;
        }

        $this->_doInsert($recArr);

        $this->commitFormElements(); // commit change in FormElement
        // in case of popup form, close it, then rerender the parent form
        if ($this->parentFormName) {
            $this->close();

            $this->renderParent();
        }

        $this->processPostAction();
    }

    /**
     * Do insert record
     *
     * @param array $inputRecord
     * @return void
     */
    protected function _doInsert($inputRecord)
    {

        $dataRec = new DataRecord(null, $this->getDataObj());

        // $inputRecord['Id'] = null; // comment it out for name PK case 
        foreach ($inputRecord as $k => $v)
            $dataRec[$k] = $v; // or $dataRec->$k = $v;

        try {
            $dataRec->save();
        } catch (Openbizx\Validation\Exception $e) {
            $errElements = $this->getErrorElements($e->errors);
            if (count($e->errors) == count($errElements)) {
                $this->processFormObjError($errElements);
            } else {
                $errmsg = implode("<br />", $e->errors);
                Openbizx::$app->getClientProxy()->showErrorMessage($errmsg);
            }
            return;
        } catch (Openbizx\Data\Exception $e) {
            $this->processDataException($e);
            return;
        }
        $this->activeRecord = null;
        $this->getActiveRecord($dataRec["Id"]);

        $this->runEventLog();
        return $dataRec["Id"];
    }

    /**
     * Cancel input and do page redirection
     *
     * @return void
     */
    public function cancel()
    {
        $this->processPostAction();
    }

    /**
     * Update form controls
     *
     * @return void
     * @access remote
     */
    public function updateForm()
    {
        // read the input to form controls
        //@todo: read inputs but should be skipp uploaders elements
        $recArr = $this->readInputRecord();
        $this->setActiveRecord($recArr);
        $this->rerender();
    }

    /**
     * Generate list for AutoSuggest element
     * Also supports elements that have hidden values
     *
     * @param string $input - the search string used to filter the list
     * @todo rename to createAutoSuggestList or createAutoSuggest(v2.5?)
     * @return void
     */
    public function autoSuggest($input)
    {
        if (defined('OPENBIZ_JSLIB_BASE') && OPENBIZ_JSLIB_BASE == 'JQUERY') {
            $value = $_GET["term"];
            // get the select from list of the element
            $element = $this->getElement($input);
            $element->setValue($value);
            $fromlist = array();
            $element->getFromList($fromlist);
            $arr = array();
            $i = 0;
            foreach ($fromlist as $item) {
                $arr[$i++] = array('label' => $item['txt'], 'value' => $item['val']);
            }
            echo json_encode($arr);
            return;
        }
        $foo = $_POST;
        $hidden_flag = FALSE;
        if (strpos($input, '_hidden')) {
            $realInput = str_replace('_hidden', '', $input);
            $hidden_flag = TRUE;
        } else {
            $realInput = $input;
        }

        $value = Openbizx::$app->getClientProxy()->getFormInputs($input);

        // get the select from list of the element
        $element = $this->getElement($realInput);
        $element->setValue($value);
        $fromlist = array();
        $element->getFromList($fromlist);
        echo "<ul>";
        if ($fromlist) {
            if ($hidden_flag = TRUE) {
                $count = 0;
                foreach ($fromlist as $item) {
                    echo "<li id=" . $item['txt'] . ">" . $item['val'] . "</li>";
                    $count++;
                    if ($count >= 5)
                        break;
                }
            }
            else {
                $count = 0;
                foreach ($fromlist as $item) {
                    echo "<li>" . $item['txt'] . "</li>";
                    $count++;
                    if ($count >= 5)
                        break;
                }
            }
        }
        echo "</ul>";
    }

    /**
     * Validate input on EasyForm level
     * default form validation do nothing.
     * developers need to override this method to implement their logic
     *
     * @return boolean
     */
    protected function validateForm($cleanError = true)
    {
        if ($cleanError == true) {
            $this->validateErrors = array();
        }
        $this->dataPanel->rewind();
        while ($this->dataPanel->valid()) {
            /* @var $element Element */
            $element = $this->dataPanel->current();
            if ($element->label) {
                $elementName = $element->label;
            } else {
                $elementName = $element->text;
            }
            if ($element->checkRequired() === true &&
                    ($element->value == null || $element->value == "")) {
                $errorMessage = $this->getMessage("FORM_ELEMENT_REQUIRED", array($elementName));
                $this->validateErrors[$element->objectName] = $errorMessage;
                //return false;
            } elseif ($element->value !== null && $element->Validate() == false) {
                $validateService = Openbizx::getService(VALIDATE_SERVICE);
                $errorMessage = $this->getMessage("FORM_ELEMENT_INVALID_INPUT", array($elementName, $value, $element->validator));
                if ($errorMessage == false) { //Couldn't get a clear error message so let's try this
                    $errorMessage = $validateService->getErrorMessage($element->validator, $elementName);
                }
                $this->validateErrors[$element->objectName] = $errorMessage;
                //return false;
            }
            $this->dataPanel->next();
        }
        if (count($this->validateErrors) > 0) {
            throw new ValidationException($this->validateErrors);
        }
        return true;
    }

    /**
     * Read user input data from UI
     *
     * @return array - record array
     */
    protected function readInputRecord()
    {
        $recArr = array();
        foreach ($this->dataPanel as $element) {
            $value = Openbizx::$app->getClientProxy()->getFormInputs($element->objectName);
            if ($value === null && (
                    !is_a($element, "FileUploader") && !is_subclass_of($element, "FileUploader") && !is_a($element, "Checkbox") && !is_a($element, "FormElement")
                    )) {
                continue;
            }
            $element->setValue($value);
            $this->formInputs[$element->objectName] = $value;
            $value = $element->getValue();
            if ($element->fieldName)
                $recArr[$element->fieldName] = $value;
        }

        foreach ($this->searchPanel as $element) {
            $value = Openbizx::$app->getClientProxy()->getFormInputs($element->objectName);
            $element->setValue($value);
            $this->formInputs[$element->objectName] = $value;
            $value = $element->getValue();
            if ($value !== null && $element->fieldName)
                $recArr[$element->fieldName] = $value;
        }
        return $recArr;
    }

    /**
     * Read inputs
     *
     * @return array array of input
     */
    protected function readInputs()
    {
        $inputArr = array();
        foreach ($this->dataPanel as $element) {
            $value = Openbizx::$app->getClientProxy()->getFormInputs($element->objectName);
            $element->setValue($value);
            $inputArr[$element->objectName] = $value;
        }

        foreach ($this->searchPanel as $element) {
            $value = Openbizx::$app->getClientProxy()->getFormInputs($element->objectName);
            $element->setValue($value);
            $inputArr[$element->objectName] = $value;
        }
        return $inputArr;
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

    /**
     * Get new record
     *
     * @return array
     */
    protected function getNewRecord()
    {
        if ($this->getDataObj()) {
            $recArr = $this->getDataObj()->newRecord();
        }
        if (!$recArr)
            return null;
        // load default values if new record value is empty
        $defaultRecArr = array();
        foreach ($this->dataPanel as $element) {
            if ($element->fieldName) {
                $defaultRecArr[$element->fieldName] = $element->getDefaultValue();
            }
        }
        foreach ($recArr as $field => $val) {
            if ($val == "" && $defaultRecArr[$field] != "") {
                $recArr[$field] = $defaultRecArr[$field];
            }
        }
        return $recArr;
    }

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
        $this->setClientScripts();

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
                $output = $this->renderHTML();
                $cacheSvc->save($output, $cache_id);
            }
            return $output;
        }

        //Moved the renderHTML function infront of declaring subforms
        $renderedHTML = $this->renderHTML();

        // prepare the subforms' dataobjs, since the subform relates to parent form by dataobj association
        if ($this->subForms && $this->getDataObj()) {
            foreach ($this->subForms as $subForm) {
                $formObj = Openbizx::getObject($subForm);
                $dataObj = $this->getDataObj()->getRefObject($formObj->dataObjName);
                if ($dataObj)
                    $formObj->setDataObj($dataObj);
            }
        }
        if (!$this->allowAccess()) {
            return "";
        }
        return $renderedHTML;
    }

    /**
     * Render context menu code
     *
     * @return string html code for context menu
     */
    protected function renderContextMenu()
    {
        $menuList = array();
        foreach ($this->panels as $panel) {
            $panel->rewind();
            while ($element = $panel->current()) {
                $panel->next();
                if (method_exists($element, 'getContextMenu') && $menus = $element->getContextMenu()) {
                    foreach ($menus as $m)
                        $menuList[] = $m;
                }
            }
        }
        if (count($menuList) == 0)
            return "";
        $str = "<div  class='contextMenu' id='" . $this->objectName . "_contextmenu'>\n";
        $str .= "<div class=\"contextMenu_header\" ></div>\n";
        $str .= "<ul>\n";
        foreach ($menuList as $m) {
            $func = $m['func'];
            $shortcutKey = isset($m['key']) ? " (" . $m['key'] . ")" : "";
            $str .= "<li><a href=\"javascript:void(0)\" onclick=\"$func\">" . $m['text'] . $shortcutKey . "</a></li>\n";
        }
        $str .= "</ul>\n";
        $str .= "<div class=\"contextMenu_footer\" ></div>\n";
        $str .= "</div>\n";
        if (defined('OPENBIZ_JSLIB_BASE') && OPENBIZ_JSLIB_BASE == 'JQUERY') {
            $str .= "
<script>
$(jq('" . $this->objectName . "')).removeAttr('onContextMenu');
$(jq('" . $this->objectName . "'))[0].oncontextmenu=function(event){return Openbizx.Menu.show(event, '" . $this->objectName . "_contextmenu');};
$(jq('" . $this->objectName . "')).bind('click',Openbizx.Menu.hide);
</script>";
        } else {
            $str .= "
<script>
$('" . $this->objectName . "').removeAttribute('onContextMenu');
$('" . $this->objectName . "').oncontextmenu=function(event){return Openbizx.Menu.show(event, '" . $this->objectName . "_contextmenu');};
$('" . $this->objectName . "').observe('click',Openbizx.Menu.hide);
</script>";
        }
        return $str;
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
            Openbizx::$app->getClientProxy()->redrawForm($this->objectName, $this->renderHTML());
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
        if (!$this->subForms)
            return;
        foreach ($this->subForms as $subForm) {
            $formObj = Openbizx::getObject($subForm);
            if ($this->getDataObj() && $formObj->dataObjName) {
                $dataObj = $this->getDataObj()->getRefObject($formObj->dataObjName);
                if ($dataObj)
                    $formObj->setDataObj($dataObj);
            }
            $formObj->rerender();
        }
        return;
    }

    /**
     * Render html content of this form
     *
     * @return string - HTML text of this form's read mode
     */
    protected function renderHTML()
    {
        $formHTML = FormRenderer::render($this);
        $otherHTML = $this->rendercontextmenu();


        if (preg_match('/iPad/si', $_SERVER['HTTP_USER_AGENT']) ||
                preg_match('/iPhone/si', $_SERVER['HTTP_USER_AGENT'])) {
            $otherHTML.="
        		<script>
				var a=document.getElementsByTagName('a');
				for(var i=0;i<a.length;i++)
				{
					if(a[i].getAttribute('href').indexOf('javascript:')==-1
					&& a[i].getAttribute('href').indexOf('#')==-1)
						{
						    a[i].onclick=function()
						    {
							    try{
						    		show_loader();
						    	}catch(e){
						    		
						    	}
						        window.location=this.getAttribute('href');
						        return false
						    }
						}else{
						}
				} 
				</script>       		
        		";
        }
        if (!$this->parentFormName) {
            if (($viewObj = $this->getWebpageObject()) != null)
                $viewObj->lastRenderedForm = $this->objectName;
        }
        return $formHTML . "\n" . $otherHTML;
    }

    /**
     * Get event log message
     *
     * @return mixed string or null
     */
    protected function getEventLogMsg()
    {
        list($element, $eventHandler) = $this->getInvokingElement();
        $eventLogMsg = $eventHandler->eventLogMsg;
        if ($eventLogMsg) {
            return $eventLogMsg;
        } else {
            return null;
        }
    }

    /**
     * Get on event elements
     *
     * @return array element list
     */
    protected function getOnEventElements()
    {
        $elementList = array();
        foreach ($this->dataPanel as $element) {
            if ($element->onEventLog == "Y")
                $elementList[] = $element->value;
        }
        return $elementList;
    }

    /**
     * Run event log
     *
     * @return void
     */
    protected function runEventLog()
    {
        $logMessage = $this->getEventLogMsg();
        $eventName = $this->eventName;
        if ($logMessage && $eventName) {
            $logElements = $this->getOnEventElements();
            $eventlog = Openbizx::getService(OPENBIZ_EVENTLOG_SERVICE);
            $eventlog->log($eventName, $logMessage, $logElements);
        }
    }

    /**
     * return redirect page and target array
     *
     * @return array {redirectPage, $target}
     */
    protected function getRedirectPage()
    {
        // get the control that issues the call
        // __this is elementName:eventHandlerName
        list($element, $eventHandler) = $this->getInvokingElement();
        $eventHandlerName = $eventHandler->objectName;
        $redirectPage = $element->getRedirectPage($eventHandlerName); // need to get postaction of eventhandler
        $functionType = $element->getFunctionType($eventHandlerName);
        switch ($functionType) {
            case "Popup":
            case "Prop_Window":
            case "Prop_Dialog":
                $target = "Popup";
                break;
            default:
                $target = "";
        }
        return array($redirectPage, $target);
    }

    /**
     * Switch to other form
     *
     * @param string $formName to-be-swtiched form name. if empty, then switch to default form
     * @param string $id id value of the target form
     * @return void
     * @access remote
     */
    public function switchForm($formName = null, $id = null, $params = null, $target = null)
    {
        $paramFields = array();
        if ($params) {
            parse_str(urldecode($params), $paramFields);
        }
        if ($id != null)
            $paramFields["Id"] = $id;
        $this->_showForm($formName, $target, $paramFields);
    }

    public function parentSwitchForm($formName = null, $id = null, $params = null, $target = null)
    {
        if ($this->parentFormName) {
            $formObj = Openbizx::getObject($this->parentFormName);
            return $formObj->switchForm($formName, $id, $params, $target);
        }
    }

    public function targetSwitchForm($targetForm, $formName = null, $id = null, $params = null, $target = null)
    {
        if ($targetForm) {
            $formObj = Openbizx::getObject($targetForm);
            if ($formObj) {
                return $formObj->switchForm($formName, $id, $params, $target);
            }
        }
    }

    /**
     * Get the element that issues the call.
     *
     * @return array element object and event handler name
     */
    protected function getInvokingElement()
    {
        if ($this->invokingElement)
            return $this->invokingElement;
        // __this is elementName:eventHandlerName
        $elementAndEventName = Openbizx::$app->getClientProxy()->getFormInputs("__this");
        if (!$elementAndEventName)
            return array(null, null);
        list ($elementName, $eventHandlerName) = explode(":", $elementAndEventName);
        $element = $this->getElement($elementName);
        $eventHandler = $element->eventHandlers->get($eventHandlerName);
        $this->invokingElement = array($element, $eventHandler);
        return $this->invokingElement;
    }

    /**
     * Process Post Action
     *
     * @return void
     */
    protected function processPostAction()
    {
        // get the $redirectPage from eventHandler
        list($redirectPage, $target) = $this->getRedirectPage();
        if ($redirectPage) {
            if ($this->hasError == false) {
                // if the redirectpage start with "form=", render the form to the target which is defined by FuntionType
                if (strpos($redirectPage, "form=") === 0) {
                    parse_str($redirectPage, $output);
                    $formName = $output['form'];

                    // parse query string. e.g. fld:Id=val&fld:name=val
                    $paramFields = array();
                    foreach ($output as $key => $value) {
                        if (substr($key, 0, 4) == "fld:") {
                            $fieldName = substr($key, 4);
                            $paramFields[$fieldName] = $value;
                        }
                    }

                    $this->_showForm($formName, $target, $paramFields);
                } else {
                    // otherwise, do page redirection
                    Openbizx::$app->getClientProxy()->ReDirectPage($redirectPage);
                }
            }
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

        // TODO: may consider cache the current record in session
        if ($this->getDataObj()) {
            $this->getDataObj()->setActiveRecordId($this->recordId);
            $rec = $this->getDataObj()->getActiveRecord();

            // update the record row
            $this->dataPanel->setRecordArr($rec);

            $this->activeRecord = $rec;
        }
        return $rec;
    }

    public function getRecordId()
    {
        return $this->recordId;
    }

    public function setRecordId($val)
    {
        $this->recordId = $val;
        return $val;
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
        if (!isset($this->activeRecord["Id"]) &&
                $this->recordId != null &&
                (strtoupper($this->formType) == 'EDIT' || $this->formType == null )) {
            if ($this->getDataObj()) {
                $this->activeRecord = $this->getDataObj()->fetchById($this->recordId)->toArray();
            }
        }
        if (is_array($record)) {
            foreach ($record as $key => $value) {
                if ($key == 'extend')
                    continue;
                $this->activeRecord[$key] = $record[$key];
            }
        }
    }

    /**
     * Set client scripts, auto add javascripts code to the page
     *
     * @return void
     */
    protected function setClientScripts()
    {
        // load custom js class
        if ($this->jsClass != "Openbizx.Form" && $this->jsClass != "Openbizx.TableForm" && $this->jsClass != "")
            Openbizx::$app->getClientProxy()->appendScripts($this->jsClass, $this->jsClass . ".js");
        /*
          if ($this->formType == 'LIST')
          {
          Openbizx::$app->getClientProxy()->appendScripts("tablekit", "tablekit.js");
          } */
    }

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
