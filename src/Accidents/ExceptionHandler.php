<?php

namespace Accidents;

use Exception;
use Throwable;

class ExceptionHandler extends Handler
{
    /**
     * Handle an exception.
     *
     * @param Exception|Throwable $e The exception
     */
    public function __invoke($e)
    {
        if (!($e instanceof Exception || $e instanceof Throwable)) {
            return;
        }

        $this->outputException($e);
    }
}
