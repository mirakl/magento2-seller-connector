<?php
namespace MiraklSeller\Api\Helper;

use Magento\Framework\Exception\LocalizedException;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\ImportMode;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferImportResult;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferProductImportTracking;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportErrorReportRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportReportRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportRequest;
use MiraklSeller\Api\Model\Connection;

class Offer extends Client\MMP
{
    /**
     * (OF01) Import offers: import file to add offers.
     * Returns the import identifier to track the status of the import.
     *
     * @param   Connection  $connection
     * @param   array       $data
     * @param   string      $importMode
     * @return  OfferProductImportTracking
     * @throws  LocalizedException
     */
    public function importOffers(Connection $connection, array $data, $importMode = ImportMode::NORMAL)
    {
        if (empty($data)) {
            throw new LocalizedException(__('No offer to import'));
        }

        // Add columns in top of file
        $cols = array_keys(reset($data));
        array_unshift($data, $cols);

        $file = \Mirakl\create_temp_csv_file($data);
        $request = new OfferImportRequest($file);
        $request->setImportMode($importMode);
        $request->setWithProducts(in_array('product-sku', $cols));
        $request->setFileName('MGT-OF01-' . time() . '.csv');

        $this->_eventManager->dispatch('mirakl_seller_api_import_offers_before', [
            'request' => $request,
        ]);

        return $this->send($connection, $request);
    }

    /**
     * (OF02) Get offers import information and stats
     *
     * @param   Connection  $connection
     * @param   int         $importId
     * @return  OfferImportResult
     */
    public function getOffersImportResult(Connection $connection, $importId)
    {
        $request = new OfferImportReportRequest($importId);

        return $this->send($connection, $request);
    }

    /**
     * (OF03) Get error report file for an offer import
     *
     * @param   Connection  $connection
     * @param   int         $importId
     * @return  FileWrapper
     */
    public function getOffersImportErrorReport(Connection $connection, $importId)
    {
        $request = new OfferImportErrorReportRequest($importId);

        return $this->send($connection, $request);
    }
}
