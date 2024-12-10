<?php

namespace Tots\Odata\Filters;

class ODataGroup extends ODataType
{
    protected int $type = ODataType::GROUP;

    protected string $logicalOperator;
    protected array $filters = [];

    public function __construct(string $logicalOperator)
    {
        $this->logicalOperator = $logicalOperator;
    }

    public function addFilter(ODataFilter $filter)
    {
        $this->filters[] = $filter;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }
}