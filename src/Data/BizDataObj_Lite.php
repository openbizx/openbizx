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
 * @version   $Id: BizDataObj_Lite.php 4108 2011-05-08 06:01:30Z jixian2003 $
 */

namespace Openbizx\Data;

use Openbizx\Openbizx;
use Openbizx\Data\DataRecord;
use Openbizx\Data\DataSet;
use Openbizx\Data\Tools\BizDataObj_SQLHelper;

//include_once(OPENBIZ_BIN . 'data/BizDataObj_Abstract.php');
//include_once(OPENBIZ_BIN."util/QueryStringParam.php");

// constant defination
define('CK_CONNECTOR', "#");  // composite key connector character

use Openbizx\Data\BizDataObj_Abstract;

/**
 * BizDataObj_Lite class - contains data object readonly functions
 *
 * @package openbiz.bin.data
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @access public
 */
class BizDataObj_Lite extends BizDataObj_Abstract
{
    /**
     * Record Id
     *
     * @var mixed
     */
    protected $recordId = null;

    /**
     * Current record
     *
     * @var array
     */
    protected $currentRecord = null;

    /**
     * Error message
     *
     * @var string
     */
    protected $errorMessage = "";

    /**
     * Array fields of error
     *
     * @var array
     */
    protected $errorFields = array();
    protected $_fetch4countQuery = null;

    /**
     * Get session variables data of this object
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function loadStatefullVars($sessionContext)
    {
        if ($this->stateless == "Y")
            return;
        $sessionContext->loadObjVar($this->objectName, "RecordId", $this->recordId);
        $sessionContext->loadObjVar($this->objectName, "SearchRule", $this->searchRule);
        $sessionContext->loadObjVar($this->objectName, "SortRule", $this->sortRule);
        $sessionContext->loadObjVar($this->objectName, "OtherSqlRule", $this->otherSQLRule);
        $sessionContext->loadObjVar($this->objectName, "Association", $this->association);
    }

    /**
     * Save Session variables/data of this object
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function saveStatefullVars($sessionContext)
    {
        if ($this->stateless == "Y")
            return;
        $sessionContext->saveObjVar($this->objectName, "RecordId", $this->recordId);
        $sessionContext->saveObjVar($this->objectName, "SearchRule", $this->searchRule);
        $sessionContext->saveObjVar($this->objectName, "SortRule", $this->sortRule);
        $sessionContext->saveObjVar($this->objectName, "OtherSqlRule", $this->otherSQLRule);
        if (is_array($this->association)) {
            $sessionContext->saveObjVar($this->objectName, "Association", $this->association);
        }
    }

    /**
     * Get the error message caused by data action
     *
     * @return string the error message string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Get error fields
     *
     * @return array
     */
    public function getErrorFields()
    {
        return $this->errorFields;
    }

    /**
     * Get the BizField object
     *
     * @param string $fieldName field name
     * @return BizField BizField object
     */
    public function getField($fieldName)
    {
        return $this->bizRecord->get($fieldName);
    }

    /**
     * Get the old value of field before active record
     *
     * @param string $fieldName field name
     * @return mix
     */
    public function getOldValue($fieldName)
    {
        return $this->getField($fieldName)->oldValue;
    }

    /**
     * Get object property
     *
     * @param string $propertyName
     * @return BizField|string|mixed
     */
    public function getProperty($propertyName)
    {
        $ret = parent::getProperty($propertyName);
        if ($ret !== null)
            return $ret;

        // get control object if propertyName is "Control[ctrlname]"
        $pos1 = strpos($propertyName, "[");
        $pos2 = strpos($propertyName, "]");

        if ($pos1 > 0 && $pos2 > $pos1) {
            $propType = substr($propertyName, 0, $pos1);
            $fieldName = substr($propertyName, $pos1 + 1, $pos2 - $pos1 - 1);
            /* if ($propType == "param") {   // get parameter
              return $this->parameters->get($ctrlname);
              } */
            return $this->getField($fieldName);
        }
    }

    /**
     * Get field name by column
     *
     * @param string $column column name
     * @return BizField BizField object
     */
    public function getFieldNameByColumn($column)
    {
        // Todo_Maynotuse: since column and join column can have the same name
        // TODO: ??? get field name but return BizField object ???
        return $this->bizRecord->getFieldByColumn($column); // main table column
    }

