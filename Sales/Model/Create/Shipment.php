<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment as MagentoShipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Shipping\Model\Order\Track;
use Mirakl\MMP\Common\Domain\Shipment\Shipment as MiraklShipment;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentLine as MiraklShipmentLine;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentTracking as MiraklTracking;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Model\Inventory\AssignSourceCodeToShipment;

class Shipment
{
    /**
     * @var ShipmentFactory
     */
    protected $shipmentFactory;

    /**
     * @var TrackFactory
     */
    protected $trackFactory;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var AssignSourceCodeToShipment
     */
    protected $assignSourceCodeToShipment;

    /**
     * @param ShipmentFactory            $shipmentFactory
     * @param TrackFactory               $trackFactory
     * @param TransactionFactory         $transactionFactory
     * @param AssignSourceCodeToShipment $assignSourceCodeToShipment
     */
    public function __construct(
        ShipmentFactory $shipmentFactory,
        TrackFactory $trackFactory,
        TransactionFactory $transactionFactory,
        AssignSourceCodeToShipment $assignSourceCodeToShipment
    ) {
        $this->shipmentFactory            = $shipmentFactory;
        $this->trackFactory               = $trackFactory;
        $this->transactionFactory         = $transactionFactory;
        $this->assignSourceCodeToShipment = $assignSourceCodeToShipment;
    }

    /**
     * @deprecated Use createFull() instead
     *
     * @param   Order       $order
     * @param   ShopOrder   $miraklOrder
     * @param   Connection  $connection
     * @return  MagentoShipment
     * @throws  \Exception
     */
    public function create(Order $order, ShopOrder $miraklOrder, Connection $connection)
    {
        return $this->createFull($order, $miraklOrder, $connection);
    }

    /**
     * @param   Order       $order
     * @param   ShopOrder   $miraklOrder
     * @param   Connection  $connection
     * @return  MagentoShipment
     * @throws  \Exception
     */
    public function createFull(Order $order, ShopOrder $miraklOrder, Connection $connection)
    {
        if (!$order->canShip()) {
            throw new \Exception('Cannot do shipment for the order.');
        }

        $itemsToShip = [];
        /** @var Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $itemsToShip[$item->getId()] = $item->getQtyToShip();
        }

        /** @var MagentoShipment $shipment */
        $shipment = $this->shipmentFactory->create($order, $itemsToShip);

        $this->assignSourceCodeToShipment->execute($shipment, $connection);

        $miraklShipping = $miraklOrder->getShipping();
        if ($miraklShipping && ($miraklShipping->getCarrierCode() || $miraklShipping->getCarrier())) {
            // Create shipment tracking
            $trackData = [
                'carrier_code' => $miraklShipping->getCarrierCode() ?: Track::CUSTOM_CARRIER_CODE,
                'title'        => $miraklShipping->getCarrier(),
                'number'       => $miraklShipping->getTrackingNumber(),
            ];

            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $this->trackFactory->create();
            $track->addData($trackData);
            $shipment->addTrack($track);
        }

        return $this->saveShipment($shipment);
    }

    /**
     * @param   Order           $order
     * @param   MiraklShipment  $miraklShipment
     * @return  MagentoShipment
     * @throws  \Exception
     */
    public function createPartial(Order $order, MiraklShipment $miraklShipment)
    {
        if (!$order->canShip()) {
            throw new \Exception('Cannot do shipment for the order.');
        }

        $itemsToShip = [];
        /** @var Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            /** @var MiraklShipmentLine $miraklShipmentLine */
            foreach ($miraklShipment->getShipmentLines() as $miraklShipmentLine) {
                if ($item->getSku() == $miraklShipmentLine->getOfferSku()) {
                    $itemsToShip[$item->getId()] = $miraklShipmentLine->getQuantity();
                }
            }
        }

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $this->shipmentFactory->create($order, $itemsToShip);

        $shipment->setMiraklShipmentId($miraklShipment->getId());

        /** @var MiraklTracking $miraklTracking */
        $miraklTracking = $miraklShipment->getTracking();
        if ($miraklTracking && ($miraklTracking->getCarrierCode() || $miraklTracking->getCarrierName())) {
            // Create shipment tracking
            $trackData = [
                'carrier_code' => $miraklTracking->getCarrierCode() ?: Track::CUSTOM_CARRIER_CODE,
                'title'        => $miraklTracking->getCarrierName(),
                'number'       => $miraklTracking->getTrackingNumber(),
            ];

            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $this->trackFactory->create();
            $track->addData($trackData);
            $shipment->addTrack($track);
        }

        return $this->saveShipment($shipment);
    }

    /**
     * @param   MagentoShipment $shipment
     * @return  MagentoShipment
     */
    protected function saveShipment(MagentoShipment $shipment)
    {
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $shipment->setFromMirakl(true);

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $shipment;
    }
}
