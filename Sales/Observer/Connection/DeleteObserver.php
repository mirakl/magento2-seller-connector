<?php
namespace MiraklSeller\Sales\Observer\Connection;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

class DeleteObserver implements ObserverInterface
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param OrderHelper $orderHelper
     */
    public function __construct(OrderHelper $orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \MiraklSeller\Api\Model\Connection $connection */
        $connection = $observer->getEvent()->getConnection();
        $orders = $this->orderHelper->getMagentoOrdersByConnection($connection);

        if ($orders->count()) {
            // Do not allow connection deletion if any order have been imported from it
            throw new \Exception(__('This connection cannot be deleted, some Magento orders are linked to it.'));
        }
    }
}