<?PHP

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
 * @license   http://www.opensource.org/licenses/bsd-license.php     BSD
 * @link      http://www.phpopenbiz.org/
 * @version   $Id: Expression.php 5348 2013-04-02 03:58:31Z agus.suhartono@gmail.com $
 */

namespace Openbizx\Core;

use Openbizx\Openbizx;
/**
 * Expression - class Expression is the base class of evaluating simple expression
 *
 * @package   openbiz.bin

 * * @author    Rocky Swen <rocky@phpopenbiz.org>
 * @copyright Copyright (c) 2005-2009, Rocky Swen
 * @access    public
 * // version 1.2 ??//
 */
class Expression
{

    static protected $services = array(
        'validate' => 'service.validateService',
        'query' => 'service.queryService',
        'vis' => 'service.visibilityService',
        'preference' => 'service.preferenceService',
        'util' => 'service.utilService',
    );
    static protected $expContainers = array(
        '{fx}' => '{/fx}',
        '{tx}' => '{/tx}',
        '{' => '}'
    );

    function __construct(&$xmlArr)
    {
    }

    /**
     * Replase field expression with value from $bizObj
     *
     * @param string $expression
     * @param BizDataObj $bizObj
     * @return mixed
     */
    protected static function replaceFieldsExpr($expression, $bizObj)
    {
        $script = "";
        $start = 0;

        // replace [field] with field value
        while (true) {
            $pos0 = strpos($expression, "[", $start);
            $pos1 = strpos($expression, "]", $start);
            if ($pos0 === false) {
                $script .= substr($expression, $start);
                break;
            }
            if ($pos0 >= 0 && $pos1 > $pos0) {
                $script .= substr($expression, $start, $pos0 - $start);
                $start = $pos1 + 1;
                $fieldName = substr($expression, $pos0 + 1, $pos1 - $pos0 - 1);
                // get field value
                $fieldValue = $bizObj->getField($fieldName)->value;
                if ($fieldValue == null) {
                    $fieldValue = $bizObj->getFieldValue($fieldName);
                }

                if ($fieldValue !== null) {
                    $script .= $fieldValue;
                } else {
                    //$script .= substr($expression, $pos0, $pos1 - $pos0);
                    //return "fail to evaluate $expression";
                    return "";
                }
            } elseif ($pos0 >= 0 && $pos1 <= $pos0) {
                break;
            }
        }
        return $script;
    }

    /**
     * Replace elements expression with value from $formObj
     *
     * @param string $expression
     * @param EasyForm $formObj
     * @return mixed
     */
    protected static function replaceElementsExpr($expression, $formObj)
    {
        $script = "";
        $start = 0;

        // replace [field] with field value
        while (true) {
            $pos0 = strpos($expression, "[", $start);
            $pos1 = strpos($expression, "]", $start);
            if ($pos0 === false) {
                $script .= substr($expression, $start);
                break;
            }
            if ($pos0 >= 0 && $pos1 > $pos0) {
                $script .= substr($expression, $start, $pos0 - $start);
                $start = $pos1 + 1;
                $elementName = substr($expression, $pos0 + 1, $pos1 - $pos0 - 1);
                // get field value
                $element = $formObj->getElement($elementName);
                if ($element) {
                    $fldval = $element->getValue();
                } else {
                    $fldval = null;
                }
                if ($fldval !== null) {
                    $script .= $fldval;
                } else {
                    //$script .= substr($expression, $pos0, $pos1 - $pos0);
                    //return "fail to evaluate $expression";
                    return $expression;  // return the original expression once it can't find element
                }
            } elseif ($pos0 >= 0 && $pos1 <= $pos0) {
                break;
            }
        }
        return $script;
    }

