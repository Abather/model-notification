<?php

namespace Abather\ModelNotification\Exceptions;

use Throwable;

class TemplateNotFoundException extends ModelNotificationException
{
    public function __construct(
        string $model,
        string $key,
        string $lang,
        string $channel,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'Template not found for model "%s" with key "%s", language "%s", and channel "%s".',
            $model,
            $key,
            $lang,
            $channel
        );

        parent::__construct($message, 404, $previous);
    }
}
