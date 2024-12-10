<?php

namespace Tots\Odata\Filters;

abstract class ODataType
{
    public const FILTER = 0;
    public const GROUP = 1;

    protected int $type;

    public function getType(): int
    {
        return $this->type;
    }
}