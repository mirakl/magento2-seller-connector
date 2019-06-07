<?php
namespace MiraklSeller\Core\Cron\Tracking;

use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Cron\AbstractCron;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Process\Model\Process;

class UpdateAll extends AbstractCron
{
    /**
     * Update all trackings
     *
     * @return  void
     */
    public function execute()
    {
        $this->updateAllConnections(Listing::TYPE_ALL);
    }

    /**
     * Update specified type for all listings of all connections
     *
     * @param   string  $exportType
     */
    protected function updateAllConnections($exportType)
    {
        /** @var Connection $connection */
        foreach ($this->getAllConnections() as $connection) {
            $this->updateConnection($connection, $exportType);
        }
    }

    /**
     * Update specified type for all listings of specified connection
     *
     * @param   Connection  $connection
     * @param   string      $exportType
     */
    protected function updateConnection(Connection $connection, $exportType)
    {
        $listings = $this->connectionHelper->getActiveListings($connection);

        /** @var Listing $listing */
        foreach ($listings as $listing) {
            $this->updateListing($listing, $exportType);
        }
    }

    /**
     * Update specified type of specified listing
     *
     * @param   Listing $listing
     * @param   string  $exportType
     */
    protected function updateListing($listing, $exportType)
    {
        $processes = $this->trackingHelper->updateListingTrackingsByType(
            $listing->getId(), $exportType, Process::TYPE_CRON
        );

        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run();
        }
    }
}
