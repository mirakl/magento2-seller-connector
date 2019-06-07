<?php
namespace MiraklSeller\Core\Model\Listing\Tracking;

use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product as ProductResource;

/**
 * @method  int     getImportId()
 * @method  $this   setImportId(int $productImportId)
 * @method  string  getImportStatus()
 * @method  $this   setImportStatus(string $productImportStatus)
 * @method  string  getImportStatusReason()
 * @method  $this   setImportStatusReason(string $productImportStatusReason)
 * @method  string  getIntegrationErrorReport()
 * @method  $this   setIntegrationErrorReport(string $errorReport)
 * @method  string  getTransformationErrorReport()
 * @method  $this   setTransformationErrorReport(string $errorReport)
 * @method  string  getIntegrationSuccessReport()
 * @method  $this   setIntegrationSuccessReport(string $integrationReport)
 */
class Product extends AbstractTracking
{
    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(ProductResource::class);
    }
}
