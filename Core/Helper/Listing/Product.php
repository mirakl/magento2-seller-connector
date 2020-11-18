<?php
namespace MiraklSeller\Core\Helper\Listing;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Data;
use MiraklSeller\Core\Model\Listing as Listing;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Product as ProductResource;
use MiraklSeller\Core\Model\ResourceModel\Product\Collection as ProductCollection;
use MiraklSeller\Core\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Product extends Data
{
    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     * @param   ProductResource             $productResource
     * @param   OfferResourceFactory        $offerResourceFactory
     * @param   AttributeCollectionFactory  $attributeCollectionFactory
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   Config                      $config
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ProductResource $productResource,
        OfferResourceFactory $offerResourceFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        Config $config
    ) {
        parent::__construct($context, $storeManager);
        $this->productResource = $productResource;
        $this->offerResourceFactory = $offerResourceFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->config = $config;
    }

    /**
     * @return  array
     */
    public function getAttributeCodes()
    {
        return $this->productResource->getExportableAttributeCodes();
    }

    /**
     * @return  AttributeCollection
     */
    public function getAttributesToExport()
    {
        return $this->productResource->getExportableAttributes();
    }

    /**
     * Retrieve category path (as array) to use for a product that will be exported.
     * Rule is:
     * - take the deepest category
     * - if several categories have the same level, take the first one alphabetically
     *
     * @param   array   $paths
     * @return  array|false
     */
    public function getCategoryFromPaths(array $paths)
    {
        uasort($paths, function ($a1, $a2) {
            $sortByName = function ($a1, $a2) {
                for ($i = count($a1) - 1; $i >= 0; $i--) {
                    $compare = strcmp($a1[$i], $a2[$i]);
                    if (1 === $compare) {
                        return 1;
                    }
                }

                return -1;
            };

            return count($a1) > count($a2) ? -1 : (count($a1) < count($a2) ? 1 : $sortByName($a1, $a2));
        });

        return current($paths);
    }

    /**
     * @param   string  $productSku
     * @return  array
     */
    public function getProductAttributeAxis($productSku)
    {
        /** @var AttributeCollection $collection */
        $collection = $this->attributeCollectionFactory->create();

        $collection->getSelect()
            ->join(
                ['psa' => $collection->getTable('catalog_product_super_attribute')],
                'main_table.attribute_id = psa.attribute_id',
                'product_id'
            )
            ->join(
                ['p' => $collection->getTable('catalog_product_entity')],
                'psa.product_id = p.entity_id',
                'sku'
            )
            ->where('p.sku = ?', $productSku);

        $axisCodes = [];
        foreach ($collection as $attribute) {
            $axisCodes[] = $attribute->getAttributeCode();
        }

        return $axisCodes;
    }

    /**
     * @param   Listing $listing
     * @return  ProductCollection
     */
    public function getProductCollection(Listing $listing)
    {
        $productIds = $listing->getProductIds();

        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToSelect('sku')
            ->addIdFilter($productIds)
            ->setStore($listing->getStoreId());

        return $collection;
    }

    /**
     * @param   Listing $listing
     * @param   array   $productIds
     * @return  array
     */
    public function getProductIdsBySkus(Listing $listing, $productIds = null)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToSelect('sku')
            ->setStore($listing->getStoreId());

        if ($productIds) {
            $collection->addIdFilter($productIds);
        }

        $products = [];
        foreach ($collection as $id => $product) {
            $products[$product['sku']] = $id;
        }

        return $products;
    }

    /**
     * @param   Listing $listing
     * @param   int     $attrChunkSize
     * @return  ProductCollection[]
     */
    public function getProductsDataCollections($listing, $attrChunkSize = 15)
    {
        // Need to split into multiple collections because MySQL has a limited number of join possible for a query
        $collections = [];

        // Working with a limited chunk size because an attribute generates multiple joins
        $attributesChunks = array_chunk($this->getAttributesToExport()->getItems(), $attrChunkSize);

        /** @var EavAttribute[] $attributes */
        foreach ($attributesChunks as $attributes) {
            $collection = $this->getProductCollection($listing);
            foreach ($attributes as $attribute) {
                if ($this->isAttributeUsingOptions($attribute)) {
                    $collection->addAttributeOptionValue($attribute); // Add real option values and not ids
                } else {
                    $collection->addAttributeToSelect($attribute->getAttributeCode());
                }
            }
            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * @param   EavAttribute    $attribute
     * @return  bool
     */
    public function isAttributeUsingOptions(EavAttribute $attribute)
    {
        $model = $attribute->getSource();
        $backend = $attribute->getBackendType();

        return $attribute->usesSource() &&
            ($backend == 'int' && $model instanceof \Magento\Eav\Model\Entity\Attribute\Source\Table) ||
            ($backend == 'varchar' && $attribute->getFrontendInput() == 'multiselect');
    }

    /**
     * Marks failed products as new if failure delay has expired and returns the number of updated products
     *
     * @param   Listing $listing
     * @return  int
     */
    public function processFailedProducts(Listing $listing)
    {
        $delay = $this->config->getNbDaysKeepFailedProducts();
        /** @var \MiraklSeller\Core\Model\ResourceModel\Offer $offerResource */
        $offerResource = $this->offerResourceFactory->create();
        $productIds = $offerResource->getListingFailedProductIds($listing->getId(), $delay);

        return $offerResource->markProductsAsNew($listing->getId(), $productIds);
    }
}
