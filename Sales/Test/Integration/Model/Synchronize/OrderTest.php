<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Synchronize;

use GuzzleHttp\Exception\ClientException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentStatus;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Helper\Shipment as ShipmentApi;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Helper\Config;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Helper\Shipment as ShipmentHelper;
use MiraklSeller\Sales\Model\Create\Shipment as ShipmentCreator;
use MiraklSeller\Sales\Model\Create\Invoice as InvoiceCreator;
use MiraklSeller\Sales\Model\Create\Order as OrderCreator;
use MiraklSeller\Sales\Model\Synchronize\Order as OrderSynchronizer;
use MiraklSeller\Sales\Model\Synchronize\Refunds as SynchronizeRefunds;
use MiraklSeller\Sales\Model\Synchronize\Shipment as ShipmentSynchronizer;
use MiraklSeller\Sales\Model\Synchronize\Shipments as SynchronizeShipments;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OrderTest extends TestCase
{
    /**
     * @var OrderCreator
     */
    protected $orderCreator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderCreator = $this->objectManager->create(OrderCreator::class);
    }

    /**
     * @param   \PHPUnit\Framework\MockObject\MockObject    $shipmentApiMock
     * @return  OrderSynchronizer
     */
    protected function getOrderSynchronizer($shipmentApiMock)
    {
        return $this->objectManager->create(OrderSynchronizer::class, [
            'orderManagement'      => $this->objectManager->create(OrderManagementInterface::class),
            'invoiceCreator'       => $this->objectManager->create(InvoiceCreator::class),
            'shipmentCreator'      => $this->objectManager->create(ShipmentCreator::class),
            'synchronizeRefunds'   => $this->objectManager->create(SynchronizeRefunds::class),
            'config'               => $this->objectManager->create(Config::class),
            'orderHelper'          => $this->objectManager->create(OrderHelper::class),
            'synchronizeShipments' => $this->objectManager->create(SynchronizeShipments::class, [
                'shipmentApi'               => $shipmentApiMock,
                'shipmentCreator'           => $this->objectManager->create(ShipmentCreator::class),
                'shipmentSynchronizer'      => $this->objectManager->create(ShipmentSynchronizer::class),
                'orderHelper'               => $this->objectManager->create(OrderHelper::class),
                'shipmentHelper'            => $this->objectManager->create(ShipmentHelper::class),
                'connectionFactory'         => $this->objectManager->create(ConnectionFactory::class),
                'connectionResourceFactory' => $this->objectManager->create(ConnectionResourceFactory::class),
                'stateCodes'                => [
                    ShipmentStatus::SHIPPED,
                    ShipmentStatus::TO_COLLECT,
                    ShipmentStatus::RECEIVED,
                    ShipmentStatus::CLOSED,
                ],
            ])
        ]);
    }

    /**
     * @return  Connection
     */
    protected function getConnectionMock()
    {
        return $this->createMock(Connection::class);
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

        /** @var ShipmentApi|\PHPUnit\Framework\MockObject\MockObject $shipmentApiMock */
        $shipmentApiMock = $this->createMock(ShipmentApi::class);
        $shipmentApiMock->expects($this->any())
            ->method('getShipments')
            ->willReturn((new SeekableCollection())->setCollection(new MiraklCollection()));

        $orderSynchronizer = $this->getOrderSynchronizer($shipmentApiMock);

        $updated = $orderSynchronizer->synchronize($order, $miraklOrderMock, $this->getConnectionMock());

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

        /** @var ShipmentApi|\PHPUnit\Framework\MockObject\MockObject $shipmentApiMock */
        $shipmentApiMock = $this->createMock(ShipmentApi::class);
        $shipmentApiMock->expects($this->any())
            ->method('getShipments')
            ->willReturn((new SeekableCollection())->setCollection(new MiraklCollection()));

        $orderSynchronizer = $this->getOrderSynchronizer($shipmentApiMock);

        $updated = $orderSynchronizer->synchronize($order, $miraklOrderMock, $this->getConnectionMock());

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

        /** @var ShipmentApi|\PHPUnit\Framework\MockObject\MockObject $shipmentApiMock */
        $shipmentApiMock = $this->createMock(ShipmentApi::class);
        $shipmentApiMock->expects($this->any())
            ->method('getShipments')
            ->willReturn((new SeekableCollection())->setCollection(new MiraklCollection()));

        $orderSynchronizer = $this->getOrderSynchronizer($shipmentApiMock);

        $updated = $orderSynchronizer->synchronize($order, $miraklOrderMock, $this->getConnectionMock());

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

        /** @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject $apiResponseMock */
        $apiResponseMock = $this->createMock(ResponseInterface::class);

        $apiResponseMock->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(404);

        $apiResponseMock->expects($this->any())
            ->method('getBody')
            ->willReturn(\GuzzleHttp\Psr7\Utils::streamFor('{"status": 404}'));

        /** @var ShipmentApi|\PHPUnit\Framework\MockObject\MockObject $shipmentApiMock */
        $shipmentApiMock = $this->createMock(ShipmentApi::class);
        $shipmentApiMock->expects($this->any())
            ->method('getShipments')
            ->willThrowException(new ClientException(
                'An error occurred',
                $this->createMock(RequestInterface::class),
                $apiResponseMock
            ));

        $orderSynchronizer = $this->getOrderSynchronizer($shipmentApiMock);

        $updated = $orderSynchronizer->synchronize($order, $miraklOrderMock, $this->getConnectionMock());

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

        /** @var ShipmentApi|\PHPUnit\Framework\MockObject\MockObject $shipmentApiMock */
        $shipmentApiMock = $this->createMock(ShipmentApi::class);
        $shipmentApiMock->expects($this->any())
            ->method('getShipments')
            ->willReturn((new SeekableCollection())->setCollection(new MiraklCollection()));

        $orderSynchronizer = $this->getOrderSynchronizer($shipmentApiMock);

        $updated = $orderSynchronizer->synchronize($order, $miraklOrderMock, $this->getConnectionMock());

        $this->assertFalse($updated);
    }
}
