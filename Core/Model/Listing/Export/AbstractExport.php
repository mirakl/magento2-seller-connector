<?php
namespace MiraklSeller\Core\Model\Listing\Export;

use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Helper\Listing\Product as ProductHelper;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Offer as OfferFormatter;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Product as ProductFormatter;
use MiraklSeller\Core\Model\Listing\Export\AdditionalField\Formatter as AdditionalFieldFormatter;
use MiraklSeller\Core\Model\ResourceModel\Product as ProductResource;

abstract class AbstractExport implements ExportInterface
{
    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var OfferFormatter
     */
    protected $offerFormatter;

    /**
     * @var ProductFormatter
     */
    protected $productFormatter;

    /**
     * @var AdditionalFieldFormatter
     */
    protected $additionalFieldFormatter;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Config                      $config
     * @param   ProductHelper               $productHelper
     * @param   OfferFormatter              $offerFormatter
     * @param   ProductFormatter            $productFormatter
     * @param   AdditionalFieldFormatter    $additionalFieldFormatter
     * @param   ProductResource             $productResource
     */
    public function __construct(
        Config $config,
        ProductHelper $productHelper,
        OfferFormatter $offerFormatter,
        ProductFormatter $productFormatter,
        AdditionalFieldFormatter $additionalFieldFormatter,
        ProductResource $productResource
    ) {
        $this->config                   = $config;
        $this->productHelper            = $productHelper;
        $this->offerFormatter           = $offerFormatter;
        $this->productFormatter         = $productFormatter;
        $this->additionalFieldFormatter = $additionalFieldFormatter;
        $this->productResource          = $productResource;
    }

    /**
     * @param   null|string $value
     * @return  array
     */
    public function getDefaultProductData($value = null)
    {
        return array_fill_keys($this->productHelper->getAttributeCodes(), $value);
    }

    /**
     * @param   Listing $listing
     * @return  array
     */
    public function getListingProductsData(Listing $listing)
    {
        $collections = $this->productHelper->getProductsDataCollections($listing);

        $data = [];
        /** @var \MiraklSeller\Core\Model\ResourceModel\Product\Collection $collection */
        foreach ($collections as $collection) {
            foreach ($collection as $product) {
                $productId = $product['entity_id'];
                if (!isset($data[$productId])) {
                    $data[$productId] = [];
                }
                $data[$productId] += $product;
            }
        }

        // Remove useless attribute from catalog_product_entity base table
        $fields = $this->productResource->getProductBaseColumns();
        foreach (array_diff($fields, ['sku', 'entity_id']) as $field) {
            foreach ($data as &$product) {
                unset($product[$field]);
            }
        }

        return $data;
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
}
