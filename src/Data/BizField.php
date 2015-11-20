<?PHP

/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.data
 * @copyright Copyright &copy; 2005-2009, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: BizField.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Data;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\Object\MetaObject;
/**
 * Class BizField is the class of a logic field which mapps to a table column
 *
 * @package openbiz.bin.data
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 * */
class BizField extends MetaObject
{

    /**
     * Name of {@link BizDataObj}
     *
     * @var string
     */
    public $bizDataObjName;
    public $index;
    public $join = null;
    public $column = null;
    public $aliasName = null;
    public $fieldIndex;

    /**
     * Type of field in string
     *
     * @var string
     */
    public $type = null;

    /**
     * Format of field
     *
     * @var string
     */
    public $format = null;

    /**
     * Length of field
     *
     * @var number
     */
    public $length = null;
    public $valueExpression = null; // support expression

    /**
     * Is field required?
     *
     * @var mixed
     */
    public $required = null;       // support expression
    public $validator = null;      // support expression
    public $sqlExpression = null;  // support expression
    public $encrypted = "N";
    public $clearText = null;

    /**
     * Default value of field
     *
     * @var mixed
     */
    public $defaultValue = null;
    public $valueOnCreate = null;
    public $valueOnUpdate = null;

    /**
     * Is on Audit?
     *
     * @var boolean
     */
    public $onAudit = false;

    /**
     * The real value of the field, not from metadata
     *
     * @var mixed
     */
    public $value = null;
    
    public $oldValue = null; // the old value of the field
    public $ignoreInQuery = false;
    protected $_prevValue, $_getValueCache;

    /**
     * Initialize BizField with xml array
     *
     * @param array $xmlArr xml array
     * @param BizDataObj $bizObj BizDataObj instance
     * @return void
     */
    function __construct(&$xmlArr, $bizObj)
    {
        $this->objectName = isset($xmlArr["ATTRIBUTES"]["NAME"]) ? $xmlArr["ATTRIBUTES"]["NAME"] : null;
        $this->bizDataObjName = $bizObj->objectName;
        $this->package = $bizObj->package;
        $this->join = isset($xmlArr["ATTRIBUTES"]["JOIN"]) ? $xmlArr["ATTRIBUTES"]["JOIN"] : null;
        $this->column = isset($xmlArr["ATTRIBUTES"]["COLUMN"]) ? $xmlArr["ATTRIBUTES"]["COLUMN"] : null;
        $this->aliasName = isset($xmlArr["ATTRIBUTES"]["ALIAS"]) ? $xmlArr["ATTRIBUTES"]["ALIAS"] : null;
        $this->valueExpression = isset($xmlArr["ATTRIBUTES"]["VALUE"]) ? $xmlArr["ATTRIBUTES"]["VALUE"] : null;
        $this->defaultValue = isset($xmlArr["ATTRIBUTES"]["DEFAULTVALUE"]) ? $xmlArr["ATTRIBUTES"]["DEFAULTVALUE"] : null;
        $this->type = isset($xmlArr["ATTRIBUTES"]["TYPE"]) ? $xmlArr["ATTRIBUTES"]["TYPE"] : null;
        $this->format = isset($xmlArr["ATTRIBUTES"]["FORMAT"]) ? $xmlArr["ATTRIBUTES"]["FORMAT"] : null;
        $this->length = isset($xmlArr["ATTRIBUTES"]["LENGTH"]) ? $xmlArr["ATTRIBUTES"]["LENGTH"] : null;
        $this->required = isset($xmlArr["ATTRIBUTES"]["REQUIRED"]) ? $xmlArr["ATTRIBUTES"]["REQUIRED"] : null;
        $this->encrypted = isset($xmlArr["ATTRIBUTES"]["ENCRYPTED"]) ? strtoupper($xmlArr["ATTRIBUTES"]["ENCRYPTED"]) : "N";
//        if($this->encrypted=='Y'){
//        	$this->clearText="N";
//        }else{
//        	$this->clearText="Y";
//        }
        $this->validator = isset($xmlArr["ATTRIBUTES"]["VALIDATOR"]) ? $xmlArr["ATTRIBUTES"]["VALIDATOR"] : null;
        $this->sqlExpression = isset($xmlArr["ATTRIBUTES"]["SQLEXPR"]) ? $xmlArr["ATTRIBUTES"]["SQLEXPR"] : null;
        $this->valueOnCreate = isset($xmlArr["ATTRIBUTES"]["VALUEONCREATE"]) ? $xmlArr["ATTRIBUTES"]["VALUEONCREATE"] : null;
        $this->valueOnUpdate = isset($xmlArr["ATTRIBUTES"]["VALUEONUPDATE"]) ? $xmlArr["ATTRIBUTES"]["VALUEONUPDATE"] : null;
        if (isset($xmlArr["ATTRIBUTES"]["ONAUDIT"]) && $xmlArr["ATTRIBUTES"]["ONAUDIT"] == 'Y') {
            $this->onAudit = true;
        }

        $this->bizDataObjName = $this->prefixPackage($this->bizDataObjName);

        if (!$this->format) {
            $this->useDefaultFormat();
        }
    }

