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
 * @version   $Id: BizDataObj_Abstract.php 4108 2011-05-08 06:01:30Z jixian2003 $
 */

namespace Openbizx\Data;

use Openbizx\Openbizx;
use Openbizx\Helpers\MessageHelper;
use Openbizx\Object\Statefullable;
use Openbizx\Object\MetaObject;
use Openbizx\Object\MetaIterator;
use Openbizx\Event\EventManager;
use Openbizx\Data\Tools\BizRecord;

/**
 * BizDataObj_Abstract class contains data object metadata functions
 *
 * @package   openbiz.bin.data
 * @author rocky swen
 * @copyright Copyright (c) 2005
 * @access public
 */
abstract class BizDataObj_Abstract extends MetaObject implements Statefullable
{
    // metadata vars are public, necessary for metadata inheritance

    /**
     * Database name
     *
     * @var string
     */
    public $databaseAliasName;

    /**
     * Base search rule
     *
     * @var string
     */
    public $baseSearchRule = null;    // support expression

    /**
     * Base sort rule
     *
     * @var string
     */
    public $baseSortRule = null;      // support expression

    /**
     * Base other SQL rule
     *
     * @var string
     */
    public $baseOtherSQLRule = null;  // support expression

    /**
     * Name of main table
     * 
     * @var string
     */
    public $mainTableName = "";

    /**
     * BizRecord object
     * 
     * @var BizRecord
     */
    public $bizRecord = null;

    /**
     * Name of inherited form (meta-form)
     *
     * @var string
     */
    public $inheritFrom;

    /**
     * Access rule (visibility) of the records
     * Can be Openbizx query string or any expression
     * Example: [user_id]={@profile['Id']} or {@vis:self([user_id])} or {@vis:group([group_id])}
     * @var string
     */
    public $accessRule = null;

    /**
     * Condition of user ability to update a record
     * @var string
     */
    public $updateCondition = null;   // support expression

    /**
     * Condition of user ability to delete a record
     * @var string
     */
    public $deleteCondition = null;   // support expression

    /**
     * Record id generation option
     *
     * @var string
     */
    public $idGeneration = null;

    /**
     * MetaIterator of ObjReferences
     *
     * @var MetaIterator
     */
    public $objReferences = null;

    /**
     * MetaIterator of TableJoin
     *
     * @var MetaIterator
     */
    public $tableJoins = null;

    /**
     *
     * @var MetaIterator
     */
    public $parameters = null;
    public $stateless = null;
    public $uniqueness = null;

    /**
     * Search rule
     *
     * @var string
     */
    public $searchRule = null;        // support expression

    /**
     * Sort rule
     * @var string
     */
    public $sortRule = null;          // support expression

    /**
     * Other SQL rule
     *
     * @var string
     */
    public $otherSQLRule = null;      // support expression

    /**
     * Life time o cache
     *
     * @var number
     */
    public $cacheLifeTime = null;     // set 0 to disbale cache function

    /**
     * Message file path
     *
     * @var string
     */
    public $messageFile = null;        // 

    /**
     * Limitation of query
     *   $this->queryLimit['count'] - count of record that loaded from database (per page)
     *   $this->queryLimit['offset'] - offset of record (for paging)
     * 
     * @var array
     */
    protected $queryLimit = array();

    /**
     * Array messages that loaded from {@link $messageFile}
     *
     * @var array
     */
    protected $objectMessages;
    protected $queryParams = array();
    public $dataPermControl;
    public $eventManagerName;
    public $association;
    public $eventManager;

