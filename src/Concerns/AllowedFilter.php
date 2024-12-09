<?php

namespace Tots\Odata\Concerns;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
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
            $key = $filter['key'];
            $operator = $filter['operator'];
            $value = $filter['value'];

            if (!$this->allowedFilters->contains($key)) {
                return;
            }

            if($this->customFilters != null && $this->customFilters->has($key) && (is_array($value) && count($value) > 0)) {
                $callback = $this->customFilters->get($key);
                $callback($query, $operator, $value);
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
        });
    }
}
