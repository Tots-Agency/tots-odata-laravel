<?php

namespace Tots\Odata;

use Tots\Odata\Filters\ODataFilter;
use Tots\Odata\Filters\ODataGroup;
use Tots\Odata\Filters\ODataType;

class ODataParser
{
    protected $compareOperators = ['eq', 'ne', 'gt', 'ge', 'lt', 'le'];
    protected $functionOperators = ['contains', 'startswith', 'endswith', 'substringof'];
    protected $listOperators = ['in', 'notin'];

    public function parseFilters(string $pathUrl, bool $withDollar = true): array
    {
        $filterKey = $withDollar ? '$filter' : 'filter';
        if (strpos($pathUrl, $filterKey) !== 0 || $pathUrl == '') {
            return [];
        }
        $filterString = str_replace($filterKey . '=', '', $pathUrl);
        $tokens = $this->tokenize($filterString);
        return $this->parseTokens($tokens);
    }

    protected function tokenize(string $expression): array
    {
        $pattern = '/(contains|startswith|endswith|substringof)\((.*?)\)/';
        $replacement = '$1$$$2$$';
        $modifiedExpression = preg_replace($pattern, $replacement, $expression);

        $pattern = '/(\(|\)| and | or )/';
        $tokens = preg_split($pattern, $modifiedExpression, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $tokens = array_map(function($token) {
            return str_replace(['contains$$', 'startswith$$', 'endswith$$', 'substringof$$', '$$'], ["contains(", 'startswith(', 'endswith(', 'substringof(', ')'], $token);
        }, $tokens);
        return array_map('trim', $tokens);
    }

    protected function parseTokens(array $tokens): array
    {
        $filters = [];
        $beforeFilter = null;
        $beforeLogicalOperator = null;
        $lastGroup = null;
        
        foreach ($tokens as $token) {
            $newFilter = $this->parseToken($token, $beforeFilter, $beforeLogicalOperator, $lastGroup);
            if($newFilter != null){
                $filters[] = $newFilter;
            }
        }

        return $filters;
    }

    protected function parseToken(string $token, &$beforeFilter, &$beforeLogicalOperator, &$lastGroup): ?ODataType
    {
        if($beforeFilter == null && $this->isLogicalOperator($token)) {
            throw new \Exception('Invalid filter format');
        } else if ($this->isLogicalOperator($token)) {
            $beforeLogicalOperator = $token;
        } else if ($token == '(') {
            $lastGroup = new ODataGroup($beforeLogicalOperator);
            $beforeLogicalOperator = null;
            return $lastGroup;
        } else if ($token == ')') {
            $beforeFilter = $lastGroup;
            $lastGroup = null;
        } else {
            $newFilter = $this->parseFilter($token, $beforeLogicalOperator ?? 'and');
            if($lastGroup != null){
                $lastGroup->addFilter($newFilter);
            } else {
                $beforeFilter = $newFilter;
                return $newFilter;
            }
        }

        return null;
    }

    protected function parseFilter(string $filter, string $logicalOperator): ODataFilter
    {
        // Verificar si alguno de los custom operators esta en el filtro
        $functionOperator = collect($this->functionOperators)->filter(function ($operator) use ($filter) {
            return strpos($filter, $operator) !== false;
        })->first();

        if ($functionOperator) {
            return $this->createFunctionFilter($filter, $logicalOperator);
        }

        // Verify if listComparator
        $listOperator = collect($this->listOperators)->filter(function ($operator) use ($filter) {
            return strpos($filter, $operator) !== false;
        })->first();

        if ($listOperator) {
            return $this->createListFilter($filter, $logicalOperator);
        }

        return $this->createCompareFilter($filter, $logicalOperator);
    }

    protected function createFunctionFilter(string $filter, string $logicalOperator): ODataFilter
    {
        $data = explode('(', $filter);
        $operator = $data[0];
        $dataTwo = explode(',', trim($data[1], ')'));

        $filter = new ODataFilter($logicalOperator, $dataTwo[0], $this->getOperatorSQL($operator), str_replace('\'', '', trim($dataTwo[1])), $operator);
        $filter->setOdataOperator($operator);
        $filter->setOdataType(ODataFilter::ODATA_TYPE_FUNCTION);

        return $filter;
    }

    protected function createListFilter(string $filter, string $logicalOperator): ODataFilter
    {
        $data = explode(' ', $filter, 3);

        $values = explode(',', str_replace(['(', ')'], ['', ''], $data[2]));

        $filter = new ODataFilter($logicalOperator, $data[0], $this->getOperatorSQL($data[1]), array_map(fn($value) => trim($value), $values));
        $filter->setOdataOperator($data[1]);
        $filter->setOdataType(ODataFilter::ODATA_TYPE_LIST);

        return $filter;
    }

    protected function createCompareFilter(string $filter, string $logicalOperator): ODataFilter
    {
        $data = explode(' ', $filter);
        $operator = $data[1];

        $value = str_replace($data[0] .' ' . $operator . ' ', '', $filter);

        if (strpos($value, "'") === 0) {
            $value = substr($value, 1, -1);
        }

        $filter = new ODataFilter($logicalOperator, $data[0], $this->getOperatorSQL($operator), $value);
        $filter->setOdataOperator($operator);
        $filter->setOdataType(ODataFilter::ODATA_TYPE_COMPARE);

        return $filter;
    }

    protected function isLogicalOperator(string $token): bool
    {
        return in_array($token, ['and', 'or']);
    }

    public function getOperatorSQL(string|null $operator): string
    {
        $mapping = [
            'eq' => '=', 'ne' => '!=', 'gt' => '>', 'ge' => '>=',
            'lt' => '<', 'le' => '<=', 'contains' => 'LIKE',
            'startswith' => 'LIKE', 'endswith' => 'LIKE', 'substringof' => 'LIKE',
            'in' => 'IN', 'notin' => 'NOT IN'
        ];

        return $mapping[$operator] ?? '=';
    }

    public static function onlyFilters(string $pathFilters, bool $withDollar = true): array
    {
        return (new static)->parseFilters($pathFilters, $withDollar);
    }
}