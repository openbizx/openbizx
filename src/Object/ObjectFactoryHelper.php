<?php

namespace Openbizx\Object;

use Openbizx\Openbizx;
use Openbizx\Helpers\XMLParser;

/**
 * Helper class for ObjectFactory
 *
 * @author agus
 */
class ObjectFactoryHelper
{

    private static $_xmlFileList = [];
    private static $_xmlArrayList = [];
    
    /**
     * Get Xml file with path
     *
     * Search the object metedata file as objname+.xml in metedata directories
     * name convension: demo.BOEvent points to metadata/demo/BOEvent.xml
     * new in 2.2.3, demo.BOEvent can point to modules/demo/BOEvent.xml
     *
     * @param string $objectName xml object name
     * @return string xml config file path
     * */
    public static function getXmlFileWithPath($objectName)
    {
        if (isset(self::$_xmlFileList[$objectName])) {
            return self::$_xmlFileList[$objectName];
        }
        // replace "." with "/"
        $xmlFile = str_replace(".", "/", $objectName);
        $xmlFile .= ".xml";
        $xmlFile = "/" . $xmlFile;

        // search in modules directory first
        $xmlFileList[] = Openbizx::$app->getModulePath() . $xmlFile;
        $xmlFileList[] = OPENBIZ_APP_PATH . $xmlFile;
        $xmlFileList[] = OPENBIZ_META . $xmlFile;

        foreach ($xmlFileList as $xmlFileItem) {
            if (file_exists($xmlFileItem)) {
                self::$_xmlFileList[$objectName] = $xmlFileItem;
                return $xmlFileItem;
            }
        }
        self::$_xmlFileList[$objectName] = null;
        return null;
    }


    /**
     * Get Xml Array.
     * If xml file has been compiled (has .cmp), load the cmp file as array;
     * otherwise, compile the .xml to .cmp first new 2.2.3, .cmp files
     * will be created in app/cache/metadata_cmp directory. replace '/' with '_'
     * for example, /module/demo/BOEvent.xml has cmp file as _module_demo_BOEvent.xml
     *
     * @param string $xmlFile
     * @return array
     **/
    public static function &getXmlArray($xmlFile)
    {	
        if (isset(self::$_xmlArrayList[$xmlFile])) {
            return self::$_xmlArrayList[$xmlFile];
        }

        $objXmlFileName = $xmlFile;
        //echo "getXmlArray($xmlFile)\n";
        //$objCmpFileName = dirname($objXmlFileName) . "/__cmp/" . basename($objXmlFileName, "xml") . ".cmp";
        //$_crc32 = sprintf('%08X', crc32(dirname($objXmlFileName)));
        $_hash = strtoupper(md5(dirname($objXmlFileName)));
        $objCmpFileName = OPENBIZ_CACHE_METADATA_PATH . '/' . $_hash . '_'
                . basename($objXmlFileName, "xml") . "cmp";

        $xmlArr = null;
        //$cacheKey = substr($objXmlFileName, strlen(META_PATH)+1);
        $cacheKey = $objXmlFileName;
        $findInCache = false;
        if (file_exists($objCmpFileName) && (filemtime($objCmpFileName) > filemtime($objXmlFileName))) {
            // search in cache first
            if (!$xmlArr && extension_loaded('apc')) {
                if (($xmlArr = apc_fetch($cacheKey)) != null) {
                    $findInCache = true;
                }
            }
            if (!$xmlArr) {
                $content_array = file($objCmpFileName);
                $xmlArr = unserialize(implode("", $content_array));
            }
        } else {
            $parser = new XMLParser($objXmlFileName, 'file', 1);
            $xmlArr = $parser->getTree();
            //echo var_dump($xmlArr);
            // simple validate the xml array
            $root_keys = array_keys($xmlArr);
            $root_key = $root_keys[0];
            if (!$root_key || $root_key == "") {
                trigger_error("Metadata file parsing error for file $objXmlFileName. Please double check your metadata xml file again.", E_USER_ERROR);
            }
            $xmlArrStr = serialize($xmlArr);
			
            if (!file_exists(dirname($objCmpFileName))) {
                mkdir(dirname($objCmpFileName));
            }
            $cmp_file = fopen($objCmpFileName, 'w') or die("can't open cmp file to write");
            fwrite($cmp_file, $xmlArrStr) or die("can't write to the cmp file");
            fclose($cmp_file);
        }
        // save to cache to avoid file processing overhead
        if (!$findInCache && extension_loaded('apc')) {
            apc_store($cacheKey, $xmlArr);
        }
        self::$_xmlArrayList[$xmlFile] = $xmlArr;
        return $xmlArr;
    }

}
