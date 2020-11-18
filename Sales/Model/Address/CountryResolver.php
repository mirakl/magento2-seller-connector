<?php
namespace MiraklSeller\Sales\Model\Address;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

class CountryResolver
{
    /**
     * @var RegionCollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @param   RegionCollectionFactory $regionCollectionFactory
     * @param   OrderHelper             $orderHelper
     * @param   string                  $defaultLocale
     */
    public function __construct(
        RegionCollectionFactory $regionCollectionFactory,
        OrderHelper $orderHelper,
        $defaultLocale = 'en_US'
    ) {
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->orderHelper = $orderHelper;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @param   string  $countryIso3Code
     * @return  string|false
     */
    private function getCountryIdByIso3Code($countryIso3Code)
    {
        /** @var \Magento\Directory\Model\ResourceModel\Region\Collection $collection */
        $collection = $this->regionCollectionFactory->create();
        $collection->addCountryCodeFilter($countryIso3Code);

        /** @var \Magento\Directory\Model\Region $region */
        $region = $collection->getFirstItem();

        return $region->getCountryId() ?: false;
    }

    /**
     * @param   string      $countryLabel
     * @param   string|null $locale
     * @return  string|false
     */
    private function getCountryIdByLabel($countryLabel, $locale = null)
    {
        $countries = $this->orderHelper->getCountryList($locale);

        return array_search($countryLabel, $countries);
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