    /**
     * Replace var expression
     * @objname:property, @objname:field[fldname].property, @objname:control[ctrlname].property
     * @:prop = @thisobjname:prop
     *
     * @global BizSystem $g_BizSystem
     * @param string $expression
     * @param object $object
     * @return string
     */
    protected static function replaceVarExpr($expression, $object)
    {
        // replace @objname:property to GetObject()->getProperty(property)
        while (true) {
            // TODO: one clause must be separated by whitespace
            //modified by jixian for support package full name of a object
            //e.g : shared.objects.compaines.objCompany:Field[Id].Value
            $pattern = "/@([[a-zA-Z0-9_\.]*):([a-zA-Z0-9_\.\[\]]+)/";
            if (!preg_match($pattern, $expression, $matches)) {
                break;
            }
            $macro = $matches[0];
            $objName = $matches[1];
            $propExpr = $matches[2];
            $obj = null;
            if ($objName == "profile") {  // @profile:attribute is reserved
                $profileAttribute = Openbizx::$app->getUserProfile($propExpr);
                $expression = str_replace($macro, $profileAttribute, $expression);
                continue;
            }
            if ($objName == "home") {  // @home:url is reserved
                switch ($propExpr) {
                    case "url":
                        $value = "'" . OPENBIZ_APP_INDEX_URL . "'";
                        break;
                    case "base_url":
                        $value = "'" . OPENBIZ_APP_URL . "'";
                        break;
                }
                $expression = str_replace($macro, $value, $expression);
                continue;
            } elseif (in_array($objName, array_keys(Expression::$services))) {
                // reserved keywords
                $body = $expression;
                $objFunc = '@' . $objName . ':' . $propExpr;
                $posStart = strpos($body, $objFunc);
                $beforeString = substr($body, 0, $posStart);
                $paramStart = strpos($body, $objFunc . '(') + strlen($objFunc . '(');
                $paramEnd = strpos($body, ')', $paramStart);
                $paramLen = $paramEnd - $paramStart;
                $function = $propExpr;
                $paramString = substr($body, $paramStart, $paramLen);
                $restString = substr($body, $paramEnd + 1);

                $paramString = Expression::evaluateExpression('{' . $paramString . '}', $object);
                $serviceName = Expression::$services[$objName];
                $serviceObj = Openbizx::getService($serviceName);

                $params = explode(',', $paramString);
                for ($i = 0; $i < count($params); $i++) {
                    $params[$i] = trim($params[$i]);
                }
                $val_result = call_user_func_array(array($serviceObj, $function), $params);
                return $beforeString . $val_result . $restString;
            } elseif ($objName == "" || $objName == "this") {
                $obj = $object;
                $body = $expression;
                $objFunc = '@' . $objName . ':' . $propExpr;
                $posStart = strpos($body, $objFunc);
                $beforeString = substr($body, 0, $posStart);

                if (strpos($body, '(') > 0 && substr($expression, 0, 2) == '@:') {
                    $paramStart = strpos($body, $objFunc . '(') + strlen($objFunc . '(');
                    $paramEnd = strpos($body, ')', $paramStart);
                    $paramLen = $paramEnd - $paramStart;
                    $function = $propExpr;
                    $paramString = substr($body, $paramStart, $paramLen);
                    $restString = substr($body, $paramEnd + 1);

                    $params = explode(',', $paramString); // bug fix
                    for ($i = 0; $i < count($params); $i++) {
                        $params[$i] = trim($params[$i]);
                    }

                    if (!is_array($params)) {
                        $params = array();
                    }
                    if (method_exists($obj, $function)) {
                        $val_result = call_user_func_array(array($obj, $function), $params);
                        return $beforeString . $val_result . $restString;
                    }
                }
            } else {
                $obj = Openbizx::getObject($objName);
            }

            if ($obj == null) {
                throw new \Exception("Wrong expression syntax " . $expression . ", cannot get object " . $objName);
            }

            $pos = strpos($propExpr, ".");

            $paramStart = strpos($expression, $objFunc . '(');
            if ($pos > 0) { // in case of @objname:field[fldname].property
                $property1 = substr($propExpr, 0, $pos);
                $property2 = substr($propExpr, $pos + 1);
                $propertyObj = $obj->getProperty($property1);
                if ($propertyObj == null) {
                    $propertyObj = $obj->getDataObj()->getProperty($property1);
                    if ($propertyObj == null) {
                        throw new Exception("Wrong expression syntax " . $expression . ", cannot get property object " . $property1 . " of object " . $objName);
                    } else {
                        $val = $propertyObj->getProperty($property2);
                    }
                }
                $val = $propertyObj->getProperty($property2);
            } else {
                // in case of @objname:property            	
                $val = $obj->getProperty($propExpr);
            }
            if ($val === null) {
                $val = "";
            }
            if (is_string($val)) {
                $val = "'$val'";
            }
            $expression = str_replace($macro, $val, $expression);
        }
        return $expression;
    }

