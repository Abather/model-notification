<?php

namespace Abather\ModelNotification;

use Abather\ModelNotification\Models\NotificationTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait Notifier
{
    public static function makeTemplateMessage(...$arguments): TemplateMessage
    {
        return TemplateMessage::make(...$arguments)
            ->model(self::class)
            ->preventIncludingFile(self::preventIncludingFile());
    }

    public static function getTemplateMessage($key, $lang, $channel): ?NotificationTemplate
    {
        $query = self::notificationTemplates()
            ->forKey($key)
            ->forChannel($channel);

        $template = $query->forLang($lang)
            ->first();

        if (blank($template)) {
            $query->forLang(config("model-notification.fallback_lang"))
                ->first();
        }

        return $template;
    }

    public static function getTemplateMessages()
    {
        return self::notificationTemplates()->get();
    }

    public static function notificationTemplates()
    {
        return NotificationTemplate::forModel(self::class);
    }

    public function getTemplateMessageText($key, $lang, $channel): string
    {
        $template = self::getTemplateMessage($key, $lang, $channel);

        if (blank($template)) {
            return "";
        }

        return $this->replaceVariables($template->template, $key, $lang, $channel);
    }

    public function getTemplateMessageProb($key, $lang, $channel): array
    {
        $template = self::getTemplateMessage($key, $lang, $channel);

        if (blank($template)) {
            return [];
        }

        if (blank($template->prob)) {
            return [];
        }

        $probs = [];

        foreach ($template->prob as $key => $prob) {
            $probs[$key] = $this->replaceVariables($prob, $key, $lang, $channel);
        }

        return $probs;
    }

    public function getFile($key, $lang, $channel, $file_path = true): ?string
    {
        $template = self::getTemplateMessage($key, $lang, $channel);

        if (!$template->with_file) {
            return null;
        }

        if ($file_path) {
            return $this->getFilePath();
        }

        return $this->getFileObject();
    }

    public function replaceVariables($text, $key, $lang, $channel): string
    {
        $starter = self::getVariableStarter();
        $ender = self::getVariableEnder();

        $variable = $this->getNextVariable($text);

        while (filled($variable)) {
            $text = Str::replace(
                $starter.$variable.$ender,
                $this->getVariableValue($variable, $key, $lang, $channel),
                $text
            );

            $variable = $this->getNextVariable($text);
        }

        return $text;
    }

    public function getNextVariable($text): ?string
    {
        $starter = self::getVariableStarter();
        $ender = self::getVariableEnder();

        $variable = Str::betweenFirst($text, $starter, $ender);

        if (Str::length($variable) === Str::length($text)) {
            return null;
        }

        return $variable;
    }

    public function getVariableValue($variable, $key, $lang, $channel): string
    {
        if (self::isFileVariable($variable)) {
            return $this->getFile($key, $lang, $channel);
        }

        return $this->{$variable};
    }

    public function isFileVariable($variable)
    {
        if (filled($this->file_variables)) {
            $file_variables = $this->file_variables;
        } else {
            $file_variables = config("model-notification.file_variables");
        }

        return in_array($variable, $file_variables);
    }

    public function getFilePath(): string
    {
        return "";
    }

    public function getFileObject(): ?File
    {
        return null;
    }

    public static function getVariableStarter(): string
    {
        return config("model-notification.variable_starter", "[");
    }

    public static function getVariableEnder(): string
    {
        return config("model-notification.variable_ender", "]");
    }

    public static function preventIncludingFile(): bool
    {
        if (isset(static::$prevent_including_file)) {
            $prevent = static::$prevent_including_file;
        } else {
            $prevent = config("model-notification.prevent_including_file");
        }

        return $prevent;
    }
}
