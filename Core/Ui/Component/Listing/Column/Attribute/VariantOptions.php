<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Column\Attribute;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler;

class VariantOptions extends AllOptions
{
    /**
     * @var ConfigurableAttributeHandler
     */
    protected $configurableAttributeHandler;

    /**
     * @var bool
     */
    protected $emptyValue = false;

    /**
     * @param   AttributeCollectionFactory      $collectionFactory
     * @param   ConfigurableAttributeHandler    $configurableAttributeHandler
     */
    public function __construct(
        AttributeCollectionFactory $collectionFactory,
        ConfigurableAttributeHandler $configurableAttributeHandler
    ) {
        parent::__construct($collectionFactory);
        $this->configurableAttributeHandler = $configurableAttributeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCollection()
    {
        return $this->configurableAttributeHandler->getApplicableAttributes();
    }
}
