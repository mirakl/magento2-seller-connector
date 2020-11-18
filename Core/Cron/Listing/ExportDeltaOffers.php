<?php
namespace MiraklSeller\Core\Cron\Listing;

use MiraklSeller\Core\Model\Listing;

class ExportDeltaOffers extends AbstractExport
{
    /**
     * Export offers for all listings of all connections
     *
     * @return  void
     */
    public function execute()
    {
        $this->offerFull = false;
        $this->exportAllConnections(Listing::TYPE_OFFER);
    }
}
