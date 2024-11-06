<?php

namespace Tots\Odata;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\ForwardsCalls;
use Tots\Odata\Concerns\AllowedFilter;
use Tots\Odata\Concerns\AllowedSort;

class ODataBuilder {

    use AllowedFilter;
    use AllowedSort;
    use ForwardsCalls;

    protected Request $request;
    protected EloquentBuilder $query;

    public function __construct(protected EloquentBuilder|Relation $subject, ?Request $request = null)
    {
        $this->query = $subject;
        $this->request = $request;
    }

    public function run()
    {
        $this->applySorts($this->query, $this->request);
        $this->applyFilters($this->query, $this->request);

        return $this->query->paginate($this->getTop(), ['*'], 'page', $this->getCurrentPage());
    }

    public function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->query, $name, $arguments);

        /*
         * If the forwarded method call is part of a chain we can return $this
         * instead of the actual $result to keep the chain going.
         */
        if ($result === $this->subject) {
            return $this;
        }

        return $result;
    }

    public function getTop(): int
    {
        return $this->request->query('$top', 10);
    }

    public function getSkip(): int
    {
        return $this->request->query('$skip', 0);
    }

    public function getCurrentPage(): int
    {
        return ($this->getSkip() / $this->getTop()) + 1;
    }

    public static function for(EloquentBuilder|Relation|string $subject, ?Request $request = null): static
    {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        return new static($subject, $request);
    }
}