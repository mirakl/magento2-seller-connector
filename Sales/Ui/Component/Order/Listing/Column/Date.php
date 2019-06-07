<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Listing\Column;

class Date extends \Magento\Ui\Component\Listing\Columns\Column
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
                /** @var \DateTime $createdAt */
                $createdAt = $item[$fieldName];
                $item[$fieldName] = $createdAt
                    ->setTimezone(new \DateTimeZone('GMT'))
                    ->format('d/m/Y H:i:s');
            }
        }

        return $dataSource;
    }
}
