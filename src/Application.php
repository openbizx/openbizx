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
 * @version   $Id: BizApplication.php 5321 2013-03-21 07:20:24Z rockyswen@gmail.com $
 */

namespace Openbizx;

use Openbizx\Openbizx;
use Openbizx\Core\TypeManager;
use Openbizx\Helpers\XHProf;
use Openbizx\Web\ClientProxy;
use Openbizx\Web\Request;
use Openbizx\Web\SessionContext;
use Openbizx\Web\UserAgent;
use Openbizx\Object\ObjectFactoryHelper;
use Openbizx\Helpers\MessageHelper;

// run controller
//
//session_cache_limiter('public');
//ob_start();


// @todo move to response / clientproxy object
//header('Content-Type: text/html; charset=utf-8');

//include_once("sysheader_inc.php");

/**
 * BizApplication is the class that dispatches client requests to proper objects
 *
 * @package   openbiz.bin
 * @author    Rocky Swen <rocky@phpopenbiz.org>
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @access    public
 */
class Application //extends \Openbizx\Object\Object
{

    const DEFAULT_THEME = 'default';

    /**
     * Request object, initialized when BizApplication created
     * @var Request 
     */
    public $request;
    public $timeZone = 'Asia/jakarta';
    private $_userTimeoutView = OPENBIZ_USER_TIMEOUT_VIEW;
    private $_accessDeniedView = OPENBIZ_ACCESS_DENIED_VIEW;
    private $_securityDeniedView = OPENBIZ_SECURITY_DENIED_VIEW;
    private $_defaultThemeName;
    private $_cssUrl;
    private $_jsUrl;
    private $_currentTheme;
    private $_modulePath = null;

