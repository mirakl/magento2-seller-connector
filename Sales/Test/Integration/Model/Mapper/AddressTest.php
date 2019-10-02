<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Mapper;

use MiraklSeller\Sales\Model\Mapper\Address as AddressMapper;
use MiraklSeller\Api\Test\Integration\TestCase;

class AddressTest extends TestCase
{
    /**
     * @param   array   $data
     * @param   array   $expected
     * @dataProvider getTestMapDataProvider
     */
    public function testMap(array $data, array $expected)
    {
        /** @var AddressMapper $addressMapper */
        $addressMapper = $this->objectManager->create(AddressMapper::class);
        $this->assertEquals($expected, $addressMapper->map($data));
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