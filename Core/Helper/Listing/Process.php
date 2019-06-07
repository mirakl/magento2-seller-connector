<?php
namespace MiraklSeller\Core\Helper\Listing;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Api\Helper\Offer as OfferApi;
use MiraklSeller\Api\Helper\Product as ProductApi;
use MiraklSeller\Core\Helper\Data;
use MiraklSeller\Core\Helper\Listing\Product as ProductHelper;
use MiraklSeller\Core\Helper\Tracking\Product as ProductTrackingHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\ListingFactory;
use MiraklSeller\Core\Model\Listing\Export\Offers;
use MiraklSeller\Core\Model\Listing\Export\Products;
use MiraklSeller\Core\Model\Listing\Tracking\OfferFactory as OfferTrackingFactory;
use MiraklSeller\Core\Model\Listing\Tracking\ProductFactory as ProductTrackingFactory;
use MiraklSeller\Core\Model\Offer;
use MiraklSeller\Core\Model\Offer\Loader as OfferLoader;
use MiraklSeller\Core\Model\ResourceModel\ListingFactory as ListingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\OfferFactory as OfferTrackingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\ProductFactory as ProductTrackingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use MiraklSeller\Process\Model\Process as ProcessModel;

class Process extends Data
{
    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var ListingFactory
     */
    protected $listingFactory;

    /**
     * @var ListingResourceFactory
     */
    protected $listingResourceFactory;

    /**
     * @var Offers
     */
    protected $offers;

    /**
     * @var OfferLoader
     */
    protected $offerLoader;

    /**
     * @var OfferApi
     */
    protected $offerApi;

    /**
     * @var OfferTrackingFactory
     */
    protected $offerTrackingFactory;

    /**
     * @var OfferTrackingResourceFactory
     */
    protected $offerTrackingResourceFactory;

    /**
     * @var Products
     */
    protected $products;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var ProductTrackingHelper
     */
    protected $productTrackingHelper;

    /**
     * @var ProductApi
     */
    protected $productApi;

    /**
     * @var ProductTrackingFactory
     */
    protected $productTrackingFactory;

    /**
     * @var ProductTrackingResourceFactory
     */
    protected $productTrackingResourceFactory;

    /**
     * @param   Context                         $context
     * @param   StoreManagerInterface           $storeManager
     * @param   OfferResourceFactory            $offerResourceFactory
     * @param   ListingFactory                  $listingFactory
     * @param   ListingResourceFactory          $listingResourceFactory
     * @param   Offers                          $offers
     * @param   OfferLoader                     $offerLoader
     * @param   OfferApi                        $offerApi
     * @param   OfferTrackingFactory            $offerTrackingFactory
     * @param   OfferTrackingResourceFactory    $offerTrackingResourceFactory
     * @param   Products                        $products
     * @param   ProductHelper                   $productHelper
     * @param   ProductTrackingHelper           $productTrackingHelper
     * @param   ProductApi                      $productApi
     * @param   ProductTrackingFactory          $productTrackingFactory
     * @param   ProductTrackingResourceFactory  $productTrackingResourceFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        OfferResourceFactory $offerResourceFactory,
        ListingResourceFactory $listingResourceFactory,
        ListingFactory $listingFactory,
        Offers $offers,
        OfferLoader $offerLoader,
        OfferApi $offerApi,
        OfferTrackingFactory $offerTrackingFactory,
        OfferTrackingResourceFactory $offerTrackingResourceFactory,
        Products $products,
        ProductHelper $productHelper,
        ProductTrackingHelper $productTrackingHelper,
        ProductApi $productApi,
        ProductTrackingFactory $productTrackingFactory,
        ProductTrackingResourceFactory $productTrackingResourceFactory
    ) {
        parent::__construct($context, $storeManager);
        $this->offerResourceFactory = $offerResourceFactory;
        $this->listingFactory = $listingFactory;
        $this->listingResourceFactory = $listingResourceFactory;
        $this->offers = $offers;
        $this->offerLoader = $offerLoader;
        $this->offerApi = $offerApi;
        $this->offerTrackingFactory = $offerTrackingFactory;
        $this->offerTrackingResourceFactory = $offerTrackingResourceFactory;
        $this->products = $products;
        $this->productHelper = $productHelper;
        $this->productTrackingHelper = $productTrackingHelper;
        $this->productApi = $productApi;
        $this->productTrackingFactory = $productTrackingFactory;
        $this->productTrackingResourceFactory = $productTrackingResourceFactory;
    }

    /**
     * @param   ProcessModel $process
     * @param   int     $listingId
     */
    public function refresh(ProcessModel $process, $listingId)
    {
        /** @var Listing $listing */
        $listing = $this->listingFactory->create();
        $this->listingResourceFactory->create()->load($listing, $listingId);

        $process->output(__('Refreshing products of listing #%1 ...', $listing->getId()), true);

        // Retrieve listing's product ids in order to filter collection
        $productIds = $listing->build();

        $process->output(__('Found %1 product(s) matching listing conditions', count($productIds)));

        $process->output(__('Updating products and offers ...'));
        $this->offerLoader->load($listing->getId(), $productIds);

        $process->output(__('Done!'));
    }

