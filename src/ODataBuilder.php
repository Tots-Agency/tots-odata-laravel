<?php

namespace Tots\Odata;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\ForwardsCalls;
use Tots\Odata\Concerns\AllowedExpand;
use Tots\Odata\Concerns\AllowedFilter;
use Tots\Odata\Concerns\AllowedSort;
use Tots\Odata\Parsers\ModelParser;

class ODataBuilder
{
    use AllowedExpand;
    use AllowedFilter;
    use AllowedSort;
    use ModelParser;
    use ForwardsCalls;

    protected Request $request;
    protected EloquentBuilder $query;

    protected string $table;

    public function __construct(protected EloquentBuilder|Relation $subject, ?Request $request = null, ?string $table = null)
    {
        $this->query = $subject;
        $this->request = $request;
        $this->table = $table;
    }

    public function run()
    {
        $this->applyExpands($this->query, $this->request);
        $this->applySorts($this->query, $this->request);
        $this->applyFilters($this->query, $this->request);

        $result = $this->query->paginate($this->getTop(), ['*'], 'page', $this->getCurrentPage());

        $this->applyExpandPerformanceResources($this->request, $result);

        return $result;
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
        if ($this->request->query('$top') == 0) {
            // This is the special case for the top=0 where we need to return the all the records.
            return DB::table($this->table)->count();
        }
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
            $table = (new $subject)->getTable();
            $subject = $subject::query();
        }

        return new static($subject, $request, $table ?? null);
    }
}
