<?php

namespace TypiCMS\Modules\Core\Traits;

use Dimsav\Translatable\Translatable as BaseTranslatable;
use TypiCMS\Modules\Core\Scopes\TranslatableScope;

trait Translatable
{
    use BaseTranslatable;

    /**
     * Boot the trait.
     */
    public static function bootTranslatable()
    {
        static::addGlobalScope(new TranslatableScope);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->getQualifiedKeyName();
    }

}
