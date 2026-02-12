<?php

namespace Abather\ModelNotification\Exceptions;

use Throwable;

class VariableResolutionException extends ModelNotificationException
{
    public function __construct(
        string $variable,
        string $reason,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'Variable "%s" cannot be resolved: %s.',
            $variable,
            $reason
        );

        parent::__construct($message, 422, $previous);
    }
}
