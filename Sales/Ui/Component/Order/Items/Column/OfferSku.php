<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Items\Column;

class OfferSku extends \Magento\Ui\Component\Listing\Columns\Column
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
                $item[$fieldName] = $item['offer_sku'];

                if (!$product = $item['product']) {
                    continue;
                }

                /** @var \Magento\Catalog\Model\Product $product */
                if ($product->isDisabled()) {
                    $item[$fieldName] = sprintf(
                        '%s<br><span class="nobr red">%s</span>',
                        $item['offer_sku'],
                        __('disabled')
                    );
                }
            }
        }

        return $dataSource;
    }
}