<?php
namespace MiraklSeller\Core\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class Product
{
    /**
     * @var AttributeCollection
     */
    protected $allowedAttributes;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * Default excluded attribute codes
     *
     * @var array
     */
    protected $excludedAttributesRegexpArray = [
        'custom_layout.*',
        'options_container',
        'custom_design.*',
        'page_layout',
        'tax_class_id',
        'is_recurring',
        'recurring_profile',
        'tier_price',
        'group_price',
        'price.*',
        'status',
        'visibility',
        'url_key',
        'special_price',
        'special_from_date',
        'special_to_date',
        'quantity_and_stock_status',
    ];

    /**
     * Excluded attribute types
     *
     * @var array
     */
    protected $excludedTypes = ['gallery', 'hidden', 'multiline', 'media_image'];

    /**
     * @param   ProductResourceFactory      $productResourceFactory
     * @param   AttributeCollectionFactory  $attributeCollectionFactory
     * @param   EventManagerInterface       $eventManager
     */
    public function __construct(
        ProductResourceFactory $productResourceFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        EventManagerInterface $eventManager
    ) {
        $this->productResource = $productResourceFactory->create();
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->eventManager = $eventManager;
    }

    /**
     * Retrieves exportable product attributes
     *
     * @return  AttributeCollection
     */
    public function getExportableAttributes()
    {
        if (null === $this->allowedAttributes) {
            $collection = $this->attributeCollectionFactory->create()
                ->addVisibleFilter()
                ->setOrder('frontend_label', 'ASC');

            foreach ($collection as $key => $attribute) {
                /** @var EavAttribute $attribute */
                if (!$this->isAttributeExportable($attribute)) {
                    $collection->removeItemByKey($key);
                }
            }

            $this->eventManager->dispatch('mirakl_seller_exportable_product_attributes', [
                'attributes' => $collection,
            ]);

            $this->allowedAttributes = $collection;
        }

        return $this->allowedAttributes;
    }

    /**
     * Retrieves exportable product attribute codes
     *
     * @return  array
     */
    public function getExportableAttributeCodes()
    {
        return $this->getExportableAttributes()->walk('getAttributeCode');
    }

    /**
     * @param   EavAttribute    $attribute
     * @return  bool
     */
    protected function isAttributeExportable(EavAttribute $attribute)
    {
        $exclAttrRegexp = sprintf('/^(%s)$/i', implode('|', $this->excludedAttributesRegexpArray));

        return $attribute->getFrontendLabel()
            && !$attribute->isStatic()
            && !in_array($attribute->getData('frontend_input'), $this->excludedTypes)
            && !preg_match($exclAttrRegexp, $attribute->getAttributeCode());
    }

    /**
     * Get all attributes of the catalog_product_entity table
     *
     * @return  array
     */
    public function getProductBaseColumns()
    {
        $columns = $this->productResource->getConnection()
            ->describeTable($this->productResource->getTable('catalog_product_entity'));

        return array_keys($columns);
    }

    /**
     * Builds exportable attributes options
     *
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->getExportableAttributes() as $attribute) {
            /** @var EavAttribute $attribute */
            $options[] = [
                'value' => $attribute->getAttributeId(),
                'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
            ];
        }

        return $options;
    }
}
