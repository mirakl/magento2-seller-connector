<?php
namespace MiraklSeller\Core\Helper\Tracking;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Data;
use MiraklSeller\Core\Helper\Listing\Product as ProductListingHelper;
use MiraklSeller\Core\Model\Listing\Tracking\Offer as OfferTracking;
use MiraklSeller\Core\Model\Listing\Tracking\Product as ProductTracking;
use MiraklSeller\Core\Model\Listing\Tracking\Status\Product as ProductStatus;
use MiraklSeller\Core\Model\Offer;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\ProductFactory as ProductTrackingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product\CollectionFactory as ProductTrackingCollectionFactory;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;

class Product extends Data
{
    use \MiraklSeller\Core\Helper\CsvTrait;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var ProductListingHelper
     */
    protected $productListingHelper;

    /**
     * @var ProductTrackingCollectionFactory
     */
    protected $productTrackingCollectionFactory;

    /**
     * @var ProductTrackingResourceFactory
     */
    protected $productTrackingResourceFactory;

    /**
     * @var string
     */
    protected $notFoundInReportMessage =
        'Product not found in marketplace product reports. ' .
        'Try to export prices & stocks. ' .
        'If the error "product not found" is returned, try to contact the marketplace. '.
        'To export the product again, mark it as "to export".';

    /**
     * @var string
     */
    protected $invalidReportFormatMessage =
        'Marketplace product reports cannot be processed. ' .
        'Download the report manually and verify your marketplace report configuration in the connection page. ' .
        'To export the product again, mark it as "to export".';

    /**
     * @param   Context                             $context
     * @param   StoreManagerInterface               $storeManager
     * @param   Config                              $config
     * @param   OfferResourceFactory                $offerResourceFactory
     * @param   ProductListingHelper                $productListingHelper
     * @param   ProductTrackingCollectionFactory    $productTrackingCollectionFactory
     * @param   ProductTrackingResourceFactory      $productTrackingResourceFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        OfferResourceFactory $offerResourceFactory,
        ProductListingHelper $productListingHelper,
        ProductTrackingCollectionFactory $productTrackingCollectionFactory,
        ProductTrackingResourceFactory $productTrackingResourceFactory
    ) {
        parent::__construct($context, $storeManager);
        $this->config = $config;
        $this->offerResourceFactory = $offerResourceFactory;
        $this->productListingHelper = $productListingHelper;
        $this->productTrackingCollectionFactory = $productTrackingCollectionFactory;
        $this->productTrackingResourceFactory = $productTrackingResourceFactory;
    }

    /**
     * @param   ProductTracking $tracking
     * @param   array           $withStatus
     * @param   string          $setDefaultStatus
     * @param   string          $setDefaultMessage
     * @return  array
     */
    protected function _getTrackingProducts(
        ProductTracking $tracking,
        $withStatus = [Offer::PRODUCT_PENDING],
        $setDefaultStatus = Offer::PRODUCT_WAITING_INTEGRATION,
        $setDefaultMessage = ''
    ) {
        $products = $this->offerResourceFactory->create()->getListingPendingProducts(
            $tracking->getListingId(),
            $tracking->getImportId(),
            ['product_id', 'id', 'product_import_status', 'product_import_message'],
            $withStatus
        );

        $now = date('Y-m-d H:i:s');
        foreach ($products as &$data) {
            $data['updated_at'] = $now;
            if ($setDefaultStatus) {
                $data['product_import_status'] = $setDefaultStatus;
            }
            if ($setDefaultMessage) {
                $data['product_import_message'] = $setDefaultMessage;
            }
        }
        unset($data);

        return $products;
    }

    /**
     * @param   ProductTracking $tracking
     * @param   string          $importStatus
     * @return  int
     */
    public function updateProductStatusFromImportStatus(ProductTracking $tracking, $importStatus)
    {
        $errorMsg = '';

        switch ($importStatus) {
            case ProductStatus::SENT:
                $finalStatus = Offer::PRODUCT_WAITING_INTEGRATION;
                break;
            case ProductStatus::COMPLETE:
                $finalStatus = Offer::PRODUCT_NOT_FOUND_IN_REPORT;
                $errorMsg = __($this->notFoundInReportMessage);
                break;
            case ProductStatus::CANCELLED:
            case ProductStatus::EXPIRED:
            case ProductStatus::FAILED:
                $finalStatus = Offer::PRODUCT_INTEGRATION_ERROR;
                $errorMsg = __($this->notFoundInReportMessage);
                break;
            default:
                return 0;
        }

        // Update only products that are in PENDING or WAITING_INTEGRATION status for this tracking product import id
        $where = [
            'listing_id'                   => $tracking->getListingId(),
            'product_import_id'            => $tracking->getImportId(),
            'product_import_status IN (?)' => [Offer::PRODUCT_PENDING, Offer::PRODUCT_WAITING_INTEGRATION],
        ];

        // Initialize data to update
        $data = [
            'updated_at'                => date('Y-m-d H:i:s'),
            'product_import_status'     => $finalStatus,
            'product_import_message'    => $errorMsg,
        ];

        return $this->offerResourceFactory->create()->update($data, $where);
    }

