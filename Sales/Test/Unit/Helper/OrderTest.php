<?php
namespace MiraklSeller\Sales\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group sales
 * @group helper
 * @coversDefaultClass \MiraklSeller\Sales\Helper\Order
 */
class OrderTest extends TestCase
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        // Mock the config object to match default values
        $configMock = $this->getMockBuilder(\MiraklSeller\Sales\Helper\Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllowedStatusesForOrdersImport'])
            ->getMock();
        $configMock->expects($this->any())
            ->method('getAllowedStatusesForOrdersImport')
            ->willReturn([OrderState::SHIPPING]);

        $this->orderHelper = $objectManager->getObject(OrderHelper::class, ['config' => $configMock]);
    }

    /**
     * @covers  ::canImport
     * @param   string  $status
     * @param   bool    $expected
     * @dataProvider getTestCanImportDataProvider()
     */
    public function testCanImport($status, $expected)
    {
        $this->assertSame($expected, $this->orderHelper->canImport($status));
    }

    /**
     * @covers  ::isMiraklOrderShipped
     * @param   string  $state
     * @param   bool    $expected
     * @dataProvider getTestIsMiraklOrderShippedDataProvider
     */
    public function testIsMiraklOrderShipped($state, $expected)
    {
        $miraklOrder = new ShopOrder(['state' => $state]);
        $this->assertSame($expected, $this->orderHelper->isMiraklOrderShipped($miraklOrder));
    }

    /**
     * @return  array
     */
    public function getTestCanImportDataProvider()
    {
        return [
            [OrderState::STAGING, false],
            [OrderState::SHIPPING, true],
            [OrderState::SHIPPED, false],
            [OrderState::CLOSED, false],
            [OrderState::REFUNDED, false],
            [OrderState::TO_COLLECT, false],
            [OrderState::WAITING_ACCEPTANCE, false],
            [OrderState::WAITING_DEBIT_PAYMENT, false],
            [OrderState::CANCELED, false],
            [OrderState::RECEIVED, false],
        ];
    }

    /**
     * @return  array
     */
    public function getTestIsMiraklOrderShippedDataProvider()
    {
        return [
            [OrderState::RECEIVED, true],
            [OrderState::SHIPPED, true],
            [OrderState::SHIPPING, false],
            [OrderState::CLOSED, true],
            [OrderState::CANCELED, false],
            [OrderState::TO_COLLECT, true],
            [OrderState::STAGING, false],
            [OrderState::WAITING_ACCEPTANCE, false],
            [OrderState::REFUNDED, false],
            [OrderState::REFUSED, false],
            [OrderState::WAITING_DEBIT, false],
            [OrderState::WAITING_DEBIT_PAYMENT, false],
        ];
    }
}
