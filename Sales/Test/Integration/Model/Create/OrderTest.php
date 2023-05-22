<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Create;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as OrderTaxItemResource;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Model\Create\Order as OrderCreator;
use MiraklSeller\Sales\Model\Mapper\CountryNotFoundException;

class OrderTest extends TestCase
{
    /**
     * @magentoDbIsolation enabled
     */
    public function testCreateOrder()
    {
        /** @var OrderCreator $orderCreator */
        $orderCreator = $this->objectManager->create(OrderCreator::class);

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
        $this->assertSame('guest@do-not-use.com', $order->getCustomerEmail());
        $this->assertEquals(240.00, $order->getSubtotal());
        $this->assertEquals(15.00, $order->getShippingAmount());
        $this->assertEquals(15.00, $order->getShippingInclTax());
        $this->assertEquals(255.00, $order->getGrandTotal());

        $shippingAddress = $order->getShippingAddress();
        $this->assertSame('guest@do-not-use.com', $shippingAddress->getEmail());
        $this->assertSame('Johann', $shippingAddress->getFirstname());
        $this->assertSame('Reinké', $shippingAddress->getLastname());
        $this->assertSame("12 rue de Lübeck\nÉtage 4", $shippingAddress->getData('street'));
        $this->assertSame('Paris', $shippingAddress->getCity());
        $this->assertSame('75116', $shippingAddress->getPostcode());
        $this->assertSame('0987654321', $shippingAddress->getTelephone());
        $this->assertSame('FR', $shippingAddress->getCountryId());
        $this->assertSame('Mirakl', $shippingAddress->getCompany());
        $this->assertSame('', $shippingAddress->getRegion());

        $billingAddress = $order->getBillingAddress();
        $this->assertSame('guest@do-not-use.com', $billingAddress->getEmail());
        $this->assertSame('Johann', $billingAddress->getFirstname());
        $this->assertSame('Reinké', $billingAddress->getLastname());
        $this->assertSame('12 rue de Lübeck', $billingAddress->getData('street'));
        $this->assertSame('Paris', $billingAddress->getCity());
        $this->assertSame('75116', $billingAddress->getPostcode());
        $this->assertSame('0601020304', $billingAddress->getTelephone());
        $this->assertSame('FR', $billingAddress->getCountryId());
        $this->assertSame('', $billingAddress->getCompany());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testCreateOrderWithTaxes()
    {
        /** @var OrderCreator $orderCreator */
        $orderCreator = $this->objectManager->create(OrderCreator::class);

        /** @var ShopOrder $miraklOrderMock */
        $miraklOrderMock = $this->objectManager->create(ShopOrder::class, [
            'data' => $this->_getJsonFileContents('mirakl_order_with_taxes.json')
        ]);

        $order = $orderCreator->create($miraklOrderMock);

        $this->assertSame('mirakl', $order->getPayment()->getMethod());
        $this->assertNull($order->getCustomer());
        $this->assertNull($order->getCustomerId());
        $this->assertSame('guest@do-not-use.com', $order->getCustomerEmail());
        $this->assertEquals('1', $order->getCustomerIsGuest());
        $this->assertEquals('2', $order->getTotalItemCount());

        $this->assertEquals(6.68, $order->getTaxAmount());
        $this->assertEquals(6.68, $order->getBaseTaxAmount());
        $this->assertEquals(2.52, $order->getShippingTaxAmount());
        $this->assertEquals(2.52, $order->getBaseShippingTaxAmount());
        $this->assertEquals(84.64, $order->getSubtotal());
        $this->assertEquals(84.64, $order->getBaseSubtotal());
        $this->assertEquals(88.80, $order->getSubtotalInclTax());
        $this->assertEquals(9.48, $order->getShippingAmount());
        $this->assertEquals(9.48, $order->getBaseShippingAmount());
        $this->assertEquals(12.00, $order->getShippingInclTax());
        $this->assertEquals(12.00, $order->getBaseShippingInclTax());
        $this->assertEquals(100.80, $order->getGrandTotal());
        $this->assertEquals(100.80, $order->getBaseGrandTotal());

        $shippingAddress = $order->getShippingAddress();
        $this->assertSame('guest@do-not-use.com', $shippingAddress->getEmail());
        $this->assertSame('Veronica', $shippingAddress->getFirstname());
        $this->assertSame('Costello', $shippingAddress->getLastname());
        $this->assertSame("12 rue de Lübeck\nÉtage 2", $shippingAddress->getData('street'));
        $this->assertSame('Paris', $shippingAddress->getCity());
        $this->assertSame('75116', $shippingAddress->getPostcode());
        $this->assertSame('0987654321', $shippingAddress->getTelephone());
        $this->assertSame('FR', $shippingAddress->getCountryId());
        $this->assertSame('', $shippingAddress->getCompany());
        $this->assertSame('Paris', $shippingAddress->getRegion());

        $billingAddress = $order->getBillingAddress();
        $this->assertSame('guest@do-not-use.com', $billingAddress->getEmail());
        $this->assertSame('Veronica', $billingAddress->getFirstname());
        $this->assertSame('Costello', $billingAddress->getLastname());
        $this->assertSame('12 rue de Lübeck', $billingAddress->getData('street'));
        $this->assertSame('Paris', $billingAddress->getCity());
        $this->assertSame('75116', $billingAddress->getPostcode());
        $this->assertSame('0601020304', $billingAddress->getTelephone());
        $this->assertSame('FR', $billingAddress->getCountryId());
        $this->assertSame('Mirakl', $billingAddress->getCompany());
        $this->assertSame('Paris', $billingAddress->getRegion());

        $orderItems = $order->getItemsCollection();

        $this->assertCount(2, $orderItems);

        /** @var Order\Item $orderItem1 */
        $orderItem1 = $orderItems->getFirstItem();
        $this->assertEquals(2, $orderItem1->getQtyOrdered());
        $this->assertSame('24-MB01', $orderItem1->getSku());
        $this->assertEquals(7.82, $orderItem1->getPrice());
        $this->assertEquals(7.82, $orderItem1->getBasePrice());
        $this->assertEquals(7.82, $orderItem1->getOriginalPrice());
        $this->assertEquals(27.0, $orderItem1->getTaxPercent());
        $this->assertEquals(4.16, $orderItem1->getTaxAmount());
        $this->assertEquals(4.16, $orderItem1->getBaseTaxAmount());
        $this->assertEquals(15.64, $orderItem1->getRowTotal());
        $this->assertEquals(15.64, $orderItem1->getBaseRowTotal());
        $this->assertEquals(9.90, $orderItem1->getPriceInclTax());
        $this->assertEquals(9.90, $orderItem1->getBasePriceInclTax());
        $this->assertEquals(19.80, $orderItem1->getRowTotalInclTax());
        $this->assertEquals(19.80, $orderItem1->getBaseRowTotalInclTax());

        /** @var Order\Item $orderItem2 */
        $orderItem2 = $orderItems->getLastItem();
        $this->assertEquals(1, $orderItem2->getQtyOrdered());
        $this->assertSame('24-WG09', $orderItem2->getSku());
        $this->assertEquals(69.00, $orderItem2->getPrice());
        $this->assertEquals(69.00, $orderItem2->getBasePrice());
        $this->assertEquals(69.00, $orderItem2->getOriginalPrice());
        $this->assertEmpty((float) $orderItem2->getTaxPercent());
        $this->assertEmpty((float) $orderItem2->getTaxAmount());
        $this->assertEmpty((float) $orderItem2->getBaseTaxAmount());
        $this->assertEquals(69.00, $orderItem2->getRowTotal());
        $this->assertEquals(69.00, $orderItem2->getBaseRowTotal());
        $this->assertEquals(69.00, $orderItem2->getPriceInclTax());
        $this->assertEquals(69.00, $orderItem2->getBasePriceInclTax());
        $this->assertEquals(69.00, $orderItem2->getRowTotalInclTax());
        $this->assertEquals(69.00, $orderItem2->getBaseRowTotalInclTax());

        /** @var OrderTaxItemResource $orderTaxItemResource */
        $orderTaxItemResource = $this->objectManager->create(OrderTaxItemResource::class);

        $orderTaxItems = $orderTaxItemResource->getTaxItemsByOrderId($order->getId());

        $this->assertCount(6, $orderTaxItems);

        $orderTaxItem1 = $orderTaxItems[0];
        $this->assertSame('shipping', $orderTaxItem1['taxable_item_type']);
        $this->assertSame(0.52, (float) $orderTaxItem1['real_amount']);
        $this->assertSame(0.52, (float) $orderTaxItem1['real_base_amount']);
        $this->assertSame('TVA5.5', $orderTaxItem1['code']);
        $this->assertSame('TVA5.5', $orderTaxItem1['title']);

        $orderTaxItem2 = $orderTaxItems[1];
        $this->assertSame('shipping', $orderTaxItem2['taxable_item_type']);
        $this->assertSame(0.10, (float) $orderTaxItem2['real_amount']);
        $this->assertSame(0.10, (float) $orderTaxItem2['real_base_amount']);
        $this->assertSame('TVA5.5', $orderTaxItem2['code']);
        $this->assertSame('TVA5.5', $orderTaxItem2['title']);

        $orderTaxItem3 = $orderTaxItems[2];
        $this->assertSame('product', $orderTaxItem3['taxable_item_type']);
        $this->assertSame(1.03, (float) $orderTaxItem3['real_amount']);
        $this->assertSame(1.03, (float) $orderTaxItem3['real_base_amount']);
        $this->assertSame('TVA5.5', $orderTaxItem3['code']);
        $this->assertSame('TVA5.5', $orderTaxItem3['title']);

        $orderTaxItem4 = $orderTaxItems[3];
        $this->assertSame('shipping', $orderTaxItem4['taxable_item_type']);
        $this->assertSame(1.58, (float) $orderTaxItem4['real_amount']);
        $this->assertSame(1.58, (float) $orderTaxItem4['real_base_amount']);
        $this->assertSame('TVA20', $orderTaxItem4['code']);
        $this->assertSame('TVA20', $orderTaxItem4['title']);

        $orderTaxItem5 = $orderTaxItems[4];
        $this->assertSame('shipping', $orderTaxItem5['taxable_item_type']);
        $this->assertSame(0.32, (float) $orderTaxItem5['real_amount']);
        $this->assertSame(0.32, (float) $orderTaxItem5['real_base_amount']);
        $this->assertSame('TVA20', $orderTaxItem5['code']);
        $this->assertSame('TVA20', $orderTaxItem5['title']);

        $orderTaxItem6 = $orderTaxItems[5];
        $this->assertSame('product', $orderTaxItem6['taxable_item_type']);
        $this->assertSame(3.13, (float) $orderTaxItem6['real_amount']);
        $this->assertSame(3.13, (float) $orderTaxItem6['real_base_amount']);
        $this->assertSame('TVA20', $orderTaxItem6['code']);
        $this->assertSame('TVA20', $orderTaxItem6['title']);
    }

    public function testCreateOrderWithCountryNotFound()
    {
        $this->expectException(CountryNotFoundException::class);
        $this->expectExceptionMessage('Could not map country for label "France Métropolitaine"');

        /** @var OrderCreator $orderCreator */
        $orderCreator = $this->objectManager->create(OrderCreator::class);

        /** @var ShopOrder $miraklOrderMock */
        $miraklOrderMock = $this->objectManager->create(ShopOrder::class, [
            'data' => $this->_getJsonFileContents('mirakl_order_with_unknown_country.json')
        ]);

        $orderCreator->create($miraklOrderMock);
    }
}