    /**
     * Returns number of updated products according to product integration error report from P44
     *
     * @param   ProductTracking $tracking
     * @return  int
     */
    public function processIntegrationErrorReport(ProductTracking $tracking)
    {
        // Create a temp file in order to parse CSV data easily
        $file = $this->createCsvFileFromString($tracking->getIntegrationErrorReport());

        return $this->processIntegrationErrorReportFile($file, $tracking);
    }

    /**
     * Process product integration error report file from P44 and returns number of updated products
     *
     * @param   \SplFileObject  $file
     * @param   ProductTracking $tracking
     * @return  int
     */
    public function processIntegrationErrorReportFile(\SplFileObject $file, ProductTracking $tracking)
    {
        return $this->processIntegrationReportFile($file, $tracking, Offer::PRODUCT_INTEGRATION_ERROR);
    }

    /**
     * Returns number of updated products according to new product report from P45
     *
     * @param   ProductTracking $tracking
     * @return  int
     */
    public function processIntegrationSuccessReport(ProductTracking $tracking)
    {
        // Create a temp file in order to parse CSV data easily
        $file = $this->createCsvFileFromString($tracking->getIntegrationSuccessReport());

        return $this->processIntegrationReportFile($file, $tracking, Offer::PRODUCT_INTEGRATION_COMPLETE);
    }

    /**
     * Returns number of updated products
     *
     * @param   \SplFileObject  $file
     * @param   ProductTracking $tracking
     * @param   string          $finalStatus
     * @return  int
     */
    public function processIntegrationReportFile(\SplFileObject $file, ProductTracking $tracking, $finalStatus)
    {
        $integrationPendingStatuses = [
            Offer::PRODUCT_PENDING,
            Offer::PRODUCT_WAITING_INTEGRATION,
            Offer::PRODUCT_INVALID_REPORT_FORMAT,
            Offer::PRODUCT_NOT_FOUND_IN_REPORT,
        ];

        // Retrieve pending products data associated with the tracking product import id
        $products = $this->_getTrackingProducts(
            $tracking,
            $integrationPendingStatuses,
            Offer::PRODUCT_WAITING_INTEGRATION
        );

        // No product to process
        if (empty($products)) {
            return 0;
        }

        $listing    = $tracking->getListing();
        $connection = $listing->getConnection();
        $productIds = array_keys($products);

        // Check report validity
        $file->rewind();
        $cols = $file->fgetcsv();

        if ($finalStatus === Offer::PRODUCT_INTEGRATION_ERROR) {
            $shopSkuColumn = $connection->getSkuCode();
            $errorsColumn  = $connection->getErrorsCode();
        } else {
            $shopSkuColumn = $connection->getSuccessSkuCode();
            $errorsColumn  = $connection->getMessagesCode();
        }

        // If integration report is not valid, mark all products as INVALID_REPORT_FORMAT and quit
        if (!$this->isCsvFileValid($file) || !in_array($shopSkuColumn, $cols)) {
            return $this->offerResourceFactory->create()
                ->updateProducts($listing->getId(), $productIds, [
                    'product_import_status'  => Offer::PRODUCT_INVALID_REPORT_FORMAT,
                    'product_import_message' => __($this->invalidReportFormatMessage),
                ]);
        }

        $productIdsBySkus = $this->productListingHelper->getProductIdsBySkus($listing, $productIds);

        // Loop on CSV file
        $file->rewind();
        $file->fgetcsv(); // Ignore first line that contains column names
        while ($row = $file->fgetcsv()) {
            $data = array_combine($cols, $row);

            $productSku = $data[$shopSkuColumn];

            if (!isset($productIdsBySkus[$productSku])) {
                continue;
            }

            $productId = $productIdsBySkus[$productSku];
            if (isset($products[$productId])) {
                $errorMessage = isset($data[$errorsColumn]) ? trim($data[$errorsColumn]) : '';
                $products[$productId]['product_import_status']  = $finalStatus;
                $products[$productId]['product_import_message'] = $errorMessage;
            }
        }

        // Update all offers in an unique query
        return $this->offerResourceFactory->create()->updateMultiple($products);
    }

