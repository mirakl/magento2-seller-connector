<?php
namespace MiraklSeller\Core\Observer\Product;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Model\ResourceModel\Listing\CollectionFactory as ListingCollectionFactory;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

abstract class AbstractObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var ListingCollectionFactory
     */
    protected $listingCollectionFactory;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   ManagerInterface            $messageManager
     * @param   OfferResourceFactory        $offerResourceFactory
     * @param   ListingCollectionFactory    $listingCollectionFactory
     * @param   ProcessFactory              $processFactory
     * @param   ProcessResourceFactory      $processResourceFactory
     * @param   Config                      $config
     */
    public function __construct(
        ManagerInterface $messageManager,
        OfferResourceFactory $offerResourceFactory,
        ListingCollectionFactory $listingCollectionFactory,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        Config $config
    ) {
        $this->messageManager           = $messageManager;
        $this->offerResourceFactory     = $offerResourceFactory;
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->processFactory           = $processFactory;
        $this->processResourceFactory   = $processResourceFactory;
        $this->config                   = $config;
    }

    /**
     * @param   array   $productIds
     */
    protected function deleteProducts(array $productIds)
    {
        try {
            // Do not try to delete a product twice
            static $deletedProductIds = [];

            $productIds = array_diff($productIds, $deletedProductIds);
            $deletedProductIds = array_merge($deletedProductIds, $productIds);

            /** @var \MiraklSeller\Core\Model\ResourceModel\Offer $offerResource */
            $offerResource = $this->offerResourceFactory->create();

            $listingIds = $offerResource->getListingIdsByProductIds($productIds);

            if (empty($listingIds)) {
                return;
            }

            /** @var \MiraklSeller\Core\Model\ResourceModel\Listing\Collection $listings */
            $listings = $this->listingCollectionFactory->create()
                ->addIdFilter($listingIds);

            /** @var \MiraklSeller\Process\Model\ResourceModel\Process $processResource */
            $processResource = $this->processResourceFactory->create();

            /** @var \MiraklSeller\Core\Model\Listing $listing */
            foreach ($listings as $listing) {
                $listing->setProductIds($productIds);
                $offerResource->markOffersAsDelete($listing->getId(), $productIds);

                /** @var Process $process */
                $process = $this->processFactory->create()
                    ->setType(Process::TYPE_ADMIN)
                    ->setName('Delete listing offers')
                    ->setHelper(\MiraklSeller\Core\Helper\Listing\Process::class)
                    ->setMethod('exportOffer')
                    ->setParams([$listing->getId(), true, $this->config->isAutoCreateTracking(), $productIds]);

                $processResource->save($process);

                // Run process synchronously
                $process->run(true);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}