    /**
     * Replace macro expression
     * replace macro @var:key to $userProfile[$key]
     * NOTE: NYU - not yet used
     * 
     * @global BizSystem $g_BizSystem
     * @param string $expression
     * @return string
     */
    protected static function replaceMacrosExpr($expression)
    {
        // replace macro @var:key to $userProfile[$key]
        while (true) {
            $pattern = "/@(\w+):(\w+)/";
            if (!preg_match($pattern, $expression, $matches)) {
                break;
            }
            $macro = $matches[0];
            $macro_var = $matches[1];
            $macro_key = $matches[2];
            $val = self::getMacroValue($macro_var, $macro_key);
            if (!$val) {
                $val = "";
            }
            // throw error
            $expression = str_replace($macro, $val, $expression);
        }
        return $expression;
    }

    /**
     * Evaluate simple expression
     * expression is combination of text, simple expressiones and field variables
     * simple expression - {...}
     * field variable - [field name]
     * expression samples: text1{[field1]*10}text2{function1([field2],'a')}text3
     *
     * @objname:property, @objname:field[fldname].property, @objname:control[ctrlname].property
     * @:prop = @thisobjname:prop
     * [fldname] = @thisobjname:field[fldname].value
     * @demo.BOEvent:Name, @:Name
     * @demo.BOEvent:Field[EventName].Column, @demo.BOEvent:Field[EventName].Value
     * @demo.FMEvent:Control[evt_name].FieldName, @demo.FMEvent:Control[evt_name].Value
     * [EventName] is @demo.BOEvent:Field[EventName].Value in BOEvent.xml
     *
     * @param string $expression - simple expression supported by the openbiz
     * @param object $object
     * @return mixed
     **/
    public static function evaluateExpression($expression, $object)
    {
        // TODO: check if it's "\[", "\]", "\{" or "\}"
        $script = "";
        $start = 0;

        if (!self::isExpression($expression)) {
            return $expression;
        }
        if (self::isCurrentObject($expression)) {
            return $object;
        }

        // evaluate the expression between {}
        while (true) {
            list($tag, $openTagPos, $closeTagPos) = self::getNextContainerPos($expression, $start);
            if ($openTagPos === false) {
                if (substr($expression, $start)) {
                    $script .= substr($expression, $start);
                }
                break;
            }
            if ($openTagPos >= 0 && $closeTagPos > $openTagPos) {
                $script .= substr($expression, $start, $openTagPos - $start);
                $start = $closeTagPos + strlen(self::$expContainers[$tag]);

                $section = substr(
                        $expression,
                        $openTagPos + strlen($tag),
                        $closeTagPos - $openTagPos - strlen($tag)
                );

                $_section = $section;
                if ($object) {
                    //Openbizx::$app->getLog()->log(LOG_DEBUG, "EXPRESSION", "###expression 1: ".$section."");
                    $section = Expression::replaceVarExpr($section, $object);  // replace variable expr;
                    //Openbizx::$app->getLog()->log(LOG_DEBUG, "EXPRESSION", "###expression 2: ".$section.""); 
                    if ($_section == $section) {
                        if ((is_subclass_of($object, "Openbizx\Data\BizDataObj") || get_class($object) == "Openbizx\Data\BizDataObj") AND strstr($section, '[')) {
                            $section = Expression::replaceFieldsExpr($section, $object);
                        }  // replace [field] with its value

                        if ((is_subclass_of($object, "Openbizx\Easy\EasyForm") || get_class($object) == "Openbizx\Easy\EasyForm") AND strstr($section, '[')) {
                            $section = Expression::replaceElementsExpr($section, $object);
                        }  // replace [field] with its value
                    }
                }
                if ($section === false) {
                    $script = ($script == '') ? $section : ($script . $section);
                }
                if ($section != null AND trim($section) != "" AND $section != false) {
                    $ret = null;

                    //$section = str_replace($section, '\', '\\');
                    //echo $section . '<br />';
                    //if (Expression::eval_syntax("\$ret = $section;"))
                    if (($tag == '{fx}' || $tag == '{') && Expression::eval_syntax("\$ret = $section;")) {
                        eval("\$ret = $section;");
                    }
                    if ($ret === null) {
                        $ret = $section;
                    }
                    $script = ($script == '') ? $ret : ($script . $ret);
                    unset($ret);
                }
            } elseif ($openTagPos >= 0 && $closeTagPos <= $openTagPos) {
                break;
            }
        }
        return $script;
    }

