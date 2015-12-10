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

namespace Openbiz\Exception;

/**
 * ExitException represents a normal termination of an application.
 * 
 * Adapted from Yii Framework by Qiang Que
 * 
 * @link https://github.com/openbizx/openbizx/blob/master/src/Exception/ExitException.php
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 * @since 2.0
 */
class ExitException extends \Exception implements ExceptionInterface
{
    /**
     * @var integer the exit status code
     */
    public $statusCode;

    /**
     * Constructor.
     * @param integer $status [optional] the exit status code
     * @param string $message [optional] The Exception message to throw.
     * @param integer $code [optional] The Exception code.
     * @param \Exception $previous [optional] The previous exception used for the exception chaining.
     */
    public function __construct($status = 0, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->statusCode = $status;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get user-friendly name of this exception
     * @return string the user-friendly name of this exception
     */    
    public function getName() {
        return 'Exit Exception';
    }
}
