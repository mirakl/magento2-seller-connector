<?php
namespace MiraklSeller\Core\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group helper
 * @group config
 * @coversDefaultClass \MiraklSeller\Core\Helper\Config
 */
class ConfigTest extends TestCase
{
    /**
     * @covers ::getAttributesChunkSize
     * @param   int $configValueMock
     * @param   int $expected
     * @dataProvider getAttributesChunkSizeDataProvider
     */
    public function testGetAttributesChunkSize($configValueMock, $expected)
    {
        /** @var \MiraklSeller\Core\Helper\Config|\PHPUnit\Framework\MockObject\MockObject $configMock */
        $configMock = $this->getMockBuilder(\MiraklSeller\Core\Helper\Config::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getAttributesChunkSize'])
            ->getMock();

        $configMock->expects($this->once())
            ->method('getValue')
            ->willReturn($configValueMock);

        $this->assertSame($expected, $configMock->getAttributesChunkSize());
    }

    /**
     * @return  array
     */
    public function getAttributesChunkSizeDataProvider()
    {
        return [
            [-1, 5],
            [0, 5],
            [10, 10],
            [15, 15],
            [18, 15],
            [150, 15],
        ];
    }
}
