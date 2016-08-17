<?php

namespace TypiCMS\Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;
use TableList;

abstract class BaseApiController extends Controller
{
    /**
     *  Array of endpoints that do not require authorization.
     */
    protected $publicEndpoints = [];

    protected $repository;

    public function __construct($repository = null)
    {
        $this->middleware('api', ['except' => $this->publicEndpoints]);
        $this->repository = $repository;
    }

    /**
     * Perform any modification on the models data if necessary
     *
     */
    protected function transform($models)
    {
        return $models;
    }

    /**
     * List resources.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($builder = null)
    {
        $request = Request::all();

        if (!empty($request["tableState"])) { // TODO - a factory here would be nice
            $builder = $builder ?: $this->repository->getModel()->query();

            $list = TableList::apply($builder);
            $perPage = $list->getPerPage();

            $models = $builder->paginate($perPage);

        } else {
            $all = $this->repository->all([], true);
            $models['data'] = $all;

        }

        $models = [$this->transform($models)];

        return response()->json($models, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($model)
    {
        return response()->json($model, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($model)
    {
        return response()->json($model, 200);
    }

}
