<?php
namespace MiraklSeller\Core\Cron\Listing;

use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Cron\AbstractCron;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Process\Model\Process;

class RefreshAll extends AbstractCron
{
    /**
     * Refresh all listings of all connections
     *
     * @return  void
     */
    public function execute()
    {
        /** @var Connection $connection */
        foreach ($this->getAllConnections() as $connection) {
            $listings = $this->connectionHelper->getActiveListings($connection);

            /** @var Listing $listing */
            foreach ($listings as $listing) {
                $process = $this->listingHelper->refresh($listing, Process::TYPE_CRON);
                $process->run();
            }
        }
    }
}
