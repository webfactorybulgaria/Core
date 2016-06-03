<?php

namespace TypiCMS\Modules\Core\Http\Controllers;

use Navigator;
use Illuminate\Routing\Controller;
use TypiCMS\Modules\Core\Services\PublicNavigator;

abstract class BasePublicController extends Controller
{
    protected $repository;
    public $module;

    public function __construct($repository = null, $module = null)
    {
        $this->middleware('public');
        $this->repository = $repository;
        $this->module = $module;
        Navigator::setController($this);
    }
}
