<?php
namespace MiraklSeller\Core\Model\Listing\Export;

use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\ObjectManagerInterface;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Listing\Product as ProductHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Export\AdditionalField\Formatter as AdditionalFieldFormatter;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Offer as OfferFormatter;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Product as ProductFormatter;
use MiraklSeller\Core\Model\Offer;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory;
use MiraklSeller\Core\Model\ResourceModel\Product as ProductResource;
use MiraklSeller\Core\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Offers extends AbstractExport
{
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
     * @param   ProductFormatter            $productFormatter
     * @param   AdditionalFieldFormatter    $additionalFieldFormatter
     * @param   ProductResource             $productResource
     * @param   AttributeFactory            $attributeFactory
     * @param   OfferFactory                $offerFactory
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   ObjectManagerInterface      $objectManager
     */
    public function __construct(
        Config $config,
        ProductHelper $productHelper,
        OfferFormatter $offerFormatter,
        ProductFormatter $productFormatter,
        AdditionalFieldFormatter $additionalFieldFormatter,
        ProductResource $productResource,
        AttributeFactory $attributeFactory,
        OfferFactory $offerFactory,
        ProductCollectionFactory $productCollectionFactory,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct(
            $config,
            $productHelper,
            $offerFormatter,
            $productFormatter,
            $additionalFieldFormatter,
            $productResource
        );

        $this->attributeFactory         = $attributeFactory;
        $this->offerFactory             = $offerFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->objectManager            = $objectManager;
        $this->isMsiEnabled             = $objectManager->get(\MiraklSeller\Core\Helper\Data::class)->isMsiEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function export(Listing $listing)
    {
        $collection = $this->productHelper->getProductCollection($listing);
        $collection->addTierPricesToSelect($listing->getWebsiteId(), $this->config->getCustomerGroup())
            ->addListingPriceData($listing)
            ->addQuantityToSelect()
            ->addAttributeToSelect(['description', 'special_price', 'special_from_date', 'special_to_date']);

        // Add mapped attributes to select
        foreach ($this->config->getOfferFieldsMapping($listing->getStoreId()) as $value) {
            if ($value) {
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

            // Add attribute corresponding to product-id if not setup as sku
            if (($productIdValueAttribute = $listing->getProductIdValueAttribute()) != 'sku') {
                $collection->addAttributeToSelect($productIdValueAttribute);
            }

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
        if ($product['offer_import_status'] == Offer::OFFER_DELETE) {
            // Set quantity to zero if offer has been flagged as "to delete"
            return 0;
        } elseif ($this->isMsiEnabled) {
            /** @var \Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface $isSingleSourceMode */
            $isSingleSourceMode = $this->objectManager->get('Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface');
            if (!$isSingleSourceMode->execute()) {
                // Handle multi-source inventory if enabled
                /** @var \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface $getProductSalableQty */
                $getProductSalableQty = $this->objectManager->get('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');

                return $getProductSalableQty->execute($product['sku'], $stockId);
            }
        }

        return $product['qty'];
    }
}
