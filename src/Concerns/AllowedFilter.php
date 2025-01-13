<?php

namespace Tots\Odata\Concerns;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Tots\Odata\Filters\ODataFilter;
use Tots\Odata\Filters\ODataGroup;
use Tots\Odata\Filters\ODataType;
use Tots\Odata\ODataParser;

trait AllowedFilter
{
    protected Collection $allowedFilters;
    protected ?Collection $customFilters = null;

    public function allowedFilters($filters): static
    {
        $filters = is_array($filters) ? $filters : func_get_args();

        $this->allowedFilters = collect($filters);

        return $this;
    }

    public function addCustomFilter($relationKey, Closure $callback): static
    {
        if($this->customFilters == null) {
            $this->customFilters = collect();
        }
        $this->customFilters->put($relationKey, $callback);

        return $this;
    }

    public function applyFilters(EloquentBuilder $query, ?Request $request = null)
    {
        // $filter is Odata protocol for filtering
        $filters = $request->query('$filter');

        if (!$filters) {
            return;
        }

        // Init parser
        $filters = ODataParser::onlyFilters($filters);

        collect($filters)->each(function ($filter) use ($query) {
            $this->applyOdataFilter($filter, $query);
        });
    }

    public function applyOdataFilter(ODataType $filter, EloquentBuilder $query)
    {
        if($filter instanceof ODataFilter){
            $this->applyOdataOneFilter($filter, $query);
            return;
        }

        if($filter instanceof ODataGroup){
            if($filter->getLogicalOperator() == 'or'){
                $query->orWhere(function($subquery) use ($filter) {
                    collect($filter->getFilters())->each(function ($filter) use ($subquery) {
                        $this->applyOdataFilter($filter, $subquery);
                    });
                });
            } else {
                $query->where(function($subquery) use ($filter) {
                    collect($filter->getFilters())->each(function ($filter) use ($subquery) {
                        $this->applyOdataFilter($filter, $subquery);
                    });
                });
            }
        }
    }

    public function applyOdataOneFilter(ODataFilter $filter, EloquentBuilder $query)
    {
        $key = $filter->getKey();

        if (!$this->allowedFilters->contains($key)) {
            return;
        }

        $value = $filter->getValue();
        $operator = $filter->getOperator();

        if(
            $this->customFilters != null &&
            $this->customFilters->has($key) &&
            ((is_array($value) && count($value) > 0) || !is_array($value))
        ) {
            $callback = $this->customFilters->get($key);
            $callback($query, $operator, $value, $filter->getLogicalOperator());
            return;
        }

        $this->applyFilter($key, $query, $filter->getLogicalOperator(), $operator, $value);
    }

    public function applyFilter(string $key, EloquentBuilder $query, $logicalOperator, $operator, $value)
    {
        if($logicalOperator == 'or'){
            if($operator == 'IN'){
                $query->orWhereIn($key, $value);
                return;
            }else if ($operator == 'NOTIN'){
                $query->orWhereNotIn($key, $value);
                return;
            }

            $query->orWhere($key, $operator, $value);
            return;
        }

        if($operator == 'IN'){
            $query->whereIn($key, $value);
            return;
        }else if ($operator == 'NOTIN'){
            $query->whereNotIn($key, $value);
            return;
        }

        $query->where($key, $operator, $value);
    }
}
