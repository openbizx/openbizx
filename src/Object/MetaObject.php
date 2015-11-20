<?php

/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id$
 */

namespace Openbizx\Object;

use Openbizx\Openbizx;
use Openbizx\ClassLoader;

/**
 * MetaObject is the base class of all derived metadata-driven classes
 *
 * @package   openbiz.bin
 * @author    Rocky Swen
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 */
abstract class MetaObject //extends BizObject
{

    /**
     * Name of meta-object
     *
     * @var string
     */
    public $objectName;

    /**
     * Class name of meta-object
     *
     * @var string
     */
    public $className;

    /**
     * Package of meta-object
     *
     * @var string
     */
    public $package;

    /**
     * Description of meta-object
     *
     * @var string
     */
    public $objectDescription;
    public $access;
    public $stateless;

    function __construct(&$xmlArr)
    {

    }

    //function __destruct() {}

    /**
     * Read meta data
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetadata(&$xmlArr)
    {
        $rootKeys = array_keys($xmlArr);
        $rootKey = $rootKeys[0];
        if ($rootKey != "ATTRIBUTES") {
            $this->objectName = isset($xmlArr[$rootKey]["ATTRIBUTES"]["NAME"]) ? $xmlArr[$rootKey]["ATTRIBUTES"]["NAME"] : null;
            $this->objectDescription = isset($xmlArr[$rootKey]["ATTRIBUTES"]["DESCRIPTION"]) ? $xmlArr[$rootKey]["ATTRIBUTES"]["DESCRIPTION"] : null;
            $this->package = isset($xmlArr[$rootKey]["ATTRIBUTES"]["PACKAGE"]) ? $xmlArr[$rootKey]["ATTRIBUTES"]["PACKAGE"] : null;
            $this->className = isset($xmlArr[$rootKey]["ATTRIBUTES"]["CLASS"]) ? $xmlArr[$rootKey]["ATTRIBUTES"]["CLASS"] : null;
            $this->access = isset($xmlArr[$rootKey]["ATTRIBUTES"]["ACCESS"]) ? $xmlArr[$rootKey]["ATTRIBUTES"]["ACCESS"] : null;
        } else {
            $this->objectName = isset($xmlArr["ATTRIBUTES"]["NAME"]) ? $xmlArr["ATTRIBUTES"]["NAME"] : null;
            $this->objectDescription = isset($xmlArr["ATTRIBUTES"]["DESCRIPTION"]) ? $xmlArr["ATTRIBUTES"]["DESCRIPTION"] : null;
            $this->package = isset($xmlArr["ATTRIBUTES"]["PACKAGE"]) ? $xmlArr["ATTRIBUTES"]["PACKAGE"] : null;
            $this->className = isset($xmlArr["ATTRIBUTES"]["CLASS"]) ? $xmlArr["ATTRIBUTES"]["CLASS"] : null;
            $this->access = isset($xmlArr["ATTRIBUTES"]["ACCESS"]) ? $xmlArr["ATTRIBUTES"]["ACCESS"] : null;
        }
    }

    public function getModuleName($name)
    {
        return substr($name, 0, intval(strpos($name, '.')));
    }

    /**
     * Read metadata collection
     *
     * @param array $xmlArr
     * @param array $metaList
     * @return void
     */
    protected function readMetaCollection(&$xmlArr, &$metaList)
    {
        if (!$xmlArr) {
            $metaList = null;
            return;
        }
        if (isset($xmlArr["ATTRIBUTES"]))
            $metaList[] = $xmlArr;
        else
            $metaList = $xmlArr;
    }

    /**
     * Set Prefix Package
     *
     * @param string $name
     * @return string     *
     * @todo NOTE: this is helper method, need to refactor
     */
    protected function prefixPackage($name)
    {
        // no package prefix as package.object, add it
        if ($name && !strpos($name, ".") && ($this->package)) {
            $name = $this->package . "." . $name;
        }
        return $name;
    }

    /**
     * Get property
     *
     * @param string $propertyName
     * @return mixed
     *
     * @todo Not safe, because can access private and protected variable. Need enhancment or delete
     */
    public function getProperty($propertyName)
    {
        // TODO: really like this?
        if (isset($this->$propertyName))
            return $this->$propertyName;
        return null;
    }

    /**
     * Check is allow access?
     *
     * @global BizSystem $g_BizSystem
     * @param <type> $access
     * @return <type>
     */
    public function allowAccess($access = null)
    {
        if (CLI) {
            return OPENBIZ_ALLOW;
        }
        if (!$access) {
            $access = $this->access;
        }
        if ($access) {
            return Openbizx::$app->allowUserAccess($access);
        }
        return OPENBIZ_ALLOW;
    }

    protected function getElementObject(&$xmlArr, $defaultClassName, $parentObj = null)
    {
        // find the class attribute
        $className = isset($xmlArr["ATTRIBUTES"]['CLASS']) ? $xmlArr["ATTRIBUTES"]['CLASS'] : $defaultClassName;

        if ((bool) strpos($className, ".")) {
            $a_package_name = explode(".", $className);
            $className = array_pop($a_package_name);
            $clsLoaded = ClassLoader::loadMetadataClass($className, implode(".", $a_package_name));
            if (!$clsLoaded) {
                trigger_error("Cannot find the load class $className", E_USER_ERROR);
            }
        }
        //echo "classname is $className\n";
        $obj = new $className($xmlArr, $parentObj);
        return $obj;
    }

}
