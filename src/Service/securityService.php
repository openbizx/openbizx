<?php
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.service
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: securityService.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Service;

use Openbizx\Object\MetaIterator;

include_once (OPENBIZ_PATH."/messages/securityService.msg");

/**
 * securityService class is the plug-in service of security
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class securityService
{  
    public $mode = 'DISABLED';
    private $_securityFilters = array();
    private $_messageFile;
    protected $errorMessage = null;

    /**
     * Initialize securityService with xml array metadata
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
    }

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetadata(&$xmlArr)
    {
        $this->mode =   isset($xmlArr["PLUGINSERVICE"]["SECURITY"]["ATTRIBUTES"]["MODE"]) ? $xmlArr["PLUGINSERVICE"]["SECURITY"]["ATTRIBUTES"]["MODE"] : "DISABLED";
        if(strtoupper($this->mode) == 'ENABLED' )
        {
            $this->_securityFilters[] = new securityFilter($xmlArr["PLUGINSERVICE"]["SECURITY"]["URLFILTER"],		"securityFilter",	"URLFilter");
            $this->_securityFilters[] = new securityFilter($xmlArr["PLUGINSERVICE"]["SECURITY"]["DOMAINFILTER"],	"securityFilter",	"DomainFilter");
            $this->_securityFilters[] = new securityFilter($xmlArr["PLUGINSERVICE"]["SECURITY"]["IPFILTER"],		"securityFilter",	"IPFilter");
            $this->_securityFilters[] = new securityFilter($xmlArr["PLUGINSERVICE"]["SECURITY"]["AGENTFILTER"],		"securityFilter",	"AgentFilter");
            $this->_securityFilters[] = new securityFilter($xmlArr["PLUGINSERVICE"]["SECURITY"]["POSTFILTER"],		"securityFilter",	"PostFilter");
            $this->_securityFilters[] = new securityFilter($xmlArr["PLUGINSERVICE"]["SECURITY"]["GETFILTER"],		"securityFilter",	"GetFilter");
        }
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Proses filter
     *
     * @return boolean
     */
    public function processFilters()
    {
        foreach($this->_securityFilters as $filter)
        {
            $filter->processRules();
            if($filter->getErrorMessage())
            {
                $this->errorMessage = $filter->getErrorMessage();
                return false;
            }
        }
        return true;

    }
}

/**
 * securityFilter class is helper class for security filter
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class securityFilter extends MetaIterator
{
    protected $name = null;
    protected $mode = 'DISABLED';
    protected $rules = null;
    protected $errorMessage = null;


    /**
     * Initialize securityFilter with xml array metadata
     *
     * @param array $xmlArr
     * @param string $filterName
     * @param string $ruleName
     * @return void
     */
    function __construct(&$xmlArr, $filterName, $ruleName)
    {
        $this->readMetadata($xmlArr, $filterName, $ruleName);
    }

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @param string $filterName
     * @param string $ruleName
     * @return void
     */
    protected function readMetadata(&$xmlArr, $filterName, $ruleName)
    {
        $this->objectName = $ruleName;
        $this->mode =   isset($xmlArr["ATTRIBUTES"]["MODE"]) ? $xmlArr["ATTRIBUTES"]["MODE"] : "DISABLED";
        if(strtoupper($this->mode) == 'ENABLED' )
        {
            $this->rules 	= new MetaIterator($xmlArr["RULE"],	 $ruleName."Rule",	$this);
        }
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Proses rule
     *
     * @return boolean|void
     */
    public function processRules()
    {
        if(is_array($this->rules->varValue))
        {
            foreach($this->rules->varValue as $name=>$obj)
            {
                $obj->process();
                if($obj->getErrorMessage())
                {
                    $this->errorMessage = $obj->getErrorMessage();
                    return false;
                }
            }
        }
    }
}

/**
 * iSecurityRule interface is interface for securityRule_Abstract
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
interface iSecurityRule
{
    /**
     * Proses security rule
     */
    public function process();
}

