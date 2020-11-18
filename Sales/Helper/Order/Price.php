<?php
namespace MiraklSeller\Sales\Helper\Order;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Helper\Config;

class Price extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Context                 $context
     * @param   StoreManagerInterface   $storeManager
     * @param   RuleFactory             $ruleFactory
     * @param   Config                  $config
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        RuleFactory $ruleFactory,
        Config $config
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->ruleFactory  = $ruleFactory;
        $this->config       = $config;
    }

    /**
     * @param   Product     $product
     * @param   Connection  $connection
     * @param   null|int    $qty
     * @return  float
     */
    public function getMagentoPrice(Product $product, Connection $connection, $qty = null)
    {
        // Check if custom price is available on the product
        if ($connection->getExportedPricesAttribute()) {
            $magentoPrice = $product->getData($connection->getExportedPricesAttribute());
            if (!empty($magentoPrice)) {
                return $magentoPrice;
            }
        }

        // Check if a discount price is available on the product
        $magentoPrice = $this->getDiscountPrice($product, $connection->getStoreId());
        if (!empty($magentoPrice)) {
            return $magentoPrice;
        }

        return $product->getFinalPrice($qty);
    }

    /**
     * @param   Product $product
     * @param   mixed   $store
     * @return  float|null
     */
    public function getDiscountPrice(Product $product, $store = null)
    {
        $store = $store ? $this->storeManager->getStore($store) : $this->storeManager->getDefaultStoreView();
        $product->setStoreId($store->getId());
        $product->setCustomerGroupId($this->getCustomerGroupId());

        // Check if a discount price is available on the product
        /** @var \Magento\CatalogRule\Model\Rule $catalogRule */
        $catalogRule = $this->ruleFactory->create();

        return $catalogRule->calcProductPriceRule($product, $product->getPrice());
    }

    /**
     * @return  int
     */
    public function getCustomerGroupId()
    {
        return $this->config->getCustomerGroup();
    }
}