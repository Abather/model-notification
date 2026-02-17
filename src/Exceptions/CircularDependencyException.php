<?php

namespace Abather\ModelNotification\Exceptions;

use Throwable;

class CircularDependencyException extends ModelNotificationException
{
    public function __construct(
        string $variable,
        array $dependencyChain,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'Circular dependency detected for variable "%s". Chain: %s -> %s',
            $variable,
            implode(' -> ', $dependencyChain),
            $variable
        );

        parent::__construct($message, 422, $previous);
    }
}
