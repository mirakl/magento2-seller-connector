<?php
namespace MiraklSeller\Sales\Ui\Component\View\Element\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;

class OrderSourceFilter  implements FilterApplierInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Collection $collection, Filter $filter)
    {
        $value = $filter->getValue();
        $cond = $value === '0' ? ['null' => true] : ['eq' => $value];
        $collection->addFieldToFilter('mirakl_connection_id', $cond);
    }
}