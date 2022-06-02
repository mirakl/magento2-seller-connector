<?php
namespace MiraklSeller\Api\Helper;

use Magento\Framework\Exception\LocalizedException;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MCI\Common\Domain\Product\ProductImportResult;
use Mirakl\MCI\Common\Domain\Product\ProductImportTracking;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportErrorReportRequest;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportTransformationErrorReportRequest;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportNewProductsReportRequest;
use Mirakl\MCI\Shop\Request\Product\ProductImportStatusRequest;
use Mirakl\MCI\Shop\Request\Product\ProductImportRequest;
use MiraklSeller\Api\Model\Connection;

class Product extends Client\MCI
{
    /**
     * (P41) Import products: import file to add products.
     * Returns the import identifier to track the status of the import.
     *
     * @param   Connection  $connection
     * @param   array       $data
     * @return  ProductImportTracking
     * @throws  LocalizedException
     */
    public function importProducts(Connection $connection, array $data)
    {
        if (empty($data)) {
            throw new LocalizedException(__('No product to import'));
        }

        // Add columns in top of file
        $cols = array_keys(reset($data));
        array_unshift($data, $cols);

        $file = $this->toCsvFile($data);
        $request = new ProductImportRequest($file);
        $request->setFileName('MGT-P41-' . time() . '.csv');

        $this->_eventManager->dispatch('mirakl_seller_api_import_products_before', [
            'request' => $request,
        ]);

        return $this->send($connection, $request);
    }

    /**
     * (P42) Get product import status
     *
     * @param   Connection  $connection
     * @param   int         $importId
     * @return  ProductImportResult
     */
    public function getProductImportResult(Connection $connection, $importId)
    {
        $request = new ProductImportStatusRequest($importId);

        return $this->send($connection, $request);
    }

    /**
     * (P44) Get errors report file for a products import
     *
     * @param   Connection  $connection
     * @param   int         $importId
     * @return  FileWrapper
     */
    public function getProductsIntegrationErrorReport(Connection $connection, $importId)
    {
        $request = new DownloadProductImportErrorReportRequest($importId);

        return $this->send($connection, $request);
    }

    /**
     * (P45) Get new product report file for a products import
     *
     * @param   Connection  $connection
     * @param   int         $importId
     * @return  FileWrapper
     */
    public function getNewProductsIntegrationReport(Connection $connection, $importId)
    {
        $request = new DownloadProductImportNewProductsReportRequest($importId);

        return $this->send($connection, $request);
    }

    /**
     * (P47) Get transformation errors report file for a product import
     *
     * @param   Connection  $connection
     * @param   int         $importId
     * @return  FileWrapper
     */
    public function getProductsTransformationErrorReport(Connection $connection, $importId)
    {
        $request = new DownloadProductImportTransformationErrorReportRequest($importId);

        return $this->send($connection, $request);
    }
}