/**
 * securityRule_Abstract class is helper class for security filter
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class securityRule_Abstract implements iSecurityRule
{
    public $objectName      =	null;
    public $action    =	null;
    public $match     =	null;
    public $status     =	null;
    public $effectiveTime =	null;
    public $errorMessage = null;

    /**
     * Initialize reportService with xml array metadata
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
    }

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetadata(&$xmlArr)
    {
        $this->objectName 	= $xmlArr["ATTRIBUTES"]["NAME"];
        $this->action	= $xmlArr["ATTRIBUTES"]["ACTION"];
        $this->status	= $xmlArr["ATTRIBUTES"]["STATUS"];
        $this->match 	= $xmlArr["ATTRIBUTES"]["MATCH"];
        $this->effectiveTime = $xmlArr["ATTRIBUTES"]["EFFECTIVETIME"];
    }

    /**
     * Proses security rule
     *
     * @return string
     */
    public function process()
    {
        return true;
    }

    /**
     * Get message error
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Check Effective Time
     *
     * @return boolean
     */
    public function checkEffectiveTime()
    {
        sscanf( $this->effectiveTime, "%2d%2d-%2d%2d",
                $start_hour, $start_min,
                $end_hour, $end_min
        );

        $startTime  = strtotime(date("Y-m-d ").$start_hour.":".$start_min) ? strtotime(date("Y-m-d ").$start_hour.":".$start_min) : strtotime(date("Y-m-d 00:00"));
        $endTime    = strtotime(date("Y-m-d ").$end_hour.":".$end_min) ? strtotime(date("Y-m-d ").$end_hour.":".$end_min) : strtotime(date("Y-m-d 23:59:59"));

        $nowTime    = time();

        if($startTime>0 && $endTime>0)
        {
            //auto convert start time and end time
            if($endTime < $startTime)
            {
                $tmpTime = $startTime;
                $startTime = $endTime;
                $endTime = $tmpTime;
            }

            if($startTime < $nowTime && $nowTime < $endTime )
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
}

/**
 * URLFilterRule class
 *
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class URLFilterRule extends securityRule_Abstract
{

    /**
     * Proses security rule
     * return true go to check next rule
     * return false report an error and stop checking
     *
     * @return boolean
     */
    public function process()
    {
    	if(strtoupper($this->status)=='ENABLE')
    	{
	        parent::process();
	        if(!$this->checkEffectiveTime())
	        {
	            return true;
	        }
	        else
	        {
	            $url = $_SERVER['REQUEST_URI'];
	            if(preg_match("/".$this->match."/si",$url))
	            {
	                if(strtoupper($this->action)=='OPENBIZ_DENY')
	                {
	                    $this->errorMessage=MessageHelper::getMessage('SECURITYSVC_URL_DENIED');
	                    return false;
	                }elseif(strtoupper($this->action)=='OPENBIZ_ALLOW')
	                {
	                    return true;
	                }
	                return false;
	            }
	        }
    	}
    }
}

/**
 * DomainFilterRule class
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class DomainFilterRule extends securityRule_Abstract
{

    /**
     * Proses security rule
     * return true go to check next rule
     * return false report an error and stop checking
     *
     * @return boolean
     */
    public function process()
    {
    	if(strtoupper($this->status)=='ENABLE')
    	{
	        parent::process();
	        if(!$this->checkEffectiveTime())
	        {
	            return true;
	        }
	        else
	        {
	            $url = $_SERVER['HTTP_HOST'];
	            if(preg_match("/".$this->match."/si",$url))
	            {
	                if(strtoupper($this->action)=='OPENBIZ_DENY')
	                {
	                    $this->errorMessage=MessageHelper::getMessage('SECURITYSVC_DOMAIN_DENIED');
	                    return false;
	                }
	                elseif(strtoupper($this->action)=='OPENBIZ_ALLOW')
	                {
	                    return true;
	                }
	                return false;
	            }
	        }
    	}
    }
}

