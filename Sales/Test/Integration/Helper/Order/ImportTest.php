<?php
namespace MiraklSeller\Sales\Test\Integration\Helper\Order;

use Magento\Sales\Model\Order;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Helper\Order\Import as OrderImportHelper;

class ImportTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The Mirakl order #foo cannot be imported
     */
    public function testImportMiraklOrderCannotBeImported()
    {
        $connection = $this->objectManager->create(Connection::class);

        $miraklOrder = new ShopOrder([
            'id'    => 'foo',
            'state' => OrderState::STAGING,
        ]);

        /** @var OrderImportHelper $orderImportHelper */
        $orderImportHelper = $this->objectManager->create(OrderImportHelper::class);
        $orderImportHelper->importMiraklOrder($connection, $miraklOrder);
    }

    /**
     * @expectedException \Magento\Framework\Exception\AlreadyExistsException
     * @expectedExceptionMessage The Mirakl order #foo has already been imported (#bar)
     */
    public function testImportMiraklOrderWithExistingOrder()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn('bar');

        $orderHelperMock = $this->getMockBuilder(OrderHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderHelperMock->expects($this->once())
            ->method('canImport')
            ->willReturn(true);
        $orderHelperMock->expects($this->once())
            ->method('getOrderByMiraklOrderId')
            ->willReturn($orderMock);

        /** @var OrderImportHelper $orderImportHelper */
        $orderImportHelper = $this->objectManager->create(OrderImportHelper::class, [
            'orderHelper' => $orderHelperMock,
        ]);

        $connection = $this->objectManager->create(Connection::class);

        $miraklOrder = new ShopOrder([
            'id'    => 'foo',
            'state' => OrderState::SHIPPED,
        ]);

        $orderImportHelper->importMiraklOrder($connection, $miraklOrder);
    }
}