    public function __construct()
    {
        Openbizx::$app = $this;

        //$this->isInitialized = true;
        // preInit
        // registerErrorHandlers
        // registerCoreComponent

        $this->request = new Request($this);
        $this->initSystemDefaultTimezone();

        // define default class alias
        $classAliases = [
            'BizDataObj' => 'Openbizx\\Data\\BizDataObj',
            'BizField' => 'Openbizx\\Data\\BizField',
            'EasyForm' => 'Openbizx\\Easy\\EasyForm',
            'EasyFormTree' => 'Openbizx\\Easy\\EasyForm',
            'WebPage' => 'Openbizx\\Easy\\WebPage',
            'PickerForm' => 'Openbizx\\Easy\\PickerForm',
            'FormReference' => 'Openbizx\\Easy\\FormReference',
            'AutoSuggest' => 'Openbizx\\Easy\\Element\\AutoSuggest',
            'BarcodeScanner' => 'Openbizx\\Easy\\Element\\BarcodeScanner',
            'Button' => 'Openbizx\\Easy\\Element\\Button',
            'Checkbox' => 'Openbizx\\Easy\\Element\\Checkbox',
            'CheckListbox' => 'Openbizx\\Easy\\Element\\CheckListbox',
            'CKEditor' => 'Openbizx\\Easy\\Element\\CKEditor',
            'ColorPicker' => 'Openbizx\\Easy\\Element\\ColorPicker',
            'ColumnBar' => 'Openbizx\\Easy\\Element\\ColumnBar',
            'ColumnBool' => 'Openbizx\\Easy\\Element\\ColumnBool',
            'ColumnHidden' => 'Openbizx\\Easy\\Element\\ColumnHidden',
            'ColumnImage' => 'Openbizx\\Easy\\Element\\ColumnImage',
            'ColumnInputText' => 'Openbizx\\Easy\\Element\\ColumnInputText',
            'ColumnList' => 'Openbizx\\Easy\\Element\\ColumnList',
            'ColumnListbox' => 'Openbizx\\Easy\\Element\\ColumnListbox',
            'ColumnPassword' => 'Openbizx\\Easy\\Element\\ColumnPassword',
            'ColumnShare' => 'Openbizx\\Easy\\Element\\ColumnShare',
            'ColumnSorting' => 'Openbizx\\Easy\\Element\\ColumnSorting',
            'ColumnStyle' => 'Openbizx\\Easy\\Element\\ColumnStyle',
            'ColumnText' => 'Openbizx\\Easy\\Element\\ColumnText',
            'ColumnValue' => 'Openbizx\\Easy\\Element\\ColumnValue',
            'DropDownList' => 'Openbizx\\Easy\\Element\\DropDownList',
            'EditCombobox' => 'Openbizx\\Easy\\Element\\EditCombobox',
            'Element' => 'Openbizx\\Easy\\Element\\Element',
            'EventHandler' => 'Openbizx\\Easy\\Element\\EventHandler',
            'File' => 'Openbizx\\Easy\\Element\\File',
            'FileInput' => 'Openbizx\\Easy\\Element\\FileInput',
            'FileUploader' => 'Openbizx\\Easy\\Element\\FileUploader',
            'FormElement' => 'Openbizx\\Easy\\Element\\FormElement',
            'Hidden' => 'Openbizx\\Easy\\Element\\Hidden',
            'HTMLBlock' => 'Openbizx\\Easy\\Element\\HTMLBlock',
            'HTMLButton' => 'Openbizx\\Easy\\Element\\HTMLButton',
            'HTMLPreview' => 'Openbizx\\Easy\\Element\\HTMLPreview',
            'IDCardReader' => 'Openbizx\\Easy\\Element\\IDCardReader',
            'IFrameBox' => 'Openbizx\\Easy\\Element\\IFrameBox',
            'ImageSelector' => 'Openbizx\\Easy\\Element\\ImageUploader',
            'ImageUploader' => 'Openbizx\\Easy\\Element\\ImageUploader',
            'InputDate' => 'Openbizx\\Easy\\Element\\InputDate',
            'InputDateRangePicker' => 'Openbizx\\Easy\\Element\\InputDateRangePicker',
            'InputDatetime' => 'Openbizx\\Easy\\Element\\InputDatetime',
            'InputElement' => 'Openbizx\\Easy\\Element\\InputElement',
            'InputPassword' => 'Openbizx\\Easy\\Element\\InputPassword',
            'InputPicker' => 'Openbizx\\Easy\\Element\\InputPicker',
            'InputText' => 'Openbizx\\Easy\\Element\\InputText',
            'LabelBack' => 'Openbizx\\Easy\\Element\\LabelBack',
            'LabelBar' => 'Openbizx\\Easy\\Element\\LabelBar',
            'LabelBool' => 'Openbizx\\Easy\\Element\\LabelBool',
            'LabelImage' => 'Openbizx\\Easy\\Element\\LabelImage',
            'LabelList' => 'Openbizx\\Easy\\Element\\LabelList',
            'LabelPassword' => 'Openbizx\\Easy\\Element\\LabelPassword',
            'LabelText' => 'Openbizx\\Easy\\Element\\LabelText',
            'LabelTextarea' => 'Openbizx\\Easy\\Element\\LabelTextarea',
            'LabelTextPaging' => 'Openbizx\\Easy\\Element\\LabelTextPaging',
            'Listbox' => 'Openbizx\\Easy\\Element\\Listbox',
            'OptionElement' => 'Openbizx\\Easy\\Element\\OptionElement',
            'PageSelector' => 'Openbizx\\Easy\\Element\\PageSelector',
            'PagesizeSelector' => 'Openbizx\\Easy\\Element\\PagesizeSelector',
            'Password' => 'Openbizx\\Easy\\Element\\Password',
            'Radio' => 'Openbizx\\Easy\\Element\\Radio',
            'RawData' => 'Openbizx\\Easy\\Element\\RawData',
            'ResetButton' => 'Openbizx\\Easy\\Element\\ResetButton',
            'RichText' => 'Openbizx\\Easy\\Element\\RichText',
            'RowCheckbox' => 'Openbizx\\Easy\\Element\\RowCheckbox',
            'Spacer' => 'Openbizx\\Easy\\Element\\Spacer',
            'SubmitButton' => 'Openbizx\\Easy\\Element\\SubmitButton',
            'Textarea' => 'Openbizx\\Easy\\Element\\Textarea',
            'TreeLabelText' => 'Openbizx\\Easy\\Element\\TreeLabelText',
            'TreeListbox' => 'Openbizx\\Easy\\Element\\TreeListbox',
        ];

        Openbizx::objectFactory()->setClassAliases($classAliases);
    }

    private $_confgiuration = null;

    /**
     * Get the Configuration object
     *
     * @return Configuration the Configuration object
     */
    public function getConfiguration()
    {
        if (!$this->_confgiuration) {
            $this->_confgiuration = new ApplicationConfiguration();
        }
        return $this->_confgiuration;
    }

