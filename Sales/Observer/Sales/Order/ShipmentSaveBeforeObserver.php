<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\MMP\Common\Domain\Collection\Shipment\CreateShipmentCollection;
use Mirakl\MMP\Common\Domain\Collection\Shipment\ShipmentLineCollection;
use Mirakl\MMP\Common\Domain\Shipment\CreateShipment;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentLine;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentTracking;

class ShipmentSaveBeforeObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept order shipping before it is saved
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if ($shipment->getFromMirakl()) {
            return; // Abort if creation comes from Mirakl synchronization
        }

        try {
            $order = $shipment->getOrder();
            if (!$this->isImportedMiraklOrder($order)) {
                return; // Not a Mirakl order, leave
            }
        } catch (NoSuchEntityException $e) {
            return; // Problem retrieving the associated order, abort
        }

        $connection  = $this->getConnectionById($order->getMiraklConnectionId());
        $miraklOrder = $this->getMiraklOrder($connection, $order->getMiraklOrderId());

        $createShipments = new CreateShipmentCollection();
        $shipmentLines = new ShipmentLineCollection();

        /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
        foreach ($shipment->getAllItems() as $item) {
            $shipmentLine = new ShipmentLine();
            $shipmentLine->setOfferSku($item->getSku());
            $shipmentLine->setQuantity($item->getQty());
            $shipmentLines->add($shipmentLine);
        }

        $createShipment = new CreateShipment();
        $createShipment->setOrderId($miraklOrder->getId());
        $createShipment->setShipmentLines($shipmentLines);
        $createShipment->setShipped(true);

        /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
        foreach ($shipment->getAllTracks() as $track) {
            $shipmentTracking = new ShipmentTracking();
            $shipmentTracking->setCarrierCode($this->getMiraklCarrierCode($connection, $track));
            $shipmentTracking->setCarrierName($track->getTitle());
            $shipmentTracking->setTrackingNumber($track->getTrackNumber());
            $createShipment->setTracking($shipmentTracking);
            break; // Stop after the first tracking, Mirakl handles only one per shipment
        }

        $createShipments->add($createShipment);

        try {
            // Create the shipment in Mirakl (API ST01)
            $createdShipments = $this->apiShipment->createShipments($connection, $createShipments);

            if (!empty($createdShipments->getShipmentErrors())) {
                $error = $createdShipments->getShipmentErrors()->first();
                if ($error) {
                    throw new LocalizedException(__('An error occurred: %1', $error->getMessage()));
                }
            }

            // Save the Mirakl created shipment id in Magento shipment
            $shipmentSuccess = $createdShipments->getShipmentSuccess()->first();
            $shipment->setMiraklShipmentId($shipmentSuccess->getId());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            try {
                $result = \Mirakl\parse_json_response($e->getResponse());

                if ($result['status'] === 404 && !$this->getOrderQtyToShip($order)) {
                    // Multi-shipment is probably disabled in Mirakl
                    // Try to send shipment tracking through API 0R23 (send the last one)

                    /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
                    foreach (array_reverse($shipment->getAllTracks()) as $track) {
                        // Send order tracking info to Mirakl
                        $this->apiOrder->updateOrderTrackingInfo(
                            $connection,
                            $miraklOrder->getId(),
                            $this->getMiraklCarrierCode($connection, $track),
                            $track->getTitle(),
                            $track->getTrackNumber()
                        );
                        break; // Stop after the first, Mirakl handles only one tracking
                    }

                    try {
                        // Confirm shipment of the order in Mirakl through API OR24
                        $this->apiOrder->shipOrder($connection, $miraklOrder->getId());
                    } catch (\Exception $e) {
                        throw new LocalizedException(__('An error occurred: %1', $e->getMessage()));
                    }
                }
            } catch (\InvalidArgumentException $e) {
                throw new LocalizedException(__('An error occurred: %1', $e->getMessage()));
            }
        }
    }
}
