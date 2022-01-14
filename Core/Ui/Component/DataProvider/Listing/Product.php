<?php
namespace MiraklSeller\Core\Ui\Component\DataProvider\Listing;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider as BaseProductDataProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Ui\DataProvider\AddFieldToCollectionInterface;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use MiraklSeller\Core\Helper\Config as ConfigHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\ListingFactory as ListingFactory;
use MiraklSeller\Core\Model\ResourceModel\ListingFactory as ListingModelFactory;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Offer as OfferFormatter;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;

class Product extends BaseProductDataProvider
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var ListingFactory
     */
    protected $listingFactory;

    /**
     * @var ListingModelFactory
     */
    protected $listingModelFactory;

    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var OfferFormatter
     */
    protected $offerFormatter;

    /**
     * @var PoolInterface
     */
    protected $modifiersPool;

    /**
     * @var bool
     */
    protected $promotionComputed = false;

    /**
     * @var array
     */
    protected $offerColumns = [
        'product_id',
        'product_import_status',
        'product_import_id',
        'product_import_message',
        'offer_import_status',
        'offer_import_id',
        'offer_error_message',
    ];

    /**
     * @param string                            $name
     * @param string                            $primaryFieldName
     * @param string                            $requestFieldName
     * @param Registry                          $coreRegistry
     * @param ConfigHelper                      $configHelper
     * @param ListingFactory                    $listingFactory
     * @param ListingModelFactory               $listingModelFactory
     * @param CollectionFactory                 $collectionFactory
     * @param RequestInterface                  $request
     * @param OfferResourceFactory              $offerResourceFactory
     * @param OfferFormatter                    $offerFormatter
     * @param AddFieldToCollectionInterface[]   $addFieldStrategies
     * @param AddFilterToCollectionInterface[]  $addFilterStrategies
     * @param PoolInterface|null                $modifiersPool
     * @param array                             $meta
     * @param array                             $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Registry $coreRegistry,
        ConfigHelper $configHelper,
        ListingFactory $listingFactory,
        ListingModelFactory $listingModelFactory,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        OfferResourceFactory $offerResourceFactory,
        OfferFormatter $offerFormatter,
        PoolInterface $modifiersPool,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $collectionFactory,
            $addFieldStrategies, $addFilterStrategies, $meta, $data);

        $this->request = $request;
        $this->coreRegistry = $coreRegistry;
        $this->configHelper = $configHelper;
        $this->listingFactory = $listingFactory;
        $this->listingModelFactory = $listingModelFactory;
        $this->offerResourceFactory = $offerResourceFactory;
        $this->offerFormatter = $offerFormatter;
        $this->modifiersPool = $modifiersPool;

        $this->addListingInfo();

        $this->collection->addAttributeToSelect(['special_price' , 'special_from_date', 'special_to_date']);
        $this->collection->addStoreFilter($this->getListing()->getStoreId());
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }

        if (!$this->promotionComputed) {
            foreach ($this->getCollection() as $product) {
                $data = $this->offerFormatter->computePromotion(
                    $product->getPrice(),
                    $product->getFinalPrice(),
                    $product->getSpecialPrice(),
                    $product->getSpecialFromDate(),
                    $product->getSpecialToDate()
                );

                $exportedPricesAttr = $this->getListing()->getConnection()->getExportedPricesAttribute();
                if ($exportedPricesAttr && !empty($product->getData($exportedPricesAttr))) {
                    // If specific price field is set on the connection, use it and reset Magento calculated prices
                    $product->setPrice($product->getData($exportedPricesAttr));
                    $product->setFinalPrice($product->getData($exportedPricesAttr));
                    $data['discount_price'] = '';
                    $data['discount_start_date'] = '';
                    $data['discount_end_date'] = '';
                }

                $product->addData($data);
            }
            $this->promotionComputed = true;
        }

        $items = $this->getCollection()->toArray();

        $data = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];

        /** @var ModifierInterface $modifier */
        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    /**
     * @return void
     */
    protected function addListingInfo()
    {
        $listing = $this->getListing();

        if (!$listing->getId()) {
            return;
        }

        $this->offerResourceFactory->create()->addOfferInfoToProductCollection(
            $listing->getId(),
            $this->collection,
            $this->offerColumns
        );

        $this->coreRegistry->register('rule_data', new \Magento\Framework\DataObject([
            'store_id' => $listing->getStoreId(),
            'website_id' => $listing->getWebsiteId(),
            'customer_group_id' => $this->configHelper->getCustomerGroup()
        ]));

        $this->data['config']['update_url'] = sprintf(
            '%slisting_id/%s/',
            $this->data['config']['update_url'],
            $listing->getId()
        );
    }

    /**
     * @return  Listing|null
     */
    protected function getListing()
    {
        if (!$listingId = $this->request->getParam('listing_id')) {
            return null;
        }

        /** @var Listing $listing */
        $listing = $this->listingFactory->create();
        $this->listingModelFactory->create()->load($listing, $listingId);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = [];

        /** @var ModifierInterface $modifier */
        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
