<?php
namespace MiraklSeller\Core\Ui\DataProvider\Offer;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;

class AddFilterToCollection implements AddFilterToCollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function addFilter(Collection $collection, $field, $condition = null)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection  */
        foreach (['gteq' => '>=', 'lteq' => '<=', 'eq' => '=', 'like' => 'like'] as $type => $operator) {
            if (isset($condition[$type]) && $condition[$type]) {
                $collection->getSelect()->where("offers.$field $operator ?", $condition[$type]);
            }
        }
    }
}
