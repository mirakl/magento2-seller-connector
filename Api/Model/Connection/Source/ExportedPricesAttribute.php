<?php
namespace MiraklSeller\Api\Model\Connection\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class ExportedPricesAttribute implements OptionSourceInterface
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
     * @param AttributeCollectionFactory $attrCollectionFactory
     */
    public function __construct(AttributeCollectionFactory $attrCollectionFactory)
    {
        $this->attrCollectionFactory = $attrCollectionFactory;
    }

    /**
     * Retrieves all attributes of type 'price'
     *
     * @return  array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $options = [
            [
                'value' => '',
                'label' => __('-- Default Price --'),
            ],
        ];

        /** @var AttributeCollection $collection */
        $collection = $this->attrCollectionFactory->create();
        $collection->addVisibleFilter()
            ->addFieldToFilter('frontend_input', 'price')
            ->setOrder('frontend_label', 'ASC');

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
