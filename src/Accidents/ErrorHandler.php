<?php

namespace Accidents;

use ErrorException;

class ErrorHandler extends Handler
{
    /**
     * Handle an error.
     *
     * @param int    $severity The error severity
     * @param string $message  The error message
     * @param string $file     The file in which the error was thrown
     * @param int    $line     The line on which the error was thrown
     *
     * @throws ErrorException
     */
    public function __invoke($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting,
            // so we ignore it
            return;
        }

        // If the error is fatal, throw an ErrorException directly so
        // that the script execution is halted
        if (self::FATAL & $severity) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }

        // If the error is non-fatal, we can throw and catch and exception
        // and then use it to output the message
        try {
            throw new ErrorException($message, 0, $severity, $file, $line);
        } catch (ErrorException $e) {
            $this->outputException($e);
        }
    }
}
