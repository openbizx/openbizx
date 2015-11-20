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
 * @version   $Id$
 */

use Openbizx\Openbizx;

include_once("default_consts.php");

register_shutdown_function("bizsystem_shutdown");

function bizsystem_shutdown()
{
    if (isset(Openbizx::$app)) {
        Openbizx::$app->getSessionContext()->saveSessionObjects();
    }
}

//
/**
 * include system message file
 * @todo system message is part of application object, refactore it
 */
include_once(OPENBIZ_PATH . "/messages/system.msg");


// error handling 
error_reporting(E_ALL ^ (E_NOTICE | E_STRICT));

// if use user defined error handling function, all errors are reported to the function
//$default_error_handler = set_error_handler("userErrorHandler");
set_error_handler(array('\Openbizx\Core\ErrorHandler','errorHandler'));
//ErrorHandler::ErrorHandler($errno, $errmsg, $filename, $linenum, $vars);
//$default_exception_handler = set_exception_handler('userExceptionHandler');
set_exception_handler(array('\Openbizx\Core\ErrorHandler','exceptionHandler'));
//ErrorHandler::ExceptionHandler($exc);

// set DOCUMENT_ROOT
setDocumentRoot();


/*
 * Set DOCUMENT_ROOT in case the server doesn't have DOCUMENT_ROOT setting (e.g. IIS). 
 * Reference from http://fyneworks.blogspot.com/2007/08/php-documentroot-in-iis-windows-servers.html
 */
function setDocumentRoot()
{
    if (!isset($_SERVER['DOCUMENT_ROOT'])) {
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
        };
    };
    if (!isset($_SERVER['DOCUMENT_ROOT'])) {
        if (isset($_SERVER['PATH_TRANSLATED'])) {
            $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
        };
    };
}

/**
 *  formatted output given variable by using print_r() function
 *
 *  @author Garbin
 *  @param  any
 *  @return void
 */
function dump($arr, $debug = false)
{
    if ($debug) {
        $debug_fun = 'debug_print_backtrace();';
    }
    echo '<pre>';
    array_walk(func_get_args(), create_function('&$item, $key', 'print_r($item);' . $debug_fun . ''));
    echo '</pre>';
    exit();
}

/**
 *  formatted output given variable by using var_dump() function
 *
 *  @author Garbin
 *  @param  any
 *  @return void
 */
function vdump($arr, $debug = false)
{
    if ($debug) {
        $debug_fun = 'debug_print_backtrace();';
    }
    echo '<pre>';
    array_walk(func_get_args(), create_function('&$item, $key', 'var_dump($item);' . $debug_fun . ''));
    echo '</pre>';
    exit();
}
