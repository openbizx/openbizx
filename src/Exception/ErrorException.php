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
 * ErrorException represents a PHP error.
 * This class replace \ErrorException for more powerfull error handling.
 * @link https://github.com/openbizx/openbizx/blob/master/src/Exception/ErrorException.php
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 * @since 1.0
 */
class ErrorException extends \ErrorException implements ExceptionInterface
{

    /**
     * Get user-friendly name of this exception
     * @return string the user-friendly name of this exception
     */    
    public function getName()
    {
        $names = [
            E_ERROR => 'PHP Fatal Error',
            E_PARSE => 'PHP Parse Error',
            E_CORE_ERROR => 'PHP Core Error',
            E_COMPILE_ERROR => 'PHP Compile Error',
            E_USER_ERROR => 'PHP User Error',
            E_WARNING => 'PHP Warning',
            E_CORE_WARNING => 'PHP Core Warning',
            E_COMPILE_WARNING => 'PHP Compile Warning',
            E_USER_WARNING => 'PHP User Warning',
            E_STRICT => 'PHP Strict Warning',
            E_NOTICE => 'PHP Notice',
            E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
            E_DEPRECATED => 'PHP Deprecated Warning',
        ];

        return isset($names[$this->getCode()]) ? $names[$this->getCode()] : 'Error';
    }
}
