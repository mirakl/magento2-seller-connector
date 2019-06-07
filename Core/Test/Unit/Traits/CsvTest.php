<?php
namespace MiraklSeller\Core\Test\Unit\Traits;

use MiraklSeller\Core\Helper\CsvTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group trait
 */
class CsvTest extends TestCase
{
    /**
     * @param   string  $str
     * @param   int     $expectedColsCount
     * @dataProvider getTestCreateCsvFileFromStringDataProvider
     */
    public function testCreateCsvFileFromString($str, $expectedColsCount)
    {
        /** @var CsvTrait $mock */
        $mock = $this->getObjectForTrait(CsvTrait::class);
        $file = $mock->createCsvFileFromString($str);
        $cols = $file->fgetcsv();

        $this->assertCount($expectedColsCount, $cols);
    }

    /**
     * @return  array
     */
    public function getTestCreateCsvFileFromStringDataProvider()
    {
        return [
            [
                "foo;bar;baz\nlorem;ipsum;dolor\n1;2;3", 3 // Must fallback to ; delimiter automatically
            ],
            [
                "blue,white,red,black\norange,pink,yellow,purple", 4 // Delimiter , is valid so no fallback needed
            ],
            [
                "one#two#three#four\n1#2#3#4\n5#6#7#8", 1 // Delimiter , is not valid and # delimiter is not handled
            ],
        ];
    }
}