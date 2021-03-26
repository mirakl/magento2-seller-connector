<?php
namespace MiraklSeller\Api\Helper;

use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Collection\Shipment\CreateShipmentCollection;
use Mirakl\MMP\Common\Domain\Collection\Shipment\UpdateShipmentTrackingCollection;
use Mirakl\MMP\Common\Domain\Shipment\CreatedShipments;
use Mirakl\MMP\Common\Domain\Shipment\UpdatedShipmentTrackings;
use Mirakl\MMP\Shop\Request\Shipment\CreateShipmentsRequest;
use Mirakl\MMP\Shop\Request\Shipment\GetShipmentsRequest;
use Mirakl\MMP\Shop\Request\Shipment\UpdateShipmentTrackingsRequest;
use MiraklSeller\Api\Model\Connection;

class Shipment extends Client\MMP
{
    /**
     * (ST01) Create shipments
     *
     * @param   Connection                  $connection
     * @param   CreateShipmentCollection    $shipments
     * @return  CreatedShipments
     */
    public function createShipments(Connection $connection, CreateShipmentCollection $shipments)
    {
        $request = new CreateShipmentsRequest($shipments);

        return $this->send($connection, $request);
    }

    /**
     * (ST11) List shipments of given orders
     *
     * @param   Connection  $connection
     * @param   array       $orderIds
     * @param   array       $stateCodes     @see \Mirakl\MMP\Common\Domain\Shipment\ShipmentStatus
     * @param   int         $limit
     * @return  SeekableCollection
     */
    public function getShipments(Connection $connection, array $orderIds = [], array $stateCodes = [], $limit = 10)
    {
        $request = new GetShipmentsRequest();

        if (!empty($orderIds)) {
            $request->setOrderIds($orderIds);
        }

        if (!empty($stateCodes)) {
            $request->setShipmentStateCodes($stateCodes);
        }

        // Force limit in range 1-100
        $limit = max(1, min(100, abs((int) $limit)));
        $request->setLimit($limit);

        return $this->send($connection, $request);
    }

    /**
     * (ST11) List shipments according to given page token
     *
     * @param   Connection  $connection
     * @param   string      $pageToken
     * @return  SeekableCollection
     */
    public function getShipmentsPage(Connection $connection, $pageToken)
    {
        $request = new GetShipmentsRequest();
        $request->setPageToken($pageToken);

        return $this->send($connection, $request);
    }

    /**
     * (ST23) Update carrier tracking information for shipments
     *
     * @param   Connection                          $connection
     * @param   UpdateShipmentTrackingCollection    $updateShipmentTrackings
     * @return  UpdatedShipmentTrackings
     */
    public function updateShipmentTrackings(
        Connection $connection,
        UpdateShipmentTrackingCollection $updateShipmentTrackings
    ) {
        $request = new UpdateShipmentTrackingsRequest($updateShipmentTrackings);

        return $this->send($connection, $request);
    }
}
