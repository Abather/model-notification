<?php

namespace Abather\ModelNotification\Resolvers;

use Illuminate\Database\Eloquent\Model;

interface VariableResolverInterface
{
    /**
     * Check if this resolver can handle the variable.
     */
    public function canResolve(string $variable): bool;

    /**
     * Resolve the variable value.
     */
    public function resolve(string $variable, Model $model, string $key, string $lang, string $channel): string|int|float|bool|null;
}