    /**
     * Initialize BizDataObj_Abstract with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
        $this->inheritParentObj();
    }

    /**
     * Read Metadata from xml array
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetadata(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->inheritFrom = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["INHERITFROM"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["INHERITFROM"] : null;
        $this->searchRule = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["SEARCHRULE"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["SEARCHRULE"] : null;
        $this->baseSearchRule = $this->searchRule;
        $this->sortRule = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["SORTRULE"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["SORTRULE"] : null;
        $this->baseSortRule = $this->sortRule;
        $this->otherSQLRule = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["OTHERSQLRULE"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["OTHERSQLRULE"] : null;
        $this->baseOtherSQLRule = $this->otherSQLRule;
        $this->accessRule = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["ACCESSRULE"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["ACCESSRULE"] : null;
        $this->updateCondition = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["UPDATECONDITION"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["UPDATECONDITION"] : null;
        $this->deleteCondition = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["DELETECONDITION"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["DELETECONDITION"] : null;
        $this->databaseAliasName = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["DBNAME"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["DBNAME"] : null;
        if ($this->databaseAliasName == null) {
            $this->databaseAliasName = "Default";
        }
        $this->mainTableName = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["TABLE"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["TABLE"] : null;
        $this->idGeneration = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["IDGENERATION"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["IDGENERATION"] : null;
        $this->stateless = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["STATELESS"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["STATELESS"] : null;

        // read in uniqueness attribute
        $this->uniqueness = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["UNIQUENESS"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["UNIQUENESS"] : null;

        $this->cacheLifeTime = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["CACHELIFETIME"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["CACHELIFETIME"] : "0";
        $this->objectName = $this->prefixPackage($this->objectName);
        if ($this->inheritFrom == '@sourceMeta') {
            $this->inheritFrom = '@' . $this->objectName;
        } else {
            $this->inheritFrom = $this->prefixPackage($this->inheritFrom);
        }

        // build BizRecord
        $this->bizRecord = new BizRecord($xmlArr["BIZDATAOBJ"]["BIZFIELDLIST"]["BIZFIELD"], "Openbizx\Data\BizField", $this);
        // build TableJoins
        $this->tableJoins = new MetaIterator($xmlArr["BIZDATAOBJ"]["TABLEJOINS"]["JOIN"], "Openbizx\Data\Tools\TableJoin", $this);
        // build ObjReferences
        $this->objReferences = new MetaIterator($xmlArr["BIZDATAOBJ"]["OBJREFERENCES"]["OBJECT"], "Openbizx\Data\Tools\ObjReference", $this);
        // read in parameters
        $this->parameters = new MetaIterator($xmlArr["BIZDATAOBJ"]["PARAMETERS"]["PARAMETER"], "Parameter");

        $this->messageFile = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["MESSAGEFILE"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["MESSAGEFILE"] : null;
        $this->objectMessages = MessageHelper::loadMessage($this->messageFile, $this->package);

        $this->eventManagerName = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["EVENTMANAGER"]) ? $xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["EVENTMANAGER"] : null;

        $this->dataPermControl = isset($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["DATAPERMCONTROL"]) ? strtoupper($xmlArr["BIZDATAOBJ"]["ATTRIBUTES"]["DATAPERMCONTROL"]) : 'N';
    }

    /**
     * Inherit from parent object. Name, Package, Class cannot be inherited
     *
     * @return void
     */
    protected function inheritParentObj()
    {
        if (!$this->inheritFrom) {
            return;
        }
        $parentObj = Openbizx::getObject($this->inheritFrom);

        $this->objectDescription = $this->objectDescription ? $this->objectDescription : $parentObj->objectDescription;
        $this->searchRule = $this->searchRule ? $this->searchRule : $parentObj->searchRule;
        $this->baseSearchRule = $this->searchRule;
        $this->sortRule = $this->sortRule ? $this->sortRule : $parentObj->sortRule;
        $this->baseSortRule = $this->sortRule;
        $this->otherSQLRule = $this->otherSQLRule ? $this->otherSQLRule : $parentObj->otherSQLRule;
        $this->accessRule = $this->accessRule ? $this->accessRule : $parentObj->accessRule;
        $this->updateCondition = $this->updateCondition ? $this->updateCondition : $parentObj->updateCondition;
        $this->deleteCondition = $this->deleteCondition ? $this->deleteCondition : $parentObj->deleteCondition;
        $this->databaseAliasName = $this->databaseAliasName ? $this->databaseAliasName : $parentObj->databaseAliasName;
        $this->mainTableName = $this->mainTableName ? $this->mainTableName : $parentObj->mainTableName;
        $this->idGeneration = $this->idGeneration ? $this->idGeneration : $parentObj->idGeneration;
        $this->stateless = $this->stateless ? $this->stateless : $parentObj->stateless;
        $this->dataPermControl = $this->dataPermControl ? $this->dataPermControl : $parentObj->dataPermControl;
        $this->bizRecord->merge($parentObj->bizRecord);

        foreach ($this->bizRecord as $field) {
            $field->adjustBizObjName($this->objectName);
        }

        $this->tableJoins->merge($parentObj->tableJoins);
        $this->objReferences->merge($parentObj->objReferences);
        $this->parameters->merge($parentObj->parameters);
    }

