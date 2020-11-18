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
use MiraklSeller\Sales\Model\Create\Invoice as InvoiceCreator;
use MiraklSeller\Sales\Model\Create\Order as OrderCreator;
use MiraklSeller\Sales\Model\Create\Shipment as ShipmentCreator;

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
     * @var ShipmentCreator
     */
    protected $shipmentCreator;

    /**
     * @param   Context                 $context
     * @param   OrderResourceFactory    $orderResourceFactory
     * @param   OrderHelper             $orderHelper
     * @param   SalesConfig             $salesConfig
     * @param   OrderCreator            $orderCreator
     * @param   InvoiceCreator          $invoiceCreator
     * @param   ShipmentCreator         $shipmentCreator
     */
    public function __construct(
        Context $context,
        OrderResourceFactory $orderResourceFactory,
        OrderHelper $orderHelper,
        SalesConfig $salesConfig,
        OrderCreator $orderCreator,
        InvoiceCreator $invoiceCreator,
        ShipmentCreator $shipmentCreator
    ) {
        parent::__construct($context);

        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderHelper          = $orderHelper;
        $this->salesConfig          = $salesConfig;
        $this->orderCreator         = $orderCreator;
        $this->invoiceCreator       = $invoiceCreator;
        $this->shipmentCreator      = $shipmentCreator;
    }

    /**
     * Converts a Mirakl order into a Magento order
     *
     * @param   ShopOrder   $miraklOrder
     * @param   mixed       $store
     * @return  Order
     */
    public function createOrder(ShopOrder $miraklOrder, $store = null)
    {
        $order = $this->orderCreator->create($miraklOrder, $store);

        if ($this->salesConfig->isAutoCreateInvoice() && $this->orderHelper->isMiraklOrderInvoiced($miraklOrder)) {
            $this->invoiceCreator->create($order);
        }

        if ($this->salesConfig->isAutoCreateShipment() && $this->orderHelper->isMiraklOrderShipped($miraklOrder)) {
            $this->shipmentCreator->create($order, $miraklOrder);
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

        // Create the Magento order
        $order = $this->createOrder($miraklOrder, $connection->getStoreId());

        // Save some Mirakl information to be able to associate actions on it later
        $order->setMiraklConnectionId($connection->getId());
        $order->setMiraklOrderId($miraklOrder->getId());

        /** @var \Magento\Sales\Model\ResourceModel\Order $orderResource */
        $orderResource = $this->orderResourceFactory->create();
        $orderResource->save($order);

        return $order;
    }
}