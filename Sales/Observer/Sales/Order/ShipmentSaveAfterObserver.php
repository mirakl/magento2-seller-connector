<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\MMP\Common\Domain\Order\OrderState;

class ShipmentSaveAfterObserver extends ShipmentObserver implements ObserverInterface
{
    /**
     * Intercept order shipping manual or automatic save
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        try {
            $order = $shipment->getOrder();
            if (!$this->isImportedMiraklOrder($order)) {
                return; // Not a Mirakl order, leave
            }
        } catch (NoSuchEntityException $e) {
            return; // Problem retrieving the associated order, abort
        }

        if ($this->getOrderQtyToShip($order) > 0) {
            return; // Order is not totally shipped, abort
        }

        $connection  = $this->getConnectionById($order->getMiraklConnectionId());
        $miraklOrder = $this->getMiraklOrder($connection, $order->getMiraklOrderId());

        try {
            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            foreach ($shipment->getAllTracks() as $track) {
                // Send order tracking info to Mirakl
                $this->apiOrder->updateOrderTrackingInfo(
                    $connection,
                    $miraklOrder->getId(),
                    '', // Carrier code may not be present in Mirakl and is not mandatory
                    $track->getTitle(),
                    $track->getTrackNumber()
                );
                break; // Stop after the first, Mirakl handles only one tracking
            }

            // Confirm shipment of the order in Mirakl
            if ($miraklOrder->getStatus()->getState() == OrderState::SHIPPING) {
                $this->apiOrder->shipOrder($connection, $miraklOrder->getId());
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }
    }
}