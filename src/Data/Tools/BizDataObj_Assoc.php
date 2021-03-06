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
 * @version   $Id: BizDataObj_Assoc.php 3975 2011-04-27 15:51:18Z jixian2003 $
 */

namespace Openbizx\Data\Tools;

/**
 * BizDataObj_Assoc class takes care of add and remove record according to
 * data object association
 *
 * @package openbiz.bin.data.private
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access private
 */
class BizDataObj_Assoc
{

    /**
     * Add a new record to current record set
     *
     * @param BizDataObj $dataObj - the instance of BizDataObj
     * @param array $recArr - the record array to be added in the data object
     * @param boolean &$isParentObjUpdated - flag that indicates if the parent form needs to be updated
     * @return boolean
     */
    public static function addRecord($dataObj, $recArr, &$isParentObjUpdated)
    {
        if ($dataObj->association["Relationship"] == "M-M") {
            $isParentObjUpdated = false;
            return self::_addRecordMtoM($dataObj, $recArr);
        } elseif ($dataObj->association["Relationship"] == "Self-Self") {
            $isParentObjUpdated = false;
            return self::_addRecordSelftoSelf($dataObj, $recArr);
        } elseif ($dataObj->association["Relationship"] == "M-1" || $dataObj->association["Relationship"] == "1-1") {
            $isParentObjUpdated = true;
            return self::_addRecordMto1($dataObj, $recArr);
        } elseif ($dataObj->association["Relationship"] == "1-M") {
            $isParentObjUpdated = false;
            return self::_addRecord1toM($dataObj, $recArr);
        } else {
            throw new Openbizx\Data\Exception("You cannot add a record in dataobj who doesn't have M-M or M-1 or Self-Self relationship with its parent object");
            return false;
        }
    }

    /**
     * Add record many to many (M-M)
     *
     * @param BizDataObj $dataObj
     * @param array $recArr
     * @return boolean
     */
    private static function _addRecordMtoM($dataObj, $recArr)
    {
        // query on this object to get the corresponding record of this object.
        $searchRule = "[Id] = '" . $recArr["Id"] . "'";
        $recordList = $dataObj->directFetch($searchRule, 1);
        if (count($recordList) == 1) {
            return true;
        }

        // insert a record on XTable
        $db = $dataObj->getDBConnection();
        $xDataObj = isset($dataObj->association["XDataObj"]) ? $dataObj->association["XDataObj"] : null;
        $val1 = $dataObj->association["FieldRefVal"];
        $val2 = $recArr["Id"];
        if ($xDataObj) {   // get new record from XDataObj
            $xObj = Openbizx::getObject($xDataObj);
            $newRecArr = $xObj->newRecord();
            // verify the main table of XDataobj is same as the XTable
            if ($xObj->mainTableName != $dataObj->association["XTable"]) {
                throw new Openbizx\Data\Exception("Unable to create a record in intersection table: XDataObj's main table is not same as XTable.");
                //return false;
            }
            $fld1 = $xObj->getFieldNameByColumn($dataObj->association["XColumn1"]);
            $newRecArr[$fld1] = $val1;
            $fld2 = $xObj->getFieldNameByColumn($dataObj->association["XColumn2"]);
            $newRecArr[$fld2] = $val2;
            $ok = $xObj->insertRecord($newRecArr);
            if ($ok === false) {
                throw new Openbizx\Data\Exception($xObj->getErrorMessage());
                //return false;
            }
        } else {
            $sql_col = "(" . $dataObj->association["XColumn1"] . ","
                    . $dataObj->association["XColumn2"] . ")";

            $sql_val = "('" . $val1 . "','" . $val2 . "')";
            $sql = "INSERT INTO " . $dataObj->association["XTable"] . " "
                    . $sql_col . " VALUES " . $sql_val;

            try {
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Associate Insert Sql = $sql");
                $db->query($sql);
            } catch (Exception $e) {
                Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query Error: " . $e->getMessage());
                throw new Openbizx\Data\Exception("Query Error: " . $e->getMessage());
                return false;
            }
        }

        // add the record to object cache. requery on this object to get the corresponding record of this object.
        $searchRule = "[Id] = '" . $recArr["Id"] . "'";
        $recordList = $dataObj->directFetch($searchRule, 1);
        if (count($recordList) == 0)
            return false;
        return true;
    }

