<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Openbizx\Web;

//namespace Openbizx\web;
/**
 * Description of UserAgent
 *
 * @author agus
 */
class UserAgent
{

    private $_userAgent;
    private $_style;
    private $_device;
    private $_isTouch = false;

    //put your code here

    public function init()
    {
        $device = '';
        $style = '';
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return;
        }
        if (stristr($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
            $device = "ipad";
            $style = "touch";
        } else if (stristr($_SERVER['HTTP_USER_AGENT'], 'iphone') 
                || strstr($_SERVER['HTTP_USER_AGENT'], 'ipod')) {
            $device = "iphone";
            $style = "touch";
        } else if (stristr($_SERVER['HTTP_USER_AGENT'], 'blackberry')) {
            $device = "blackberry";
            $style = "touch";
        } else if (stristr($_SERVER['HTTP_USER_AGENT'], 'android')) {
            $device = "android";
            $style = "touch";
        }

        $this->_userAgent = $device;
        $this->_style = $style;
        if ($device != '' && $style == 'touch') {
            $this->_isTouch = true;
            $this->_device = 'mobile';
        } else {
            $this->_isTouch = false;
            $this->_device = 'desktop';
        }
    }

    public function getUserAgent()
    {
        if ($this->_userAgent !== null) {
            $this->init();
        }
        return $this->_userAgent;
    }

    public function getStyle()
    {
        if ($this->_style !== null) {
            $this->init();
        }
        return $this->_style;
    }


    public function isTouch()
    {
        if ($this->_isTouch !== null) {
            $this->init();
        }
        return $this->_isTouch;
    }

    public function getDevice()
    {
        if ($this->_device !== null) {
            $this->init();
        }
        return $this->_device;
    }

}