    /**
     * Use default format if no format is given
     *
     * @return void
     */
    protected function useDefaultFormat()
    {
        if ($this->type == "Date") {
            $this->format = '%Y-%m-%d';
        } elseif ($this->type == "Datetime") {
            $this->format = '%Y-%m-%d %H:%M:%S';
        }
    }

    /**
     * Get property value
     * 
     * @param string $propertyName property name
     * @return mixed property value
     */
    public function getProperty($propertyName)
    {
        $ret = parent::getProperty($propertyName);
        if ($ret) {
            return $ret;
        }
        //if ($propertyName == "Value") return $this->getValue();
        if ($propertyName == "Value") {
            return $this->lookupValue();
        }
        return $this->$propertyName;
    }

    /**
     * Change the {@link BizDataObj} name. This function is used in case of the current {@link BizDataObj}
     * inheriting from another {@link BizDataObj}, BizField's {@link BizDataObj} name should be changed to
     * current {@link BizDataObj} name, not the parent object name.
     *
     * @param string $bizObjName the name of {@link BizDataObj} object
     * @return void
     */
    public function adjustBizObjName($bizObjName)
    {
        if ($this->bizDataObjName != $bizObjName) {
            $this->bizDataObjName = $bizObjName;
        }
    }

    /**
     * Get string used in sql - with single quote, or without single quote in case of number
     *
     * @param mixed $input the value to add quote. If null, use the current field value
     * @return string string used in sql
     */
    public function getSqlValue($input = null)
    {
        $value = ($input !== null) ? $input : $this->value;
        if ($value === null) {
            return "";
        }
        /*
          if ($this->type != 'Number')
          {
          if (get_magic_quotes_gpc() == 0) {
          $val = addcslashes($value, "\000\n\r\\'\"\032");
          }
          return "'$value'";
          }
         */

        return $value;
    }

    /**
     * Check if the field is a LOB type column
     *
     * @return boolean true if the field points a LOB type column
     */
    public function isLobField()
    {
        return ($this->type == 'Blob' || $this->type == 'Clob');
    }

    /**
     * Get insert lob value when execute insert SQL. For a lob column, insert SQL first inserts
     * an empty entry in the lob column. Then use update to actually add the lob data.
     *
     * @param string $dbType database type
     * @return string the insert string for the lob column
     */
    public function getInsertLobValue($dbType)
    {
        if ($dbType == 'oracle' || $dbType == 'oci8') {
            if ($this->type != 'Blob') {
                return 'empty_blob()';
            }
            if ($this->type != 'Clob') {
                return 'empty_clob()';
            }
        }
        return 'null';
    }

