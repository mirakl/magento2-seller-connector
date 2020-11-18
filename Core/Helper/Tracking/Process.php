<?php
namespace MiraklSeller\Core\Helper\Tracking;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\ImportStatus;
use MiraklSeller\Core\Helper\Data;
use MiraklSeller\Core\Helper\Tracking\Api as TrackingApiHelper;
use MiraklSeller\Core\Helper\Tracking\Offer as TrackingOfferHelper;
use MiraklSeller\Core\Helper\Tracking\Product as TrackingProductHelper;
use MiraklSeller\Core\Model\Listing\Tracking\Product as TrackingProduct;
use MiraklSeller\Core\Model\Listing\Tracking\Status\Product as ProductStatus;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer\CollectionFactory as OfferTrackingCollectionFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product\CollectionFactory as ProductTrackingCollectionFactory;
use MiraklSeller\Process\Model\Process as ProcessModel;

class Process extends Data
{
    /**
     * @var TrackingApiHelper
     */
    protected $apiHelper;

    /**
     * @var TrackingOfferHelper
     */
    protected $offerHelper;

    /**
     * @var TrackingProductHelper
     */
    protected $productHelper;

    /**
     * @var OfferTrackingCollectionFactory
     */
    protected $offerTrackingCollectionFactory;

    /**
     * @var ProductTrackingCollectionFactory
     */
    protected $productTrackingCollectionFactory;

    /**
     * @param   Context                             $context
     * @param   StoreManagerInterface               $storeManager
     * @param   TrackingApiHelper                   $apiHelper
     * @param   TrackingOfferHelper                 $offerHelper
     * @param   TrackingProductHelper               $productHelper
     * @param   OfferTrackingCollectionFactory      $offerTrackingCollectionFactory
     * @param   ProductTrackingCollectionFactory    $productTrackingCollectionFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        TrackingApiHelper $apiHelper,
        TrackingOfferHelper $offerHelper,
        TrackingProductHelper $productHelper,
        OfferTrackingCollectionFactory $offerTrackingCollectionFactory,
        ProductTrackingCollectionFactory $productTrackingCollectionFactory
    ) {
        parent::__construct($context, $storeManager);
        $this->apiHelper = $apiHelper;
        $this->offerHelper = $offerHelper;
        $this->productHelper = $productHelper;
        $this->offerTrackingCollectionFactory = $offerTrackingCollectionFactory;
        $this->productTrackingCollectionFactory = $productTrackingCollectionFactory;
    }

    /**
     * @param   ProcessModel    $process
     * @param   array           $trackingIds
     */
    public function updateOffersImportStatus(ProcessModel $process, $trackingIds)
    {
        $collection = $this->offerTrackingCollectionFactory->create();
        $collection->addIdFilter($trackingIds)
            ->addExcludeOfferStatusCompleteFilter()
            ->addWithImportIdFilter()
            ->setOrder('id', 'desc');

        if (!$collection->count()) {
            $process->output(__('No available tracking to update'));

            return;
        }

        $process->output(__('Found %1 tracking(s) to update', $collection->count()));

        foreach ($collection as $tracking) {
            $process->output(__('Getting import status of tracking #%1 ...', $tracking->getId()));

            try {
                // Call API OF02 and save offer import status
                $result = $this->apiHelper->updateOfferTrackingStatus($tracking);

                // Output API result, might be useful
                $process->output(json_encode($result->toArray(), JSON_PRETTY_PRINT));

                // Check for error report
                if ($result->hasErrorReport()) {
                    $process->output(__('Downloading error report ...', $tracking->getId()));

                    // Call API OF03 and save offer import error report
                    $this->apiHelper->updateOfferErrorReport($tracking);
                    $process->output(__('Error report saved'));

                    // Update offers status according to error report
                    $process->output(__('Updating offers status according to error report ...'));
                    $updatedOffersCount = $this->offerHelper->processErrorReport($tracking);
                    $process->output(__('Updated %1 prices & stocks', $updatedOffersCount));
                } elseif ($result->getStatus() == ImportStatus::COMPLETE) {
                    // If import is complete and no error report is present, mark all offers as SUCCESS
                    $updatedOffersCount = $this->offerHelper->markAsSuccess($tracking);
                    $process->output(__('Updated %1 prices & stocks', $updatedOffersCount));
                }

                if ($result->getStatus() == ImportStatus::COMPLETE) {
                    $updatedProductsCount = $this->productHelper->updateProductStatusFromOffer($tracking);
                    $process->output(__('Updated %1 products', $updatedProductsCount));
                }

                $process->output(__('Tracking #%1 updated!', $tracking->getId()));
            } catch (\Exception $e) {
                // Do not stop process execution if an error occurred, continue with next tracking
                $process->output(__('ERROR: %1', $e->getMessage()));
            }
        }
    }

