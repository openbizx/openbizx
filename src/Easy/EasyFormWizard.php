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
 * @version   $Id: EasyFormWizard.php 3037 2010-12-30 04:00:30Z iceve $
 */

namespace Openbizx\Easy;

use Openbizx\Data\DataRecord;
use Openbizx\Easy\EasyForm;

/**
 * EasyFormWizard class, extension of EasyForm to support wizard form
 *
 * @package openbiz.bin.easy
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class EasyFormWizard extends EasyForm
{
    protected $dropSession = false;

    /**
     * Wizard Navigation Panel object
     *
     * @var Panel
     */
    public $wizardPanel;    
    
    /**
     * Get/Retrieve Session data of this object
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function loadStatefullVars($sessionContext)
    {
    	parent::loadStatefullVars($sessionContext);
        $sessionContext->loadObjVar($this->objectName, "ActiveRecord", $this->activeRecord, true);
        $sessionContext->loadObjVar($this->objectName, "FormInputs", $this->formInputs, true);
        $this->setActiveRecord($this->activeRecord);
    }

 	protected function readMetadata(&$xmlArr)
    {
        parent::readMetaData($xmlArr);        
        $this->wizardPanel = new Panel($xmlArr["EASYFORM"]["WIZARDPANEL"]["ELEMENT"],"",$this);
    }    
    
    /**
     * Save object variable to session context
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function saveStatefullVars($sessionContext)
    {    	
        if ($this->dropSession)
            $sessionContext->cleanObj($this->objectName, true);
        else {
        	parent::saveStatefullVars($sessionContext);
            $sessionContext->saveObjVar($this->objectName, "ActiveRecord", $this->activeRecord, true);
            $sessionContext->saveObjVar($this->objectName, "FormInputs", $this->formInputs, true);
        }
    }

    /**
     * Go to next wizard page
     *
     * @param boolean $commit true if need to commit current form data
     * @return void
     * @access remote
     */
    public function goNext($commit=false)
    {
        // call ValidateForm()
        $recArr = $this->readInputRecord();
        $this->setActiveRecord($recArr);
    	
   		 try
        {
             if ($this->ValidateForm() == false)
            return;
        }catch (Openbizx\Validation\Exception $e)
        {
            $this->processFormObjError($e->errors);
            return;
        }

        $this->activeRecord = $this->readInputRecord();
		$viewObj = $this->getWebpageObject();
        // get the step
    	if($viewObj->getCurrentStep()){
        	$step = $viewObj->getCurrentStep();
        }else{
        	$step = $_GET['step'];
        }
        if (!$step || $step=="")
            $step=1;

        // redirect the prev step
        /* @var $viewObj WebPageWizard */
        
        $viewObj->renderStep($step+1);
    }
    
/**
     * Skip current wizard page
     *
     * @return void
     * @access remote
     */
    public function skip()
    {
		$viewObj = $this->getWebpageObject();
        // get the step
    	if($viewObj->getCurrentStep()){
        	$step = $viewObj->getCurrentStep();
        }else{
        	$step = $_GET['step'];
        }
        if (!$step || $step=="")
            $step=1;

        $viewObj->renderStep($step+2);
    }

    /**
     * Go to previous wizard page
     *
     * @return void
     * @access remote
     */
    public function goBack()
    {
        $recArr = $this->readInputRecord();
        $this->setActiveRecord($recArr);
		$this->activeRecord = $this->readInputRecord();
		
        $viewObj = $this->getWebpageObject();
        
        // get the step
        if($viewObj->getCurrentStep()){
        	$step = $viewObj->getCurrentStep();
        }else{
        	$step = $_GET['step'];
        }

        // redirect the prev step
        /* @var $viewObj WebPageWizard */
        
        $viewObj->renderStep($step-1);
    }

    /**
     * Finish the wizard process
     *
     * @return void
     * @access remote
     */
    public function doFinish() //- call FinishWizard() by default

    {
        // call ValidateForm()
        $recArr = $this->readInputRecord();
        $this->setActiveRecord($recArr);                
    	$this->setFormInputs($this->formInputs);
    	
   		 try
        {
             if ($this->ValidateForm() == false)
            return;
        }catch (Openbizx\Validation\Exception $e)
        {                    	
        	$this->processFormObjError($e->errors);
            return;
        }

        $this->activeRecord = $this->readInputRecord();
		
        /* @var $viewObj WebPageWizard */
        $viewObj = $this->getWebpageObject();
        
        $r = $viewObj->commit();        
        if (!$r)
            return;

        $this->processPostAction();
    }

    /**
     * Cancel the wizard process
     *
     * @return void
     * @access remote
     */
    public function doCancel() //- call CancelWizard() by default

    {
        /* @var $viewObj WebPageWizard */
        $viewObj = $this->getWebpageObject();
        $viewObj->cancel();

        $this->processPostAction();
    }

    /**
     * Save wizard data of current+previous pages into database or other storage
     *
     * @return void
     */
    public function commit()
    {
		if(!$this->getDataObj()){
			return true;
		}
    	// commit the form input. call SaveRecord()        
        $recArr = $this->activeRecord;
        
        if (strtoupper($this->formType) == "NEW")
            $dataRec = new DataRecord(null, $this->getDataObj());
        else
        {
            //$currentRec = $this->fetchData(); // wrong way to get current data. need to query the old one
            $currentRec = array(); // to get record with "" values
            $dataRec = new DataRecord($currentRec, $this->getDataObj());
        }

        foreach ($recArr as $k => $v)
            $dataRec[$k] = $v; // or $dataRec->$k = $v;
        try
        {
            $dataRec->save();
        } catch (Openbizx\Data\Exception $e)
        {
            $this->processDataException($e);
            return false;
        }

        return true;
    }

    public function dropSession(){
    	// clean the session record    	
        $this->dropSession = true;
        return true;
    }    
    

    /**
     * Clean up the sessions of view and all forms
     *
     * @return void
     */
    public function cancel()
    {
        // clean the session record
        $this->dropSession = true;
        Openbizx::$app->getSessionContext()->cleanObj($this->objectName, true);
    }

    /**
     * Render this form
     *
     * @return @return string - HTML text of this form's read mode
     */
    public function render()
    {
        $viewobj = $this->getWebpageObject();
        $viewobj->setFormState($this->objectName, 'visited', 1);

        return parent::render();
    }
    public function outputAttrs()
    {
        $output = parent::outputAttrs();
        $viewobj = $this->getWebpageObject();
        $forms = array();
        $viewobj->formRefs->rewind();
        while($viewobj->formRefs->valid()){
        	$form=$viewobj->formRefs->current();
        	$forms[$form->objectName] = $form;
        	$viewobj->formRefs->next();
        }        
        $output['forms'] = $forms;                
        $output['step'] = $viewobj->getCurrentStep();        
        return $output;
    }    
}
?>