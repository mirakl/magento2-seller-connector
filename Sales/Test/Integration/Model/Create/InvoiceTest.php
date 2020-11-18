<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Create;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Model\Create\Invoice as InvoiceCreator;
use MiraklSeller\Sales\Model\Create\Order as OrderCreator;

class InvoiceTest extends TestCase
{
    /**
     * @magentoDbIsolation enabled
     */
    public function testCreateInvoice()
    {
        /** @var OrderCreator $orderCreator */
        $orderCreator = $this->objectManager->create(OrderCreator::class, [
            'customerEmail' => 'test@do-not-use.com',
        ]);

        /** @var ShopOrder $miraklOrderMock */
        $miraklOrderMock = $this->objectManager->create(ShopOrder::class, [
            'data' => $this->_getJsonFileContents('mirakl_order.json')
        ]);

        $order = $orderCreator->create($miraklOrderMock);

        /** @var InvoiceCreator $invoiceCreator */
        $invoiceCreator = $this->objectManager->create(InvoiceCreator::class);

        $invoice = $invoiceCreator->create($order);

        $this->assertInstanceOf(Invoice::class, $invoice);

        $invoiceItems = $invoice->getItemsCollection();
        /** @var Item $invoiceItem */
        $invoiceItem = $invoiceItems->getFirstItem();

        $this->assertCount(1, $invoiceItems);
        $this->assertEquals(2, $invoiceItem->getQty());
        $this->assertSame(Invoice::STATE_PAID, $invoice->getState());
        $this->assertEquals(255, $order->getTotalInvoiced());
        $this->assertSame($order->getId(), $invoice->getOrderId());
        $this->assertFalse($order->canInvoice());
    }
}