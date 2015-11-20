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
 * @version   $Id$
 */

namespace Openbizx\Web;

use Openbizx\Web\UrlManager;

/**
 * Description of Request
 *
 * @author k6
 */
class Request
{

    CONST INVOCATION_TYPE_INVOKE = 'Invoke';
    CONST INVOCATION_TYPE_RPC_INVOKE = 'RPCInvoke';
    CONST RPC_BOOTSTRAP = true;

    /**
     * Invocation type 'RPCInvoke', 'Invoke' or other.
     * Other is not valid invocation.
     * 
     * @var string 
     */
    public $invocationType;
    public $params;
    public $form;
    public $rule;
    public $hist;
    public $view;
    public $targetView;
    private $_urlManager;

    /**
     * Initialize request object.
     * @param BizApplication $controller
     */
    public function __construct($controller)
    {
        $this->_urlManager = new UrlManager($this);
        $requestInfo = $this->_urlManager->parserRequestUri($this);

        if ( $requestInfo['module'] ==='' ) {
            $requestInfo['module'] = $controller->getDefaultModule();
        }
        if ( $requestInfo['shortView'] ==='' ) {
           $requestInfo['shortView'] = $controller->getDefaultShortView();
        }

        //echo 'shortView : ' . $requestInfo['shortView'] . '<br />';
        if ( !isset($_GET['view']) ) {
            $view = $requestInfo['module'] . ".view." . $requestInfo['shortView'];
            $_REQUEST['view'] = $view;
            $_GET['view'] = $_REQUEST['view'];
        }

        $PARAM_MAPPING = $requestInfo['uriParams'];
        if (isset($PARAM_MAPPING)) {
            foreach ($PARAM_MAPPING as $param => $value) {
                //if (isset($_GET[$param]))
                $_GET[$param] = $_REQUEST[$param] = $value;
            }
        }
        
        $this->view = isset($_GET['view']) ? $_GET['view'] : "";
        $this->form = isset($_GET['form']) ? $_GET['form'] : "";
        $this->rule = isset($_GET['rule']) ? $_GET['rule'] : "";
        $this->hist = isset($_GET['hist']) ? $_GET['hist'] : "";

        $this->_convertPostUrl2GetVars();
        //echo __METHOD__.__LINE__ . ' view : ' . $this->view . '<br />';
        //DebugLine::show(var_dump($_REQUEST));
        //DebugLine::show(var_dump($_GET));
    }

    private $_uri;

    /**
     * Resolves the request URI portion for the currently requested URL.
     * This refers to the portion that is after the [[hostInfo]] part. It includes the [[queryString]] part if any.
     * The implementation of this method referenced \Zend_Controller_Request_Http in \Zend Framework.
     *
     * @return string|boolean the request URI portion for the currently requested URL.
     * Note that the URI returned is URL-encoded.
     * @throws InvalidConfigException if the request URI cannot be determined due to unusual server configuration
     */
    public function getUri()
    {
        if (!isset($this->_uri)) {
            $this->_uri = $this->resolveUri();
        }
        return $this->_uri;
    }

    private $_pathUri;

    /**
     * Resolves the request URI portion for the currently requested URL.
     * This refers to the portion that is after the [[hostInfo]] part. It includes the [[queryString]] part if any.
     * The implementation of this method referenced \Zend_Controller_Request_Http in \Zend Framework.
     *
     * @return string|boolean the request URI portion for the currently requested URL.
     * Note that the URI returned is URL-encoded.
     * @throws InvalidConfigException if the request URI cannot be determined due to unusual server configuration
     */
    public function getPathUri()
    {
        if (!isset($this->_pathUri)) {
            $this->_pathUri = $this->resolvePathUri();
        }
        return $this->_pathUri;
    }

