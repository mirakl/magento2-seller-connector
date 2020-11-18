<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Column\Attribute;

use Magento\Customer\Model\Data\AttributeMetadata;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class AllOptions implements OptionSourceInterface
{
    /**
     * @var AttributeCollectionFactory
     */
    protected $attrCollectionFactory;

    /**
     * @var bool
     */
    protected $emptyValue = true;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param   AttributeCollectionFactory  $collectionFactory
     */
    public function __construct(AttributeCollectionFactory $collectionFactory)
    {
        $this->attrCollectionFactory = $collectionFactory;
    }

    /**
     * @return  AttributeCollection
     */
    public function getAttributeCollection()
    {
        $collection = $this->attrCollectionFactory->create()
            ->addVisibleFilter()
            ->setOrder('frontend_label', 'ASC');

        return $collection;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        if ($this->emptyValue) {
            $this->options = [
                [
                    'value' => '',
                    'label' => __('-- Please Select --'),
                ]
            ];
        } else {
            $this->options = [];
        }

        $collection = $this->getAttributeCollection();
        foreach ($collection as $attribute) {
            /** @var AttributeMetadata $attribute */
            if ($attribute->getFrontendLabel()) {
                $this->options[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
                ];
            }
        }

        return $this->options;
    }
}
