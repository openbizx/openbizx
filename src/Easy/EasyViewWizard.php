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
 * @version   $Id: WebPageWizard.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy;

use Openbizx\Easy\WebPage;

/**
 * WebPageWizard is the class that controls the wizard forms
 *
 * @package openbiz.bin.easy
 * @author rocky swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class WebPageWizard extends WebPage
{
    protected $currentStep;
    protected $formStates;    // (formname, visited, committed)
    protected $dropSession = false;
    protected $naviMethod = 'SwitchPage';

    protected function readMetadata(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->naviMethod = isset($xmlArr["WEBPAGE"]["ATTRIBUTES"]["NAVIMETHOD"]) ? $xmlArr["WEBPAGE"]["ATTRIBUTES"]["NAVIMETHOD"] :'SwitchPage';
    }
    /**
     * Get/Retrieve Session data of this object
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function loadStatefullVars($sessionContext)
    {
        $sessionContext->loadObjVar($this->objectName, "FormStates", $this->formStates, true);
        $sessionContext->loadObjVar($this->objectName, "CurrentStep", $this->currentStep, true);
    }

    /**
     * Save Session data of this object
     *
     * @param \Openbizx\Web\SessionContext $sessionContext
     * @return void
     */
    public function saveStatefullVars($sessionContext)
    {
        if ($this->dropSession){
            $sessionContext->cleanObj($this->objectName, true);
        }else{
            $sessionContext->saveObjVar($this->objectName, "FormStates", $this->formStates, true);
            $sessionContext->saveObjVar($this->objectName, "CurrentStep", $this->currentStep, true);
        }
        
    }

    /**
     * Initialize all form objects.
     * NOTE: Do not initiate the all forms
     *
     * @return void
     */
    protected function initAllForms()
    {
    }

    /**
     * Process request
     *
     * @return void
     */
    protected function processRequest()
    {
        parent::processRequest();

        $step = $this->getCurrentStep();

        // only display given step form
        $i = 1;
        foreach ($this->formRefs as $formRef)
        {
            if ($i == $step)
                $formRef->display = true;
            else
                $formRef->display = false;
            $i++;
        }
    }
        
    protected function getStepName($step)
    {
		$i = 1;
        foreach ($this->formRefs as $formRef){
            if($i == $step){            	
            	return $formRef->objectName;
            }        	
            $i++;
        }
        return "";
    }
        
    /**
     * Get current step
     *
     * @return number
     */
    public function getCurrentStep()
    {  	if($_GET['step'])
	    {
	    	$this->currentStep=$_GET['step'];
	    	return $this->currentStep;
	    }
    	elseif($this->currentStep)
    	{
    		if($this->currentStep > $this->formRefs->count()){    			            			
    			return $this->formRefs->count();	
    		}else{
    			return $this->currentStep;	
    		}    		
    	}
    	else
    	{
	        $step = isset($_GET['step']) ? $_GET['step'] : 1;
	        $numForms = 0;
	        foreach ($this->formRefs as $formRef)
	            $numForms++;
	
	        if ($step < 1)
	            $step = 1;
	        if ($step > $numForms)
	            $step = $numForms;
	        $this->currentStep = $step;
	        return $step;
    	}
    }

    /**
     * Render step
     *
     * @param number $step
     * @return void
     */
    public function renderStep($step)
    {
    	if($this->currentStep){
    		$currentStep = $this->currentStep;
    	}else{
        	$currentStep = $this->getCurrentStep();
    	}
        if ($currentStep == $step)
            return;            
		switch(strtoupper($this->naviMethod)){
			case "SWITCHFORM":
				$targetForm = $this->getStepName($step);
				$currentForm = $this->getStepName($currentStep);
				$this->currentStep = $step;		
				$formObj = Openbizx::getObject($currentForm);
				$formObj->switchForm($targetForm);
				break;
				
			case "SWITCHPAGE":
			default:
				$currentURL = Openbizx::getService(OPENBIZ_UTIL_SERVICE)->getViewURL($this->objectName);
		        $url = OPENBIZ_APP_INDEX_URL.'/'.$currentURL.'/step_'.$step;
				Openbizx::$app->getClientProxy()->ReDirectPage($url);
				break;
			
		}
    }

    /**
     * Get form inputs
     *
     * @param string $formName
     * @return array
     */
    public function getFormInputs($formName)
    {
        $formObj = Openbizx::getObject($formName);
        $rec = $formObj->getActiveRecord();
        return $rec;
    }

    /**
     * Set form state
     *
     * @param string $formName form name
     * @param mixed $state state key
     * @param mixed $value
     * @return void
     */
    public function setFormState($formName, $state, $value)
    {
        $this->formStates[$formName][$state] = $value;
    }

    /**
     * Save wizard data of current+previous pages into database or other storage
     *
     * @return void
     */
    public function commit()
    {
        // call all step forms Commit method    	
        foreach ($this->formStates as $formName=>$state)
        {        	
            if ($state['visited'])
            {
                $r = Openbizx::getObject($formName)->commit();                
                if (!$r)
                {                	                	
                	return false;
                }
            }
        }              
        foreach ($this->formStates as $formName=>$state)
        {
            if ($state['visited'])
            {
                $r = Openbizx::getObject($formName)->dropSession();
                if (!$r)
                {                	
                    return false;
                }
            }
        }         
        $this->dropSession = true;
        return true;
    }
   
    /**
     * Cancel, clean up the sessions of view and all forms
     *
     * @return void
     */
    public function cancel()
    {
        // call all step forms Cancel method
        if(is_array($this->formStates)){
	        foreach ($this->formStates as $formName=>$state)
	        {
	            if ($state['visited'])
	                Openbizx::getObject($formName)->cancel();
	        }
        }
        $this->dropSession = true;
    }

    /**
     * Get output attributs
     *
     * @return array
     * @todo need to raname to getOutputAttributs() or getAttributes
     */
    public function outputAttrs()
    {
        $out = parent::outputAttrs();
        $out['step'] = $this->currentStep;
        $out['forms'] = $this->formRefs;
        return $out;
    }

}

?>