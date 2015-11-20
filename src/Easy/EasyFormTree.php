<?PHP
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
 * @version   $Id: EasyFormTree.php 2606 2010-11-25 07:51:59Z mr_a_ton $
 */

namespace Openbizx\Easy;

use Openbizx\Data\Helpers\QueryStringParam;
use Openbizx\Easy\EasyForm;

/**
 * EasyFormTree class - contains formtree object metadata functions
 *
 * @package openbiz.bin.easy
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @since 1.2
 * @access public
 */
class EasyFormTree extends EasyForm
{
	public $titleField;
	public $rootSearchRule;
    public $treeDepth;
    
	protected function readMetadata(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->titleField = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["TITLEFIELD"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["TITLEFIELD"] : "title";
        $this->rootSearchRule = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["ROOTSEARCHRULE"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["ROOTSEARCHRULE"] : null;
        $this->treeDepth = isset($xmlArr["EASYFORM"]["ATTRIBUTES"]["TREEDEPTH"]) ? $xmlArr["EASYFORM"]["ATTRIBUTES"]["TREEDEPTH"] : 10;
    }
    
   public function fetchDataSet()
    {
        
        $dataObj = $this->getDataObj();
        if (!$dataObj) return null;
        
        QueryStringParam::setBindValues($this->searchRuleBindValues);

        if ($this->isRefreshData)
            $dataObj->resetRules();
        else
            $dataObj->clearSearchRule();

        if ($this->fixSearchRule)
        {
            if ($this->searchRule)
                $searchRule = $this->searchRule . " AND " . $this->fixSearchRule;
            else
                $searchRule = $this->fixSearchRule;
        }
        else
            $searchRule = $this->searchRule;

        $dataObj->setSearchRule($searchRule);
        if($this->startItem>1)
        {
            $dataObj->setLimit($this->range, $this->startItem);
        }
        else
        {
            $dataObj->setLimit($this->range, ($this->currentPage-1)*$this->range);
        }
        //$resultRecords = $dataObj->fetch();
        
        $resultRecordTree = $dataObj->fetchTree($this->rootSearchRule,$this->treeDepth);
        if(is_array($resultRecordTree)){
	        foreach ($resultRecordTree as $resultRecordTreeNode){
	        	$this->tree2array($resultRecordTreeNode, $resultRecords);
	        }
        }
        $this->totalRecords = $dataObj->count();
        if ($this->range && $this->range > 0)
            $this->totalPages = ceil($this->totalRecords/$this->range);
        $selectedIndex = 0;
        $this->getDataObj()->setActiveRecord($resultRecords[$selectedIndex]);

        QueryStringParam::ReSet();

        return $resultRecords;
    }    
    
    private function tree2array($tree,&$array,$level=0){
    	if(!is_array($array)){
    		$array=array();
    	}
    	
    	$treeNodeArray = array(
    		"Level" => $level,
    		"Id" => $tree->recordId,
    		"PId" => $tree->recordParentId,
    	);
    	foreach ($tree->record as $key=>$value){
    		$treeNodeArray[$key] = $value;    		
    	}
    	$treeNodeArray[$this->titleField] = "+ ".str_repeat("- - - -", $level)." ".$treeNodeArray[$this->titleField];
    	
    	array_push($array, $treeNodeArray);
    	$level++;   
    	if(is_array($tree->childNodes)){    		
    		foreach($tree->childNodes as $treeNode){    			
    			$this->tree2array($treeNode, $array, $level);    			    			
    		}    		
    	}
    	return $array;
    }
}
?>