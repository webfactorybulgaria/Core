<?php

namespace TypiCMS\Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;
use TypiCMS\Modules\Core\Shells\Facades\TypiCMS;
use TypiCMS\Modules\Core\Shells\Traits\HtmlCacheEvents;

abstract class Base extends Model
{
    use HtmlCacheEvents;
    /**
     * Get preview uri.
     *
     * @return null|string string or null
     */
    public function previewUri()
    {
        if (!$this->id) {
            return '/';
        }

        return url($this->uri(config('translatable.locale')));
    }

    /**
     * Get public uri.
     *
     * @return string
     */
    public function uri($locale = null)
    {
        $locale = $locale ?: config('app.locale');
        $page = TypiCMS::getPageLinkedToModule($this->getTable());
        if ($page) {
            return $page->uri($locale).'/'.$this->translate($locale)->slug;
        }

        return '/';
    }

    /**
     * Attach files to model.
     *
     * @param Builder $query
     * @param bool    $all   : all models or online models
     *
     * @return Builder $query
     */
    public function scopeFiles(Builder $query, $all = false)
    {
        return $query->with(
            ['files' => function (Builder $query) use ($all) {
                if ($all)
                    $query->online();
                $query->orderBy('position', 'asc');
            }]
        );
    }

    /**
     * Get models that have online non empty translation.
     *
     * @param Builder $query
     *
     * @return Builder $query
     */
    public function scopeOnline(Builder $query)
    {
        if (method_exists($this, 'translations')) {
            if (!Request::input('preview')) {
                $query->where('status', 1);
            }
            $query->where('locale', config('app.locale'));
            return $query;
        } else {
            return $query->where('status', 1);
        }
    }

    /**
     * Get online galleries.
     *
     * @param Builder $query
     *
     * @return Builder $query
     */
    public function scopeWithOnlineGalleries(Builder $query)
    {
        if (!method_exists($this, 'galleries')) {
            return $query;
        }

        return $query->with(
            [
                'galleries.files',
                'galleries' => function (MorphToMany $query) {
                    $query->online();
                },
            ]
        );
    }

    /**
     * Order items according to GET value or model value, default is id asc.
     *
     * @param Builder $query
     *
     * @return Builder $query
     */
    public function scopeOrder(Builder $query)
    {
        if ($order = config('typicms.'.$this->getTable().'.order')) {
            foreach ($order as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    /**
     * A model has many tags.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tags()
    {
        return $this->morphToMany('TypiCMS\Modules\Tags\Shells\Models\Tag', 'taggable')
            ->orderBy('tag')
            ->withTimestamps();
    }

    /**
     * Get back office’s edit url of model.
     *
     * @return string|void
     */
    public function editUrl()
    {
        try {
            return route('admin::edit-'.str_singular($this->getTable()), $this->id);
        } catch (InvalidArgumentException $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Get back office’s index of models url.
     *
     * @return string|void
     */
    public function indexUrl()
    {
        try {
            return route('admin::index-'.$this->getTable());
        } catch (InvalidArgumentException $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Generic Translate method to maintain compatibility
     * when a model doesn't have Translatable trait.
     *
     * @param string $lang
     *
     * @return $this
     */
    public function translate($lang = null)
    {
        return $this;
    }

    /**
     * Models without translatable trait doesn’t have translation.
     *
     * @param string $locale
     *
     * @return bool
     */
    public function hasTranslation($locale)
    {
        return false;
    }
}
