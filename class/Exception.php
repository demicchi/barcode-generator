<?php

namespace StudioDemmys\barcode;

use StudioDemmys\barcode\type\ErrorLevel;

class Exception extends \Exception
{
    const ERROR_FILE = "./config/error.yml";
    const ERROR_FILE_LOCALIZED = "./config/error.%s.yml";
    
    public function __construct(ErrorLevel $error_level, string $code = "E_DEFAULT", string $message = '',
                                ?\Throwable $previous = null)
    {
        $error_file_path = Common::getAbsolutePath(self::ERROR_FILE);
        $error_file_localized_path = Common::getAbsolutePath(self::ERROR_FILE_LOCALIZED);
        
        $error = yaml_parse_file($error_file_path);
        if ($error === false) {
            $error_message = "Failed to load " . self::ERROR_FILE . " while trying to throw an exception. -- " . $message;
            Logging::error($error_message);
            throw new \Exception("[FATAL] " . $error_message);
        }
        if (!isset($error[$code])) {
            $error_message = "Undefined error code ({$code}). -- " . $message;
            Logging::error($error_message);
            throw new \Exception("[FATAL] " . $error_message);
        }
        
        $error_message = $error[$code];
        
        $error_localized_file = sprintf($error_file_localized_path, Config::getConfig("language"));
        if (is_readable($error_localized_file)) {
            $error_message_localized = yaml_parse_file($error_localized_file);
            if ($error_message_localized !== false && isset($error_message_localized[$code])) {
                $error_message = $error_message_localized[$code];
            }
        }
        
        $error_message = "[$code] " . $error_message;
        if ($message != "")
            $error_message .= " -- " . $message;
        
        switch($error_level) {
            case ErrorLevel::Debug:
                Logging::debug($error_message, true);
                $error_message = "[debug]" . $error_message;
                break;
            case ErrorLevel::Info:
                Logging::info($error_message, true);
                $error_message = "[info]" . $error_message;
                break;
            case ErrorLevel::Warn:
                Logging::warn($error_message, true);
                $error_message = "[warn]" . $error_message;
                break;
            case ErrorLevel::Error:
            default:
                Logging::error($error_message, true);
            $error_message = "[error]" . $error_message;
                break;
        }
        
        parent::__construct($error_message, null, $previous);
    }
}
