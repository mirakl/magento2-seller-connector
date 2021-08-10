<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Create;

use Magento\Sales\Model\Order\Shipment;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Model\Create\Order as OrderCreator;
use MiraklSeller\Sales\Model\Create\Shipment as ShipmentCreator;

class ShipmentTest extends TestCase
{
    /**
     * @magentoDbIsolation enabled
     */
    public function testCreateShipment()
    {
        /** @var Connection $connectionMock */
        $connectionMock = $this->createMock(Connection::class);

        /** @var OrderCreator $orderCreator */
        $orderCreator = $this->objectManager->create(OrderCreator::class);

        /** @var ShopOrder $miraklOrderMock */
        $miraklOrderMock = $this->objectManager->create(ShopOrder::class, [
            'data' => $this->_getJsonFileContents('mirakl_order.json')
        ]);

        $order = $orderCreator->create($miraklOrderMock);

        /** @var ShipmentCreator $shipmentCreator */
        $shipmentCreator = $this->objectManager->create(ShipmentCreator::class);

        $shipment = $shipmentCreator->create($order, $miraklOrderMock, $connectionMock);

        $this->assertInstanceOf(Shipment::class, $shipment);

        $shipmentItems = $shipment->getItemsCollection();
        /** @var \Magento\Sales\Api\Data\ShipmentItemInterface $shipmentItem */
        $shipmentItem = reset($shipmentItems);

        $this->assertCount(1, $shipmentItems);
        $this->assertEquals(2, $shipmentItem->getQty());
        $this->assertEquals(2, $shipment->getTotalQty());
        $this->assertSame($order->getId(), $shipment->getOrderId());
        $this->assertFalse($order->canShip());
    }
}
