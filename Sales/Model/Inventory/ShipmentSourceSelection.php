<?php
namespace MiraklSeller\Sales\Model\Inventory;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;

class ShipmentSourceSelection
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var bool
     */
    protected $isMsiEnabled;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->isMsiEnabled  = $objectManager->get(\MiraklSeller\Core\Helper\Data::class)->isMsiEnabled();
    }

    /**
     * @param   ShipmentInterface   $shipment
     * @param   string              $algorithmCode
     * @return  \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface
     */
    public function execute(ShipmentInterface $shipment, $algorithmCode)
    {
        if (!$this->isMsiEnabled) {
            return null;
        }

        $orderId = $shipment->getOrderId();

        if (empty($algorithmCode)) {
            $getDefaultSourceSelectionAlgorithmCode = $this->objectManager
                ->get('Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface');
            $algorithmCode = $getDefaultSourceSelectionAlgorithmCode->execute();
        }

        $requestItems = [];
        foreach ($shipment->getItems() as $item) {
            /** @var ShipmentItemInterface $item */
            $requestItems[] = [
                'sku' => $item->getSku(),
                'qty' => $item->getQty(),
            ];
        }

        $getInventoryRequestFromOrder = $this->objectManager
            ->get('Magento\InventorySourceSelectionApi\Model\GetInventoryRequestFromOrder');
        $sourceSelectionService = $this->objectManager
            ->get('Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface');

        $inventoryRequest = $getInventoryRequestFromOrder->execute($orderId, $requestItems);

        return $sourceSelectionService->execute($inventoryRequest, $algorithmCode);
    }
}
