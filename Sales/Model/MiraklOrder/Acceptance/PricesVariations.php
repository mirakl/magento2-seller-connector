<?php
namespace MiraklSeller\Sales\Model\MiraklOrder\Acceptance;

use MiraklSeller\Sales\Helper\Config;

class PricesVariations
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Config  $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return  int|null
     */
    public function getConfig()
    {
        return $this->config->getPricesVariationsPercent();
    }

    /**
     * Returns true if price variation between Magento and Mirakl is valid according to config
     *
     * @param   float   $magentoPrice
     * @param   float   $miraklPrice
     * @return  bool
     */
    public function isPriceVariationValid($magentoPrice, $miraklPrice)
    {
        $percent = $this->getConfig();

        if (null === $percent) {
            return true;
        }

        return $miraklPrice >= ($magentoPrice * (1 - $percent / 100));
    }
}