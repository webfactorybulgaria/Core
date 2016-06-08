<?php 

namespace TypiCMS\Modules\Core\Scopes;

use App;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;

class TranslatableScope implements Scope
{
    protected $table;

    protected $i18nTable;

    protected $locale;

    protected $fallback;

    protected $joinType = 'leftJoin';

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Eloquent $model)
    {
        $this->table = $model->getTable();
        $this->locale = config('app.locale');
        
        $translationModelName = $model->getTranslationModelName();
        $translationModel = new $translationModelName;
        $this->i18nTable = $translationModel->getTable();

        $this->createJoin($builder, $model);
        $this->createSelect($builder, $model);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    protected function createJoin(Builder $builder, Eloquent $model)
    {
        $joinType = $this->getJoinType($model);

        $clause = $this->getJoinClause($model, $this->locale, $this->i18nTable);
        $builder->$joinType($this->i18nTable, $clause);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return string
     */
    protected function getJoinType(Eloquent $model)
    {
        return $this->joinType;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $locale
     * @param string $alias
     * @return callable
     */
    protected function getJoinClause(Eloquent $model, $locale, $alias)
    {
        return function (JoinClause $join) use ($model, $locale, $alias) {
            $primary = $model->getTable() . '.' . $model->getKeyName();
            $foreign = $model->getForeignKey();
            $langKey = $model->getLocaleKey();

            $join->on($alias . '.' . $foreign, '=', $primary)
                ->where($alias . '.' . $langKey, '=', $locale);
        };
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    protected function createSelect(Builder $builder, Eloquent $model)
    {
        if($builder->getQuery()->columns) {
            return;
        }

        $select = $this->formatColumns($builder, $model);
        $builder->select(array_merge([$this->table . '.*'], $select));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function formatColumns(Builder $builder, Eloquent $model)
    {
        $map = function ($field) use ($builder, $model) {
            $primary = "{$this->i18nTable}.{$field}";
            $fallback = "{$this->i18nTable}_fallback.{$field}";
            $alias = $field;

            // return new Expression($builder->getQuery()->compileIfNull($primary, $fallback, $alias));
            return new Expression($builder->getQuery()->getGrammar()->wrap($primary));
        };

        return array_map($map, $model->translatedAttributes);
    }


    /**
     * @param Grammar $grammar
     * @return string
     */
    protected function getIfNull(Grammar $grammar)
    {
        return $grammar instanceof SqlServerGrammar ? 'isnull' : 'ifnull';
    }

}