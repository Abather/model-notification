<?php

namespace Abather\ModelNotification\Cache;

use Abather\ModelNotification\Contracts\TemplateCacheInterface;
use Abather\ModelNotification\Contracts\TemplateRepositoryInterface;
use Abather\ModelNotification\Models\NotificationTemplate;
use Abather\ModelNotification\DTOs\TemplateDTO;
use Abather\ModelNotification\Exceptions\CacheException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TemplateCache implements TemplateCacheInterface
{
    protected TemplateRepositoryInterface $repository;

    protected int $ttl;

    protected string $prefix;

    public function __construct(
        TemplateRepositoryInterface $repository,
        ?int $ttl = null,
        ?string $prefix = null
    ) {
        $this->repository = $repository;
        $this->ttl = $ttl ?? config('model-notification.cache.ttl', 86400);
        $this->prefix = $prefix ?? config('model-notification.cache.prefix', 'model_notification');
    }

    /**
     * Find a template by key, language, channel, and model.
     */
    public function findByKey(string $key, string $lang, string $channel, string $model): ?NotificationTemplate
    {
        if (!config('model-notification.cache.enabled', true)) {
            return $this->repository->findByKey($key, $lang, $channel, $model);
        }

        $cacheKey = $this->getCacheKey($model, $key, $lang, $channel);
        $tags = $this->getCacheTags($model, $key);

        try {
            return Cache::tags($tags)
                ->remember($cacheKey, $this->ttl, function () use ($key, $lang, $channel, $model) {
                    Log::debug('Cache miss for template', [
                        'model' => $model,
                        'key' => $key,
                        'lang' => $lang,
                        'channel' => $channel,
                    ]);

                    return $this->repository->findByKey($key, $lang, $channel, $model);
                });
        } catch (\Exception $e) {
            Log::warning('Cache operation failed, falling back to database', [
                'operation' => 'findByKey',
                'error' => $e->getMessage(),
            ]);

            // Fall back to database if cache fails
            return $this->repository->findByKey($key, $lang, $channel, $model);
        }
    }

    /**
     * Find all templates for a specific model.
     */
    public function findForModel(string $model): Collection
    {
        if (!config('model-notification.cache.enabled', true)) {
            return $this->repository->findForModel($model);
        }

        $cacheKey = $this->getModelCacheKey($model);
        $tags = $this->getModelCacheTags($model);

        try {
            return Cache::tags($tags)
                ->remember($cacheKey, $this->ttl, function () use ($model) {
                    Log::debug('Cache miss for model templates', ['model' => $model]);

                    return $this->repository->findForModel($model);
                });
        } catch (\Exception $e) {
            Log::warning('Cache operation failed, falling back to database', [
                'operation' => 'findForModel',
                'error' => $e->getMessage(),
            ]);

            return $this->repository->findForModel($model);
        }
    }

    /**
     * Save a new template.
     */
    public function save(TemplateDTO $dto): NotificationTemplate
    {
        $template = $this->repository->save($dto);

        // Clear cache for this model and key
        $this->clearModelCache($dto->model);
        $this->clearTemplateCache($dto->key);

        Log::debug('Cache cleared after template save', [
            'model' => $dto->model,
            'key' => $dto->key,
        ]);

        return $template;
    }

    /**
     * Update an existing template.
     */
    public function update(int $id, TemplateDTO $dto): NotificationTemplate
    {
        $template = $this->repository->update($id, $dto);

        // Clear cache for this model and key
        if ($dto->model) {
            $this->clearModelCache($dto->model);
        }
        if ($dto->key) {
            $this->clearTemplateCache($dto->key);
        }

        Log::debug('Cache cleared after template update', [
            'id' => $id,
            'model' => $dto->model,
            'key' => $dto->key,
        ]);

        return $template;
    }

    /**
     * Delete a template by ID.
     */
    public function delete(int $id): bool
    {
        // Get the template before deleting to know what cache to clear
        $template = NotificationTemplate::find($id);

        $deleted = $this->repository->delete($id);

        if ($deleted && $template) {
            $this->clearModelCache($template->model);
            $this->clearTemplateCache($template->key);

            Log::debug('Cache cleared after template delete', [
                'id' => $id,
                'model' => $template->model,
                'key' => $template->key,
            ]);
        }

        return $deleted;
    }

    /**
     * Check if a template exists.
     */
    public function exists(string $model, string $key, string $lang, string $channel): bool
    {
        return $this->repository->exists($model, $key, $lang, $channel);
    }

    /**
     * Clear cache for a specific model.
     */
    public function clearModelCache(string $model): void
    {
        if (!config('model-notification.cache.enabled', true)) {
            return;
        }

        try {
            Cache::tags($this->getModelCacheTags($model))->flush();
        } catch (\Exception $e) {
            Log::warning('Failed to clear model cache', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear cache for a specific template key.
     */
    public function clearTemplateCache(string $key): void
    {
        if (!config('model-notification.cache.enabled', true)) {
            return;
        }

        try {
            Cache::tags($this->getTemplateCacheTags($key))->flush();
        } catch (\Exception $e) {
            Log::warning('Failed to clear template cache', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate cache key for a specific template.
     */
    protected function getCacheKey(string $model, string $key, string $lang, string $channel): string
    {
        $modelHash = md5($model);
        return "{$this->prefix}:template:{$modelHash}:{$key}:{$lang}:{$channel}";
    }

    /**
     * Generate cache key for all templates of a model.
     */
    protected function getModelCacheKey(string $model): string
    {
        $modelHash = md5($model);
        return "{$this->prefix}:model:{$modelHash}:all";
    }

    /**
     * Get cache tags for a specific template.
     */
    protected function getCacheTags(string $model, string $key): array
    {
        $modelTag = $this->getModelCacheTags($model);
        $keyTag = $this->getTemplateCacheTags($key);

        return array_merge($modelTag, $keyTag);
    }

    /**
     * Get cache tags for a model.
     */
    protected function getModelCacheTags(string $model): array
    {
        $modelHash = md5($model);
        return [
            "{$this->prefix}:model:{$modelHash}",
        ];
    }

    /**
     * Get cache tags for a template key.
     */
    protected function getTemplateCacheTags(string $key): array
    {
        return [
            "{$this->prefix}:template:{$key}",
        ];
    }
}
