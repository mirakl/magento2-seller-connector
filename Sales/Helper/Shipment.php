<?php
namespace MiraklSeller\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;

class Shipment extends AbstractHelper
{
    /**
     * @var ShipmentCollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @param   Context                     $context
     * @param   ShipmentCollectionFactory   $shipmentCollectionFactory
     */
    public function __construct(
        Context $context,
        ShipmentCollectionFactory $shipmentCollectionFactory)
    {
        parent::__construct($context);

        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
    }

    /**
     * @param   string  $miraklShipmentId
     * @return  OrderModel\Shipment
     */
    public function getShipmentByMiraklShipmentId($miraklShipmentId)
    {
        /** @var OrderModel\Shipment $shipment */
        $shipment = $this->shipmentCollectionFactory->create()
            ->addFieldToFilter('mirakl_shipment_id', $miraklShipmentId)
            ->getFirstItem();

        return $shipment;
    }
}
