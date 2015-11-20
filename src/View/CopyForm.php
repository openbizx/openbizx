<?php
/**
 * CopyForm class
 *
 * @package 
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */

namespace Openbizx\View;

use Openbizx\View\NewForm;

 /*
  * public methods: fetchData, insertRecord, 
  */
class CopyForm extends NewForm
{
	//list of method that can directly from browser
	protected $directMethodList = array('insertrecord','switchform'); 
	
	public $recordId;
	public $activeRecord;
	
	// get request parameters from the url
	protected function getUrlParameters()
	{
		if (isset($_REQUEST['fld:Id'])) {
			$this->recordId = $_REQUEST['fld:Id'];
		}
	}
	
	public function render()
	{
		$this->getUrlParameters();
		if (empty($this->recordId))
        {
            Openbizx::$app->getClientProxy()->showClientAlert($this->getMessage("PLEASE_EDIT_A_RECORD"));
            return;
        }
		return parent::render();
	}
	
	/**
     * Fetch single record
     *
     * @return array one record array
     */
    public function fetchData()
    {    	
        // if has valid active record, return it, otherwise do a query
        if ($this->activeRecord != null)
            return $this->activeRecord;
        
        $dataObj = $this->getDataObj();
        if ($dataObj == null) return;
		
        // TODO: use getDataById to fetch one record
		$dataRec = $dataObj->fetchById($this->recordId);
		return $dataRec->toArray();
    }
}
?>