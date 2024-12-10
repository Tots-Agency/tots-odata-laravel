<?php

namespace Tots\Odata\Parsers;

use Tots\Odata\Filters\ODataType;

class ODataTypeParser
{
    public static function toString(array $filters): string
    {
        $result = '';

        foreach($filters as $filter) {
            if($filter->getType() == ODataType::FILTER) {
                $result .= ($result == '' ? '' : ' '.$filter->getLogicalOperator(). ' ') . $filter->toString();
            } else {
                $result .= ' '.$filter->getLogicalOperator() . ' (' . self::toString($filter->getFilters()) . ')';
            }
        }

        return $result;
    }
}