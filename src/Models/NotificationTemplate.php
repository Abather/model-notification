<?php

namespace Abather\ModelNotification\Models;

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
//        static::creating(function (NotificationTemplate $template) {
//            //through an error if it is already exists
//        });
    }
}
