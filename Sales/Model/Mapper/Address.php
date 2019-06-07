<?php
namespace MiraklSeller\Sales\Model\Mapper;

use MiraklSeller\Sales\Helper\Order as OrderHelper;

class Address implements MapperInterface
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var string
     */
    protected $defaultLocale;

    /**
     * @param   OrderHelper $orderHelper
     * @param   string      $defaultLocale
     */
    public function __construct(OrderHelper $orderHelper, $defaultLocale = 'en_US')
    {
        $this->orderHelper = $orderHelper;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $data, $locale = null)
    {
        if (empty($locale)) {
            $locale = $this->defaultLocale;
        }

        $countries = $this->orderHelper->getCountryList($locale);
        $countryCode = array_search($data['country'], $countries);
        $phone = $data['phone'];
        if (!$phone && !empty($data['phone_secondary'])) {
            $phone = $data['phone_secondary'];
        }

        $result = [
            'firstname'  => $data['firstname'] ?? '',
            'lastname'   => $data['lastname'] ?? '',
            'street'     => trim(($data['street_1'] ?? '') . "\n" . ($data['street_2'] ?? '')),
            'telephone'  => $phone,
            'postcode'   => $data['zip_code'] ?? '',
            'city'       => $data['city'] ?? '',
            'country_id' => $countryCode ?: $data['country'],
            'country'    => $data['country'],
        ];

        return $result;
    }
}