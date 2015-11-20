<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Openbizx\Web;

use Openbizx\Web\Request;

/**
 * Description of UrlManager
 *
 * @author agus
 */
class UrlManager
{

    private $_request;

    public function __construct($request)
    {
        $this->_request = $request;
    }

    function getViewName($urlArr)
    {
        $url_path = $urlArr[1];
        if (!$url_path) {
            return gotoDefaultView($urlArr[0]);
        }
        if (preg_match_all("/([a-z]*)_?/si", $url_path, $match)) {
            $view_name = "";
            $match = $match[1];
            foreach ($match as $part) {
                if ($part) {
                    $part = ucwords($part); //ucwords(strtolower($part));
                    $view_name .= $part;
                }
            }
            $view_name.="View";
        }
        return $view_name;
    }

    /**
     * Parser Uri request
     * @param Request $request
     */
    public function parserRequestUri($request)
    {
        $url = $request->getPathUri();
        if ($url) {
            $urlArr = preg_split("/\//si", $url);
            if (preg_match("/^[a-z_]*$/si", $urlArr[1])) {
                // http://localhost/?/ModuleName/ViewName/
                $module_name = $urlArr[0];
                $view_name = $request->pathNameToViewName($urlArr, 1);
                $uriParams = $request->getUriParameters($urlArr, 1);
            } elseif (preg_match("/^[a-z_]*$/si", $urlArr[0])) {
                // http://localhost/?/ViewName/
                $module_name = '';
                $view_name = $request->pathNameToViewName($urlArr, 0);
                $uriParams = $request->getUriParameters($urlArr, 0);
            } else {
                throw new Exception();/** @todo Change Exception class more specific. */
            }
        } else {
                $module_name = '';
                $view_name = '';
                $uriParams = [];
        }
        return array(
            'module' => $module_name,
            'shortView' => $view_name,
            'uriParams' => $uriParams,
        );
    }
}