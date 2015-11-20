<?php
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.easy
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: EasyFormGrouping.php 1664 2012-02-02 15:33:22Z hellojixian@gmail.com $
 */

namespace Openbizx\Easy;

use Openbizx\Data\Helpers\QueryStringParam;
use Openbizx\Easy\EasyForm;

class EasyFormGrouping extends EasyForm {

    protected $groupBy;
    public $totalPagesBak;

    protected function readMetadata(&$xmlArr) {
        parent::readMetaData($xmlArr);
        $this->groupBy = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["GROUPBY"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["GROUPBY"] : null;
    }

    public function fetchDataGroup() {
        //get group list first
        $dataObj = $this->getDataObj();

        if (!$dataObj)
            return null;
        if ($this->isRefreshData)
            $dataObj->resetRules();
        else
            $dataObj->clearSearchRule();

        if (strpos($this->groupBy, ":")) {
            preg_match("/\[(.*?):(.*?)\]/si", $this->groupBy, $match);
            $GroupFieldName = $match[1];
            $GroupField = $match[2];
        } else {
            $GroupField = str_replace("[", "", $this->groupBy);
            $GroupField = str_replace("]", "", $GroupField);
        }
        $GroupSQLRule = "GROUP BY [$GroupField]";
        $dataObj->setOtherSQLRule($GroupSQLRule);

        //within each group, search records like before
        QueryStringParam::setBindValues($this->searchRuleBindValues);

        if ($this->fixSearchRule) {
            if ($this->searchRule)
                $searchRule = $this->searchRule . " AND " . $this->fixSearchRule;
            else
                $searchRule = $this->fixSearchRule;
        } else
            $searchRule = $this->searchRule;

        $dataObj->setSearchRule($searchRule);

        $resultRecords = $dataObj->fetch();
        $this->totalRecords = $dataObj->count();
        if ($this->range && $this->range > 0)
            $this->totalPages = ceil($this->totalRecords / $this->range);

        $this->totalPagesBak = $this->totalPages;
        QueryStringParam::ReSet();
        //looping
        $i = 0;
        $results = array();
        foreach ($resultRecords as $record) {
            if ($this->isRefreshData) {
                $dataObj->resetRules();
            } else {
                $dataObj->clearSearchRule();
            }
            QueryStringParam::setBindValues($this->searchRuleBindValues);
            $group_val = $record[$GroupField];
            if ($this->fixSearchRule) {
                if ($this->searchRule)
                    $searchRule = $this->searchRule . " AND " . $this->fixSearchRule;
                else
                    $searchRule = $this->fixSearchRule;
            } else
                $searchRule = $this->searchRule;
            if ($group_val) {
                if ($searchRule != "") {
                    $searchRule = $searchRule . " AND [$GroupField]='$group_val'";
                } else {
                    $searchRule = " [$GroupField]='$group_val'";
                }
            } else {
                if ($searchRule != "") {
                    $searchRule = $searchRule . " AND [$GroupField]  is NULL";
                } else {
                    $searchRule = " [$GroupField] is NULL";
                }
            }

            $dataObj->setOtherSQLRule("");
            $dataObj->setLimit(0, 0);
            $dataObj->setSearchRule($searchRule);
            $resultRecords_grouped = $dataObj->fetch();
            //renderTable
            $resultRecords_grouped_table = $this->dataPanel->renderTable($resultRecords_grouped);

            if ($record[$GroupField]) {
                if ($GroupFieldName) {
                    $results[$record[$GroupFieldName]] = $resultRecords_grouped_table;
                } else {
                    $results[$record[$GroupField]] = $resultRecords_grouped_table;
                }
            } else {
                $results["Empty"] = $resultRecords_grouped_table;
            }


            $i++;
            QueryStringParam::ReSet();
        }

        //set active records
        $selectedIndex = 0;
        $this->getDataObj()->setActiveRecord($resultRecords[$selectedIndex]);
        return $results;
    }

    public function fetchDataSet() {
        $this->fetchDataGroup();
        $resultset = parent::fetchDataSet();
        $this->totalPages = $this->totalPagesBak;
        return $resultset;
    }

    public function outputAttrs() {
        $output = parent::outputAttrs();
        $output['dataGroup'] = $this->fetchDataGroup();
        return $output;
    }

}

?>
