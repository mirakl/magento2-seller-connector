<?php
namespace MiraklSeller\Sales\Test\Integration\Helper;

use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\ResourceModel\Country as CountryResource;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

class OrderTest extends TestCase
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderHelper = $this->objectManager->create(OrderHelper::class, [
            'countryFactory'  => $this->objectManager->create(CountryFactory::class),
            'countryResource' => $this->objectManager->create(CountryResource::class),
        ]);
    }

    /**
     * @covers  OrderHelper::getCountryByCode
     * @param   string  $code
     * @param   bool    $expected
     * @dataProvider getTestGetCountryByCodeDataProvider
     */
    public function testGetCountryByCode($code, $expected)
    {
        $this->assertSame($expected, $this->orderHelper->getCountryByCode($code));
    }

    /**
     * @return  array
     */
    public function getTestGetCountryByCodeDataProvider()
    {
        return [
            ['FR', 'France'],
            ['US', 'United States'],
            ['RU', 'Russia'],
            ['ES', 'Spain'],
            ['DE', 'Germany'],
        ];
    }
}
