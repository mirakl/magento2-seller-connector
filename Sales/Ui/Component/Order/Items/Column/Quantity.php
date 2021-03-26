<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Items\Column;

class Quantity extends \Magento\Ui\Component\Listing\Columns\Column
{
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
                    $inStock = $item['salable_quantity'] >= $item['quantity'];
                    $item[$fieldName] .= sprintf(
                        '<div class="right nobr%s">%s</div>',
                        $inStock ? '' : ' red',
                        $inStock ? __('%1 in stock', $item['salable_quantity']) : __('out of stock')
                    );
                }
            }
        }

        return $dataSource;
    }
}
