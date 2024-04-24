<?php

namespace Abather\ModelNotification\Models;

use Abather\ModelNotification\Exceptions\DuplicatedTemplateException;
use App\Enums\ActivityStatuses;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeForModel(Builder $query, $model): void
    {
        $query->where("model", $model);
    }

    public function scopeForChannel(Builder $query, $channel): void
    {
        $query->where("channel", $channel);
    }

    public function scopeForLang(Builder $query, $lang): void
    {
        $query->where("lang", $lang);
    }

    public function scopeForKey(Builder $query, $key): void
    {
        $query->where("key", $key);
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
