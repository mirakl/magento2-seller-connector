<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Listing\Column;

class OrderLinesCount extends \Magento\Ui\Component\Listing\Columns\Column
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
                $item[$fieldName] = count($item['order_lines']);
            }
        }

        return $dataSource;
    }
}