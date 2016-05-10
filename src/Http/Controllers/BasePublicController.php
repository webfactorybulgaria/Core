<?php

namespace TypiCMS\Modules\Core\Http\Controllers;

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

        /*
        |--------------------------------------------------------------------------
        | Navigation utilities.
        |--------------------------------------------------------------------------
        */
        app()->instance('public.navigator', new PublicNavigator($this));

    }
}
