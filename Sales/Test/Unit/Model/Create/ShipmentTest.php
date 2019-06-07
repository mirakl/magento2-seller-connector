<?php
namespace MiraklSeller\Sales\Test\Unit\Model\Create;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Sales\Model\Create\Shipment as ShipmentCreator;
use PHPUnit\Framework\TestCase;

/**
 * @group sales
 * @group model
 * @coversDefaultClass \MiraklSeller\Sales\Model\Create\Shipment
 */
class ShipmentTest extends TestCase
{
    /**
     * @var ShipmentCreator
     */
    protected $shipmentCreator;

    protected function setUp()
    {
        $this->shipmentCreator = (new ObjectManager($this))->getObject(ShipmentCreator::class);
    }

    /**
     * @covers  ::create
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot do shipment for the order.
     */
    public function testCreateShipmentThrowsException()
    {
        /** @var Order|\PHPUnit_Framework_MockObject_MockObject $order */
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order->expects($this->once())
            ->method('canShip')
            ->willReturn(false);

        /** @var ShopOrder|\PHPUnit_Framework_MockObject_MockObject $miraklOrder */
        $miraklOrder = $this->getMockBuilder(ShopOrder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentCreator->create($order, $miraklOrder);
    }
}