    private static function _addRecordSelftoSelf($dataObj, $recArr)
    {
        $result = self::_addRecordMtoM($dataObj, $recArr);
        return true;
    }

    /**
     * Add record Many to One (M-1)
     * @param BizDataObj $dataObj
     * @param array $recArr
     * @return boolean
     */
    private static function _addRecordMto1($dataObj, $recArr)
    {
        // set the $recArr[Id] to the parent table foriegn key column
        // get parent/association dataobj
        $asscObj = Openbizx::getObject($dataObj->association["AsscObjName"]);
        // call parent dataobj's updateRecord
        $updateRecArr["Id"] = $asscObj->getFieldValue("Id");
        $updateRecArr[$dataObj->association["FieldRef"]] = $recArr["Id"];
        $ok = $asscObj->updateRecord($updateRecArr);
        if ($ok == false)
            return false;
        // requery on this object
        $dataObj->association["FieldRefVal"] = $recArr["Id"];
        return $dataObj->runSearch();
    }

    private static function _addRecord1toM($dataObj, $recArr)
    {
        $column = $dataObj->association['Column'];
        $field = $dataObj->getFieldNameByColumn($column);

        $parentRefVal = $dataObj->association["FieldRefVal"];

        $newRecArr["Id"] = $recArr["Id"];
        $newRecArr[$field] = $parentRefVal;

        $column2 = $dataObj->association['Column2'];
        $field2 = $dataObj->getFieldNameByColumn($column2);

        $parentRefVal2 = $dataObj->association["FieldRefVal2"];
        if ($column2) {
            $newRecArr[$field2] = $parentRefVal2;
        }

        $cond_column = $dataObj->association['CondColumn'];
        $cond_value = $dataObj->association['CondValue'];
        if ($cond_column) {
            $cond_field = $dataObj->getFieldNameByColumn($cond_column);
            $newRecArr[$cond_field] = $cond_value;
        }

        $newDO = Openbizx::getObject($dataObj->objectName, 1);
        $ok = $newDO->updateRecord($newRecArr, $recArr);

        if ($ok == false)
            return false;
        // requery on this object        
        return true;
    }

    /**
     * Remove a record from current record set of current association relationship
     *
     * @param BizDataObj $dataObj - the instance of BizDataObj
     * @param array $recArr - the record array to be removed from the data object
     * @param boolean &$isParentObjUpdated - flag that indicates if the parent form needs to be updated
     * @return boolean
     */
    public static function removeRecord($dataObj, $recArr, &$isParentObjUpdated)
    {
        if ($dataObj->association["Relationship"] == "M-M") {
            $isParentObjUpdated = false;
            return self::_removeRecordMtoM($dataObj, $recArr);
        } elseif ($dataObj->association["Relationship"] == "Self-Self") {
            $isParentObjUpdated = false;
            return self::_removeRecordSelftoSelf($dataObj, $recArr);
        } elseif ($dataObj->association["Relationship"] == "M-1" || $dataObj->association["Relationship"] == "1-1") {
            $isParentObjUpdated = true;
            return self::_removeRecordMto1($dataObj, $recArr);
        } elseif ($dataObj->association["Relationship"] == "1-M") {
            $isParentObjUpdated = false;
            return self::_removeRecord1toM($dataObj, $recArr);
        } else {
            throw new Openbizx\Data\Exception("You cannot add a record in dataobj who doesn't have M-M or M-1 or Self-Self relationship with its parent object");
            return false;
        }
    }

