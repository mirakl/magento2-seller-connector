<?php
namespace MiraklSeller\Core\Model\Config\Source\Attribute;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class Dropdown implements OptionSourceInterface
{
    /**
     * @var AttributeCollectionFactory
     */
    protected $attrCollectionFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var bool
     */
    protected $withEmpty = true;

    /**
     * @param AttributeCollectionFactory $attrCollectionFactory
     */
    public function __construct(AttributeCollectionFactory $attrCollectionFactory)
    {
        $this->attrCollectionFactory = $attrCollectionFactory;
    }

    /**
     * Retrieves all product attributes collection
     *
     * @return  AttributeCollection
     */
    protected function getAttributeCollection()
    {
        $collection = $this->attrCollectionFactory->create();
        $collection->addVisibleFilter()
            ->setOrder('frontend_label', 'ASC');

        return $collection;
    }

    /**
     * Retrieves all product attributes with type Dropdown or Yes/No, use as configurable attribute
     * and in global scope in order to choose a potential variant axis.
     *
     * @return  array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $options = [];

        if ($this->withEmpty) {
            $options[] = ['value' => '', 'label' => __('-- Empty Value --')];
        }

        $collection = $this->getAttributeCollection();

        foreach ($collection as $attribute) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            if ($attribute->getFrontendLabel()) {
                $options[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
                ];
            }
        }
        $this->options = $options;

        return $this->options;
    }
}
