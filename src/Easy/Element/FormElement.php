<?php

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Easy\Element\InputElement;


class FormElement extends InputElement
{
    protected $formReference;
    protected $renameElementSet;
    protected $parentFormElementMeta;

    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->formReference = isset($xmlArr["ATTRIBUTES"]["FORMREFERENCE"]) ? $xmlArr["ATTRIBUTES"]["FORMREFERENCE"] : null;        
        $this->renameElementSet = isset($xmlArr["ATTRIBUTES"]["RENAMEELEMENTSET"]) ? $xmlArr["ATTRIBUTES"]["RENAMEELEMENTSET"] : 'Y';
    }
    
    public function FormRecordCount()
    {
    	if(strtoupper($this->renameElementSet)!='Y'){
    		return;
    	}
    	$formElementObj = Openbizx::getObject($this->formReference);
    	if(strtolower($formElementObj->formType)!='list'){
    		return;
    	}
    	if(!$formElementObj->getDataObj())
    	{
    		$methodName = "getRecordList";
    		if(method_exists($formElementObj, $methodName))
    		{
    			$recs = $formElementObj->$methodName();
    			$count = count($recs);
    		}else{
    			return;
    		}
    	}else{	    	
	    	$prtDO = $formElementObj->getDataObj();
	    	$prtDO->clearSearchRule();
	    	$count = $prtDO->count();
    	}
    	
   	 	if($count<0){
	    		return;
	    }
    	$my_elementset = $this->elementSet;
    	
    	//update other elements
    	$panel = $this->getFormObj()->dataPanel;
    	$panel->rewind();
        while($panel->valid())    	    	
        {      
        	$elem = $panel->current();
        	if($elem->elementSet ){     
        		if($elem->elementSet == $my_elementset && !preg_match("/tab_label_count/si",$elem->elementSet)){
        			$elem->elementSet.=" <span class=\"tab_label_count\">$count</span>";
        		}
        	}     
        	$panel->next();        	                                  
        }
    }
    
    /**
     * Draw the element according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {    	
        if(!$this->formReference)
        {
        	return null;
        }
        $formObj = $this->getFormObj();   
        $formElementObj = Openbizx::getObject($this->formReference);
        $formElementObj->parentFormName = $formObj->objectName;
        $formElementObj->parentFormElementMeta = $this->xmlMeta;
		$formElementObj->canUpdateRecord = $formObj->canUpdateRecord;
        if (method_exists($formObj,"SetSubForms"))
        {
                $formObj->setSubForms($this->formReference);   
                if($formObj->dataObjName){             
                	$formDataObj = Openbizx::getObject($formObj->dataObjName);
               	 	$dataObj = $formDataObj->getRefObject($formElementObj->dataObjName);
                }
                if ($dataObj)
                    $formObj->setDataObj($dataObj);                
        }        
    	$sHTML = $formElementObj->render();    	
    	$formObj->setDataObj($formDataObj);
    	$this->FormRecordCount();    
    	if(strlen($sHTML))
    	{
    		$this->hidden = "N";
    	}
    	else
    	{
    		$this->hidden = "Y";
    	}
        return $sHTML;
    }

    public function setValue($value)
    {
    	if($this->allowAccess())
    	{
	    	$formElementObj = Openbizx::getObject($this->formReference);
	    	if(method_exists($formElementObj, "setValue"))
	    	{
	    		return $formElementObj->setValue($value);
	    	}
    	}
    }
    
    public function getValue()
    {
    	if($this->allowAccess())
    	{
	    	$formElementObj = Openbizx::getObject($this->formReference);
	    	if(method_exists($formElementObj, "getValue"))
	    	{
	    		return $formElementObj->getValue();
	    	}
    	}
    }    
}
