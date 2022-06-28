<?php
namespace MiraklSeller\Sales\Helper\Order;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Helper\Config as SalesConfig;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Model\Address\CountryMapper;
use MiraklSeller\Sales\Model\Create\Invoice as InvoiceCreator;
use MiraklSeller\Sales\Model\Create\Order as OrderCreator;
use MiraklSeller\Sales\Model\Mapper\CountryNotFoundException;
use MiraklSeller\Sales\Model\Synchronize\Shipments as SynchronizeShipments;

class Import extends AbstractHelper
{
    /**
     * @var OrderResourceFactory
     */
    protected $orderResourceFactory;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var SalesConfig
     */
    protected $salesConfig;

    /**
     * @var OrderCreator
     */
    protected $orderCreator;

    /**
     * @var InvoiceCreator
     */
    protected $invoiceCreator;

    /**
     * @var SynchronizeShipments
     */
    protected $synchronizeShipments;

    /**
     * @var CountryMapper
     */
    protected $countryMapper;

    /**
     * @param   Context                 $context
     * @param   OrderResourceFactory    $orderResourceFactory
     * @param   OrderHelper             $orderHelper
     * @param   SalesConfig             $salesConfig
     * @param   OrderCreator            $orderCreator
     * @param   InvoiceCreator          $invoiceCreator
     * @param   SynchronizeShipments    $synchronizeShipments
     * @param   CountryMapper           $countryMapper
     */
    public function __construct(
        Context $context,
        OrderResourceFactory $orderResourceFactory,
        OrderHelper $orderHelper,
        SalesConfig $salesConfig,
        OrderCreator $orderCreator,
        InvoiceCreator $invoiceCreator,
        SynchronizeShipments $synchronizeShipments,
        CountryMapper $countryMapper
    ) {
        parent::__construct($context);

        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderHelper          = $orderHelper;
        $this->salesConfig          = $salesConfig;
        $this->orderCreator         = $orderCreator;
        $this->invoiceCreator       = $invoiceCreator;
        $this->synchronizeShipments = $synchronizeShipments;
        $this->countryMapper        = $countryMapper;
    }

    /**
     * Converts a Mirakl order into a Magento order
     *
     * @param   ShopOrder   $miraklOrder
     * @param   Connection  $connection
     * @return  Order
     */
    public function createOrder(ShopOrder $miraklOrder, Connection $connection)
    {
        try {
            $order = $this->orderCreator->create($miraklOrder, $connection->getStoreId());
        } catch (CountryNotFoundException $e) {
            if ($country = $e->getCountry()) {
                $this->countryMapper->add($country);
            }

            throw $e;
        }

        // Save some Mirakl information to be able to associate actions on it later
        $order->setMiraklConnectionId($connection->getId());
        $order->setMiraklOrderId($miraklOrder->getId());
        $order->setMiraklSync(true);

        $fulfillment = $miraklOrder->getFulfillment();
        if ($fulfillment && $center = $fulfillment->getCenter()) {
            $order->setMiraklFulfillmentCenter($center->getCode());
        }

        /** @var \Magento\Sales\Model\ResourceModel\Order $orderResource */
        $orderResource = $this->orderResourceFactory->create();
        $orderResource->saveAttribute($order, ['mirakl_connection_id', 'mirakl_order_id', 'mirakl_sync', 'mirakl_fulfillment_center']);

        if ($this->salesConfig->isAutoCreateInvoice()
            && ($this->orderHelper->isMiraklOrderInvoiced($miraklOrder) || $this->orderHelper->isAutoPayInvoice($miraklOrder))
        ) {
            $this->invoiceCreator->create($order);
        }

        if ($this->salesConfig->isAutoCreateShipment()) {
            $this->synchronizeShipments->synchronize($order, $miraklOrder);
        }

        return $order;
    }

    /*
     * @param   Connection  $connection
     * @param   ShopOrder   $miraklOrder
     * @return  Order
     * @throws  \Exception
     */
    public function importMiraklOrder(Connection $connection, ShopOrder $miraklOrder)
    {
        if (!$this->orderHelper->canImport($miraklOrder->getStatus()->getState())) {
            throw new \Exception(__('The Mirakl order #%1 cannot be imported', $miraklOrder->getId()));
        }

        if ($order = $this->orderHelper->getOrderByMiraklOrderId($miraklOrder->getId())) {
            throw new AlreadyExistsException(__(
                'The Mirakl order #%1 has already been imported (#%2)', $miraklOrder->getId(), $order->getIncrementId()
            ));
        }

        return $this->createOrder($miraklOrder, $connection);
    }
}
