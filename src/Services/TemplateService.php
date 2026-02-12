<?php

namespace Abather\ModelNotification\Services;

use Abather\ModelNotification\Contracts\TemplateCacheInterface;
use Abather\ModelNotification\Models\NotificationTemplate;
use Abather\ModelNotification\DTOs\TemplateDTO;
use Abather\ModelNotification\Exceptions\TemplateNotFoundException;
use Abather\ModelNotification\Exceptions\DataMissingException;
use Abather\ModelNotification\Exceptions\TemplateValidationException;
use Abather\ModelNotification\Resolvers\VariableResolver;
use Abather\ModelNotification\Resolvers\SimpleVariableResolver;
use Abather\ModelNotification\Resolvers\RelationshipVariableResolver;
use Abather\ModelNotification\Resolvers\MethodVariableResolver;
use Abather\ModelNotification\Validators\TemplateValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TemplateService
{
    protected TemplateCacheInterface $cache;

    protected VariableResolver $variableResolver;

    protected TemplateValidator $validator;

    public function __construct(TemplateCacheInterface $cache)
    {
        $this->cache = $cache;

        // Initialize variable resolver with default resolvers
        $this->variableResolver = new VariableResolver([
            new SimpleVariableResolver(),
            new RelationshipVariableResolver(),
            new MethodVariableResolver(),
        ]);

        // Initialize validator
        $this->validator = new TemplateValidator();
    }

    /**
     * Create a new template.
     */
    public function createTemplate(array $data): NotificationTemplate
    {
        $this->validateRequiredFields($data, ['model', 'key', 'lang', 'channel', 'template']);

        // Validate template
        $result = $this->validator->validate($data['template']);
        if (!$result->isValid()) {
            throw new TemplateValidationException(
                $result->getErrors(),
                $result->getWarnings()
            );
        }

        $dto = TemplateDTO::create($data);

        return $this->cache->save($dto);
    }

    /**
     * Update an existing template.
     */
    public function updateTemplate(int $id, array $data): NotificationTemplate
    {
        $dto = TemplateDTO::update($id, $data);

        // Validate template if provided
        if (!empty($dto->template)) {
            $result = $this->validator->validate($dto->template);
            if (!$result->isValid()) {
                throw new TemplateValidationException(
                    $result->getErrors(),
                    $result->getWarnings()
                );
            }
        }

        return $this->cache->update($id, $dto);
    }

    /**
     * Get a template by key, language, channel, and model.
     */
    public function getTemplate(string $key, string $lang, string $channel, Model $model): NotificationTemplate
    {
        $template = $this->cache->findByKey($key, $lang, $channel, get_class($model));

        if (!$template) {
            // Try fallback language
            $fallbackLang = config('model-notification.fallback_lang', 'ar');
            if ($fallbackLang !== $lang) {
                $template = $this->cache->findByKey($key, $fallbackLang, $channel, get_class($model));
            }
        }

        if (!$template) {
            throw new TemplateNotFoundException(
                get_class($model),
                $key,
                $lang,
                $channel
            );
        }

        return $template;
    }

    /**
     * Render a template with variable replacement.
     */
    public function renderTemplate(string $key, string $lang, string $channel, Model $model): string
    {
        $template = $this->getTemplate($key, $lang, $channel, $model);

        return $this->variableResolver->resolve($template->template, $model, $key, $lang, $channel);
    }

    /**
     * Delete a template by ID.
     */
    public function deleteTemplate(int $id): bool
    {
        return $this->cache->delete($id);
    }

    /**
     * Get all templates for a model.
     */
    public function getTemplatesForModel(string $model): \Illuminate\Database\Eloquent\Collection
    {
        return $this->cache->findForModel($model);
    }

    /**
     * Clear cache for a specific model.
     */
    public function clearModelCache(string $model): void
    {
        $this->cache->clearModelCache($model);

        Log::info('Model cache cleared', ['model' => $model]);
    }

    /**
     * Clear cache for a specific template key.
     */
    public function clearTemplateCache(string $key): void
    {
        $this->cache->clearTemplateCache($key);

        Log::info('Template cache cleared', ['key' => $key]);
    }

    /**
     * Validate a template.
     */
    public function validateTemplate(string $template, ?Model $model = null, array $knownVariables = []): \Abather\ModelNotification\DTOs\ValidationResult
    {
        return $this->validator->validate($template, $model, $knownVariables);
    }

    /**
     * Get the variable resolver.
     */
    public function getVariableResolver(): VariableResolver
    {
        return $this->variableResolver;
    }

    /**
     * Set the variable resolver.
     */
    public function setVariableResolver(VariableResolver $resolver): self
    {
        $this->variableResolver = $resolver;
        return $this;
    }

    /**
     * Get the validator.
     */
    public function getValidator(): TemplateValidator
    {
        return $this->validator;
    }

    /**
     * Set the validator.
     */
    public function setValidator(TemplateValidator $validator): self
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Validate required fields.
     */
    protected function validateRequiredFields(array $data, array $required): void
    {
        $missing = array_diff($required, array_keys($data));

        if (!empty($missing)) {
            throw new DataMissingException($missing);
        }
    }
}
