<?php

namespace StudioDemmys\barcode;

$__demmys_global_config = null;

class Config
{
    const CONFIG_FILE = "./config/config.yml";
    
    protected function __construct()
    {
    }

    public static function loadConfig()
    {
        global $__demmys_global_config;
        $config_path = Common::getAbsolutePath(self::CONFIG_FILE);
        $__demmys_global_config = yaml_parse_file($config_path);
        if ($__demmys_global_config === false) {
            $__demmys_global_config = null;
            throw new \Exception("[FATAL] failed to load " . $config_path . ".");
        }
    }
    
    public static function getConfig(string $key)
    {
        global $__demmys_global_config;
        $key_array = explode("/", $key);
        if (empty($key_array))
            throw new \Exception("[FATAL](getConfig) config key is empty.");
        $target_config =& $__demmys_global_config;
        foreach ($key_array as $key_part) {
            if (isset($target_config[$key_part])) {
                $target_config =& $target_config[$key_part];
            } else {
                return null;
            }
        }
        return $target_config;
    }
    
    public static function setConfig(string $key, mixed $value = null)
    {
        global $__demmys_global_config;
        $key_array = explode("/", $key);
        if (empty($key_array))
            throw new \Exception("[FATAL](setConfig) config key is empty.");
        $target_config =& $__demmys_global_config;
        foreach ($key_array as $key_part) {
            if (!isset($target_config[$key_part])) {
                $target_config[$key_part] = null;
            }
            $target_config =& $target_config[$key_part];
        }
        $target_config = $value;
    }
    
    public static function getConfigOrSetIfUndefined(string $key, mixed $default_value = null)
    {
        $value = self::getConfig($key);
        if (is_null($value)) {
            self::setConfig($key, $default_value);
            return $default_value;
        }
        return $value;
    }
}