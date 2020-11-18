<?php
namespace MiraklSeller\Core\Helper\Tracking;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Api\Helper\Offer as OfferApiHelper;
use MiraklSeller\Api\Helper\Product as ProductApiHelper;
use MiraklSeller\Core\Helper\Data;
use MiraklSeller\Core\Model\Listing\Tracking\Offer as OfferTracking;
use MiraklSeller\Core\Model\Listing\Tracking\Product as ProductTracking;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\OfferFactory as OfferTrackingFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\ProductFactory as ProductTrackingFactory;

class Api extends Data
{
    /**
     * @var OfferApiHelper
     */
    protected $offerApiHelper;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;

    /**
     * @var OfferTrackingFactory
     */
    protected $offerTrackingFactory;

    /**
     * @var ProductTrackingFactory
     */
    protected $productTrackingFactory;

    /**
     * @param   Context                 $context
     * @param   StoreManagerInterface   $storeManager
     * @param   OfferApiHelper          $offerApiHelper
     * @param   ProductApiHelper        $productApiHelper,
     * @param   OfferTrackingFactory    $offerTrackingFactory
     * @param   ProductTrackingFactory  $productTrackingFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        OfferApiHelper $offerApiHelper,
        ProductApiHelper $productApiHelper,
        OfferTrackingFactory $offerTrackingFactory,
        ProductTrackingFactory $productTrackingFactory
    ) {
        parent::__construct($context, $storeManager);
        $this->offerApiHelper = $offerApiHelper;
        $this->productApiHelper = $productApiHelper;
        $this->offerTrackingFactory = $offerTrackingFactory;
        $this->productTrackingFactory = $productTrackingFactory;
    }

    /**
     * @param   OfferTracking   $tracking
     * @return  \Mirakl\Core\Domain\FileWrapper
     */
    public function updateOfferErrorReport(OfferTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API OF03 to get import error report
        $result = $this->offerApiHelper
            ->getOffersImportErrorReport($listing->getConnection(), $tracking->getImportId());

        $file = $result->getFile();
        $file->rewind();

        // Save import error report in tracking
        $tracking->setErrorReport($file->fread($file->fstat()['size']));
        $this->offerTrackingFactory->create()->save($tracking);

        return $result;
    }

    /**
     * @param   OfferTracking   $tracking
     * @return  \Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferImportResult
     */
    public function updateOfferTrackingStatus(OfferTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API OF02 to get import result
        $result = $this->offerApiHelper
            ->getOffersImportResult($listing->getConnection(), $tracking->getImportId());

        // Save import status in tracking
        $tracking->setImportStatus($result->getStatus());
        $this->offerTrackingFactory->create()->save($tracking);

        return $result;
    }

    /**
     * @param   ProductTracking     $tracking
     * @return  \Mirakl\Core\Domain\FileWrapper
     */
    public function updateProductIntegrationErrorReport(ProductTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API P44 to get integration error report
        $result = $this->productApiHelper
            ->getProductsIntegrationErrorReport($listing->getConnection(), $tracking->getImportId());

        $file = $result->getFile();
        $file->rewind();

        // Save integration error report in tracking
        $tracking->setIntegrationErrorReport($file->fread($file->fstat()['size']));
        $this->productTrackingFactory->create()->save($tracking);

        return $result;
    }

    /**
     * @param   ProductTracking     $tracking
     * @return  \Mirakl\Core\Domain\FileWrapper
     */
    public function updateProductIntegrationSuccessReport(ProductTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API P45 to get new product integration report
        $result = $this->productApiHelper
            ->getNewProductsIntegrationReport($listing->getConnection(), $tracking->getImportId());

        $file = $result->getFile();
        $file->rewind();

        // Save integration success report in tracking
        $tracking->setIntegrationSuccessReport($file->fread($file->fstat()['size']));
        $this->productTrackingFactory->create()->save($tracking);

        return $result;
    }

    /**
     * @param   ProductTracking     $tracking
     * @return  \Mirakl\Core\Domain\FileWrapper
     */
    public function updateProductTransformationErrorReport(ProductTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API P47 to get transformation error report
        $result = $this->productApiHelper
            ->getProductsTransformationErrorReport($listing->getConnection(), $tracking->getImportId());

        $file = $result->getFile();
        $file->rewind();

        // Save transformation error report in tracking
        $tracking->setTransformationErrorReport($file->fread($file->fstat()['size']));
        $this->productTrackingFactory->create()->save($tracking);

        return $result;
    }

    /**
     * @param   ProductTracking     $tracking
     * @return  \Mirakl\MCI\Common\Domain\Product\ProductImportResult
     */
    public function updateProductTrackingStatus(ProductTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API P42 to get import result
        $result = $this->productApiHelper
            ->getProductImportResult($listing->getConnection(), $tracking->getImportId());

        // Save import status in tracking
        $tracking->setImportStatus($result->getImportStatus());
        $tracking->setImportStatusReason($result->getReasonStatus());
        $this->productTrackingFactory->create()->save($tracking);

        return $result;
    }
}
