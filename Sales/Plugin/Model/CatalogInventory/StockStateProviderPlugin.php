<?php
namespace MiraklSeller\Sales\Plugin\Model\CatalogInventory;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use MiraklSeller\Sales\Model\InventorySales\SkipQtyCheckFlag;

class StockStateProviderPlugin
{
    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var SkipQtyCheckFlag
     */
    private $skipQtyCheckFlag;

    /**
     * @param   ObjectFactory       $objectFactory
     * @param   SkipQtyCheckFlag    $skipQtyCheckFlag
     */
    public function __construct(ObjectFactory $objectFactory, SkipQtyCheckFlag $skipQtyCheckFlag)
    {
        $this->objectFactory = $objectFactory;
        $this->skipQtyCheckFlag = $skipQtyCheckFlag;
    }

    /**
     * @param   StockStateProviderInterface $stateProvider
     * @param   \Closure                    $proceed
     * @param   StockItemInterface          $stockItem
     * @param   int|float                   $qty
     * @param   int|float                   $summaryQty
     * @param   int|float                   $origQty
     * @return \Magento\Framework\DataObject
     */
    public function aroundCheckQuoteItemQty(
        StockStateProviderInterface $stateProvider,
        \Closure $proceed,
        StockItemInterface $stockItem,
        $qty,
        $summaryQty,
        $origQty = 0
    ) {
        if ($this->skipQtyCheckFlag->getQtySkipQtyCheck()) {
            return $this->objectFactory->create(['has_error' => false]);
        }

        return $proceed($stockItem, $qty, $summaryQty, $origQty);
    }
}