<?php

namespace Abather\ModelNotification\Contracts;

use Abather\ModelNotification\Models\NotificationTemplate;
use Abather\ModelNotification\TemplateMessage;
use Illuminate\Support\Facades\File;

interface Notifier
{
    public static function makeTemplateMessage(...$arguments): TemplateMessage;

    public static function notificationTemplates();

    public static function getTemplateMessage($key, $lang, $channel): ?NotificationTemplate;

    public static function getTemplateMessages();

    public function getTemplateMessageText($key, $lang, $channel): string;

    public function getTemplateMessageProb($key, $lang, $channel): array|string;

    public function getFile($key, $lang, $channel, $file_path = true): ?string;

    public function getFilePath(): string;

    public function getFileObject();

    public function replaceVariables($text, $key, $lang, $channel): string;

    public static function isFileVariable($variable): bool;

    public static function getVariableStarter(): string;

    public static function getVariableEnder(): string;
}
