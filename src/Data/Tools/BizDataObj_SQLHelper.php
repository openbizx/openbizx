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
 * @version   $Id: BizDataObj_SQLHelper.php 3994 2011-04-28 12:39:54Z jixian2003 $
 */

namespace Openbizx\Data\Tools;

use Openbizx\Openbizx;
use Openbizx\Data\BizDataSql;
use Openbizx\Core\Expression;

/**
 * Class BizDataObj_SQLHelper takes care of building sql for BizDataObj
 * BizDataObj_SQLHelper is singleton object
 *
 * @package openbiz.bin.data.private
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 */
class BizDataObj_SQLHelper
{

    /**
     *
     * @var BizDataSql
     */
    private $_dataSqlObj = null;
    private $_dataSqlObjList;

    /**
     *
     * @var BizDataObj_SQLHelper
     */
    private static $_instance = null;
    private $_doRuleCache;

    /**
     * Get the singleton instance
     *
     * @return BizDataObj_SQLHelper BizDataObj_SQLHelper object
     */
    public static function instance()
    {
        if (self::$_instance == null)
            self::$_instance = new BizDataObj_SQLHelper();
        return self::$_instance;
    }

    /**
     * Get curent BizDataSql object, if object=null, create new object
     *
     * @return BizDataSql
     */
    protected function getDataSqlObj()
    {
        if (!$this->_dataSqlObj)
            $this->_dataSqlObj = new BizDataSql();
        return $this->_dataSqlObj;
    }

    /**
     * Get new BizDataSql object and store on internal variable (current object)
     *
     * @return BizDataSql
     */
    protected function getNewDataSqlObj($dataObj = null)
    {
        if ($dataObj) {
            $doName = $dataObj->objectName;
            if (!isset($this->_dataSqlObjList[$doName])) {
                $this->_dataSqlObjList[$doName] = new BizDataSql();
            }
            $this->_dataSqlObj = $this->_dataSqlObjList[$doName];
        } else {
            $this->_dataSqlObj = null;
            $this->_dataSqlObj = new BizDataSql();
        }
        return $this->_dataSqlObj;
    }

