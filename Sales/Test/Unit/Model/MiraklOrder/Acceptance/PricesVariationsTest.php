<?php
namespace MiraklSeller\Sales\Test\Unit\Model\MiraklOrder\Acceptance;

use MiraklSeller\Sales\Model\MiraklOrder\Acceptance\PricesVariations;
use PHPUnit\Framework\TestCase;

/**
 * @group sales
 * @group model
 * @coversDefaultClass \MiraklSeller\Sales\Model\MiraklOrder\Acceptance\PricesVariations
 */
class PricesVariationsTest extends TestCase
{
    /**
     * @covers ::isPriceVariationValid
     * @param   int|null    $config
     * @param   float       $magentoPrice
     * @param   float       $miraklPrice
     * @param   bool        $expected
     * @dataProvider getIsPriceVariationValidDataProvider
     */
    public function testIsPriceVariationValid($config, $magentoPrice, $miraklPrice, $expected)
    {
        /** @var PricesVariations|\PHPUnit_Framework_MockObject_MockObject $modelMock */
        $modelMock = $this->getMockBuilder(PricesVariations::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['isPriceVariationValid'])
            ->getMock();
        $modelMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->assertSame($expected, $modelMock->isPriceVariationValid($magentoPrice, $miraklPrice));
    }

    /**
     * @return  array
     */
    public function getIsPriceVariationValidDataProvider()
    {
        return [
            [null, 2.90, 1.90, true],
            [0, 2.90, 2.89, false],
            [0, 2.90, 2.90, true],
            [10, 2.90, 2.60, false],
            [10, 2.90, 2.61, true],
            [20, 2.90, 9.90, true],
            [100, 3, 6, true],
        ];
    }
}