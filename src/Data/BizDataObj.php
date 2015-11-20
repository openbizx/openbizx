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
 * @version   $Id: BizDataObj.php 4108 2011-05-08 06:01:30Z jixian2003 $
 */

namespace Openbizx\Data;

use Openbizx\Openbizx;
use Openbizx\Object\ObjectFactoryHelper;
use Openbizx\Data\Tools\BizDataObj_Assoc;

/**
 * BizDataObj class is the base class of all data object classes
 *
 * @package openbiz.bin.data
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class BizDataObj extends BizDataObj_Lite
{

    public $useTransaction = true;

    public $m_db = null;
    public $bizFormName = null;

    /**
     * Initialize BizDataObj_Abstract with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        parent::__construct($xmlArr);
    }

    /**
     * Validate user input data and trigger error message and adjust BizField if invalid.
     *
     * @return boolean
     * @todo: throw Openbizx\Data\Exception
     * */
    public function validateInput()
    {
        $this->errorFields = array();
        foreach ($this->bizRecord->inputFields as $fld) {

            /* @var $bizField BizField */
            $bizField = $this->bizRecord->get($fld);
            if ($bizField->encrypted == "Y") {
                if ($bizField->checkRequired() == true &&
                        ($bizField->value === null || $bizField->value === "")) {
                    $this->errorMessage = $this->getMessage("DATA_FIELD_REQUIRED", array($fld));
                    $this->errorFields[$bizField->objectName] = $this->errorMessage;
                }
                continue;
            }
            if ($bizField->checkRequired() == true &&
                    ($bizField->value === null || $bizField->value === "")) {
                $this->errorMessage = $this->getMessage("DATA_FIELD_REQUIRED", array($fld));
                $this->errorFields[$bizField->objectName] = $this->errorMessage;
            } elseif ($bizField->value !== null && $bizField->checkValueType() == false) {
                $this->errorMessage = $this->getMessage("DATA_FIELD_INCORRECT_TYPE", array($fld, $bizField->type));
                $this->errorFields[$bizField->objectName] = $this->errorMessage;
            } elseif ($bizField->value !== null && $bizField->Validate() == false) {

                /* @var $validateService validateService */
                $validateService = Openbizx::getService(VALIDATE_SERVICE);
                $this->errorMessage = $validateService->getErrorMessage($bizField->validator, $bizField->objectName);
                if ($this->errorMessage == false) { //Couldn't get a clear error message so let's try this
                    $this->errorMessage = $this->getMessage("DATA_FIELD_INVALID_INPUT", array($fld, $value, $bizField->validator));                //
                }
                $this->errorFields[$bizField->objectName] = $this->errorMessage;
            }
        }
        if (count($this->errorFields) > 0) {
            //print_r($this->errorFields);
            throw new Openbizx\Validation\Exception($this->errorFields);
            //return false;
        }

        // validate uniqueness
        if ($this->validateUniqueness() == false) {
            return false;
        }

        return true;
    }

    /**
     * Validate uniqueness
     * Uniqueness = "fld1,fld2;fld3,fld4;..."
     *
     * @return boolean
     */
    protected function validateUniqueness()
    {
        if (!$this->uniqueness)
            return true;
        $groupList = explode(";", $this->uniqueness);
        foreach ($groupList as $group) {
            $searchRule = "";
            $needCheck = true;
            $fields = explode(",", $group);
            foreach ($fields as $fld) {
                $bizField = $this->bizRecord->get($fld);
                if ($bizField->value === null || $bizField->value === "" || $bizField->value == $bizField->oldValue) {
                    $needCheck = false;
                    break;
                }
                if ($searchRule == "") {
                    $searchRule = "[" . $bizField->objectName . "]='" . addslashes($bizField->value) . "'";
                } else {
                    $searchRule .= " AND [" . $bizField->objectName . "]='" . addslashes($bizField->value) . "'";
                }
            }
            if ($needCheck) {
                $recordList = $this->directFetch($searchRule, 1);
                if ($recordList->count() > 0) {
                    $this->errorMessage = $this->getMessage("DATA_NOT_UNIQUE", array($group));
                    foreach ($fields as $fld) {
                        $this->errorFields[$fld] = $this->errorMessage;
                    }
                }
            }
        }
        if (count($this->errorFields) > 0) {
            throw new Openbizx\Validation\Exception($this->errorFields);
            return false;
        }
        return true;
    }

    /**
     * Check if the current record can be updated
     *
     * @return boolean
     */
    public function canUpdateRecord($record = null)
    {

        if ($this->dataPermControl == 'Y') {
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            if (!$record) {
                $record = $this->getActiveRecord();
            }
            $result = $svcObj->checkDataPerm($record, 2, $this);
            if ($result == false) {
                return false;
            }
        }

        $result = $this->canUpdateRecordCondition();
        return $result;
    }

    public function canUpdateRecordCondition()
    {
        if ($this->updateCondition) {
            //return Expression::evaluateExpression($this->updateCondition,$this);
            return $this->allowAccess($this->updateCondition);
        }
        return true;
    }

    /**
     * Check if the current record can be deleted
     *
     * @return boolean
     */
    public function canDeleteRecord($record = null)
    {
        if ($this->dataPermControl == 'Y') {
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            if (!$record) {
                $record = $this->getActiveRecord();
            }
            $result = $svcObj->checkDataPerm($record, 3, $this);
            if ($result == false) {
                return false;
            }
        }

        $result = $this->canDeleteRecordCondition();
        return $result;
    }

    /**
     *
     * @return boolean
     */
    public function canDeleteRecordCondition()
    {
        if ($this->deleteCondition) {
            // return Expression::evaluateExpression($this->deleteCondition,$this);
            return $this->allowAccess($this->deleteCondition);
        }
        return true;
    }

    /**
     * Update record using given input record array
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @param array $oldRecord - associated array who is the old record field name / value pairs
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     * */
    public function updateRecord($recArr, $oldRecord = null)
    {
        $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('record' => $recArr, 'old_record' => $oldRecord));
        if (!$this->canUpdateRecord($oldRecord)) {
            $this->errorMessage = MessageHelper::getMessage("DATA_NO_PERMISSION_UPDATE", $this->objectName);
            throw new Openbizx\Data\Exception($this->errorMessage);
            return false;
        }

        if (!$oldRecord)
            $oldRecord = $this->getActiveRecord();

        if (!$recArr["Id"])
            $recArr["Id"] = $this->getFieldValue("Id");

        // save the old values
        $this->bizRecord->saveOldRecord($oldRecord);
        // set the new values
        $this->bizRecord->setInputRecord($recArr);

        if (!$this->validateInput())
            return false;

        $sql = $this->getSQLHelper()->buildUpdateSQL($this);

        if ($sql) {
            $db = $this->getDBConnection("WRITE");
            if ($this->useTransaction)
                $db->beginTransaction();

            try {
                $this->cascadeUpdate(); // cascade update

                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Update Sql = $sql");
                $db->query($sql);

                if ($this->useTransaction)
                    $db->commit();
            } catch (Exception $e) {
                if ($this->useTransaction)
                    $db->rollBack();

                if ($e instanceof Openbizx\Data\Exception)
                    throw $e;
                else {
                    Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query error : " . $e->getMessage());
                    $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": " . $sql . ". " . $e->getMessage();
                    throw new Openbizx\Data\Exception($this->errorMessage);
                }
                return false;
            }

            $this->cleanCache(); //clean cached data
            $this->_postUpdateLobFields($recArr);
            $this->currentRecord = null;
            $this->_postUpdateRecord($recArr);
        }
        $this->events()->trigger(__FUNCTION__ . '.post', $this, array('record' => $recArr, 'old_record' => $oldRecord));
        return true;
    }

    /**
     *
     * @param type $setValue
     * @param type $condition
     * @return boolean
     * @throws Openbizx\Data\Exception
     */
    public function updateRecords($setValue, $condition = null)
    {
        if (!$this->canUpdateRecordCondition()) {
            $this->errorMessage = MessageHelper::getMessage("DATA_NO_PERMISSION_UPDATE", $this->objectName);
            return false;
        }
        /* 当$setValue是数组时转成[field]=value格式 */
        if (is_array($setValue)) {
            $setValue_srt = '';
            foreach ($setValue as $key => $value) {
                if ($value != '') {
                    $setValue_srt.=$setValue_srt ? ",[$key]='$value'" : "[$key]='$value'";
                }
            }
            $setValue = $setValue_srt;
        }
        $sql = $this->getSQLHelper()->buildUpdateSQLwithCondition($this, $setValue, $condition);
        $db = $this->getDBConnection("WRITE");

        try {
            if ($sql) {  // delete joint table first then delete main table's data'
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Delete Sql = $sql");
                $db->query($sql);
            }
        } catch (Exception $e) {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query error : " . $e->getMessage());
            $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": " . $sql . ". " . $e->getMessage();
            throw new Openbizx\Data\Exception($this->errorMessage);
            return false;
        }

        //clean cached data
        $this->cleanCache();
        return true;
    }

    /**
     * Check if the field is blob/clob type.
     * In the lob case, update (lob value only)
     *
     * @param array $recArr
     * @return mixed boolean or null
     */
    private function _postUpdateLobFields(&$recArr)
    {
        $searchRule = $this->bizRecord->getKeySearchRule(false, true);
        foreach ($this->bizRecord as $field) {
            if (isset($recArr[$field->objectName]) && $field->isLobField() && $field->column != "") {
                $db = $this->getDBConnection("WRITE");
                $sql = "UPDATE " . $this->mainTableName . " SET " . $field->column . "=? WHERE $searchRule";
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Update lob Sql = $sql");
                $stmt = $db->prepare($sql);

                $fp = fopen($recArr[$field->objectName], 'rb');
                $stmt->bindParam(1, $fp, PDO::PARAM_LOB);

                try {
                    $stmt->execute();
                } catch (Exception $e) {
                    $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": " . $sql . ". " . $e->getMessage();
                    Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Update lob error = $sql");
                    fclose($fp);
                    throw new Openbizx\Data\Exception($this->errorMessage);
                    return null;
                }

                fclose($fp);
                return true;
            }
        }
        return true;
    }

    /**
     * Action after update record is done
     *
     * @param array $recArr
     * @return void
     */
    private function _postUpdateRecord($recArr)
    {
        // run DO trigger
        $this->_runDOTrigger("UPDATE");
    }

    /**
     * Create an empty new record
     *
     * @return array - empty record array with default values
     * */
    public function newRecord()
    {
        $recArr = $this->bizRecord->getEmptyRecordArr();

        // if association is 1-M, set the field (pointing to the column) value as the FieldRefVal
        if ($this->association["Relationship"] == "1-M") {
            foreach ($this->bizRecord as $field) {
                if ($field->column == $this->association["Column"] && !$field->join) {
                    $recArr[$field->objectName] = $this->association["FieldRefVal"];
                    break;
                }
            }
        }

        return $recArr;
    }

    /**
     * Generate Id according to the IdGeneration attribute
     *
     * @param boolean $isBeforeInsert
     * @param string $tableName
     * @param string $idCloumnName
     * @return long|string|boolean
     */
    protected function generateId($isBeforeInsert = true, $tableName = null, $idCloumnName = null)
    {
        // Identity type id is generated after insert is done.
        // If this method is called before insert, return null.
        if ($isBeforeInsert && $this->idGeneration == 'Identity')
            return null;

        if (!$isBeforeInsert && $this->idGeneration != 'Identity') {
            $this->errorMessage = MessageHelper::getMessage("DATA_UNABLE_GET_ID", $this->objectName);
            return false;
        }

        /* @var $genIdService genIdService */
        $genIdService = Openbizx::getService(GENID_SERVICE);
        if ($this->m_db) {
            $db = $this->m_db;
        } else {
            $db = $this->getDBConnection("READ");
        }
        $dbInfo = Openbizx::$app->getConfiguration()->getDatabaseInfo($this->databaseAliasName);
        $dbType = $dbInfo["Driver"];
        $table = $tableName ? $tableName : $this->mainTableName;
        $column = $idCloumnName ? $idCloumnName : $this->getField("Id")->column;

        try {
            $newId = $genIdService->getNewID($this->idGeneration, $db, $dbType, $table, $column);
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            return false;
        }
        return $newId;
    }

    /**
     * Insert record using given input record array
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @return boolean - if return false, the caller can call getErrorMessage to get the error.
     * */
    public function insertRecord($recArr)
    {
        $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('record', $recArr));
        if ($this->_isNeedGenerateId($recArr))
            $recArr["Id"] = $this->generateId();    // for certain cases, id is generated before insert

        $this->bizRecord->setInputRecord($recArr);

        if (!$this->validateInput())
            return false;

        $db = $this->getDBConnection("WRITE");

        try {
            $sql = $this->getSQLHelper()->buildInsertSQL($this, $joinValues);
            if ($sql) {
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Insert Sql = $sql");
                $db->query($sql, $bindValues);
            }
            //$mainId = $db->lastInsertId();
            if ($this->_isNeedGenerateId($recArr)) {
                $this->m_db = $db; //compatiable for CLI mode and also speed up of it running
                $mainId = $this->generateId(false);
                $recArr["Id"] = $mainId;
            }
            Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "New record Id is " . $recArr["Id"]);
        } catch (Exception $e) {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query Error : " . $e->getMessage());
            $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": " . $sql . ". " . $e->getMessage();
            throw new Openbizx\Data\Exception($this->errorMessage);
            return null;
        }

        $this->bizRecord->setInputRecord($recArr);

        if ($this->_postUpdateLobFields($recArr) === false) {
            $this->errorMessage = $db->ErrorMsg();
            return false;
        }

        $this->cleanCache();

        $this->recordId = $recArr["Id"];
        $this->currentRecord = null;

        $this->_postInsertRecord($recArr);
        $this->events()->trigger(__FUNCTION__ . '.post', $this, array('record', $recArr));
        return $recArr["Id"];
    }

    /**
     * Action after insert record is done
     *
     * @param array $recArr
     */
    private function _postInsertRecord($recArr)
    {
        // do trigger
        $this->_runDOTrigger("INSERT");
    }

    /**
     * Delete current record or delete the given input record
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     * */
    public function deleteRecord($recArr)
    {
        $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('record', $recArr));
        if (!$this->canDeleteRecord()) {
            $this->errorMessage = MessageHelper::getMessage("DATA_NO_PERMISSION_DELETE", $this->objectName);
            throw new Openbizx\Data\Exception($this->errorMessage);
            return false;
        }

        if ($recArr) {
            $delrec = $recArr;
        } else {
            $delrec = $this->getActiveRecord();
        }

        $this->bizRecord->setInputRecord($delrec);

        $sql = $this->getSQLHelper()->buildDeleteSQL($this);
        if ($sql) {
            $db = $this->getDBConnection("WRITE");
            $db->beginTransaction();
            try {
                $this->cascadeDelete(); // cascade delete
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Delete Sql = $sql");
                $db->query($sql);
                $db->commit();
                $this->bizRecord->saveOldRecord($delrec); // save old record only if delete success
            } catch (Exception $e) {
                $db->rollBack();
                if ($e instanceof Openbizx\Data\Exception)
                    throw $e;
                else {
                    Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query error : " . $e->getMessage());
                    $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": " . $sql . ". " . $e->getMessage();
                    throw new Openbizx\Data\Exception($this->errorMessage);
                }
                return false;
            }
        }

        //clean cached data
        $this->cleanCache();

        $this->_postDeleteRecord($this->bizRecord->getKeyValue());
        $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('record', $recArr));
        return true;
    }

    public function deleteRecords($condition = null)
    {
        if (!$this->canDeleteRecordCondition()) {
            throw new Openbizx\Data\Exception(MessageHelper::getMessage("DATA_NO_PERMISSION_DELETE", $this->objectName));
            return false;
        }

        $sql = $this->getSQLHelper()->buildDeleteSQLwithCondition($this, $condition);
        $db = $this->getDBConnection("WRITE");

        try {
            if ($sql) {  // delete joint table first then delete main table's data'
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Delete Sql = $sql");
                $db->query($sql);
            }
        } catch (Exception $e) {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query error : " . $e->getMessage());
            $db->rollBack(); //if one failed then rollback all
            $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": " . $sql . ". " . $e->getMessage();
            throw new Openbizx\Data\Exception($this->errorMessage);
            return false;
        }

        //clean cached data
        $this->cleanCache();
        return true;
    }

    /**
     * Action after delete record is done
     *
     * @return void
     */
    private function _postDeleteRecord()
    {
        // do trigger
        $this->_runDOTrigger("DELETE");
    }

    // $action: Delete, Update
    protected function processCascadeAction($objRef, $cascadeType)
    {
        if (($cascadeType == 'Delete' && $objRef->onDelete) || ($cascadeType == 'Update' && $objRef->onUpdate)) {
            if ($objRef->relationship == "1-M" || $objRef->relationship == "1-1") {
                $table = $objRef->table;
                $column = $objRef->column;
                $column2 = $objRef->column2;
            } else if ($objRef->relationship == "M-M" || $objRef->relationship == "Self-Self") {
                $table = $objRef->xTable;
                $column = $objRef->xColumn1;
            }
            $refField = $this->getField($objRef->fieldRef);
            $fieldVal = $this->getFieldValue($objRef->fieldRef);

            $fieldVal2 = $this->getFieldValue($objRef->fieldRef2);
            if (!$fieldVal)
                return;
            if ($column2) {
                if (!$fieldVal2)
                    return;
            }

            $db = $this->getDBConnection("WRITE");
            // get the cascade action sql
            if ($cascadeType == 'Delete') {
                if ($objRef->onDelete == "Cascade") {
                    $sql = "DELETE FROM " . $table . " WHERE " . $column . "='" . $fieldVal . "'";
                    if ($column2 && $fieldVal2) {
                        $sql .= " AND " . $column2 . "='" . $fieldVal2 . "'";
                    }
                } else if ($objRef->onDelete == "SetNull") {
                    $sql = "UPDATE " . $table . " SET $column=null WHERE " . $column . "='" . $fieldVal . "'";
                    if ($column2 && $fieldVal2) {
                        $sql .= " AND " . $column2 . "='" . $fieldVal2 . "'";
                    }
                } else if ($objRef->onDelete == "Restrict") {
                    // check if objRef has records
                    $refObj = $this->getRefObject($objRef->objectName);
                    $sql = "`$column`='" . $refField->value . "'";
                    if ($column2 && $fieldVal2) {
                        $sql .= " AND " . $column2 . "='" . $fieldVal2 . "'";
                    }
                    if (count($refObj->directFetch($sql, 1)) == 1) {
                        throw new Openbizx\Data\Exception($this->getMessage("DATA_UNABLE_DEL_REC_CASCADE", array($objRef->objectName)));
                    }
                    return;
                }
            } else if ($cascadeType == 'Update') {
                // check if the column value is actually changed
                if ($refField->oldValue == $refField->value)
                    return;

                if ($objRef->onUpdate == "Cascade") {
                    $sql = "UPDATE " . $table . " SET $column='" . $refField->value . "' WHERE " . $column . "='" . $refField->oldValue . "'";
                    if ($column2 && $fieldVal2) {
                        $sql .= " AND " . $column2 . "='" . $fieldVal2 . "'";
                    }
                } else if ($objRef->onUpdate == "SetNull") {
                    $sql = "UPDATE " . $table . " SET $column=null WHERE " . $column . "='" . $refField->oldValue . "'";
                    if ($column2 && $fieldVal2) {
                        $sql .= " AND " . $column2 . "='" . $fieldVal2 . "'";
                    }
                } else if ($objRef->onUpdate == "Restrict") {
                    // check if objRef has records
                    $refObj = Openbizx::getObject($objRef->objectName);
                    $sql = "[" . $objRef->fieldRef . "]='" . $refField->oldValue . "'";
                    if ($column2 && $fieldVal2) {
                        $sql .= " AND " . $column2 . "='" . $fieldVal2 . "'";
                    }
                    if (count($refObj->directFetch($sql, 1)) == 1) {
                        throw new Openbizx\Data\Exception($this->getMessage("DATA_UNABLE_UPD_REC_CASCADE", array($objRef->objectName)));
                    }
                    return;
                }
            }
            try {
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Cascade $cascadeType Sql = $sql");
                $db->query($sql);
            } catch (Exception $e) {
                Openbizx::$app->getLog()->log(LOG_Err, "DATAOBJ", "Cascade $cascadeType Error: " . $e->getMessage());
                $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": " . $sql . ". " . $e->getMessage();
                throw new Openbizx\Data\Exception($this->errorMessage);
            }
        }
    }

    /**
     * Run cascade delete
     * @return void
     */
    protected function cascadeDelete()
    {
        foreach ($this->objReferences as $objRef) {
            $this->processCascadeAction($objRef, "Delete");
        }
    }

    /**
     * Run cascade update
     * @return void
     */
    protected function cascadeUpdate()
    {
        foreach ($this->objReferences as $objRef) {
            $this->processCascadeAction($objRef, "Update");
        }
    }

    /**
     * Get auditable fields
     *
     * @return array list of {@link BizField} objects who are auditable
     */
    public function getOnAuditFields()
    {
        $fieldList = array();
        foreach ($this->bizRecord as $field) {
            if ($field->onAudit)
                $fieldList[] = $field;
        }
        return $fieldList;
    }

    /**
     * Run DataObject trigger
     *
     * @param string $triggerType type of the trigger
     */
    private function _runDOTrigger($triggerType)
    {
        // locate the trigger metadata file BOName_Trigger.xml
        $triggerServiceName = $this->objectName . "_Trigger";
        $xmlFile = ObjectFactoryHelper::getXmlFileWithPath($triggerServiceName);
        if (!$xmlFile) {
            return;
        }

        $triggerService = Openbizx::getObject($triggerServiceName);
        if ($triggerService == null) {
            return;
        }
        // invoke trigger service ExecuteTrigger($triggerType, $currentRecord)

        $triggerService->execute($this, $triggerType);
    }

    /**
     * Get all fields that belong to the same join of the input field
     *
     * @param BizDataObj $joinDataObj the join data object
     * @return array joined fields array
     */
    public function getJoinFields($joinDataObj)
    {
        // get the maintable of the joindataobj
        $joinTable = $joinDataObj->mainTableName;
        $returnRecord = array();

        // find the proper join according to the maintable
        foreach ($this->tableJoins as $tableJoin) {
            if ($tableJoin->table == $joinTable) {
                // populate the column-fieldvalue to columnRef-fieldvalue
                // get the field mapping to the column, then get the field value
                $joinFieldName = $joinDataObj->bizRecord->getFieldByColumn($tableJoin->column); // joined-main table

                if (!$joinFieldName) {
                    continue;
                }

                $refFieldName = $this->bizRecord->getFieldByColumn($tableJoin->columnRef); // join table
                $returnRecord[$refFieldName] = $joinFieldName;

                // populate joinRecord's field to current record
                foreach ($this->bizRecord as $field) {
                    if ($field->join == $tableJoin->objectName) {
                        // use join column to match joinRecord field's column
                        $jFieldName = $joinDataObj->bizRecord->getFieldByColumn($field->column); // joined-main table
                        $returnRecord[$field->objectName] = $jFieldName;
                    }
                }
                break;
            }
        }
        return $returnRecord;
    }

    /**
     * Pick the joined object's current record to the current record
     *
     * @param BizDataObj $joinDataObj
     * @param string $joinName name of join (optional)
     * @return array return a modified record with joined record data
     */
    public function joinRecord($joinDataObj, $joinName = "")
    {
        // get the maintable of the joindataobj
        $joinTable = $joinDataObj->mainTableName;
        $joinRecord = null;
        $returnRecord = array();

        // find the proper join according to join name and the maintable
        foreach ($this->tableJoins as $tableJoin) {
            if (($joinName == $tableJoin->objectName || $joinName == "") && $tableJoin->table == $joinTable) {
                // populate the column-fieldvalue to columnRef-fieldvalue
                // get the field mapping to the column, then get the field value
                $joinFieldName = $joinDataObj->bizRecord->getFieldByColumn($tableJoin->column); // joined-main table
                if (!$joinFieldName)
                    continue;
                if (!$joinRecord)
                    $joinRecord = $joinDataObj->getActiveRecord();
                $refFieldName = $this->bizRecord->getFieldByColumn($tableJoin->columnRef); // join table
                $returnRecord[$refFieldName] = $joinRecord[$joinFieldName];
                // populate joinRecord's field to current record
                foreach ($this->bizRecord as $fld) {
                    if ($fld->join == $tableJoin->objectName) {
                        // use join column to match joinRecord field's column
                        $jfldname = $joinDataObj->bizRecord->getFieldByColumn($fld->column); // joined-main table
                        $returnRecord[$fld->objectName] = $joinRecord[$jfldname];
                    }
                }
                break;
            }
        }
        // return a modified record with joined record data
        return $returnRecord;
    }

    /**
     * Add a new record to current record set
     *
     * @param array $recArr
     * @param boolean $isParentObjUpdated
     * @return boolean
     */
    public function addRecord($recArr, &$isParentObjUpdated)
    {
        $oldBaseSearchRule = $this->baseSearchRule;
        $this->baseSearchRule = "";
        $result = BizDataObj_Assoc::addRecord($this, $recArr, $isParentObjUpdated);
        //$this->baseSearchRule=$oldBaseSearchRule;
        return $result;
    }

    /**
     * Remove a record from current record set of current association relationship
     *
     * @param array $recArr
     * @param boolean &$isParentObjUpdated
     * @return boolean
     */
    public function removeRecord($recArr, &$isParentObjUpdated)
    {
        return BizDataObj_Assoc::removeRecord($this, $recArr, $isParentObjUpdated);
    }

    /**
     * Clean chache
     *
     * @global BizSystem $g_BizSystem
     * @return void
     */
    public function cleanCache()
    {
        if ($this->cacheLifeTime > 0) {
            $cacheSvc = Openbizx::getService(CACHE_SERVICE, 1);
            $cacheSvc->init($this->objectName, $this->cacheLifeTime);
            $cacheSvc->cleanAll();
        }
    }

    /**
     * Is need to generate Id?
     *
     * @param array $recArr array of record
     * @return boolean
     */
    private function _isNeedGenerateId($recArr)
    {
        if ($this->idGeneration != 'None' && (!$recArr["Id"] || $recArr["Id"] == "")) {
            return true;
        }
        if ($this->idGeneration == 'Identity') {
            return true;
        }
    }

}