    private $_clientProxy = null;

    /**
     * Get the ClientProxy object
     *
     * @return ClientProxy the ClientProxy object
     */
    public function getClientProxy()
    {
        if (!$this->_clientProxy) {
            $this->_clientProxy = new ClientProxy();
        }
        return $this->_clientProxy;
    }

    private $_sessionContext = null; // instant of SessionContext class

    /**
     * Get the SessionContext object
     *
     * @return \Openbizx\Web\SessionContext the SessionContext object
     */

    public function getSessionContext()
    {
        if (!$this->_sessionContext) {
            $this->_sessionContext = new SessionContext();
            $this->_sessionContext->retrieveSessionObjects();
        }
        return $this->_sessionContext;
    }

    private $_typeManager = null;

    /**
     * Get the TypeManager object
     *
     * @return TypeManager the TypeManager object
     */
    public function getTypeManager()
    {
        if (!$this->_typeManager) {
            $this->_typeManager = new TypeManager();

            /* @var $localeInfoService localeInfoService */
            $localeInfoService = Openbizx::getService(LOCALEINFO_SERVICE);
            $localeInfo = $localeInfoService->getLocaleInfo();

            if ($localeInfo) {
                $this->_typeManager->setLocaleInfo($localeInfo);
            }
        }
        return $this->_typeManager;
    }

    private $_dbConnection = array();

    /**
     * Get the database connection object
     *
     * @param string $dbName    database name declared in config.xml
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getDBConnection($dbName = null)
    {
        $rDBName = (!$dbName) ? "Default" : $dbName;
        if (isset($this->_dbConnection[$rDBName])) {
            $db = $this->_dbConnection[$rDBName];
            if (!CLI) {
                return $db;
            }
        }

        $dbInfo = $this->getConfiguration()->getDatabaseInfo($rDBName);

        //require_once 'Zend/Db.php';

        $params = array(
            'host' => $dbInfo["Server"],
            'username' => $dbInfo["User"],
            'password' => $dbInfo["Password"],
            'dbname' => $dbInfo["DBName"],
            'port' => $dbInfo["Port"],
            'charset' => $dbInfo["Charset"]
        );
        if ($dbInfo["Options"]) {
            $options = explode(";", $dbInfo["Options"]);
            foreach ($options as $opt) {
                list($k, $v) = explode("=", $opt);
                $params[$k] = $v;
            }
        }
        foreach ($params as $name => $val) {
            if (empty($val)) {
                unset($params[$name]);
            }
        }
        if (strtoupper($dbInfo["Driver"]) == "PDO_MYSQL") {
            $pdoParams = array(
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            );
            $params["driver_options"] = $pdoParams;
        }

        $db = \Zend_Db::factory($dbInfo["Driver"], $params);
        $db->setFetchMode(\PDO::FETCH_NUM);

        if (strtoupper($dbInfo["Driver"]) == "PDO_MYSQL" &&
                $dbInfo["Charset"] != "") {
            $db->query("SET NAMES '" . $params['charset'] . "'");
        }
        $this->_dbConnection[$rDBName] = $db;
        return $db;
    }

    /**
     * Remove database connection by name
     * @param string $dbName
     * @return \Zend_Db_Adapter_Abstract
     */
    public function removeDBConnection($dbName = "Default")
    {
        if (isset($this->_dbConnection[$dbName])) {
            $this->_dbConnection[$dbName]->closeConnection();
            unset($this->_dbConnection[$dbName]);
        }
        return $this->getDBConnection($dbName);
    }

//=========================================


    private $_defaultModule = 'user';

    /**
     * Get default URL
     * @return string
     */
    public function getDefaultModule()
    {
        return $this->_defaultModule;
    }

    /**
     * Set default module name.
     * @param string $module new module name
     */
    public function setDefaultModule($module)
    {
        $this->_defaultModule = $module;
    }

    private $_defaultShortView = "LoginView";

    /**
     * Get default view name, without namespace
     * @return type
     */
    public function getDefaultShortView()
    {
        return $this->_defaultShortView;
    }

    /**
     * Set default view
     * @param string $view view name, without namespace
     */
    public function setDefaultShortView($view)
    {
        $this->_defaultShortView = $view;
    }

