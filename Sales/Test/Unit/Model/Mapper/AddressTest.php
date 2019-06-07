<?php
namespace MiraklSeller\Sales\Test\Unit\Model\Mapper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Model\Mapper\Address as AddressMapper;
use PHPUnit\Framework\TestCase;

/**
 * @group sales
 * @group model
 * @coversDefaultClass \MiraklSeller\Sales\Model\Mapper\Address
 */
class AddressTest extends TestCase
{
    /**
     * @var AddressMapper
     */
    protected $addressMapper;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $orderHelperMock = $this->getMockBuilder(OrderHelper::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getCountryList'])
            ->getMock();
        $this->addressMapper = $objectManager->getObject(AddressMapper::class, [
            'orderHelper' => $orderHelperMock,
        ]);
    }

    /**
     * @covers  ::map
     * @param   array   $data
     * @param   array   $expected
     * @dataProvider getTestMapDataProvider
     */
    public function testMap(array $data, array $expected)
    {
        $this->assertEquals($expected, $this->addressMapper->map($data));
    }

    /**
     * @return  array
     */
    public function getTestMapDataProvider()
    {
        return [
            [
                [
                    'firstname' => 'Mirakl',
                    'lastname'  => 'PHP Team',
                    'street_1'  => '45, rue de la Bienfaisance',
                    'street_2'  => '',
                    'phone'     => '+33 1 72 31 62 00',
                    'zip_code'  => '75008',
                    'city'      => 'Paris',
                    'country'   => 'France',
                ],
                [
                    'firstname'  => 'Mirakl',
                    'lastname'   => 'PHP Team',
                    'street'     => '45, rue de la Bienfaisance',
                    'telephone'  => '+33 1 72 31 62 00',
                    'postcode'   => '75008',
                    'city'       => 'Paris',
                    'country_id' => 'FR',
                    'country'    => 'France',
                ],
            ],
            [
                [
                    'firstname' => 'Mirakl Boston',
                    'lastname'  => 'PHP Team',
                    'street_1'  => '100 Dover St',
                    'street_2'  => 'Additional street information',
                    'phone'     => '+ 1 844 264-7255',
                    'zip_code'  => '02144',
                    'city'      => 'Boston',
                    'country'   => 'United States',
                ],
                [
                    'firstname'  => 'Mirakl Boston',
                    'lastname'   => 'PHP Team',
                    'street'     => "100 Dover St\nAdditional street information",
                    'telephone'  => '+ 1 844 264-7255',
                    'postcode'   => '02144',
                    'city'       => 'Boston',
                    'country_id' => 'US',
                    'country'    => 'United States',
                ],
            ],
        ];
    }
}