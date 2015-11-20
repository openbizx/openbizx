<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Openbizx\Helpers;

use Openbizx\Openbizx;
use Openbizx\I18n\I18n;

/**
 * Description of MessageHelper
 *
 * @author agus
 */
class MessageHelper
{

    /**
     * Load message from file
     *
     * @param string $messageFile
     * @return mixed
     */
    public static function loadMessage($messageFile, $packageName = "")
    {

        if (!isset($messageFile)) {
            return null;
        }
        if ($messageFile === "") {
            return null;
        }

        //if (isset($messageFile) && $messageFile != "") {
        // message file location order
        // 1. OPENBIZ_APP_MESSAGE_PATH."/".$messageFile
        // 2. Openbizx::$app->getModulePath() . "/$moduleName/message/" . $messageFile;
        // 3. CORE_Openbiz::$app->getModulePath() . "/$moduleName/message/" . $messageFile;
        // OPENBIZ_APP_PATH / OPENBIZ_APP_MESSAGE_PATH : OPENBIZ_APP_PATH / messages
        if (is_file(OPENBIZ_APP_MESSAGE_PATH . "/" . $messageFile)) {
            return parse_ini_file(OPENBIZ_APP_MESSAGE_PATH . "/" . $messageFile);
        } else if (is_file(Openbizx::$app->getModulePath() . "/" . $messageFile)) {
            return parse_ini_file(Openbizx::$app->getModulePath() . "/" . $messageFile);
        } else {
            if (isset($packageName) && $packageName != "") {
                $dirs = explode('.', $packageName);
                $moduleName = $dirs[0];
                $msgFile = Openbizx::$app->getModulePath() . "/$moduleName/message/" . $messageFile;
                if (is_file($msgFile)) {
                    return parse_ini_file($msgFile);
                } else {
                    $errmsg = self::getMessage("SYS_ERROR_INVALID_MSGFILE", array($msgFile));
                    trigger_error($errmsg, E_USER_ERROR);
                }
            } else {
                $errmsg = self::getMessage("SYS_ERROR_INVALID_MSGFILE", array(OPENBIZ_APP_MESSAGE_PATH . "/" . $messageFile));
                trigger_error($errmsg, E_USER_ERROR);
            }
        }
    }

    /**
     * Get message from CONSTANT, translate and format it
     * @param string $msgId ID if constant
     * @param array $params parameter for format (use vsprintf)
     * @return string
     */
    public static function getMessage($msgId, $params = array())
    {
        $message = constant($msgId);
        if (isset($message)) {
            $message = I18n::t($message, $msgId, 'system');
            $result = vsprintf($message, $params);
        }
        return $result;
    }

}