    /**
     * @param   ProcessModel    $process
     * @param   array           $trackingIds
     */
    public function updateProductsImportStatus(ProcessModel $process, $trackingIds)
    {
        $collection = $this->productTrackingCollectionFactory->create();
        $collection->addIdFilter($trackingIds)
            ->addExcludeProductStatusFinalFilter()
            ->addWithImportIdFilter()
            ->setOrder('id', 'desc');

        if (!$collection->count()) {
            $process->output(__('No available tracking to update'));

            return;
        }

        $process->output(__('Found %1 tracking(s) to update', $collection->count()));

        /** @var TrackingProduct $tracking */
        foreach ($collection as $tracking) {
            $listing = $tracking->getListing();

            $process->output(__('Getting import status of tracking #%1 ...', $tracking->getId()));

            try {
                // Call API P42 and save product import status
                $result = $this->apiHelper->updateProductTrackingStatus($tracking);

                // Output API result, might be useful
                $process->output(json_encode($result->toArray(), JSON_PRETTY_PRINT));

                // Check for transformation error report
                if ($result->hasTransformationErrorReport() && !$tracking->getTransformationErrorReport()) {
                    $process->output(__('Downloading transformation error report ...', $tracking->getId()));

                    // Call API P47 and save product transformation error report
                    $this->apiHelper->updateProductTransformationErrorReport($tracking);

                    // Update products status according to error report
                    $process->output(__('Updating products status according to transformation error report ...'));
                    $updatedProductsCount = $this->productHelper->processTransformationErrorReport($tracking);
                    $process->output(__('Updated %1 products', $updatedProductsCount));
                }

                // Check for integration error report
                if ($result->hasErrorReport() && !$tracking->getIntegrationErrorReport()) {
                    $process->output(__('Downloading integration error report ...', $tracking->getId()));

                    // Call API P44 and save product integration error report
                    $this->apiHelper->updateProductIntegrationErrorReport($tracking);

                    // Process integration error report
                    $process->output(__('Updating products status according to integration error report ...'));
                    $updatedProductsCount = $this->productHelper->processIntegrationErrorReport($tracking);
                    $process->output(__('Updated %1 products', $updatedProductsCount));
                }

                // Check for integration success report
                if ($result->hasNewProductReport() && !$tracking->getIntegrationSuccessReport()) {
                    $process->output(__('Downloading product success report ...', $tracking->getId()));

                    // Call API P45 and save new product integration
                    $this->apiHelper->updateProductIntegrationSuccessReport($tracking);

                    // Process integration success report
                    $successCount = $this->productHelper->processIntegrationSuccessReport($tracking);
                    $process->output(__('Updated %1 products', $successCount));
                }

                // Update products status according to import status
                if (ProductStatus::isStatusComplete($result->getImportStatus())) {
                    // If product is still in PENDING status, update it according to the import status
                    $process->output(__('Updating products status according to import status ...'));
                    $updatedProductsCount = $this->productHelper->updateProductStatusFromImportStatus(
                        $tracking, $result->getImportStatus()
                    );
                    $process->output(__('Updated %1 products', $updatedProductsCount));
                }

                // Process expired product with Magento configuration nb_days_expired
                $expiredProducts = $this->productHelper->processExpiredProducts($listing->getId(), $tracking);
                $process->output(__('Expiring products of listing #%1 ... %2 expired products', $listing->getId(), $expiredProducts));

                $process->output(__('Tracking #%1 updated!', $tracking->getId()));
            } catch (\Exception $e) {
                // Do not stop process execution if an error occurred, continue with next tracking
                $process->output(__('ERROR: %1', $e->getMessage()));
            }
        }
    }
}