    public function resolvePathUriX()
    {
        $script = quotemeta($_SERVER['SCRIPT_NAME']);
        echo 'script  : ' . $script . '<br />';

        $pattern = "|^$script?\?\/?(.*?)(\.html)?$|si";

        if ($_SERVER["REDIRECT_QUERY_STRING"]) {
            $requestUri = $_SERVER["REDIRECT_QUERY_STRING"];
        } elseif (preg_match($pattern, $_SERVER['REQUEST_URI'], $match)) {
            echo __METHOD__ . ' ? ' . '<br />';
            //supports for http://localhost/?/user/login format
            //supports for http://localhost/index.php?/user/login format
            $requestUri = $match[1];
        } elseif (strlen($_SERVER['REQUEST_URI']) > strlen($_SERVER['SCRIPT_NAME'])) {
            echo __METHOD__ . ' not ? ' . '<br />';
            //supports for http://localhost/index.php/user/login format
            $requestUri = str_replace($_SERVER['SCRIPT_NAME'], "", $_SERVER['REQUEST_URI']);
            preg_match("/\/?(.*?)(\.html)?$/si", $requestUri, $match);
            $requestUri = $match[1];
        } else {
            // REQUEST_URI = /cubi/
            // SCRIPT_NAME = /cubi/index.php
            $requestUri = "";
        }
        //remove repeat slash //
        $requestUri = preg_replace("/([\/\/]+)/", "/", $requestUri);
        preg_match("/\/?(.*?)(\.html)?$/si", $requestUri, $match);
        $requestUri = $match[1];
        return $requestUri;
    }

