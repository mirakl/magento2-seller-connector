<?php
namespace MiraklSeller\Core\Cron\Listing;

use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Cron\AbstractCron;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Process\Model\Process;

abstract class AbstractExport extends AbstractCron
{
    /**
     * @var bool
     */
    protected $offerFull = true;

    /**
     * Export specified type for all listings of all connections
     *
     * @param   string  $exportType
     */
    protected function exportAllConnections($exportType)
    {
        /** @var Connection $connection */
        foreach ($this->getAllConnections() as $connection) {
            $this->exportConnection($connection, $exportType);
        }
    }

    /**
     * Export specified type for all listings of specified connection
     *
     * @param   Connection  $connection
     * @param   string      $exportType
     */
    protected function exportConnection(Connection $connection, $exportType)
    {
        $listings = $this->connectionHelper->getActiveListings($connection);

        /** @var Listing $listing */
        foreach ($listings as $listing) {
            $this->exportListing($listing, $exportType);
        }
    }

    /**
     * Export specified type of specified listing
     *
     * @param   Listing $listing
     * @param   string  $exportType
     */
    protected function exportListing($listing, $exportType)
    {
        $processes = $this->listingHelper->export(
            $listing,
            $exportType,
            $this->offerFull,
            Listing::PRODUCT_MODE_PENDING,
            Process::TYPE_CRON
        );

        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run();
        }
    }
}
