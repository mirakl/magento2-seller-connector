<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\MMP\Common\Domain\Collection\Shipment\UpdateShipmentTrackingCollection;
use Mirakl\MMP\Common\Domain\Shipment\UpdateShipmentTracking;

class ShipmentSaveTrackBeforeObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept order shipping track before it is saved
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment\Track $shipmentTracking */
        $shipmentTracking = $observer->getEvent()->getTrack();

        if ($shipmentTracking->getFromMirakl()) {
            return; // Abort if creation comes from Mirakl synchronization
        }

        try {
            $order = $shipmentTracking->getShipment()->getOrder();
            if (!$this->isImportedMiraklOrder($order)) {
                return; // Not a Mirakl order, leave
            }

            $connection = $this->getConnectionById($order->getMiraklConnectionId());

            // Retrieve associated Magento shipment
            $shipment = $shipmentTracking->getShipment();

            if (!$shipment->getMiraklShipmentId()) {
                $miraklOrder = $this->getMiraklOrder($connection, $order->getMiraklOrderId());
                $miraklShipping = $miraklOrder->getShipping();

                if (!empty($miraklShipping->getCarrierCode()) || !empty($miraklShipping->getCarrier())) {
                    return; // Mirakl shipment already has an associated tracking
                }

                try {
                    // Send order tracking info to Mirakl
                    $this->apiOrder->updateOrderTrackingInfo(
                        $connection,
                        $order->getMiraklOrderId(),
                        $this->getMiraklCarrierCode($connection, $shipmentTracking),
                        $shipmentTracking->getTitle(),
                        $shipmentTracking->getTrackNumber()
                    );

                    return;
                } catch (\Exception $e) {
                    throw new LocalizedException(__('An error occurred: %1', $e->getMessage()));
                }
            }
        } catch (NoSuchEntityException $e) {
            return; // Problem retrieving the associated order, abort
        }

        $miraklShipment = $this->getMiraklShipment($connection, $order->getMiraklOrderId(), $shipment->getMiraklShipmentId());

        if (!empty($miraklShipment->getTracking()->getData())) {
            return; // Mirakl shipment already has an associated tracking
        }

        $updateShipmentTracking = new UpdateShipmentTracking([
            'id' => $miraklShipment->getId(),
            'tracking' => [
                'carrier_name' => $shipmentTracking->getTitle(),
                'tracking_number' => $shipmentTracking->getTrackNumber(),
            ],
        ]);
        $updateShipmentTrackings = new UpdateShipmentTrackingCollection();
        $updateShipmentTrackings->add($updateShipmentTracking);

        try {
            // Create the shipment tracking in Mirakl (API ST23)
            $createdTrackings = $this->apiShipment->updateShipmentTrackings($connection, $updateShipmentTrackings);

            if (!empty($createdTrackings->getShipmentErrors())) {
                $error = $createdTrackings->getShipmentErrors()->first();
                if ($error) {
                    throw new LocalizedException(__('An error occurred: %1', $error->getMessage()));
                }
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            try {
                $result = \Mirakl\parse_json_response($e->getResponse());
                throw new LocalizedException(__('An error occurred: %1', $result['message']));
            } catch (\InvalidArgumentException $e) {
                throw new LocalizedException(__('An error occurred: %1', $e->getMessage()));
            }
        }
    }
}
