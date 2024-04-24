<?php

namespace Abather\ModelNotification\Exceptions;

use Exception;
use Throwable;

class DuplicatedTemplateException extends Exception
{
    public function __construct($message = 'The template is already exists', $code = 422, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
