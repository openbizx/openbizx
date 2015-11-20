<?php
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.easy.element
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: Element.php 4049 2011-05-01 12:56:06Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\I18n\I18n;
use Openbizx\Object\MetaObject;
use Openbizx\Object\MetaIterator;
use Openbizx\Object\UIControlInterface;

/**
 * Element class is the base class of all HTML Element
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class Element extends MetaObject implements UIControlInterface
{
    public $style;
    public $cssClass;
    public $cssErrorClass;
    public $cssFocusClass;
    public $width;
    public $height;
    public $bizDataObj;
    public $hidden = "N";       // support expression
    public $htmlAttr;
    public $label;
    public $text;
    public $eventHandlers;
    public $translatable;
    public $fuzzySearch;
    public $onEventLog;
    public $allowURLParam = 'N';
    public $xmlMeta;

    public $sortFlag;
    public $value = "";
    public $formName;
    public $extra;
    public $elementSet;
    public $elementSetCode;
    public $tabSet;
    public $tabSetCode;
    public $fieldName;
    public $required = null;
    public $validator = null;
    public $clientValidator = null;
	public $keepCookie = null;
	public $cookieLifetime = 3600;
	public $backgroundColor;
	
	public $dataRole = "";

    public $defaultValue;
	
    /**
     * Initialize Element with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr, $formObj)
    {
    	$this->xmlMeta = $xmlArr;
        $this->formName = $formObj->objectName;
        $this->package = $formObj->package;

        $this->readMetaData($xmlArr);
                
        $this->translate();	// translate for multi-language support
    }

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        $this->objectName = isset($xmlArr["ATTRIBUTES"]["NAME"]) ? $xmlArr["ATTRIBUTES"]["NAME"] : null;
        $this->bizDataObj = isset($xmlArr["ATTRIBUTES"]["BIZDATAOBJ"]) ? $xmlArr["ATTRIBUTES"]["BIZDATAOBJ"] : null;
		$this->backgroundColor = isset($xmlArr["ATTRIBUTES"]["BACKGROUNDCOLOR"]) ? $xmlArr["ATTRIBUTES"]["BACKGROUNDCOLOR"] : null;        
        $this->className = isset($xmlArr["ATTRIBUTES"]["CLASS"]) ? $xmlArr["ATTRIBUTES"]["CLASS"] : null;
        $this->objectDescription = isset($xmlArr["ATTRIBUTES"]["DESCRIPTION"]) ? $xmlArr["ATTRIBUTES"]["DESCRIPTION"] : null;
        $this->access = isset($xmlArr["ATTRIBUTES"]["ACCESS"]) ? $xmlArr["ATTRIBUTES"]["ACCESS"] : null;
        $this->defaultValue = isset($xmlArr["ATTRIBUTES"]["DEFAULTVALUE"]) ? $xmlArr["ATTRIBUTES"]["DEFAULTVALUE"] : null;
        $this->cssClass = isset($xmlArr["ATTRIBUTES"]["CSSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSCLASS"] : null;
        $this->cssErrorClass = isset($xmlArr["ATTRIBUTES"]["CSSERRORCLASS"]) ? $xmlArr["ATTRIBUTES"]["CSSERRORCLASS"] : "input_error";
        $this->style = isset($xmlArr["ATTRIBUTES"]["STYLE"]) ? $xmlArr["ATTRIBUTES"]["STYLE"] : null;
        $this->width = isset($xmlArr["ATTRIBUTES"]["WIDTH"]) ? $xmlArr["ATTRIBUTES"]["WIDTH"] : null;
        $this->height = isset($xmlArr["ATTRIBUTES"]["HEIGHT"]) ? $xmlArr["ATTRIBUTES"]["HEIGHT"] : null;
        $this->hidden = isset($xmlArr["ATTRIBUTES"]["HIDDEN"]) ? $xmlArr["ATTRIBUTES"]["HIDDEN"] : null;
        $this->htmlAttr = isset($xmlArr["ATTRIBUTES"]["HTMLATTR"]) ? $xmlArr["ATTRIBUTES"]["HTMLATTR"] : null;
        $this->elementSet = isset($xmlArr["ATTRIBUTES"]["ELEMENTSET"]) ? $xmlArr["ATTRIBUTES"]["ELEMENTSET"] : null;
        $this->elementSetCode = isset($xmlArr["ATTRIBUTES"]["ELEMENTSET"]) ? $xmlArr["ATTRIBUTES"]["ELEMENTSET"] : null;          
        $this->tabSet = isset($xmlArr["ATTRIBUTES"]["TABSET"]) ? $xmlArr["ATTRIBUTES"]["TABSET"] : null;
        $this->tabSetCode = isset($xmlArr["ATTRIBUTES"]["TABSET"]) ? $xmlArr["ATTRIBUTES"]["TABSET"] : null;

        $this->text = isset($xmlArr["ATTRIBUTES"]["TEXT"]) ? $xmlArr["ATTRIBUTES"]["TEXT"] : null;

        $this->translatable = isset($xmlArr["ATTRIBUTES"]["TRANSLATABLE"]) ? $xmlArr["ATTRIBUTES"]["TRANSLATABLE"] : null;
        $this->fuzzySearch = isset($xmlArr["ATTRIBUTES"]["FUZZYSEARCH"]) ? $xmlArr["ATTRIBUTES"]["FUZZYSEARCH"] : null;
        $this->onEventLog = isset($xmlArr["ATTRIBUTES"]["ONEVENTLOG"]) ? $xmlArr["ATTRIBUTES"]["ONEVENTLOG"] : null;
        $this->required = isset($xmlArr["ATTRIBUTES"]["REQUIRED"]) ? $xmlArr["ATTRIBUTES"]["REQUIRED"] : null;
        $this->validator = isset($xmlArr["ATTRIBUTES"]["VALIDATOR"]) ? $xmlArr["ATTRIBUTES"]["VALIDATOR"] : null;
        $this->clientValidator = isset($xmlArr["ATTRIBUTES"]["CLIENTVALIDATOR"]) ? $xmlArr["ATTRIBUTES"]["CLIENTVALIDATOR"] : null;
        $this->allowURLParam = isset($xmlArr["ATTRIBUTES"]["ALLOWURLPARAM"]) ? $xmlArr["ATTRIBUTES"]["ALLOWURLPARAM"] : 'Y';
        $this->keepCookie = isset($xmlArr["ATTRIBUTES"]["KEEPCOOKIE"]) ? $xmlArr["ATTRIBUTES"]["KEEPCOOKIE"] : 'N';
        $this->cookieLifetime = isset($xmlArr["ATTRIBUTES"]["COOKIELIFETIME"]) ? (int)$xmlArr["ATTRIBUTES"]["COOKIELIFETIME"] : '3600';
		$this->dataRole = isset($xmlArr["ATTRIBUTES"]["DATAROLE"]) ? $xmlArr["ATTRIBUTES"]["DATAROLE"] : null;
		$this->extra = isset($xmlArr["ATTRIBUTES"]["EXTRA"]) ? $xmlArr["ATTRIBUTES"]["EXTRA"] : null;

        // read EventHandler element
        if (isset($xmlArr["EVENTHANDLER"]))  // 2.1 eventhanlders
            $this->eventHandlers = new MetaIterator($xmlArr["EVENTHANDLER"],"EventHandler");

        if ($this->eventHandlers != null)
        {
            foreach ($this->eventHandlers as $eventHandler)
                $eventHandler->setFormName($this->formName, $this->objectName);
        }

        // additional data in HTMLAttr
		$this->htmlAttr .= ($this->dataRole) ? " data-role='".$this->dataRole."'" : "";
        $this->htmlAttr .= " title='".$this->objectDescription."'"." clientValidator='".$this->clientValidator."'";
    }

    /**
     * Get form ({@link EasyForm}) object
     *
     * @return EasyForm
     */
    protected function getFormObj()
    {
        return Openbizx::getObject($this->formName);
    }

    //
    /**
     * Adjust form ({@link EasyForm}) name
     * change the form name after inherit from parent form
     *
     * @param string $formName
     * @return void
     */
    public function adjustFormName($formName)
    {
        if ($this->formName == $formName)
            return;
        $this->formName = $formName;
        if ($this->eventHandlers != null)
        {
            foreach ($this->eventHandlers as $eventHandler)
                $eventHandler->adjustFormName($this->formName);
        }
    }

    public function reset()
    {
    	$this->value = null;
    	if ($this->eventHandlers != null)
        {
            foreach ($this->eventHandlers as $eventHandler)
                $eventHandler->formedFunction = null;
        }
    }

    /**
     * Set value of element
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;        
        if($this->keepCookie=='Y'){
        	if($value!=""){
        		$formName = $this->getFormObj()->objectName;       
        		setcookie($formName."-".$this->objectName,$value,time()+(int)$this->cookieLifetime,"/");
        	}
        }
    }

    /**
     * Get value of element
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get property of element
     *
     * @param string $propertyName
     * @return mixed
     */
    public function getProperty($propertyName)
    {
        if ($propertyName == "Value") return $this->getValue();
        $ret = parent::getProperty($propertyName);
        if ($ret) return $ret;
        return $this->$propertyName;
    }

    /**
     * Check if the element can be displayed
     *
     * @return boolean
     */
    public function canDisplayed()
    {
        if (!$this->allowAccess())
            return false;
        if ($this->getHidden() == "Y")
            return false;
        return true;
    }

    /**
     * Get default value
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue == "" && $this->keepCookie!='Y')
            return "";
        $formObj = $this->getFormObj();
        if($this->keepCookie=='Y'){
        	$cookieName = $formObj->objectName."-".$this->objectName;      
        	$cookieName = str_replace(".","_",$cookieName);
        	$defValue = $_COOKIE[$cookieName];         	       
        }                
        if(!$defValue){
        	$defValue = Expression::evaluateExpression($this->defaultValue, $formObj);
        }
        //add automatic append like new record (2)
        if($this->defaultValueRename!='N'){
	        if(!is_numeric($defValue)){
		        $dataobj = $formObj->getDataObj();
		        if($this->fieldName && $dataobj){
		        	if(substr($this->fieldName,0,1)!='_'){        	
			        	$recs = $dataobj->directfetch("[".$this->fieldName."] = '$defValue' OR "."[".$this->fieldName."] LIKE '$defValue (%)'" );	        	
			        	if($recs->count()>0){
			        		$defValue.= " ( ".$recs->count()." )";
			        	}
		        	}
		        }
	        }
        }
        return $defValue;
    }

    /**
     * Render the element by html
     *
     * @return string HTML text
     */
    public function render()
    {
     	return "";
    }

    public function renderLabel()
    {        
        $sHTML = $this->translateString($this->label);       
        return $sHTML;
    }    
    
    /**
     * Get hidden status
     *
     * @return mixed
     */
    protected function getHidden()
    {
		if (!$this->hidden || $this->hidden=='N') return "N";
        $formObj = $this->getFormObj();
        return Expression::evaluateExpression($this->hidden, $formObj);
    }

    /**
     * Get style of element
     *
     * @return string
     */
    protected function getStyle()
    {        
		$formobj = $this->getFormObj();
        $htmlClass = Expression::evaluateExpression($this->cssClass, $formobj);
        $htmlClass = "CLASS='$htmlClass'";
        if(!$htmlClass){
        	$htmlClass = null;
        }
        $style ='';
        if ($this->width && $this->width>=0)
            $style .= "width:".$this->width."px;";
        if ($this->height && $this->height>=0)
            $style .= "height:".$this->height."px;";
        if ($this->style)
            $style .= $this->style;
        if (!isset($style) && !$htmlClass)
            return null;
        if (isset($style))
        {
            
            $style = Expression::evaluateExpression($style, $formobj);
            $style = "STYLE='$style'";
        }
        if($formobj->errors[$this->objectName])
        {
      	    $htmlClass = "CLASS='".$this->cssErrorClass."'";
        }
        if ($htmlClass)
            $style = $htmlClass." ".$style;
        return $style;
    }

    protected function getBackgroundColor()
    {
        if ($this->backgroundColor == null)
            return null;   
        $formObj = $this->getFormObj();
        return Expression::evaluateExpression($this->backgroundColor, $formObj);
    }        
    
    /**
     * Get text of element
     *
     * @return string
     */
    protected function getText()
    {
        if ($this->text == null)
            return null;
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->text, $formobj);
    }
    
    public function getDescription()
    {
        if ($this->objectDescription == null)
            return null;
        $formobj = $this->getFormObj();
        $text =  Expression::evaluateExpression($this->objectDescription, $formobj);
        $text = str_replace("[b]","<strong>",$text);
        $text = str_replace("[/b]","</strong>",$text);
        return $text;
    }    

    /**
     * Get shortcut key function map
     *
     * @return array
     */
    public function getSCKeyFuncMap()
    {
        if (!$this->canDisplayed()) return null;

        $map = array();
        /**
         * @todo need to remove, not used (mr_a_ton)
         */
        //$formObj = $this->getFormObj(); // not used

        if ($this->eventHandlers == null)
            return null;
        foreach ($this->eventHandlers as $eventHandler)
        {
            if ($eventHandler->shortcutKey)
            {
                $map[$eventHandler->shortcutKey] = $eventHandler->getFormedFunction();
            }
        }
        return $map;
    }

    /**
     * Get context menu
     *
     * @return array
     */
    public function getContextMenu()
    {
        if (!$this->canDisplayed()) return null;
        $menus = array();
        $formObj = $this->getFormObj();
        if ($this->eventHandlers == null)
            return null;
        $i = 0;
        foreach ($this->eventHandlers as $eventHandler)
        {
            if ($eventHandler->contextMenu)
            {
                $menus[$i]['text'] = $eventHandler->contextMenu;
                $menus[$i]['func'] = $eventHandler->getFormedFunction();
                $menus[$i]['key']  = $eventHandler->shortcutKey;
            }
            $i++;
        }
        return $menus;
    }

    /**
     * Get function of element in JavaScript format
     *
     * @return string
     */
    protected function getFunction()
    {
        $events = $this->getEvents();
		foreach ($events as $event=>$function){
			if(is_array($function)){
				foreach($function as $f){
					$function_str.=$f.";";
				}
				$func .= " $event=\"$function_str\"";
			}else{
				$func .= " $event=\"$function\"";
			}
		}
        return $func;
    }
    
    public function getEvents(){
    	$name = $this->objectName;
        // loop through the event handlers
        $func = "";

        $events = array();
        
        if ($this->eventHandlers == null)
            return $events;
        $formobj = $this->getFormObj();
       
        foreach ($this->eventHandlers as $eventHandler)
        {
            $ehName = $eventHandler->objectName;
            $event = $eventHandler->event;
            $type = $eventHandler->functionType;
            if (!$event) continue;
            if($events[$event]!=""){
            	$events[$event]=array_merge(array($events[$event]),array($eventHandler->getFormedFunction()));
            }else{
            	$events[$event]=$eventHandler->getFormedFunction();
            }
        }
        return $events;
    }
    
    public function getFunctionByEventHandlerName($eventHandlerName)
    {
    	if ($this->eventHandlers == null)
            return null;
    	$eventHandler = $this->eventHandlers->get($eventHandlerName);
    	if ($eventHandler)
    		$func = Expression::evaluateExpression($eventHandler->function, $formobj);
    	return $func;
    }

    /**
     * Get redirect page
     *
     * @param string $eventHandlerName event handler name
     * @return string
     */
    public function getRedirectPage($eventHandlerName)
    {
        $formObj = $this->getFormObj();
        $eventHandler = $this->eventHandlers->get($eventHandlerName);
        if (!$eventHandler) return null;
        //echo $evthandler->redirectPage."<br>";
        return Expression::evaluateExpression($eventHandler->redirectPage, $formObj);
    }

    /**
     * Get parameter
     *
     * @param string $eventHandlerName
     * @return mixed
     */
    public function getParameter($eventHandlerName){
    	$formObj = $this->getFormObj();
        $eventHandler = $this->eventHandlers->get($eventHandlerName);
        if (!$eventHandler) return null;
        //echo $evthandler->redirectPage."<br>";
        return Expression::evaluateExpression($eventHandler->parameter, $formObj);
    }
    
    /**
     * Get function type
     *
     * @param string $eventHandlerName event handler name
     * @return string function type in string format
     */
    public function getFunctionType($eventHandlerName)
    {
        $eventHandler = $this->eventHandlers->get($eventHandlerName);
        if (!$eventHandler) return null;
        return $eventHandler->functionType;
    }

    /**
     * Check if element must required (must have value)
     *
     * @return boolean
     */
    public function checkRequired()
    {
        if (!$this->required || $this->required == "")
            return false;
        else if ($this->required == "Y")
            $required = true;
        else if($this->required == "N")
            $required = false;
        else{
            $required = Expression::evaluateExpression($this->required, $this->getFormObj());
            if(strtoupper($required)=='Y')
            {
            	$required=true;
            }
            elseif(strtoupper($required)=='N')
            {
            	
            }
            else
            {            	
            	$required=false;
            }
        }
        return $required;
    }

    /**
     * Validate element
     * 
     * @return boolean
     */
    public function validate()
    {
        $ret = true;
        if ($this->validator)
            $ret = Expression::evaluateExpression($this->validator, $this->getFormObj());
        return $ret;
    }

    /**
     * Get client validator
     *
     * @return string
     */
    public function getClientValidator()
    {
        if ($this->clientValidator)
            return $this->clientValidator;

        //return Expression::evaluateExpression($this->clientValidator, $this->getFormObj());
        return null;
    }
    
    public function matchRemoteMethod($method)
    {
        return false;
    }
    
    protected function translate()
    {
    	$module = $this->getModuleName($this->formName);
    	if (!empty($this->text))
    		$this->text = I18n::t($this->text, $this->getTransKey('Text'), $module, $this->getTransPrefix());
    	if (!empty($this->label))
    		$this->label = I18n::t($this->label, $this->getTransKey('Label'), $module, $this->getTransPrefix());
    	if (!empty($this->objectDescription))
    		$this->objectDescription = I18n::t($this->objectDescription, $this->getTransKey('Description'), $module, $this->getTransPrefix());
        if (!empty($this->defaultValue) && !preg_match("/\{/si",$this->defaultValue))
    		$this->defaultValue = I18n::t($this->defaultValue, $this->getTransKey('DefaultValue'), $module, $this->getTransPrefix());
		if (!empty($this->elementSet))
    		$this->elementSet = I18n::t($this->elementSet, $this->getTransKey('ElementSet'), $module, $this->getTransPrefix());
    	if (!empty($this->blankOption))
    		$this->blankOption = I18n::t($this->blankOption, $this->getTransKey('BlankOption'), $module, $this->getTransPrefix());
    	if (!empty($this->tabSet))
    		$this->tabSet = I18n::t($this->tabSet, $this->getTransKey('TabSet'), $module, $this->getTransPrefix());  
    	if (!empty($this->hint))
    		$this->hint = I18n::t($this->hint, $this->getTransKey('Hint'), $module, $this->getTransPrefix());  		
    }

	protected function getTransPrefix()
    {    	
    	$nameArr = explode(".",$this->formName);
    	for($i=1;$i<count($nameArr)-1;$i++)
    	{
    		$prefix .= strtoupper($nameArr[$i])."_";
    	}
    	return $prefix;
    }    
    
    protected function getTransKey($name)
    {
    	$shortFormName = substr($this->formName,intval(strrpos($this->formName,'.')+1));
    	return strtoupper($shortFormName.'_'.$this->objectName.'_'.$name);
    }
    
    protected function translateString($value)
    {
        $module = $this->getModuleName($this->formName);
        if(defined($value)) $value = constant($value);
        return I18n::t($value, 'STRING_'.$value, $module);
    }
    
    public function getDataObj()
    {
    	if(!$this->bizDataObj){
    		return $this->getFormObj()->getDataObj();
    	}else{
    		return Openbizx::getDataObject($this->bizDataObj);
    	}
    }
}

