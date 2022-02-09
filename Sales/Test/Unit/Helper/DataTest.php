<?php
namespace MiraklSeller\Sales\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use MiraklSeller\Sales\Helper\Data as Helper;
use PHPUnit\Framework\TestCase;

/**
 * @group sales
 * @group helper
 * @coversDefaultClass \MiraklSeller\Sales\Helper\Data
 */
class DataTest extends TestCase
{
    /**
     * @var Helper
     */
    protected $helper;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->helper = $objectManager->getObject(Helper::class);
    }

    /**
     * @covers ::getOrderStatusList
     */
    public function testGetOrderStatusList()
    {
        $expected = [
            OrderState::STAGING               => 'Fraud Check Pending',
            OrderState::WAITING_ACCEPTANCE    => 'Pending Acceptance',
            OrderState::REFUSED               => 'Rejected',
            OrderState::WAITING_DEBIT         => 'Pending Debit',
            OrderState::WAITING_DEBIT_PAYMENT => 'Debit in Progress',
            OrderState::SHIPPING              => 'Shipping in Progress',
            OrderState::TO_COLLECT            => 'To Collect',
            OrderState::SHIPPED               => 'Shipped',
            OrderState::RECEIVED              => 'Received',
            OrderState::INCIDENT_OPEN         => 'Incident Open',
            OrderState::INCIDENT_CLOSED       => 'Incident Closed',
            OrderState::CLOSED                => 'Closed',
            OrderState::CANCELED              => 'Canceled',
            OrderState::REFUNDED              => 'Refunded',
        ];

        $list = $this->helper->getOrderStatusList();
        $this->assertSame($expected, $list);
    }

    /**
     * @covers ::getPaymentWorkflowList
     */
    public function testGetPaymentWorkflowList()
    {
        $expected = [
            'PAY_ON_ACCEPTANCE'                => 'Pay on acceptance',
            'PAY_ON_DELIVERY'                  => 'Pay on delivery',
            'PAY_ON_DUE_DATE'                  => 'Pay on due date',
            'PAY_ON_SHIPMENT'                  => 'Pay on shipment',
            'NO_CUSTOMER_PAYMENT_CONFIRMATION' => 'No payment confirmation',
        ];

        $list = $this->helper->getPaymentWorkflowList();
        $this->assertSame($expected, $list);
    }
}
