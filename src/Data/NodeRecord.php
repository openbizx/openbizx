<?php

namespace Openbizx\Data;

/**
 * NodeRecord class, for tree structure
 *
 * @package openbiz.bin.data
 * @author Rocky Swen
 * @copyright Copyright (c) 2005-2009
 * @since 1.2
 * @todo need to move to other package (tool, base, etc?)
 * @access public
 *
 */
class NodeRecord
{
    public $recordId = "";
    public $recordParentId = "";
    public $childNodes = null;
    public $record;

    /**
     * Initialize Node
     *
     * @param array $rec
     * @return void
     */
    function __construct($rec)
    {
        $this->recordId = $rec['Id'];
        $this->recordParentId = $rec['PId'];
        $this->record = $rec;
    }
}