    /**
     * @param   ProcessModel    $process
     * @param   int             $listingId
     * @param   bool            $full
     * @param   bool            $createTracking
     * @param   array           $productIds
     */
    public function exportOffer(
        ProcessModel $process,
        $listingId,
        $full = true,
        $createTracking = true,
        array $productIds = []
    ) {
        /** @var Listing $listing */
        $listing = $this->listingFactory->create();
        $this->listingResourceFactory->create()->load($listing, $listingId);
        $listing->validate();

        // Retrieve product ids associated with the listing and with offer import status set to NEW, SUCCESS, ERROR or DELETE
        /** @var \MiraklSeller\Core\Model\ResourceModel\Offer $offerResource */
        $offerResource = $this->offerResourceFactory->create();
        $offerStatuses = [Offer::OFFER_NEW, Offer::OFFER_SUCCESS, Offer::OFFER_ERROR, Offer::OFFER_DELETE];
        $where = ['offer_import_status IN (?)' => $offerStatuses];
        if (!empty($productIds)) {
            // Retrieve only products specified
            $where['product_id IN (?)'] = $productIds;
        }
        $cols = ['product_id', 'id', 'offer_hash'];
        $updateProducts = $offerResource->getListingProducts($listing->getId(), $where, $cols);

        if (empty($updateProducts)) {
            $process->output(__('No offer to export'));
            return;
        }

        // Filter listing product ids with only offer with status NEW, SUCCESS, ERROR or DELETE
        $listing->setProductIds(array_keys($updateProducts));

        // Retrieve offers data to export
        $process->output(__('Exporting offers of listing #%1 ...', $listing->getId()), true);

        $data = $this->offers->export($listing);

        $process->output(__('  => Found %1 product(s) to export', count($data)));

        // Calculate hashes of offers data to import only modified ones later if in delta mode
        foreach ($data as $productId => $values) {
            // serialize and sha1 seem better than json_encode and md5 (hashing 100k+ products takes less than 1s)
            $hash = sha1(serialize($values));

            if ($full || $updateProducts[$productId]['offer_hash'] !== $hash) {
                // Update hash if full import mode or if offer's hash has changed
                $updateProducts[$productId]['offer_hash'] = $hash;
            } else {
                // We are doing a delta update so we remove products from being imported in Mirakl if hash did not change
                unset($updateProducts[$productId]);
                unset($data[$productId]);
            }
        }

        if (!$full) {
            $process->output(__('Only modified offers will be imported in Mirakl ...'));
            $process->output(__('  => Found %1 product(s) available for export', count($updateProducts)));
        }

        if (empty($data)) {
            $process->output(__('No offer to export'));
            return;
        }

        // Export data to Mirakl through API OF01
        $process->output(__('Sending file to Mirakl through API OF01 ...'));
        $result = $this->offerApi->importOffers($listing->getConnection(), $data);

        // Update hash of imported offers in db
        $offerResource->updateMultiple($updateProducts);

        // Set offers status to PENDING for exported product ids and import tracking id
        $exportedProductIds = array_keys($data);
        $offerResource->deleteListingOffers($listing->getId(), Offer::OFFER_DELETE); // Remove offers with status DELETE
        $offerResource->markOffersAsPending($listing->getId(), $exportedProductIds, $result->getImportId());

        // Update listing last export date
        $listing->setLastExportDate(date('Y-m-d H:i:s'));
        $this->listingResourceFactory->create()->save($listing);

        // Create a tracking if needed
        if ($createTracking) {
            $tracking = $this->offerTrackingFactory->create();
            $tracking->setListingId($listing->getId())
                ->setImportId($result->getImportId());
            $this->offerTrackingResourceFactory->create()->save($tracking);
            $process->output(__('New prices & stocks tracking created (id: %1)', $tracking->getId()));
        }

        $process->output(__('Done!'));
    }