    /**
     * Get container position from $start
     * @param string $expression
     * @param int $start
     * @return array array contain Tag, openTagPos and closeTagPos;
     */
    protected static function getNextContainerPos($expression, &$start)
    {
        foreach (self::$expContainers as $tag => $closeTag) {
            $openTagPos = strpos($expression, $tag, $start);
            $closeTagPos = strpos($expression, $closeTag, $start);
            if ($openTagPos === false) {
                continue;
            }
            if ($openTagPos >= 0 && $closeTagPos > $openTagPos) {
                return array($tag, $openTagPos, $closeTagPos);
            }
            if ($openTagPos >= 0 && $closeTagPos <= $openTagPos) {
                trigger_error("Incorrect Expression - no matching end tag $closeTag for $tag.", E_USER_ERROR);
            }
        }
        return array(null, false, false);
    }

    /**
     * Check expression for syntax errors just before eval() function
     * If the expression fails, do not eval the funciton.  Return DEBUG error in logs
     *
     * @param string $code - expression text
     * @return boolean
     * */
    public static function eval_syntax($code)
    {
        $b = 0;

        foreach (token_get_all($code) as $token) {
            if ('{' == $token) {
                ++$b;
            } else if ('}' == $token) {
                --$b;
            }
        }

        // Unbalanced braces would break the eval below
        if ($b) {
            return false;
        } else {
            ob_start(); // Catch potential parse error messages
            // if(preg_match("/.*?\= '.*?'/si",$code)){
            //if(!preg_match("/,/si",$code) && !preg_match("/\//si",$code)){
            //if( !preg_match("/\//si",$code)){
            $r = eval('if(0){' . $code . '}'); // Put $code in a dead code sandbox to prevent its execution
            //}else{
            //	return false;
            //}
            $error = ob_get_contents();
            if ($r === false) {
                //trigger_error("EVAL: $code ".$error, E_USER_ERROR);
                //Openbizx::$app->getLog()->log(LOG_ERR, "ERROR", "EVAL: $code. ".$error);
            }
            ob_end_clean();

            return (false !== $r);
        }
    }

    /**
     * Evaluate macro, this method can only be used to get profile in 2.0
     * For example, @macro_var:macro_key. i.e. @profile:ROLE
     *
     * @param string $var, macro name
     * @param string $key, macro key
     * @return string
     */
    public static function getMacroValue($var, $key)
    {
        if ($var == "profile") {
            return Openbizx::$app->getUserProfile($key);
        }
        return null;
    }

    /**
     * Check if the expression is expression.
     * @param type $expression
     */
    public static function isExpression($expression)
    {
        return !(strpos($expression, "{") === false);
    }

    /**
     * Check if the expression is expression.
     * @param type $expression
     */
    protected static function isCurrentObject($expression)
    {
        return ($expression == "{@}");
    }
}
