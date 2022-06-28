<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CancelObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept order cancelation to cancel the order in Mirakl if possible.
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$this->isImportedMiraklOrder($order)) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        if (!$order->getMiraklSync()) {
            return; // We ignore orders not flagged mirakl_sync
        }

        $connection    = $this->getConnectionById($order->getMiraklConnectionId());
        $miraklOrderId = $order->getMiraklOrderId();
        $miraklOrder   = $this->getMiraklOrder($connection, $miraklOrderId);

        if ($miraklOrder->getPaymentWorkflow() != 'PAY_ON_DELIVERY') {
            return; // Do not do anything for payment workflow different than PAY_ON_DELIVERY
        }

        try {
            // Synchronize Magento and Mirakl orders together
            $this->synchronizeOrder->synchronize($order, $miraklOrder, $connection);

            // Block order cancelation if not possible
            if (!$miraklOrder->getData('can_cancel')) {
                $this->fail(__('This order cannot be canceled.'), $observer->getEvent()->getControllerAction());
            }

            // Cancel the Mirakl order just before canceling the Magento order
            $this->apiOrder->cancelOrder($connection, $miraklOrderId);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }
    }
}
