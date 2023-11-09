<?php
namespace MiraklSeller\Sales\Helper;

use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\ResourceModel\Country as CountryResource;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
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
     * @var CountryResource
     */
    protected $countryResource;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Context                 $context
     * @param   ResolverInterface       $localeResolver
     * @param   OrderCollectionFactory  $orderCollectionFactory
     * @param   Config                  $config
     * @param   CountryResource         $countryResource
     * @param   CountryFactory          $countryFactory
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        OrderCollectionFactory $orderCollectionFactory,
        Config $config,
        CountryResource $countryResource,
        CountryFactory $countryFactory
    ) {
        parent::__construct($context);
        $this->localeResolver = $localeResolver;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->config = $config;
        $this->countryResource = $countryResource;
        $this->countryFactory = $countryFactory;
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
        $country = $this->countryFactory->create();

        try {
            $this->countryResource->loadByCode($country, $code);
        } catch (LocalizedException $e) {
            return $code;
        }

        $locale = $this->localeResolver->getLocale();

        return $country->getId() ? $country->getName($locale) : $code;
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
     *
     * <code>
     * [
     *     'product' => [
     *         'sku_1' => [
     *             'tax_code_1' => ['amount' => 7.20, 'rate' => 5.5],
     *             'tax_code_2' => ['amount' => 2.08, 'rate' => 1.5],
     *         ],
     *         'sku_2' => [
     *             'tax_code_1' => ['amount' => 3.81, 'rate' => 20],
     *             'tax_code_2' => ['amount' => 0.87, 'rate' => 9.2],
     *             'tax_code_3' => ['amount' => 0.19, 'rate' => 3.4],
     *         ],
     *     ],
     *     'shipping' => [
     *         'sku_1' => [
     *             'tax_code_1' => ['amount' => 1.78, 'rate' => 8.9],
     *         ],
     *         'sku_2' => [
     *             'tax_code_1' => ['amount' => 0.99, 'rate' => 0.67],
     *         ],
     *     ],
     * ]
     * </code>
     *
     * @param   ShopOrder   $miraklOrder
     * @param   array       $excludeStatuses
     * @return  array
     */
    public function getMiraklOrderTaxDetails($miraklOrder, $excludeStatuses = [OrderState::REFUSED, OrderState::CANCELED])
    {
        $result = [];

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            if (in_array($orderLine->getStatus()->getState(), $excludeStatuses)) {
                continue; // Do not use refused or canceled order lines
            }

            $sku = $orderLine->getOffer()->getSku();

            /** @var \Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount $tax */
            foreach ($orderLine->getTaxes() as $tax) {
                if (!isset($result['product'][$sku][$tax->getCode()])) {
                    $result['product'][$sku][$tax->getCode()] = [
                        'amount' => 0,
                        'rate'   => $tax->getRate(),
                    ];
                }
                $result['product'][$sku][$tax->getCode()]['amount'] += $tax->getAmount();
            }

            foreach ($orderLine->getShippingTaxes() as $tax) {
                if (!isset($result['shipping'][$sku][$tax->getCode()])) {
                    $result['shipping'][$sku][$tax->getCode()] = [
                        'amount' => 0,
                        'rate'   => $tax->getRate(),
                    ];
                }
                $result['shipping'][$sku][$tax->getCode()]['amount'] += $tax->getAmount();
            }
        }

        return $result;
    }

    /**
     * Will return Mirakl order tax details like that:
     * <code>
     * [
     *     'tax_code_1' => ['amount' => 13.78, 'rate' => 10],
     *     'tax_code_2' => ['amount' => 2.95, 'rate' => 5.5],
     *     'tax_code_3' => ['amount' => 0.19, 'rate' => 8.9],
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
                foreach ($taxDetails as $code => $tax) {
                    if (!isset($result[$code])) {
                        $result[$code] = [
                            'amount' => 0,
                            'rate'   => $tax['rate'] ?? 0,
                        ];
                    }
                    $result[$code]['amount'] += $tax['amount'];
                }
            }
        }

        return $result;
    }

    /**
     * @param ShopOrder $miraklOrder
     * @param bool      $withShipping
     * @param array     $excludeStatuses
     * @return float
     */
    public function getMiraklOrderTaxAmount(
        ShopOrder $miraklOrder,
        $withShipping = false,
        $excludeStatuses = [OrderState::REFUSED, OrderState::CANCELED]
    ) {
        $taxAmount = 0;

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $taxAmount += $this->getMiraklOrderLineTaxAmount($orderLine, $withShipping, $excludeStatuses);
        }

        return $taxAmount;
    }

    /**
     * @param ShopOrderLine $miraklOrderLine
     * @param bool          $withShipping
     * @param array         $excludeStatuses
     * @return float
     */
    public function getMiraklOrderLineTaxAmount(
        ShopOrderLine $miraklOrderLine,
        $withShipping = false,
        $excludeStatuses = [OrderState::REFUSED, OrderState::CANCELED]
    ) {
        $taxAmount = 0;

        if (!in_array($miraklOrderLine->getStatus()->getState(), $excludeStatuses)) {
            /** @var OrderTaxAmount $tax */
            foreach ($miraklOrderLine->getTaxes() as $tax) {
                $taxAmount += $tax->getAmount();
            }
        }

        return $taxAmount + ($withShipping ? $this->getMiraklOrderLineShippingTaxAmount($miraklOrderLine, $excludeStatuses) : 0);
    }

    /**
     * @param ShopOrderLine $miraklOrderLine
     * @param array         $excludeStatuses
     * @return float
     */
    public function getMiraklOrderLineShippingTaxAmount(
        ShopOrderLine $miraklOrderLine,
        $excludeStatuses = [OrderState::REFUSED, OrderState::CANCELED]
    ) {
        $taxAmount = 0;

        if (!in_array($miraklOrderLine->getStatus()->getState(), $excludeStatuses)) {
            /** @var OrderTaxAmount $tax */
            foreach ($miraklOrderLine->getShippingTaxes() as $tax) {
                $taxAmount += $tax->getAmount();
            }
        }

        return $taxAmount;
    }

    /**
     * @param ShopOrder $miraklOrder
     * @param array     $excludeStatuses
     * @return float
     */
    public function getMiraklOrderShippingTaxAmount(
        ShopOrder $miraklOrder,
        $excludeStatuses = [OrderState::REFUSED, OrderState::CANCELED]
    ) {
        $taxAmount = 0;

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $taxAmount += $this->getMiraklOrderLineShippingTaxAmount($orderLine, $excludeStatuses);
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
            'PAY_ON_DELIVERY',
            'PAY_ON_DUE_DATE',
            'PAY_ON_SHIPMENT',
            'NO_CUSTOMER_PAYMENT_CONFIRMATION',
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
