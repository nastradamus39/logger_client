<?php


class HttpLogger {


    private static $user = '';

    private static $key = '';

    /**
     * Default host
     * @var string
     */
    private static $apiBaseUrl = 'http://api.httplog.dev0.pro';

    private static $header = '';

    private static $stack = [];

    private static $timeout = 30;

    private static $enabled = true;

    /**
     * Errors types
     * @var array
     */
    private static $levels = [
        'warning'   => 1,
        'notice'    => 2,
        'fatal'     => 3,
        'error'     => 4,
        'info'      => 5
    ];

    public static function init($user, $key, $apiUrl = null)
    {
        error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

        self::$user = $user;
        self::$key = $key;

        if(!is_null($apiUrl)) self::$apiBaseUrl = $apiUrl;

        self::$header = "Content-Type: application/json\r\n".
            "USER: {$user}\r\n".
            "UKEY: {$key}\r\n";

        set_error_handler([__CLASS__,'errorHandler']);
        set_exception_handler([__CLASS__,'exceptionHandler']);
        register_shutdown_function([__CLASS__,'shutdown']);
    }

    public static function warning($message)
    {
        $message = new message($message, self::$levels['warning']);
        array_push(self::$stack, $message);
        self::_log($message);
    }

    public static function notice($message)
    {
        $message = new message($message, self::$levels['notice']);
        array_push(self::$stack, $message);
        self::_log($message);
    }

    public static function fatal($message)
    {
        $message = new message($message, self::$levels['fatal']);
        array_push(self::$stack, $message);
        self::_log($message);
    }

    public static function error($message)
    {
        $message = new message($message, self::$levels['error']);
        array_push(self::$stack, $message);
        self::_log($message);
    }

    public static function info($message)
    {
        $message = new message($message, self::$levels['info']);
        array_push(self::$stack, $message);
        self::_log($message);
    }

    private static function _log(message $message)
    {
        if(self::$enabled){
            $streamContext = [
                'http' => [
                    'method'  => 'PUT',
                    'header'  => self::$header,
                    'content' => $message->toJson(),
                    'timeout' => self::$timeout
                ]
            ];
            file_get_contents(self::$apiBaseUrl, false, stream_context_create($streamContext));
        }
    }

    public static function shutdown()
    {
        $error = error_get_last();
        if($error['message']){
            $message = new message($error['message'], self::$levels['fatal']);
            $message->setFile($error['file']);
            $message->setLine($error['line']);
        }else{
            $message = new message("Exit", self::$levels['notice']);
        }

        self::_log($message);
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $msg = "[{$errno}] {$errstr} in {$errfile} on {$errline}.";

        switch ($errno) {
            case E_USER_ERROR:
                self::error($msg);
                break;

            case E_USER_WARNING:
                self::warning($msg);
                break;

            case E_USER_NOTICE:
                self::notice($msg);
                break;

            default:
                self::warning($msg);
                break;
        }

        return true;
    }

    public static function exceptionHandler(Exception $e)
    {
        self::error($e->getMessage());
    }

    public static function enable()
    {
        self::$enabled = true;
    }

    public static function disable()
    {
        self::$enabled = false;
    }

}

class message {


    private $messageStr;

    private $level;

    private $file;

    private $line;

    private $date;

    private static $order = 0;

    private $stackTrace;

    public function __construct($message, $level)
    {
        $backtrace = debug_backtrace();

        $this->messageStr  = strval($message);
        $this->level        = intval($level);
        $this->file         = isset($backtrace[1]['file']) ? $backtrace[1]['file'] : 'unknown file';
        $this->line         = isset($backtrace[1]['line']) ? $backtrace[1]['line'] : 999999999999;
        $this->date         = time();
        $this->stackTrace   = json_encode($backtrace);

        self::$order++;
    }

    public function message(){ return $this->messageStr; }
    public function level(){ return $this->level; }
    public function file(){ return $this->file; }
    public function line(){ return $this->line; }
    public function date(){ return $this->date; }

    public function setFile($file){ $this->file = ( $file ? $file : __FILE__); }
    public function setLine($line){ $this->line = ( $line ? $line : 999999999999); }


    public function toJson()
    {
        return json_encode([
            "message"       => $this->messageStr,
            "level"         => $this->level,
            "file"          => $this->file,
            "line"          => $this->line,
            "date"          => $this->date,
            "order"         => self::$order,
            "stacktrace"    => $this->stackTrace
        ]);
    }

}