    private $_defaultUrl;

    /**
     * Get default URL.
     * @return string
     */
    public function getDefaultUrl()
    {
        if (!$this->_defaultUrl) {
            $this->_defaultUrl = 'index.php/' . $this->getDefaultModule() . ' /login';
        }
        return $this->_defaultUrl;
    }

    /**
     * Set default URL.
     * @param string $url The url that will be set as default.
     */
    public function setDefaultUrl($url)
    {
        $this->_defaultUrl = $url;
    }

    /**
     * Process Security Filters
     *
     * @return boolean true if success, and false if have error
     */
    public function processSecurityFilters()
    {
        $securityService = Openbizx::getService(SECURITY_SERVICE);
        $securityService->processFilters();
        $err_msg = $securityService->getErrorMessage();
        if ($err_msg) {
            if ($this->_securityDeniedView) {
                $view = $this->_securityDeniedView;
            } else {
                $view = $this->_accessDeniedView;
            }
            $this->renderView($view);
            return false;
        }
        return true;
    }

    /**
     * Dispatches client requests to proper objects, print the returned html text.
     *
     * @return void
     */
    public function dispatchRequest()
    {
        if ($this->request->hasInvocation()) {
            if ($this->isSessionTimeout()) {
                $this->getSessionContext()->destroy();
                $this->getClientProxy()->redirectView($this->_userTimeoutView);
            }
            $this->dispatchRPC();
        } else {
            $this->dispatchView();
        }
    }

    /**
     * Get the parameter from the url
     *
     * @return array parameter array
     */
    private function getParameters()
    {
        $getKeys = array_keys($_GET);
        $params = null;
        // read parameters "param:name=value"
        foreach ($getKeys as $key) {
            if (substr($key, 0, 6) == "param:") {
                $paramName = substr($key, 6);
                $paramValue = $_GET[$key];
                $params[$paramName] = $paramValue;
            }
        }
        return $params;
    }

    /**
     * Check if session timed out.
     *
     * @return boolean true - session timed out, false - session alive
     */
    private function isSessionTimeout()
    {
        return $this->getSessionContext()->isTimeout();
    }

    /**
     * Check if the view can be accessed by current user. Call accessService to do the check
     *
     * @param string $viewName view name
     * @return boolean true= allow, false not allow
     */
    private function canUserAccessView($viewName)
    {
        $svcobj = Openbizx::getService(ACCESS_SERVICE);
        return $svcobj->allowViewAccess($viewName);
    }

    /**
     * Render a bizview
     *
     * @param string $viewName name of bizview
     * @param string $rule the search rule of a bizform who is not depent on (a subctrl of) another bizform
     * @return void
     */
    public function renderView($viewName, $form = "", $rule = "", $params = null, $hist = "")
    {
        /* @var $webpage \Openbizx\Easy\WebPage */
        if ($viewName == "__DynPopup") {
            $webpage = Openbizx::getWebpageObject($viewName);
            $webpage->render();
            return;
        }        
        $this->setCurrentViewName($viewName);
        $webpage = Openbizx::getWebpageObject($viewName);
        if (!$webpage) {
            return;
        }
        
        $viewSet = $webpage->getViewSet();
        $this->setCurrentViewSet($viewSet);
        
        $this->getSessionContext()->clearSessionObjects(true);

        if ($hist == "N") { // clean view history
            $webpage->cleanViewHistory();
        }
        if ($form != "" && $rule != "") {
            $webpage->processRule($form, $rule, TRUE);
        }
        if ($params) {
            $webpage->setParameters($params);
        }
        if (isset($_GET['mode'])) {   // can specify mode of form
            $webpage->setFormMode($form, $_GET['mode']);
        }
        $webpage->render();
    }

    /**
     * Invoke the action passed from browser
     *
     * @return HTML content
     */
    protected function invokeRPC()
    {
        $request = $this->request;
        if (!$request->hasInvocation()) {
            return null;
        }
        if (!$request->isValidInvocation()) {
            $invocationType = $request->getInvocationType();
            trigger_error("$invocationType is not a valid invocation", E_USER_ERROR);
            return;
        }
        if ($request->isRPCInvokeInvocation()) {
            $this->getClientProxy()->setRpcFlag(true);
        }
        $rpcParams = $this->request->getRpcParameters();

        $num_arg = count($rpcParams);
        if ($num_arg < 2) {
            //$errmsg = MessageHelper::getMessage("SYS_ERROR_RPCARG", array($class));
            $errmsg = MessageHelper::getMessage("SYS_ERROR_RPCARG");
            trigger_error($errmsg, E_USER_ERROR);
        } else {
            $objName = array_shift($rpcParams);
            $methodName = array_shift($rpcParams);
            return $this->executeRpcMethod($objName, $methodName, $rpcParams);
        }
    }

