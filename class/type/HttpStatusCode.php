<?php

namespace StudioDemmys\barcode\type;

enum HttpStatusCode : string
{
    case I100 = "Continue";
    case I101 = "Switching Protocols";
    case S200 = "OK";
    case S201 = "Created";
    case S202 = "Accepted";
    case S203 = "Non-Authoritative Information";
    case S204 = "No Content";
    case S205 = "Reset Content";
    case S206 = "Partial Content";
    case R300 = "Multiple Choices";
    case R301 = "Moved Permanently";
    case R302 = "Found";
    case R303 = "See Other";
    case R304 = "Not Modified";
    case R305 = "Use Proxy";
    case R306 = "Unused";
    case R307 = "Temporary Redirect";
    case R308 = "Permanent Redirect";
    case C400 = "Bad Request";
    case C401 = "Unauthorized";
    case C402 = "Payment Required";
    case C403 = "Forbidden";
    case C404 = "Not Found";
    case C405 = "Method Not Allowed";
    case C406 = "Not Acceptable";
    case C407 = "Proxy Authentication Required";
    case C408 = "Request Timeout";
    case C409 = "Conflict";
    case C410 = "Gone";
    case C411 = "Length Required";
    case C412 = "Precondition Failed";
    case C413 = "Content Too Large";
    case C414 = "URI Too Long";
    case C415 = "Unsupported Media Type";
    case C416 = "Range Not Satisfiable";
    case C417 = "Expectation Failed";
    case C418 = "I'm a teapot";
    case C421 = "Misdirected Request";
    case C422 = "Unprocessable Content";
    case C426 = "Upgrade Required";
    case E500 = "Internal Server Error";
    case E501 = "Not Implemented";
    case E502 = "Bad Gateway";
    case E503 = "Service Unavailable";
    case E504 = "Gateway Timeout";
    case E505 = "HTTP Version Not Supported";
    
    public static function getNumber(HttpStatusCode $http_status_code): int
    {
        return intval(substr($http_status_code->name, 1, 3));
    }
    
    public static function getMessage(HttpStatusCode $http_status_code, int $variation = 0): string
    {
        switch ($http_status_code) {
            case self::C413:
                return match ($variation) {
                    1 => 'Payload Too Large',
                    2 => 'Request Entity Too Large',
                    default => self::C413->value,
                };
            case self::C414:
                return match ($variation) {
                    1 => 'Request-URI Too Long',
                    default => self::C414->value,
                };
            case self::C416:
                return match ($variation) {
                    1 => 'Requested Range Not Satisfiable',
                    default => self::C416->value,
                };
        }
        return $http_status_code->value;
    }
    
    public static function getHttpStatusCode(int $code): ?HttpStatusCode
    {
        foreach (self::cases() as $case) {
            if ($code == intval(substr($case->name, 1, 3)))
                return $case;
        }
        return null;
    }
    
    public static function getHeaderString(HttpStatusCode $http_status_code, int $variation = 0): string
    {
        return sprintf('%03u', self::getNumber($http_status_code)) . ' ' . self::getMessage($http_status_code, $variation);
    }
}
