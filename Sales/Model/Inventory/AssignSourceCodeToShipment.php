<?php
namespace MiraklSeller\Sales\Model\Inventory;

use Magento\Sales\Api\Data\ShipmentInterface;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Helper\Data as Helper;

class AssignSourceCodeToShipment
{
    /**
     * @var ShipmentSourceSelection
     */
    private $shipmentSourceSelection;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param ShipmentSourceSelection $shipmentSourceSelection
     * @param Helper                  $helper
     */
    public function __construct(ShipmentSourceSelection $shipmentSourceSelection, Helper $helper)
    {
        $this->shipmentSourceSelection = $shipmentSourceSelection;
        $this->helper = $helper;
    }

    /**
     * @param   ShipmentInterface   $shipment
     * @param   Connection          $connection
     * @return  void
     */
    public function execute(ShipmentInterface $shipment, Connection $connection)
    {
        if (!$this->helper->isMsiEnabled()) {
            return;
        }

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
