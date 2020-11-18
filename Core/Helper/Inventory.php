<?php
namespace MiraklSeller\Core\Helper;

use Magento\CatalogInventory\Helper\Minsaleqty;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Inventory extends AbstractHelper
{
    /**
     * @var Minsaleqty
     */
    protected $minsaleqty;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var bool
     */
    protected $flagEnableQtyIncrements = false;

    /**
     * @var bool
     */
    protected $configEnableQtyIncrements;

    /**
     * @var bool
     */
    protected $flagMinSaleQuantity = false;

    /**
     * @var mixed
     */
    protected $configMinSaleQuantity;

    /**
     * @var bool
     */
    protected $flagMaxSaleQuantity = false;

    /**
     * @var mixed
     */
    protected $configMaxSaleQuantity;

    /**
     * @var bool
     */
    protected $flagQtyIncrements = false;

    /**
     * @var mixed
     */
    protected $configQtyIncrements;

    /**
     * @param   Context     $context
     * @param   Minsaleqty  $minsaleqty
     * @param   Config      $config
     */
    public function __construct(
        Context $context,
        Minsaleqty $minsaleqty,
        Config $config
    ) {
        parent::__construct($context);
        $this->minsaleqty = $minsaleqty;
        $this->config = $config;
    }

    /**
     * @return  bool
     */
    protected function getConfigEnableQtyIncrements()
    {
        if (!$this->flagEnableQtyIncrements) {
            $this->configEnableQtyIncrements = (bool) $this->getConfigValue(
                Configuration::XML_PATH_ENABLE_QTY_INCREMENTS
            );
            $this->flagEnableQtyIncrements = true;
        }

        return $this->configEnableQtyIncrements;
    }

    /**
     * @return  mixed
     */
    protected function getConfigMaxSaleQuantity()
    {
        if (!$this->flagMaxSaleQuantity) {
            $this->configMaxSaleQuantity = $this->getConfigValue(
                Configuration::XML_PATH_MAX_SALE_QTY
            );
            $this->flagMaxSaleQuantity = true;
        }

        return $this->configMaxSaleQuantity;
    }

    /**
     * Returns a config value
     *
     * @param   string  $path
     * @param   mixed   $store
     * @return  mixed
     */
    protected function getConfigValue($path, $store = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @return  mixed
     */
    protected function getConfigMinSaleQuantity()
    {
        if (!$this->flagMinSaleQuantity) {
            $this->configMinSaleQuantity = $this->minsaleqty
                ->getConfigValue($this->config->getCustomerGroup());
            $this->flagMinSaleQuantity = true;
        }

        return $this->configMinSaleQuantity;
    }

    /**
     * @return  mixed
     */
    protected function getConfigQtyIncrements()
    {
        if (!$this->flagQtyIncrements) {
            $this->configQtyIncrements = $this->getConfigValue(
                Configuration::XML_PATH_QTY_INCREMENTS
            );
            $this->flagQtyIncrements = true;
        }

        return $this->configQtyIncrements;
    }

    /**
     * @param   bool    $useConfig
     * @param   float   $productValue
     * @return  float|null
     */
    public function getMaxSaleQuantity($useConfig, $productValue)
    {
        $val = $useConfig ? $this->getConfigMaxSaleQuantity() : $productValue;

        return (float) $val ?: null;
    }

    /**
     * @param   bool    $useConfig
     * @param   float   $productValue
     * @return  float|null
     */
    public function getMinSaleQuantity($useConfig, $productValue)
    {
        $val = $useConfig ? $this->getConfigMinSaleQuantity() : $productValue;

        return (float) $val ?: null;
    }

    /**
     * @param   bool    $useConfig
     * @param   float   $productValue
     * @return  float|null
     */
    public function getQtyIncrements($useConfig, $productValue)
    {
        $val = $useConfig ? $this->getConfigQtyIncrements() : $productValue;

        return (float) $val ?: null;
    }

    /**
     * @param   bool    $useConfig
     * @param   bool    $productValue
     * @return  bool
     */
    public function isEnabledQtyIncrements($useConfig, $productValue)
    {
        $val = $useConfig ? $this->getConfigEnableQtyIncrements() : $productValue;

        return (bool) $val;
    }
}