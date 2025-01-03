<?php

namespace StudioDemmys\barcode;

if (!defined('__DEMMYS_UNIQUE_ID'))
    define("__DEMMYS_UNIQUE_ID", bin2hex(random_bytes(4)));

// fundamental classes and default configurations
// The loading order is important! DO NOT modify!
require_once dirname(__FILE__)."/class/Common.php";
require_once dirname(__FILE__)."/class/Config.php";

Config::loadConfig();
Config::getConfigOrSetIfUndefined("language", "en");
Config::getConfigOrSetIfUndefined("logging/level", "debug");
Config::getConfigOrSetIfUndefined("logging/file", "./barcode.log");

require_once dirname(__FILE__)."/class/type/ErrorLevel.php";
require_once dirname(__FILE__)."/class/Logging.php";
require_once dirname(__FILE__)."/class/Exception.php";

// composer libraries
require_once dirname(__FILE__)."/vendor/autoload.php";