    /**
     * Remove record many to many
     *
     * @param BizDataObj $dataObj
     * @param array $recArr
     * @return boolean
     */
    private static function _removeRecordMtoM($dataObj, $recArr)
    {
        // delete a record on XTable
        $db = $dataObj->getDBConnection();

        //TODO: delete using XDataObj if XDataObj is defined

        $where = $dataObj->association["XColumn1"] . "='" . $dataObj->association["FieldRefVal"] . "'";
        $where .= " AND " . $dataObj->association["XColumn2"] . "='" . $recArr["Id"] . "'";
        $sql = "DELETE FROM " . $dataObj->association["XTable"] . " WHERE " . $where;

        try {
            Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Associate Delete Sql = $sql");
            $db->query($sql);
        } catch (Exception $e) {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query Error: " . $e->getMessage());
            throw new Openbizx\Data\Exception("Query Error: " . $e->getMessage());
            return false;
        }
        return true;
    }

    private static function _removeRecordSelftoSelf($dataObj, $recArr)
    {
        // delete a record on XTable
        $db = $dataObj->getDBConnection();

        //TODO: delete using XDataObj if XDataObj is defined

        $where = $dataObj->association["XColumn1"] . "='" . $dataObj->association["FieldRefVal"] . "'";
        $where .= " AND " . $dataObj->association["XColumn2"] . "='" . $recArr["Id"] . "'";
        $sql = "DELETE FROM " . $dataObj->association["XTable"] . " WHERE " . $where;

        $where_2 = $dataObj->association["XColumn2"] . "='" . $dataObj->association["FieldRefVal"] . "'";
        $where_2 .= " AND " . $dataObj->association["XColumn1"] . "='" . $recArr["Id"] . "'";
        $sql_2 = "DELETE FROM " . $dataObj->association["XTable"] . " WHERE " . $where_2;

        try {
            Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Associate Delete Sql = $sql");
            $db->query($sql);
            $db->query($sql_2);
        } catch (Exception $e) {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query Error: " . $e->getMessage());
            throw new Openbizx\Data\Exception("Query Error: " . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Remove record many to one
     *
     * @global BizSystem $g_BizSystem
     * @param BizDataObj $dataObj
     * @param array $recArr
     * @return boolean
     */
    private static function _removeRecordMto1($dataObj, $recArr)
    {
        // set the $recArr[Id] to the parent table foriegn key column
        // get parent/association dataobj
        $asscObj = Openbizx::getObject($dataObj->association["AsscObjName"]);
        // call parent dataobj's updateRecord
        $updateRecArr["Id"] = $asscObj->getFieldValue("Id");
        $updateRecArr[$dataObj->association["FieldRef"]] = "";
        $ok = $asscObj->updateRecord($updateRecArr);
        if ($ok == false)
            return false;
        // requery on this object
        $dataObj->association["FieldRefVal"] = "";
        return $dataObj->runSearch();
    }

    private static function _removeRecord1toM($dataObj, $recArr)
    {

        $column = $dataObj->association['Column'];
        $field = $dataObj->getFieldNameByColumn($column);

        $column2 = $dataObj->association['Column2'];
        $field2 = $dataObj->getFieldNameByColumn($column2);

        $newRecArr["Id"] = $recArr["Id"];
        $newRecArr[$field] = '';

        if ($field2) {
            $newRecArr[$field2] = '';
        }

        $cond_column = $dataObj->association['CondColumn'];
        $cond_value = $dataObj->association['CondValue'];

        if ($cond_column) {
            $cond_field = $dataObj->getFieldNameByColumn($cond_column);
            $newRecArr[$cond_field] = $cond_value;
        }
        $ok = Openbizx::getObject($dataObj->objectName, 1)->updateRecord($newRecArr, $recArr);
        if ($ok == false)
            return false;
        // requery on this object        
        return true;
    }

}