    /**
     * Get the BizField value
     *
     * @param string $fieldName field name
     * @return mixed BizField value
     */
    public function getFieldValue($fieldName)
    {
        $rec = $this->getActiveRecord();
        return $rec[$fieldName];
    }

    /**
     * Set the current working record values
     *
     * @param array $currentRecord record array
     * @return void
     * */
    public function setActiveRecord($currentRecord)
    {
        $this->currentRecord = $currentRecord;
        $this->recordId = $this->currentRecord['Id'];
    }

    /**
     * Get the active record
     *
     * @todo throw Openbizx\Data\Exception
     * @return array - record array
     * */
    public function getActiveRecord()
    {
        if ($this->recordId == null || $this->recordId == "") {
            return null;
        }
        if ($this->currentRecord == null) {
            // query on $recordId
            $records = $this->directFetch("[Id]='" . $this->recordId . "'", 1);
            if (count($records) == 1) {
                $this->currentRecord = $records[0];
                $this->recordId = $this->currentRecord["Id"];
            } else {
                $this->currentRecord = null;
            }
        }

        return $this->currentRecord;
    }

    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Set the active record according to the record id
     * @param mixed $recordId record id
     * @return void
     * */
    public function setActiveRecordId($recordId)
    {
        if ($this->recordId != $recordId) {
            $this->recordId = $recordId;
            $this->currentRecord = null;
        }
    }

    /**
     * Fetches SQL result rows as a sequential array according the query rules set before.
     *
     * @return DataSet array of records
     */
    public function fetch()
    {
        $dataSet = new DataSet($this);
        $this->_fetch4countQuery = null;
        $resultSet = $this->_run_search($this->queryLimit);  // regular search or page search
        if ($resultSet !== null) {
            $i = 0;
            while ($recArray = $this->_fetch_record($resultSet)) {
                $dataSet[$i++] = $recArray;
            }
        } else {
            return null;
        }
        return $dataSet;
    }

    /**
     * Fetches SQL result rows as a sequential array without using query rules set before.
     *
     * @param string $searchRule the search rule string
     * @param int $count number of records to return
     * @param int $offset the starting point of the return records
     * @return DataSet set of records
     */
    public function directFetch($searchRule = "", $count = -1, $offset = 0, $sortRule = "")
    {
     
        //Openbizx::$app->getClientProxy()->showClientAlert(__METHOD__ .'-'. __LINE__ . ' msg: $searchRule : '.$searchRule);
        
        $curRecord = $this->currentRecord;
        $recId = $this->recordId;
        $this->currentRecord = null;

        $oldSearchRule = $this->searchRule;
        $this->clearSearchRule();
        $this->setSearchRule($searchRule);

        $oldSortRule = $this->sortRule;
        $this->clearSortRule();
        if ($sortRule) {
            $this->setSortRule($sortRule);
        } else {
            $this->setSortRule($this->sortRule);
        }

        $limit = ($count == -1) ? null : array('count' => $count, 'offset' => $offset);

        $dataSet = new DataSet($this);
        $resultSet = $this->_run_search($limit);
        if ($resultSet !== null) {
            $i = 0;
            while ($recArray = $this->_fetch_record($resultSet)) {
                $dataSet[$i++] = $recArray;
            }
        }

        $this->sortRule = $oldSortRule;
        $this->searchRule = $oldSearchRule;
        $this->currentRecord = $curRecord;
        $this->recordId = $recId;

        if (count($dataSet) == 0) {
            return new DataSet(null);
        }
        return $dataSet;
    }

    /**
     * Fetch a data record by specified Id
     * 
     * @param $Id the Id value of this data object, typically its a numerical value. but  also could be string if the DO is defined for expected a string as Id.
     * @return DataRecord 
     */
    public function fetchById($id)
    {
        $searchRule = "[Id] = '$id'";
        return $this->fetchOne($searchRule);
    }

    /**
     * 
     * Fetch a data record by specified value of name field.
     * its only used if the current data object has a name field. like typical Openbizx Cubi's data object does.
     * 
     * @param string $nameVal
     * @return DataRecord 
     */
    public function fetchByName($nameVal)
    {
        $searchRule = "[name] = '$nameVal'";
        return $this->fetchOne($searchRule);
    }

