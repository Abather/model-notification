<?php

namespace Abather\ModelNotification;

use Abather\ModelNotification\Exceptions\DataMissingException;
use Abather\ModelNotification\Exceptions\DuplicatedTemplateException;
use Abather\ModelNotification\Models\NotificationTemplate;

class TemplateMessage
{
    private NotificationTemplate $templateMessage;
    private $model;
    private $key;
    private $channel;
    private $text;
    private $lang;
    private $with_file;
    private $prevent_including_file;
    private $prob;
    private $required = [
        "model",
        "key",
        "channel",
        "text",
        "lang",
    ];

    public function __construct(NotificationTemplate|string|int|null $templateMessage = null)
    {
        $this->templateMessage = $this->setObject($templateMessage);
    }

    public function setObject(NotificationTemplate|string|int|null $templateMessage): NotificationTemplate
    {
        if (filled($templateMessage)) {
            if ($templateMessage instanceof NotificationTemplate) {
                return $templateMessage;
            }

            $templateMessage = NotificationTemplate::find($templateMessage);

            if (filled($templateMessage)) {
                return $templateMessage;
            }
        }

        return new NotificationTemplate;
    }

    public function key($key): self
    {
        $this->key = $key;
        return $this;
    }

    public function model($model): self
    {
        $this->model = $model;
        return $this;
    }

    public function lang($lang): self
    {
        $this->lang = $lang;
        return $this;
    }

    public function template($text): self
    {
        $this->text = $text;
        return $this;
    }

    public function channel($channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    public function includeFile($with_file = true): self
    {
        $this->with_file = $this->prevent_including_file ? false : $with_file;
        return $this;
    }

    public function preventIncludingFile($prevent_including_file = true): self
    {
        $this->prevent_including_file = $prevent_including_file;
        return $this;
    }

    public function prob(array $prob): self
    {
        $this->prob = $prob;
        return $this;
    }

    public function save(): NotificationTemplate
    {
        $this->validate();
        $this->templateMessage->model = $this->model;
        $this->templateMessage->template = $this->text;
        $this->templateMessage->channel = $this->channel;
        $this->templateMessage->key = $this->key;
        $this->templateMessage->lang = $this->lang;
        $this->templateMessage->with_file = $this->with_file ?? false;
        $this->templateMessage->prob = $this->prob ?? null;

        $this->templateMessage->save();
        $this->templateMessage->refresh();
        return $this->templateMessage;
    }

    public function update(): NotificationTemplate
    {
        if (filled($this->text)) {
            $this->templateMessage->template = $this->text;
        }

        if (filled($this->with_file)) {
            $this->templateMessage->with_file = $this->with_file;
        }

        if (filled($this->prob)) {
            $this->templateMessage->prob = $this->prob;
        }

        $this->templateMessage->save();
        $this->templateMessage->refresh();
        return $this->templateMessage;
    }

    public function validate(): void
    {
        if ($this->templateExists()) {
            throw new DuplicatedTemplateException(
                $this->model,
                $this->key,
                $this->lang,
                $this->channel
            );
        }

        $missing = $this->getMissingFields();
        if (!empty($missing)) {
            throw new DataMissingException($missing);
        }
    }

    public function getMissingFields(): array
    {
        $missing = [];
        foreach ($this->required as $var) {
            if (blank($this->{$var})) {
                $missing[] = $var;
            }
        }

        return $missing;
    }

    public function completeData(): bool
    {
        return empty($this->getMissingFields());
    }

    public function templateDoesNotExists(): bool
    {
        return !$this->templateExists();
    }

    public function templateExists(): bool
    {
        return NotificationTemplate::templateExists($this->model, $this->key, $this->lang, $this->channel);
    }

    public static function make(...$arguments): self
    {
        return new static(...$arguments);
    }
}
