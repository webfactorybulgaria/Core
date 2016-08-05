<?php
namespace TypiCMS\Modules\Core\Services;

use Illuminate\Support\Facades\Request;

class PublicNavigator
{
    private $controller;

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    public function currentModule()
    {
        if (!empty($this->controller)) {
            return $this->controller->module;
        }

        return null;
    }

    public function currentPage()
    {
        if (empty($this->currentModule())) return null;

        if ($this->currentModule() != 'page') {
            app()->instance('currentPage', app('typicms')->getPageLinkedToModule($this->currentModule()));
        }

        return app('currentPage');
    }
    
    /**
    * Get array with IDs of pages (path to root)
    *
    * @return - array
    *
    */
    public function pathToRoot()
    {
        $eloquentPage = app('TypiCMS\Modules\Pages\Custom\Repositories\PageInterface');
        $arrParents = $eloquentPage->allForTreeMap();

        $path = [];

        if ($currentPage = $this->currentPage()) {
            $id = (int) $currentPage->id;
            $path[] = $id;
            while (!empty($arrParents[$id])) {
                $path[] = (int) $arrParents[$id];
                $id = (int) $arrParents[$id];
            }
        }

        return $path;
    }
}
