<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Items\Column;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class Quantity extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @param   ContextInterface        $context
     * @param   UiComponentFactory      $uiComponentFactory
     * @param   StockRegistryInterface  $stockRegistry
     * @param   array                   $components
     * @param   array                   $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StockRegistryInterface $stockRegistry,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param   array   $dataSource
     * @return  array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$fieldName] = sprintf('<div class="right">%d</div>', $item['quantity']);

                if (!$product = $item['product']) {
                    continue;
                }

                /** @var \Magento\Catalog\Model\Product $product */
                if ($product->getId()) {
                    /** @var StockItemInterface $stockItem */
                    $stockItem = $this->stockRegistry->getStockItem($product->getId());
                    $item[$fieldName] .= sprintf(
                        '<div class="right nobr%s">%s</div>',
                        (!$stockItem->getIsInStock() || $stockItem->getQty() < $item['quantity']) ? ' red' : '',
                        $stockItem->getIsInStock() ? __('%1 in stock', $stockItem->getQty()) : __('out of stock')
                    );
                }
            }
        }

        return $dataSource;
    }
}