    /**
     * Get Message
     *
     * @param <type> $msgid message Id
     * @param array $params
     * @return string
     */
    protected function getMessage($msgid, $params = array())
    {
        $message = isset($this->objectMessages[$msgid]) ? $this->objectMessages[$msgid] : constant($msgid);
        //$message = I18n::getInstance()->translate($message);
        $message = I18n::t($message, $msgid, $this->getModuleName($this->objectName));
        return vsprintf($message, $params);
    }

    /**
     * Get session variables
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     */
    public function loadStatefullVars($sessionContext)
    {

    }

    /**
     * Set session variables
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function saveStatefullVars($sessionContext)
    {

    }

    public function events()
    {
        if (!$this->eventManagerName && defined('EVENT_MANAGER')) {
            $this->eventManagerName = EVENT_MANAGER;
        } else {
            $this->eventManager = new EventManager();
        }
        if (!$this->eventManager) {
            $this->eventManager = Openbizx::getObject($this->eventManagerName);
        }
        return $this->eventManager;
    }

    /**
     * Reset rules
     *
     * @return void
     */
    public function resetRules()
    {
        $this->searchRule = $this->baseSearchRule;
        $this->sortRule = $this->baseSortRule;
        $this->otherSQLRule = $this->baseOtherSQLRule;
        return $this;
    }

    /**
     * Clear search rule.
     * Reset the search rule to default search rule set in metadata
     *
     * @return BizDataObj_Abstract
     */
    public function clearSearchRule()
    {
        $this->searchRule = $this->baseSearchRule;
        return $this;
    }

    /**
     * Clear sort rule.
     * Reset the sort rule to default sort rule set in metadata
     *
     * @return void
     */
    public function clearSortRule()  // reset sortrule
    {
        $this->sortRule = $this->baseSortRule;
        return $this;
    }

    /**
     * Clear other SQL rule.
     * Reset the other SQL rule to default other SQL rule set in metadata
     *
     * @return void
     */
    public function clearOtherSQLRule()
    {
        $this->otherSQLRule = $this->baseOtherSQLRule;
        return $this;
    }

    /**
     * Reset all rules (search, sort, other SQL rule)
     *
     * @return void
     */
    public function clearAllRules()
    {
        $this->searchRule = $this->baseSearchRule;
        $this->sortRule = $this->baseSortRule;
        $this->otherSQLRule = $this->baseOtherSQLRule;
        $this->queryLimit = array();
        return $this;
    }

    /**
     * Set search rule as text in sql where clause. i.e. [fieldName] opr Value
     *
     * @param string $rule search rule has format "[fieldName] opr Value"
     * @param boolean $overWrite specify if this rule should overwrite any existing rule
     * @return void
     * */
    public function setSearchRule($rule, $overWrite = false)
    {
        if (!$rule || $rule == "")
            return;
        if (!$this->searchRule || $overWrite == true) {
            $this->searchRule = $rule;
        } elseif (strpos($this->searchRule, $rule) === false) {
            $this->searchRule .= " AND " . $rule;
        }
    }

