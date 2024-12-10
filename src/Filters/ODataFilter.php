<?php

namespace Tots\Odata\Filters;

class ODataFilter extends ODataType
{
    public const ODATA_TYPE_COMPARE = 0;
    public const ODATA_TYPE_FUNCTION = 1;
    public const ODATA_TYPE_LIST = 2;

    protected int $type = ODataType::FILTER;

    protected string $logicalOperator;
    protected string $key;
    protected string $operator;
    protected mixed $value;
    protected string $odataOperator;
    protected int $odataType;

    public function __construct(string $logicalOperator, string $key, string $operator, mixed $value)
    {
        $this->logicalOperator = $logicalOperator;
        $this->key = $key;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }

    public function getOdataOperator(): string
    {
        return $this->odataOperator;
    }

    public function setOdataOperator(string $odataOperator): void
    {
        $this->odataOperator = $odataOperator;
    }

    public function getOdataType(): int
    {
        return $this->odataType;
    }

    public function setOdataType(int $odataType): void
    {
        $this->odataType = $odataType;
    }

    public function toString(): string
    {
        if ($this->odataType == self::ODATA_TYPE_FUNCTION) {
            return $this->odataOperator . '(' . $this->key . ', \'' . $this->value . '\')';
        }

        if ($this->odataType == self::ODATA_TYPE_LIST) {
            return $this->key . ' ' . $this->odataOperator . ' (' . implode(',', $this->value) . ')';
        }

        return $this->key . ' ' . $this->odataOperator . ' \'' . $this->value . '\'';
    }
}