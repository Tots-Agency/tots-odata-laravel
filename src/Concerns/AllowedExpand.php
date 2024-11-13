<?php

namespace Tots\Odata\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;

trait AllowedExpand
{
    protected Collection $allowedExpands;

    public function allowedExpands($expands): static
    {
        $expands = is_array($expands) ? $expands : func_get_args();

        $this->allowedExpands = collect($expands);

        return $this;
    }

    public function applyExpands(EloquentBuilder $query, ?Request $request = null)
    {
        $expands = $request->query('$expand');

        if (!$expands) {
            return;
        }

        collect(explode(',', $expands))->each(function ($with) use ($query) {
            if (!$this->allowedExpands->contains($with)) {
                return;
            }

            $query->with($with);
        });
    }
}