    /**
     * 
     * Fetch a data record by specified search condition, if the query can return more than one record, 
     * the sort rule will be used for decide which record will be record, like if sort by [Id] ASC, then the smallest id record will be return.
     * @param string $searchRule
     * @param string $sortRule
     * @return DataRecord 
     */
    public function fetchOne($searchRule, $sortRule = "")
    {
        $recordList = $this->directFetch($searchRule, 1, 0, $sortRule);
        if (count($recordList) >= 1) {
            return new DataRecord($recordList[0], $this);
        } else {
            return null;
        }
    }

    /**
     * Run query and get the query results without affecting DataObject internal state
     * by default it gets number of records starting from the first row.
     * if pageNum > 0, it gets number of records starting from the first row of the page
     *
     * @param $searchRule search rule applied on the query
     * @param $resultRecord returned result record array
     * @param $recNum number of records to be returned. if -1, all query results returned
     * @param $clearSearchRule indicates if search rule need to be cleared before query
     * @param $noAssociation indicates if current association condition is not used in query
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     *
     * @todo Why use parameter as result, fetch word mean the function return value by return keyword.
     *       Need to change parameter and result methodology.
     */
    public function fetchRecords($searchRule, &$resultRecords, $count = -1, $offset = 0, $clearSearchRule = true, $noAssociation = false)
    {
        if ($count == 0)
            return;
        $curRecord = $this->currentRecord;
        $recId = $this->recordId;
        $oldSearchRule = $this->searchRule;
        $this->currentRecord = null;
        if ($clearSearchRule)
            $this->clearSearchRule();
        $this->setSearchRule($searchRule);
        if ($noAssociation) {
            $oldAssociation = $this->association;
            $this->association = null;
        }
        $limit = ($count == -1) ? null : array('count' => $count, 'offset' => $offset);

        $resultRecords = array();

        $resultSet = $this->_run_search($limit);
        if ($resultSet !== null) {
            while ($recArray = $this->_fetch_record($resultSet)) {
                $resultRecords[] = $recArray;
            }
        }
        if ($noAssociation) {
            $this->association = $oldAssociation;
        }
        $this->searchRule = $oldSearchRule;
        $this->currentRecord = $curRecord;
        $this->recordId = $recId;
        return true;
    }

    /**
     * Do the search query and return results set as PDOStatement
     *
     * @return PDOStatement PDO statement object
     */
    public function find()
    {
        return $this->_run_search($this->queryLimit);
    }

    /**
     * Get SQL helper
     *
     * @return BizDataObj_SQLHelper
     */
    protected function getSQLHelper()
    {
        return BizDataObj_SQLHelper::instance();
    }

    /**
     * Count the number of record according to the search results set before.
     * It ignores limit setting
     *
     * @return int number of records
     */
    public function count()
    {
        // get database connection
        $db = $this->getDBConnection("READ");
        if ($this->_fetch4countQuery) {
            $querySQL = $this->_fetch4countQuery;
        } else {
            $querySQL = $this->getSQLHelper()->buildQuerySQL($this);
        }
        $this->_fetch4countQuery = null;
        return $this->_getNumberRecords($db, $querySQL);
    }

    /**
     * Run query with current search rule and returns PDO statement
     *
     * @param array $limit - if limit is not null, do the limit search
     * @return PDOStatement
     */
    protected function _run_search($limit = null)
    {
        // get database connection
        $db = $this->getDBConnection("READ");
        $querySQL = $this->getSQLHelper()->buildQuerySQL($this);
        $this->_fetch4countQuery = $querySQL;
        if ($limit && count($limit) > 0 && $limit['count'] > 0) {
            $sql = $db->limit($querySQL, $limit['count'], $limit['offset']);
        } else {
            $sql = $querySQL;
        }

        try {
            if ($this->cacheLifeTime > 0) {
                $cache_id = md5($this->objectName . $sql . serialize($bindValues));
                //try to process cache service.
                $cacheSvc = Openbizx::getService(CACHE_SERVICE, 1);
                $cacheSvc->init($this->objectName, $this->cacheLifeTime);
                if ($cacheSvc->test($cache_id)) {
                    //Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Cache Hit. Query Sql = ".$sql);
                    $resultSetArray = $cacheSvc->load($cache_id);
                } else {
                    Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Query Sql = " . $sql);
                    $resultSet = $db->query($sql);
                    $resultSetArray = $resultSet->fetchAll();
                    $cacheSvc->save($resultSetArray, $cache_id);
                }
            } else {
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Query Sql = " . $sql);
                $resultSet = $db->query($sql);
                $resultSetArray = $resultSet->fetchAll();
            }
        } catch (Exception $e) {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query Error: " . $e->getMessage());
            $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": " . $sql . ". " . $e->getMessage();
            throw new \Openbizx\Data\Exception($this->errorMessage);
            return null;
        }
        return $resultSetArray;
    }

