<?PHP

/**
 * Openbizx Framework
 * 
 * Based on Openbiz Framework by Rocky Swen (Openbiz LLC)
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
 * @version   $Id: ObjectFactory.php 5155 2013-01-17 07:07:34Z agus.suhartono@gmail.com $
 */

namespace Openbizx\Object;

use Openbizx\Openbizx;
use Openbizx\ClassLoader;
use Openbizx\Object\ObjectFactoryHelper;

/**
 * ObjectFactory is factory class to create metadata based objects
 * (bizview, bizform, bizdataobj...)
 *
 * @package   openbiz.bin
 * @author    Rocky Swen <rocky@phpopenbiz.org>
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 */
class ObjectFactory
{

    /**
     * Internal array for cache Openbizx\Object\MetaObject
     * @var array
     */
    private $_objectsMap = array();
    
    
    /**
     * @var array service definitions
     */
    private $_definitions = [];

    public function setDefinition( $name, $definition ) 
    {
        
    }
     
    public function getDefinition()
    {
        
    }

    public function __construct()
    {

    }

    public function __destruct()
    {

    }

    /**
     * Get a metadata based object instance.
     * It returns the instance the internal object map or create a new one and save it in the map.
     *
     * @param string $objectName name of object that want to get
     * @return object
     */
    public function getObject($objectName, $new = 0)
    {
        if (isset($this->_objectsMap[$objectName]) && $new == 0) {
            return $this->_objectsMap[$objectName];
        }

        $obj = $this->constructObject($objectName);
        if (!$obj) {
            return null;
        } 
        // save object to cache
        $this->_objectsMap[$objectName] = $obj;
        if ($new != 1) {
            if (method_exists($obj, "loadStatefullVars")) {
                $obj->loadStatefullVars(Openbizx::$app->getSessionContext());
            }
        }

        return $obj;
    }

    /**
     * Create a new metadata based object instance
     *
     * @param string $objName name of object will be create
     * @param array $xmlArr propery array of object
     * @return object
     */
    public function createObject($objName, &$xmlArr = null)
    {
        $obj = $this->constructObject($objName, $xmlArr);
        return $obj;
    }

    public function setObject($objName, $obj)
    {
        $this->_objectsMap[$objName] = $obj;
    }

    /**
     * Get all object from the internal object array (object cache)
     *
     * @return array array of object
     */
    public function getAllObjects()
    {
        return $this->_objectsMap;
    }

