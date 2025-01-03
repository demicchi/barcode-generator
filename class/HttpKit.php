<?php

namespace StudioDemmys\barcode;

use StudioDemmys\barcode\type\ErrorLevel;
use StudioDemmys\barcode\type\HttpStatusCode;

class HttpKit
{
    protected bool $connection_closed = false;
    
    public function sendOK(bool $close_connection = true, string $content = "") : void
    {
        Logging::debug('Send 200 OK ' . (($close_connection) ? 'immediately' : 'later'));
        $this->sendStatusCodeImmediately(HttpStatusCode::S200, $close_connection, $content);
    }
    
    public function sendForbidden(bool $close_connection = true, string $content = "") : void
    {
        Logging::debug('Send 403 Forbidden ' . (($close_connection) ? 'immediately' : 'later'));
        $this->sendStatusCodeImmediately(HttpStatusCode::C403, $close_connection, $content);
    }
    
    public function sendBadRequest(bool $close_connection = true, string $content = "") : void
    {
        Logging::debug('Send 400 Bad Request ' . (($close_connection) ? 'immediately' : 'later'));
        $this->sendStatusCodeImmediately(HttpStatusCode::C400, $close_connection, $content);
    }
    
    public function sendStatusCodeImmediately(HttpStatusCode $status_code, bool $close_connection = true,
                                              string $content = "") : void
    {
        Logging::debug("Try to send the status code ({$status_code->name})");
        // Cleaning buffer if exists
        while (@ob_end_clean()) ;
        
        $status_message = HttpStatusCode::getHeaderString($status_code);
        
        // Set status code
        $sapi_name = substr(php_sapi_name(), 0, 3);
        if ($sapi_name === 'cgi' || $sapi_name === 'fpm') {
            header("Status: {$status_message}");
        } else {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
            header("{$protocol} {$status_message}");
        }
        
        if ($close_connection) {
            // Set "Connection: close" and "Content-Encoding: none" to send the status code immediately
            Logging::debug('  try to send the status code immediately -- step1. send headers');
            header("Connection: close");
            header("Content-Encoding: none");
            ignore_user_abort(true);
            header("Content-Length: " . strlen($content));
        }
        
        // Disable buffering for nginx fastcgi (nginx>=1.5.6)
        // Only effective for the first nginx hosting fastcgi if the responses are to pass through multiple proxies
        // You should manually disable gzip and proxy_buffering for the rest of nginx proxies or be sure to propagate
        // an X-Accel-Buffering header
        header('X-Accel-Buffering: no');
        
        if (!empty($content)) {
            echo $content;
        }
        
        // Flushing ob_buffer if exists
        while (@ob_end_flush()) ;
        
        // Flushing system buffer
        flush();
        
        // Cleaning buffer if exists
        while (@ob_end_clean()) ;
        
        if ($close_connection) {
            // Immediately close the request and send the headers above, while the actual fastcgi process is alive
            // to do some complex tasks
            // This function call is needed when no body are sent just like in this case (Content-Length is 0)
            // because nginx seems to return the headers when the first content body is flushed
            // no matter how many headers are set and how many times flush() functions are called,
            // which means the status code above will never be sent in this case that no body should exist
            // unless fastcgi_finish_request() are called or the fastcgi process are finished
            Logging::debug('  try to send the status code immediately -- step2. fastcgi_finish_request()');
            fastcgi_finish_request();
            $this->connection_closed = true;
            Logging::debug('Sent ' . $status_message);
        } else {
            Logging::debug($status_message . ' will be sent after the body is constructed');
        }
    }
    
    public function closeConnectionImmediatelyWithoutBody() : void
    {
        if ($this->connection_closed) {
            throw new Exception(ErrorLevel::Error, "E_HTTPKIT_CONNECTION_CLOSED");
        }
        Logging::debug('Try to close the connection immediately');
        header("Connection: close");
        header("Content-Encoding: none");
        ignore_user_abort(true);
        header("Content-Length: 0");
        // Flushing ob_buffer if exists
        while (@ob_end_flush()) ;
        // Flushing system buffer
        flush();
        // Cleaning buffer if exists
        while (@ob_end_clean()) ;
        fastcgi_finish_request();
        Logging::debug('The connection should be closed now');
    }
    
}