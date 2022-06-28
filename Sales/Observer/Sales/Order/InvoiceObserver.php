<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class InvoiceObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept order invoicing from back office to avoid partial invoicing and to cancel the order on Mirakl if needed.
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

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $action->getRequest();

        $invoiceQtys = $request->getParam('invoice');
        if (empty($invoiceQtys['items'])) {
            return;
        }

        try {
            if (array_sum($invoiceQtys['items']) < $order->getTotalQtyOrdered()) {
                $this->fail(__('Partial invoicing is not allowed on this Mirakl order.'), $action);
            }

            $connection  = $this->getConnectionById($order->getMiraklConnectionId());
            $miraklOrder = $this->getMiraklOrder($connection, $order->getMiraklOrderId());

            if ($miraklOrder->getPaymentWorkflow() != 'PAY_ON_DELIVERY') {
                return; // Do not do anything for payment workflow different than PAY_ON_DELIVERY
            }

            // Synchronize Magento and Mirakl orders together
            $this->synchronizeOrder->synchronize($order, $miraklOrder, $connection);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }
    }
}
