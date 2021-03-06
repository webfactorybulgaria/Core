<?php

namespace TypiCMS\Modules\Core\Providers;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Shells\Commands\CacheKeyPrefix;
use TypiCMS\Modules\Core\Shells\Commands\ClearHtml;
use TypiCMS\Modules\Core\Shells\Commands\Create;
use TypiCMS\Modules\Core\Shells\Commands\Database;
use TypiCMS\Modules\Core\Shells\Commands\Install;
use TypiCMS\Modules\Core\Shells\Commands\Publish;
use TypiCMS\Modules\Core\Shells\Commands\Shell;
use TypiCMS\Modules\Core\Shells\Services\TypiCMS;
use TypiCMS\Modules\Core\Shells\Services\PublicNavigator;
use TypiCMS\Modules\Core\Shells\Services\Upload\FileUpload;
use TypiCMS\Modules\Users\Shells\Models\User;
use TypiCMS\Modules\Users\Shells\Repositories\EloquentUser;

class ModuleProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'core');

        $this->publishes([
            __DIR__.'/../resources/views'        => base_path('resources/views/vendor/core'),
            __DIR__.'/../resources/views/errors' => base_path('resources/views/errors'),
        ], 'views');

        AliasLoader::getInstance()->alias(
            'Navigator',
            'TypiCMS\Modules\Core\Shells\Facades\PublicNavigator'
        );
        AliasLoader::getInstance()->alias(
            'TableList',
            'TypiCMS\Modules\Core\Shells\Facades\TableList'
        );

        // translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'core');

        /*
        |--------------------------------------------------------------------------
        | Commands.
        |--------------------------------------------------------------------------
        */
        $this->commands('command.cachekeyprefix');
        $this->commands('command.clearhtml');
        $this->commands('command.create');
        $this->commands('command.database');
        $this->commands('command.install');
        $this->commands('command.publish');
        $this->commands('command.shell');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(\TypiCMS\Modules\Core\Shells\Providers\RouteServiceProvider::class);

        /*
        |--------------------------------------------------------------------------
        | Init list of modules.
        |--------------------------------------------------------------------------
        */
        Config::set('typicms.modules', []);

        /*
        |--------------------------------------------------------------------------
        | TypiCMS utilities.
        |--------------------------------------------------------------------------
        */
        $this->app->singleton('typicms', function () {
            return new TypiCMS();
        });

        /*
        |--------------------------------------------------------------------------
        | TypiCMS upload service.
        |--------------------------------------------------------------------------
        */
        $this->app->singleton('upload.file', function () {
            return new FileUpload();
        });

        /*
        |--------------------------------------------------------------------------
        | Navigation utilities.
        |--------------------------------------------------------------------------
        */
        $this->app->singleton('public.navigator', function() {
            return new PublicNavigator();
        });

        /*
        |--------------------------------------------------------------------------
        | Sidebar view creator.
        |--------------------------------------------------------------------------
        */
        $app->view->creator('core::admin._sidebar', \TypiCMS\Modules\Core\Shells\Composers\SidebarViewCreator::class);

        /*
        |--------------------------------------------------------------------------
        | View composers.
        |--------------------------------------------------------------------------
        */
        $app->view->composers([
            \TypiCMS\Modules\Core\Shells\Composers\MasterViewComposer::class => '*',
            \TypiCMS\Modules\Core\Shells\Composers\LocaleComposer::class     => '*::public.*',
            \TypiCMS\Modules\Core\Shells\Composers\LocalesComposer::class    => '*::admin.*',
        ]);

        $this->registerCommands();
        $this->registerModuleRoutes();
        $this->registerCoreModules();
        $this->registerTableList();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Register artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->app->bind('command.cachekeyprefix', function () {
            return new CacheKeyPrefix(new Filesystem());
        });
        $this->app->bind('command.clearhtml', function () {
            return new ClearHtml();
        });
        $this->app->bind('command.create', function () {
            return new Create(
                new Filesystem()
            );
        });
        $this->app->bind('command.database', function () {
            return new Database(new Filesystem());
        });
        $this->app->bind('command.install', function () {
            return new Install(
                new EloquentUser(new User()),
                new Filesystem()
            );
        });
        $this->app->bind('command.publish', function () {
            return new Publish(
                new Filesystem()
            );
        });
        $this->app->bind('command.shell', function () {
            return new Shell(
                new Filesystem()
            );
        });
    }

    /**
     * Get routes from pages.
     *
     * @return void
     */
    private function registerModuleRoutes()
    {
        $this->app->singleton('typicms.routes', function (Application $app) {
            try {
                return $app->make(\TypiCMS\Modules\Pages\Shells\Repositories\PageInterface::class)->getForRoutes();
            } catch (Exception $e) {
                return [];
            }
        });
    }

    /**
     * Register core modules.
     *
     * @return void
     */
    protected function registerCoreModules()
    {
        $app = $this->app;
        $app->register(\TypiCMS\Modules\Translations\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Blocks\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Settings\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\History\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Users\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Roles\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Files\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Galleries\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Dashboard\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Menus\Shells\Providers\ModuleProvider::class);
        $app->register(\TypiCMS\Modules\Sitemap\Shells\Providers\ModuleProvider::class);
        // Pages module needs to be at last for routing to work.
        $app->register(\TypiCMS\Modules\Pages\Shells\Providers\ModuleProvider::class);
    }

    /**
     * Register the table list service.
     *
     * @return void
     */
    public function registerTableList()
    {
        $this->app->bind('table.list', \TypiCMS\Modules\Core\Shells\Services\TableList\SmartTableList::class);
    }
}
