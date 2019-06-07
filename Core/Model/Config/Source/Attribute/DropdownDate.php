<?php
namespace MiraklSeller\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;

class DropdownDate extends Dropdown
{
    /**
     * Retrieves all product attributes collection
     * Filtered by FrontendInputType Text with validation number
     *
     * @return  AttributeCollection
     */
    protected function getAttributeCollection()
    {
        $collection = parent::getAttributeCollection();
        $collection->setFrontendInputTypeFilter('date');

        return $collection;
    }
}
