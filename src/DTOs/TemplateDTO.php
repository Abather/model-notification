<?php

namespace Abather\ModelNotification\DTOs;

class TemplateDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $model,
        public readonly string $key,
        public readonly string $lang,
        public readonly string $channel,
        public readonly string $template,
        public readonly bool $withFile,
        public readonly ?array $prob,
    ) {
    }

    /**
     * Create a DTO for a new template.
     */
    public static function create(array $data): self
    {
        return new self(
            id: null,
            model: $data['model'],
            key: $data['key'],
            lang: $data['lang'],
            channel: $data['channel'],
            template: $data['template'],
            withFile: $data['with_file'] ?? false,
            prob: $data['prob'] ?? null,
        );
    }

    /**
     * Create a DTO for updating an existing template.
     */
    public static function update(int $id, array $data): self
    {
        return new self(
            id: $id,
            model: $data['model'] ?? '',
            key: $data['key'] ?? '',
            lang: $data['lang'] ?? '',
            channel: $data['channel'] ?? '',
            template: $data['template'] ?? '',
            withFile: $data['with_file'] ?? false,
            prob: $data['prob'] ?? null,
        );
    }

    /**
     * Get only the fillable attributes.
     */
    public function toFillableArray(): array
    {
        return array_filter([
            'model' => $this->model ?: null,
            'key' => $this->key ?: null,
            'lang' => $this->lang ?: null,
            'channel' => $this->channel ?: null,
            'template' => $this->template ?: null,
            'with_file' => $this->withFile,
            'prob' => $this->prob,
        ], fn($value) => $value !== null);
    }
}
