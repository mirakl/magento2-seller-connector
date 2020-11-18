<?php
namespace MiraklSeller\Core\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Core\Model\Listing as Listing;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer\Collection as OfferCollection;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer\CollectionFactory as OfferCollectionFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product\Collection as ProductCollection;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product\CollectionFactory as ProductCollectionFactory;
use MiraklSeller\Process\Model\Process as Process;
use MiraklSeller\Process\Model\ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ResourceFactory;

class Tracking extends Data
{
    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var OfferCollectionFactory
     */
    protected $offerCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     * @param   ProcessFactory              $processFactory
     * @param   ResourceFactory             $resourceFactory
     * @param   OfferCollectionFactory      $offerCollectionFactory
     * @param   ProductCollectionFactory    $productCollectionFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ProcessFactory $processFactory,
        ResourceFactory $resourceFactory,
        OfferCollectionFactory $offerCollectionFactory,
        ProductCollectionFactory $productCollectionFactory
    ) {
        parent::__construct($context, $storeManager);
        $this->processFactory = $processFactory;
        $this->resourceFactory = $resourceFactory;
        $this->offerCollectionFactory = $offerCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param   array   $trackingIds
     * @param   string  $processType
     * @return  Process
     */
    public function updateOfferTrackings($trackingIds, $processType = Process::TYPE_ADMIN)
    {
        $process = $this->processFactory->create()
            ->setType($processType)
            ->setName('Update tracking offers import status (OF02)')
            ->setHelper(\MiraklSeller\Core\Helper\Tracking\Process::class)
            ->setMethod('updateOffersImportStatus')
            ->setParams([$trackingIds]);

        $this->resourceFactory->create()->save($process);

        return $process;
    }

    /**
     * @param   array   $trackingIds
     * @param   string  $processType
     * @return  Process
     */
    public function updateProductTrackings($trackingIds, $processType = Process::TYPE_ADMIN)
    {
        $process = $this->processFactory->create()
            ->setType($processType)
            ->setName('Update tracking products import status (P42)')
            ->setHelper(\MiraklSeller\Core\Helper\Tracking\Process::class)
            ->setMethod('updateProductsImportStatus')
            ->setParams([$trackingIds]);

        $this->resourceFactory->create()->save($process);

        return $process;
    }

    /**
     * @param   int     $listingId
     * @param   string  $updateType
     * @param   string  $processType
     * @return  Process[]
     */
    public function updateListingTrackingsByType($listingId, $updateType = Listing::TYPE_ALL, $processType = Process::TYPE_ADMIN)
    {
        $processes = [];

        if ($updateType == Listing::TYPE_OFFER || $updateType == Listing::TYPE_ALL) {
            /** @var OfferCollection $collection */
            $collection = $this->offerCollectionFactory->create();
            $collection->addListingFilter($listingId)
                ->addExcludeOfferStatusCompleteFilter()
                ->addWithImportIdFilter();

            // Update the offer export trackings
            $processes[] = $this->updateOfferTrackings($collection->getAllIds(), $processType);
        }

        if ($updateType == Listing::TYPE_PRODUCT || $updateType == Listing::TYPE_ALL) {
            /** @var ProductCollection $collection */
            $collection = $this->productCollectionFactory->create();
            $collection->addListingFilter($listingId)
                ->addExcludeProductStatusFinalFilter()
                ->addWithImportIdFilter();

            // Update the product export trackings
            $processes[] = $this->updateProductTrackings($collection->getAllIds(), $processType);
        }

        return $processes;
    }

    /**
     * @param   array   $trackingIds
     * @param   string  $updateType
     * @param   string  $processType
     * @return  Process[]
     * @throws  LocalizedException
     */
    public function updateTrackingsByType($trackingIds, $updateType = Listing::TYPE_ALL, $processType = Process::TYPE_ADMIN)
    {
        $processes = [];

        switch ($updateType) {
            case Listing::TYPE_OFFER:
                $processes[] = $this->updateOfferTrackings($trackingIds, $processType);
                break;
            case Listing::TYPE_PRODUCT:
                $processes[] = $this->updateProductTrackings($trackingIds, $processType);
                break;
            case Listing::TYPE_ALL:
                $processes[] = $this->updateOfferTrackings($trackingIds, $processType);
                $processes[] = $this->updateProductTrackings($trackingIds, $processType);
                break;
            default:
                throw new LocalizedException(__('Bad update type specified: %1', $updateType));
        }

        return $processes;
    }
}
