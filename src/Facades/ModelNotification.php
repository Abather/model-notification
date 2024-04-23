<?php

namespace Abather\ModelNotification\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Abather\ModelNotification\ModelNotification
 */
class ModelNotification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Abather\ModelNotification\ModelNotification::class;
    }
}
