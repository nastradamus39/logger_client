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
         file_get_contents(self::$apiBaseUrl, false, stream_context_create(array(
            'http' => array(
                'method'  => 'PUT',
                'header'  => self::$header,
                'content' => $message->toJson(),
                'timeout' => self::$timeout
            )
        )));
    }

    public static function shutdown()
    {
        self::fatal("Fatal error...");
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_USER_ERROR:

                $msg = "Fatal error [{$errno}] {$errstr}<br/>";
                $msg .= "On string {$errline} in file {$errfile}<br/>";
                self::error(new message(self::$levels['error'], $msg));
                break;

            case E_USER_WARNING:

                $msg = "Warning - [{$errno}] on {$errstr}";
                self::warning(new message(self::$levels['warning'], $msg));
                break;

            case E_USER_NOTICE:

                $msg = "Notice - [{$errno}] on {$errstr}";
                self::notice(new message(self::$levels['warning'], $msg));
                break;

            default:

                $msg = "Unknow error - [{$errno}] on {$errstr}";
                self::info(new message(self::$levels['info'], $msg));
                break;
        }

        return true;
    }

    public static function exceptionHandler(Exception $e)
    {
        self::error($e->getMessage());
    }

}

class message {


    private $message;

    private $level;

    private $file;

    private $line;

    private $date;

    private static $order = 0;

    private $stackTrace;

    public function __construct($message, $level)
    {
        $backtrace = debug_backtrace();

        $this->message      = (string)$message;
        $this->level        = intval($level);
        $this->file         = $backtrace[1]['file'];
        $this->line         = $backtrace[1]['line'];
        $this->date         = time();
        $this->stackTrace   = $backtrace;
        self::$order++;
    }

    public function message(){ return $this->message; }

    public function level(){ return $this->level; }

    public function file(){ return $this->file; }

    public function line(){ return $this->line; }

    public function date(){ return $this->date; }

    public function toJson()
    {
        return json_encode([
            "message"       => $this->message,
            "level"         => $this->level,
            "file"          => $this->file,
            "line"          => $this->line,
            "date"          => $this->date,
            "order"         => self::$order,
            "stacktrace"    => $this->stackTrace
        ]);
    }

}