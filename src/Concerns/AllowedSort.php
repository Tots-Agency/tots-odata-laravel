<?php

namespace Tots\Odata\Concerns;

use Illuminate\Support\Collection;

trait AllowedSort
{
    protected Collection $allowedSorts;

    public function allowedSorts($sorts): static
    {
        $sorts = is_array($sorts) ? $sorts : func_get_args();

        $this->allowedSorts = collect($sorts);

        return $this;
    }
}