    /**
     * Updates product status to SUCCESS if offer status is SUCCESS.
     * Returns number of updated products.
     *
     * @param   OfferTracking   $tracking
     * @return  int
     */
    public function updateProductStatusFromOffer(OfferTracking $tracking)
    {
        // Update ALL products for this tracking product import id where import status is SUCCESS
        $where = [
            'listing_id'                 => $tracking->getListingId(),
            'offer_import_id'            => $tracking->getImportId(),
            'product_import_status != ?' => Offer::PRODUCT_SUCCESS,
            'offer_import_status = ?'    => Offer::OFFER_SUCCESS,
        ];

        // Initialize data to update
        $data = [
            'updated_at'             => date('Y-m-d H:i:s'),
            'product_import_status'  => Offer::PRODUCT_SUCCESS,
            'product_import_message' => '',
        ];

        // If offer status is SUCCESS in Mirakl, it means that product has been imported successfully
        return $this->offerResourceFactory->create()->update($data, $where);
    }

    /**
     * Returns number of updated products according to error report from P47
     *
     * @param   ProductTracking $tracking
     * @return  int
     */
    public function processTransformationErrorReport(ProductTracking $tracking)
    {
        // Create a temp file in order to parse CSV data easily
        $file = $this->createCsvFileFromString($tracking->getTransformationErrorReport());

        return $this->processTransformationErrorReportFile($file, $tracking);
    }

    /**
     * Returns number of updated products according to error report file (P47)
     *
     * @param   \SplFileObject  $file
     * @param   ProductTracking $tracking
     * @return  int
     */
    public function processTransformationErrorReportFile(
        \SplFileObject $file,
        ProductTracking $tracking
    ) {
        // Retrieve pending products data associated with the tracking product import id
        $products = $this->_getTrackingProducts($tracking);

        // Loop on CSV file
        $cols = $file->fgetcsv();
        while ($row = $file->fgetcsv()) {
            $data = array_combine($cols, $row);
            $productId = $data['entity_id'];
            if (isset($products[$productId])) {
                $warnings = !empty($data['warnings']) ? $data['warnings'] : '';
                $errors = !empty($data['errors']) ? $data['errors'] : '';
                if (!empty($errors)) {
                    $products[$productId]['product_import_status'] = Offer::PRODUCT_TRANSFORMATION_ERROR;
                }
                $products[$productId]['product_import_message'] = trim($warnings . "\n" . $errors);
            }
        }

        // Update all offers in an unique query
        return $this->offerResourceFactory->create()->updateMultiple($products);
    }

    /**
     * Process expired products with diff between (now() - nb days in Magento configuration) and last tracking product creation date
     *   -> Change product statuses from "Waiting for integration" to "Waiting for export"
     *   -> Change last tracking product status to "Integration expired"
     *
     * @param   int                     $listingId
     * @param   ProductTracking|null    $lastTrackingProduct
     * @return  int
     */
    public function processExpiredProducts($listingId, ProductTracking $lastTrackingProduct = null)
    {
        $nbUpdatedProducts = 0;

        if (empty($lastTrackingProduct)) {
            // Retrieve last product tracking
            $lastTrackingProduct = $this->productTrackingCollectionFactory->create()
                ->getLastProductTrackingForListing($listingId)
                ->getFirstItem();
        }

        if ($lastTrackingProduct->getId()) {
            $today = new \DateTime();
            $interval = sprintf('P%dD', $this->config->getNbDaysExpired());
            $compareDate = $today->sub(new \DateInterval($interval));

            if (new \DateTime($lastTrackingProduct->getCreatedAt()) < $compareDate) {
                $offerResource = $this->offerResourceFactory->create();
                $waitingProductIds = $offerResource->getListingProductIds($listingId, null, [Offer::PRODUCT_WAITING_INTEGRATION]);

                if (is_array($waitingProductIds) && !empty($waitingProductIds)) {
                    // Update offers with status Waiting for export
                    $nbUpdatedProducts = $offerResource->markProductsAsNew($listingId, $waitingProductIds);

                    if ($nbUpdatedProducts > 0) {
                        $lastTrackingProduct->setImportStatus(ProductStatus::EXPIRED);
                        $this->productTrackingResourceFactory->create()->save($lastTrackingProduct);
                    }
                }
            }
        }

        return $nbUpdatedProducts;
    }
}
