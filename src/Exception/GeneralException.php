<?php

/**
 * Openbizx Framework (http://openbizx.github.io/)
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @copyright Copyright (c) 2015 Mpu Technology
 * @license   http://opensource.org/licenses/BSD-3-Clause
 * @link      http://openbizx.github.io/
 */

namespace Openbizx\Exception;

/**
 * Exception represents a generic exception for all purposes.
 * This class replace  \Exception for more powerfull error handling.
 * @link https://github.com/openbizx/openbizx/blob/master/src/Exception/GeneralException.php
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 * @since 1.0
 */
class GeneralException extends \Exception implements ExceptionInterface
{

    /**
     * Get user-friendly name of this exception
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Exception';
    }

}