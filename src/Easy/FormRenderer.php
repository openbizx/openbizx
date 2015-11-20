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
 * @licennse   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: FormRenderer.php 4075 2011-05-02 13:43:39Z jixian2003 $
 */

namespace Openbizx\Easy;

use Openbizx\Openbizx;
use Openbizx\Helpers\TemplateHelper;

/**
 * FormRenderer class is form helper for rendering form
 *
 * @package openbiz.bin.easy
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2011
 * @access public
 */
class FormRenderer
{

    /**
     * Render form object
     *
     * @param EasyForm $formObj
     * @return string result of rendering process
     */
    static public function render($formObj)
    {
        $tplEngine = $formObj->templateEngine;
        $tplAttributes = FormRenderer::buildTemplateAttributes($formObj);
        if (isset($formObj->jsClass)) {
            $subForms = ($formObj->subForms) ? implode(";", $formObj->subForms) : "";

            if ($formObj->staticOutput != true) {
                $formScript = "\n<script>Openbizx.newFormObject('$formObj->objectName','$formObj->jsClass','$subForms'); </script>\n";
            }
            if ($formObj->autoRefresh > 0) {
                $formScript .= "\n<script>setTimeout(\"Openbizx.CallFunction('$formObj->objectName.UpdateForm()');\",\"" . ($formObj->autoRefresh * 1000) . "\") </script>\n";
            }
        }

        if ($tplEngine == "Smarty" || $tplEngine == null) {
            return FormRenderer::renderSmarty($formObj, $tplAttributes) . $formScript;
        } else
            return FormRenderer::renderPHP($formObj, $tplAttributes) . $formScript;
    }

    /**
     * Gather all template variables needed. Should play well with Smarty or \Zend templates
     *
     * @param WebPage $formObj
     * @return array associative array holding all needed VIEW based template variables
     */
    static public function buildTemplateAttributes($formObj)
    {
        // Assocative Array to hold all Template Values
        // Fill with default viewobj attributes
        $tplAttributes = array();

        $tplAttributes['title'] = $formObj->title;
        $tplAttributes['errors'] = $formObj->errors;
        $tplAttributes['notices'] = $formObj->notices;
        $tplAttributes['formname'] = $formObj->objectName;
        $tplAttributes['module'] = $formObj->getModuleName($formObj->objectName);

        // if the $formobj form type is list render table, otherwise render record
        if (strtoupper($formObj->formType) == 'LIST') {
            $recordSet = $formObj->fetchDataSet();
            $tplAttributes['dataPanel'] = $formObj->dataPanel->renderTable($recordSet);
        } else {
            $record = $formObj->fetchData();
            $tplAttributes['dataPanel'] = $formObj->dataPanel->renderRecord($record);
        }

        if (isset($formObj->searchPanel)) {
            $search_record = $formObj->searchPanelValues;
            foreach ($formObj->searchPanel as $elem) {
                if (!$elem->fieldName)
                    continue;
                $post_value = Openbizx::$app->getClientProxy()->getFormInputs($elem->objectName);
                if ($post_value) {
                    $search_record[$elem->fieldName] = $post_value;
                }
            }
            $tplAttributes['searchPanel'] = $formObj->searchPanel->renderRecord($search_record);
        } else {
            $tplAttributes['searchPanel'] = $formObj->searchPanel->render();
        }
        $tplAttributes['actionPanel'] = $formObj->actionPanel->render();
        $tplAttributes['navPanel'] = $formObj->navPanel->render();

        if (isset($formObj->wizardPanel)) {
            $tplAttributes['wizardPanel'] = $formObj->wizardPanel->render();
        }

        $tplAttributes['form'] = $formObj->outputAttrs();

        return $tplAttributes;
    }

    /**
     * Render smarty template for form object
     *
     * @param EasyForm $formObj
     * @param string $tplFile
     * @return string result of rendering process
     */
    static protected function renderSmarty($formObj, $tplAttributes = Array())
    {
        $smarty = TemplateHelper::getSmartyTemplate();
        $tplFile = TemplateHelper::getTplFileWithPath($formObj->templateFile, $formObj->package);

        //Translate Array of template variables to \Zend template object
        foreach ($tplAttributes as $key => $value) {
            $smarty->assign($key, $value);
        };

        return $smarty->fetch($tplFile);
    }

    /**
     * Render PHP template for form object
     *
     * @param EasyForm $formObj
     * @param string $tplFile
     * @return string result of rendering process
     */
    static protected function renderPHP($formObj, $tplAttributes = Array())
    {
        $form = TemplateHelper::getZendTemplate();
        $tplFile = TemplateHelper::getTplFileWithPath($formObj->templateFile, $formObj->package);
        $form->addScriptPath(dirname($tplFile));

        /* $formOutput = $formObj->outputAttrs();
          foreach ($formOutput as $k=>$v) {
          $form->$k = $v;
          } */

        foreach ($tplAttributes as $key => $value) {
            if ($value == NULL) {
                $form->$key = '';
            } else {
                $form->$key = $value;
            }
        }

        // render the formobj attributes
        //$form->form = $formOutput;

        return $form->render($formObj->templateFile);
    }

}

?>