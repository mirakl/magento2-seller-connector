<?php
namespace MiraklSeller\Sales\Test\Unit\Helper;

use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group sales
 * @group helper
 * @group config
 * @coversDefaultClass \MiraklSeller\Sales\Helper\Config
 */
class ConfigTest extends TestCase
{
    /**
     * @covers ::getCountryLabelsMapping
     * @param   int $configValueMock
     * @param   int $expected
     * @dataProvider getCountryLabelsMappingDataProvider
     */
    public function testGetCountryLabelsMapping($configValueMock, $expected)
    {
        $contextMock = $this->createMock(Context::class);
        $configurationMock = $this->createMock(MagentoConfig::class);
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);

        /** @var \MiraklSeller\Sales\Helper\Config|\PHPUnit\Framework\MockObject\MockObject $configMock */
        $configMock = $this->getMockBuilder(\MiraklSeller\Sales\Helper\Config::class)
            ->setConstructorArgs([$contextMock, $configurationMock, $storeManagerMock, new Serializer()])
            ->setMethodsExcept(['getCountryLabelsMapping'])
            ->getMock();

        $configMock->expects($this->once())
            ->method('getValue')
            ->willReturn($configValueMock);

        $this->assertSame($expected, $configMock->getCountryLabelsMapping());
    }

    /**
     * @return  array
     */
    public function getCountryLabelsMappingDataProvider()
    {
        return [
            [null, []],
            ['', []],
            ['[]', []],
            [['foo'], []],
            [
                '{"0":{"country_label":"France M\u00e9tropolitaine","country_id":"FR"},"1":{"country_label":"USA","country_id":"US"},"2":{"country_label":"Great Britain","country_id":"GB"}}',
                [
                    ['country_label' => 'France Métropolitaine', 'country_id' => 'FR'],
                    ['country_label' => 'USA', 'country_id'=> 'US'],
                    ['country_label' => 'Great Britain', 'country_id' => 'GB'],
                ]
            ],
        ];
    }
}
