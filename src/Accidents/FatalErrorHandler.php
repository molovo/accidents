<?php

namespace Accidents;

use ErrorException;

class FatalErrorHandler extends Handler
{
    /**
     * Since PHP<7 can't catch a fatal error, this handler is invoked as
     * a shutdown function to output the trace before the script exits.
     */
    public function __invoke()
    {
        if ($error = error_get_last()) {
            if (self::FATAL & $error['type']) {
                // Create an ErrorException
                $e = new ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                );

                // The script is already shutting down, so just
                // output the exception directly rather than throwing
                $this->outputException($e);
            }
        }
    }
}