    /**
     * Resolves the request URI portion for the currently requested URL.
     * This refers to the portion that is after the [[hostInfo]] part. It includes the [[queryString]] part if any.
     * The implementation of this method referenced \Zend_Controller_Request_Http in \Zend Framework.
     * @return string|boolean the request URI portion for the currently requested URL.
     * Note that the URI returned is URL-encoded.
     * @throws InvalidConfigException if the request URI cannot be determined due to unusual server configuration
     */
    protected function resolveUri()
    {
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new InvalidConfigException('Unable to determine the request URI.');
        }
        return $requestUri;
    }

    /**
     * Get invocation type 'RPCInvoke', 'Invoke' or other.
     * 
     * @return string
     */
    public function getInvocationType()
    {
        return (isset($_REQUEST['F']) ? $_REQUEST['F'] : "");
    }

    public function hasInvocation()
    {
        $invocationType = $this->getInvocationType();
        return ( $invocationType != '' );
    }

    /**
     * Check is invocation valid ('RPCInvoke' or 'Invoke') ?
     *
     * @return type
     */
    public function isValidInvocation()
    {
        $invocationType = $this->getInvocationType();
        return ( $invocationType == self::INVOCATION_TYPE_RPC_INVOKE || $invocationType == self::INVOCATION_TYPE_INVOKE );
    }

    public function isRPCInvokeInvocation()
    {
        return ($this->getInvocationType() === self::INVOCATION_TYPE_RPC_INVOKE);
    }

    public function isInvokeInvocation()
    {
        return ($this->getInvocationType() === self::INVOCATION_TYPE_INVOKE);
    }

    public function isRpcBootstrap()
    {
        return !isset($_GET['view']);
    }

    /**
     * Get the parameter from the url
     *
     * @return array parameter array
     */
    public function getParameters()
    {
        if (!$this->params) {
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
            $this->params = $params;
        } else {
            return $params;
        }
    }

    /**
     * Check whether the request in the form view, not rpc
     *
     * @return boolean
     */
    public function hasView()
    {
        return isset($_GET['view']);
    }

    /**
     * Check whether the request has '__url' on POST variable     *
     * @return type
     */
    public function hasPostUrl()
    {
        return isset($_POST['__url']);
    }

    public function getPostUrl()
    {
        return $_POST['__url'];
    }

    public function hasUri()
    {
        return ( !($this->getUri() === '')  );
    }

    public function getRpcParameters()
    {
        $arg_list = array();
        $i = 0;

        eval("\$P$i = (isset(\$_REQUEST['P$i']) ? \$_REQUEST['P$i']:'');");
        $Ptmp = "P" . $i;

        eval("\$P$i = (isset(\$_REQUEST['P$i']) ? \$_REQUEST['P$i']:'');");

        if (strstr($P0, Popup_Suffix)) { // _popupx_?
            $name_len = strlen($P0);
            $suffix_len = strlen(Popup_Suffix);
            $P0 = substr($P0, 0, $name_len - $suffix_len - 1) . "]";
        }

        while ($$Ptmp != "") {
            $parm = $$Ptmp;
            $parm = substr($parm, 1, strlen($parm) - 2);
            $arg_list[] = $parm;
            $i++;
            eval("\$P$i = (isset(\$_REQUEST['P$i']) ? \$_REQUEST['P$i']:'');");
            $Ptmp = "P" . $i;
        }
        return $arg_list;
    }

    private function _convertPostUrl2GetVars()
    {
        //patched by jixian for fix ajax post data
        if ($this->hasPostUrl()) {
            $getUrl = parse_url($_POST['__url']);
            $query = $getUrl['query'];
            $parameter = explode('&', $query);
            foreach ($parameter as $param) {
                $data = explode('=', $param);
                $name = $data[0];
                $value = $data[1];
                $_GET[$name] = $value;
            }
        }
    }

    /**
     * Resolves the path info part of the currently requested URL.
     * A path info refers to the part that is after the entry script and before the question mark (query string).
     * The starting slashes are both removed (ending slashes will be kept).
     * @return string part of the request URL that is after the entry script and before the question mark.
     * Note, the returned path info is decoded.
     * @throws InvalidConfigException if the path info cannot be determined due to unexpected server configuration
     */
    public function resolvePathUri()
    {
        $pathInfo = $this->getUri();

        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        $pathInfo = urldecode($pathInfo);



        // try to encode in UTF8 if not so
        // http://w3.org/International/questions/qa-forms-utf-8.html
        if (!preg_match('%^(?:
				[\x09\x0A\x0D\x20-\x7E]              # ASCII
				| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
				| \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
				| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
				| \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
				| \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
				| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
				)*$%xs', $pathInfo)) {
            $pathInfo = utf8_encode($pathInfo);
        }

        $scriptUrl = $this->getScriptUrl();
        $baseUrl = $this->getBaseUrl();
        if (strpos($pathInfo, $scriptUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
        } elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        } elseif (strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
            $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
        } else {
            throw new InvalidConfigException('Unable to determine the path info of the current request.');
        }

        return ltrim($pathInfo, '/');
    }

    /**
     * Checks whether rpc has container view.
     * 
     * @return boolean
     */
    public function hasContainerView()
    {
        return isset($_REQUEST['_thisView']) && !empty($_REQUEST['_thisView']);
    }

    /**
     * Get name of container view that call the remote procedure
     *
     * @return string name of view
     */
    public function getContainerViewName()
    {
        return $_REQUEST['_thisView'];
    }

    /**
     * Get view name
     *
     * @param type $urlArr
     * @return string
     * @todo Need to move to UrlManager and redirectToDefaultModuleView remove and move to controller.
     */
    function pathNameToViewName($urlArr, $viewIndex = 1)
    {
        $url_path = $urlArr[$viewIndex];
        //if (!$url_path) {
        //    return redirectToDefaultModuleView($urlArr[0]);
        //}
        if (preg_match_all("/([a-z]*)_?/si", $url_path, $match)) {
            $view_name = "";
            $match = $match[1];
            foreach ($match as $part) {
                if ($part) {
                    $part = ucwords($part); //ucwords(strtolower($part));
                    $view_name .= $part;
                }
            }
            if ($view_name) {
                $view_name.="View";
            }
        }
        return $view_name;
    }

    /**
     * Returns the port to use for secure requests.
     * Defaults to 443, or the port specified by the server if the current
     * request is secure.
     * The implementation of this method referenced yii\web\Request.
     * 
     * @return integer port number for secure requests.
     * @see setSecurePort()
     */
    public function getSecurePort()
    {
        if ($this->_securePort === null) {
            $this->_securePort = $this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 443;
        }
        return $this->_securePort;
    }

    /**
     * Sets the port to use for secure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * The implementation of this method referenced yii\web\Request.
     * 
     * @param integer $value port number.
     */
    public function setSecurePort($value)
    {
        if ($value != $this->_securePort) {
            $this->_securePort = (int) $value;
            $this->_hostInfo = null;
        }
    }

    private $_scriptUrl;

    /**
     * Returns the relative URL of the entry script.
     * The implementation of this method referenced \Zend_Controller_Request_Http in \Zend Framework.
     * @return string the relative URL of the entry script.
     * @throws InvalidConfigException if unable to determine the entry script URL
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
            } else {
                throw new InvalidConfigException('Unable to determine the entry script URL.');
            }
        }
        return $this->_scriptUrl;
    }

    private $_scriptFile;

    /**
     * Returns the entry script file path.
     * The default implementation will simply return `$_SERVER['SCRIPT_FILENAME']`.
     * @return string the entry script file path
     */
    public function getScriptFile()
    {
        return isset($this->_scriptFile) ? $this->_scriptFile : $_SERVER['SCRIPT_FILENAME'];
    }

    private $_baseUrl;

    /**
     * Returns the relative URL for the application.
     * This is similar to [[scriptUrl]] except that it does not include the script file name,
     * and the ending slashes are removed.
     * @return string the relative URL for the application
     * @see setScriptUrl()
     */
    public function getBaseUrl()
    {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }
        return $this->_baseUrl;
    }

    /**
     * Get Uri Parameters
     *
     * @param type $urlArr
     * @return array
     * @todo need to move to UrlManager
     */
    public function getUriParameters($urlArr, $viewIndex = 1)
    {
        $PARAM_MAPPING = array();
        //foreach($urlArr as $path)
        for ($i = ($viewIndex + 1); $i < count($urlArr); $i++) { // ignore the first 2 parts
            //only numberic like 20 parse it as fld:Id=20
            if (preg_match("/^([0-9]*)$/si", $urlArr[$i], $match)) {
                $PARAM_MAPPING["fld:Id"] = $match[1];
                //$_GET['Id'] = $match[1];
                //$_GET['fld:Id'] = $match[1];
                //DebugLine::show(__METHOD__.__LINE__);
                continue;
            }
            //Cid_20 parse it as fld:Cid=20
            // http://localhost/cubi/some/thing/Cid_20
            // echo $_GET['Cid'];  // 20
            // http://local.openbiz.me/index.php/collab/task_manage/fld_type_1/
            // array(1) { ["fld:fld_type"]=> string(1) "1" }
            elseif (preg_match("/^([a-z_]*?)_([^\/]*)$/si", $urlArr[$i], $match)) {
                $PARAM_MAPPING["fld:" . $match[1]] = $match[2];
                $_GET[$match[1]] = $match[2];
                //DebugLine::show(__METHOD__.__LINE__);
                continue;
            }
            // parse the string to query string
            parse_str($urlArr[$i], $arr);
            foreach ($arr as $k => $v) {
                $_GET[$k] = $v;
                $PARAM_MAPPING[$k] = $v;
                //DebugLine::show(__METHOD__.__LINE__);
            }
        }
        /*
        echo '<pre>';
        echo '<br />';
        echo var_dump($PARAM_MAPPING);
        echo '<br />';
        echo var_dump($_GET);
         * 
         */
        return $PARAM_MAPPING;
    }


    /**
     * Convert URI parameters to GET variables.
     * @param array $uriParams
     */
    public function convertUriParamsToGetVars($uriParams)
    {
        if (isset($uriParams)) {
            foreach ($uriParams as $param => $value) {
                //if (isset($_GET[$param]))
                $_GET[$param] = $_REQUEST[$param] = $value;
            }
        }
    }


        /**
     * Get current page URL
     * NOTE: NYU not yet used
     *
     * @return string current page URL
     */
    public static function currentPageURL()
    {
        if ($_REQUEST['__url']) {
            return $_REQUEST['__url'];
        } else {
            return $_SERVER['REQUEST_URI'];
        }
    }


}
