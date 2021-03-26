<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class ViewObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept view order from back office
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if (!$order = $this->getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        // Disable the 'Reorder' button on an imported Mirakl order
        $order->setActionFlag(Order::ACTION_FLAG_REORDER, false);

        $connection = $this->getConnectionById($order->getMiraklConnectionId());

        try {
            $miraklOrder = $this->getMiraklOrder($connection, $order->getMiraklOrderId());

            $this->messageManager->addNoticeMessage(__(
                'This is a Mirakl Marketplace order from the connection "%1".', $connection->getName()
            ));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while downloading the Mirakl order information: %1', $e->getMessage())
            );
        }

        try {
            $updated = $this->synchronizeOrder->synchronize($order, $miraklOrder, $connection);
            $miraklOrderUrl = $this->connectionHelper->getMiraklOrderUrl($connection, $miraklOrder);

            if ($updated) {
                $this->messageManager->addSuccessMessage(__(
                    'Your order <a href="%1" target="_blank">%2</a> has been synchronized with Mirakl.',
                    $miraklOrderUrl,
                    $miraklOrder->getId()
                ));
                $this->resetLastAddedMessageEscaping();
            } else {
                $this->messageManager->addNoticeMessage(__(
                    'Your order <a href="%1" target="_blank">%2</a> is up to date with Mirakl.',
                    $miraklOrderUrl,
                    $miraklOrder->getId()
                ));
                $this->resetLastAddedMessageEscaping();
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while synchronizing the Mirakl order: %1', $e->getMessage())
            );
        }
    }
}
