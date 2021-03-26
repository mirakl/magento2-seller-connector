<?php
namespace MiraklSeller\Sales\Model\Inventory;

use Magento\Sales\Api\Data\ShipmentInterface;
use MiraklSeller\Api\Model\Connection;

class AssignSourceCodeToShipment
{
    /**
     * @var ShipmentSourceSelection
     */
    private $shipmentSourceSelection;

    /**
     * @param ShipmentSourceSelection $shipmentSourceSelection
     */
    public function __construct(ShipmentSourceSelection $shipmentSourceSelection)
    {
        $this->shipmentSourceSelection = $shipmentSourceSelection;
    }

    /**
     * @param   ShipmentInterface   $shipment
     * @param   Connection          $connection
     * @return  void
     */
    public function execute(ShipmentInterface $shipment, Connection $connection)
    {
        $inventorySources = $this->shipmentSourceSelection->execute($shipment, $connection->getShipmentSourceAlgorithm());

        foreach ($inventorySources->getSourceSelectionItems() as $source) {
            // Take the first source that is priority, might be smarter in next versions
            $sourceCode = $source->getSourceCode();
            $shipmentExtension = $shipment->getExtensionAttributes();
            $shipmentExtension->setSourceCode($sourceCode);
            break;
        }
    }
}
