<?php

namespace Abather\ModelNotification\Contracts;

use Abather\ModelNotification\Models\NotificationTemplate;
use Abather\ModelNotification\TemplateMessage;

interface Notifier
{
    public static function makeTemplateMessage(...$arguments): TemplateMessage;

    public static function notificationTemplates();

    public static function getTemplateMessage($key, $lang, $channel): NotificationTemplate|null;

    public static function getTemplateMessages();

    public function getTemplateMessageText($key, $lang, $channel): string;

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
