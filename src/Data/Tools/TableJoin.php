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
 * @version   $Id: TableJoin.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Data\Tools;

use Openbizx\Object\MetaObject;

/**
 * TableJoin class defines the table join used in BizDataObj
 *
 * Configuration of TabelJoin stored in BizDataObj xml file.
 *
 * @package openbiz.bin.data.private
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 */
class TableJoin extends MetaObject
{
    /**
     * Name of tabel that joined to master table
     *
     * @var string
     */
    public $table;

    /**
     * Column/field name of table that joined to master table as reference
     *
     * @var string
     */
    public $column;

    /**
     * ????
     *
     * @var string
     * @todo blank description
     */
    public $joinRef;

    /**
     * Column name of master table as reference for join table
     *
     * @var string
     */
    public $columnRef;

    /**
     * SQL command for join type like INNER JOIN, LEFT JOIN, RIGHT JOIN or OUTER JOIN
     *
     * @var string
     */
    public $joinType;
	
	/**
     * Additional join condition other than the foriegn matching
     *
     * @var string
     */
	public $joinCondition;

    /**
     *
     * @var <type>
     * @todo what is mean?
     */
    public $onSaveDataObj;

    /**
     * Initialize TableJoin with xml array
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
        $this->table = isset($xmlArr["ATTRIBUTES"]["TABLE"]) ? $xmlArr["ATTRIBUTES"]["TABLE"] : null;
        $this->column = isset($xmlArr["ATTRIBUTES"]["COLUMN"]) ? $xmlArr["ATTRIBUTES"]["COLUMN"] : null;
        $this->joinRef = isset($xmlArr["ATTRIBUTES"]["JOINREF"]) ? $xmlArr["ATTRIBUTES"]["JOINREF"] : null;
        $this->columnRef = isset($xmlArr["ATTRIBUTES"]["COLUMNREF"]) ? $xmlArr["ATTRIBUTES"]["COLUMNREF"] : null;
        $this->joinType = isset($xmlArr["ATTRIBUTES"]["JOINTYPE"]) ? $xmlArr["ATTRIBUTES"]["JOINTYPE"] : null;
		$this->joinCondition = isset($xmlArr["ATTRIBUTES"]["JOINCONDITION"]) ? $xmlArr["ATTRIBUTES"]["JOINCONDITION"] : null;
        $this->onSaveDataObj = isset($xmlArr["ATTRIBUTES"]["ONSAVEDATAOBJ"]) ? $xmlArr["ATTRIBUTES"]["ONSAVEDATAOBJ"] : null;

        // tmp_remark
        //$this->bizDataObjName = $this->prefixPackage($this->bizDataObjName);
    }
}

?>