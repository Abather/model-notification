<?php

namespace Abather\ModelNotification\Contracts;

use Abather\ModelNotification\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Collection;
use Abather\ModelNotification\DTOs\TemplateDTO;

interface TemplateCacheInterface
{
    /**
     * Find a template by key, language, channel, and model.
     *
     * @param string $key
     * @param string $lang
     * @param string $channel
     * @param string $model
     * @return NotificationTemplate|null
     */
    public function findByKey(string $key, string $lang, string $channel, string $model): ?NotificationTemplate;

    /**
     * Find all templates for a specific model.
     *
     * @param string $model
     * @return Collection<int, NotificationTemplate>
     */
    public function findForModel(string $model): Collection;

    /**
     * Save a new template.
     *
     * @param TemplateDTO $dto
     * @return NotificationTemplate
     */
    public function save(TemplateDTO $dto): NotificationTemplate;

    /**
     * Update an existing template.
     *
     * @param int $id
     * @param TemplateDTO $dto
     * @return NotificationTemplate
     */
    public function update(int $id, TemplateDTO $dto): NotificationTemplate;

    /**
     * Delete a template by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Check if a template exists.
     *
     * @param string $model
     * @param string $key
     * @param string $lang
     * @param string $channel
     * @return bool
     */
    public function exists(string $model, string $key, string $lang, string $channel): bool;

    /**
     * Clear cache for a specific model.
     *
     * @param string $model
     * @return void
     */
    public function clearModelCache(string $model): void;

    /**
     * Clear cache for a specific template key.
     *
     * @param string $key
     * @return void
     */
    public function clearTemplateCache(string $key): void;
}
