<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Synchronize;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Model\Create\Order as OrderCreator;
use MiraklSeller\Sales\Model\Synchronize\Order as OrderSynchronizer;

class OrderTest extends TestCase
{
    /**
     * @var OrderCreator
     */
    protected $orderCreator;

    /**
     * @var OrderSynchronizer
     */
    protected $orderSynchronizer;

    protected function setUp()
    {
        parent::setUp();
        $this->orderCreator = $this->objectManager->create(OrderCreator::class);
        $this->orderSynchronizer = $this->objectManager->create(OrderSynchronizer::class);
    }

    /**
     * @return  ShopOrder
     */
    protected function getMiraklOrderMock()
    {
        return $this->objectManager->create(ShopOrder::class, [
            'data' => $this->_getJsonFileContents('mirakl_order.json')
        ]);
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_invoice 0
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_shipment 0
     */
    public function testCancelMagentoOrder()
    {
        $miraklOrderMock = $this->getMiraklOrderMock();

        $order = $this->orderCreator->create($miraklOrderMock);

        $this->assertFalse($order->isCanceled());

        // Emulates status CANCELED on Mirakl order to cancel the associated Magento order
        $miraklOrderMock->getStatus()->setState(OrderState::CANCELED);

        $updated = $this->orderSynchronizer->synchronize($order, $miraklOrderMock);

        $this->assertTrue($updated);
        $this->assertTrue($order->isCanceled());
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_invoice 0
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_shipment 0
     */
    public function testHoldMagentoOrder()
    {
        $miraklOrderMock = $this->getMiraklOrderMock();

        $order = $this->orderCreator->create($miraklOrderMock);

        $this->assertCount(0, $order->getInvoiceCollection());

        // Emulates status REFUSED on Mirakl order to hold the associated Magento order
        $miraklOrderMock->getStatus()->setState(OrderState::REFUSED);

        $updated = $this->orderSynchronizer->synchronize($order, $miraklOrderMock);

        $this->assertTrue($updated);
        $this->assertTrue($order->getStatus() === Order::STATE_HOLDED);
        $this->assertFalse($order->canHold());
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_invoice 1
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_shipment 0
     */
    public function testInvoiceMagentoOrder()
    {
        $miraklOrderMock = $this->getMiraklOrderMock();

        $order = $this->orderCreator->create($miraklOrderMock);

        $this->assertCount(0, $order->getInvoiceCollection());

        // Emulates a customer debited date to create an invoice on the associated Magento order
        $miraklOrderMock->setCustomerDebitedDate(new \DateTime('now', new \DateTimeZone('UTC')));

        $updated = $this->orderSynchronizer->synchronize($order, $miraklOrderMock);

        $this->assertTrue($updated);

        /** @var \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection $invoices */
        $invoices = $this->objectManager->get(InvoiceCollectionFactory::class)->create();
        $invoices->setOrderFilter($order);

        $this->assertCount(1, $invoices);
        $this->assertFalse($order->canInvoice());
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_invoice 0
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_shipment 1
     */
    public function testShipMagentoOrder()
    {
        $miraklOrderMock = $this->getMiraklOrderMock();

        $order = $this->orderCreator->create($miraklOrderMock);

        $this->assertCount(0, $order->getShipmentsCollection());

        // Emulates status SHIPPED on Mirakl order to create shipment on the associated Magento order
        $miraklOrderMock->getStatus()->setState(OrderState::SHIPPED);

        $updated = $this->orderSynchronizer->synchronize($order, $miraklOrderMock);

        $this->assertTrue($updated);

        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments */
        $shipments = $this->objectManager->get(ShipmentCollectionFactory::class)->create();
        $shipments->setOrderFilter($order);

        $this->assertCount(1, $shipments);
        $this->assertFalse($order->canShip());
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_invoice 0
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_shipment 0
     */
    public function testNothingToSynchronize()
    {
        $miraklOrderMock = $this->getMiraklOrderMock();

        $order = $this->orderCreator->create($miraklOrderMock);

        $updated = $this->orderSynchronizer->synchronize($order, $miraklOrderMock);

        $this->assertFalse($updated);
    }
}