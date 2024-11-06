<?php

namespace Tots\Odata\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;

trait AllowedSort
{
    protected Collection $allowedSorts;

    public function allowedSorts($sorts): static
    {
        $sorts = is_array($sorts) ? $sorts : func_get_args();

        $this->allowedSorts = collect($sorts);

        return $this;
    }

    public function applySorts(EloquentBuilder $query, ?Request $request = null)
    {
        $sorts = $request->query('$orderby');

        if (!$sorts) {
            return;
        }

        collect(explode(',', $sorts))->each(function ($sort) use ($query) {
            $data = explode(' ', $sort);
            $key = $data[0];
            $direction = $data[1] ?? 'asc';

            if (!$this->allowedSorts->contains($key)) {
                return;
            }

            $query->orderBy($sort, $direction);
        });
    }
}