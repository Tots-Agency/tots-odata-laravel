<?php

namespace Tots\Odata\Concerns;

use Illuminate\Support\Collection;

trait AllowedFilter
{
    protected Collection $allowedFilters;

    public function allowedFilters($filters): static
    {
        $filters = is_array($filters) ? $filters : func_get_args();

        $this->allowedFilters = collect($filters);

        return $this;
    }
}