    /**
     * Construct an instance of an object
     *
     * @param string $objName object name
     * @param array $xmlArr xml array
     * @return object the instance of the object
     */
    protected function constructObject($objName, &$xmlArr = null)
    {               
        
        if (!$xmlArr) {
            $xmlFile = ObjectFactoryHelper::getXmlFileWithPath($objName);            
            if ($xmlFile) { 
                $xmlArr = ObjectFactoryHelper::getXmlArray($xmlFile);                
            } else {
                
                //$this->_createObjectFromClass($objName);
                /* 
                 * if not have metadata, create object from class directly
                 * metadata is on the class it self.                 * 
                 */
                
                $class = str_replace('.', '\\', $objName);
                
                /*
                $dotPos = strrpos($objName, ".");
                if ($dotPos > 0) { // if has package/namespace
                    $objectPackage =  substr($objName, 0, $dotPos) ;
                    $class = substr($objName, $dotPos + 1);
                } else {
                    $objectPackage = null;
                    $class = $objName;
                }
                * 
                */
                
            }
        }        
        
        if ($xmlArr) {           
            $keys = array_keys($xmlArr);
            $root = $keys[0];

            // add by mr_a_ton , atrubut name must match with object name
            $dotPos = strrpos($objName, ".");
            $shortObjectName = $dotPos > 0 ? substr($objName, $dotPos + 1) : $objName;
            if ($xmlArr[$root]["ATTRIBUTES"]["NAME"] == "") {
                $xmlArr[$root]["ATTRIBUTES"]["NAME"] = $shortObjectName;
            } else {
                if ($shortObjectName != $xmlArr[$root]["ATTRIBUTES"]["NAME"]) {
                    trigger_error("Metadata file parsing error for object $objName. Name attribut [" . $xmlArr[$root]["ATTRIBUTES"]["NAME"] . "] not same with object name. Please double check your metadata xml file again.", E_USER_ERROR);
                }
            }
            
            $class = $xmlArr[$root]["ATTRIBUTES"]["CLASS"];
            
            //echo __METHOD__.'-'.__LINE__ . ' class : ' . $class . ' <br />';
            
            // if class has package name as prefix, change the package to the prefix
            $dotPos = strrpos($class, ".");
            if ($dotPos > 0) {
                $classPrefix = substr($class, 0, $dotPos);
                $classPackage = $classPrefix;
                $shortClass = substr($class, $dotPos + 1);
            } else {
                $classPrefix = null;
                $classPackage = null;                
                $shortClass = $class;
            }            
           
            //echo 'shortclass : ' . $shortClass .'<br />' ;
            // set object package
            $dotPos = strrpos($objName, ".");
            $objectPackage = $dotPos > 0 ? substr($objName, 0, $dotPos) : null;
            if (strpos($objectPackage, '@') === 0) {
                $objectPackage = substr($objectPackage, 1);
            }
            if (!$classPackage) {
                $classPackage = $objectPackage;
            }
            $xmlArr[$root]["ATTRIBUTES"]["PACKAGE"] = $objectPackage;
        }


        //$package = $xmlArr[$root]["ATTRIBUTES"]["PACKAGE"];
        //$class = $xmlArr[$root]["ATTRIBUTES"]["CLASS"];

        $class = $shortClass;
        
        // echo $class . '<br />';
        
        $class = $this->getClassNameFromAlias($class);
        
        if (strrpos($class, '\\' ) !== false) {
            $obj_ref = new $class($xmlArr);
            return $obj_ref;
        }        

        //echo $class . '<br />';
        //echo $classPackage . '<br />';
        
        if (!class_exists($class, false)) {
            //echo 'class not exist<br />';
            $classFile = ClassLoader::getLibFileWithPath($class, $classPackage);
            //echo 'classFile: '.$classFile .'<br />';
            if (!$classFile) {
                if ($objectPackage) {
                    trigger_error("Cannot find the class with name as $objectPackage.$class", E_USER_ERROR);
                } else {
                    trigger_error("Cannot find the class with name as $class of $objName", E_USER_ERROR);
                }
                exit();
            }
            include_once($classFile);
        }

        //echo 'class_exists($class, false): '. (class_exists($class, false)? 'true':'false') . '<br />';
        if (class_exists($class, false)) {
            //if ($objName == "collab.calendar.form.EventListForm") { print_r($xmlArr); exit; }
            $obj_ref = new $class($xmlArr);
            if ($obj_ref) {
                return $obj_ref;
            }
        } else {
            trigger_error("Cannot find the class with name as $class in $classFile", E_USER_ERROR);
        }
        return null;
    }
    
    
    private function _createObjectFromMetadata() {
        
    }
    
    private function _createObjectFromClass($param)
    {
        
    }
    
    
    private $_prefix=[];
    private $_path=[];
    private $_ext=[];
    
    /**
     * Register Prefix, location and extension of MetaObject
     * 
     * @param string $prefix prefix of metaobject, like "Contact" for "Contact.form.ContactForm"
     * @param string $path location of metaobject with $prefix
     * @param array|string $ext array of string, collection of extension that support by prefix
     * @return null
     * @since OpenbizX CubiX
     */
    public function register($prefix, $path, $ext=null) {
        $this->_prefix = $prefix;
        $this->_path = $path;        
        if ($ext == null) {
            $this->_ext[] = array('xml');
        } else if (is_array($ext)) {
            $this->_ext[] = $ext;
        } else if (is_string($ext)) {
            $ext[] = array($ext);
        }
    }
    
    private $_classAlias = [];
    
    public function setClassAlias($classAlias, $className)
    {
        $this->_classAlias[$classAlias] = $className;
    }
    
    
    public function getClassNameFromAlias($className) {
        if ( isset($this->_classAlias[$className])) {
            return $this->_classAlias[$className];
        } else {
            return $className;
        }
    }

    public function setClassAliases($classAliases)
    {
        $this->_classAlias = $classAliases ;
    }    
    
}
