<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment as ShipmentModel;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

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
     * @param   ShipmentFactory     $shipmentFactory
     * @param   TrackFactory        $trackFactory
     * @param   TransactionFactory  $transactionFactory
     */
    public function __construct(
        ShipmentFactory $shipmentFactory,
        TrackFactory $trackFactory,
        TransactionFactory $transactionFactory
    ) {
        $this->shipmentFactory    = $shipmentFactory;
        $this->trackFactory       = $trackFactory;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param   Order       $order
     * @param   ShopOrder   $miraklOrder
     * @return  ShipmentModel
     * @throws  \Exception
     */
    public function create(Order $order, ShopOrder $miraklOrder)
    {
        if (!$order->canShip()) {
            throw new \Exception('Cannot do shipment for the order.');
        }

        $itemsToShip = [];
        /** @var Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $itemsToShip[$item->getId()] = $item->getQtyToShip();
        }

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $this->shipmentFactory->create($order, $itemsToShip);

        $miraklShipping = $miraklOrder->getShipping();
        if ($miraklShipping && $miraklShipping->getCarrierCode()) {
            // Create shipment tracking
            $trackData = [
                'carrier_code' => $miraklShipping->getCarrierCode(),
                'title'        => $miraklShipping->getCarrier(),
                'number'       => $miraklShipping->getTrackingNumber(),
            ];

            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $this->trackFactory->create();
            $track->addData($trackData);
            $shipment->addTrack($track);
        }

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $shipment;
    }
}