<?php
namespace MiraklSeller\Sales\Ui\Component\Filters\Type;

class OrderSource extends \Magento\Ui\Component\Filters\Type\Select
{
    /**
     * {@inheritdoc}
     */
    protected function applyFilter()
    {
        if (isset($this->filterData[$this->getName()])) {
            $value = $this->filterData[$this->getName()];
            $filter = $this->filterBuilder->setConditionType('mirakl_seller_order_source')
                ->setField($this->getName())
                ->setValue($value)
                ->create();

            $this->getContext()->getDataProvider()->addFilter($filter);
        }
    }
}