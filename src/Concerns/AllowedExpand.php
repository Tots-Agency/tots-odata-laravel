<?php

namespace Tots\Odata\Concerns;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

trait AllowedExpand
{
    protected Collection $allowedExpands;

    protected ?Collection $expandPerformances = null;
    protected ?Collection $expandPerformanceModels = null;

    protected bool $isSelectRawInit = false;

    public function allowedExpands($expands): static
    {
        $expands = is_array($expands) ? $expands : func_get_args();

        $this->allowedExpands = collect($expands);

        return $this;
    }

    public function applyExpands(EloquentBuilder $query, ?Request $request = null)
    {
        if($this->expandPerformances != null && $this->expandPerformances->isNotEmpty()) {
            $this->applyExpandPerformance($query, $request);
            return;
        }

        $this->getExpandsByRequest($request)->each(function ($with) use ($query) {
            if (!$this->allowedExpands->contains($with)) {
                return;
            }

            $query->with($with);
        });
    }

    public function applyExpandPerformance(EloquentBuilder $query, ?Request $request = null)
    {
        $this->getExpandsByRequest($request)->each(function ($with) use ($query) {
            if (!$this->allowedExpands->contains($with)) {
                return;
            }

            if($this->isExpandPerformance($with)) {
                $this->initSelectRaw($query);
                $this->addSelectRawByModel($this->expandPerformanceModels->get($with));

                $callback = $this->expandPerformances->get($with);
                $callback($this);
                return;
            }

            $query->with($with);
        });
    }

    public function applyExpandPerformanceResources(?Request $request, \Illuminate\Contracts\Pagination\LengthAwarePaginator $result)
    {
        $this->getExpandsByRequest($request)->each(function ($with) use ($result) {
            if (!$this->allowedExpands->contains($with)) {
                return;
            }

            if(!$this->isExpandPerformance($with)) {
                return;
            }

            $this->convertToRelationModel($result, $with, $this->expandPerformanceModels->get($with));
        });
    }

    public function addExpandPerformance($relationKey, Model|string $subject, Closure $callback): static
    {
        if($this->expandPerformances == null) {
            $this->expandPerformances = collect();
            $this->expandPerformanceModels = collect();
        }
        $this->expandPerformances->put($relationKey, $callback);
        $this->expandPerformanceModels->put($relationKey, $subject);

        return $this;
    }

    public function isExpandPerformance($relationKey): bool
    {
        return $this->expandPerformances->has($relationKey);
    }

    protected function getExpandsByRequest(?Request $request = null): Collection
    {
        $expands = $request->query('$expand');

        if (!$expands) {
            return collect();
        }

        return collect(explode(',', $expands));
    }

    protected function initSelectRaw(EloquentBuilder $query)
    {
        if($this->isSelectRawInit) {
            return;
        }

        $query->selectRaw($this->table . '.*');

        $this->isSelectRawInit = true;
    }
}
