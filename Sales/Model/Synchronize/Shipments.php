<?php
namespace MiraklSeller\Sales\Model\Synchronize;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Helper\Shipment as ShipmentApi;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Helper\Shipment as ShipmentHelper;
use MiraklSeller\Sales\Model\Create\Shipment as ShipmentCreator;
use MiraklSeller\Sales\Model\Synchronize\Shipment as ShipmentSynchronizer;

class Shipments
{
    /**
     * @var ShipmentApi
     */
    protected $shipmentApi;

    /**
     * @var ShipmentCreator
     */
    protected $shipmentCreator;

    /**
     * @var ShipmentSynchronizer
     */
    protected $shipmentSynchronizer;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var ShipmentHelper
     */
    protected $shipmentHelper;

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @var array
     */
    protected $stateCodes = [];

    /**
     * @param   ShipmentApi                 $shipmentApi
     * @param   ShipmentCreator             $shipmentCreator
     * @param   ShipmentSynchronizer        $shipmentSynchronizer
     * @param   OrderHelper                 $orderHelper
     * @param   ShipmentHelper              $shipmentHelper
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   array                       $stateCodes
     */
    public function __construct(
        ShipmentApi $shipmentApi,
        ShipmentCreator $shipmentCreator,
        ShipmentSynchronizer $shipmentSynchronizer,
        OrderHelper $orderHelper,
        ShipmentHelper $shipmentHelper,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        array $stateCodes = []
    ) {
        $this->shipmentApi = $shipmentApi;
        $this->shipmentCreator = $shipmentCreator;
        $this->shipmentSynchronizer = $shipmentSynchronizer;
        $this->orderHelper = $orderHelper;
        $this->shipmentHelper = $shipmentHelper;
        $this->connectionFactory = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
        $this->stateCodes = $stateCodes;
    }

    /**
     * @param   Order   $magentoOrder
     * @return  Connection
     */
    protected function getConnection(Order $magentoOrder)
    {
        $connectionId = $magentoOrder->getMiraklConnectionId();
        $connection = $this->connectionFactory->create();
        $this->connectionResourceFactory->create()->load($connection, $connectionId);

        return $connection;
    }

    /**
     * Returns true if Magento order has been updated or false if nothing has changed (order is up to date with Mirakl)
     *
     * @param   Order       $magentoOrder
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function synchronize(Order $magentoOrder, ShopOrder $miraklOrder)
    {
        if (!$magentoOrder->canShip()) {
            return false;
        }

        $updated = false; // Flag to mark Magento order as updated or not

        $connection = $this->getConnection($magentoOrder);

        try {
            $miraklShipments = $this->shipmentApi->getShipments($connection, [$miraklOrder->getId()], $this->stateCodes);

            /** @var \Mirakl\MMP\Common\Domain\Shipment\Shipment $miraklShipment */
            foreach ($miraklShipments->getCollection() as $miraklShipment) {
                $existingShipment = $this->shipmentHelper->getShipmentByMiraklShipmentId($miraklShipment->getId());
                if ($existingShipment->getId()) {
                    if ($this->shipmentSynchronizer->synchronize($existingShipment, $miraklShipment)) {
                        $updated = true;
                    }
                } elseif (null !== $this->shipmentCreator->createPartial($magentoOrder, $miraklShipment)) {
                    $updated = true;
                }
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            try {
                $result = \Mirakl\parse_json_response($e->getResponse());

                if ($result['status'] === 404 && $this->orderHelper->isMiraklOrderShipped($miraklOrder)) {
                    // Multi-shipment is probably disabled in Mirakl
                    // Try to create a full shipment

                    try {
                        $updated = true;
                        $this->shipmentCreator->createFull($magentoOrder, $miraklOrder);
                    } catch (\Exception $e) {
                        throw new LocalizedException(__('An error occurred: %1', $e->getMessage()));
                    }
                }
            } catch (\InvalidArgumentException $e) {
                throw new LocalizedException(__('An error occurred: %1', $e->getMessage()));
            }
        }

        return $updated;
    }
}
