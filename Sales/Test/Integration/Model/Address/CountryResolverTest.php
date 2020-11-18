<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Address;

use MiraklSeller\Sales\Model\Address\CountryResolver;
use MiraklSeller\Api\Test\Integration\TestCase;

class CountryResolverTest extends TestCase
{
    /**
     * @param   array           $data
     * @param   string|null     $locale
     * @param   string          $defaultLocale
     * @param   string|false    $expected
     * @dataProvider getTestResolveDataProvider
     */
    public function testResolve(array $data, $locale, $defaultLocale, $expected)
    {
        /** @var CountryResolver $countryResolver */
        $countryResolver = $this->objectManager->create(CountryResolver::class, [
            'defaultLocale' => $defaultLocale
        ]);

        $countryId = $countryResolver->resolve($data, $locale);

        $this->assertSame($expected, $countryId);
    }

    /**
     * @return  array
     */
    public function getTestResolveDataProvider()
    {
        return [
            [['country_iso_code' => 'FRA', 'country' => 'foo'], 'fr_FR', 'en_US', 'FR'],
            [['country_iso_code' => 'GBR', 'country' => 'Royaume-Uni'], 'fr_FR', 'en_US', 'GB'],
            [['country' => 'Royaume-Uni'], 'fr_FR', 'en_US', 'GB'],
            [['country' => 'United Kingdom'], 'fr_FR', 'en_US', 'GB'],
            [['country' => ''], 'en_US', 'en_US', false],
            [['country' => 'foobar'], 'en_GB', 'en_GB', false],
        ];
    }
}