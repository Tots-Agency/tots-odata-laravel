<?php

namespace Tots\Odata;

class ODataParser
{
    /*protected $objectOperators = [
        'and', 'or', 'between', 'notBetween', 'in', 'notIn', 'any', 'overlap', 'contains', 'contained'
    ];

    protected $valueOperators = [
        'gt', 'gte', 'lt', 'lte', 'ne', 'eq', 'not', 'like', 'notLike', 'iLike', 'notILike', 'regexp', 'notRegexp', 'iRegexp', 'notIRegexp', 'col'
    ];

    protected $customOperators = [
        'ge', 'le', 'substringof', 'startswith', 'endswith', 'tolower', 'toupper', 'trim', 'year', 'month', 'day', 'hour', 'minute', 'second'
    ];*/

    protected $compareOperators = [
        'eq', 'ne', 'gt', 'ge', 'lt', 'le'
    ];

    protected $functionOperators = [
        'contains', 'startswith', 'endswith', 'substringof'
    ];

    public function parseFilters(string $pathUrl, bool $withDollar = true): array
    {
        // $filter is Odata protocol for filtering
        $filterKey = $withDollar ? '$filter' : 'filter';
        // Verify if starts with $filter
        if(strpos($pathUrl, $filterKey) != 0 || $pathUrl == ''){
            return [];
        }
        // Remove $filter from path
        $filterString = str_replace($filterKey . '=', '', $pathUrl);
        // Separate filters by and
        $filters = explode(' and ', $filterString);

        return collect($filters)->map(function ($filter) {
            return $this->parseAnd($filter);
        })->toArray();
    }

    public function parseAnd(string $filter): array
    {
        // Verificar si alguno de los custom operators esta en el filtro
        $customOperator = collect($this->functionOperators)->filter(function ($operator) use ($filter) {
            return strpos($filter, $operator) !== false;
        })->first();

        if ($customOperator) {
            return $this->parseFunctionOperator($filter);
        }

        return $this->parseCompareOperator($filter);
    }

    public function parseCompareOperator(string $filter): array
    {
        // Separate filter by space
        $data = explode(' ', $filter);

        // Detect if value is in quotes
        if (strpos($data[2], "'") === 0) {
            $data[2] = substr($data[2], 1, -1);
        }

        return [
            'key' => $data[0],
            'operator' => $this->getOperatorSQL($data[1]),
            'value' => $data[2]
        ];
    }

    public function parseFunctionOperator(string $filter): array
    {
        // Separate filter by (
        $data = explode('(', $filter);
        // Get the key
        $operator = $data[0];
        // Separate remaining data by )
        $dataTwo = explode(' ', $data[1]);

        return [
            'key' => substr($dataTwo[0], 0, -1),
            'operator' => $this->getOperatorSQL($operator),
            'value' => substr($dataTwo[1], 1, -2)
        ];
    }

    public function getOperatorSQL(string $operator): string
    {
        switch ($operator) {
            case 'eq':
                return '=';
            case 'ne':
                return '!=';
            case 'gt':
                return '>';
            case 'ge':
                return '>=';
            case 'lt':
                return '<';
            case 'le':
                return '<=';
            case 'contains':
                return 'LIKE';
            case 'startswith':
                return 'LIKE';
            case 'endswith':
                return 'LIKE';
            case 'substringof':
                return 'LIKE';
            default:
                return '=';
        }
    }

    public static function onlyFilters(string $pathFilters, bool $withDollar = true): array
    {
        return (new static)->parseFilters($pathFilters, $withDollar);
    }
}