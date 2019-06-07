<?php
namespace MiraklSeller\Core\Cron\Listing;

use MiraklSeller\Core\Model\Listing;

class ExportAllProducts extends AbstractExport
{
    /**
     * Export products for all listings of all connections
     *
     * @return  void
     */
    public function execute()
    {
        $this->exportAllConnections(Listing::TYPE_PRODUCT);
    }
}
