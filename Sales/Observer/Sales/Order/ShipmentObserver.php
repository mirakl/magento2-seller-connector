<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Mirakl\MMP\Common\Domain\Order\OrderState;

class ShipmentObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept order shipping from back office
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if (!$order = $this->getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var \Magento\Backend\App\Action $action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $action->getRequest();

        $shipmentQtys = $request->getParam('shipment');
        if (empty($shipmentQtys['items']) || !($qtyToShip = array_sum($shipmentQtys['items']))) {
            return;
        }

        $connection  = $this->getConnectionById($order->getMiraklConnectionId());
        $miraklOrder = $this->getMiraklOrder($connection, $order->getMiraklOrderId());

        try {
            // Synchronize Magento and Mirakl orders together
            $this->synchronizeOrder->synchronize($order, $miraklOrder);

            if ($qtyToShip < $this->getOrderQtyToShip($order)) {
                // Block partial shipping
                $this->fail(__('Partial shipping is not allowed on this Mirakl order.'), $action);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }
    }

    /**
     * Returns order total quantity to ship
     *
     * @param   Order  $order
     * @return  int
     */
    protected function getOrderQtyToShip($order)
    {
        $qtyToShip = 0;
        /** @var Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $qtyToShip += $item->getQtyToShip();
        }

        return $qtyToShip;
    }
}