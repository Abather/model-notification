<?php

namespace Abather\ModelNotification\Models;

use Abather\ModelNotification\Exceptions\DuplicatedTemplateException;
use App\Enums\ActivityStatuses;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'with_file' => 'boolean',
        'prob' => 'array'
    ];

    protected static function booted()
    {
        static::creating(function (NotificationTemplate $template) {
            throw_if(
                self::templateExists($template->model, $template->key, $template->lang, $template->channel),
                new DuplicatedTemplateException
            );
        });
    }

    public static function templateExists($model, $key, $lang, $channel): bool
    {
        return self::where("model", $model)
            ->where("key", $key)
            ->where("lang", $lang)
            ->where("channel", $channel)
            ->exists();
    }
}
