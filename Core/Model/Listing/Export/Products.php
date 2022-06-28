<?php
namespace MiraklSeller\Core\Model\Listing\Export;

use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Listing\Product as ProductHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Export\Formatter;
use MiraklSeller\Core\Model\ResourceModel\Product as ProductResource;

class Products extends AbstractExport
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var Formatter\Product
     */
    protected $productFormatter;

    /**
     * @var Formatter\PageBuilder
     */
    protected $pageBuilderFormatter;

    /**
     * @param   Config                  $config
     * @param   ProductHelper           $productHelper
     * @param   ProductResource         $productResource
     * @param   Formatter\Product       $productFormatter
     * @param   Formatter\PageBuilder   $pageBuilderFormatter
     */
    public function __construct(
        Config $config,
        ProductHelper $productHelper,
        ProductResource $productResource,
        Formatter\Product $productFormatter,
        Formatter\PageBuilder $pageBuilderFormatter
    ) {
        $this->config               = $config;
        $this->productHelper        = $productHelper;
        $this->productResource      = $productResource;
        $this->productFormatter     = $productFormatter;
        $this->pageBuilderFormatter = $pageBuilderFormatter;
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
        $chunkSize = $this->config->getAttributesChunkSize();
        $collections = $this->productHelper->getProductsDataCollections($listing, $chunkSize);

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
     * {@inheritdoc}
     */
    public function export(Listing $listing)
    {
        $data = $this->getListingProductsData($listing);

        $collection = $this->productHelper->getProductCollection($listing);
        $collection->load(); // Load collection to be able to use methods below
        $collection->addCategoryPaths();

        $allExportableAttributes = array_chunk($listing->getConnection()->getExportableAttributes(), 15);
        if (!empty($allExportableAttributes)) {
            foreach ($allExportableAttributes as $exportableAttributes) {
                $collection->overrideByParentData(['parent_sku' => 'sku'], $exportableAttributes);
                $collection->setFlag('parent_data_override', false);
            }
        } else {
            $collection->overrideByParentData(['parent_sku' => 'sku']);
        }

        $nbImageToExport = $this->config->getNumberImageMaxToExport();
        $variantsAttributes = $listing->getVariantsAttributes();

        $defaultProductData = $this->getDefaultProductData();

        if ($nbImageToExport >= 1) {
            $collection->setStoreId($listing->getStoreId());
            $collection->addMediaGalleryAttribute($nbImageToExport);
        }

        foreach ($collection as $product) {
            $productId = $product['entity_id'];
            $data[$productId] = array_merge(
                $defaultProductData,
                $data[$productId],
                $this->productFormatter->format($product, $listing)
            );

            $data[$productId] = $this->pageBuilderFormatter->format($data[$productId], $listing);

            // Extend parent code for specific listings
            if (!empty($data[$productId][Formatter\Product::VARIANT_GROUP_CODE_FIELD]) && count($variantsAttributes)) {
                $parentSku = $data[$productId][Formatter\Product::VARIANT_GROUP_CODE_FIELD];
                $productAxis = $this->productHelper->getProductAttributeAxis($parentSku);

                foreach ($variantsAttributes as $attributeCode) {
                    if (in_array($attributeCode, $productAxis)) {
                        $parentSku = sprintf(
                            '%s-%s',
                            $parentSku,
                            $data[$productId][$attributeCode]
                        );
                    }
                }

                $data[$productId][Formatter\Product::VARIANT_GROUP_CODE_FIELD] = $parentSku;
            }
        }

        return $data;
    }
}
