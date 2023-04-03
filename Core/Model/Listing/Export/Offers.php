<?php
namespace MiraklSeller\Core\Model\Listing\Export;

use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\ObjectManagerInterface;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Listing\Product as ProductHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Export\AdditionalField\Formatter as AdditionalFieldFormatter;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Offer as OfferFormatter;
use MiraklSeller\Core\Model\Offer;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory;
use MiraklSeller\Core\Model\ResourceModel\Product\Collection as ProductCollection;
use MiraklSeller\Core\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Offers extends AbstractExport
{
    const NO_MANAGE_STOCK_QTY = 999;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var AdditionalFieldFormatter
     */
    protected $additionalFieldFormatter;

    /**
     * @var OfferFormatter
     */
    protected $offerFormatter;

    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var OfferFactory
     */
    protected $offerFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var bool
     */
    protected $isMsiEnabled;

    /**
     * @param   Config                      $config
     * @param   ProductHelper               $productHelper
     * @param   OfferFormatter              $offerFormatter
     * @param   AdditionalFieldFormatter    $additionalFieldFormatter
     * @param   AttributeFactory            $attributeFactory
     * @param   OfferFactory                $offerFactory
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   ObjectManagerInterface      $objectManager
     */
    public function __construct(
        Config $config,
        ProductHelper $productHelper,
        OfferFormatter $offerFormatter,
        AdditionalFieldFormatter $additionalFieldFormatter,
        AttributeFactory $attributeFactory,
        OfferFactory $offerFactory,
        ProductCollectionFactory $productCollectionFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->config                   = $config;
        $this->productHelper            = $productHelper;
        $this->offerFormatter           = $offerFormatter;
        $this->additionalFieldFormatter = $additionalFieldFormatter;
        $this->attributeFactory         = $attributeFactory;
        $this->offerFactory             = $offerFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->objectManager            = $objectManager;
        $this->isMsiEnabled             = $objectManager->get(\MiraklSeller\Core\Helper\Data::class)->isMsiEnabled();
    }

    /**
     * Prepares offer data for export
     *
     * @param   array   $product
     * @param   Listing $listing
     * @param   string  $action
     * @return  array
     */
    public function prepareOffer(array $product, Listing $listing, $action = 'update')
    {
        $product['action'] = $action;
        $product['state'] = $listing->getOfferState();

        $product = $this->handleProductReferenceIdentifiers($product, $listing);
        $additionalFields = $this->handleAdditionalFields($product, $listing);
        $product = $this->offerFormatter->format($product, $listing);

        return array_merge($product, $additionalFields);
    }

    /**
     * Handles offer additional fields
     *
     * @param   array   $product
     * @param   Listing $listing
     * @return  array
     */
    public function handleAdditionalFields(array $product, Listing $listing)
    {
        $data = [];
        $fields = $listing->getOfferAdditionalFields();
        $values = $listing->getOfferAdditionalFieldsValues();

        foreach ($fields as $field) {
            $key = $field['code']; // Additional field code

            // Initialize default value and optional Magento attribute code mapping
            $defaultValue = isset($values[$key]['default']) ? $values[$key]['default'] : '';
            $attrCode = isset($values[$key]['attribute']) ? $values[$key]['attribute'] : '';

            // Init additional field with default value, even if empty
            $value = $defaultValue;

            // If Magento attribute is specified AND has a value then use it.
            // If Magento attribute is specified AND has an empty value then allow empty only if field is not required.
            if ($attrCode && (!empty($product[$attrCode]) || !$field['required'])) {
                $value = isset($product[$attrCode]) ? $product[$attrCode] : '';
            }

            $data[$key] = $this->additionalFieldFormatter->format($field, $value);
        }

        return $data;
    }

    /**
     * Handles product reference identifiers
     *
     * @param   array   $product
     * @param   Listing $listing
     * @return  array
     */
    public function handleProductReferenceIdentifiers(array $product, Listing $listing)
    {
        // Handle product reference identifiers
        $productIdValueAttribute = $listing->getProductIdValueAttribute();
        $productIdType = $listing->getProductIdType();

        $product['product-id'] = !empty($productIdValueAttribute) && isset($product[$productIdValueAttribute])
            ? $product[$productIdValueAttribute]
            : $product['sku'];

        $product['product-id-type'] = !empty($productIdType)
            ? $productIdType
            : OfferFormatter::DEFAULT_PRODUCT_ID_TYPE;

        return $product;
    }

    /**
     * @param   ProductCollection   $collection
     * @param   Listing             $listing
     */
    protected function addMappedFieldsToCollection(ProductCollection $collection, Listing $listing)
    {
        // Add mapped attributes to select
        foreach ($this->config->getOfferFieldsMapping($listing->getStoreId()) as $value) {
            if (!$value) {
                continue;
            }

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $attribute = $this->attributeFactory->create()->loadByCode(
                \Magento\Catalog\Model\Product::ENTITY,
                $value
            );

            if ($collection->isAttributeUsingOptions($attribute)) {
                $collection->addAttributeOptionValue($attribute);
            } else {
                $collection->addAttributeToSelect($value);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function export(Listing $listing)
    {
        $collection = $this->productHelper->getProductCollection($listing);
        $customerGroupId = $this->config->getCustomerGroup();
        $tierPricesApplyOn = $listing->getConnection()->getMagentoTierPricesApplyOn();
        $collection->addTierPricesToSelect($listing->getWebsiteId(), $customerGroupId, $tierPricesApplyOn)
            ->addListingPriceData($listing)
            ->addQuantityToSelect()
            ->addAttributeToSelect(['special_price', 'special_from_date', 'special_to_date']);

        // Add offer fields mapped in config
        $this->addMappedFieldsToCollection($collection, $listing);

        // Add attribute corresponding to product-id if not setup as sku
        if (($productIdValueAttribute = $listing->getProductIdValueAttribute()) != 'sku') {
            $collection->addAttributeToSelect($productIdValueAttribute);
        }

        // Add potential attributes associated with offer additional fields
        $collection->addAdditionalFieldsAttributes($listing);

        $this->offerFactory->create()
            ->addOfferInfoToProductCollection($listing->getId(), $collection, ['offer_import_status']);

        $collection->load(); // Load collection to be able to use methods below
        $collection->overrideByParentData([], [], true);

        $stockId = 1;
        if ($this->isMsiEnabled) {
            /** @var \Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface $stockByWebsiteId */
            $stockByWebsiteId = $this->objectManager->get('Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface');
            $stockId = $stockByWebsiteId->execute($listing->getWebsiteId())->getStockId();
        }

        $data = [];
        foreach ($collection as $product) {
            $productId = $product['entity_id'];
            $product['qty'] = $this->overrideQty($product, $stockId);
            $data[$productId] = $this->prepareOffer($product, $listing);
        }

        // Mark out of stock products that are not in the export (out of stock, no price)
        $deleteIds = array_diff($listing->getProductIds(), array_keys($data));
        if (count($deleteIds)) {
            $collection = $this->productCollectionFactory->create();
            $collection->addFieldToSelect('sku')
                ->addAttributeToSelect('price')
                ->setStore($listing->getStoreId())
                ->addIdFilter($deleteIds);

            // Add offer fields mapped in config
            $this->addMappedFieldsToCollection($collection, $listing);

            // Add attribute corresponding to product-id if not setup as sku
            if (($productIdValueAttribute = $listing->getProductIdValueAttribute()) != 'sku') {
                $collection->addAttributeToSelect($productIdValueAttribute);
            }

            // Add potential attributes associated with offer additional fields
            $collection->addAdditionalFieldsAttributes($listing);

            // Add attribute corresponding to exported price if customized
            if ($exportedPricesAttr = $listing->getConnection()->getExportedPricesAttribute()) {
                $collection->addAttributeToSelect($exportedPricesAttr);
            }

            foreach ($collection as $product) {
                $productId = $product['entity_id'];
                $product['qty'] = 0; // Set quantity to zero, do not delete the offer in Mirakl
                $data[$productId] = $this->prepareOffer($product, $listing);
            }
        }

        return $data;
    }

    /**
     * @param   array   $product
     * @param   int     $stockId
     * @return  float|int
     */
    public function overrideQty(array $product, $stockId)
    {
        if (!$product['use_config_manage_stock'] && !$product['manage_stock']) {
            return self::NO_MANAGE_STOCK_QTY;
        }

        if ($product['offer_import_status'] == Offer::OFFER_DELETE) {
            // Set quantity to zero if offer has been flagged as "to delete"
            return 0;
        } elseif ($this->isMsiEnabled) {
            // Handle multi-source inventory if enabled
            /** @var \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface $getProductSalableQty */
            $getProductSalableQty = $this->objectManager->get('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');

            return $getProductSalableQty->execute($product['sku'], $stockId);
        }

        return $product['qty'];
    }
}
