<?php
namespace Mirakl\Test\Integration\Core\Model\Listing\Export;

use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexer;
use Magento\Framework\Indexer\IndexerRegistry;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Model\Listing\Export\Offers as ExportModel;
use MiraklSeller\Core\Model\Offer;
use MiraklSeller\Core\Model\Offer\Loader;
use MiraklSeller\Core\Model\ResourceModel\Offer as OfferResourceModel;
use MiraklSeller\Core\Test\Integration\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @group export
 * @coversDefaultClass \MiraklSeller\Core\Model\Listing\Export\Offers
 */
class OffersTest extends TestCase
{
    /**
     * @var ExportModel
     */
    protected $exportModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportModel = $this->objectManager->create(ExportModel::class);
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportDataProvider
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Core/Test/Integration/Model/_fixtures/products_with_special_price.php
     * @magentoDbIsolation enabled
     */
    public function testExport($productIds, $expectedResult)
    {
        // Product need to be indexed to having final price
        if (count($productIds)) {
            $indexerRegistry = $this->objectManager->create(IndexerRegistry::class);
            $indexerRegistry->get(PriceIndexer::INDEXER_ID)->reindexList($productIds);
        }

        $listing = $this->createSampleListing();

        /** @var Loader $offerLoader */
        $offerLoader = $this->objectManager->create(Loader::class);
        $offerLoader->load($listing->getId(), $productIds);

        $result = $this->exportModel->export($listing);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportDataProvider()
    {
        return [
            [[267, 268, 269], $this->_getJsonFileContents('expected_export_offers_1.json')],
            [[341, 342, 343, 344, 345], $this->_getJsonFileContents('expected_export_offers_2.json')],
            [[287, 288, 289, 290, 291], $this->_getJsonFileContents('expected_export_offers_with_prices_1.json')],
            [[], []],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithDeleteOffersDataProvider
     * @magentoDbIsolation enabled
     */
    public function testExportWithDeleteOffers($productIds, $expectedResult)
    {
        $listing = $this->createSampleListing();

        /** @var OfferResourceModel $offerResource */
        $offerResource = $this->objectManager->create(OfferResourceModel::class);
        $offerResource->createOffers($listing->getId(), $productIds);
        $offerResource->updateProducts($listing->getId(), $productIds, [
            'offer_import_status' => Offer::OFFER_DELETE,
        ]);

        $result = $this->exportModel->export($listing);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithDeleteOffersDataProvider()
    {
        return [
            [
                [267, 268, 269],
                $this->_getJsonFileContents('expected_export_offers_delete_1.json')
            ],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithCustomPriceFieldDataProvider
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Core/Test/Integration/Model/_fixtures/products_with_custom_price.php
     * @magentoDbIsolation enabled
     */
    public function testExportWithCustomPriceField($productIds, $expectedResult)
    {
        $listing = $this->createSampleListing();

        $listing->getConnection()->setExportedPricesAttribute('msrp');

        /** @var Loader $offerLoader */
        $offerLoader = $this->objectManager->create(Loader::class);
        $offerLoader->load($listing->getId(), $productIds);

        $result = $this->exportModel->export($listing);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithCustomPriceFieldDataProvider()
    {
        return [
            [[267, 268, 269], $this->_getJsonFileContents('expected_export_offers_with_custom_price_1.json')],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithPromotionActivatedDataProvider
     * @magentoConfigFixture mirakl_seller_core/prices/enable_promotion_catalog_price_rule 1
     * @magentoDbIsolation enabled
     */
    public function testExportWithPromotionActivated($productIds, $expectedResult)
    {
        // Product need to be indexed to having final price
        if (count($productIds)) {
            $indexerRegistry = $this->objectManager->create(IndexerRegistry::class);
            $indexerRegistry->get(PriceIndexer::INDEXER_ID)->reindexList($productIds);
        }

        // Check Config Fixture
        /** @var Config $configHelper */
        $configHelper = $this->objectManager->get(Config::class);
        $this->assertEquals($configHelper->isPromotionPriceExported(), true);

        $listing = $this->createSampleListing();

        /** @var Loader $offerLoader */
        $offerLoader = $this->objectManager->create(Loader::class);
        $offerLoader->load($listing->getId(), $productIds);

        $result = $this->exportModel->export($listing);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithPromotionActivatedDataProvider()
    {
        return [
            [[267, 268, 269], $this->_getJsonFileContents('expected_export_offers_1.json')],
            [[341, 342, 343, 344, 345], $this->_getJsonFileContents('expected_export_offers_2.json')],
            [[], []],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $additionalFields
     * @param   array   $additionalFieldsValues
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithAdditionalFieldsDataProvider
     * @magentoDbIsolation enabled
     */
    public function testExportWithAdditionalFields($productIds, $additionalFields, $additionalFieldsValues, $expectedResult)
    {
        $listing = $this->createSampleListing();

        $connection = $listing->getConnection();
        $connection->setOfferAdditionalFields($additionalFields);
        $this->objectManager->get(\MiraklSeller\Api\Model\ResourceModel\Connection::class)
            ->save($connection);

        $listing->setOfferAdditionalFieldsValues($additionalFieldsValues);
        $this->objectManager->get(\MiraklSeller\Core\Model\ResourceModel\Listing::class)
            ->save($listing);

        /** @var Loader $offerLoader */
        $offerLoader = $this->objectManager->create(Loader::class);
        $offerLoader->load($listing->getId(), $productIds);

        $result = $this->exportModel->export($listing);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithAdditionalFieldsDataProvider()
    {
        return [
            [
                [267, 268, 269],
                $this->_getJsonFileContents('sample_additional_fields.json'),
                $this->_getJsonFileContents('sample_additional_fields_values.json'),
                $this->_getJsonFileContents('expected_export_offers_with_additional_fields_1.json'),
            ],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithOrderConditionConfigurationDataProvider
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Core/Test/Integration/Model/_fixtures/wholesale.php
     * @magentoConfigFixture current_store cataloginventory/item_options/enable_qty_increments 1
     * @magentoConfigFixture current_store cataloginventory/item_options/qty_increments 2
     * @magentoConfigFixture current_store cataloginventory/item_options/min_sale_qty [2]
     * @magentoDbIsolation enabled
     */
    public function testExportWithOrderConditionConfiguration($productIds, $expectedResult)
    {
        $listing = $this->createSampleListing();

        /** @var Loader $offerLoader */
        $offerLoader = $this->objectManager->create(Loader::class);
        $offerLoader->load($listing->getId(), $productIds);

        $result = $this->exportModel->export($listing);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithOrderConditionConfigurationDataProvider()
    {
        return [
            [
                [267, 268, 269, 271],
                $this->_getJsonFileContents('expected_export_offers_with_order_condition_configuration_1.json')
            ],
        ];
    }
}
