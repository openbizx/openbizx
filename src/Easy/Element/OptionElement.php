<?PHP

/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.easy.element
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: OptionElement.php 3561 2011-03-30 06:15:47Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;
use Openbizx\I18n\I18n;
use Openbizx\Data\Helpers\QueryStringParam;
use Openbizx\Object\ObjectFactoryHelper;
use Openbizx\Easy\Element\InputElement;

/**
 * OptionElement is the base class of element that render list (from Selection.xml)
 * Used by :
 *   - {@link AutoSuggest}
 *   - {@link Checkbox}
 *   - {@link ColumnList}
 *   - {@link EditCombobox}
 *   - {@link LabelList}
 *   - {@link Listbox}
 *
 * @package openbiz.bin.easy.element
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class OptionElement extends InputElement
{

    public $selectFrom;
    public $selectFromSQL;
    public $selectedList;

    /**
     * Read metadata info from metadata array and store to class variable
     *
     * @param array $xmlArr metadata array
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->selectFrom = isset($xmlArr["ATTRIBUTES"]["SELECTFROM"]) ? $xmlArr["ATTRIBUTES"]["SELECTFROM"] : null;
        $this->selectedList = isset($xmlArr["ATTRIBUTES"]["SELECTEDLIST"]) ? $xmlArr["ATTRIBUTES"]["SELECTEDLIST"] : null;
        $this->selectFromSQL = isset($xmlArr["ATTRIBUTES"]["SELECTFROMSQL"]) ? $xmlArr["ATTRIBUTES"]["SELECTFROMSQL"] : null;
    }

    /**
     * Get select from
     *
     * @return string
     */
    protected function getSelectFrom()
    {
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->selectFrom, $formobj);
    }

    protected function getSelectedList()
    {
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->selectedList, $formobj);
    }

    protected function getSelectFromSQL()
    {
        $formobj = $this->getFormObj();
        return Expression::evaluateExpression($this->selectFromSQL, $formobj);
    }

    /**
     * Render, draw the control according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        return "";
    }

    /**
     * Get from list
     *
     * @param array $list
     * @return void
     */
    public function getFromList(&$list, $selectFrom = null)
    {
        if (!$selectFrom) {
            $selectFrom = $this->getSelectFrom();
        }
        if (!$selectFrom) {
            return $this->getSQLFromList($list);
        }
        $this->getXMLFromList($list, $selectFrom);
        if ($list != null)
            return;
        $this->getDOFromList($list, $selectFrom);
        if ($list != null)
            return;
        $this->getSimpleFromList($list, $selectFrom);
        if ($list != null)
            return;

        return;
    }

    protected function getXMLFromList(&$list, $selectFrom)
    {
        $pos0 = strpos($selectFrom, "(");
        $pos1 = strpos($selectFrom, ")");
        if ($pos0 > 0 && $pos1 > $pos0) {  // select from xml file
            $xmlFile = substr($selectFrom, 0, $pos0);
            $tag = substr($selectFrom, $pos0 + 1, $pos1 - $pos0 - 1);
            $tag = strtoupper($tag);
            $xmlFile = ObjectFactoryHelper::getXmlFileWithPath($xmlFile);
            if (!$xmlFile) {
                return false;
            }

            $xmlArr = &ObjectFactoryHelper::getXmlArray($xmlFile);
            if ($xmlArr) {
                $i = 0;
                if (!isset($xmlArr["SELECTION"][$tag])) {
                    return false;
                }
                if (!$xmlArr["SELECTION"][$tag][0]) {
                    $array = $xmlArr["SELECTION"][$tag];
                    unset($xmlArr["SELECTION"][$tag]);
                    $xmlArr["SELECTION"][$tag][0] = $array;
                }
                foreach ($xmlArr["SELECTION"][$tag] as $node) {
                    $list[$i]['val'] = $node["ATTRIBUTES"]["VALUE"];
                    $list[$i]['pic'] = $node["ATTRIBUTES"]["PICTURE"];
                    if ($node["ATTRIBUTES"]["TEXT"]) {
                        $list[$i]['txt'] = $node["ATTRIBUTES"]["TEXT"];
                    } else {
                        $list[$i]['txt'] = $list[$i]['val'];
                    }
                    $i++;
                }
                $this->translateList($list, $tag); // supprot multi-language
            }
            return true;
        }
        return false;
    }

    protected function getDOFromList(&$list, $selectFrom)
    {
        $pos0 = strpos($selectFrom, "[");
        $pos1 = strpos($selectFrom, "]");
        if ($pos0 > 0 && $pos1 > $pos0) {  // select from bizObj
            // support BizObjName[BizFieldName] or 
            // BizObjName[BizFieldName4Text:BizFieldName4Value] or 
            // BizObjName[BizFieldName4Text:BizFieldName4Value:BizFieldName4Pic]
            $bizObjName = substr($selectFrom, 0, $pos0);
            $pos3 = strpos($selectFrom, ":");
            if ($pos3 > $pos0 && $pos3 < $pos1) {
                $fieldName = substr($selectFrom, $pos0 + 1, $pos3 - $pos0 - 1);
                $fieldName_v = substr($selectFrom, $pos3 + 1, $pos1 - $pos3 - 1);
            } else {
                $fieldName = substr($selectFrom, $pos0 + 1, $pos1 - $pos0 - 1);
                $fieldName_v = $fieldName;
            }
            $pos4 = strpos($fieldName_v, ":");
            if ($pos4) {
                $fieldName_v_mixed = $fieldName_v;
                $fieldName_v = substr($fieldName_v_mixed, 0, $pos4);
                $fieldName_p = substr($fieldName_v_mixed, $pos4 + 1, strlen($fieldName_v_mixed) - $pos4 - 1);
                unset($fieldName_v_mixed);
            }
            $commaPos = strpos($selectFrom, ",", $pos1);
            if ($commaPos > $pos1) {
                $searchRule = trim(substr($selectFrom, $commaPos + 1));
            }

            /* @var $bizObj BizDataObj */
            $bizObj = Openbizx::getObject($bizObjName);
            if (!$bizObj) {
                return false;
            }

            $recList = array();
            $oldAssoc = $bizObj->association;
            $bizObj->association = null;
            QueryStringParam::reset();
            $recList = $bizObj->directFetch($searchRule);
            $bizObj->association = $oldAssoc;

            foreach ($recList as $rec) {
                $list[$i]['val'] = $rec[$fieldName_v];
                $list[$i]['txt'] = $rec[$fieldName];
                $list[$i]['pic'] = $rec[$fieldName_p];
                $i++;
            }

            return true;
        }
        return false;
    }

    protected function getSimpleFromList(&$list, $selectFrom)
    {
        // in case of a|b|c
        if (strpos($selectFrom, "[") > 0 || strpos($selectFrom, "(") > 0) {
            return;
        }
        $recList = explode('|', $selectFrom);
        foreach ($recList as $rec) {
            $list[$i]['val'] = $rec;
            $list[$i]['txt'] = $rec;
            $list[$i]['pic'] = $rec;
            $i++;
        }
    }

    public function getSQLFromList(&$list)
    {
        $sql = $this->getSelectFromSQL();
        if (!$sql)
            return;
        $formObj = $this->getFormObj();
        $do = $formObj->getDataObj();
        $db = $do->getDBConnection();
        try {
            $resultSet = $db->query($sql);
            $recList = $resultSet->fetchAll();
            foreach ($recList as $rec) {
                $list[$i]['val'] = $rec[0];
                $list[$i]['txt'] = isset($rec[1]) ? $rec[1] : $rec[0];
                $i++;
            }
        } catch (Exception $e) {
            Openbizx::$app->getLog()->log(LOG_ERR, "DATAOBJ", "Query Error: " . $e->getMessage());
            $this->errorMessage = "Error in SQL query: " . $sql . ". " . $e->getMessage();
            throw new Openbizx\Data\Exception($this->errorMessage);
            return null;
        }
    }

    protected function translateList(&$list, $tag)
    {
        $module = $this->getModuleName($this->selectFrom);
        if (empty($module)) {
            $module = $this->getModuleName($this->formName);
        }
        for ($i = 0; $i < count($list); $i++) {
            $key = 'SELECTION_' . strtoupper($tag) . '_' . $i . '_TEXT';
            $list[$i]['txt'] = I18n::t($list[$i]['txt'], $key, $module, $this->getTransLOVPrefix());
        }
    }

    protected function getTransLOVPrefix()
    {
        $nameArr = explode(".", $this->selectFrom);
        for ($i = 1; $i < count($nameArr) - 1; $i++) {
            $prefix .= strtoupper($nameArr[$i]) . "_";
        }
        return $prefix;
    }

}

?>