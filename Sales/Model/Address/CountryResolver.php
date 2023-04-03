<?php
namespace MiraklSeller\Sales\Model\Address;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

class CountryResolver
{
    /**
     * @var CountryCollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var CountryMapper
     */
    private $countryMapper;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @param   RegionCollectionFactory $regionCollectionFactory
     * @param   OrderHelper             $orderHelper
     * @param   CountryMapper           $countryMapper
     * @param   string                  $defaultLocale
     */
    public function __construct(
        CountryCollectionFactory $countryCollectionFactory,
        OrderHelper $orderHelper,
        CountryMapper $countryMapper,
        $defaultLocale = 'en_US'
    ) {
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->orderHelper = $orderHelper;
        $this->countryMapper = $countryMapper;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @param   string  $countryIso3Code
     * @return  string|false
     */
    private function getCountryIdByIso3Code($countryIso3Code)
    {
        /** @var \Magento\Directory\Model\ResourceModel\Country\Collection $collection */
        $collection = $this->countryCollectionFactory->create();
        $collection->addCountryCodeFilter($countryIso3Code, 'iso3');

        /** @var \Magento\Directory\Model\Country $country */
        $country = $collection->getFirstItem();

        return $country->getCountryId() ?: false;
    }

    /**
     * @param   string      $countryLabel
     * @param   string|null $locale
     * @return  string|false
     */
    private function getCountryIdByLabel($countryLabel, $locale = null)
    {
        $countryLabel = trim($countryLabel);

        if (false !== ($countryId = $this->countryMapper->get($countryLabel))) {
            return $countryId;
        }

        $countryCollection = $this->countryCollectionFactory->create();

        foreach ($countryCollection as $country) {
            /** @var $country \Magento\Directory\Model\Country */
            if ($countryLabel === $country->getName($locale)) {
                return $country->getCountryId();
            }
        }

        return false;
    }

    /**
     * @param   array       $data
     * @param   string|null $locale
     * @return  string|false
     */
    public function resolve(array $data, $locale = null)
    {
        $countryId = false;

        if (!empty($data['country_iso_code'])) {
            // Try with country ISO 3 code
            $countryId = $this->getCountryIdByIso3Code($data['country_iso_code']);
        }

        if (false === $countryId && !empty($data['country'])) {
            if (null !== $locale) {
                // Try with specified locale
                $countryId = $this->getCountryIdByLabel($data['country'], $locale);
            }

            if (false === $countryId && $locale !== $this->defaultLocale) {
                // Try with default locale
                $countryId = $this->getCountryIdByLabel($data['country'], $this->defaultLocale);
            }
        }

        return $countryId;
    }
}