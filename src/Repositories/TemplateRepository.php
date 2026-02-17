<?php

namespace Abather\ModelNotification\Repositories;

use Abather\ModelNotification\Contracts\TemplateRepositoryInterface;
use Abather\ModelNotification\Exceptions\TemplateNotFoundException;
use Abather\ModelNotification\Exceptions\DuplicatedTemplateException;
use Abather\ModelNotification\Models\NotificationTemplate;
use Abather\ModelNotification\DTOs\TemplateDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TemplateRepository implements TemplateRepositoryInterface
{
    public function __construct()
    {
    }
    /**
     * Find a template by key, language, channel, and model.
     */
    public function findByKey(string $key, string $lang, string $channel, string $model): ?NotificationTemplate
    {
        try {
            return NotificationTemplate::query()
                ->forModel($model)
                ->forKey($key)
                ->forLang($lang)
                ->forChannel($channel)
                ->first();
        } catch (\Exception $e) {
            Log::error('Failed to find template by key', [
                'model' => $model,
                'key' => $key,
                'lang' => $lang,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Find all templates for a specific model.
     */
    public function findForModel(string $model): Collection
    {
        try {
            return NotificationTemplate::query()
                ->forModel($model)
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to find templates for model', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Save a new template.
     */
    public function save(TemplateDTO $dto): NotificationTemplate
    {
        try {
            // Check for duplicate before saving
            if ($this->exists($dto->model, $dto->key, $dto->lang, $dto->channel)) {
                throw new DuplicatedTemplateException(
                    $dto->model,
                    $dto->key,
                    $dto->lang,
                    $dto->channel
                );
            }

            $template = new NotificationTemplate();
            $template->fill($dto->toFillableArray());
            $template->save();

            Log::info('Template created', [
                'id' => $template->id,
                'model' => $dto->model,
                'key' => $dto->key,
            ]);

            return $template;
        } catch (DuplicatedTemplateException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to save template', [
                'model' => $dto->model,
                'key' => $dto->key,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing template.
     */
    public function update(int $id, TemplateDTO $dto): NotificationTemplate
    {
        try {
            $template = NotificationTemplate::findOrFail($id);

            // Check for duplicate if key/lang/channel/model changed
            if ($dto->model && $dto->key && $dto->lang && $dto->channel) {
                $existing = $this->findByKey($dto->key, $dto->lang, $dto->channel, $dto->model);
                if ($existing && $existing->id !== $id) {
                    throw new DuplicatedTemplateException(
                        $dto->model,
                        $dto->key,
                        $dto->lang,
                        $dto->channel
                    );
                }
            }

            $template->fill($dto->toFillableArray());

            $template->save();

            Log::info('Template updated', [
                'id' => $id,
                'model' => $dto->model,
                'key' => $dto->key,
            ]);

            return $template->fresh();
        } catch (DuplicatedTemplateException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update template', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a template by ID.
     */
    public function delete(int $id): bool
    {
        try {
            $template = NotificationTemplate::findOrFail($id);
            $deleted = $template->delete();

            if ($deleted) {
                Log::info('Template deleted', ['id' => $id]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to delete template', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if a template exists.
     */
    public function exists(string $model, string $key, string $lang, string $channel): bool
    {
        return NotificationTemplate::query()
            ->forModel($model)
            ->forKey($key)
            ->forLang($lang)
            ->forChannel($channel)
            ->exists();
    }
}
