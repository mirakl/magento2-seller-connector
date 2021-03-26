<?php
namespace MiraklSeller\Sales\Model\Inventory;

use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\InventorySourceSelectionApi\Model\GetInventoryRequestFromOrder;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;

class ShipmentSourceSelection
{
    /**
     * @var GetInventoryRequestFromOrder
     */
    private $getInventoryRequestFromOrder;

    /**
     * @var GetDefaultSourceSelectionAlgorithmCodeInterface
     */
    private $getDefaultSourceSelectionAlgorithmCode;

    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * @param GetInventoryRequestFromOrder                    $getInventoryRequestFromOrder
     * @param GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode
     * @param SourceSelectionServiceInterface                 $sourceSelectionService
     */
    public function __construct(
        GetInventoryRequestFromOrder $getInventoryRequestFromOrder,
        GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode,
        SourceSelectionServiceInterface $sourceSelectionService
    ) {
        $this->getInventoryRequestFromOrder = $getInventoryRequestFromOrder;
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        $this->sourceSelectionService = $sourceSelectionService;
    }

    /**
     * @param   ShipmentInterface   $shipment
     * @param   string              $algorithmCode
     * @return  SourceSelectionResultInterface
     */
    public function execute(ShipmentInterface $shipment, $algorithmCode)
    {
        $orderId = $shipment->getOrderId();

        if (empty($algorithmCode)) {
            $algorithmCode = $this->getDefaultSourceSelectionAlgorithmCode->execute();
        }

        $requestItems = [];
        foreach ($shipment->getItems() as $item) {
            /** @var ShipmentItemInterface $item */
            $requestItems[] = [
                'sku' => $item->getSku(),
                'qty' => $item->getQty(),
            ];
        }

        $inventoryRequest = $this->getInventoryRequestFromOrder->execute($orderId, $requestItems);

        return $this->sourceSelectionService->execute($inventoryRequest, $algorithmCode);
    }
}