    /**
     * Get the number of records according the Select SQL
     *
     * @param object $db database connection
     * @param string $sql SQL string
     * @return int number of records
     */
    private function _getNumberRecords($db, $sql)
    {
        $has_subquery = false;
        if (preg_match("/\(\s*?SELECT\s*?.+\)/si", $sql)) {
            $has_subquery = true;
        }
        if (preg_match("/^\s*SELECT\s+DISTINCT/is", $sql) || preg_match('/\s+GROUP\s+BY\s+/is', $sql)) {
            // ok, has SELECT DISTINCT or GROUP BY so see if we can use a table alias
            $rewritesql = preg_replace('/(\sORDER\s+BY\s.*)/is', '', $sql);
            $rewritesql = "SELECT COUNT(*) FROM ($rewritesql) _TABLE_ALIAS_";
        } elseif ($has_subquery == false) {
            // now replace SELECT ... FROM with SELECT COUNT(*) FROM
            $rewritesql = preg_replace('/\s*?SELECT\s.*?\s+FROM\s/is', 'SELECT COUNT(*) FROM ', $sql);
            // Because count(*) and 'order by' fails with mssql, access and postgresql.
            // Also a good speedup optimization - skips sorting!
            $rewritesql = preg_replace('/(\sORDER\s+BY\s.*)/is', '', $rewritesql);
        } else {
            $rewritesql = $sql;
        }

        try {
            if ($this->cacheLifeTime > 0) {
                $cache_id = md5($this->objectName . $rewritesql . serialize($bindValues));
                //try to process cache service.
                $cacheSvc = Openbizx::getService(CACHE_SERVICE);
                $cacheSvc->init($this->objectName, $this->cacheLifeTime);
                if ($cacheSvc->test($cache_id)) {
                    //Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", ". Query Sql = ".$rewritesql);
                    $resultArray = $cacheSvc->load($cache_id);
                } else {
                    Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Query Sql = " . $rewritesql);
                    $result = $db->query($rewritesql);
                    $resultArray = $result->fetch();
                    $cacheSvc->save($resultArray, $cache_id);
                }
            } else {
                Openbizx::$app->getLog()->log(LOG_DEBUG, "DATAOBJ", "Query Sql = " . $rewritesql);
                $resultSet = $db->query($rewritesql);
                $resultArray = $resultSet->fetch();
            }
        } catch (Exception $e) {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query Error: " . $e->getMessage());
            $this->errorMessage = $this->getMessage("DATA_ERROR_QUERY") . ": Rewrite:" . $rewritesql . ". Raw:" . $sql . ". " . $e->getMessage();
            throw new \Openbizx\Data\Exception($this->errorMessage);
            return 0;
        }

        if ($has_subquery) {
            $record_count = (int) $resultSet->rowCount();
        } else {
            $record_count = (int) $resultArray[0];
        }
        return (string) $record_count;
    }

    /**
     * Get record from result setand move the cursor to next row
     *
     * @return array record array
     */
    protected function _fetch_record(&$resultSet)
    {
        if (!is_array($resultSet)) {
            return null;
        }
        $sqlArr = current($resultSet);
        if ($sqlArr) {
            $this->currentRecord = $this->bizRecord->convertSqlArrToRecArr($sqlArr);
            $this->currentRecord = $this->bizRecord->getRecordArr($sqlArr);
            $this->recordId = $this->currentRecord["Id"];
            next($resultSet);
        } else {
            return null;
        }
        return $this->currentRecord;
    }

    /**
     * Validate user input data and trigger error message and adjust BizField if invalid.
     *
     * @return boolean
     * */
    public function validateInput() {}

    /**
     * Create an empty new record
     *
     * @return array - empty record array with default values
     * */
    public function newRecord() {}

    /**
     * Insert record using given input record array
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     * */
    public function insertRecord($recArr)
    {

    }

    /**
     * Update record using given input record array
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @param array $oldRec - associated array who is the old record field name / value pairs
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     * */
    public function updateRecord($recArr, $oldRec = null) {}

    /**
     * Delete current record or delete the given input record
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     * */
    public function deleteRecord($recArr) {}

}
