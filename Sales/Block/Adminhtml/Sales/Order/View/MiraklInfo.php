<?php
namespace MiraklSeller\Sales\Block\Adminhtml\Sales\Order\View;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Core\Helper\Connection as ConnectionHelper;
use MiraklSeller\Sales\Helper\Data as SalesHelper;

class MiraklInfo extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @var SalesHelper
     */
    protected $salesHelper;

    /**
     * @var ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $_template = 'sales/order/view/mirakl.phtml';

    /**
     * @param   Template\Context            $context
     * @param   Registry                    $registry
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   SalesHelper                 $salesHelper
     * @param   ConnectionHelper            $connectionHelper
     * @param   TimezoneInterface           $timezone
     * @param   array                       $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        SalesHelper $salesHelper,
        ConnectionHelper $connectionHelper,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry                  = $registry;
        $this->salesHelper               = $salesHelper;
        $this->connectionFactory         = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
        $this->connectionHelper          = $connectionHelper;
        $this->timezone                  = $timezone;
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        if (null === $this->connection) {
            $connectionId = $this->getMagentoOrder()->getMiraklConnectionId();
            $this->connection = $this->connectionFactory->create();
            $this->connectionResourceFactory->create()->load($this->connection, $connectionId);
        }

        return $this->connection;
    }

    /**
     * @return  Order
     */
    public function getMagentoOrder()
    {
        return $this->registry->registry('sales_order');
    }

    /**
     * @return  ShopOrder
     */
    public function getMiraklOrder()
    {
        return $this->registry->registry('mirakl_order');
    }

    /**
     * @return  string
     */
    public function getMiraklOrderCustomerName()
    {
        $customer = $this->getMiraklOrder()->getCustomer();

        return $customer->getFirstname() . ' ' . $customer->getLastname();
    }

    /**
     * @return  string
     */
    public function getMiraklOrderStatus()
    {
        return $this->salesHelper->getOrderStatusLabel($this->getMiraklOrder());
    }

    /**
     * @return  string
     */
    public function getViewMiraklOrderUrl()
    {
        return $this->connectionHelper->getMiraklOrderUrl($this->getConnection(), $this->getMiraklOrder());
    }

    /**
     * @return  string
     */
    public function getViewMiraklOrderInMagentoUrl()
    {
        return $this->getUrl('mirakl_seller/order/view', [
            'order_id'      => $this->getMiraklOrder()->getId(),
            'connection_id' => $this->getConnection()->getId(),
        ]);
    }

    /**
     * Returns true if a refund has been issued on the given Mirakl order (even if partial), false otherwise.
     *
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function isMiraklOrderRefunded(ShopOrder $miraklOrder)
    {
        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            if ($orderLine->getRefunds()->count()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getOrderFulfillmentCenter()
    {
        $magentoOrder = $this->getMagentoOrder();
        if ($magentoOrder->getMiraklFulfillmentCenter() !== 'DEFAULT') {
            return $magentoOrder->getMiraklFulfillmentCenter();
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isMiraklSync()
    {
        return $this->getMagentoOrder()->getMiraklSync();
    }

    /**
     * @return string|null
     */
    public function getShippingDeadline()
    {
        $miraklOrder = $this->getMiraklOrder();
        $shippingDeadline = $miraklOrder->getShippingDeadline();
        $adminTimezone = $this->getAdminTimezone();

        return $shippingDeadline
            ? $this->timezone->formatDate($this->timezone->date($shippingDeadline, $adminTimezone), \IntlDateFormatter::MEDIUM, true)
            : '';
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getExpectedDeliveryDate()
    {
        $deliveryDate = $this->getMiraklOrder()->getDeliveryDate();
        $earliestDeliveryDate = $deliveryDate ? $deliveryDate->getEarliest() : false;
        $latestDeliveryDate = $deliveryDate ? $deliveryDate->getLatest() : false;

        $adminTimezone = $this->getAdminTimezone();

        $earliestDeliveryDate = $earliestDeliveryDate
            ? $this->timezone->formatDate($this->timezone->date($earliestDeliveryDate, $adminTimezone), \IntlDateFormatter::MEDIUM)
            : false;

        $latestDeliveryDate = $latestDeliveryDate
            ? $this->timezone->formatDate($this->timezone->date($latestDeliveryDate, $adminTimezone), \IntlDateFormatter::MEDIUM)
            : false;

        if (!$earliestDeliveryDate && !$latestDeliveryDate) {
            return '';
        }

        if ($earliestDeliveryDate && !$latestDeliveryDate) {
            return __('After %1', $earliestDeliveryDate);
        }

        if (!$earliestDeliveryDate && $latestDeliveryDate) {
            return __('Before %1', $latestDeliveryDate);
        }

        return __('%1 to %2', $earliestDeliveryDate, $latestDeliveryDate);
    }

    /**
     * Get timezone for admin user
     *
     * @return string
     */
    public function getAdminTimezone()
    {
        return $this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, 'admin');
    }

    /**
     * @return string
     */
    public function getMiraklUnsyncUrl()
    {
        return $this->getUrl(
            'mirakl_seller/order/unsync',
            [
                'order_id' => $this->getMagentoOrder()->getId(),
            ]
        );
    }

    /**
     * @return  string
     */
    protected function _toHtml()
    {
        if (!$this->getMagentoOrder()->getMiraklConnectionId()) {
            return ''; // No Mirakl info for non-Mirakl imported orders
        }

        return parent::_toHtml();
    }
}