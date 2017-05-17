<?php

namespace TypiCMS\Modules\Core\Providers;

use TypiCMS\Modules\Core\Shells\Providers\BaseRouteServiceProvider as ServiceProvider;

use Illuminate\Routing\Router;
use URL;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'TypiCMS\Modules\Core\Shells\Http\Controllers';

    /**
     * Define the routes for the application.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function map(Router $router)
    {
        $router->group(['namespace' => $this->namespace], function (Router $router) {
            /*
             * Admin routes
             */
            $router->get('admin/_locale/{locale}', 'LocaleController@setContentLocale')->name('admin::change-locale');
        });

        /*
         * Api routes
         */
        $router->get('admin/duplicate/{alias}/{resource}', function($alias, $resource) {

            $repository = app(ucfirst($alias));
            $oldItem = $repository::make()->skip()->find($resource);
            $newItem = $oldItem->replicate();


            if(isset($newItem->system_name)) $newItem->system_name .= ' (copy)';
            unset($newItem->translations);
            unset($newItem->translatedAttributes);
            dd($newItem->getAttributes());
            $newItem = $newItem->create($newItem->getAttributes());

            foreach ($oldItem->translations as $translation) {
                $parent_id = $oldItem->getRelationKey();
                $translation->{$parent_id} = $newItem->id;
                if(isset($translation->title)) $translation->title .= ' (copy)';
                $translation = $translation->replicate();
                $translation->save();
            }

            return redirect(URL::previous());
        });
    }
}
