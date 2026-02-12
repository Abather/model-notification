<?php

namespace Abather\ModelNotification\Validators;

use Abather\ModelNotification\DTOs\ValidationResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TemplateValidator
{
    /**
     * Validate a template.
     */
    public function validate(
        string $template,
        ?Model $model = null,
        array $knownVariables = []
    ): ValidationResult {
        $result = ValidationResult::success();

        // Check if validation is enabled
        if (!config('model-notification.validation.enabled', true)) {
            return $result;
        }

        // Check max template length
        $maxLength = config('model-notification.validation.max_template_length', 10000);
        if (strlen($template) > $maxLength) {
            $result->addError("Template exceeds maximum length of {$maxLength} characters");
        }

        // Validate bracket syntax
        $this->validateBrackets($template, $result);

        // Validate variable names
        $this->validateVariableNames($template, $result);


        // Check for undefined variables (if strict mode)
        if (config('model-notification.validation.check_undefined_variables', false) && !empty($knownVariables)) {
            $this->checkUndefinedVariables($template, $result, $knownVariables);
        }

        return $result;
    }

    /**
     * Validate bracket syntax.
     */
    protected function validateBrackets(string $template, ValidationResult $result): void
    {
        $starter = (string) config('model-notification.variables.starter', '[');
        $ender = (string) config('model-notification.variables.ender', ']');

        if ($starter === '' || $ender === '') {
            return;
        }

        $starterCount = substr_count($template, $starter);
        $enderCount = substr_count($template, $ender);

        if ($starterCount !== $enderCount) {
            $result->addError("Mismatched brackets: found {$starterCount} opening and {$enderCount} closing brackets");
        }

        // Check for nested brackets
        $depth = 0;
        $maxDepth = 0;
        $pos = 0;
        while (($matchPos = strpos($template, $starter, $pos)) !== false) {
            $depth++;
            $maxDepth = max($maxDepth, $depth);

            // Find matching ender
            $endPos = strpos($template, $ender, $matchPos);
            if ($endPos === false) {
                $result->addError("Unclosed bracket at position {$matchPos}");
                return;
            }

            $depth--;
            $pos = $endPos + 1;
        }

        if ($depth !== 0) {
            $result->addError("Unclosed brackets found (depth: {$depth})");
        }
    }

    /**
     * Validate variable names.
     */
    protected function validateVariableNames(string $template, ValidationResult $result): void
    {
        $starter = config('model-notification.variables.starter', '[');
        $ender = config('model-notification.variables.ender', ']');
        $relationshipSymbol = config('model-notification.variables.relationship_symbol', '->');
        $methodSymbol = config('model-notification.variables.method_symbol', '()');

        $variables = $this->extractVariables($template);

        foreach ($variables as $variable) {
            // Check for empty variable
            if (empty($variable)) {
                $result->addError("Empty variable found");
                continue;
            }

            // Check for invalid characters
            if (preg_match('/[^a-zA-Z0-9_\-\->\(\)\s"\',]/', $variable)) {
                $result->addError("Variable '{$variable}' contains invalid characters");
            }

            // Check for consecutive relationship symbols
            if (str_contains($variable, $relationshipSymbol . $relationshipSymbol)) {
                $result->addError("Variable '{$variable}' has consecutive relationship symbols");
            }

            // Check for method syntax without method name
            if (str_ends_with($variable, $methodSymbol) && strlen($variable) <= strlen($methodSymbol)) {
                $result->addError("Variable '{$variable}' has empty method name");
            }
        }
    }


    /**
     * Check for undefined variables.
     */
    protected function checkUndefinedVariables(string $template, ValidationResult $result, array $knownVariables): void
    {
        $variables = $this->extractVariables($template);

        foreach ($variables as $variable) {
            // Extract variable name (remove method call syntax, relationship syntax)
            $varName = preg_replace('/\(.+\)$/', '', $variable); // Remove method calls
            $varName = preg_replace('/->.+$/', '', $varName); // Remove relationship access

            if (!in_array($varName, $knownVariables)) {
                $result->addWarning("Variable '{$variable}' may be undefined");
            }
        }
    }

    /**
     * Extract all variables from template.
     */
    protected function extractVariables(string $template): array
    {
        $starter = config('model-notification.variables.starter', '[');
        $ender = config('model-notification.variables.ender', ']');

        $variables = [];
        $pos = 0;

        if ($starter === '' || $ender === '') {
            return [];
        }

        while (($matchPos = strpos($template, $starter, $pos)) !== false) {
            $endPos = strpos($template, $ender, $matchPos);

            if ($endPos === false) {
                break;
            }

            $variable = substr($template, $matchPos + 1, $endPos - $matchPos - 1);
            $variables[] = $variable;

            $pos = $endPos + 1;
        }

        return $variables;
    }
}
