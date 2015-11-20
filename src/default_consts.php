<?php

/* * **************************************************************************
  openbiz core path
 * ************************************************************************** */
//define('OPENBIZ_PATH', 'absolute_dir/Openbizx');
if (!defined('OPENBIZ_PATH')) {
    define('OPENBIZ_PATH', dirname(dirname(__FILE__)));
}
if (!defined('OPENBIZ_BIN')) {
    define('OPENBIZ_BIN', OPENBIZ_PATH . "/bin/");
}
if (!defined('OPENBIZ_META')) {
    define('OPENBIZ_META', OPENBIZ_PATH . "/metadata/");
}

/* * **************************************************************************
  third party library path
 * ************************************************************************** */
// Smarty package
if (!defined('SMARTY_DIR')) {
    define('SMARTY_DIR', realpath(OPENBIZ_PATH . "/../Smarty/libs").'/');
}

//echo 'SMARTY_DIR ' .SMARTY_DIR;
//exit;
/****************************************************************************
  application services
 ****************************************************************************/
if (!defined('AUTH_SERVICE')) {
    define('AUTH_SERVICE', "service.authService");
}
if (!defined('ACCESS_SERVICE')) {
    define('ACCESS_SERVICE', "service.accessService");
}
if (!defined('ACL_SERVICE')) {
    define('ACL_SERVICE', "service.aclService");
}
if (!defined('PROFILE_SERVICE')) {
    define('PROFILE_SERVICE', "service.profileService");
}
if (!defined('LOG_SERVICE')) {
    define('LOG_SERVICE', "service.logService");
}
if (!defined('EXCEL_SERVICE')) {
    define('EXCEL_SERVICE', "service.excelService");
}
if (!defined('OPENBIZ_PDF_SERVICE')) {
    define('OPENBIZ_PDF_SERVICE', "service.pdfService");
}
if (!defined('IO_SERVICE')) {
    define('IO_SERVICE', "service.ioService");
}
if (!defined('EMAIL_SERVICE')) {
    define('EMAIL_SERVICE', "service.emailService");
}
if (!defined('DOTRIGGER_SERVICE')) {
    define('DOTRIGGER_SERVICE', "service.doTriggerService");
}
if (!defined('GENID_SERVICE')) {
    define('GENID_SERVICE', "service.genIdService");
}
if (!defined('VALIDATE_SERVICE')) {
    define('VALIDATE_SERVICE', "service.validateService");
}
if (!defined('QUERY_SERVICE')) {
    define('QUERY_SERVICE', "service.queryService");
}
if (!defined('SECURITY_SERVICE')) {
    define('SECURITY_SERVICE', "service.securityService");
}
if (!defined('OPENBIZ_EVENTLOG_SERVICE')) {
    define('OPENBIZ_EVENTLOG_SERVICE', "service.eventlogService");
}
if (!defined('CACHE_SERVICE')) {
    define('CACHE_SERVICE', "service.cacheService");
}
if (!defined('CRYPT_SERVICE')) {
    define('CRYPT_SERVICE', "service.cryptService");
}
if (!defined('LOCALEINFO_SERVICE')) {
    define('LOCALEINFO_SERVICE', "service.localeInfoService");
}

/* whether print debug infomation or not */
if (!defined('OPENBIZ_DEBUG')) {
    define("OPENBIZ_DEBUG", 1);
}
if (!defined('PROFILING')) {
    define("PROFILING", 1);
}

/* check whether user logged in */
//if(!defined('CHECKUSER')) define("CHECKUSER", "N");
/* session timeout seconds */
if (!defined('OPENBIZ_TIMEOUT')) {
    define("OPENBIZ_TIMEOUT", -1);  // -1 means never timeout.
}

// defined \Zend framework library home as ZEND_FRWK_HOME
define('ZEND_FRWK_HOME', OPENBIZ_PATH . "/../");
// add zend framework to include path
//set_include_path(get_include_path() . PATH_SEPARATOR . ZEND_FRWK_HOME);

/* Popup Suffix for Modal or Popup Windows */
define('Popup_Suffix', "_popupx_");

if (isset($_SERVER['SERVER_NAME'])) {
    define('CLI', 0);
    define('nl', "<br/>");
} else {
    define('CLI', 1);
    define('nl', "\n");
}