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
 * InvalidCallException represents an exception caused by calling a method in a wrong way. * 
 * 
 * @link https://github.com/openbizx/openbizx/blob/master/src/Exception/BadMethodCallException .php
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 * @since 1.0
 */
class BadMethodCallException extends \BadMethodCallException implements ExceptionInterface
{
    /**
     * Get user-friendly name of this exception
     * @return string the user-friendly name of this exception
     */    
    public function getName()
	{
		return 'Bad Method Call Exception';
	}
}