<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CreditMemoObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept order refund from back office
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

        $creditmemoQtys = $request->getParam('creditmemo');
        if (empty($creditmemoQtys['items'])) {
            return;
        }

        $connection = $this->getConnectionById($order->getMiraklConnectionId());

        try {
            $this->fail(__(
                'Refund is not possible on a Mirakl order from Magento. ' .
                'You can go to your <a href="%1" target="_blank">Mirakl back office</a> to handle it.',
                $connection->getBaseUrl()
            ), $action);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
            $this->resetLastAddedMessageEscaping();
        }
    }
}