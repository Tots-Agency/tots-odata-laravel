<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tots\Odata\Parsers\ODataTypeParser;

class ExampleTest extends TestCase
{
    public function test_can_simple()
    {
        $filter = 'name eq \'John\'';

        $parser = new \Tots\Odata\ODataParser();
        $result = $parser->parseFilters('$filter=' . $filter);

        $this->assertEquals($filter, ODataTypeParser::toString($result));
    }

    public function test_can_multiple_and()
    {
        $filter = 'name eq \'John\' and age gt \'30\' and city eq \'New York\'';

        $parser = new \Tots\Odata\ODataParser();
        $result = $parser->parseFilters('$filter=' . $filter);

        $this->assertEquals($filter, ODataTypeParser::toString($result));
    }

    public function test_can_multiple_or()
    {
        $filter = 'name eq \'John\' or (age gt \'30\' and city eq \'New York\')';

        $parser = new \Tots\Odata\ODataParser();
        $result = $parser->parseFilters('$filter=' . $filter);

        $this->assertEquals($filter, ODataTypeParser::toString($result));
    }

    public function test_can_multiple_complex()
    {
        $filter = 'name eq \'John\' or (age gt \'30\' and city eq \'New York\') and (edad gt \'30\' or country eq \'USA\')';

        $parser = new \Tots\Odata\ODataParser();
        $result = $parser->parseFilters('$filter=' . $filter);

        $this->assertEquals($filter, ODataTypeParser::toString($result));
    }

    public function test_can_function()
    {
        $filter = 'name eq \'John\' and contains(city, \'New York\') or (age gt \'30\' and startswith(name, \'John\'))';
        $filterExpected = 'name eq \'John\' and contains(city, \'%New York%\') or (age gt \'30\' and startswith(name, \'John%\'))';

        $parser = new \Tots\Odata\ODataParser();
        $result = $parser->parseFilters('$filter=' . $filter);;

        $this->assertEquals($filterExpected, ODataTypeParser::toString($result));
    }

    /*public function test_can_invalid()
    {
        $parser = new \Tots\Odata\ODataParser();
        $result = $parser->parseFilters('$filter=name eq \'John\' or (age gt 30 and city');

        $this->assertEmpty($result, 'The parser should return an empty array for invalid expressions.');
    }*/
}
