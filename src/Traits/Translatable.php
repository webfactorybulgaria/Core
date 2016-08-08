<?php

namespace TypiCMS\Modules\Core\Traits;

use Dimsav\Translatable\Translatable as BaseTranslatable;
use TypiCMS\Modules\Core\Shells\Scopes\TranslatableScope;

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


    /**
     * Removes the default translatable toArray functionality as it is not needed anymore
     *
     * @return array
     */
    public function toArray()
    {
        return parent::toArray();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        return parent::getAttribute($key);
    }

    public function hasTranslation($locale = null)
    {
        if ($locale && ($locale != config('app.locale'))) {

            $locale = $locale ?: $this->locale();

            foreach ($this->translations as $translation) {
                if ($translation->getAttribute($this->getLocaleKey()) == $locale) {
                    return true;
                }
            }

            return false;
        }

        $field = $this->translatedAttributes[0];

        return $this->$field !== null;
    }
}