/**
 * EventHandler class is manage event handler of element
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class EventHandler
{
    public $objectName;
    public $event;
    public $function;     // support expression
    public $functionType;
    public $postAction;   // support expression
    public $shortcutKey;
    public $contextMenu;
    public $redirectPage;
    public $parameter;
    public $eventLogMsg;
    public $formedFunction;
    private $_formName;
    private $_elemName;

    // add URL here so that direct url string can be given
    public $url;

    /**
     * Initialize EventHandler with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->objectName = isset($xmlArr["ATTRIBUTES"]["NAME"]) ? $xmlArr["ATTRIBUTES"]["NAME"] : null;
        $this->event = isset($xmlArr["ATTRIBUTES"]["EVENT"]) ? $xmlArr["ATTRIBUTES"]["EVENT"] : null;
        $this->function = isset($xmlArr["ATTRIBUTES"]["FUNCTION"]) ? $xmlArr["ATTRIBUTES"]["FUNCTION"] : null;
        $this->origFunction = $this->function;
        $this->functionType = isset($xmlArr["ATTRIBUTES"]["FUNCTIONTYPE"]) ? $xmlArr["ATTRIBUTES"]["FUNCTIONTYPE"] : null;
        $this->postAction = isset($xmlArr["ATTRIBUTES"]["POSTACTION"]) ? $xmlArr["ATTRIBUTES"]["POSTACTION"] : null;
        $this->shortcutKey = isset($xmlArr["ATTRIBUTES"]["SHORTCUTKEY"]) ? $xmlArr["ATTRIBUTES"]["SHORTCUTKEY"] : null;
        $this->contextMenu = isset($xmlArr["ATTRIBUTES"]["CONTEXTMENU"]) ? $xmlArr["ATTRIBUTES"]["CONTEXTMENU"] : null;
        $this->redirectPage = isset($xmlArr["ATTRIBUTES"]["REDIRECTPAGE"]) ? $xmlArr["ATTRIBUTES"]["REDIRECTPAGE"] : null;        
		$this->parameter = isset($xmlArr["ATTRIBUTES"]["PARAMETER"]) ? $xmlArr["ATTRIBUTES"]["PARAMETER"] : null;        
        $this->eventLogMsg = isset($xmlArr["ATTRIBUTES"]["EVENTLOGMSG"]) ? $xmlArr["ATTRIBUTES"]["EVENTLOGMSG"] : null;
        $this->url = isset($xmlArr["ATTRIBUTES"]["URL"]) ? $xmlArr["ATTRIBUTES"]["URL"] : null;
    }

    /**
     * Set form name that contain element and EventHandler
     * 
     * @param string $formName
     * @param string $elemName
     * @return void
     */
    public function setFormName($formName, $elemName)
    {
        $this->_formName = $formName;
        $this->_elemName = $elemName;
        if (strpos($this->function, "js:")===0)
            return;
        // if no class name, add default class name. i.e. NewRecord => ObjName.NewRecord
        if ($this->function)
        {
            $pos_dot = strpos($this->function, ".");
            $pos_lpt = strpos($this->function, "(");
            if (!$pos_dot || $pos_lpt < $pos_dot)
                $this->function = $this->_formName.".".$this->function;
        }
        $this->translate();	// translate for multi-language support
    }

    /**
     * Adjust form name
     *
     * @param string $formName
     * @return void
     */
    public function adjustFormName($formName)
    {
        $this->_formName = $formName;
        // if no class name, add default class name. i.e. NewRecord => ObjName.NewRecord
        if ($this->function)
        {
        	if(strtolower(substr($this->function,0,3))!='js:'){
				$pos0 = strpos($this->function, "(");
				$len = strlen($this->function);
				if ($pos0 > 0)
					$pos = strrpos($this->function, ".", $pos0-$len);
				else 
					$pos = strrpos($this->function, ".");
				if ($pos > 0)
					$this->function = $this->_formName.".".substr($this->function, $pos+1);
			}
        }
    }

    /**
     * Get formed function
     *
     * @return string
     */
    public function getFormedFunction()
    {
        //return $this->getInvokeAction();
        $name = $this->_elemName;
        $ehName = $this->objectName;
        $formobj = Openbizx::getObject($this->_formName);
        if ($this->formedFunction)
        {
            return $this->formedFunction;
        }        
        if (!$this->formedFunction || $isDataPanelElement==true)
        {
            // add direct URL support
            if ($this->url) 
            {
                $_func = "loadPage('" . $this->url . "');";
                $_func = Expression::evaluateExpression($_func, $formobj);
            }
            else if (strpos($this->function, "js:") === 0) 
            {
                $_func = substr($this->function, 3).";";
                $_func = Expression::evaluateExpression($_func, $formobj);
            }
            else 
            {
                //$temp = ($this->functionType==null) ? "" : ",'".$this->functionType."'";
                //$_func = "SetOnElement('$name:$ehName'); $selectRecord CallFunction('" . $this->function . "'$temp);";
                //$_func = "Openbizx.CallFunction('" . $this->function . "'$temp);";
                $_func = Expression::evaluateExpression($this->function, $formobj);
                $options = "{'type':'$this->functionType','target':'','evthdl':'$name:$ehName'}";
                $_func = "Openbizx.CallFunction('$_func',$options);";
            }
            $this->formedFunction = $_func;
        }
        return $this->formedFunction;
    }
    
    public function getInvokeAction()
    {
        if ($this->formedFunction)
            return $this->formedFunction;
    	$name = $this->_elemName;
        $ehName = $this->objectName;
        $formobj = Openbizx::getObject($this->_formName);
     
        if (!$this->formedFunction)
        {
            // add direct URL support
            if ($this->url)
                $_func = "loadPage('" . $this->url . "');";
            else if (strpos($this->function, "js:") === 0)
                $_func = substr($this->function, 3).";";
            else
            {
                $temp = ($this->functionType==null) ? "" : ",'".$this->functionType."'";                
                //$_func = "SetOnElement('$name:$ehName'); Openbizx.CallFunction('" . $this->function . "'$temp);";
                list($funcName, $funcParams) = $this->parseFunction($this->function);
                $funcParams = Expression::evaluateExpression($funcParams, $formobj);
                $action = "$name:$ehName";
                // TODO: encrypt paramString to add more security
                $_func = "Openbizx.invoke('$this->_formName','$action','$funcParams'$temp);";
            }
            //$_func = Expression::evaluateExpression($_func, $formobj);
            $this->formedFunction = $_func;
        }
        return $this->formedFunction;
    }
    
    // parse function string and get functionName and functionParams
    public function parseFunction($funcString)
    {
        $pos = strpos($funcString, "(");
        $pos1 = strpos($funcString, ")");
        if ($pos>0 && $pos1>$pos)
        {
            $funcName = substr($funcString,0,$pos);
            $funcParams = substr($funcString,$pos+1,$pos1-$pos-1);
            return array($funcName, $funcParams);
        }
        return null;
    }
    
    protected function translate()
    {
    	$module = substr($this->_formName,0,intval(strpos($this->_formName,'.')));
    	if (!empty($this->contextMenu))
    		$this->contextMenu = I18n::t($this->contextMenu, $this->getTransKey('ContextMenu'), $module);
    }
    
    protected function getTransKey($name)
    {
    	$shortFormName = substr($this->formName,intval(strrpos($this->formName,'.'))+1);
    	return strtoupper($shortFormName.'_'.$this->objectName.'_'.$name);
    }
    
}
?>