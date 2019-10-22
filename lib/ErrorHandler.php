<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\RollbarHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\BrowserConsoleHandler;

/**
 * The error handler.
 * 
 * This is a drop-in replacement for the Monolog error handler
 *  - Allows overwriting methods for customization
 *  - Displays a message on fatal errors
 *
 * @codeCoverageIgnore
 */
class ErrorHandler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var callback
     */
    protected $previousExceptionHandler;
    
    /**
     * @var int
     */
    protected $uncaughtExceptionLevel;

    /**
     * @var callback
     */
    protected $previousErrorHandler;
    
    /**
     * @var array
     */
    protected $errorLevelMap;

    /**
     * @var boolean
     */
    protected $hasFatalErrorHandler;
    
    /**
     * @var int
     */
    protected $fatalLevel;
    
    /**
     * @var int
     */
    protected $reservedMemory;
    
    /**
     * @var array
     */
    protected static $fatalErrors = [
        E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR
    ];
    
    /**
     * @var array
     */
    protected static $unrecoverableErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    
    /**
     * Class constructor
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }   
    
    /**
     * Registers a new ErrorHandler for a given Logger
     *
     * By default it will handle errors, exceptions and fatal errors
     *
     * @param  LoggerInterface $logger
     * @param  array|false     $errorLevelMap  an array of E_* constant to LogLevel::* constant mapping, or false to
     *    disable error handling
     * @param  int|false       $exceptionLevel a LogLevel::* constant, or false to disable exception handling
     * @param  int|false       $fatalLevel     a LogLevel::* constant, or false to disable fatal error handling
     * @return ErrorHandler
     */
    public static function register(
        LoggerInterface $logger,
        $errorLevelMap = [],
        $exceptionLevel = null,
        $fatalLevel = null
    )
    {
        $handler = new static($logger);
        
        if ($errorLevelMap !== false) {
            $handler->registerErrorHandler($errorLevelMap);
        }
        if ($exceptionLevel !== false) {
            $handler->registerExceptionHandler($exceptionLevel);
        }
        if ($fatalLevel !== false) {
            $handler->registerFatalHandler($fatalLevel);
        }

        return $handler;
    }

    /**
     * Register the exception handler
     * 
     * @param int     $level         LogLevel::* constant
     * @param boolean $callPrevious
     */
    public function registerExceptionHandler($level = null, $callPrevious = true)
    {
        $prev = set_exception_handler(array($this, 'handleException'));
        $this->uncaughtExceptionLevel = $level;
        if ($callPrevious && $prev) {
            $this->previousExceptionHandler = $prev;
        }
    }

    /**
     * Register the error handler
     * 
     * @param array   $levelMap       an array of E_* constant to LogLevel::* constant mapping
     * @param boolean $callPrevious
     * @param int     $errorTypes     Combination of E_* values
     */
    public function registerErrorHandler(array $levelMap = [], $callPrevious = true, $errorTypes = -1)
    {
        $prev = set_error_handler(array($this, 'handleError'), $errorTypes);
        $this->errorLevelMap = array_replace($this->defaultErrorLevelMap(), $levelMap);
        if ($callPrevious) {
            $this->previousErrorHandler = $prev ?: true;
        }
    }

    /**
     * Register the exception handler
     * 
     * @param int $level               LogLevel::* constant
     * @param int $reservedMemorySize  Memory in kb
     */
    public function registerFatalHandler($level = null, $reservedMemorySize = 20)
    {
        register_shutdown_function(array($this, 'handleFatalError'));

        $this->reservedMemory = str_repeat(' ', 1024 * $reservedMemorySize);
        $this->fatalLevel = $level;
        $this->hasFatalErrorHandler = true;
    }

    /**
     * Set display errors based on the configuration.
     * 
     * If `debug` isn't of is false, display_errors is disabled.
     * 
     * If `debug` is true, display_errors is set to the `display_errors` setting if it exists, otherwise it will set it
     *   to the `X-Display-Errors` header (useful for testing). If neither exist, it will remain the value set in
     *   php.ini.
     * 
     * @param stdClass $config
     */
    public function setDisplayErrors(stdClass $config)
    {
        if (!empty($config->debug)) {
            $display_errors = isset($config->display_errors)
                ? $config->display_errors
                : (isset($_SERVER['HTTP_X_DISPLAY_ERRORS']) ? $_SERVER['HTTP_X_DISPLAY_ERRORS'] : null);

            if (isset($display_errors)) {
                ini_set('display_errors', $display_errors);
                ini_set('log_errors', !$display_errors);
                return;
            }
        } else {
            ini_set('display_errors', false);
            ini_set('log_errors', true);
            error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT);
        }
    }
    

    /**
     * Return an array of E_* constant to LogLevel::* constant mapping
     * 
     * @return array
     */
    protected function defaultErrorLevelMap()
    {
        return array(
            E_ERROR             => LogLevel::CRITICAL,
            E_WARNING           => LogLevel::WARNING,
            E_PARSE             => LogLevel::ALERT,
            E_NOTICE            => LogLevel::NOTICE,
            E_CORE_ERROR        => LogLevel::CRITICAL,
            E_CORE_WARNING      => LogLevel::WARNING,
            E_COMPILE_ERROR     => LogLevel::ALERT,
            E_COMPILE_WARNING   => LogLevel::WARNING,
            E_USER_ERROR        => LogLevel::ERROR,
            E_USER_WARNING      => LogLevel::WARNING,
            E_USER_NOTICE       => LogLevel::NOTICE,
            E_STRICT            => LogLevel::NOTICE,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_DEPRECATED        => LogLevel::NOTICE,
            E_USER_DEPRECATED   => LogLevel::NOTICE,
        );
    }

    
    /**
     * Display a message on a fatal error
     */
    protected function showFatalMessage()
    {
        if (headers_sent() || ini_get('display_errors')) {
            return;
        }
        
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code(500);
        header("Content-Type: text/plain");
        echo "An unexpected error occured.";
        
        if ($this->logger instanceof Monolog\Logger) {
            foreach ($this->logger->getHandlers() as $handler) {
                if ($handler instanceof RollbarHandler) {
                    echo ' Check Rollbar for more information.';
                    break;
                }
                
                if (
                    $handler instanceof FirePHPHandler ||
                    $handler instanceof ChromePHPHandler ||
                    $handler instanceof BrowserConsoleHandler
                ) { 
                    echo ' Check your browser console for more information.';
                    break;
                }
            }
        }
    }
    
    /**
     * @private
     */
    public function handleException($e)
    {
        $level = $this->uncaughtExceptionLevel === null ? LogLevel::ERROR : $this->uncaughtExceptionLevel;
        $msg = sprintf('Uncaught Exception %s: "%s" at %s line %s', get_class($e), $e->getMessage(), $e->getFile(),
            $e->getLine());
        
        $this->logger->log($level, $msg, array('exception' => $e));

        if ($this->previousExceptionHandler) {
            call_user_func($this->previousExceptionHandler, $e);
        } else {
            $this->showFatalMessage();
        }

        exit(255);
    }

    /**
     * @private
     */
    public function handleError($code, $message, $file = '', $line = 0, $context = array())
    {
        if (!(error_reporting() & $code)) {
            return;
        }
        
        // fatal error codes are ignored if a fatal error handler is present as well to avoid duplicate log entries
        $level = isset($this->errorLevelMap[$code]) ? $this->errorLevelMap[$code] : LogLevel::CRITICAL;
        $this->logger->log(
            $level,
            static::codeToString($code).': '.$message,
            ['code' => $code, 'message' => $message, 'file' => $file, 'line' => $line]
        );
        
        if ($this->previousErrorHandler === true) {
            if (in_array($code, [E_USER_ERROR, E_RECOVERABLE_ERROR])) {
                $this->showFatalMessage();
            }
            return false;
        } elseif ($this->previousErrorHandler) {
            return call_user_func($this->previousErrorHandler, $code, $message, $file, $line, $context);
        }
    }

    /**
     * @private
     */
    public function handleFatalError()
    {
        $this->reservedMemory = null;

        $lastError = error_get_last();
        
        if ($lastError && in_array($lastError['type'], static::$unrecoverableErrors, true)) {
            $this->logger->log(
                $this->fatalLevel === null ? LogLevel::ALERT : $this->fatalLevel,
                'Fatal Error ('.static::codeToString($lastError['type']).'): '.$lastError['message'],
                [
                    'code' => $lastError['type'],
                    'message' => $lastError['message'],
                    'file' => $lastError['file'],
                    'line' => $lastError['line']
                ]
            );
        }
        
        if ($this->logger instanceof Monolog\Logger) {
            foreach ($this->logger->getHandlers() as $handler) {
                if ($handler instanceof AbstractHandler) {
                    $handler->close();
                }
            }
        }
    }
    
    /**
     * Turn an E_* constant into a string
     * 
     * @param int $code
     * @return string
     */
    protected static function codeToString($code)
    {
        switch ($code) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return 'Unknown PHP error';
    }
}