    /**
     * Set query parameter for parameter binding in the query
     *
     * @param array {fieldname:value} list
     * @return void
     * */
    public function setQueryParameters($paramValues)
    {
        foreach ($paramValues as $param => $value)
            $this->queryParams[$param] = $value;
    }

    public function getQueryParameters()
    {
        return $this->queryParams;
    }

    /**
     * Set search rule as text in sql order by clause. i.e. [fieldName] DESC|ASC
     *
     * @param string $rule sort rule has format "[fieldName] DESC|ASC"
     * @return void
     * */
    public function setSortRule($rule)
    {
        // sort rule has format "[fieldName] DESC|ASC", replace [fieldName] with table.column
        $this->sortRule = $rule;
    }

    /**
     * Set other SQL rule, append extra SQL statment in sql. i.e. GROUP BY [fieldName]
     *
     * @param string $rule search rule with SQL format "GROUP BY [fieldName] HAVING ..."
     * @return void
     * */
    public function setOtherSQLRule($rule)
    {
        // $rule has SQL format "GROUP BY [fieldName] HAVING ...". replace [fieldName] with table.column
        $this->otherSQLRule = $rule;
    }

    /**
     * Set limit of the query.
     *
     * @param int $count the number of records to return
     * @param int $offset the starting position of the result records
     * @return void
     */
    public function setLimit($count, $offset = 0)
    {
        if ($count < 0) {
            $count = 0;
        }
        if ($offset < 0) {
            $offset = 0;
        }
        $this->queryLimit['count'] = $count;
        $this->queryLimit['offset'] = $offset;
    }

    /**
     * Get database connection
     *
     * @return \Zend_Db_Adapter_Abstract
     * */
    public function getDBConnection($type = 'default')
    {
        switch (strtolower($type)) {
            case "default":
            case "read":
                if (isset($this->databaseAliasNameforRead)) {
                    $dbName = $this->databaseAliasNameforRead;
                } else {
                    $dbName = $this->databaseAliasName;
                }
                break;
            case "write":
                if (isset($this->databaseAliasNameforWrite)) {
                    $dbName = $this->databaseAliasNameforWrite;
                } else {
                    $dbName = $this->databaseAliasName;
                }
                break;
        }
        return Openbizx::$app->getDbConnection($dbName);
    }

    /**
     * Get the property of the object. Used in expression language
     * 
     * @param string $propertyName name of the property
     * @return BizField|string property value
     */
    public function getProperty($propertyName)
    {
        $ret = parent::getProperty($propertyName);
        if ($ret)
            return $ret;
        if ($propertyName == "Table")
            return $this->table;
        if ($propertyName == "SearchRule")
            return $this->searchRule;
        // get control object if propertyName is "Field[fldname]"
        $pos1 = strpos($propertyName, "[");
        $pos2 = strpos($propertyName, "]");
        if ($pos1 > 0 && $pos2 > $pos1) {
            $propType = substr($propertyName, 0, $pos1);
            $fieldName = substr($propertyName, $pos1 + 1, $pos2 - $pos1 - 1);
            if ($propType == "param") {   // get parameter
                return $this->parameters->get($fieldName);
            }
            return $this->getField($fieldName);
        }
    }

    /**
     * Get object parameter value
     *
     * @param string $paramName name of the parameter
     * @return string parameter value
     */
    public function getParameter($paramName)
    {
        return $this->parameters[$paramName]->value;
    }

    /**
     * Get the object instance defined in the object reference
     *
     * @param string $objName the object name list in the ObjectReference part
     * @return BizDataObj object instance
     */
    public function getRefObject($objName)
    {
        // see if there is such object in the ObjReferences
        $objRef = $this->objReferences->get($objName);
        if (!$objRef)
            return null;

        // apply association on the object
        // $assc = $this->EvaluateExpression($objRef->association);
        // get the object instance
        $obj = Openbizx::getObject($objName);
        $obj->setAssociation($objRef, $this);
        return $obj;
    }