    /**
     * Lookup the value of the field. Typically used in expression @:Field[name].Value
     *
     * @param boolean $formatted true if want to get the formatted value
     * @return mixed string or number depending on the field type
     */
    public function lookupValue()
    {
        $this->getDataObj()->getActiveRecord();
        return $this->getValue();
    }

    /**
     * Get the value of the field.
     *
     * @param boolean $formatted true if want to get the formatted value
     * @return mixed string or number depending on the field type
     */
    public function getValue($formatted = true)
    {
        // need to ensure that value are retrieved from source/cache
        //if ($this->getDataObj()->CheckDataRetrieved() == false)    	
        //$this->getDataObj()->getActiveRecord();

        if ($this->_prevValue == $this->value) {
            return $this->_getValueCache;
        }
        //$value = stripcslashes($this->value);
        $value = $this->value;
        if ($this->valueExpression && trim($this->column) == "") {
            $value = Expression::evaluateExpression($this->valueExpression, $this->getDataObj());
        }
        if ($this->format && $formatted) {
            $value = Openbizx::$app->getTypeManager()->valueToFormattedString($this->type, $this->format, $value);
        }
        $this->_prevValue = $this->value;
        $this->_getValueCache = $value;
        return $value;
    }

    /**
     * Set the value of the field.
     *
     * @param mixed $value
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Save the old value to an internal variable
     *
     * @param mixed $value
     * @return void
     */
    public function saveOldValue($value = null)
    {
        if ($value) {
            $this->oldValue = $value;
        } else {
            $this->oldValue = $this->value;
        }
    }

    /**
     * Get default value of the field
     *
     * @return string the default value of the field
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return Expression::evaluateExpression($this->defaultValue, $this->getDataObj());
        }
        return "";
    }

    /**
     * Get the value when a new record is created
     *
     * @return mixed the value of the field
     */
    public function getValueOnCreate()
    {
        if ($this->valueOnCreate !== null) {
            return $this->getSqlValue(Expression::evaluateExpression($this->valueOnCreate, $this->getDataObj()));
        }
        return "";
    }

    /**
     * Get the value when a record is updated
     *
     * @return mixed the value of the field
     */
    public function getValueOnUpdate()
    {
        if ($this->valueOnUpdate !== null)
            return $this->getSqlValue(Expression::evaluateExpression($this->valueOnUpdate, $this->getDataObj()));
        return "";
    }

    /**
     * Get the {@link BizDataObj} instance
     *
     * @return BizDataObj {@link BizDataObj} instance
     */
    protected function getDataObj()
    {
        return Openbizx::getObject($this->bizDataObjName);
    }

    /**
     * Check if the field is a required field
     *
     * @return boolean true if the field is a required field
     */
    public function checkRequired()
    {
        if (!$this->required || $this->required == "") {
            return false;
        } elseif ($this->required == "Y") {
            $required = true;
        } elseif ($required != "N") {
            $required = false;
        } else {
            $required = Expression::evaluateExpression($this->required, $this->getDataObj());
        }
        return $required;
    }

    /**
     * Check value type
     *
     * @param mixed $value
     * @return mixed|boolean
     */
    public function checkValueType($value = null)
    {
        if (!$value) {
            $value = $this->value;
        }
        $validator = Openbizx::getService(VALIDATE_SERVICE);
        switch ($this->type) {
            case "Number":
                $result = is_numeric($value);
                break;

            case "Text":
                $result = is_string($value);
                break;

            case "Date":
                $result = $validator->date($value);
                break;
            /*
              case "Datetime":    // zend doesn't support date time
              $result = $validator->date($value);
              break;

              case "Currency":
              $result = $validator->date($value);
              break;
             */
            case "Phone":
                $result = $validator->phone($value);
                break;

            default:
                $result = true;
                break;
        }

        return $result;
    }

    /**
     * Check if the field has valid value
     *
     * @return boolean true if validation is good
     */
    public function validate()
    {
        $ret = true;
        if ($this->validator)
            $ret = Expression::evaluateExpression($this->validator, $this->getDataObj());
        return $ret;
    }

}

?>