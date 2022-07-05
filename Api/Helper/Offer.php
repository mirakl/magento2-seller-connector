<?php
namespace MiraklSeller\Api\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\ImportMode;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferImportResult;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferProductImportTracking;
use Mirakl\MMP\Shop\Domain\Collection\Offer\State\OfferStateCollection;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportErrorReportRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportReportRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportRequest;
use Mirakl\MMP\Shop\Request\Offer\State\GetOfferStateListRequest;
use MiraklSeller\Api\Model\Cache\Type\OfferConditions;
use MiraklSeller\Api\Model\Client\Manager;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\Log\LoggerManager;
use MiraklSeller\Api\Model\Log\RequestLogValidator;

class Offer extends Client\MMP
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Context             $context
     * @param Manager             $manager
     * @param LoggerManager       $loggerManager
     * @param RequestLogValidator $requestLogValidator
     * @param CacheInterface      $cache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        Manager $manager,
        LoggerManager $loggerManager,
        RequestLogValidator $requestLogValidator,
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        parent::__construct(
            $context,
            $manager,
            $loggerManager,
            $requestLogValidator
        );
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    /**
     * (OF01) Import offers: import file to add offers.
     * Returns the import identifier to track the status of the import.
     *
     * @param   Connection  $connection
     * @param   array       $data
     * @param   string      $importMode
     * @param   bool        $withProducts
     * @return  OfferProductImportTracking
     * @throws  LocalizedException
     */
    public function importOffers(Connection $connection, array $data, $importMode = ImportMode::NORMAL, $withProducts = false)
    {
        if (empty($data)) {
            throw new LocalizedException(__('No offer to import'));
        }

        // Add columns in top of file
        $cols = array_keys(reset($data));
        array_unshift($data, $cols);

        $file = $this->toCsvFile($data);
        $request = new OfferImportRequest($file);
        $request->setImportMode($importMode);
        $request->setWithProducts($withProducts);
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

    /**
     * (OF61) Get Mirakl offers conditions (states) list
     *
     * We use cache as Mirakl limits the number of allowed calls per day for this API
     *
     * @param Connection $connection
     * @return OfferStateCollection
     */
    public function getOffersStateList(Connection $connection)
    {
        $offerConditionsData = $this->cache->load(OfferConditions::TYPE_IDENTIFIER);

        // load() method returns false when cache content is expired
        if ($offerConditionsData === false) {
            $request = new GetOfferStateListRequest();
            $offerConditions = $this->send($connection, $request);
            $this->cache->save(
                $this->serializer->serialize($offerConditions->toArray()),
                OfferConditions::TYPE_IDENTIFIER,
                [OfferConditions::CACHE_TAG],
                OfferConditions::CACHE_LIFETIME
            );
        } else {
            $offerConditionsData = $this->serializer->unserialize($this->cache->load(OfferConditions::TYPE_IDENTIFIER));
            $offerConditions = new OfferStateCollection($offerConditionsData);
        }

        return $offerConditions;
    }
}
