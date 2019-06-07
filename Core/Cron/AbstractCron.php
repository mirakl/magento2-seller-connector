<?php
namespace MiraklSeller\Core\Cron;

use MiraklSeller\Api\Model\ResourceModel\Connection\Collection as ConnectionCollection;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory as ConnectionCollectionFactory;
use MiraklSeller\Core\Helper\Connection as ConnectionHelper;
use MiraklSeller\Core\Helper\Listing as ListingHelper;
use MiraklSeller\Core\Helper\Tracking as TrackingHelper;

abstract class AbstractCron
{
    /**
     * @var ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * @var ListingHelper
     */
    protected $listingHelper;

    /**
     * @var TrackingHelper
     */
    protected $trackingHelper;

    /**
     * @var ConnectionCollectionFactory
     */
    protected $connectionCollectionFactory;

    /**
     * @param   ConnectionHelper            $connectionHelper
     * @param   ListingHelper               $listingHelper
     * @param   TrackingHelper              $trackingHelper
     * @param   ConnectionCollectionFactory $connectionCollectionFactory
     */
    public function __construct(
        ConnectionHelper $connectionHelper,
        ListingHelper $listingHelper,
        TrackingHelper $trackingHelper,
        ConnectionCollectionFactory $connectionCollectionFactory
    ) {
        $this->connectionHelper = $connectionHelper;
        $this->listingHelper = $listingHelper;
        $this->trackingHelper = $trackingHelper;
        $this->connectionCollectionFactory = $connectionCollectionFactory;
    }

    /**
     * @return  ConnectionCollection
     */
    protected function getAllConnections()
    {
        return $this->connectionCollectionFactory->create();
    }
}
