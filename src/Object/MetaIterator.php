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

namespace Openbizx\Object;

use Openbizx\Openbizx;
use Openbizx\ClassLoader;

/**
 * MetaIterator class
 * MetaIterator is the base class of all derived metadata-driven classes who support iteration
 *
 * @package   openbiz.bin
 * @author    Rocky Swen
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 * @todo please check MetaObjExport.
 */
class MetaIterator implements \Iterator
{

    /**
     * Parent object
     * @var object
     */
    protected $parentObj = null;

    /**
     * Store value
     * @var array
     */
    protected $varValue = array();

    /**
     * Contructor of class
     *
     * @param array $xmlArr
     * @param string $childClassName
     * @param object $parentObj
     * @return void
     */
    public function __construct(&$xmlArr, $childClassName, $parentObj = null)
    {
        $this->parentObj = $parentObj;

        if (!$xmlArr) {
            return;
        }

        if (isset($xmlArr["ATTRIBUTES"])) {
            $className = isset($xmlArr["ATTRIBUTES"]['CLASS']) ? $xmlArr["ATTRIBUTES"]['CLASS'] : $childClassName;
            if ((bool) strpos($className, ".")) {
                $a_package_name = explode(".", $className);
                $className = array_pop($a_package_name);
                $clsLoaded = ClassLoader::loadMetadataClass($className, implode(".", $a_package_name));
            }
            //if (!$clsLoaded) trigger_error("Cannot find the load class $className", E_USER_ERROR);
            
            $className = Openbizx::objectFactory()->getClassNameFromAlias($className);
            
            $obj = new $className($xmlArr, $parentObj);
            $this->varValue[$obj->objectName] = $obj;
        } else {
            foreach ($xmlArr as $child) {
                $className = isset($child["ATTRIBUTES"]['CLASS']) ? $child["ATTRIBUTES"]['CLASS'] : $childClassName;

                /**
                 * If a '.' is found within className we need to require such class
                 * and then get the className after the last dot
                 * ex. shared.dataobjs.FieldName, in this case FieldName is the class, shared/dataobjs the path
                 *
                 * The best solution to this is enable object factory to specify its resulting object constructor parameters
                 */
                if ($className) { //bug fixed by jixian for resolve load an empty classname
                    if ((bool) strpos($className, ".")) {
                        $a_package_name = explode(".", $className);
                        $className = array_pop($a_package_name);
                        $clsLoaded = ClassLoader::loadMetadataClass($className, implode(".", $a_package_name));
                    } elseif ($parentObj->package) {
                        $clsLoaded = ClassLoader::loadMetadataClass($className, $parentObj->package);
                    }

                    $className = Openbizx::objectFactory()->getClassNameFromAlias($className);
                    
                    $obj = new $className($child, $parentObj);
                    $this->varValue[$obj->objectName] = $obj;
                }
            }
        }
    }

    /**
     * Merge to another MetaIterator object
     *
     * @param MetaIterator $anotherMIObj another MetaIterator object
     * @return void
     */
    public function merge(&$anotherMIObj)
    {
        $old_varValue = $this->varValue;
        $this->varValue = array();
        foreach ($anotherMIObj as $key => $value) {
            if (!$old_varValue[$key]) {
                $this->varValue[$key] = $value;
            } else {
                $this->varValue[$key] = $old_varValue[$key];
            }
        }
        foreach ($old_varValue as $key => $value) {
            if (!isset($this->varValue[$key])) {
                $this->varValue[$key] = $value;
            }
        }
    }

    /**
     * Get value
     *
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        return isset($this->varValue[$key]) ? $this->varValue[$key] : null;
    }

    /**
     * Set value
     *
     * @param mixed $key
     * @param mixed $val
     */
    public function set($key, $val)
    {
        $this->varValue[$key] = $val;
    }

    /**
     * Clear value
     *
     * @param mixed $key
     * @param mixed $val
     */
    public function clear($key)
    {
        unset($this->varValue[$key]);
    }

    public function count()
    {
        return count($this->varValue);
    }

    /**
     * Rewind
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->varValue);
    }

    /**
     * Current item
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->varValue);
    }

    /**
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->varValue);
    }

    /**
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->varValue);
    }

    /**
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->current() !== false;
    }

}