    /**
     * Build the Select SQL statement based on the fields and search/sort rule
     *
     * @param BizDataObj $dataObj
     * @return void
     */
    public function buildQuerySQL($dataObj)
    {
        //echo "buildQuerySQL ".$dataObj->objectName."\n";
        // TODO: the same dataobj re-uses the same datasqlobj ...
        // build the SQL statement based on the fields and search rule
        $dataSqlObj = $this->getNewDataSqlObj($dataObj);

        if ($dataSqlObj->isfresh()) {
            // add table
            $dataSqlObj->addMainTable($dataObj->mainTableName);
            // add join table
            if ($dataObj->tableJoins) {
                foreach ($dataObj->tableJoins as $tableJoin) {
                    $tbl_col = $dataSqlObj->addJoinTable($tableJoin, $this);
                }
            }
            // add columns
            foreach ($dataObj->bizRecord as $bizField) {
                if ($bizField->ignoreInQuery) // field to be ignore in query - save memory
                    continue;
                if ($bizField->column && $bizField->type == "Blob")   // ignore blob column
                    continue;
                if ($bizField->column && !$bizField->sqlExpression && (strpos($bizField->column, ',') == 0))
                    $dataSqlObj->addTableColumn($bizField->join, $bizField->column, $bizField->aliasName);
                if ($bizField->sqlExpression) {
                    $dataSqlObj->addSqlExpression($this->_convertSqlExpression($dataObj, $bizField->sqlExpression), $bizField->aliasName);
                }
            }
        }
        $dataSqlObj->resetSQL();

        // append DataPerm in the WHERE clause
        if ($dataObj->dataPermControl == 'Y') {
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            $hasOwnerField = $this->_hasOwnerField($dataObj);
            $dataPermSQLRule = $svcObj->buildSqlRule($dataObj, 'select', $hasOwnerField);
            $sqlSearchRule = $this->_ruleToSql($dataObj, $dataPermSQLRule);
            $dataSqlObj->addSqlWhere($sqlSearchRule);
        }

        // append QueryPameters in the WHERE clause
        foreach ($dataObj->getQueryParameters() as $fieldName => $value) {
            $queryRule = queryParamToRule($fieldName, $value, $dataObj);
            $sqlSearchRule = $this->_ruleToSql($dataObj, $queryRule);
            $dataSqlObj->addSqlWhere($sqlSearchRule);
        }

        // append SearchRule in the WHERE clause
        $sqlSearchRule = $this->_ruleToSql($dataObj, $dataObj->searchRule);
        $dataSqlObj->addSqlWhere($sqlSearchRule);

        // append SearchRule in the ORDER BY clause
        $sqlSortRule = $this->_ruleToSql($dataObj, $dataObj->sortRule);
        $dataSqlObj->addOrderBy($sqlSortRule);

        // append SearchRule in the other SQL clause
        $sqlOtherSQLRule = $this->_ruleToSql($dataObj, $dataObj->otherSQLRule);
        $dataSqlObj->addOtherSQL($sqlOtherSQLRule);

        // append SearchRule in the AccessRule clause
        $sqlAccessSQLRule = $this->_ruleToSql($dataObj, $dataObj->accessRule);
        $dataSqlObj->addSqlWhere($sqlAccessSQLRule);

        // add association to SQL
        if ($dataObj->association["AsscObjName"] != "" && $dataObj->association["FieldRefVal"] == "") {
            $asscObj = Openbizx::getObject($dataObj->association["AsscObjName"]);
            $dataObj->association["FieldRefVal"] = $asscObj->getFieldValue($dataObj->association["FieldRef"]);
        }

        if ($dataObj->association["AsscObjName"] != "" && $dataObj->association["FieldRefVal2"] == "") {
            $asscObj = Openbizx::getObject($dataObj->association["AsscObjName"]);
            $dataObj->association["FieldRefVal2"] = $asscObj->getFieldValue($dataObj->association["FieldRef2"]);
        }

        if ($dataObj->association["Relationship"] == "Self-Self") {
            $dataObj->association["ParentRecordIdColumn"] = $dataObj->getField("Id")->column;
        }
        $dataSqlObj->addAssociation($dataObj->association);

        // apply _ruleToSql to JoinCondition if any
        if ($dataSqlObj->hasJoinCondition) {
            $dataSqlObj->setTableJoinStm($this->_ruleToSql($dataObj, $dataSqlObj->getTableJoinStm()));
        }

        $querySQL = $dataSqlObj->getSqlStatement() . " ";

        //echo $querySQL."###\n";
        return $querySQL;
    }

    /**
     * Build update sql
     * UPDATE table SET col1=val1, col2=val2 ...
     * WHERE idcol1='id1' AND idcol2='id2'
     *
     * @param BizDataObj $dataObj
     * @return mixed
     * @todo consider the record data on main table as well as join table, this function can return a sql array.
     * */
    public function buildUpdateSQL($dataObj)
    {
        // generate column value pairs. ignore those whose inputValue=fieldValue
        $sqlFlds = $dataObj->bizRecord->getToSaveFields('UPDATE');
        $colval_pairs = null;
        foreach ($sqlFlds as $field) {
            $col = $field->column;

            // ignore empty vallue for Date or Datetime
            if (($field->value == "" && $field->oldValue == "") && ($field->type == "Date" || $field->type == "Datetime"))
                continue;

            if ($field->valueOnUpdate != "") // ignore ValueOnUpdate field first
                continue;

            if ($field->isLobField())  // take care of blob/clob type later
                continue;

            // ignore the column where old value is same as new value; set the column only if new value is diff than the old value
            if ($field->oldValue == $field->value)
                continue;

            $_val = $field->getSqlValue();
            $colval_pairs[$col] = $_val; //($_val===null || $_val === '') ? "''" : $_val;
        }
        if ($colval_pairs == null)
            return false;

        // take care value on update fields only
        foreach ($sqlFlds as $field) {
            $col = $field->column;
            if ($field->valueOnUpdate != "") {
                $_val = $field->getValueOnUpdate();
                $colval_pairs[$col] = $_val; //($_val===null || $_val === '') ? "''" : $_val;
            }
        }

        $db = $dataObj->getDBConnection('WRITE');
        $sql = "";
        foreach ($colval_pairs as $col => $val) {
            //$queryString = QueryStringParam::formatQueryString("`$col`", "=", $val);
            $queryString = "`$col`=" . $db->quote($val);
            if ($sql != "") {
                $sql .= ", $queryString";
            } else {
                $sql .= $queryString;
            }
        }

        $sql = "UPDATE `" . $dataObj->mainTableName . "` SET " . $sql;

        $whereStr = $dataObj->bizRecord->getKeySearchRule(true, true);  // use old value and column name
        $sql .= " WHERE " . $whereStr;

        // append DataPerm in the WHERE clause
        if ($dataObj->dataPermControl == 'Y') {
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            $hasOwnerField = $this->_hasOwnerField($dataObj);
            $dataPermSQLRule = $svcObj->buildSqlRule($dataObj, 'update', $hasOwnerField);
            $sqlSearchRule = $this->_convertSqlExpressionWithoutPrefix($dataObj, $dataPermSQLRule);
            if ($whereStr != '') {
                $sql .= ' AND ' . $sqlSearchRule;
            } else {
                $sql .= $sqlSearchRule;
            }
        }
        return $sql;
    }

