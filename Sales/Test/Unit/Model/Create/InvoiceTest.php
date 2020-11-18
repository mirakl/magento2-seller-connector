<?php
namespace MiraklSeller\Sales\Test\Unit\Model\Create;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use MiraklSeller\Sales\Model\Create\Invoice as InvoiceCreator;
use PHPUnit\Framework\TestCase;

/**
 * @group sales
 * @group model
 * @coversDefaultClass \MiraklSeller\Sales\Model\Create\Invoice
 */
class InvoiceTest extends TestCase
{
    /**
     * @var InvoiceCreator
     */
    protected $invoiceCreator;

    protected function setUp(): void
    {
        $this->invoiceCreator = (new ObjectManager($this))->getObject(InvoiceCreator::class);
    }

    /**
     * @covers  ::create
     */
    public function testCreateInvoiceThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot do invoice for the order.');

        /** @var Order|\PHPUnit\Framework\MockObject\MockObject $order */
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order->expects($this->once())
            ->method('canInvoice')
            ->willReturn(false);

        $this->invoiceCreator->create($order);
    }
}
