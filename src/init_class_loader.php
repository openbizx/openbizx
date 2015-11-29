<?php

use Openbizx\ClassLoader;

include_once("ClassLoader.php");

spl_autoload_register(['\Openbizx\ClassLoader', 'autoload']);

// loadCoreClassMap
//$coreClassMap = include( __DIR__ . DIRECTORY_SEPARATOR . 'autoload_classmap.php' );
//ClassLoader::registerClassMap($coreClassMap);

// loadVendorAutoLoadClassMap
//$othersClassMap = include( realpath(__DIR__ . '/../../autoload_classmap_selected.php') );
//ClassLoader::registerClassMap($othersClassMap);


//define('ZF2_PATH', realpath(__DIR__ . '/../../Zend2/library'));
//require_once ZF2_PATH . '/Zend/Loader/StandardAutoloader.php';
//$loader = new Zend\Loader\StandardAutoloader(array(
//    'autoregister_zf' => true,
//));
//$loader->register();