    public function buildUpdateSQLwithCondition($dataObj, $setValue, $condition = null)
    {

        $setValueStr = $this->_convertSqlExpressionWithoutPrefix($dataObj, $setValue);
        $sql = "UPDATE `" . $dataObj->mainTableName . "` SET " . $setValueStr;
        if ($condition) {
            $whereStr = $this->_convertSqlExpressionWithoutPrefix($dataObj, $condition);
            $sql .= " WHERE " . $whereStr;
        }

        // append DataPerm in the WHERE clause
        if ($dataObj->dataPermControl == 'Y') {
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            $hasOwnerField = $this->_hasOwnerField($dataObj);
            $dataPermSQLRule = $svcObj->buildSqlRule($dataObj, 'update', $hasOwnerField);
            $sqlSearchRule = $this->_convertSqlExpressionWithoutPrefix($dataObj, $dataPermSQLRule);
            if ($whereStr != '') {
                $sql .= ' AND ' . $sqlSearchRule;
            } else {
                $sql .= $sqlSearchRule;
            }
        }
        return $sql;
    }

    /**
     * Build delete-sql DELETE FROM table WHERE idcol1='id1' AND idcol2='id2'
     *
     * @param BizDataObj $dataObj
     * @return string SQL statement
     */
    public function buildDeleteSQL($dataObj)
    {
        $sql = "DELETE FROM `" . $dataObj->mainTableName . "`";
        $whereStr = $dataObj->bizRecord->getKeySearchRule(false, true);  // use cur value and column name
        $sql .= " WHERE " . $whereStr;
        // append DataPerm in the WHERE clause
        if ($dataObj->dataPermControl == 'Y') {
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            $hasOwnerField = $this->_hasOwnerField($dataObj);
            $dataPermSQLRule = $svcObj->buildSqlRule($dataObj, 'delete', $hasOwnerField);
            $sqlSearchRule = $this->_convertSqlExpressionWithoutPrefix($dataObj, $dataPermSQLRule);
            if ($whereStr != '') {
                $sql .= ' AND ' . $sqlSearchRule;
            } else {
                $sql .= $sqlSearchRule;
            }
        }
        return $sql;
    }

    public function buildDeleteSQLwithCondition($dataObj, $condition = null)
    {

        $sql = "DELETE FROM `" . $dataObj->mainTableName . "`";
        if ($condition) {
            $whereStr = $this->_convertSqlExpressionWithoutPrefix($dataObj, $condition);
            $sql .= " WHERE " . $whereStr;
        }
        // append DataPerm in the WHERE clause
        if ($dataObj->dataPermControl == 'Y') {
            $svcObj = Openbizx::getService(OPENBIZ_DATAPERM_SERVICE);
            $hasOwnerField = $this->_hasOwnerField($dataObj);
            $dataPermSQLRule = $svcObj->buildSqlRule($dataObj, 'delete', $hasOwnerField);
            $sqlSearchRule = $this->_convertSqlExpressionWithoutPrefix($dataObj, $dataPermSQLRule);
            if ($whereStr != '') {
                $sql .= ' AND ' . $sqlSearchRule;
            } else {
                $sql .= $sqlSearchRule;
            }
        }
        return $sql;
    }

