<?php

namespace StudioDemmys\barcode;

class Common
{
    protected function __construct()
    {
    }
    
    public static function getAbsolutePath(string $path): string
    {
        if (str_starts_with($path, "/") || preg_match( '@^[a-zA-Z]:(\\\\|/)@', $path )) {
            return $path;
        } else {
            return dirname(__FILE__) . "/../" . $path;
        }
    }
    
    public static function sanitizeUserInput(array|string $text): string
    {
        if (is_array($text)) {
            $text = implode("", $text);
        }
        $text = htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
        return preg_replace('/[\p{Cc}\p{Cf}\p{Z}]/u', '', $text);
    }
}