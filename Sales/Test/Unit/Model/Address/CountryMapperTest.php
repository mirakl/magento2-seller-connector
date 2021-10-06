<?php
namespace MiraklSeller\Sales\Test\Unit\Model\Address;

use MiraklSeller\Sales\Model\Address\CountryMapper;
use PHPUnit\Framework\TestCase;

class CountryMapperTest extends TestCase
{
    /**
     * @param   array           $mapping
     * @param   string          $countryLabel
     * @param   string|false    $expected
     * @dataProvider getTestGetDataProvider
     */
    public function testGet(array $mapping, $countryLabel, $expected)
    {
        /** @var \MiraklSeller\Sales\Helper\Config|\PHPUnit\Framework\MockObject\MockObject $configMock */
        $configMock = $this->createMock(\MiraklSeller\Sales\Helper\Config::class);

        $configMock->expects($this->once())
            ->method('getCountryLabelsMapping')
            ->willReturn($mapping);

        $countryMapper = new CountryMapper($configMock);
        $countryId = $countryMapper->get($countryLabel);

        $this->assertSame($expected, $countryId);
    }

    /**
     * @return  array
     */
    public function getTestGetDataProvider()
    {
        return [
            [
                [], 'France', false
            ],
            [
                ['foo' => 'bar'], 'foo', false
            ],
            [
                [['country_label' => 'USA', 'country_id' => 'US']], 'France', false
            ],
            [
                [['country_label' => 'USA', 'country_id' => '']], 'foo', false
            ],
            [
                [
                    ['country_label' => 'USA', 'country_id' => 'US'],
                    ['country_label' => 'France Métropolitaine', 'country_id' => 'FR'],
                ],
                'France Métropolitaine',
                'FR'
            ],
            [
                [['country_label' => 'foo', 'country_id' => 'bar']], null, ['foo' => 'bar']
            ]
        ];
    }
}