/**
 * AgentFilterRule class
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class AgentFilterRule extends securityRule_Abstract
{
    /**
     * Proses security rule
     * return true go to check next rule
     * return false report an error and stop checking
     *
     * @return boolean
     */
    public function process()
    {
    	if(strtoupper($this->status)=='ENABLE')
    	{
	        parent::process();
	        if(!$this->checkEffectiveTime())
	        {
	            return true;
	        }
	        else
	        {
	            $url = $_SERVER['HTTP_USER_AGENT'];
	            if(preg_match("/".$this->match."/si",$url))
	            {
	                if(strtoupper($this->action)=='OPENBIZ_DENY')
	                {
	                    $this->errorMessage=MessageHelper::getMessage('SECURITYSVC_AGENT_DENIED');
	                    return false;
	                }
	                elseif(strtoupper($this->action)=='OPENBIZ_ALLOW')
	                {
	                    return true;
	                }
	                return false;
	            }
	        }
    	}
    }
}

/**
 * IPFilterRule class
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class IPFilterRule extends securityRule_Abstract
{
    /**
     * Proses security rule
     * return true go to check next rule
     * return false report an error and stop checking
     *
     * @return boolean
     */
    public function process()
    {
    	if(strtoupper($this->status)=='ENABLE')
    	{
    		parent::process();
	        if(!$this->checkEffectiveTime())
	        {
	            return true;
	        }
	        else
	        {
	            $url = $_SERVER['REMOTE_ADDR'];
	            if(preg_match("/".$this->match."/si",$url))
	            {
	                if(strtoupper($this->action)=='OPENBIZ_DENY')
	                {
	                    $this->errorMessage = MessageHelper::getMessage('SECURITYSVC_IPADDR_DENIED');
	                    return false;
	                }
	                elseif(strtoupper($this->action)=='OPENBIZ_ALLOW')
	                {
	                    return true;
	                }
	                return false;
	            }
	        }
    	}
    }
}

/**
 * PostFilterRule class
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class PostFilterRule extends securityRule_Abstract
{

    /**
     * Proses security rule
     * return true go to check next rule
     * return false report an error and stop checking
     *
     * @return boolean
     */
    public function process()
    {
    	if(strtoupper($this->status)=='ENABLE')
    	{
	        parent::process();
	        if(!$this->checkEffectiveTime())
	        {
	            return true;
	        }
	        else
	        {
	            $post_str = serialize($_POST);
	            if($this->match!="")
	            {
	                if(preg_match("/".$this->match."/si",$post_str))
	                {
	                    if(strtoupper($this->action)=='OPENBIZ_DENY')
	                    {
	                        $this->errorMessage=MessageHelper::getMessage('SECURITYSVC_POST_DENIED');
	                        return false;
	                    }
	                    elseif(strtoupper($this->action)=='OPENBIZ_ALLOW')
	                    {
	                        return true;
	                    }
	                    return false;
	                }
	            }
	            else
	            {
	                return false;
	            }
	        }
    	}
    }
}

/**
 * GetFilterRule class
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class GetFilterRule extends securityRule_Abstract
{

    /**
     * Proses security rule
     * return true go to check next rule
     * return false report an error and stop checking
     *
     * @return boolean
     */
    public function process()
    {
    	if(strtoupper($this->status)=='ENABLE')
    	{
	        parent::process();
	        if(!$this->checkEffectiveTime())
	        {
	            return true;
	        }
	        else
	        {
	            $get_str = serialize($_GET);
	            if(preg_match("/".$this->match."/si",$get_str))
	            {
	                if(strtoupper($this->action)=='OPENBIZ_DENY')
	                {
	                    $this->errorMessage=MessageHelper::getMessage('SECURITYSVC_GET_DENIED');
	                    return false;
	                }
	                elseif(strtoupper($this->action)=='OPENBIZ_ALLOW')
	                {
	                    return true;
	                }
	                return false;
	            }
	        }
    	}
    }
}
?>