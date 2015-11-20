<?php

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
 * @version   $Id: ImageUploader.php 2825 2010-12-08 19:22:02Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Easy\Element\FileUploader;

/**
 * File class is the element for Upload Image
 *
 * @package openbiz.bin.easy.element
 * @author jixian2003
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class ImageUploader extends FileUploader
{

    public $picWidth;
    public $picHeight;
    public $thumbWidth;
    public $thumbHeight;
    public $thumbFolder;
    public $preview;
    public $picQuality;
    public $thumbQuality;

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
        $this->translate();
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
        $this->picWidth = isset($xmlArr["ATTRIBUTES"]["PICWIDTH"]) ? $xmlArr["ATTRIBUTES"]["PICWIDTH"] : null;
        $this->picHeight = isset($xmlArr["ATTRIBUTES"]["PICHEIGHT"]) ? $xmlArr["ATTRIBUTES"]["PICHEIGHT"] : null;
        $this->picQuality = isset($xmlArr["ATTRIBUTES"]["PICQUALITY"]) ? $xmlArr["ATTRIBUTES"]["PICQUALITY"] : 80;
        $this->thumbWidth = isset($xmlArr["ATTRIBUTES"]["THUMBWIDTH"]) ? $xmlArr["ATTRIBUTES"]["THUMBWIDTH"] : null;
        $this->thumbHeight = isset($xmlArr["ATTRIBUTES"]["THUMBHEIGHT"]) ? $xmlArr["ATTRIBUTES"]["THUMBHEIGHT"] : null;
        $this->thumbQuality = isset($xmlArr["ATTRIBUTES"]["THUMBQUALITY"]) ? $xmlArr["ATTRIBUTES"]["THUMBQUALITY"] : 50;
        $this->thumbFolder = isset($xmlArr["ATTRIBUTES"]["THUMBFOLDER"]) ? $xmlArr["ATTRIBUTES"]["THUMBFOLDER"] : null;
        $this->preview = isset($xmlArr["ATTRIBUTES"]["PREVIEW"]) ? $xmlArr["ATTRIBUTES"]["PREVIEW"] : false;
    }

    /**
     * Set value of element
     *
     * @param mixed $value
     * @return mixed
     */
    function setValue($value)
    {
        if ($this->deleteable == 'N') {
            
        } else {
            $delete_user_opt = Openbizx::$app->getClientProxy()->getFormInputs($this->objectName . "_DELETE");
            if ($delete_user_opt) {
                $this->value = "";
                return;
            } else {
                if (count($_FILES) > 0) {
                    
                } else {
                    $this->value = $value;
                }
            }
        }

        if (count($_FILES) > 0) {
            if (!$this->uploaded && $_FILES[$this->objectName]["size"] > 0) {
                $picFileName = parent::setValue($value);
                if ((int) $this->picWidth > 0 || (int) $this->picHeight > 0) {
                    //resize picture size
                    $fileName = $this->uploadRoot . $picFileName;
                    $width = $this->picWidth;
                    $height = $this->picHeight;
                    $quality = $this->picQuality;

                    $this->resizeImage($fileName, $fileName, $width, $height, $quality);
                }
                if (
                        ((int) $this->thumbWidth > 0 || (int) $this->thumbHeight > 0) &&
                        $this->thumbFolder != ""
                ) {
                    //generate thumbs picture
                    if (!is_dir($this->uploadRoot . $this->thumbFolder)) {
                        mkdir($this->uploadRoot . $this->thumbFolder, 0777, true);
                    }
                    $file = $_FILES[$this->objectName];
                    $thumbPath = $this->thumbFolder . "/thumbs-" . date("YmdHis") . "-" . urlencode($file['name']);
                    $thumbFileName = $this->uploadRoot . $thumbPath;
                    $width = $this->thumbWidth;
                    $height = $this->thumbHeight;
                    $quality = $this->thumbQuality;

                    $this->resizeImage($fileName, $thumbFileName, $width, $height, $quality);

                    $result = array('picture' => $this->uploadRootURL . $picFileName, 'thumbpic' => $this->uploadRootURL . $thumbPath);
                    $this->value = serialize($result);
                }
            }
        } else {
            $this->value = $value;
        }
    }

    /**
     * Resize the image     *
     *
     * @param string $sourceFileName
     * @param string $destFileName
     * @param number $width
     * @param number $height
     * @param int    $quality <p>
     * quality is optional, and ranges from 0 (worst
     * quality, smaller file) to 100 (best quality, biggest file). The
     * default is the default IJG quality value (about 75).
     * </p>
     * @return boolean true is success
     */
    protected function resizeImage($sourceFileName, $destFileName, $width, $height, $quality)
    {
        if (!function_exists("imagejpeg")) {
            return;
        }
        if ($width == 0) {
            $width = $height;
        }

        if ($height == 0) {
            $height = $width;
        }

        list($origWidth, $origHeight) = getimagesize($sourceFileName);

        $origRatio = $origWidth / $origHeight;

        if (($width / $height) > $origRatio) {
            $width = $height * $origRatio;
        } else {
            $height = $width / $origRatio;
        }

        $image_p = imagecreatetruecolor($width, $height);
        try {
            $image = @imagecreatefromjpeg($sourceFileName);
        } catch (Exception $e) {

        }
        try {
            if (!$image) {
                $image = @imagecreatefrompng($sourceFileName);
            }
        } catch (Exception $e) {

        }
        try {
            if (!$image) {
                $image = @imagecreatefromgif($sourceFileName);
            }
        } catch (Exception $e) {

        }
        try {
            if (!$image) {
                $image = @imagecreatefromwbmp($sourceFileName);
            }
        } catch (Exception $e) {

        }

        try {
            if (!$image) {
                $image = @imagecreatefromxbm($sourceFileName);
            }
        } catch (Exception $e) {

        }


        try {
            if (!$image) {
                $image = @imagecreatefromxpm($sourceFileName);
            }
        } catch (Exception $e) {

        }

        if (!$image) {
            return;
        }
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        return imagejpeg($image_p, $destFileName, $quality);
    }

    public function render()
    {
        $disabledStr = ($this->getEnabled() == "N") ? "disabled=\"true\"" : "";
        $style = $this->getStyle();
        $func = $this->getFunction();
        $value = $this->getValue();
        if ($this->preview) {
            if ($value) {
                $preview = "<img id=\"" . $this->objectName . "_preview\" src=\"$value\" class=\"image_preview\" />";
            }
        }
        if ($this->deleteable == "Y") {
            $delete_opt = "<input type=\"checkbox\" name=\"" . $this->objectName . "_DELETE\" id=\"" . $this->objectName . "_DELETE\" >Delete";
        } else {
            $delete_opt = "";
        }
        $sHTML .= "
        $preview
        <input type=\"file\" onchange=\"Openbizx.ImageUploader.updatePreview('" . $this->objectName . "')\" name=\"$this->objectName\" id=\"" . $this->objectName . "\" value=\"$this->value\" $disabledStr $this->htmlAttr $style $func>
        $delete_opt
        ";
        return $sHTML;
    }

}

?>