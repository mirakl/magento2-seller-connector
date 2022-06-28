<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class EditObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept edit order from back office
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if (!$order = $this->getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        if (!$order->getMiraklSync()) {
            return; // We ignore orders not flagged mirakl_sync
        }

        /** @var \Magento\Backend\App\Action $action */
        $action = $observer->getEvent()->getControllerAction();

        try {
            $this->fail(__('It is not possible to edit this Mirakl order.'), $action);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }
    }
}