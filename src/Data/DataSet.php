<?php
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.data
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: DataSet.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Data;

use Openbizx\Data\DataRecord;

/**
 * DataSet class is the wrapper class of record array.
 * It is recommmended to be used in data update and deletion.
 *
 * @package openbiz.bin.data
 * @author Rocky Swen
 * @copyright Copyright (c) 2007-2009
 * @access public
 **/
class DataSet implements \Iterator, \ArrayAccess, \Countable 
{
    /**
     * Record in array format
     *
     * @var array
     */
    protected $varValue = array();

    /**
     * Reference of {@link BizDataObj}
     *
     * @var BizDataObj
     */
    protected $bizDataObj = null;

    /**
     * Initialize DataSet
     *
     * @param array $recArray record array.
     * @param BizDataObj $bizObj BizDataObj instance
     * @return void
     */
    public function __construct($bizObj)
    {
        $this->bizDataObj = $bizObj;
    }

    // Iterator methods BEGIN---------

    /**
     * Get item value of array
     *
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {       
        if (isset($this->varValue[$key])) {
            return new DataRecord($this->varValue[$key], $this->bizDataObj);
        } else {
            return NULL;
        }        
    }

    /**
     * Set item value of array
     *
     * @param mixed $key
     * @param mixed $val
     */
    public function set($key, $val)
    {
        $this->varValue[$key] = $val;

    }

    /**
     * Rewind, Send pointer to start of list
     *
     * @return void
     */
    public function rewind()
    { 
        reset($this->varValue);
    }

    /**
     * Return element at current pointer position
     *
     * @return mixed
     */
    public function current()
    { 
        return current($this->varValue);
    }


    /**
     * Return current key (i.e., pointer value)
     *
     * @return mixed
     */
    public function key()
    { 
        return key($this->varValue);
    }

    /**
     * Return element at current pointer and advance pointer
     *
     * @return mixed
     */
    public function next()
    { 
        return next($this->varValue);
    }

    /**
     * Confirm that there is an element at the current pointer position
     *
     * @return boolean
     */
    public function valid()
    { 
        return $this->current() !== false;
    }
    
    // ArrayAccess methods
    
    /**
     * Check is offset value (by key) exist?
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetExists($key)
    { 
        return isset($this->varValue[$key]);
    }

    /**
     * Get value of offset (by key)
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    { 
        return $this->get($key);
    }

    /**
     * Set value of offset by key
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    { 
        $this->set($key, $value);
    }

    /**
     * Unset element by key
     *
     * @param mixed $key key of element
     */
    public function offsetUnset($key)
    { 
        unset($this->varValue[$key]);
    }
    
    public function count() 
    {
        return count($this->varValue);
    }

    /**
     * Return record in array
     *
     * @return array record array
     */
    public function toArray()
    {
        return $this->varValue;
    }

    /**
     * Get reference object with given object name
     *
     * @param string $objName name of the object reference
     * @return obejct the instance of reference object
     */
    public function getRefObject($objName)
    {
        return $this->bizDataObj->getRefObject($objName);
    }

}
?>