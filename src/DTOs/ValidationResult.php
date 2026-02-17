<?php

namespace Abather\ModelNotification\DTOs;

use Illuminate\Support\Collection;

class ValidationResult
{
    public function __construct(
        public readonly Collection $errors,
        public readonly Collection $warnings,
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(): self
    {
        return new self(
            errors: collect(),
            warnings: collect(),
        );
    }

    /**
     * Create a result with errors.
     */
    public static function withErrors(array $errors): self
    {
        return new self(
            errors: collect($errors),
            warnings: collect(),
        );
    }

    /**
     * Create a result with warnings.
     */
    public static function withWarnings(array $warnings): self
    {
        return new self(
            errors: collect(),
            warnings: collect($warnings),
        );
    }

    /**
     * Create a result with errors and warnings.
     */
    public static function withErrorsAndWarnings(array $errors, array $warnings): self
    {
        return new self(
            errors: collect($errors),
            warnings: collect($warnings),
        );
    }

    /**
     * Check if validation passed.
     */
    public function isValid(): bool
    {
        return $this->errors->isEmpty();
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

    /**
     * Get all errors.
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    /**
     * Get all warnings.
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    /**
     * Add an error.
     */
    public function addError(string $error): self
    {
        $this->errors->push($error);
        return $this;
    }

    /**
     * Add a warning.
     */
    public function addWarning(string $warning): self
    {
        $this->warnings->push($warning);
        return $this;
    }

    /**
     * Merge with another result.
     */
    public function merge(ValidationResult $other): self
    {
        return new self(
            errors: $this->errors->merge($other->errors),
            warnings: $this->warnings->merge($other->warnings),
        );
    }
}
