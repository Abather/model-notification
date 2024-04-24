<?php

namespace Abather\ModelNotification\Contracts;

use Abather\ModelNotification\Models\NotificationTemplate;
use Abather\ModelNotification\TemplateMessage;

interface Notifier
{
    public static function makeTemplateMessage(): TemplateMessage;

    public static function updateMessage(
        NotificationTemplate $template,
        $key,
        $lang,
        $channel,
        $text,
        bool $with_file
    ): bool;

    public static function getMessage($key, $lang, $channel): NotificationTemplate|null;

    public static function getMessages();

    public function getMessageText($key, $lang, $channel): string;

    public function getFile($key, $lang, $channel, $file_path = true): string|null;

    public function getFilePath(): string;

    public function getFileObject();

    public function replaceVariables($text, $key, $lang, $channel): string;

    public function getNextVariable($text): string|null;

    public function getVariableValue($variable, $key, $lang, $channel): string;

    public function isFileVariable($variable);

    public static function getVariableStarter(): string;

    public static function getVariableEnder(): string;
}
