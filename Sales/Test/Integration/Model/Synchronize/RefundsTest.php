<?php
namespace MiraklSeller\Sales\Test\Integration\Model\Synchronize;

use Magento\Sales\Model\Order\Creditmemo\Item as CreditMemoItem;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Helper\Order\Import as OrderImportHelper;
use MiraklSeller\Sales\Model\Synchronize\Refunds as RefundsSynchronizer;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;

class RefundsTest extends TestCase
{
    /**
     * @var OrderImportHelper
     */
    protected $orderImportHelper;

    /**
     * @var RefundsSynchronizer
     */
    protected $refundsSynchronizer;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    protected function setUp()
    {
        parent::setUp();
        $this->orderImportHelper = $this->objectManager->create(OrderImportHelper::class);
        $this->refundsSynchronizer = $this->objectManager->create(RefundsSynchronizer::class);
        $this->connectionLoader = $this->objectManager->create(ConnectionLoader::class);
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_invoice 1
     * @magentoConfigFixture default/mirakl_seller_sales/order/auto_create_shipment 0
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Sales/Test/Integration/Model/Synchronize/_fixtures/connection.php
     */
    public function testSynchronize()
    {
        $miraklOrdersData = $this->_getJsonFileContents('mirakl_order_with_refunds.json');
        $miraklOrder = ShopOrder::create($miraklOrdersData['orders'][0]);

        $connection = $this->connectionLoader->getCurrentConnection();

        $magentoOrder = $this->orderImportHelper->importMiraklOrder($connection, $miraklOrder);

        $this->assertTrue($magentoOrder->canCreditmemo());

        $updated = $this->refundsSynchronizer->synchronize($magentoOrder, $miraklOrder);

        $this->assertTrue($updated);

        $creditMemos = $magentoOrder->getCreditmemosCollection()->getItems();
        $this->assertCount(3, $creditMemos);

        /** @var CreditMemoItem $creditMemo1 */
        $creditMemo1 = current($creditMemos);
        $this->assertEquals(1120, $creditMemo1->getMiraklRefundId());
        $this->assertEquals(2.81, $creditMemo1->getSubtotal());
        $this->assertEquals(2.81, $creditMemo1->getSubtotalInclTax());
        $this->assertEquals(2.81, $creditMemo1->getGrandTotal());
        $this->assertEquals(0, $creditMemo1->getTaxAmount());
        $this->assertEquals(0, $creditMemo1->getShippingAmount());
        $this->assertEquals(0, $creditMemo1->getShippingTaxAmount());
        $this->assertEquals(0, $creditMemo1->getShippingInclTax());

        /** @var CreditMemoItem $creditMemo1Item1 */
        $creditMemo1Item1 = $creditMemo1->getItemsCollection()->getIterator()->current();
        $this->assertEquals('24-MB01', $creditMemo1Item1->getSku());
        $this->assertEquals('Joust Duffle Bag', $creditMemo1Item1->getName());
        $this->assertEquals(1, $creditMemo1Item1->getQty());
        $this->assertEquals(2.81, $creditMemo1Item1->getPrice());
        $this->assertEquals(2.81, $creditMemo1Item1->getPriceInclTax());
        $this->assertEquals(2.81, $creditMemo1Item1->getRowTotal());
        $this->assertEquals(2.81, $creditMemo1Item1->getRowTotalInclTax());
        $this->assertEquals(0, $creditMemo1Item1->getTaxAmount());

        /** @var CreditMemoItem $creditMemo2 */
        $creditMemo2 = next($creditMemos);
        $this->assertEquals(1121, $creditMemo2->getMiraklRefundId());
        $this->assertEquals(20, $creditMemo2->getSubtotal());
        $this->assertEquals(30, $creditMemo2->getSubtotalInclTax());
        $this->assertEquals(30.37, $creditMemo2->getGrandTotal());
        $this->assertEquals(10.17, $creditMemo2->getTaxAmount());
        $this->assertEquals(0.20, $creditMemo2->getShippingAmount());
        $this->assertEquals(0.17, $creditMemo2->getShippingTaxAmount());
        $this->assertEquals(0.37, $creditMemo2->getShippingInclTax());

        /** @var \ArrayIterator $creditMemo2ItemsIterator */
        $creditMemo2ItemsIterator = $creditMemo2->getItemsCollection()->getIterator();
        /** @var CreditMemoItem $creditMemo2Item1 */
        $creditMemo2Item1 = $creditMemo2ItemsIterator->current();
        $this->assertEquals('24-MB01', $creditMemo2Item1->getSku());
        $this->assertEquals('Joust Duffle Bag', $creditMemo2Item1->getName());
        $this->assertEquals(0, $creditMemo2Item1->getQty());
        $this->assertEquals(2.81, $creditMemo2Item1->getPrice());
        $this->assertEquals(2.81, $creditMemo2Item1->getPriceInclTax());
        $this->assertEquals(0, $creditMemo2Item1->getRowTotal());
        $this->assertEquals(0, $creditMemo2Item1->getRowTotalInclTax());
        $this->assertEquals(0, $creditMemo2Item1->getTaxAmount());

        /** @var CreditMemoItem $creditMemo2Item2 */
        $creditMemo2ItemsIterator->next();
        $creditMemo2Item2 = $creditMemo2ItemsIterator->current();
        $this->assertEquals('MH01-XL-Orange', $creditMemo2Item2->getSku());
        $this->assertEquals('Chaz Kangeroo Hoodie-XL-Orange', $creditMemo2Item2->getName());
        $this->assertEquals(1, $creditMemo2Item2->getQty());
        $this->assertEquals(20, $creditMemo2Item2->getPrice());
        $this->assertEquals(30, $creditMemo2Item2->getPriceInclTax());
        $this->assertEquals(20, $creditMemo2Item2->getRowTotal());
        $this->assertEquals(30, $creditMemo2Item2->getRowTotalInclTax());
        $this->assertEquals(10, $creditMemo2Item2->getTaxAmount());

        /** @var CreditMemoItem $creditMemo3 */
        $creditMemo3 = next($creditMemos);
        $this->assertEquals(1122, $creditMemo3->getMiraklRefundId());
        $this->assertEquals(0, $creditMemo3->getSubtotal());
        $this->assertEquals(0, $creditMemo3->getSubtotalInclTax());
        $this->assertEquals(0.06, $creditMemo3->getGrandTotal());
        $this->assertEquals(0.05, $creditMemo3->getTaxAmount());
        $this->assertEquals(0.01, $creditMemo3->getShippingAmount());
        $this->assertEquals(0.05, $creditMemo3->getShippingTaxAmount());
        $this->assertEquals(0.06, $creditMemo3->getShippingInclTax());
        $this->assertEquals(2, $creditMemo3->getItemsCollection()->count());

        $updated = $this->refundsSynchronizer->synchronize($magentoOrder, $miraklOrder);

        $this->assertFalse($updated);
    }
}