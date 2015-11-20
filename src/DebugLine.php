<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DebugLine
 *
 * @author agus
 */
class DebugLine
{
    public static $isShow = true;
    //put your code here

    public static function show($message)
    {
        if ( self::$isShow ) {
            echo $message . '<br />';
        }
    }
}
