<?php
/**
 * EditForm class
 *
 * @package 
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */

namespace Openbizx\View;

use Openbizx\View\InputForm;
use Openbizx\Data\DataRecord;
 /*
  * public methods: fetchData, updateRecord, 
  */
class EditForm extends InputForm
{
	//list of method that can directly from browser
	protected $directMethodList = array('updaterecord','switchform'); 

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

	/**
     * Update record
     *
     * @return mixed
     */
    public function updateRecord()
    {
		$recArr = $this->readInputRecord();
		
		$this->recordId = $recArr['Id'];
        $currentRec = $this->getDataObj()->fetchById($this->recordId);
		
        //$this->setActiveRecord($recArr);
        if (count($recArr) == 0)
            return;

        try
        {
            $this->ValidateForm();
        }
        catch (Openbizx\Validation\Exception $e)
        {
            $this->processFormObjError($e->errors);
            return;
        }
	
		if ($this->_doUpdate($recArr, $currentRec) == false) return;
			
		//$this->commitFormElements(); // commit change in FormElement
		
        // in case of popup form, close it, then rerender the parent form
        /*if ($this->parentFormName)
        {
            $this->close();

            $this->renderParent();
        }*/
        
        $this->formHelper->processPostAction();
    }
	
	 /**
     * Do update record
     *
     * @param array $inputRecord
     * @param array $currentRecord
     * @return void
     */
    protected function _doUpdate($inputRecord, $currentRecord)
    {
        $dataRec = new DataRecord($currentRecord, $this->getDataObj());

        foreach ($inputRecord as $k => $v){
           	$dataRec[$k] = $v; // or $dataRec->$k = $v;
        }

        try
        {
            $dataRec->save();
        }
        catch (Openbizx\Validation\Exception $e)
        {
            $errElements = $this->getErrorElements($e->errors);           
        	if(count($e->errors)==count($errElements)){
            	$this->formHelper->processFormObjError($errElements);
            }else{            	
            	$errmsg = implode("<br />",$e->errors);
		        Openbizx::$app->getClientProxy()->showErrorMessage($errmsg);
            }
            return false;
        }
        catch (Openbizx\Data\Exception $e)
        {
            $this->processDataException($e);
            return false;
        }
		$this->activeRecord = null;
        $this->getActiveRecord($dataRec["Id"]);

        //$this->runEventLog();
        return true;
    }
}
?>