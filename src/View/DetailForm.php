<?php
/**
 * DetailForm class
 *
 * @package 
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
 
namespace Openbizx\View;

use Openbizx\View\BaseForm;

 /*
  * public methods: fetchData, deleteRecord
  */
class DetailForm extends BaseForm
{
	//list of method that can directly from browser
	protected $directMethodList = array('deleterecord','switchform'); 
	
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
	
	/**
     * Delete Record
     * NOTE: use redirectpage attr of eventhandler to redirect or redirect to previous page by default
     *
     * @param string $id
     * @return void
     */
    public function deleteRecord($id)
	{  	
		$dataRec = $this->getDataObj()->fetchById($id);
		//$this->getDataObj()->setActiveRecord($dataRec);
		
		// take care of exception
		try {
			$dataRec->delete();
		} catch (Openbizx\Data\Exception $e) {
			// call $this->processDataException($e);
			$this->processDataException($e);
			return;
		}

        //$this->runEventLog();
        $this->processPostAction();
	}
}
?>