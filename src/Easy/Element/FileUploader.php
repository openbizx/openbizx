<?PHP
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.easy.element
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: FileUploader.php 3086 2011-01-22 20:06:43Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Easy\Element\FileInput;

/**
 * File class is the element for Upload File
 *
 * @package openbiz.bin.easy.element
 * @author jixian2003
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class FileUploader extends FileInput
{
    public $uploadRoot ;
    public $uploadRootURL ;
    public $uploadFolder ;
    public $uploadFileType ;
    public $uploaded =false;   	
    public $deleteable;
    public $useRawName=false;        

    /**
     * Initialize Element with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr, $formObj)
    {
        parent::__construct($xmlArr, $formObj);
        $this->readMetaData($xmlArr);
        if(defined("OPENBIZ_PUBLIC_UPLOAD_PATH")){
        	$this->uploadRoot= constant("OPENBIZ_PUBLIC_UPLOAD_PATH");
        }else{
        	$this->uploadRoot= OPENBIZ_APP_PATH."/files/upload";
        }
        if(defined("OPENBIZ_PUBLIC_UPLOAD_URL")){
        	$this->uploadRootURL = str_replace(OPENBIZ_APP_URL,"",constant("OPENBIZ_PUBLIC_UPLOAD_URL"));
        }else{
        	$this->uploadRootURL = "/files/upload";
        }
    }

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->uploadFolder = isset($xmlArr["ATTRIBUTES"]["UPLOADFOLDER"]) ? $xmlArr["ATTRIBUTES"]["UPLOADFOLDER"] : null;
        $this->uploadFileType = isset($xmlArr["ATTRIBUTES"]["FILETYPE"]) ? $xmlArr["ATTRIBUTES"]["FILETYPE"] : null;
        $this->deleteable = isset($xmlArr["ATTRIBUTES"]["DELETEABLE"]) ? $xmlArr["ATTRIBUTES"]["DELETEABLE"] : "N";
        $this->useRawName = isset($xmlArr["ATTRIBUTES"]["USERAWNAME"]) ? $xmlArr["ATTRIBUTES"]["USERAWNAME"] : false;
    }

    /**
     * Set value of element
     *
     * @param mixed $value
     * @return string
     */
    function setValue($value)
    {
        if($this->deleteable=='N')
    	{

    	}
    	else
    	{
    		$delete_user_opt= Openbizx::$app->getClientProxy()->getFormInputs($this->objectName."_DELETE");
    		if($delete_user_opt)
    		{
    			$this->value="";
    			return;
    		}
    		else
    		{
    			if(count($_FILES)>0){
    				
    			}else{
    				$this->value = $value;
    			}   
    		} 
    	}
    	if(count($_FILES)>0)
		{
			if(!$this->uploaded && $_FILES[$this->objectName]["size"] > 0)
			{
				$file = $_FILES[$this->objectName];

				if(!is_dir($this->uploadRoot.$this->uploadFolder))
				{
					mkdir($this->uploadRoot.$this->uploadFolder ,0777,true);
				}
				if($this->useRawName){
					$uploadFile = $this->uploadFolder."/".$file['name'];
				}else{
					$uploadFile = $this->uploadFolder."/".date("YmdHis")."-".md5($file['name']);
				}
				if($this->uploadFileType){
					$pattern = "/".$this->uploadFileType."$/si";
					if(!preg_match($pattern,$file['name'])){
						return;
					}	                		                	
				}
				if(move_uploaded_file($file['tmp_name'], $this->uploadRoot.$uploadFile))
				{
					$this->value = $this->uploadRootURL.$uploadFile;
					$this->uploaded=true;
				}	                	                
				return $uploadFile;		
			}
		}    	
    }

    public function render()
    {
    	if($this->deleteable=="Y"){
        	$delete_opt="<input type=\"checkbox\" name=\"" . $this->objectName . "_DELETE\" id=\"" . $this->objectName ."_DELETE\" >Delete";
        } else{
        	$delete_opt="";
        }
        $disabledStr = ($this->getEnabled() == "N") ? "disabled=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();
        $sHTML .= "<input type=\"file\" name='$this->objectName' id=\"" . $this->objectName ."\" value='$this->value' $disabledStr $this->htmlAttr $style $func />        
        			$delete_opt";
        return $sHTML;
    }    

}
?>