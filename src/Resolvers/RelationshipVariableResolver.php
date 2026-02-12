<?php

namespace Abather\ModelNotification\Resolvers;

use Abather\ModelNotification\Exceptions\VariableResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class RelationshipVariableResolver implements VariableResolverInterface
{
    /**
     * Check if this resolver can handle the variable.
     */
    public function canResolve(string $variable): bool
    {
        $relationshipSymbol = config('model-notification.variables.relationship_symbol', '->');

        // Check if variable contains relationship symbol
        if (!str_contains($variable, $relationshipSymbol)) {
            return false;
        }

        // Don't start with symbol (e.g., "->name")
        if (str_starts_with($variable, $relationshipSymbol)) {
            return false;
        }

        // Don't end with symbol (e.g., "name->")
        if (str_ends_with($variable, $relationshipSymbol)) {
            return false;
        }

        return true;
    }

    /**
     * Resolve the variable value from model relationships.
     */
    public function resolve(string $variable, Model $model, string $key, string $lang, string $channel): string|int|float|bool|null
    {
        $relationshipSymbol = config('model-notification.variables.relationship_symbol', '->');
        $parts = explode($relationshipSymbol, $variable);
        $currentModel = $model;

        while (count($parts) > 1) {
            $relationName = array_shift($parts);

            // Check if relationship exists on model
            if (!method_exists($currentModel, $relationName) && !isset($currentModel->{$relationName})) {
                $strictMode = config('model-notification.variables.strict_mode', false);

                if ($strictMode) {
                    throw new VariableResolutionException(
                        $variable,
                        "Relationship '{$relationName}' does not exist on model " . get_class($currentModel)
                    );
                }

                return config('model-notification.variables.fallback_value', '');
            }

            // Load relationship if not already loaded (if it's a model)
            if ($currentModel instanceof Model && !$currentModel->relationLoaded($relationName)) {
                $currentModel->load($relationName);
            }

            // Move to the next model in the chain
            $currentModel = $currentModel->{$relationName};

            // Check if related model is null
            if ($currentModel === null) {
                return config('model-notification.variables.fallback_value', '');
            }
        }

        // The last part is the attribute name
        $attributeName = array_shift($parts);

        // Check if attribute exists on the final model/object
        if (!isset($currentModel->{$attributeName})) {
            $strictMode = config('model-notification.variables.strict_mode', false);

            if ($strictMode) {
                throw new VariableResolutionException(
                    $variable,
                    "Attribute '{$attributeName}' does not exist on related model " . (is_object($currentModel) ? get_class($currentModel) : gettype($currentModel))
                );
            }

            return config('model-notification.variables.fallback_value', '');
        }

        return $currentModel->{$attributeName};
    }
}
