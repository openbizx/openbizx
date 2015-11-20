<?php

/**
 * NewForm class
 *
 * @package 
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */

namespace Openbizx\View;

use Openbizx\Data\DataRecord;
use Openbizx\View\InputForm;

/*
 * public methods: fetchData, insertRecord, 
 */

class NewForm extends InputForm
{

    //list of method that can directly from browser
    protected $directMethodList = array('insertrecord', 'switchform');
    public $recordId;
    public $activeRecord;

    /**
     * Fetch single record
     *
     * @return array one record array
     */
    public function fetchData()
    {
        return $this->getNewRecord();
    }

    /**
     * Insert new record
     *
     * @return mixed
     */
    public function insertRecord()
    {
        $recArr = $this->readInputRecord();
        //$this->setActiveRecord($recArr);
        if (count($recArr) == 0)
            return;

        try {
            $this->validateForm();
        } catch (Openbizx\Validation\Exception $e) {
            $this->processFormObjError($e->errors);
            return;
        }

        $this->_doInsert($recArr);

        //$this->commitFormElements(); // commit change in FormElement
        // in case of popup form, close it, then rerender the parent form
        /* if ($this->parentFormName)
          {
          $this->close();

          $this->renderParent();
          } */

        $this->formHelper->processPostAction();
    }

    /**
     * Do insert record
     *
     * @param array $inputRecord
     * @return void
     */
    protected function _doInsert($inputRecord)
    {

        $dataRec = new DataRecord(null, $this->getDataObj());

        // $inputRecord['Id'] = null; // comment it out for name PK case 
        foreach ($inputRecord as $k => $v)
            $dataRec[$k] = $v; // or $dataRec->$k = $v;

        try {
            $dataRec->save();
        } catch (Openbizx\Validation\Exception $e) {
            $errElements = $this->getErrorElements($e->errors);
            if (count($e->errors) == count($errElements)) {
                $this->formHelper->processFormObjError($errElements);
            } else {
                $errmsg = implode("<br />", $e->errors);
                Openbizx::$app->getClientProxy()->showErrorMessage($errmsg);
            }
            return;
        } catch (Openbizx\Data\Exception $e) {
            $this->processDataException($e);
            return;
        }
        $this->activeRecord = null;
        $this->getActiveRecord($dataRec["Id"]);

        //$this->runEventLog();
        return $dataRec["Id"];
    }

    /**
     * Get new record
     *
     * @return array
     */
    protected function getNewRecord()
    {
        if ($this->getDataObj()) {
            $recArr = $this->getDataObj()->newRecord();
        }
        if (!$recArr)
            return null;
        // load default values if new record value is empty
        $defaultRecArr = array();
        foreach ($this->dataPanel as $element) {
            if ($element->fieldName) {
                $defaultRecArr[$element->fieldName] = $element->getDefaultValue();
            }
        }
        foreach ($recArr as $field => $val) {
            if ($val == "" && $defaultRecArr[$field] != "") {
                $recArr[$field] = $defaultRecArr[$field];
            }
        }
        return $recArr;
    }

}

?>