    /**
     * Get the Association (array)
     *
     * @return array array of association
     * */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * Set the association of the object
     *
     * @param ObjReference $objRef
     * @param BizDataObj $asscObj
     * @return void
     */
    protected function setAssociation($objRef, $asscObj)
    {
        $this->association["AsscObjName"] = $asscObj->objectName;
        $this->association["Relationship"] = $objRef->relationship;
        $this->association["Table"] = $objRef->table;
        $this->association["Column"] = $objRef->column;
        $this->association["FieldRef"] = $objRef->fieldRef;
        $this->association["FieldRefVal"] = $asscObj->getFieldValue($objRef->fieldRef);
        $this->association["CondColumn"] = $objRef->condColumn;
        $this->association["CondValue"] = $objRef->condValue;
        $this->association["Condition"] = $objRef->condition;
        if ($objRef->relationship == "M-M" || $objRef->relationship == "Self-Self") {
            $this->association["XTable"] = $objRef->xTable;
            $this->association["XColumn1"] = $objRef->xColumn1;
            $this->association["XColumn2"] = $objRef->xColumn2;
            $this->association["XKeyColumn"] = $objRef->xKeyColumn;
            $this->association["XDataObj"] = $objRef->xDataObj;
        }
    }

    /**
     * Create an new (empty) record
     *
     * @return array - empty record array with default values
     * */
    abstract public function newRecord();

    /**
     * Insert record using given input record array
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     * */
    abstract public function insertRecord($recArr);

    /**
     * Update record using given input record array
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @param array $oldRec - associated array who is the old record field name / value pairs
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     * */
    abstract public function updateRecord($recArr, $oldRec = null);

    /**
     * Delete current record or delete the given input record
     *
     * @param array $recArr - associated array whose keys are field names of this BizDataObj
     * @return boolean - if return false, the caller can call GetErrorMessage to get the error.
     * */
    abstract public function deleteRecord($recArr);

    /**
     * Fetches SQL result rows as a sequential array according the query rules set before.
     * Sample code:
     * <pre>
     *   $do->resetRules();
     *   $do->setSearchRule($search_rule1);
     *   $do->setSearchRule($search_rule2);
     *   $do->setSortRule($sort_rule);
     *   $do->SetOtherRule($groupby);
     *   $total = $do->count();
     *   $do->setLimit($count, $offset=0);
     *   $recordSet = $do->fetch();
     * </pre>
     *
     * @return array array of records
     */
    abstract public function fetch();

    /**
     * Fetches SQL result rows as a sequential array without using query rules set before.
     * Sample code:
     * <pre>
     *   // fetch all record with firstname starting with Mike
     *   $do->directFetch("[FirstName] LIKE 'Mike%'");
     *   // fetch first 10 records with firstname starting with Mike
     *   $do->directFetch("[FirstName] LIKE 'Mike%'", 10);
     *   // fetch 20th-30th records with firstname starting with Mike
     *   $do->directFetch("[FirstName] LIKE 'Mike%'", 10, 20);
     * </pre>
     *
     * @param string $searchRule the search rule string
     * @param int $count number of records to return
     * @param int $offset the starting point of the return records
     * @return array array of records
     */
    abstract public function directFetch($searchRule = "", $count = -1, $offset = 0);

    /**
     * Do the search query and return results set as PDOStatement.
     * Sample code:
     * <pre>
     *   $do->resetRules();
     *   $do->setSearchRule($search_rule1);
     *   $do->setSearchRule($search_rule2);
     *   $do->setSortRule($sort_rule);
     *   $do->SetOtherRule($groupby);
     *   $total = $do->count();
     *   $do->setLimit($count, $offset=0);
     *   $resultSet = $do->find();
     *   $do->getDBConnection()->setFetchMode(PDO::FETCH_ASSOC);
     *   while ($record = $resultSet->fetch())
     *   {
     *       print_r($record);
     *   }
     * </pre>
     *
     * @return PDOStatement PDO statement object
     */
    abstract public function find();

    /**
     * Count the number of record according to the search results set before.
     * it ignores limit setting
     *
     * @return int number of records
     */
    abstract public function count();
}
