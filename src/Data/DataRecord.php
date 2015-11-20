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
 * @version   $Id: DataRecord.php 4086 2011-05-03 06:00:35Z rockys $
 */

namespace Openbizx\Data;

/**
 * DataRecord class  is the wrapper class of record array.
 * It is recommmended to be used in data update and deletion.
 *
 * @package openbiz.bin.data
 * @author Rocky Swen
 * @copyright Copyright (c) 2007-2009
 * @access public
 **/
class DataRecord implements \Iterator, \ArrayAccess
{
    /**
     * Record in array format
     *
     * @var array
     */
    protected $varValue = array();

    /**
     * Old record in array format
     *
     * @var array
     */
    protected $oldVarValue = array();

    /**
     * Reference of {@link BizDataObj}
     *
     * @var BizDataObj
     */
    protected $bizDataObj = null;

    /**
     * Initialize DataRecord with record array.
     * Creat a new record - new {@link DataRecord(null, $bizObj)}
     * Get a current record - new {@link DataRecord($recArr, $bizObj)}
     *
     * @param array $recArray record array.
     * @param BizDataObj $bizObj BizDataObj instance
     * @return void
     * @todo fix for non array and non DataRecord condition
     */
    public function __construct($recArray, $bizObj)
    {
        //echo __METHOD__ . '-' . __LINE__ . 'B EGIN ==========<br />';
        if ($recArray != null) {
            if (is_array($recArray)) {
                $this->varValue = $recArray;
                $this->oldVarValue = $recArray;
            } else if (is_a($recArray, "Openbizx\Data\DataRecord")) {
                $this->varValue = $recArray->toArray();
                $this->oldVarValue = $this->varValue;
            } else {
                // please fix here
            }
        } else {
            $this->varValue = $bizObj->newRecord();
        }
        $this->bizDataObj = $bizObj;
    }



    /**
     * Get item value of array
     *
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        return isset($this->varValue[$key]) ? $this->varValue[$key] : null;
    }
    
    public function getOldValue($key)
    {
    	return isset($this->oldVarValue[$key]) ? $this->oldVarValue[$key] : null;
    }

	public function getDataObj()
    {
    	return $this->bizDataObj;
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

    // Iterator methods BEGIN---------
    
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

    // Iterator method END

    // ArrayAccess methods Begin
    //========================================
    
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

    /**
     * Get field value with property format
     * <pre>
     *   $value = $obj->get($fieldName); => $value = $obj->fieldName;
     * </pre>
     *
     * @param string $fieldName name of a field
     * @return mixed value of the field
     */
    public function __get($fieldName)
    {
        return $this->get($fieldName);
    }

    /**
     * Set field value with property format
     * <pre>
     *   $obj->set($fieldName, $value); => $obj->fieldName = $value;
     * </pre>
     *
     * @param string $fieldName name of a field
     * @param mixed value of the field
     * @return avoid
     */
    public function __set($fieldName, $value)
    {
        $this->set($fieldName, $value);
    }

    /**
     * Save record. This function calls {@link BizDataObj::updateRecord} method internally
     *
     * @return boolean true for success
     */
    public function save()
    {
        if (count($this->oldVarValue) > 0) {
            $ok = $this->bizDataObj->updateRecord($this->varValue, $this->oldVarValue);
        } else {
            $ok = $this->bizDataObj->insertRecord($this->varValue);
        }
        
        // repopulate current record with bizdataobj activerecord
        if ($ok) {
            $this->varValue = $this->bizDataObj->getActiveRecord();
            $this->oldVarValue = $this->varValue;
        }
        return $ok;
    }

    /**
     * Delete record. This function calls {@link BizDataObj::deleteRecord} method internally
     *
     * @return boolean true for success
     */
    public function delete()
    {
        return $this->bizDataObj->deleteRecord($this->varValue);
    }

    /**
     * Get error message
     *
     * @return string error message
     */
    public function getError()
    {
        return $this->bizDataObj->getErrorMessage();
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