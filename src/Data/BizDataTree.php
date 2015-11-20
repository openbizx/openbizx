<?php

/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.data
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: BizDataTree.php 2585 2010-11-23 18:58:17Z jixian2003 $
 */

namespace Openbizx\Data;

use Openbizx\Data\BizDataObj;
use Openbizx\Data\NodeRecord;

/**
 * BizDataTree class provide query for tree structured records
 *
 * @package openbiz.bin.data
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @access public
 */
class BizDataTree extends BizDataObj {

    protected $rootNodes;

    /**
     * Deep of tree
     * @var int
     */
    protected $depth;

    /**
     * Global search rule
     * @var string
     */
    protected $globalSearchRule;

    /**
     * Fetch records in tree structure
     *
     * @return <type>
     */
    public function fetchTree($rootSearchRule, $depth, $globalSearchRule = "") {
        $this->depth = $depth;
        $this->globalSearchRule = $globalSearchRule;

        // query on given search rule
        $searchRule = "(" . $rootSearchRule . ")";
        if ($globalSearchRule != "")
            $searchRule .= " AND (" . $globalSearchRule . ")";
        $recordList = $this->directFetch($searchRule);
        if (!$recordList) {
            $this->rootNodes = array();
            return;
        }
        foreach ($recordList as $rec) {
            $this->rootNodes[] = new NodeRecord($rec);
        }
        if ($this->depth <= 1)
            return $this->rootNodes;
        if (is_array($this->rootNodes)) {
            foreach ($this->rootNodes as $node) {
                $this->_getChildrenNodes($node, 1);
            }
        }
        return $this->rootNodes;
    }

    /**
     * Fetch node path
     *
     * @param string $nodeSearchRule
     * @param array $pathArray
     * @return <type>
     */
    public function fetchNodePath($nodeSearchRule, &$pathArray) {
        $recordList = $this->directFetch($nodeSearchRule);
        if (count($recordList) >= 1) {

            if ($recordList[0]['PId'] != '0') {
                $searchRule = "[Id]='" . $recordList[0]['PId'] . "'";
                $this->fetchNodePath($searchRule, $pathArray);
            }
            $nodes = new \NodeRecord($recordList[0]);
            array_push($pathArray, $nodes);
            return $pathArray;
        }
    }

    /**
     * List all children records of a given record
     *
     * @return void
     */
    private function _getChildrenNodes(&$node, $depth) {
        $pid = $node->recordId;

        $searchRule = "[PId]='$pid'";
        if ($this->globalSearchRule != "")
            $searchRule .= " AND " . $this->globalSearchRule;
        $recordList = $this->directFetch($searchRule);

        foreach ($recordList as $rec) {
            $node->childNodes[] = new NodeRecord($rec);
        }

        // reach leave node
        if ($node->childNodes == null) {
            return;
        }

        $depth++;
        // reach given depth
        if ($depth >= $this->depth) {
            return;
        } else {
            foreach ($node->childNodes as $node_c) {
                $this->_getChildrenNodes($node_c, $depth);
            }
        }
    }

}
