<?php


class HttpLogger {

    private static $client;

    private static $user = '';

    private static $key = '';

    private static $apiBaseUrl = '';

    private static $stack = [];

    private static $order = 0;

    public static function init()
    {
        set_error_handler([__CLASS__,'errorHandler']);
        set_exception_handler([__CLASS__,'exceptionHandler']);
    }

    public static function log($level, $message)
    {
        $log = [
            'type' => $level,
            'message' => $message,
            'mktime' => time(),
            'timezone' => date_default_timezone_get(),
            'order' => self::$order
        ];

        array_push(self::$stack, $log);
        self::$order++;
        self::_log();
    }

    private static function _log()
    {
        // ...
    }


    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_USER_ERROR:
                break;

            case E_USER_WARNING:
                break;

            case E_USER_NOTICE:
                break;

            default:
                break;
        }

        return true;
    }

    public static function exceptionHandler(\Exception $e)
    {
        // ...
    }

}