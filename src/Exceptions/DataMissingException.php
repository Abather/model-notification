<?php

namespace Abather\ModelNotification\Exceptions;

use Exception;
use Throwable;

class DataMissingException extends Exception
{
    public function __construct($message = 'There are some data missing', $code = 422, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->code = $code ?: 422;
    }
}