    private function executeRpcMethod($objName, $methodName, $params)
    {
        $obj = Openbizx::getObject($objName);
        if ($obj) {
            if (method_exists($obj, $methodName)) {
                if (!$this->validateRequest($obj, $methodName)) {
                    $errmsg = MessageHelper::getMessage("SYS_ERROR_REQUEST_REJECT", array($obj->objectName, $methodName));
                    trigger_error($errmsg, E_USER_ERROR);
                }
                switch (count($params)) {
                    case 0: $rt_val = $obj->$methodName();
                        break;
                    case 1: $rt_val = $obj->$methodName($params[0]);
                        break;
                    case 2: $rt_val = $obj->$methodName($params[0], $params[1]);
                        break;
                    case 3: $rt_val = $obj->$methodName($params[0], $params[1], $params[2]);
                        break;
                    default: $rt_val = call_user_func_array(array($obj, $methodName), $params);
                }
            } else {
                $errmsg = MessageHelper::getMessage("SYS_ERROR_METHODNOTFOUND", array($objName, $methodName));
                trigger_error($errmsg, E_USER_ERROR);
            }
        } else {
            $errmsg = MessageHelper::getMessage("SYS_ERROR_CLASSNOTFOUND", array($objName));
            trigger_error($errmsg, E_USER_ERROR);
        }

        $invocationType = $this->request->getInvocationType();
        if ($invocationType == "Invoke") {  // no RPC invoke, page reloaded -> rerender view
            if ($this->getClientProxy()->hasOutput()) {
                $this->getClientProxy()->printOutput();
            }
        } else if ($invocationType == "RPCInvoke") {  // RPC invoke
            if ($this->getClientProxy()->hasOutput()) {
                if ($_REQUEST['jsrs'] == 1) {
                    echo "<html><body><form name=\"jsrs_Form\"><textarea name=\"jsrs_Payload\" id=\"jsrs_Payload\">";
                }
                $this->getClientProxy()->printOutput();
                if ($_REQUEST['jsrs'] == 1) {
                    echo "</textarea></form></body></html>";
                }
            } else {
                return $rt_val;
            }
        }
    }

