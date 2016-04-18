<?php

namespace TypiCMS\Modules\Core\Traits;

trait VersioningEvents
{
    /**
     * Event to save a version.
     *
     * @return void
     */
    public static function bootVersioningEvents()
    {
        static::saved(function ($model) {
            // dd(json_encode($model));
        });

        static::deleted(function ($model) {
            // dd($this);
        });
    }
}
