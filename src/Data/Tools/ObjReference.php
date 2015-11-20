<?php
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.data.private
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: ObjReference.php 3858 2011-04-23 01:14:49Z jixian2003 $
 */

namespace Openbizx\Data\Tools;

use Openbizx\Object\MetaObject;

/**
 * ObjReference class defines the object reference of a BizDataObj
 *
 * @package openbiz.bin.data.private
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 */
class ObjReference extends MetaObject
{
    public $relationship;
    /**
     * Table name
     *
     * @var string
     */
    public $table;
    /**
     * Column name
     *
     * @var string
     */
    public $column;
    public $column2;
    /**
     * Field name for reference
     *
     * @var string
     */
    public $fieldRef;
    public $fieldRef2;
    public $xTable;
    public $xColumn1;
    public $xColumn2;
    public $xKeyColumn;   // may not be used any more due to XDataObj
    public $xDataObj;
    /**
     * Is cascade action
     *
     * @var boolean
     */
    public $cascadeDelete=false;
    public $onDelete;
    public $onUpdate;

    public $condColumn;
    public $condField;
    public $condValue;
    public $condition;
    //public $association;

    /**
     * Initialize ObjReference with xml array
     *
     * @param array $xmlArr
     * @param BizDataObj $bizObj
     * @return void
     */
    function __construct(&$xmlArr, $bizObj)
    {
        $this->objectName = isset($xmlArr["ATTRIBUTES"]["NAME"]) ? $xmlArr["ATTRIBUTES"]["NAME"] : null;
        $this->package = $bizObj->package;
        $this->objectDescription= isset($xmlArr["ATTRIBUTES"]["DESCRIPTION"]) ? $xmlArr["ATTRIBUTES"]["DESCRIPTION"] : null;
        $this->relationship = isset($xmlArr["ATTRIBUTES"]["RELATIONSHIP"]) ? $xmlArr["ATTRIBUTES"]["RELATIONSHIP"] : null;
        $this->table = isset($xmlArr["ATTRIBUTES"]["TABLE"]) ? $xmlArr["ATTRIBUTES"]["TABLE"] : null;
        $this->column = isset($xmlArr["ATTRIBUTES"]["COLUMN"]) ? $xmlArr["ATTRIBUTES"]["COLUMN"] : null;
        $this->fieldRef = isset($xmlArr["ATTRIBUTES"]["FIELDREF"]) ? $xmlArr["ATTRIBUTES"]["FIELDREF"] : null;
        $this->column2 = isset($xmlArr["ATTRIBUTES"]["COLUMN2"]) ? $xmlArr["ATTRIBUTES"]["COLUMN2"] : null;
        $this->fieldRef2 = isset($xmlArr["ATTRIBUTES"]["FIELDREF2"]) ? $xmlArr["ATTRIBUTES"]["FIELDREF2"] : null;
        $this->cascadeDelete = (isset($xmlArr["ATTRIBUTES"]["CASCADEDELETE"]) && $xmlArr["ATTRIBUTES"]["CASCADEDELETE"] == "Y");
        $this->onDelete = isset($xmlArr["ATTRIBUTES"]["ONDELETE"]) ? $xmlArr["ATTRIBUTES"]["ONDELETE"] : null;
        $this->onUpdate = isset($xmlArr["ATTRIBUTES"]["ONUPDATE"]) ? $xmlArr["ATTRIBUTES"]["ONUPDATE"] : null;
        $this->condColumn = isset($xmlArr["ATTRIBUTES"]["CONDCOLUMN"]) ? $xmlArr["ATTRIBUTES"]["CONDCOLUMN"] : null;
        $this->condValue = isset($xmlArr["ATTRIBUTES"]["CONDVALUE"]) ? $xmlArr["ATTRIBUTES"]["CONDVALUE"] : null;
        $this->condition = isset($xmlArr["ATTRIBUTES"]["CONDITION"]) ? $xmlArr["ATTRIBUTES"]["CONDITION"] : null;
        if ($this->cascadeDelete) $this->onDelete = "Cascade";
        if ($this->relationship == "M-M" || $this->relationship == "Self-Self")
        {
            $this->xTable = isset($xmlArr["ATTRIBUTES"]["XTABLE"]) ? $xmlArr["ATTRIBUTES"]["XTABLE"] : null;
            $this->xColumn1 = isset($xmlArr["ATTRIBUTES"]["XCOLUMN1"]) ? $xmlArr["ATTRIBUTES"]["XCOLUMN1"] : null;
            $this->xColumn2 = isset($xmlArr["ATTRIBUTES"]["XCOLUMN2"]) ? $xmlArr["ATTRIBUTES"]["XCOLUMN2"] : null;
            $this->xKeyColumn = isset($xmlArr["ATTRIBUTES"]["XKEYCOLUMN"]) ? $xmlArr["ATTRIBUTES"]["XKEYCOLUMN"] : null;
            $this->xDataObj = isset($xmlArr["ATTRIBUTES"]["XDATAOBJ"]) ? $xmlArr["ATTRIBUTES"]["XDATAOBJ"] : null;
            $this->xDataObj = $this->prefixPackage($this->xDataObj);
        }
        //$this->association = @$xmlArr["ATTRIBUTES"]["ASSOCIATION"];

        $this->objectName = $this->prefixPackage($this->objectName);
    }
}

?>