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
use Mirakl\MMP\Common\Domain\Payment\PaymentWorkflow;
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
     * Will return Mirakl order tax details like that:
     * <code>
     * [
     *     'product' => [
     *         'sku_1' => [
     *             'tax_code_1' => 7.20,
     *             'tax_code_2' => 2.08,
     *         ],
     *         'sku_2' => [
     *             'tax_code_1' => 3.81,
     *             'tax_code_2' => 0.87,
     *             'tax_code_3' => 0.19,
     *         ],
     *     ],
     *     'shipping' => [
     *         'sku_1' => [
     *             'tax_code_1' => 1.78,
     *         ],
     *         'sku_2' => [
     *             'tax_code_1' => 0.99,
     *         ],
     *     ],
     * ]
     * </code>
     *
     * @param   ShopOrder   $miraklOrder
     * @return  array
     */
    public function getMiraklOrderTaxDetails($miraklOrder)
    {
        $result = [];

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            if (in_array($orderLine->getStatus()->getState(), [OrderState::REFUSED, OrderState::CANCELED])) {
                continue; // Do not use refused or canceled order lines
            }

            $sku = $orderLine->getOffer()->getSku();

            /** @var \Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount $tax */
            foreach ($orderLine->getTaxes() as $tax) {
                if (!isset($result['product'][$sku][$tax->getCode()])) {
                    $result['product'][$sku][$tax->getCode()] = 0;
                }
                $result['product'][$sku][$tax->getCode()] += $tax->getAmount();
            }

            foreach ($orderLine->getShippingTaxes() as $tax) {
                if (!isset($result['shipping'][$sku][$tax->getCode()])) {
                    $result['shipping'][$sku][$tax->getCode()] = 0;
                }
                $result['shipping'][$sku][$tax->getCode()] += $tax->getAmount();
            }
        }

        return $result;
    }

    /**
     * Will return Mirakl order tax details like that:
     * <code>
     * [
     *     'tax_code_1' => 13.78,
     *     'tax_code_2' => 2.95,
     *     'tax_code_3' => 0.19,
     * ]
     * </code>
     *
     * @param   ShopOrder   $miraklOrder
     * @return  array
     */
    public function getMiraklOrderTaxDetailsComputed($miraklOrder)
    {
        $result = [];

        foreach ($this->getMiraklOrderTaxDetails($miraklOrder) as $orderLineTaxes) {
            foreach ($orderLineTaxes as $taxDetails) {
                foreach ($taxDetails as $code => $amount) {
                    if (!isset($result[$code])) {
                        $result[$code] = 0;
                    }
                    $result[$code] += $amount;
                }
            }
        }

        return $result;
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

        if (!in_array($miraklOrderLine->getStatus()->getState(), [OrderState::REFUSED, OrderState::CANCELED])) {
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

        if (!in_array($miraklOrderLine->getStatus()->getState(), [OrderState::REFUSED, OrderState::CANCELED])) {
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
     * @param   OrderModel  $order
     * @param   string      $sku
     * @return  OrderModel\Item|null
     */
    public function getOrderItemBySku(OrderModel $order, $sku)
    {
        /** @var OrderModel\Item $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getSku() == $sku) {
                return $orderItem;
            }
        }

        return null;
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function isAutoPayInvoice(ShopOrder $miraklOrder)
    {
        if (!$this->config->isAutoPayInvoice()) {
            return false;
        }

        return in_array($miraklOrder->getPaymentWorkflow(), [
            PaymentWorkflow::PAY_ON_DELIVERY,
            PaymentWorkflow::PAY_ON_DUE_DATE,
        ]);
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
