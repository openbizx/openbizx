<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Openbizx\Core;

/**
 * Arrayable should be implemented by classes that need to be represented in array format.
 * Inspired by yii\base\Arrayable.
 *
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 * @since 1.0
 */
interface Arrayable
{
	/**
	 * Converts the object into an array.
	 * @return array the array representation of this object
	 */
	public function toArray();
}
