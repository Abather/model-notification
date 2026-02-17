<?php

namespace Abather\ModelNotification\Models;

use Abather\ModelNotification\Exceptions\DuplicatedTemplateException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model',
        'key',
        'lang',
        'channel',
        'template',
        'with_file',
        'prob',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'with_file' => 'boolean',
        'prob' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
    ];

    protected static function booted()
    {
        static::creating(function (NotificationTemplate $template) {
            throw_if(
                self::templateExists($template->model, $template->key, $template->lang, $template->channel),
                new DuplicatedTemplateException(
                    $template->model,
                    $template->key,
                    $template->lang,
                    $template->channel
                )
            );
        });
    }

    public function scopeForModel(Builder $query, string $model): void
    {
        $query->where("model", $model);
    }

    public function scopeForChannel(Builder $query, string $channel): void
    {
        $query->where("channel", $channel);
    }

    public function scopeForLang(Builder $query, string $lang): void
    {
        $query->where("lang", $lang);
    }

    public function scopeForKey(Builder $query, string $key): void
    {
        $query->where("key", $key);
    }



    public static function templateExists(string $model, string $key, string $lang, string $channel): bool
    {
        return self::where("model", $model)
            ->where("key", $key)
            ->where("lang", $lang)
            ->where("channel", $channel)
            ->exists();
    }
}
