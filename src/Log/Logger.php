<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Openbizx\Log;

use Openbizx\Object\Object;
use Openbizx\Log\LoggerInterface;

/**
 * Description of Logger
 *
 * @author agus
 */
class Logger extends Object implements LoggerInterface
{


    private $_path;

    public function alert($message, array $context = array())
    {

    }

    public function critical($message, array $context = array())
    {
        
    }

    public function debug($message, array $context = array())
    {

    }

    public function emergency($message, array $context = array())
    {
        
    }

    public function error($message, array $context = array())
    {

    }

    public function info($message, array $context = array())
    {
        
    }

    public function log($level, $message, array $context = array())
    {

    }

    public function notice($message, array $context = array())
    {
        
    }

    public function warning($message, array $context = array())
    {

    }


    /**
     * Get path based on config options
     *
     * @global BizSystem $g_BizSystem
     * @param string $fileName
     * @return string log_path - The path where a log entry should be written
     */
    private function _getPath($fileName = null)
    {
        $level = $this->_level;
        if ($fileName) {
            return $this->getPath() . '/' . $fileName . $this->_extension;
        }
        switch ($this->_org) {
            case 'DATE':
                return $this->getPath() . '/' . date("Y_m_d") . $this->_extension;
            case 'LEVEL':
                $level = $this->_level2filename($level);
                return $this->getPath() . '/' . $level . $this->_extension;
            case 'LEVEL-DATE':
                $level = $this->_level2filename($level);
                //delete old log files
                if ($this->_daystolive > 0) {
                    if (is_array(glob($this->getPath() . '/' . $level . '-*' . $this->_extension))) {
                        foreach (glob($this->getPath() . '/' . $level . '-*' . $this->_extension) as $filename) {
                            $mtime = filemtime($filename);
                            if ((time() - $mtime) >= $this->_daystolive * 86400) {
                                @unlink($filename);
                            }
                        }
                    }
                }
                return $this->getPath() . '/' . $level . '-' . date("Y_m_d") . $this->_extension;
            case 'PROFILE':
                $profile = Openbizx::$app->getUserProfile('USERID');
                if (!$profile) {
                    $profile = 'Guest';
                }
                return $this->getPath() . '/' . $profile . $this->_extension;
            default:
                break;
        }
    }


    public function getPath()
    {
		if ($this->_path === null) {
			$class = new \ReflectionClass($this);
			$this->_path = dirname($class->getFileName());
		}
		return $this->_path;
    }

    public function setPath($path)
    {
		$path = realpath($path);
		if ($path !== false && is_dir($path)) {
			$this->_path = $path;
		} else {
			throw new InvalidParamException("The directory does not exist: $path");
		}
    }


//put your code here
}
