<?php

namespace Abather\ModelNotification;

use Abather\ModelNotification\Models\NotificationTemplate;
use Illuminate\Support\Str;

trait Notifier
{
    public static function makeTemplateMessage(...$arguments): TemplateMessage
    {
        return TemplateMessage::make(...$arguments)->model(self::class);
    }

    public static function getMessage($key, $lang, $channel): NotificationTemplate|null
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

    public static function getMessages()
    {
        return self::notificationTemplates()->get();
    }

    public static function notificationTemplates()
    {
        return NotificationTemplate::forModel(self::class);
    }

    public function getMessageText($key, $lang, $channel): string
    {
        $template = self::getMessage($key, $lang, $channel);
        return $this->replaceVariables($template->template, $key, $lang, $channel);
    }

    public function getFile($key, $lang, $channel, $file_path = true): string|null
    {
        $template = self::getMessage($key, $lang, $channel);

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

    public function getNextVariable($text): string|null
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

    public static function getVariableStarter(): string
    {
        return config("model-notification.variable_starter", "[");
    }

    public static function getVariableEnder(): string
    {
        return config("model-notification.variable_ender", "]");
    }
}
