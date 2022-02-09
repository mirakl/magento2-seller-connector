<?php
namespace MiraklSeller\Core\Helper;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Core\Helper\Config as ConfigHelper;
use MiraklSeller\Core\Helper\Listing\Process as ProcessHelper;
use MiraklSeller\Core\Model\Listing as ListingModel;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

class Listing extends Data
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @param   Context                 $context
     * @param   StoreManagerInterface   $storeManager
     * @param   ConfigHelper            $configHelper
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory
    ) {
        parent::__construct($context, $storeManager);
        $this->configHelper = $configHelper;
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
    }

    /**
     * @param   ListingModel        $listing
     * @param   ProductCollection   $collection
     * @param   bool                $joinLeft
     * @return  $this
     */
    public function addListingPriceDataToCollection(ListingModel $listing, ProductCollection $collection, $joinLeft = false)
    {
        $collection->setStore($listing->getStoreId());
        $collection->addPriceData($this->configHelper->getCustomerGroup(), $listing->getWebsiteId());

        if ($joinLeft) {
            $fromPart = $collection->getSelect()->getPart(Select::FROM);
            if (isset($fromPart['price_index'])) {
                $fromPart['price_index']['joinType'] = 'left join';
                $collection->getSelect()->setPart(Select::FROM, $fromPart);
            }
        }

        if ($exportedPricesAttr = $listing->getConnection()->getExportedPricesAttribute()) {
            $collection->addAttributeToSelect($exportedPricesAttr);
        }

        return $this;
    }

    /**
     * Export the specified listing asynchronously (export products or offers or both)
     *
     * @param   ListingModel  $listing
     * @param   string        $exportType
     * @param   bool          $offerFull
     * @param   string        $productMode
     * @param   string        $processType
     * @return  Process[]
     */
    public function export(
        ListingModel $listing,
        $exportType = ListingModel::TYPE_ALL,
        $offerFull = true,
        $productMode = ListingModel::PRODUCT_MODE_PENDING,
        $processType = Process::TYPE_ADMIN
    ) {
        $processes = [];
        $resource = $this->processResourceFactory->create();

        if ($exportType == ListingModel::TYPE_PRODUCT || $exportType == ListingModel::TYPE_ALL) {
            $process = $this->processFactory->create()
                ->setType($processType)
                ->setName('Export listing products')
                ->setHelper(ProcessHelper::class)
                ->setMethod('exportProduct')
                ->setParams([$listing->getId(), $productMode, $this->configHelper->isAutoCreateTracking()]);
            $resource->save($process);
            $processes[] = $process;
        }

        if ($exportType == ListingModel::TYPE_OFFER || $exportType == ListingModel::TYPE_ALL) {
            $process = $this->processFactory->create()
                ->setType($processType)
                ->setName('Export listing offers')
                ->setHelper(ProcessHelper::class)
                ->setMethod('exportOffer')
                ->setParams([$listing->getId(), $offerFull, $this->configHelper->isAutoCreateTracking()]);
            $resource->save($process);
            $processes[] = $process;
        }

        return $processes;
    }

    /**
     * Refresh the specified listing asynchronously
     *
     * @param   ListingModel  $listing
     * @param   string        $processType
     * @return  Process
     */
    public function refresh(ListingModel $listing, $processType = Process::TYPE_ADMIN)
    {
        $process = $this->processFactory->create()
            ->setType($processType)
            ->setName('Listing refresh')
            ->setHelper(ProcessHelper::class)
            ->setMethod('refresh')
            ->setParams([$listing->getId()]);
        $this->processResourceFactory->create()->save($process);

        return $process;
    }
}
