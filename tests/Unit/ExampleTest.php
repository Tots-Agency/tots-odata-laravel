<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_can_parser(): void
    {
        $testFilters = '$filter=contains(name, \'John\') and startswith(name, \'Doe\')';
        $testFilterTwo = '$filter=name eq \'John\' and age gt 20';

        $parser = new \Tots\Odata\ODataParser();
        $filtersOne = $parser->parseFilters($testFilters);
        $filtersTwo = $parser->parseFilters($testFilterTwo);

        $this->assertIsArray($filtersOne);
        $this->assertIsArray($filtersTwo);
    }
}