    /**
     * Build insert-sql
     * INSERT INTO table_name (column1, column2,...) VALUES (value1, value2,....)
     *
     * @param BizDataObj $dataObj
     * @param array $joinValues array of join values
     * @return string Insert-SQL statement
     */
    public function buildInsertSQL($dataObj, $joinValues = null)
    {
        // generate column value pairs.
        $sqlFields = $dataObj->bizRecord->getToSaveFields('CREATE');

        $dbInfo = Openbizx::$app->getConfiguration()->getDatabaseInfo($dataObj->databaseAliasName);
        $dbType = $dbInfo["Driver"];

        $sql_col = "";
        $sql_val = "";
        $db = $dataObj->getDBConnection('WRITE');

        /* @var $field BizField */
        foreach ($sqlFields as $field) {
            $col = $field->column;

            // if Field Id has null value and Id is an identity type, remove the Id's column from the array
            if ($field->objectName == "Id" && $dataObj->idGeneration == "Identity") {
                continue;
            }

            if ($field->isLobField()) {  // special value for blob/clob type
                $_val = $field->getInsertLobValue($dbType);
            } else {
                $_val = $field->getSqlValue();
                if ($_val == '' && $field->valueOnCreate != "") {
                    $_val = $field->getValueOnCreate();
                }
            }

            //if (!$_val || $_val == '') continue;
            // modified by jixian for not ignore 0 value
            if ($_val === '') {
                continue;
            }
            $sql_col .= "`" . $col . "`, ";
            $sql_val .= $db->quote($_val) . ", ";
            //$sql_val .= QueryStringParam::formatQueryValue($_val). ", ";
        }

        // if joinValues is given then add join values in to the main table InsertSQL.
        if (is_array($joinValues)) {
            foreach ($joinValues as $joinColumn => $joinValue) {
                if (!$joinValue || $joinValue == '') {
                    continue;
                }
                $sql_col .= "`" . $joinColumn . "`, ";
                $sql_val .= "'" . $joinValue . "', ";
            }
        }

        $sql_col = substr($sql_col, 0, -2);
        $sql_val = substr($sql_val, 0, -2);

        $sql = "INSERT INTO  `" . $dataObj->mainTableName . "` (" . $sql_col . ") VALUES (" . $sql_val . ")";
        return $sql;
    }

    public function ruleToSql($dataObj, $rule)
    {
        return _ruleToSql($dataObj, $rule);
    }

    /**
     * Convert search/sort rule to sql clause, replace [fieldName] with table.column
     * openbiz SQL expression as :
     * "[fieldName] opr 'Value' AND/OR [fieldName] opr 'Value'...". "()" is valid syntax
     *
     * @param BizDataObj $dataObj
     * @param string $rule "[fieldName] ..."
     * @return string sql statement
     * */
    private function _ruleToSql($dataObj, $rule)
    {
        $cacheKey = $dataObj->objectName . "-" . $rule;
        if (isset($this->_doRuleCache[$cacheKey])) {
            return $this->_doRuleCache[$cacheKey];
        }
        //echo " _ruleToSql ".$dataObj->objectName." ". $rule."\n";
        $dataSqlObj = $this->getDataSqlObj();

        $rule = Expression::evaluateExpression($rule, $dataObj);

        // replace all [field] with table.column
        // use regexp to find [] parts and replace with fields
        if (!preg_match_all("/\[([0-9a-zA-Z_]+)\]/", $rule, $m)) {
            $this->_doRuleCache[$cacheKey] = $rule;
            return $rule;
        }
        foreach ($m[1] as $fld) {
            $bizField = $dataObj->getField($fld);
            if (!$bizField)
                continue;
            $fld_pattern = "[" . $bizField->objectName . "]";
            if ($bizField->column && (strpos($bizField->column, ',') != 0)) {  // handle composite key.
                if (!preg_match('/\[' . $bizField->objectName . '\].*=.*\'(.+)\'/', $rule, $matches))
                    continue; //print_r($matches);
                $keyval = $matches[1];
                // replace the matching part [compkey field]='value'
                $compkey_value = $this->_compKeyRuleToSql($bizField->column, $keyval);
                $rule = $str_replace($matches[0], $compkey_value, $rule);
            }
            else {
                if ($bizField->aliasName) {
                    $rule = str_replace($fld_pattern, $bizField->aliasName, $rule);
                } elseif ($bizField->sqlExpression) {
                    $rule = str_replace($fld_pattern, $bizField->sqlExpression, $rule);
                } else {
                    $tableColumn = $dataSqlObj->getTableColumn($bizField->join, $bizField->column);
                    $rule = str_replace($fld_pattern, $tableColumn, $rule);
                }
            }
        }

        $this->_doRuleCache[$cacheKey] = $rule;
        return $rule;
    }

