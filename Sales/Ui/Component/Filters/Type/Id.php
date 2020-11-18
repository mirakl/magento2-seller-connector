<?php
namespace MiraklSeller\Sales\Ui\Component\Filters\Type;

use Magento\Ui\Component\Filters\Type\AbstractFilter;

class Id extends AbstractFilter
{
    const NAME = 'filter_id';

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $this->applyFilter();

        parent::prepare();
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFilter()
    {
        if (!empty($this->filterData[$this->getName()])) {
            $value = $this->filterData[$this->getName()];
            $filter = $this->filterBuilder->setConditionType('eq')
                ->setField($this->getName())
                ->setValue($value)
                ->create();

            $this->getContext()->getDataProvider()->addFilter($filter);
        }
    }
}