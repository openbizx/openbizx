<?php

namespace Openbizx\Easy;

use Openbizx\Easy\EasyForm;

class EasyFormORM extends EasyForm {

    protected $assocDOs = array();

    /**
     * 
     * Get all assocsiated data object which has been
     * referenced on current form's data panel's element
     * 
     * @return array of BizDataObj 
     */
    protected function getAssocDOs() {
        if ($this->assocDOs) {
            return $this->assocDOs;
        }
        $formMainDO = $this->getDataObj();
        foreach ($this->dataPanel as $element) {
            $objName = $element->bizDataObj;
            $refObj = $formMainDO->getRefObject($objName);
            if ($refObj) {
                $this->assocDOs[$refObj->objectName] = $refObj;
            }
        }
        return $this->assocDOs;
    }

    /**
     * 
     * get an input record by specified DO name
     * @param array $inputRecord
     */
    protected function getAssocRec($doName) {
        $recArr = array();
        foreach ($this->dataPanel as $element) {
            if ($element->bizDataObj == $doName) {
                $value = Openbizx::$app->getClientProxy()->getFormInputs($element->objectName);
                if ($value === null && (
                        !is_a($element, "FileUploader") && !is_subclass_of($element, "FileUploader") && !is_a($element, "Checkbox") && !is_a($element, "FormElement")
                        )) {
                    continue;
                }
                $element->setValue($value);
                $this->formInputs[$element->objectName] = $value;
                $value = $element->getValue();
                if ($element->fieldName)
                    $recArr[$element->fieldName] = $value;
            }
        }
        return $recArr;
    }

    protected function _doInsert($inputRecord) {
        $recId = parent::_doInsert($inputRecord);
        $formMainDO = $this->getDataObj();
        foreach ($this->getAssocDOs() as $refDO) {

            $inputRefRecord = $this->getAssocRec($refDO->objectName);
            $refRecId = $refDO->insertRecord($inputRefRecord);
            $inputRefRecord['Id'] = $refRecId;
            $refRec = $inputRefRecord;
            $refDO->addRecord($refRec, $isParentObjUpdated);
        }
    }

}

?>