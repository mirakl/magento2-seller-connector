<?php
namespace MiraklSeller\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Common\Domain\Order\ShopOrderLine;
use Mirakl\MMP\Common\Domain\Order\State\OrderStatus;
use Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Model\Connection;

class Order extends AbstractHelper
{
    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Context                 $context
     * @param   ResolverInterface       $localeResolver
     * @param   OrderCollectionFactory  $orderCollectionFactory
     * @param   Config                  $config
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        OrderCollectionFactory $orderCollectionFactory,
        Config $config
    ) {
        parent::__construct($context);

        $this->localeResolver = $localeResolver;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->config = $config;
    }

    /**
     * @param   string  $status
     * @return  bool
     */
    public function canImport($status)
    {
        return in_array($status, $this->config->getAllowedStatusesForOrdersImport());
    }

    /**
     * @param   string  $code
     * @return  string
     */
    public function getCountryByCode($code)
    {
        $countries = $this->getCountryList();

        return isset($countries[$code]) ? $countries[$code] : $code;
    }

    /**
     * @param   string|null $locale
     * @return  array
     */
    public function getCountryList($locale = null)
    {
        if (null === $locale) {
            $locale = $this->localeResolver->getLocale();
        }

        return \Zend_Locale::getTranslationList('territory', $locale, 2);
    }

    /**
     * @param   Connection  $connection
     * @return  OrderCollection
     */
    public function getMagentoOrdersByConnection(Connection $connection)
    {
        /** @var OrderCollection $collection */
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('mirakl_connection_id', $connection->getId());

        return $collection;
    }

    /**
     * Retrieves the Magento orders associated with the specified Mirakl order ids
     *
     * @param   array   $miraklOrderIds
     * @return  OrderCollection
     */
    public function getMagentoOrdersByMiraklOrderIds(array $miraklOrderIds)
    {
        /** @var OrderCollection $collection */
        $collection = $this->orderCollectionFactory->create();

        if (empty($miraklOrderIds)) {
            $collection->addFieldToFilter('entity_id', 0); // Must return an empty collection
        } else {
            $collection->addFieldToFilter('mirakl_order_id', $miraklOrderIds);
        }

        return $collection;
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @param   bool        $withShipping
     * @return  float
     */
    public function getMiraklOrderTaxAmount(ShopOrder $miraklOrder, $withShipping = false)
    {
        $taxAmount = 0;

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $taxAmount += $this->getMiraklOrderLineTaxAmount($orderLine, $withShipping);
        }

        return $taxAmount;
    }

    /**
     * @param   ShopOrderLine   $miraklOrderLine
     * @param   bool            $withShipping
     * @return  float
     */
    public function getMiraklOrderLineTaxAmount(ShopOrderLine $miraklOrderLine, $withShipping = false)
    {
        $taxAmount = 0;

        if ($miraklOrderLine->getStatus()->getState() !== OrderState::REFUSED) {
            /** @var OrderTaxAmount $shippingTax */
            foreach ($miraklOrderLine->getTaxes() as $tax) {
                $taxAmount += $tax->getAmount();
            }
        }

        return $taxAmount + ($withShipping ? $this->getMiraklOrderLineShippingTaxAmount($miraklOrderLine) : 0);
    }

    /**
     * @param   ShopOrderLine   $miraklOrderLine
     * @return  float
     */
    public function getMiraklOrderLineShippingTaxAmount(ShopOrderLine $miraklOrderLine)
    {
        $taxAmount = 0;

        if ($miraklOrderLine->getStatus()->getState() !== OrderState::REFUSED) {
            /** @var OrderTaxAmount $shippingTax */
            foreach ($miraklOrderLine->getShippingTaxes() as $tax) {
                $taxAmount += $tax->getAmount();
            }
        }

        return $taxAmount;
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @return  float
     */
    public function getMiraklOrderShippingTaxAmount(ShopOrder $miraklOrder)
    {
        $taxAmount = 0;

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $taxAmount += $this->getMiraklOrderLineShippingTaxAmount($orderLine);
        }

        return $taxAmount;
    }

    /**
     * Returns a Magento order associated with the specified Mirakl order id if exists
     *
     * @param   string  $miraklOrderId
     * @return  OrderModel|null
     */
    public function getOrderByMiraklOrderId($miraklOrderId)
    {
        /** @var OrderCollection $collection */
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('mirakl_order_id', $miraklOrderId);

        return $collection->count() ? $collection->getFirstItem() : null;
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function isMiraklOrderInvoiced($miraklOrder)
    {
        return !empty($miraklOrder->getCustomerDebitedDate());
    }

    /**
     * Returns true if given Mirakl order has been shipped
     *
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function isMiraklOrderShipped(ShopOrder $miraklOrder)
    {
        return in_array($miraklOrder->getStatus()->getState(), [
            OrderStatus::SHIPPED,
            OrderStatus::TO_COLLECT,
            OrderStatus::RECEIVED,
            OrderStatus::CLOSED,
        ]);
    }
}
