<?php
namespace MiraklSeller\Sales\Block\Adminhtml\MiraklOrder\Grid;

use Magento\Backend\Block\Template;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Collection\Order\ShopOrderCollection;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;

class Filters extends Template
{
    /**
     * @var string
     */
    protected $_template = 'MiraklSeller_Sales::mirakl_order/grid/filters.phtml';

    /**
     * @var ApiOrder
     */
    protected $apiOrder;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @var ShopOrderCollection
     */
    protected $pendingOrders;

    /**
     * @var ShopOrderCollection
     */
    protected $incidentOrders;

    /**
     * @param   Template\Context    $context
     * @param   ApiOrder            $apiOrder
     * @param   ConnectionLoader    $connectionLoader
     * @param   array               $data
     */
    public function __construct(
        Template\Context $context,
        ApiOrder $apiOrder,
        ConnectionLoader $connectionLoader,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->connectionLoader = $connectionLoader;
        $this->apiOrder         = $apiOrder;
    }

    /**
     * @return  \MiraklSeller\Api\Model\ResourceModel\Connection\Collection
     */
    public function getConnections()
    {
        return $this->connectionLoader->getConnections();
    }

    /**
     * @return  ShopOrderCollection
     */
    public function getOrdersWithIncident()
    {
        if (null === $this->incidentOrders) {
            $params = ['has_incident' => true];
            $this->incidentOrders = $this->apiOrder->getOrders($this->connectionLoader->getCurrentConnection(), $params);
        }

        return $this->incidentOrders;
    }

    /**
     * @return  int
     */
    public function getOrdersWithIncidentCount()
    {
        try {
            return $this->getOrdersWithIncident()->getTotalCount();
        } catch (\Exception $e) {
            /** @var \Magento\Framework\View\Element\Messages $messagesBlock */
            $messagesBlock = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class);
            $messagesBlock->addError(__('An error occurred: %1', $e->getMessage()));
            $this->setChild('messages', $messagesBlock);

            return 0;
        }
    }

    /**
     * @return  ShopOrderCollection
     */
    public function getPendingOrders()
    {
        if (null === $this->pendingOrders) {
            $params = ['order_states' => [OrderState::WAITING_ACCEPTANCE]];
            $this->pendingOrders = $this->apiOrder->getOrders($this->connectionLoader->getCurrentConnection(), $params);
        }

        return $this->pendingOrders;
    }

    /**
     * @return  int
     */
    public function getPendingOrdersCount()
    {
        try {
            return $this->getPendingOrders()->getTotalCount();
        } catch (\Exception $e) {
            /** @var \Magento\Framework\View\Element\Messages $messagesBlock */
            $messagesBlock = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class);
            $messagesBlock->addError(__('An error occurred: %1', $e->getMessage()));
            $this->setChild('messages', $messagesBlock);

            return 0;
        }
    }
}