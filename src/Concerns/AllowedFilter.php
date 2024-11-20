<?php

namespace Tots\Odata\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Tots\Odata\ODataParser;

trait AllowedFilter
{
    protected Collection $allowedFilters;

    public function allowedFilters($filters): static
    {
        $filters = is_array($filters) ? $filters : func_get_args();

        $this->allowedFilters = collect($filters);

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
