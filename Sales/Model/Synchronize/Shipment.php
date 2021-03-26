<?php
namespace MiraklSeller\Sales\Model\Synchronize;

use Magento\Sales\Model\Order\Shipment as MagentoShipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\ResourceModel\Order\ShipmentFactory as ShipmentResourceFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\TrackFactory as TrackResourceFactory;
use Mirakl\MMP\Common\Domain\Shipment\Shipment as MiraklShipment;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentTracking as MiraklTracking;

class Shipment
{
    /**
     * @var TrackFactory
     */
    protected $trackFactory;

    /**
     * @var TrackResourceFactory
     */
    protected $trackResourceFactory;

    /**
     * @var ShipmentResourceFactory
     */
    protected $shipmentResourceFactory;

    /**
     * @param   TrackFactory            $trackFactory
     * @param   TrackResourceFactory    $trackResourceFactory
     * @param   ShipmentResourceFactory $shipmentResourceFactory
     */
    public function __construct(
        TrackFactory $trackFactory,
        TrackResourceFactory $trackResourceFactory,
        ShipmentResourceFactory $shipmentResourceFactory
    ) {
        $this->trackFactory = $trackFactory;
        $this->trackResourceFactory = $trackResourceFactory;
        $this->shipmentResourceFactory = $shipmentResourceFactory;
    }

    /**
     * Returns true if Magento shipment has been updated or false if not
     *
     * @param   MagentoShipment   $shipment
     * @param   MiraklShipment    $miraklShipment
     * @return  bool
     */
    public function synchronize(MagentoShipment $shipment, MiraklShipment $miraklShipment)
    {
        if (!$miraklShipment->getTracking()) {
            return false;
        }

        $updated = false; // Flag to mark Magento shipment as updated or not

        /** @var MiraklTracking $miraklTracking */
        $miraklTracking = $miraklShipment->getTracking();

        if (!$shipment->getTracksCollection()->count()) {
            if ($miraklTracking->getCarrierCode() || $miraklTracking->getCarrierName()) {
                // Create shipment tracking if not created yet
                $trackData = [
                    'carrier_code' => $miraklTracking->getCarrierCode() ?: Track::CUSTOM_CARRIER_CODE,
                    'title'        => $miraklTracking->getCarrierName(),
                    'number'       => $miraklTracking->getTrackingNumber(),
                ];

                $track = $this->trackFactory->create();
                $track->addData($trackData);
                $shipment->addTrack($track);
                $this->shipmentResourceFactory->create()->save($shipment);
                $updated = true;
            }
        } else {
            // Update existing shipment tracking
            /** @var Track $existingTrack */
            foreach ($shipment->getTracksCollection() as $existingTrack) {
                if ($shipment->getMiraklShipmentId() !== $miraklShipment->getId()) {
                    continue;
                }

                if ($this->synchronizeTracking($existingTrack, $miraklTracking)) {
                    $updated = true;
                }

                break; // exit loop
            }
        }

        return $updated;
    }

    /**
     * Returns true if the Magento shipment tracking has been modified and saved, false otherwise
     *
     * @param   Track           $magentoTracking
     * @param   MiraklTracking  $miraklTracking
     * @return  bool
     */
    public function synchronizeTracking(Track $magentoTracking, MiraklTracking $miraklTracking)
    {
        $saveTrack = false;

        if ($magentoTracking->getCarrierCode() != $miraklTracking->getCarrierCode() && !empty($miraklTracking->getCarrierCode())) {
            $saveTrack = true;
            $magentoTracking->setCarrierCode($miraklTracking->getCarrierCode());
        }

        if ($magentoTracking->getTrackNumber() != $miraklTracking->getTrackingNumber()) {
            $saveTrack = true;
            $magentoTracking->setTrackNumber($miraklTracking->getTrackingNumber());
        }

        if ($magentoTracking->getTitle() != $miraklTracking->getCarrierName()) {
            $saveTrack = true;
            $magentoTracking->setTitle($miraklTracking->getCarrierName());
        }

        if ($saveTrack) {
            $this->trackResourceFactory->create()->save($magentoTracking);
        }

        return $saveTrack;
    }
}
