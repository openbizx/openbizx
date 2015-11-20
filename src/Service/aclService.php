<?PHP
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.service
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: aclService.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

define ('OPENBIZ_DENY', 0);
define ('OPENBIZ_ALLOW', 1);
define ('OPENBIZ_ALLOW_OWNER', 2);

namespace Openbizx\Service;

/**
 * aclService class is the plug-in service to manage ACL
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 */
class aclService
{
    // TODO: conver it to AclService
    // TODO: save the data $userAccesses in session
    
    /**
     *
     * @param <type> $resAction
     * @param string $module module name
     * @return number OPENBIZ_ALLOW, OPENBIZ_DENY, OPENBIZ_ALLOW_OWNER
     */
    public static function allowAccess($resAction, $module="")
    {
        return OPENBIZ_ALLOW;
    }
}