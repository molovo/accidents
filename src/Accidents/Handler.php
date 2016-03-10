<?php

namespace Accidents;

use ErrorException;
use Exception;
use Molovo\Graphite\Graphite;
use Throwable;

class Handler
{
    /**
     * A map of error codes to strings.
     */
    const SEVERITY = [
        1     => 'E_ERROR',
        2     => 'E_WARNING',
        4     => 'E_PARSE',
        8     => 'E_NOTICE',
        16    => 'E_CORE_ERROR',
        32    => 'E_CORE_WARNING',
        64    => 'E_COMPILE_ERROR',
        128   => 'E_COMPILE_WARNING',
        256   => 'E_USER_ERROR',
        512   => 'E_USER_WARNING',
        1024  => 'E_USER_NOTICE',
        2048  => 'E_STRICT',
        4096  => 'E_RECOVERABLE_ERROR',
        8192  => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED',
        32767 => 'E_ALL',
    ];

    /**
     * Error codes which are fatal.
     */
    const FATAL = E_ERROR |
                  E_PARSE |
                  E_CORE_ERROR |
                  E_COMPILE_ERROR |
                  E_USER_ERROR;

    /**
     * Register the error and exception handlers.
     */
    public static function register()
    {
        set_error_handler(new ErrorHandler);

        set_exception_handler(new ExceptionHandler);

        // Stop PHP from internally handling the output of fatal errors
        ini_set('display_errors', false);

        register_shutdown_function(new FatalErrorHandler);
    }

    /**
     * Create a new handler instance.
     */
    public function __construct()
    {
        $this->output = new Graphite;
        $this->output->setGlobalIndent(4);
    }

    protected function outputException($e)
    {
        if (!($e instanceof Exception || $e instanceof Throwable)) {
            return;
        }

        // Get the error/exception type
        $type = $this->getType($e);

        // Add the exception name to the title line and render it
        echo "\n";
        $exception = $this->output->bold->red(get_class($e));
        echo $this->output->render("$type $exception");

        // Reset the indent for the stack trace
        $indent = strlen($this->output->strip($type)) + 5;
        $this->output->setGlobalIndent($indent);

        // Output the exception message
        echo $this->output->yellow->render($e->getMessage());

        // Get and colorise the filename and line and render them
        $file = $this->output->magenta($e->getFile());
        $line = $this->output->magenta($e->getLine());
        echo "\n";
        echo $this->output->render("at  $file : $line");

        // Loop through the stack trace
        foreach ($e->getTrace() as $i => $trace) {
            // Get and colorise the different parts of the trace
            $file     = isset($trace['file']) ? $this->output->magenta($trace['file']) : null;
            $line     = isset($trace['line']) ? $this->output->magenta($trace['line']) : null;
            $class    = isset($trace['class']) ? $this->output->yellow($trace['class']) : null;
            $type     = isset($trace['type']) ? $trace['type'] : null;
            $function = isset($trace['function']) ? $trace['function'] : null;
            $args     = isset($trace['args']) ? $this->formatArgs($trace['args'], true) : null;
            $pos      = str_pad($i + 1, 4, ' ');

            // Echo the trace
            echo "\n";
            echo $this->output->render("$pos$class$type$function($args)");
            echo $this->output->render("    $file : $line");
        }
    }

    /**
     * Get and format the error/exception type.
     *
     * @param Exception $e The exception to query
     *
     * @return string
     */
    protected function getType($e)
    {
        if (!($e instanceof Exception || $e instanceof Throwable)) {
            return;
        }

        $type = 'EXCEPTION';

        if ($e instanceof ErrorException) {
            $type = static::SEVERITY[$e->getSeverity()];

            // Non-fatal notices and warnings are shown with a yellow background
            if (!(self::FATAL & $e->getSeverity())) {
                return $this->output->bold->yellowbg->black(" $type ");
            }
        }

        if ($e instanceof Error) {
            $type = 'ERROR';
        }

        // Everything else is fatal, so we show a red background
        return $this->output->bold->redbg->white(" $type ");
    }

    /**
     * Format arguments to function calls so that they
     * can be displayed in the stack trace.
     *
     * @param array $args           The arguments to format
     * @param bool  $allowRecursion Whether to allow the function to recurse
     *                              deeper into arrays
     *
     * @return string
     */
    protected function formatArgs($args, $allowRecursion = false)
    {
        $formatted = [];

        // Loop through each of the arguments
        foreach ($args as $arg) {
            // Wrap string in quotes
            if (is_string($arg)) {
                $formatted[] = '"'.$arg.'"';
                continue;
            }

            if (is_array($arg)) {
                // Check if the array is associative
                $associative = array_keys($arg) !== range(0, count($arg) - 1);

                // If recursion is allowed, the array is not associative,
                // and small enough to make the output readable, then format
                // the values in the array in the same way
                if ($allowRecursion && $associative && count($arg) <= 5) {
                    $arg         = $this->formatArgs($arg);
                    $formatted[] = "[$arg]";
                }
                continue;
            }

            // For objects, show the class name
            if (is_object($arg)) {
                $class       = get_class($arg);
                $formatted[] = "Object($class)";
                continue;
            }

            $formatted[] = $arg;
        }

        // Return the values separated by commas
        return implode(', ', $formatted);
    }
}
