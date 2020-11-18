<?php
namespace MiraklSeller\Core\Cron\Listing;

use MiraklSeller\Core\Model\Listing;

class ExportAllOffers extends AbstractExport
{
    /**
     * Export offers for all listings of all connections
     *
     * @return  void
     */
    public function execute()
    {
        $this->exportAllConnections(Listing::TYPE_OFFER);
    }
}
