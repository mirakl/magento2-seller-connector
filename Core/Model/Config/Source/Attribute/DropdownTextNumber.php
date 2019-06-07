<?php
namespace MiraklSeller\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;

class DropdownTextNumber extends Dropdown
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
        $collection->setFrontendInputTypeFilter('text')
            ->addFieldToFilter('frontend_class', 'validate-digits');

        return $collection;
    }
}
