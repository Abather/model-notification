<?php

namespace Abather\ModelNotification;

use Abather\ModelNotification\Models\NotificationTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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

    public function getTemplateMessageProb($key, $lang, $channel, $prob = null): array|string
    {
        $template = self::getTemplateMessage($key, $lang, $channel);

        if (blank($template)) {
            return [];
        }

        if (blank($template->prob)) {
            return [];
        }

        $template_probs = $template->prob;

        if (filled($prob)) {
            if (!array_key_exists($prob, $template_probs)) {
                return "";
            }

            $template_probs = $template_probs[$prob];

            return $this->replaceVariables($template_probs, $key, $lang, $channel);
        }

        $probs = [];

        foreach ($template_probs as $key => $value) {
            $probs[$key] = $this->replaceVariables($value, $key, $lang, $channel);
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

        if (self::isRelationshipVariable($variable)) {
            return $this->getRelationshipValue($variable);
        }

        return $this->{$variable};
    }

    public static function isRelationshipVariable($variable): bool
    {
        if (!Str::contains($variable, self::getRelationVariableSymbol())) {
            return false;
        }

        if (Str::startsWith($variable, self::getRelationVariableSymbol())) {
            return false;
        }

        return !Str::endsWith($variable, self::getRelationVariableSymbol());
    }

    public static function isFileVariable($variable): bool
    {
        if (isset(static::$file_variables)) {
            $file_variables = static::$file_variables;
        } else {
            $file_variables = config("model-notification.file_variables");
        }

        return in_array($variable, $file_variables);
    }

    public function getRelationshipValue($variable): string
    {
        $variable = explode(self::getRelationVariableSymbol(), $variable);
        $model = array_shift($variable);
        $attribute = array_shift($variable);

        if (blank($this->{$model})) {
            return "";
        }

        return $this->{$model}->{$attribute};
    }

    public function getFilePath(): string
    {
        return Storage::url($this->getFileName());
    }

    public function getFileObject()
    {
        return Storage::get($this->getFileName());
    }

    public function getFileName(): string
    {
        if (isset(static::$file_name)) {
            return static::$file_name;
        }

        return config("model-notification.file_name");
    }

    public static function getVariableStarter(): string
    {
        return config("model-notification.variable_starter", "[");
    }

    public static function getVariableEnder(): string
    {
        return config("model-notification.variable_ender", "]");
    }

    public static function getRelationVariableSymbol(): string
    {
        return config("model-notification.relationship_variable_symbol", "->");
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
