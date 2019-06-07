<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Create;

use Magento\Sales\Model\Order;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Model\Create\Order as OrderCreator;

class OrderTest extends TestCase
{
    /**
     * @magentoDbIsolation enabled
     */
    public function testCreateOrder()
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

        $this->assertInstanceOf(Order::class, $order);

        $orderItems = $order->getItemsCollection();
        /** @var Order\Item $orderItem */
        $orderItem = $orderItems->getFirstItem();

        $this->assertEmpty($order->getDiscountAmount());
        $this->assertCount(1, $orderItems);
        $this->assertEquals(2, $orderItem->getQtyOrdered());
        $this->assertSame('MH02-XS-Red', $orderItem->getSku());
        $this->assertSame('mirakl', $order->getPayment()->getMethod());
        $this->assertNull($order->getCustomer());
        $this->assertNull($order->getCustomerId());
        $this->assertSame('test@do-not-use.com', $order->getCustomerEmail());
        $this->assertEquals(240.00, $order->getSubtotal());
        $this->assertEquals(15.00, $order->getShippingAmount());
        $this->assertEquals(15.00, $order->getShippingInclTax());
        $this->assertEquals(255.00, $order->getGrandTotal());

        $shippingAddress = $order->getShippingAddress();
        $this->assertSame('test@do-not-use.com', $shippingAddress->getEmail());
        $this->assertSame('Johann', $shippingAddress->getFirstname());
        $this->assertSame('Reinké', $shippingAddress->getLastname());
        $this->assertSame("45 rue de la Bienfaisance\nEtage 4", $shippingAddress->getData('street'));
        $this->assertSame('Paris', $shippingAddress->getCity());
        $this->assertSame('75008', $shippingAddress->getPostcode());
        $this->assertSame('0987654321', $shippingAddress->getTelephone());
        $this->assertSame('FR', $shippingAddress->getCountryId());
        $this->assertNull($shippingAddress->getCompany());

        $billingAddress = $order->getBillingAddress();
        $this->assertSame('test@do-not-use.com', $billingAddress->getEmail());
        $this->assertSame('Johann', $billingAddress->getFirstname());
        $this->assertSame('Reinké', $shippingAddress->getLastname());
        $this->assertSame('45 rue de la Bienfaisance', $billingAddress->getData('street'));
        $this->assertSame('Paris', $billingAddress->getCity());
        $this->assertSame('75008', $billingAddress->getPostcode());
        $this->assertSame('0619874662', $billingAddress->getTelephone());
        $this->assertSame('FR', $billingAddress->getCountryId());
        $this->assertNull($billingAddress->getCompany());
    }
}