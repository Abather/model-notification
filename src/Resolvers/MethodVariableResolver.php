<?php

namespace Abather\ModelNotification\Resolvers;

use Abather\ModelNotification\Exceptions\VariableResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MethodVariableResolver implements VariableResolverInterface
{
    /**
     * Check if this resolver can handle the variable.
     */
    public function canResolve(string $variable): bool
    {
        $methodSymbol = config('model-notification.variables.method_symbol', '()');

        // Check if variable ends with method symbol
        return str_ends_with($variable, $methodSymbol);
    }

    /**
     * Resolve the variable value by calling a method.
     */
    public function resolve(string $variable, Model $model, string $key, string $lang, string $channel): string|int|float|bool|null
    {
        $methodSymbol = config('model-notification.variables.method_symbol', '()');
        $allowMethodCalls = config('model-notification.variables.allow_method_calls', true);

        if (!$allowMethodCalls) {
            $strictMode = config('model-notification.variables.strict_mode', false);

            if ($strictMode) {
                throw new VariableResolutionException(
                    $variable,
                    "Method calls are disabled"
                );
            }

            return config('model-notification.variables.fallback_value', '');
        }

        // Extract method name and arguments
        $methodName = str_replace($methodSymbol, '', $variable);
        $arguments = [];

        // Parse arguments if any (e.g., formatDate('Y-m-d'))
        if (preg_match('/^(.+)\((.*)\)$/', $variable, $matches)) {
            $methodName = $matches[1];
            $argsString = $matches[2];

            if (!empty($argsString)) {
                $arguments = $this->parseArguments($argsString);
            }
        }

        // Check if method is whitelisted
        if (!$this->isMethodWhitelisted($methodName)) {
            $strictMode = config('model-notification.variables.strict_mode', false);

            if ($strictMode) {
                throw new VariableResolutionException(
                    $variable,
                    "Method '{$methodName}' is not whitelisted"
                );
            }

            Log::debug("Method not whitelisted, using fallback", [
                'variable' => $variable,
                'model' => get_class($model),
                'method' => $methodName,
            ]);

            return config('model-notification.variables.fallback_value', '');
        }

        // Check if method exists on model
        if (!method_exists($model, $methodName)) {
            $strictMode = config('model-notification.variables.strict_mode', false);

            if ($strictMode) {
                throw new VariableResolutionException(
                    $variable,
                    "Method '{$methodName}' does not exist on model " . get_class($model)
                );
            }

            Log::debug("Method not found on model, using fallback", [
                'variable' => $variable,
                'model' => get_class($model),
                'method' => $methodName,
            ]);

            return config('model-notification.variables.fallback_value', '');
        }

        // Call the method with arguments
        try {
            return call_user_func_array([$model, $methodName], $arguments);
        } catch (\Exception $e) {
            Log::error("Failed to call method on model", [
                'variable' => $variable,
                'model' => get_class($model),
                'method' => $methodName,
                'error' => $e->getMessage(),
            ]);

            $strictMode = config('model-notification.variables.strict_mode', false);

            if ($strictMode) {
                throw new VariableResolutionException(
                    $variable,
                    "Method call failed: " . $e->getMessage()
                );
            }

            return config('model-notification.variables.fallback_value', '');
        }
    }

    /**
     * Parse method arguments from string.
     */
    protected function parseArguments(string $argsString): array
    {
        $arguments = [];
        $current = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($argsString); $i++) {
            $char = $argsString[$i];

            if ($inString) {
                if ($char === $stringChar && $argsString[$i - 1] !== '\\') {
                    $inString = false;
                }
                $current .= $char;
            } elseif ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($char === ',') {
                $arguments[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (!empty($current)) {
            $arguments[] = trim($current);
        }

        return array_map(function ($arg) {
            // Remove quotes from strings
            if ((str_starts_with($arg, '"') && str_ends_with($arg, '"')) ||
                (str_starts_with($arg, "'") && str_ends_with($arg, "'"))) {
                return substr($arg, 1, -1);
            }
            // Convert to number if possible
            if (is_numeric($arg)) {
                return strpos($arg, '.') !== false ? (float) $arg : (int) $arg;
            }
            return $arg;
        }, $arguments);
    }

    /**
     * Check if method is whitelisted.
     */
    protected function isMethodWhitelisted(string $methodName): bool
    {
        $whitelistedMethods = config('model-notification.variables.whitelisted_methods', ['*']);

        // Allow all methods if wildcard
        if (in_array('*', $whitelistedMethods)) {
            return true;
        }

        return in_array($methodName, $whitelistedMethods);
    }
}
