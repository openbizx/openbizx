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
 * @version   $Id: ColumnBool.php 3687 2011-04-12 19:58:36Z jixian2003 $
 */

namespace Openbizx\Easy\Element;

use Openbizx\Openbizx;
use Openbizx\Core\Expression;

//include_once("ColumnText.php");

/**
 * ColumnBool class is element for ColumnBool
 * show boolean on data list (table)
 *
 * @package openbiz.bin.easy.element
 * @author wangdong1984 
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class ColumnShare extends ColumnText
{

    public $myPrivateImg = null;
    public $mySharedImg = null;
    public $myAssignedImg = null;
    public $myDistributedImg = null;
    public $groupSharedImg = null;
    public $otherSharedImg = null;
    public $defaultImg = null;
    public $recordOwnerId = null;
    public $recordGroupId = null;
    public $recordGroupPerm = null;
    public $recordOtherPerm = null;
    public $recordCreatorId = null;
    protected $recordOwnerId_AutoLoad = false;
    protected $recordGroupId_AutoLoad = false;
    protected $recordGroupPerm_AutoLoad = false;
    protected $recordOtherPerm_AutoLoad = false;
    public $hasOwnerField = false;

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->myPrivateImg = isset($xmlArr["ATTRIBUTES"]["MYPRIVATEIMG"]) ? $xmlArr["ATTRIBUTES"]["MYPRIVATEIMG"] : "{OPENBIZ_RESOURCE_URL}/common/images/icon_data_private.gif";
        $this->mySharedImg = isset($xmlArr["ATTRIBUTES"]["MYSHAREDIMG"]) ? $xmlArr["ATTRIBUTES"]["MYSHAREDIMG"] : "{OPENBIZ_RESOURCE_URL}/common/images/icon_data_shared.gif";
        $this->myAssignedImg = isset($xmlArr["ATTRIBUTES"]["MYASSIGNEDIMG"]) ? $xmlArr["ATTRIBUTES"]["MYASSIGNEDIMG"] : "{OPENBIZ_RESOURCE_URL}/common/images/icon_data_assigned.gif";
        $this->myDistributedImg = isset($xmlArr["ATTRIBUTES"]["MYDISTRIBUTEDIMG"]) ? $xmlArr["ATTRIBUTES"]["MYDISTRIBUTEDIMG"] : "{OPENBIZ_RESOURCE_URL}/common/images/icon_data_distributed.gif";
        $this->groupSharedImg = isset($xmlArr["ATTRIBUTES"]["GROUPSHAREDIMG"]) ? $xmlArr["ATTRIBUTES"]["GROUPSHAREDIMG"] : "{OPENBIZ_RESOURCE_URL}/common/images/icon_data_shared_group.gif";
        $this->otherSharedImg = isset($xmlArr["ATTRIBUTES"]["OTHERSHAREDIMG"]) ? $xmlArr["ATTRIBUTES"]["OTHERSHAREDIMG"] : "{OPENBIZ_RESOURCE_URL}/common/images/icon_data_shared_other.gif";
        $this->defaultImg = isset($xmlArr["ATTRIBUTES"]["DEFAULTIMG"]) ? $xmlArr["ATTRIBUTES"]["DEFAULTIMG"] : "{OPENBIZ_RESOURCE_URL}/common/images/icon_data_shared_other.gif";

        $this->recordCreatorId = isset($xmlArr["ATTRIBUTES"]["CREATORID"]) ? $xmlArr["ATTRIBUTES"]["CREATORID"] : null;
        $this->recordOwnerId = isset($xmlArr["ATTRIBUTES"]["OWNERID"]) ? $xmlArr["ATTRIBUTES"]["OWNERID"] : null;
        $this->recordGroupId = isset($xmlArr["ATTRIBUTES"]["GROUPID"]) ? $xmlArr["ATTRIBUTES"]["GROUPID"] : null;
        $this->recordGroupPerm = isset($xmlArr["ATTRIBUTES"]["GROUPPERM"]) ? $xmlArr["ATTRIBUTES"]["GROUPPERM"] : null;
        $this->recordOtherPerm = isset($xmlArr["ATTRIBUTES"]["OTHERPERM"]) ? $xmlArr["ATTRIBUTES"]["OTHERPERM"] : null;

        $this->recordOwnerId_AutoLoad = isset($xmlArr["ATTRIBUTES"]["OWNERID"]) ? false : true;
        $this->recordGroupId_AutoLoad = isset($xmlArr["ATTRIBUTES"]["GROUPID"]) ? false : true;
        $this->recordGroupPerm_AutoLoad = isset($xmlArr["ATTRIBUTES"]["GROUPPERM"]) ? false : true;
        $this->recordOtherPerm_AutoLoad = isset($xmlArr["ATTRIBUTES"]["OTHERPERM"]) ? false : true;
    }

    public function setValue($value)
    {
        $formObj = $this->getFormObj();
        $rec = $formObj->getActiveRecord();

        if ($this->recordOwnerId_AutoLoad) {
            $this->hasOwnerField = $this->_hasOwnerField();
            if ($this->hasOwnerField) {
                $this->recordOwnerId = $rec['owner_id'];
                $this->recordCreatorId = $rec['create_by'];
            } else {
                $this->recordOwnerId = $rec['create_by'];
            }
        }

        if ($this->recordGroupId_AutoLoad) {
            $this->recordGroupId = $rec['group_id'];
        }

        if ($this->recordGroupPerm_AutoLoad) {
            $this->recordGroupPerm = $rec['group_perm'];
        }

        if ($this->recordOtherPerm_AutoLoad) {
            $this->recordOtherPerm = $rec['other_perm'];
        }
    }

    public function getValue()
    {
        $user_id = Openbizx::$app->getUserProfile("Id");
        $groups = Openbizx::$app->getUserProfile("groups");
        if (!$groups)
            $groups = array();

        $this->hasOwnerField = $this->_hasOwnerField();
        if ($this->hasOwnerField) {

            if ($this->recordOwnerId != $this->recordCreatorId) {
                if ($this->recordOwnerId == $user_id) {
                    $this->value = 4;
                    return $this->value;
                } elseif ($this->recordCreatorId == $user_id) {
                    $this->value = 5;
                    return $this->value;
                }
            }
        }

        if ($user_id == $this->recordOwnerId) {
            if ((int) $this->recordGroupPerm > 0 || (int) $this->recordOtherPerm > 0) {

                $this->value = 1;
            } else {
                $this->value = 0;
            }
        } elseif ($this->recordOtherPerm > 0) {
            $this->value = 3;
        } else {
            foreach ($groups as $group_id) {
                if ($group_id == $this->recordGroupId) {
                    $this->value = 2;
                    break;
                }
            }
        }



        return $this->value;
    }

    /**
     * Render element, according to the mode
     *
     * @return string HTML text
     */
    public function render()
    {
        $style = $this->getStyle();
        $text = $this->getText();
        $id = $this->objectName;
        $func = $this->getFunction();

        switch ($this->getValue()) {
            case "0":
                $image_url = $this->myPrivateImg;
                break;
            case "1":
                $image_url = $this->mySharedImg;
                break;
            case "2":
                $image_url = $this->groupSharedImg;
                break;
            case "3":
                $image_url = $this->otherSharedImg;
                break;
            case "4":
                $image_url = $this->myAssignedImg;
                break;
            case "5":
                $image_url = $this->myDistributedImg;
                break;
            default:
                if ($this->defaultImg == '{OPENBIZ_RESOURCE_URL}/common/images/icon_data_shared_other.gif') {
                    $this->defaultImg = $this->otherSharedImg;
                }
                $image_url = $this->defaultImg;
                break;
        }

        if (preg_match("/\{.*\}/si", $image_url)) {
            $formobj = $this->getFormObj();
            $image_url = Expression::evaluateExpression($image_url, $formobj);
        } else {
            $image_url = Openbizx::$app->getImageUrl() . "/" . $image_url;
        }
        if ($this->width) {
            $width = "width=\"$this->width\"";
        }
        if ($this->link) {
            $link = $this->getLink();
            $target = $this->getTarget();
            $sHTML = "<a   id=\"$id\" href=\"$link\" $target $func $style><img $width src='$image_url' /></a>";
        } else {
            $sHTML = "<img id=\"$id\"  alt=\"" . $text . "\" title=\"" . $text . "\" $width src='$image_url' />";
        }
        return $sHTML;
    }

    private function _hasOwnerField()
    {
        $field = $this->getFormObj()->getDataObj()->getField('owner_id');
        if ($field) {
            return true;
        } else {
            return false;
        }
    }

}