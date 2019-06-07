<?php
namespace MiraklSeller\Core\Model\Listing\Tracking;

use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer as OfferResource;

/**
 * @method  int     getImportId()
 * @method  $this   setImportId(int $offerImportId)
 * @method  string  getImportStatus()
 * @method  $this   setImportStatus(string $offerImportStatus)
 * @method  string  getErrorReport()
 * @method  $this   setErrorReport(string $errorReport)
 */
class Offer extends AbstractTracking
{
    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(OfferResource::class);
    }
}
