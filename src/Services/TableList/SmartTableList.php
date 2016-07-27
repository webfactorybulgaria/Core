<?php

namespace TypiCMS\Modules\Core\Services\TableList;

use Illuminate\Support\Facades\Request;

class SmartTableList
{
    protected $perPage;
    protected $builder;

    public function getPerPage()
    {
        return $this->perPage;
    }

    public function getBuilder()
    {
        return $this->builder;
    }


    public function initPaging()
    {
        $request = Request::all();
        $tableState = json_decode($request["tableState"]);
        if ($tableState->pagination) {
            $perPage = !empty($tableState->pagination->number) ? $tableState->pagination->number : 20;
            Request::replace(['page' => round($tableState->pagination->start / $perPage + 1)]);

            $this->perPage = $perPage;            
        }
    }

    protected function dateFilter($field, $value)
    {
        if (!empty($value->filter_from)) {
            $this->builder->where($field, '>=', $value->filter_from);
        }
        if (!empty($value->filter_to)) {
            $this->builder->where($field, '<=', $value->filter_to);
        }
    }

    protected function booleanFilter($field, $value)
    {
        $this->builder->where($field, '=', $value == 'true' ? true : false);
    }

    protected function intFilter($field, $value)
    {
        $this->builder->where($field, '=', $value);
    }

    public function applyTableStateSearch($tableState)
    {
        // Filtering
        if (!empty($tableState->search) && !empty($tableState->search->predicateObject)) {
            $arr = (array)$tableState->search->predicateObject;
            foreach ($arr as $field => $search) {
                if (is_object($search)) {
                    $search = (array)$search;
                    foreach($search as $key => $value) {
                        $method = $key . 'Filter';
                        if (method_exists($this, $method)) {
                            $this->$method($field, $value);
                        }
                    }
                } else if ($field == '$') {
                    // global search in all translatable fields
                    if(!empty($fields = $this->builder->getModel()->translatedAttributes)) {
                        $query = $this->builder->getModel();
                        $this->builder->where(function($query) use ($fields, $search) {
                            foreach ($fields as $field) {
                                $query->orwhere($field, 'LIKE', '%' . $search . '%');
                            }
                        });
                    }
                } else {
                    $this->builder->where($field, 'LIKE', '%' . $search . '%');
                }
            }
        }

    }

    public function applyTableStateSort($tableState)
    {
        // Sorting
        if (!empty($tableState->sort) && !empty($tableState->sort->predicate)) {
            $this->builder->orderBy($tableState->sort->predicate, $tableState->sort->reverse ? 'desc' : 'asc');
        }
    }

    public function apply($builder)
    {
        $request = Request::all();
        $tableState = json_decode($request["tableState"]);
        $this->builder = $builder;
        $this->initPaging();
        
        $this->applyTableStateSearch($tableState);
        $this->applyTableStateSort($tableState);


        return $this;
    }

}