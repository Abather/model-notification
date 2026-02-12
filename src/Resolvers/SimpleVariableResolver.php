<?php

namespace Abather\ModelNotification\Resolvers;

use Abather\ModelNotification\Exceptions\VariableResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SimpleVariableResolver implements VariableResolverInterface
{
    /**
     * Check if this resolver can handle the variable.
     */
    public function canResolve(string $variable): bool
    {
        // Simple variables don't contain relationship or method symbols
        $relationshipSymbol = config('model-notification.variables.relationship_symbol', '->');
        $methodSymbol = config('model-notification.variables.method_symbol', '()');

        return !str_contains($variable, $relationshipSymbol) && !str_contains($variable, $methodSymbol);
    }

    /**
     * Resolve the variable value from model attributes.
     */
    public function resolve(string $variable, Model $model, string $key, string $lang, string $channel): string|int|float|bool|null
    {
        // Check if attribute exists on model
        if (!isset($model->{$variable})) {
            $strictMode = config('model-notification.variables.strict_mode', false);

            if ($strictMode) {
                throw new VariableResolutionException(
                    $variable,
                    "Attribute '{$variable}' does not exist on model " . get_class($model)
                );
            }

            Log::debug("Attribute not found on model, using fallback", [
                'variable' => $variable,
                'model' => get_class($model),
            ]);

            return config('model-notification.variables.fallback_value', '');
        }

        return $model->{$variable};
    }
}
