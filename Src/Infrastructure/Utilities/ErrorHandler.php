<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Utilities;

use It_All\Spaghettify\Src\Infrastructure\Database\Postgres;
use It_All\Spaghettify\Src\Infrastructure\SystemEvents\SystemEventsModel;

class ErrorHandler
{
    private $logPath;
    private $redirectPage;
    private $isLiveServer;
    private $mailer;
    private $emailTo;
    private $database;
    private $systemEventsModel;
    private $fatalMessage;

    public function __construct(
        string $logPath,
        string $redirectPage,
        bool $isLiveServer,
        PhpMailerService $m,
        array $emailTo,
        $fatalMessage = 'Apologies, there has been an error on our site. We have been alerted and will correct it as soon as possible.'
    )
    {
        $this->logPath = $logPath;
        $this->redirectPage = $redirectPage;
        $this->isLiveServer = $isLiveServer;
        $this->mailer = $m;
        $this->emailTo = $emailTo;
        $this->fatalMessage = $fatalMessage;
    }

    public function setDatabaseAndSystemEventsModel(Postgres $database, SystemEventsModel $systemEventsModel)
    {
        $this->setDatabase($database);
        $this->setSystemEventsModel($systemEventsModel);
    }

    public function setDatabase(Postgres $database)
    {
        $this->database = $database;
    }

    public function setSystemEventsModel(SystemEventsModel $systemEventsModel)
    {
        $this->systemEventsModel = $systemEventsModel;
    }

    /*
     * 4 ways to handle:
     * database - always (as long as database and systemEventsModel properties have been set), (use @ to avoid infinite loop)
     * log - always (use @ to avoid infinite loop)
     * echo - never on live server, depends on config and @ controller on dev
     * email - always on live server, depends on config on dev. never email error deets. (use @ to avoid infinite loop)
     * Then, die if necessary
     */
    private function handleError(string $messageBody, int $errno, bool $die = false)
    {
        // happens when an expression is prefixed with @ (meaning: ignore errors).
        if (error_reporting() == 0) {
            return;
        }
        $errorMessage = $this->generateMessage($messageBody);

        if (isset($this->database) && isset($this->systemEventsModel)) {
            // database
            switch ($this->getErrorType($errno)) {
                case 'Core Error':
                case 'Parse Error':
                case 'Fatal Error':
                    $systemEventType = 'critical';
                    break;
                case 'Core Warning':
                case 'Warning':
                    $systemEventType = 'warning';
                    break;
                case 'Deprecated':
                case 'Notice':
                    $systemEventType = 'notice';
                    break;
                default:
                    $systemEventType = 'error';
            }

            // note this will be null for errors occurring prior to session initialization
            $adminId = (isset($_SESSION[SESSION_USER][SESSION_USER_ID])) ? (int) $_SESSION[SESSION_USER][SESSION_USER_ID] : null;

            @$this->systemEventsModel->insertEvent('PHP Error', $systemEventType, $adminId, explode('Stack Trace:', $errorMessage)[0].'. See phpErrors.log for further details.');
        }

        // log
        @error_log($errorMessage, 3, $this->logPath);

        // email
        @$this->mailer->send($_SERVER['SERVER_NAME'] . " Error", "Check log file for details.", $this->emailTo);

        // echo
        if (!$this->isLiveServer) {
            echo nl2br($errorMessage, false);
            if ($die) {
                die();
            }
        }

        if ($die) {
            $_SESSION[SESSION_NOTICE] = [$this->fatalMessage, 'error'];
            header("Location: https://$this->redirectPage");
            exit();
        }
    }

    /**
     * used in register_shutdown_function to see if a fatal error has occurred and handle it.
     * note, this does not occur often in php7, as almost all errors are now exceptions and will be caught by the registered exception handler. fatal errors can still occur for conditions like out of memory: https://trowski.com/2015/06/24/throwable-exceptions-and-errors-in-php7/
     * see also https://stackoverflow.com/questions/10331084/error-logging-in-a-smooth-way
     */
    public function shutdownFunction()
    {
        $error = error_get_last();

        if (!isset($error)) {
            return;
        }

        $fatalErrorTypes = [E_USER_ERROR, E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING];
        if (in_array($error["type"], $fatalErrorTypes)) {
            $this->handleError($this->generateMessageBodyCommon($error["type"], $error["message"], $error["file"], $error["line"]),$error["type"], true);
        }
    }

    /** @param \Throwable $e
     * catches both Errors and Exceptions
     * create error message and send to handleError
     */
    public function throwableHandler(\Throwable $e)
    {
        $message = $this->generateMessageBodyCommon($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
        $exitPage = ($e->getCode() == E_ERROR || $e->getCode() == E_USER_ERROR) ? true : false;

        $traceString = "";
        foreach ($e->getTrace() as $k => $v) {
            $traceString .= "#$k ";
            $traceString .= arrayWalkToStringRecursive($v);
            $traceString .= "\n";
        }

        $message .= "\nStack Trace:\n".str_replace('/media/gcat/storage/it-all.com/Software/ProjectsSrc/Spaghettify', '', $traceString);
        $this->handleError($message, $e->getCode(), $exitPage);
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string|null $errfile
     * @param string|null $errline
     * to be registered with php's set_error_handler()
     * trigger_error() will call this
     * called for php Notices and possibly more
     */
    public function phpErrorHandler(int $errno, string $errstr, string $errfile = null, string $errline = null)
    {
        $this->handleError($this->generateMessageBodyCommon($errno, $errstr, $errfile, $errline), $errno, false);
    }

    private function generateMessage(string $messageBody): string
    {
        $message = "[".date('Y-m-d H:i:s e')."] ";

        $message .= isRunningFromCommandLine() ? gethostname() : $_SERVER['SERVER_NAME'];

        if (isRunningFromCommandLine()) {
            global $argv;
            $message .= "Command line: " . $argv[0];
        } else {
            $message .= "\nWeb Page: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'];
            if (strlen($_SERVER['QUERY_STRING']) > 0) {
                $message .= "?" . $_SERVER['QUERY_STRING'];
            }
        }
        $message .= "\n" . $messageBody . "\n\n";
        return $message;
    }

    private function getErrorType($errno)
    {
        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
                return 'Fatal Error';
            case E_WARNING:
            case E_USER_WARNING:
                return 'Warning';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'Notice';
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'Deprecated';
            case E_PARSE:
                return 'Parse Error';
            case E_CORE_ERROR:
                return 'Core Error';
            case E_CORE_WARNING:
                return 'Core Warning';
            case E_COMPILE_ERROR:
                return 'Compile Error';
            case E_COMPILE_WARNING:
                return 'Compile Warning';
            case E_STRICT:
                return 'Strict';
            case E_RECOVERABLE_ERROR:
                return 'Recoverable Error';
            default:
                return 'Unknown error type';
        }

    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string|null $errfile
     * @param null $errline
     * @return string
     * errline seems to be passed in as a string or int depending on where it's coming from
     */
    private function generateMessageBodyCommon(int $errno, string $errstr, string $errfile = null, $errline = null): string
    {
        $message = $this->getErrorType($errno).": ";
        $message .= "$errstr\n";

        if (!is_null($errfile)) {
            $message .= "$errfile";
            // note it only makes sense to have line if we have file
            if (!is_null($errline)) {
                $message .= " line: $errline";
            }
        }

        return $message;
    }
}
