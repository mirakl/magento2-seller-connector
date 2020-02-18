<?php
namespace MiraklSeller\Sales\Model\Mapper;

use MiraklSeller\Sales\Model\Address\CountryResolver;

class Address implements MapperInterface
{
    /**
     * @var CountryResolver
     */
    protected $countryResolver;

    /**
     * @param CountryResolver $countryResolver
     */
    public function __construct(CountryResolver $countryResolver)
    {
        $this->countryResolver = $countryResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $data, $locale = null)
    {
        $countryId = $this->countryResolver->resolve($data, $locale);

        $phone = $data['phone'];
        if (!$phone && !empty($data['phone_secondary'])) {
            $phone = $data['phone_secondary'];
        }

        $result = [
            'firstname'  => $data['firstname'] ?? '',
            'lastname'   => $data['lastname'] ?? '',
            'company'    => $data['company'] ?? '',
            'street'     => trim(($data['street_1'] ?? '') . "\n" . ($data['street_2'] ?? '')),
            'telephone'  => $phone,
            'postcode'   => $data['zip_code'] ?? '',
            'city'       => $data['city'] ?? '',
            'region'     => $data['state'] ?? '',
            'country_id' => $countryId ?: '',
            'country'    => $data['country'],
        ];

        return $result;
    }
}