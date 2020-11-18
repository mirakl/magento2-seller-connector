<?php
namespace MiraklSeller\Core\Helper\Tracking;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Core\Helper\Data;
use MiraklSeller\Core\Model\Listing\Tracking\Offer as Tracking;
use MiraklSeller\Core\Model\Offer as OfferModel;
use MiraklSeller\Core\Model\ResourceModel\Offer as OfferRessource;

class Offer extends Data
{
    use \MiraklSeller\Core\Helper\CsvTrait;

    /**
     * @var OfferRessource
     */
    protected $offerResource;

    /**
     * @param   Context                 $context
     * @param   StoreManagerInterface   $storeManager
     * @param   OfferRessource          $offerResource
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        OfferRessource $offerResource
    ) {
        parent::__construct($context, $storeManager);
        $this->offerResource = $offerResource;
    }

    /**
     * Mark ALL pending offers associated with the tracking offer import id as SUCCESS
     *
     * @param   Tracking    $tracking
     * @return  int
     */
    public function markAsSuccess(Tracking $tracking)
    {
        // Retrieve pending offers data associated with the tracking offer import id
        $offersData = $this->getTrackingPendingOffersData($tracking);

        // Update all offers in an unique query
        return $this->offerResource->updateMultiple($offersData);
    }

    /**
     * Returns number of updated offers according to error report
     *
     * @param   Tracking    $tracking
     * @return  int
     */
    public function processErrorReport(Tracking $tracking)
    {
        // Create a temp file in order to parse CSV data easily
        $file = $this->createCsvFileFromString($tracking->getErrorReport());

        return $this->processErrorReportFile($file, $tracking);
    }

    /**
     * Returns number of updated offers according to error report file
     *
     * @param   \SplFileObject  $file
     * @param   Tracking        $tracking
     * @return  int
     */
    public function processErrorReportFile(\SplFileObject $file, Tracking $tracking)
    {
        // Retrieve pending offers data associated with the tracking offer import id
        $offersData = $this->getTrackingPendingOffersData($tracking);

        $file->rewind();

        // Loop on CSV file
        $cols = $file->fgetcsv();

        while ($row = $file->fgetcsv()) {
            $data = array_combine($cols, $row);
            $productId = $data['entity_id'];
            if (isset($offersData[$productId])) {
                $offersData[$productId]['offer_import_status'] = OfferModel::OFFER_ERROR;
                $offersData[$productId]['offer_error_message'] = $data['error-message'];
            }
        }

        // Update all offers in an unique query
        return $this->offerResource->updateMultiple($offersData);
    }

    /**
     * Returns tracking exported offers that have status PENDING
     * (need to specify status PENDING because it may have been updated to DELETE in the meanwhile)
     *
     * @param   Tracking    $tracking
     * @return  array
     */
    protected function getTrackingPendingOffersData(Tracking $tracking)
    {
        $offersData = $this->offerResource->getListingPendingOffers(
            $tracking->getListingId(), $tracking->getImportId()
        );

        // No error occurred on product means offer added so define status to SUCCESS by default
        array_walk($offersData, function(&$value) {
            $value['offer_import_status'] = OfferModel::OFFER_SUCCESS;
            $value['offer_error_message'] = null;
            $value['updated_at']          = date('Y-m-d H:i:s');
        });

        return $offersData;
    }
}
