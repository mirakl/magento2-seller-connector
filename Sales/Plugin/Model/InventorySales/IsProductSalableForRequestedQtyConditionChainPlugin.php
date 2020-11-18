<?php
namespace MiraklSeller\Sales\Plugin\Model\InventorySales;

use Magento\Framework\ObjectManagerInterface;
use MiraklSeller\Sales\Model\InventorySales\SkipQtyCheckFlag;

/**
 * This plugin has been created to be able to skip the quantity validator implemented since Magento 2.3.0.
 * In case we are importing Mirakl order items, we must avoid potential errors coming from invalid stock quantity.
 */
class IsProductSalableForRequestedQtyConditionChainPlugin
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SkipQtyCheckFlag
     */
    private $skipQtyCheckFlag;

    /**
     * @param   ObjectManagerInterface  $objectManager
     * @param   SkipQtyCheckFlag        $skipQtyCheckFlag
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        SkipQtyCheckFlag $skipQtyCheckFlag
    ) {
        $this->objectManager = $objectManager;
        $this->skipQtyCheckFlag = $skipQtyCheckFlag;
    }

    /**
     * @param   object      $subject
     * @param   \Closure    $proceed
     * @param   string      $sku
     * @param   int         $stockId
     * @param   float       $requestedQty
     * @return  \Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface
     */
    public function aroundExecute($subject, \Closure $proceed, $sku, $stockId, $requestedQty)
    {
        if ($this->skipQtyCheckFlag->getQtySkipQtyCheck()) {
            return $this->objectManager->create('\Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface', ['errors' => []]);
        }

        return $proceed($sku, $stockId, $requestedQty);
    }
}
