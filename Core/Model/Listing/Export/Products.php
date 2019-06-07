<?php
namespace MiraklSeller\Core\Model\Listing\Export;

use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Product as ProductFormatter;

class Products extends AbstractExport
{
    /**
     * {@inheritdoc}
     */
    public function export(Listing $listing)
    {
        $data = $this->getListingProductsData($listing);

        $collection = $this->productHelper->getProductCollection($listing);
        $collection->load(); // Load collection to be able to use methods below
        $collection->addCategoryPaths();

        $exportableAttributes = $listing->getConnection()->getExportableAttributes();
        $collection->overrideByParentData(['parent_sku' => 'sku'], $exportableAttributes);

        $nbImageToExport = $this->config->getNumberImageMaxToExport();
        $variantsAttributes = $listing->getVariantsAttributes();

        $defaultProductData = $this->getDefaultProductData();

        if ($nbImageToExport >= 1) {
            $collection->addMediaGalleryAttribute($nbImageToExport);
        }

        foreach ($collection as $product) {
            $productId = $product['entity_id'];
            $data[$productId] = array_merge(
                $defaultProductData,
                $data[$productId],
                $this->productFormatter->format($product, $listing)
            );

            // Extend parent code for specific listings
            if (!empty($data[$productId][ProductFormatter::VARIANT_GROUP_CODE_FIELD]) && count($variantsAttributes)) {
                $parentSku = $data[$productId][ProductFormatter::VARIANT_GROUP_CODE_FIELD];
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

                $data[$productId][ProductFormatter::VARIANT_GROUP_CODE_FIELD] = $parentSku;
            }
        }

        return $data;
    }
}