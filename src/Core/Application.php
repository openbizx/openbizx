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
 * @version   $Id: Resource.php 4179 2011-05-26 07:40:53Z rockys $
 */
namespace Openbizx\Core;

/**
 * Application is the base class for all application classes.
 *
 * @author Agus Suhartono <agus.suhartono@gmail.com>
 */
class Application
{

	/**
	 * @var string the root directory of the module.
	 */
	private $_basePath = null;

	/**
	 * Returns the root directory of the application.
	 * It defaults to the directory containing the module class file.
	 * @return string the root directory of the module.
	 */
	public function getBasePath()
	{
		if ($this->_basePath === null) {
			$class = new \ReflectionClass($this);
			$this->_basePath = dirname($class->getFileName());
		}
		return $this->_basePath;
	}

	/**
	 * Sets the root directory of the application.
	 * NOTE: This method can only be invoked at the beginning of the constructor.
	 * @param string $path the root directory of the module. This can be either a directory name or a path alias.
	 * @throws InvalidParamException if the directory does not exist.
	 */
	public function setBasePath($path)
	{
		$path = Yii::getAlias($path);
		$p = realpath($path);
		if ($p !== false && is_dir($p)) {
			$this->_basePath = $p;
		} else {
			throw new InvalidParamException("The directory does not exist: $path");
		}
	}

}