    /**
     * Validate the request from client.
     *
     * @param object $obj the to be called object
     * @param string $methodName the to be called method name
     * @return boolean
     */
    protected function validateRequest($obj, $methodName)
    {
        if (is_a($obj, "Openbizx\Easy\EasyForm") || is_a($obj, "BaseForm")) {
            if ($obj->validateRequest($methodName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Dispatch request to view
     */
    private function dispatchView()
    {
        $request = $this->request;

        if (!ObjectFactoryHelper::getXmlFileWithPath($request->view)) {
            $this->renderNotFoundView();
            exit;
        }
        if (!$this->canUserAccessView($request->view)) {  //access denied error
            $this->renderView($this->_accessDeniedView);
        }
        $this->renderView($request->view, $request->form, $request->rule, $request->params, $request->hist);
    }

    private function renderNotFoundView()
    {
        if (defined('OPENBIZ_NOTFOUND_VIEW')) {
            $request = $this->request;
            $this->renderView(OPENBIZ_NOTFOUND_VIEW, $request->form, $request->rule, $request->params, $request->hist);
        } else {
            throw new Exception("'Not Found View' not defined.");
        }
    }

    /**
     * Dispatch request as RPC (remote procedure call)
     */
    public function dispatchRPC()
    {
        if ($this->request->hasContainerView()) {
            $this->setCurrentViewName($this->request->getContainerViewName());
        }
        $retval = $this->invokeRPC();
        print($retval . " "); // why use space on end of data?
        exit();
    }

    /**
     * Goto default view of module
     *
     * @param string $pmodule
     * @todo need to move to front controller
     */
    public function redirectToDefaultModuleView($pmodule)
    {
        $module = strtolower($pmodule);
        $modfile = $this->getModulePath() . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'mod.xml';
        $xml = simplexml_load_file($modfile);
        $defaultURL = OPENBIZ_APP_INDEX_URL . $xml->Menu->MenuItem['URL'];
        header("Location: $defaultURL");
    }

    /**
     * Goto default view of user
     *
     * @todo have options : redirect or renderDefaultView
     */
    public function redirectToDefaultUserView()
    {
        $profile = $this->getUserProfile();
        if ($profile['roleStartpage'][0]) {
            $DEFAULT_URL = OPENBIZ_APP_INDEX_URL . $profile['roleStartpage'][0];
        }
        header("Location: $DEFAULT_URL");
        exit;
    }

    /**
     * Run application
     */
    public function run()
    {
        $this->onBeforeRun();
        $this->getSessionContext();
        $eventlog = Openbizx::getService(OPENBIZ_EVENTLOG_SERVICE);
        $logComment = array('address', $_SERVER['REMOTE_ADDR']);
        $eventlog->log("LOGIN", "MSG_LOGIN_SUCCESSFUL", $logComment);
        if ($this->processSecurityFilters()) {
            $this->dispatchRequest();
        }
        $this->onAfterRun();
    }

    public function onBeforeRun()
    {
        XHProf::setEnable(true);
        XHProf::setPath('/Users/jixian/xhprof/');
        XHProf::setUrl('http://localhost/xhprof/xhprof_html/index.php?source=xhprof_testing&run=');
        XHProf::start();
    }

    public function onAfterRun()
    {
        XHProf::finish();
    }

    public function getDefaultThemeName()
    {
        if ($this->_defaultThemeName !== null) {
            $userAgent = new UserAgent();
            if ($userAgent->isTouch()) {
                $this->_defaultThemeName = 'touch';
            } else {
                $this->_defaultThemeName = 'default';
            }
        }
    }

    public $isForceDefaultTheme = false;
    private $_themeName;

    public function getThemeName()
    {
        if ($this->_themeName !== null) {
            if ($this->isForceDefaultTheme) {
                $this->_themeName = $this->getDefaultThemeName();
            } else {
                if (isset($_GET['theme'])) {
                    $this->_themeName = $_GET['theme'];
                    setcookie("OPENBIZ_THEME_NAME", $_GET['theme'], time() + 86400 * 365, "/");
                } elseif (isset($_COOKIE['OPENBIZ_THEME_NAME'])) {
                    $this->_themeName = $_COOKIE['OPENBIZ_THEME_NAME'];
                } else {
                    $this->_themeName = $this->getDefaultThemeName();
                }
            }
        }
        return $this->_themeName;
    }

    protected function initSystemDefaultTimezone()
    {
        $defaultTimezone = $this->getSessionContext()->getVar("TIMEZONE");
        if ($defaultTimezone == "") {
            $defaultTimezone = $this->timeZone;
        }
        date_default_timezone_set($defaultTimezone);
    }

    //=========== USER MANAGEMENT

    /**
     * Check if user can access the given resource action
     *
     * @param string $resourceAction resource action
     * @return boolean true or false
     */
    public function allowUserAccess($resourceAction)
    {
        $serviceObj = Openbizx::getService(ACL_SERVICE);
        return $serviceObj->allowAccess($resourceAction);
    }

    /**
     * Initialize User Profile
     *
     * @param string $username
     * @return array Profile array
     * @todo need to move, only used in profileService
     */
    public function initUserProfile($username)
    {
        /* @var $profileService profileService */
        $profileService = Openbizx::getService(PROFILE_SERVICE);
        if (method_exists($profileService, 'InitProfile')) {
            $profile = $profileService->initProfile($username);
        } else {
            $profile = $profileService->getProfile($username);
        }
        $this->getSessionContext()->setVar("_USER_PROFILE", $profile);
        return $profile;
    }

    /**
     * Get user profile
     *
     * @param string $attribute user attribute
     * @return array user profile array
     */
    public function getUserProfile($attribute = null)
    {
        if (!ObjectFactoryHelper::getXmlFileWithPath(PROFILE_SERVICE)) {
            return null;
        }
        $profileService = Openbizx::getService(PROFILE_SERVICE);
        $profile = $this->getSessionContext()->getVar("_USER_PROFILE");
        if (method_exists($profileService, 'getProfile')) {
            return $profileService->getProfile($attribute);
        } else {
            $profile = $this->getSessionContext()->getVar("_USER_PROFILE");
            return isset($profile[$attribute]) ? $profile[$attribute] : "";
        }
    }

    /**
     * Get user preference
     *
     * @param string $attribute key that representing attribute
     * @return mixed
     */
    public function getUserPreference($attribute = null)
    {
        if (!ObjectFactoryHelper::getXmlFileWithPath(OPENBIZ_PREFERENCE_SERVICE)) {
            return null;
        }
        $preferenceService = Openbizx::getService(OPENBIZ_PREFERENCE_SERVICE);
        if (method_exists($preferenceService, 'getPreference')) {
            return $preferenceService->getPreference($attribute);
        } else {
            $preference = $this->getSessionContext()->getVar("_USER_PREFERENCE");
            return isset($preference[$attribute]) ? $preference[$attribute] : "";
        }
    }

    /**
     * Get default user perm
     * @param type $pgroup
     * @return string
     */
    public function getDefaultPerm($pgroup)
    {
        $group = strtolower($pgroup);
        switch ($group) {
            default:
            case 'owner':
                $setting = $this->getUserPreference('owner_perm');
                if ($setting != '') {
                    $perm_code = $setting;
                } else {
                    $perm_code = OPENBIZ_DEFAULT_OWNER_PERM;
                }
                break;
            case 'group':
                $setting = $this->getUserPreference('owner_group');
                if ($setting != '') {
                    $perm_code = $setting;
                } else {
                    $perm_code = OPENBIZ_DEFAULT_GROUP_PERM;
                }
                break;
            case 'other':
                $setting = $this->getUserPreference('owner_other');
                if ($setting != '') {
                    $perm_code = $setting;
                } else {
                    $perm_code = OPENBIZ_DEFAULT_OTHER_PERM;
                }
                break;
        }
        return $perm_code;
    }

    private $_currentViewName = "";

    /**
     * Get the current view name
     *
     * @return string current view name
     */
    public function getCurrentViewName()
    {
        if ($this->_currentViewName == "") {
            $this->_currentViewName = $this->getSessionContext()->getVar("CVN"); // CVN stands for CurrentViewName
        }   
        return $this->_currentViewName;
    }

    /**
     * Set the current view name
     *
     * @param string $viewname new current view name
     */
    public function setCurrentViewName($viewname)
    {
        $this->_currentViewName = $viewname;
        $this->getSessionContext()->setVar("CVN", $this->_currentViewName);   // CVN stands for CurrentViewName
    }

    private $_currentViewSet = "";

    /**
     * Get the current view set
     *
     * @return string current view set
     */
    public function getCurrentViewSet()
    {
        if ($this->_currentViewSet == "") {
            $this->_currentViewSet = $this->getSessionContext()->getVar("CVS");
        }   // CVS stands for CurrentViewSet
        return $this->_currentViewSet;
    }

    /**
     * Set current view set
     *
     * @param <type> $viewSet
     */
    public function setCurrentViewSet($viewSet)
    {
        $this->_currentViewSet = $viewSet;
        $this->getSessionContext()->setVar("CVS", $this->_currentViewSet);   // CVS stands for CurrentViewSet
    }

    /**
     * get log service
     * @return logService
     */
    public function getLog()
    {
        return Openbizx::getService(LOG_SERVICE);
    }

    public function getProfile()
    {
        return Openbizx::getService(PROFILE_SERVICE);
    }

    /**
     * @var string the root directory of the application.
     */
    private $_basePath;

    /**
     * Returns the root directory of the application.
     * It defaults to the directory containing the application class file.
     * 
     * Note: this method will replace OPENBIZ_APP_PATH const.
     * 
     * @return string the root directory of the module.
     * @see OPENBIZ_APP_PATH
     */
    public function getBasePath()
    {
        if ($this->_basePath === null) {
            $class = new \ReflectionClass($this);
            $this->_basePath = dirname($class->getFileName());
        }
        return $this->_basePath;
    }

    /**
     * Sets the root directory of the module.
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the module. This can be either a directory name or a path alias.
     * @throws InvalidParamException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        //$path = Yii::getAlias($path);
        $p = realpath($path);
        if ($p !== false && is_dir($p)) {
            $this->_basePath = $p;
        } else {
            throw new InvalidParamException("The directory does not exist: $path");
        }
    }

    private $_vendorPath;

    /**
     * Returns the directory that stores vendor files.
     * @return string the directory that stores vendor files.
     * Defaults to "vendor" directory under [[basePath]].
     */
    public function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
        }
        return $this->_vendorPath;
    }

    /**
     * Sets the directory that stores vendor files.
     * @param string $path the directory that stores vendor files.
     */
    public function setVendorPath($path)
    {
        $this->_vendorPath = Yii::getAlias($path);
        Yii::setAlias('@vendor', $this->_vendorPath);
    }

    /**
     * Returns the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_get().
     * If time zone is not configured in php.ini or application config,
     * it will be set to UTC by default.
     * @return string the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-get.php
     */
    public function getTimeZone()
    {
        return date_default_timezone_get();
    }

    /**
     * Sets the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_set().
     * @param string $value the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-set.php
     */
    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }

    private $_imageUrl;

    /**
     * Get image URL
     * @return string
     */
    public function getImageUrl()
    {
        if (isset($this->_imageUrl)) {
            return $this->_imageUrl;
        }
        $useTheme = !defined('OPENBIZ_USE_THEME') ? 0 : OPENBIZ_USE_THEME;
        $themeUrl = !defined('OPENBIZ_THEME_URL') ? "../themes" : OPENBIZ_THEME_URL;
        $themeName = $this->getCurrentTheme();
        if ($useTheme) {
            $this->_imageUrl = "$themeUrl/$themeName/images";
        } else {
            $this->_imageUrl = "../images";
        }
        return $this->_imageUrl;
    }

    /**
     * Get CSS URL
     * @return string
     */
    public function getCssUrl()
    {
        if (isset($this->_cssUrl)) {
            return $this->_cssUrl;
        }
        $useTheme = !defined('OPENBIZ_USE_THEME') ? 0 : OPENBIZ_USE_THEME;
        $themeUrl = !defined('OPENBIZ_THEME_URL') ? OPENBIZ_APP_URL . "/themes" : OPENBIZ_THEME_URL;
        $themeName = $this->getCurrentTheme();
        if ($useTheme) {
            $this->_cssUrl = "$themeUrl/$themeName/css";
        } else {
            $this->_cssUrl = OPENBIZ_APP_URL . "/css";
        }
        return $this->_cssUrl;
    }

    /**
     * Get JavaScript(JS) URL
     * @return string
     */
    public function getJsUrl()
    {
        if (isset($this->_jsUrl)) {
            return $this->_jsUrl;
        }
        $this->_jsUrl = !defined('OPENBIZ_JS_URL') ? OPENBIZ_APP_URL . "/js" : OPENBIZ_JS_URL;
        return $this->_jsUrl;
    }

    /**
     * theme selection priority: url, session, userpref, system(constant)
     * @todo user pereference has language setting
     * @return type
     */
    public function getCurrentTheme()
    {
        if ($this->_currentTheme != null) {
            return $this->_currentTheme;
        }
        $currentTheme = "";
        if (isset($_GET['theme'])) {
            $currentTheme = $_GET['theme'];
        }
        if ($currentTheme == "") {
            $currentTheme = $this->getSessionContext()->getVar("THEME");
        }
        if ($currentTheme == "") {
            $currentTheme = $this->getUserPreference("theme");
        }
        if ($currentTheme == "" && defined('OPENBIZ_THEME_NAME')) {
            $currentTheme = OPENBIZ_THEME_NAME;
        }
        if ($currentTheme == "") {
            $currentTheme = self::DEFAULT_THEME;
        }
        $this->getSessionContext()->setVar("THEME", $currentTheme);
        $this->_currentTheme = $currentTheme;
        return $currentTheme;
    }

    /**
     * Get module path of application.
     * @return string
     */
    public function getModulePath()
    {
        if ($this->_modulePath === null) {
            $this->_modulePath = OPENBIZ_APP_PATH . DIRECTORY_SEPARATOR . "modules";
        }
        return $this->_modulePath;
    }

    /**
     * Set module path of application
     * @param string $path
     */
    public function setModulePath($path)
    {
        $this->_modulePath = $path;
    }

}
