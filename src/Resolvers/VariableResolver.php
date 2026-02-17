<?php

namespace Abather\ModelNotification\Resolvers;

use Abather\ModelNotification\Exceptions\VariableResolutionException;
use Abather\ModelNotification\Exceptions\CircularDependencyException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class VariableResolver
{
    /**
     * @var array<VariableResolverInterface>
     */
    protected array $resolvers = [];

    /**
     * @var array<string, mixed>
     */
    protected array $resolutionStack = [];

    /**
     * @var int
     */
    protected int $maxDepth = 10;

    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
        $this->maxDepth = config('model-notification.variables.max_depth', 10);
    }

    /**
     * Resolve all variables in a template.
     */
    public function resolve(string $template, Model $model, string $key, string $lang, string $channel): string
    {
        $this->resolutionStack = [];

        $variable = $this->getNextVariable($template);
        $iterations = 0;
        $maxIterations = 100; // Safeguard

        $starter = (string) config('model-notification.variables.starter', '[');
        $ender = (string) config('model-notification.variables.ender', ']');

        if ($starter === '' || $ender === '') {
            return $template;
        }

        while ($variable !== null && $iterations < $maxIterations) {
            $resolvedValue = $this->resolveVariable($variable, $model, $key, $lang, $channel);
            $template = str_replace($starter . $variable . $ender, $resolvedValue, $template);

            $variable = $this->getNextVariable($template);
            $iterations++;
        }

        if ($iterations >= $maxIterations) {
            Log::warning("Maximum variable resolution iterations ({$maxIterations}) exceeded for template", [
                'template_key' => $key,
                'lang' => $lang,
                'channel' => $channel,
            ]);
        }

        return $template;
    }

    /**
     * Resolve a single variable.
     */
    protected function resolveVariable(string $variable, Model $model, string $key, string $lang, string $channel): string
    {
        // Check for circular dependency
        if (in_array($variable, $this->resolutionStack)) {
            throw new CircularDependencyException($variable, $this->resolutionStack);
        }

        // Check max depth
        if (count($this->resolutionStack) >= $this->maxDepth) {
            throw new VariableResolutionException(
                $variable,
                "Maximum resolution depth of {$this->maxDepth} exceeded"
            );
        }

        // Add to resolution stack
        $this->resolutionStack[] = $variable;

        try {
            // Try each resolver in order
            foreach ($this->resolvers as $resolver) {
                if ($resolver->canResolve($variable)) {
                    $value = $resolver->resolve($variable, $model, $key, $lang, $channel);

                    // Remove from resolution stack
                    array_pop($this->resolutionStack);

                    return (string) $value;
                }
            }

            // No resolver could handle this variable
            $strictMode = config('model-notification.variables.strict_mode', false);
            $fallbackValue = config('model-notification.variables.fallback_value', '');

            if ($strictMode) {
                throw new VariableResolutionException(
                    $variable,
                    "No resolver found for variable"
                );
            }

            Log::debug("Variable could not be resolved, using fallback", [
                'variable' => $variable,
                'fallback' => $fallbackValue,
            ]);

            array_pop($this->resolutionStack);

            return $fallbackValue;
        } catch (VariableResolutionException $e) {
            array_pop($this->resolutionStack);
            throw $e;
        }
    }

    /**
     * Get the next variable from the template.
     */
    protected function getNextVariable(string $template): ?string
    {
        $starter = config('model-notification.variables.starter', '[');
        $ender = config('model-notification.variables.ender', ']');

        $starterPos = strpos($template, $starter);
        if ($starterPos === false) {
            return null;
        }

        $enderPos = strpos($template, $ender, $starterPos);
        if ($enderPos === false) {
            return null;
        }

        $variable = substr($template, $starterPos + strlen($starter), $enderPos - $starterPos - strlen($starter));

        return $variable;
    }

    /**
     * Add a resolver to the chain.
     */
    public function addResolver(VariableResolverInterface $resolver): self
    {
        $this->resolvers[] = $resolver;
        return $this;
    }

    /**
     * Get all resolvers.
     */
    public function getResolvers(): array
    {
        return $this->resolvers;
    }
}
