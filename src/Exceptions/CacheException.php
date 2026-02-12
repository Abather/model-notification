<?php

namespace Abather\ModelNotification\Exceptions;

use Throwable;

class CacheException extends ModelNotificationException
{
    public function __construct(
        string $operation,
        string $reason,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'Cache operation "%s" failed: %s.',
            $operation,
            $reason
        );

        parent::__construct($message, 500, $previous);
    }
}
