<?php
/**
 * Openbizx Framework
 *
 * LICENSE
 *
 * This source file is subject to the BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @package   openbiz.bin.service
 * @copyright Copyright (c) 2005-2011, Rocky Swen
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: reportService.php 2553 2010-11-21 08:36:48Z mr_a_ton $
 */

namespace Openbizx\Service;

use Openbizx\Object\MetaObject;
use Openbizx\Helpers\TemplateHelper;

/**
 * reportService class is the plug-in service of generate report for BizDataobj
 *
 * @package   openbiz.bin.service
 * @author    Rocky Swen
 * @copyright Copyright (c) 2003-2009, Rocky Swen
 * @access    public
 */
class reportService extends MetaObject
{
    public $targetReportPath; // = "D:\\Tomcat5\\webapps\\birt-viewer\\report\\";
    public $rptTemplate; // = "dataobj.rptdesign.tpl";
    public $birtViewer; // = "http://localhost:8080/birt-viewer";

    /**
     * Initialize reportService with xml array metadata
     *
     * @param array $xmlArr
     * @return void
     */
    function __construct(&$xmlArr)
    {
        $this->readMetadata($xmlArr);
    }

    /**
     * Read array meta data, and store to meta object
     *
     * @param array $xmlArr
     * @return void
     */
    protected function readMetadata(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->targetReportPath = isset($xmlArr["PLUGINSERVICE"]["ATTRIBUTES"]["TARGETREPORTPATH"]) ? $xmlArr["PLUGINSERVICE"]["ATTRIBUTES"]["TARGETREPORTPATH"] : null;
        $this->rptTemplate = isset($xmlArr["PLUGINSERVICE"]["ATTRIBUTES"]["REPORTTEMPLATE"]) ? $xmlArr["PLUGINSERVICE"]["ATTRIBUTES"]["REPORTTEMPLATE"] : null;
        $this->birtViewer = isset($xmlArr["PLUGINSERVICE"]["ATTRIBUTES"]["BIRTVIEWER"]) ? $xmlArr["PLUGINSERVICE"]["ATTRIBUTES"]["BIRTVIEWER"] : null;
    }

    /**
     * render the report output
     *
     * @param string $objName object name which is the bizform name
     * @return void
     */
    public function render($objName)
    {
        // get the current UI bizobj
        $bizform = Openbizx::getObject($objName);    // get the existing bizform object
        $bizobj = $bizform->getDataObj();

        $h=opendir($this->targetReportPath);
        if (!$h)
        {
            echo "cannot read dir ".$this->targetReportPath;
            exit;
        }
        // create a tmp csv file for hold the data, then feed csv file to report engine
        $uid = $this->getUniqueString();
        $tmpfname = $this->targetReportPath . $uid . ".csv";
        //echo "csv file is at $tmpfname.<br>";
        $fp = fopen($tmpfname, 'w');

        $keyList = $bizform->recordRow->GetSortControlKeys();
        $fieldNames = array();
        foreach($keyList as $key)
        {
            $fieldNames[] = $bizform->GetControl($key)->bizFieldName;
        }
        fputcsv($fp, $fieldNames);

        $recList = $bizobj->directFetch();
        foreach ($recList as $recArray)
        {
            unset($fieldValues);
            $fieldValues = array();
            $line = "";
            foreach($keyList as $key)
            {
                $fieldValues[] = $recArray[$bizform->GetControl($key)->bizFieldName];
            }
            fputcsv($fp, $fieldValues);
        }

        fclose($fp);

        $i = 0;
        foreach($keyList as $key)
        {
            $rpt_fields[$i]["name"] = $bizform->GetControl($key)->bizFieldName;
            $rpt_fields[$i]["type"] = $bizobj->getField($rpt_fields[$i]["name"])->type;
            $i++;
        }

        // dataobj.rptdesign.tpl
        // $rpt_data_dir, $rpt_title, $rpt_csv_file, $rpt_fields[](name,type)
        $smarty = TemplateHelper::getSmartyTemplate();
        $smarty->assign("rpt_data_dir", $this->targetReportPath);
        $smarty->assign("rpt_title", $bizform->title);
        $smarty->assign("rpt_csv_file", basename($tmpfname));
        $smarty->assign("rpt_fields", $rpt_fields);
        $reportContent = $smarty->fetch($this->rptTemplate);

        $tmpRptDsgn = $this->targetReportPath . $uid . ".rptdesign";
        //echo "temp rpt design file is at $tmpRptDsgn.<br>";
        $fp = fopen($tmpRptDsgn, 'w');
        fwrite($fp, $reportContent);
        fclose($fp);

        ob_clean();
        $designFileName = $uid . ".rptdesign";
        $content = "<div style='font-family:Arial; font-size:12px; background-color:#FCFCFC;'>";
        $content .= "Reports can be viewed as ";
        $content .= "<li><a href='".$this->birtViewer."/run?__report=report\\$designFileName' target='__blank'>HTML report</a></li>";
        $content .= "<li><a href='".$this->birtViewer."/run?__report=report\\$designFileName&__format=pdf' target='__blank'>PDF report</a></li>";
        $content .= "<li><a href='".$this->birtViewer."/frameset?__report=report\\$designFileName' target='__blank'>Interactive report</a></li>";
        $content .= "</div>";

        echo $content;
        exit;
    }

    /**
     * Clear files on specified directory
     *
     * @param string $dir
     * @param number $seconds
     */
    public function cleanFiles($dir, $seconds)
    {
        //Delete temporary files
        $currentTime = time();
        $dirHandle = opendir($dir);
        while($file = readdir($dirHandle))
        {
            $path=$dir.'/'.$file;
            if($currentTime - filemtime($path) > $seconds)
                unlink($path);
        }
        closedir($dirHandle);
    }

    /**
     * Get unique string by time and MD5
     *
     * @return string
     */
    public function getUniqueString()
    {
        $mdy = date("mdy");
        $hms = date("His");
        $rightnow = $mdy.$hms;

        return md5($rightnow);
    }
}
