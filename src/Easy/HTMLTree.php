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
 * @version   $Id: HTMLTree.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Easy;

use Openbizx\Object\MetaObject; 
use Openbizx\Object\UIControlInterface;

/**
 * HTMLTree class is the base class of HTML tree
 *
 * @package openbiz.bin.easy
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @since 1.2
 * @access public
 */
class HTMLTree extends MetaObject implements UIControlInterface
{
    protected $nodesXml = null;

    /**
     * Initialize HTMLTree with xml array
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
    }

    /**
     * Read Metadata from xml array
     *
     * @param array $xmlArr
     */
    protected function readMetadata(&$xmlArr)
    {
        $this->objectName = $xmlArr["TREE"]["ATTRIBUTES"]["NAME"];
        $this->package = $xmlArr["TREE"]["ATTRIBUTES"]["PACKAGE"];
        $this->className = $xmlArr["TREE"]["ATTRIBUTES"]["CLASS"];

        $this->nodesXml = $xmlArr["TREE"]["NODE"];
    }

    /**
     * Render HTML Tree
     *
     * @return string html content of the tree
     */
    public function render()
    {
        // preload images
        $sHTML = "<script language=\"JavaScript\">\n".
                " minus = new Image();\n minus.src = \"".Openbizx::$app->getImageUrl()."/minus.gif\";\n".
                " plus = new Image();\n plus.src = \"".Openbizx::$app->getImageUrl()."/plus.gif\";\n".
                "</script>\n";

        // list all views and highlight the current view
        $sHTML .= "<ul class='expanded'>\n";
        $sHTML .= $this->renderNodeItems($this->nodesXml);
        $sHTML .= "</ul>";
        return $sHTML;
    }

    /**
     * Render the html tree
     *
     * @return string html content of the tree
     */
    protected function renderNodeItems(&$nodeItemArray)
    {
        $sHTML = "";
        if (isset($nodeItemArray["ATTRIBUTES"]))
        {
            $sHTML .= $this->renderSingleNodeItem($nodeItemArray);
        }
        else
        {
            foreach ($nodeItemArray as $nodeItem)
            {
                $sHTML .= $this->renderSingleNodeItem($nodeItem);
            }
        }
        return $sHTML;
    }

    /**
     * Render single node item
     *
     * @param array $nodeItem
     * @return <type>
     */
    protected function renderSingleNodeItem(&$nodeItem)
    {
        $url = $nodeItem["ATTRIBUTES"]["URL"];
        $caption = $this->translate($nodeItem["ATTRIBUTES"]["CAPTION"]);
        $target = $nodeItem["ATTRIBUTES"]["TARGET"];
        //$img = $nodeItem["ATTRIBUTES"]["IMAGE"];
        if ($nodeItem["NODE"]) {
            $image = "<img src='" . Openbizx::$app->getImageUrl() . "/plus.gif' class='collapsed' onclick='mouseClickHandler(this)'>";
        } else {
            $image = "<img src='" . Openbizx::$app->getImageUrl() . "/topic.gif'>";
        }

        if ($target) {
            if ($url) {
                $sHTML .= "<li class='tree'>$image <a href=\"" . $url . "\" target='$target'>" . $caption . "</a>";
            } else {
                $sHTML .= "<li class='tree'>$image $caption";
            }
        } elseif ($url) {
            $sHTML .= "<li class='tree'>$image <a href=\"" . $url . "\">" . $caption . "</a>";
        } else {
            $sHTML .= "<li class='tree'>$image $caption";
        }
        if ($nodeItem["NODE"])
        {
            $sHTML .= "\n<ul class='collapsed'>\n";
            $sHTML .= $this->renderNodeItems($nodeItem["NODE"]);
            $sHTML .= "</ul>";
        }
        $sHTML .= "</li>\n";
        return $sHTML;
    }

    /**
     * Rerender the menu
     *
     * @return string html content of the menu
     */
    public function rerender()
    {
        return $this->render();
    }
    
    protected function translate($caption)
    {
    	$module = $this->getModuleName($this->objectName);
   		return I18n::t($caption, $this->getTransKey(caption), $module);
    }
    
    protected function getTransKey($name)
    {
    	$shortFormName = substr($this->objectName,intval(strrpos($this->objectName,'.'))+1);
    	return strtoupper($shortFormName.'_'.$name);
    }
}