    //TODO: refactor:rename, what's mean of comp?
    /**
     *
     * @param string $compColumn
     * @param string $compValue
     * @return string SQL rule statement
     */
    private function _compKeyRuleToSql($compColumn, $compValue)
    {
        $dataSqlObj = $this->getDataSqlObj();
        $colArr = explode(",", $compColumn);
        $valArr = explode(CK_CONNECTOR, $compValue);
        $sql = "";
        for ($i = 0; $i < count($colArr); $i++) {
            if ($i > 0)
                $sql .= "and";
            $tableColumn = $dataSqlObj->getTableColumn("", $colArr[$i]);
            if ($valArr[$i] == '')
                $sql .= " ($tableColumn = '" . $valArr[$i] . "' OR $tableColumn is null) ";
            else
                $sql .= " $tableColumn = '" . $valArr[$i] . "' ";
        }
        return $sql;
    }

    /**
     * Convert Sql Expression
     * Replace [field name] in the SQL expression with table_alias.column
     *
     * @param BizDataObj $dataObj - the instance of BizDataObj
     * @param string $sqlExpr - SQL expression supported by the database engine. The syntax is FUNC([FieldName1]...[FieldName2]...)
     * @return string real sql expression with column names
     * */
    private function _convertSqlExpression($dataObj, $sqlExpr)
    {
        $dataSqlObj = $this->getDataSqlObj();
        $sqlstr = $sqlExpr;
        $startpos = 0;
        while (true) {
            $fieldname = substr_lr($sqlstr, "[", "]", $startpos);
            if ($fieldname == "")
                break;
            else {
                $bizField = $dataObj->bizRecord->get($fieldname);
                $tableColumn = $dataSqlObj->getTableColumn($bizField->join, $bizField->column);
                $sqlstr = str_replace("[$fieldname]", $tableColumn, $sqlstr);
                $startpos = strpos($sqlstr, '['); // Move startpos to the first [ (if it exists) in order to be detect by next itteration
            }
        }
        return $sqlstr;
    }

    private function _convertSqlExpressionWithoutPrefix($dataObj, $sqlExpr)
    {
        $dataSqlObj = $this->getDataSqlObj();
        $sqlstr = $sqlExpr;
        $startpos = 0;
        while (true) {
            $fieldname = substr_lr($sqlstr, "[", "]", $startpos);
            if ($fieldname == "")
                break;
            else {
                $bizField = $dataObj->bizRecord->get($fieldname);
                $tableColumn = "`" . $bizField->column . "`";
                $sqlstr = str_replace("[$fieldname]", $tableColumn, $sqlstr);
                $startpos = strpos($sqlstr, '['); // Move startpos to the first [ (if it exists) in order to be detect by next itteration
            }
        }
        return $sqlstr;
    }

    private function _hasOwnerField($dataObj)
    {
        $fld = $dataObj->getField('owner_id');
        if ($fld) {
            return true;
        } else {
            return false;
        }
    }

}

