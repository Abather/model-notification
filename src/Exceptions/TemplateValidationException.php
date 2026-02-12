<?php

namespace Abather\ModelNotification\Exceptions;

use Throwable;
use Illuminate\Support\Collection;

class TemplateValidationException extends ModelNotificationException
{
    public function __construct(
        public readonly Collection $errors,
        public readonly Collection $warnings,
        ?Throwable $previous = null
    ) {
        $message = 'Template validation failed: ' . $errors->implode(', ');

        parent::__construct($message, 422, $previous);
    }

    /**
     * Get all validation errors.
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    /**
     * Get all validation warnings.
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return $this->errors->isNotEmpty();
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return $this->warnings->isNotEmpty();
    }
}
