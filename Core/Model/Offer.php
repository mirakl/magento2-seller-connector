<?php
namespace MiraklSeller\Core\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MiraklSeller\Core\Model\ResourceModel\ListingFactory as ListingResourceFactory;

/**
 * @method  string  getCreatedAt()
 * @method  $this   setCreatedAt(string $createdAt)
 * @method  int     getListingId()
 * @method  $this   setListingId(int $listingId)
 * @method  int     getOfferImportId()
 * @method  $this   setOfferImportId(int $offerImportId)
 * @method  string  getOfferImportStatus()
 * @method  $this   setOfferImportStatus(string $importStatus)
 * @method  string  getOfferErrorMessage()
 * @method  $this   setOfferErrorMessage(string $errorMessage)
 * @method  string  getOfferHash()
 * @method  $this   setOfferHash(string $offerHash)
 * @method  int     getProductId()
 * @method  $this   setProductId(int $productId)
 * @method  int     getProductImportId()
 * @method  $this   setProductImportId(int $productImportId)
 * @method  string  getProductImportStatus()
 * @method  $this   setProductImportStatus(string $importStatus)
 * @method  string  getProductImportMessage()
 * @method  $this   setProductImportMessage(string $importMessage)
 * @method  string  getUpdatedAt()
 * @method  $this   setUpdatedAt(string $updatedAt)
 */
class Offer extends AbstractModel
{
    const PRODUCT_NEW                   = 'NEW';
    const PRODUCT_PENDING               = 'PENDING';
    const PRODUCT_TRANSFORMATION_ERROR  = 'TRANSFORMATION_ERROR';
    const PRODUCT_WAITING_INTEGRATION   = 'WAITING_INTEGRATION';
    const PRODUCT_INTEGRATION_COMPLETE  = 'INTEGRATION_COMPLETE';
    const PRODUCT_INTEGRATION_ERROR     = 'INTEGRATION_ERROR';
    const PRODUCT_INVALID_REPORT_FORMAT = 'INVALID_REPORT_FORMAT';
    const PRODUCT_NOT_FOUND_IN_REPORT   = 'NOT_FOUND_IN_REPORT';
    const PRODUCT_SUCCESS               = 'SUCCESS';

    const OFFER_NEW     = 'NEW';
    const OFFER_PENDING = 'PENDING';
    const OFFER_SUCCESS = 'SUCCESS';
    const OFFER_ERROR   = 'ERROR';
    const OFFER_DELETE  = 'DELETE';

    /**
     * @var array
     */
    protected static $_productStatusLabels = [
        self::PRODUCT_NEW                    => 'Waiting for export',      // Product not exported yet to Mirakl
        self::PRODUCT_PENDING                => 'Waiting in Mirakl',       // Product exported, waiting for import in Mirakl
        self::PRODUCT_TRANSFORMATION_ERROR   => 'Transformation failed',   // Transformation failed for this product, fix mapping
        self::PRODUCT_WAITING_INTEGRATION    => 'Waiting for integration', // Transformation ok, waiting for operator integration
        self::PRODUCT_INTEGRATION_COMPLETE   => 'Integration complete',    // Integration complete in operator system
        self::PRODUCT_INTEGRATION_ERROR      => 'Integration failed',      // Integration failed in operator system
        self::PRODUCT_INVALID_REPORT_FORMAT  => 'Invalid report format',   // Integration cannot be confirmed because of invalid report format
        self::PRODUCT_NOT_FOUND_IN_REPORT    => 'Not found in report',     // Integration cannot be confirmed because of missing product in report
        self::PRODUCT_SUCCESS                => 'Import succeeded',        // Product successfully created in Mirakl
    ];

    /**
     * @var array
     */
    protected static $_offerStatusLabels = [
        self::OFFER_NEW     => 'Waiting for export', // Offer not exported yet to Mirakl
        self::OFFER_PENDING => 'Waiting in Mirakl',  // Offer exported, waiting for import in Mirakl
        self::OFFER_SUCCESS => 'Import succeeded',   // Offer successfully created in Mirakl
        self::OFFER_ERROR   => 'Import failed',      // Offer with error in Mirakl
        self::OFFER_DELETE  => 'To delete',          // Offer to delete in Mirakl
    ];

    /**
     * @var ListingFactory
     */
    protected $listingFactory;

    /**
     * @var ListingResourceFactory
     */
    protected $listingResourceFactory;

    /**
     * @var Listing
     */
    protected $listing;

    /**
     * @param   Context                 $context
     * @param   Registry                $registry
     * @param   ListingFactory          $listingFactory
     * @param   ListingResourceFactory  $listingResourceFactory
     * @param   AbstractResource|null   $resource
     * @param   AbstractDb|null         $resourceCollection
     * @param   array                   $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ListingFactory $listingFactory,
        ListingResourceFactory $listingResourceFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->listingFactory = $listingFactory;
        $this->listingResourceFactory = $listingResourceFactory;
    }

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Offer::class);
    }

    /**
     * @return  Listing
     */
    public function getListing()
    {
        if (null === $this->listing) {
            $this->listing = $this->listingFactory->create();
            $this->listingResourceFactory->create()->load($this->listing, $this->getListingId());
        }

        return $this->listing;
    }

    /**
     * @return  array
     */
    public static function getOfferStatuses()
    {
        return array_keys(self::getOfferStatusLabels());
    }

    /**
     * @return  array
     */
    public static function getOfferStatusLabels()
    {
        return self::$_offerStatusLabels;
    }

    /**
     * @return  array
     */
    public static function getProductErrorStatuses()
    {
        return [
            self::PRODUCT_TRANSFORMATION_ERROR,
            self::PRODUCT_INTEGRATION_ERROR,
        ];
    }

    /**
     * @return  array
     */
    public static function getProductStatuses()
    {
        return array_keys(self::getProductStatusLabels());
    }

    /**
     * @return  array
     */
    public static function getProductStatusLabels()
    {
        return self::$_productStatusLabels;
    }

    /**
     * @return  array
     */
    public static function getProductImportCompleteStatuses()
    {
        return [self::PRODUCT_WAITING_INTEGRATION, self::PRODUCT_INTEGRATION_COMPLETE];
    }

    /**
     * @return  array
     */
    public static function getProductImportFailedStatuses()
    {
        return [
            self::PRODUCT_TRANSFORMATION_ERROR,
            self::PRODUCT_INTEGRATION_ERROR,
            self::PRODUCT_INVALID_REPORT_FORMAT,
            self::PRODUCT_NOT_FOUND_IN_REPORT,
        ];
    }
}
