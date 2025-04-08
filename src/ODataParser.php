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
        $filterString = str_replace($filterKey . '=', '', $pathUrl);
        $tokens = $this->tokenize($filterString);
        return $this->parseTokens($tokens);
    }

    protected function tokenize(string $expression): array
    {
        // Examples
        // game_status_id eq 'completed'
        // game_status_id eq 'completed' and team_name eq 'Team 1'
        // event_name eq 'Tots Event (Demo)' and (team_name eq 'team 1' or team_name eq 'Team 2')
        // event_name eq 'Tots Event (Demo)' and (team_name eq 'team 1' or team_name eq 'Team 2') and (game_status_id eq 'cancelled' or game_status_id eq 'completed')
        $pattern = "/\\(|\\)|\\s+and\\s+|\\s+or\\s+|('[^']*')|([a-zA-Z_]+\\s+(eq|ne|gt|lt|ge|le)\\s+'[^']*')|(contains|startswith|endswith|substringof)\\([a-zA-Z0-9_\\.]+,\\s*'[^']*'\\)|([a-zA-Z_]+\\s+in\\s*\\([0-9,\\s]+\\))/";

        // Extraer los tokens sin dividir los valores entre comillas
        preg_match_all($pattern, $expression, $matches);

        // Limpiar espacios extra y devolver el array de tokens
        return array_map('trim', $matches[0]);
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
            //throw new \Exception('Invalid filter format');
        } else if ($this->isLogicalOperator($token)) {
            $beforeLogicalOperator = $token;
        } else if ($token == '(') {
            $lastGroup = new ODataGroup($beforeLogicalOperator ?? 'and');
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

        $filter = new ODataFilter(
            $logicalOperator,
            $dataTwo[0],
            $this->getOperatorSQL($operator),
            $this->getValueSQL($operator, str_replace('\'', '', trim($dataTwo[1])))
        );
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
        $data = $this->splitIgnoringQuotes($filter);
        $operator = $data[1];

        $value = str_replace($data[0] .' ' . $operator . ' ', '', $filter);

        if (strpos($value, "'") === 0) {
            $value = substr($value, 1, -1);
        }

        $filter = new ODataFilter($logicalOperator, $data[0], $this->getOperatorSQL($operator), $this->getValueSQL($operator, $value));
        $filter->setOdataOperator($operator);
        $filter->setOdataType(ODataFilter::ODATA_TYPE_COMPARE);

        return $filter;
    }

    protected function isLogicalOperator(string $token): bool
    {
        return in_array($token, ['and', 'or']);
    }

    public function getValueSQL(string $operator, $value)
    {
        switch ($operator) {
            case 'contains':
                return '%' . $value . '%';
            case 'startswith':
                return $value . '%';
            case 'endswith':
                return '%' . $value;
            case 'substringof':
                return '%' . $value . '%';
            default:
                return $value;
        }
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

    protected function splitIgnoringQuotes($input) {
        $pattern = "/'[^']*'|\S+/";  // Captura texto entre comillas o palabras separadas por espacios
        preg_match_all($pattern, $input, $matches);
        return $matches[0];
    }

    public static function onlyFilters(string $pathFilters, bool $withDollar = true): array
    {
        return (new static)->parseFilters($pathFilters, $withDollar);
    }
}
