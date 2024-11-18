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
            if (is_array($filter)) {
                $key = $filter['key'];
                $operator = $filter['operator'];
                $value = $filter['value'];
            } else {
                $data = explode(' ', $filter);
                $key = $data[0];
                $operator = $data[1];
                $value = $data[2];
            }

            if (!$this->allowedFilters->contains($key)) {
                return;
            }

            $query->where($key, $operator, $value);
        });
    }
}