    /**
     * @param   ProcessModel    $process
     * @param   int             $listingId
     * @param   string          $productMode
     * @param   bool            $createTracking
     */
    public function exportProduct(
        ProcessModel $process,
        $listingId,
        $productMode = Listing::PRODUCT_MODE_PENDING,
        $createTracking = true
    ) {
        $listing = $this->listingFactory->create();
        $this->listingResourceFactory->create()->load($listing, $listingId);
        $listing->validate();

        if ($productMode == Listing::PRODUCT_MODE_ALL) {
            $productStatus = Offer::getProductStatuses();
        } elseif ($productMode == Listing::PRODUCT_MODE_ERROR) {
            $productStatus = Offer::getProductImportFailedStatuses();
        } else {
            // Process expired products with Magento configuration nb_days_expired
            $expiredProducts = $this->productTrackingHelper->processExpiredProducts($listingId);
            $process->output(__('Expiring products of listing #%1 ... %2 expired products', $listing->getId(), $expiredProducts));

            // Process failed products with Magento configuration nb_days_keep_failed_products
            $nbFailedProductsUpdated = $this->productHelper->processFailedProducts($listing);
            $process->output(__(
                'Marking failed products of listing #%1 as "to export" (failure period expired) ... %2 product(s) updated',
                $listing->getId(),
                $nbFailedProductsUpdated
            ));

            $productStatus = [Offer::PRODUCT_NEW];
        }

        // Retrieve product ids associated with the listing and with product import status set to NEW
        /** @var \MiraklSeller\Core\Model\ResourceModel\Offer $offerResource */
        $offerResource = $this->offerResourceFactory->create();
        $productIds = $offerResource->getListingProductIds($listing->getId(), null, $productStatus);

        if (empty($productIds)) {
            $process->output(__('No product to export'));
            return;
        }

        // Filter listing products
        $listing->setProductIds($productIds);

        // Retrieve offers and products data to export
        $process->output(__('Exporting products of listing #%1 ...', $listing->getId()), true);

        $data = $this->products->export($listing);

        if (empty($data)) {
            $process->output(__('No product to export'));
            return;
        }

        $process->output(__('  => Found %1 product(s) to export', count($data)));

        // Export data to Mirakl through API P41
        $process->output(__('Sending file to Mirakl through API P41 ...'));
        $result = $this->productApi->importProducts($listing->getConnection(), $data);

        // Set offers and products status to PENDING for exported product ids and import tracking id
        $exportedProductIds = array_keys($data);
        $offerResource->markProductsAsPending($listing->getId(), $exportedProductIds, $result->getImportId());

        // Update listing last export date
        $listing->setLastExportDate(date('Y-m-d H:i:s'));
        $this->listingResourceFactory->create()->save($listing);

        // Create a tracking if needed
        if ($createTracking) {
            /** @var \MiraklSeller\Core\Model\Listing\Tracking\Product $tracking */
            $tracking = $this->productTrackingFactory->create();
            $tracking->setListingId($listing->getId())
                ->setImportId($result->getImportId());
            $this->productTrackingResourceFactory->create()->save($tracking);
            $process->output(__('New products tracking created (id: %1)', $tracking->getId()));
        }

        $process->output(__('Done!'));
    }
}