/**
 * Convert the user input on a given fieldcontrol in query mode to search rule
 *
 * @param string $fieldName - field name
 * @param string $value - field value
 * @param EasyForm $formObj
 * @return string - searchRule
 */
function queryParamToRule($fieldName, $value, $dataObj)
{
    // todo: should check single quote for nonoperators clauses
    // find locations for all sql key words
    // search for starting ' and closing ' pair, check if sql key word in the pair

    $val = trim($value);
    // unformat the data
    $bizField = $dataObj->getField($fieldName);
    $val = Openbizx::$app->getTypeManager()->formattedStringToValue($bizField->type, $bizField->format, $val);
    // check " AND ", " OR "
    if (($pos = strpos($val, " AND ")) !== false) {
        $inputArr = explode(" AND ", $val);
        $retStr = null;
        foreach ($inputArr as $v)
            $retStr .= ($retStr) ? " AND " . inputValToRule($fieldName, $v, $formObj) : inputValToRule($fieldName, $v, $formObj);
        return $retStr;
    } else if (($pos = strpos($val, " OR ")) !== false) {
        $inputArr = explode(" OR ", $val);
        $retStr = null;
        foreach ($inputArr as $v)
            $retStr .= ($retStr) ? " OR " . inputValToRule($fieldName, $v, $formObj) : inputValToRule($fieldName, $v, $formObj);
        return "(" . $retStr . ")";
    }

    // check >=, >, <=, <, =
    if (($pos = strpos($val, "<>")) !== false || ($pos = strpos($val, "!=")) !== false) {
        $opr = "<>";
        $oprlen = 2;
    } else if (($pos = strpos($val, ">=")) !== false) {
        $opr = ">=";
        $oprlen = 2;
    } else if (($pos = strpos($val, ">")) !== false) {
        $opr = ">";
        $oprlen = 1;
    } else if (($pos = strpos($val, "<=")) !== false) {
        $opr = "<=";
        $oprlen = 2;
    } else if (($pos = strpos($val, "<")) !== false) {
        $opr = "<";
        $oprlen = 1;
    } else if (($pos = strpos($val, "=")) !== false) {
        $opr = "=";
        $oprlen = 1;
    }
    if ($opr) {
        $val = trim(substr($val, $pos + $oprlen));
    }

    if (strpos($val, "*") !== false) {
        $opr = "LIKE";
        $val = str_replace("*", "%", $val);
    }
    //if (strpos($val, "'") !== false) {   // not needed since addslashes() is called before
    //   $val = str_replace("'", "\\'", $val);
    //}
    if (!$opr)
        $opr = "=";

    // set the query param
    //$queryString = QueryStringParam::formatQueryString("[$fieldName]", $opr, $realValue);
    //return $queryString;
    $db = $dataObj->getDBConnection("READ");
    return "[" . $fieldName . "] " . $opr . $db->quote($val);
}

/**
 * substr_lr() - help function (helper).
 * Get the sub string whose left and right boundary character is $left and $right.
 * The search is in $str, starting from position of $startpos.
 * If $findfirst is true, $left must be the charater on the $startpos.
 *
 * @return string
 * */
function substr_lr(&$str, $left, $right, &$startpos, $findfirst = false)
{
    $pos0 = strpos($str, $left, $startpos);
    if ($pos0 === false)
        return false;
    $tmp = trim(substr($str, $startpos, $pos0 - $startpos));
    if ($findfirst && $tmp != "")
        return false;

    $posleft = $pos0 + strlen($left);
    while (true) {
        $pos1 = strpos($str, $right, $posleft);
        if ($pos1 === false) {
            if (trim($right) == "") {
                $pos1 = strlen($str); // if right is whitespace
                break;
            } else
                return false;
        }
        else {   // avoid \$right is found
            if (substr($str, $pos1 - 1, 1) == "\\")
                $posleft = $pos1 + 1;
            else
                break;
        }
    }

    $startpos = $pos1 + strlen($right);
    $retStr = substr($str, $pos0 + strlen($left), $pos1 - $pos0 - strlen($left));
    return $retStr;
}

?>