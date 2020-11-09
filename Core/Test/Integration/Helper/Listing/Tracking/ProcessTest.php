<?php
namespace Mirakl\Test\Integration\Core\Helper\Tracking;

use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MCI\Common\Domain\Product\ProductImportResult;
use MiraklSeller\Api\Helper\Product as ProductApiHelper;
use MiraklSeller\Api\Model\ResourceModel\Connection as ConnectionRessource;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionRessourceFactory;
use MiraklSeller\Core\Helper\Tracking\Api as TrackingApiHelper;
use MiraklSeller\Core\Helper\Tracking\Process as ProcessHelper;
use MiraklSeller\Core\Model\Listing\Tracking\ProductFactory as TrackingProductFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product as TrackingProductResource;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\ProductFactory as TrackingProductResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Offer as OfferResource;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
use MiraklSeller\Core\Model\OfferFactory;
use MiraklSeller\Core\Test\Integration\TestCase;
use MiraklSeller\Process\Model\Process;

/**
 * @group core
 * @group helper
 * @group tracking
 * @group process
 * @coversDefaultClass \MiraklSeller\Core\Helper\Tracking\Process
 */
class ProcessTest extends TestCase
{
    /**
     * @var ProcessHelper
     */
    protected $helper;

    /**
     * @var ProductApiHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productApiHelper;

    /**
     * @var ConnectionRessource
     */
    protected $connectionRessource;

    /**
     * @var OfferFactory
     */
    protected $offerFactory;

    /**
     * @var OfferResource
     */
    protected $offerResource;

    /**
     * @var OfferCollectionFactory
     */
    protected $offerCollectionFactory;

    /**
     * @var TrackingProductFactory
     */
    protected $trackingProductFactory;

    /**
     * @var TrackingProductResource
     */
    protected $trackingProductResource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productApiHelper = $this->createMock(ProductApiHelper::class);
        $trackingApiHelper = $this->objectManager->create(TrackingApiHelper::class, ['productApiHelper' => $this->productApiHelper]);
        $this->helper = $this->objectManager->create(ProcessHelper::class, ['apiHelper' => $trackingApiHelper]);
        $this->connectionRessource = $this->objectManager->create(ConnectionRessourceFactory::class)->create();
        $this->offerFactory = $this->objectManager->create(OfferFactory::class);
        $this->offerResource = $this->objectManager->create(OfferResourceFactory::class)->create();
        $this->offerCollectionFactory = $this->objectManager->create(OfferCollectionFactory::class);
        $this->trackingProductFactory = $this->objectManager->create(TrackingProductFactory::class);
        $this->trackingProductResource = $this->objectManager->create(TrackingProductResourceFactory::class)->create();
    }

    /**
     * @covers ::updateOffersImportStatus
     * @param   string  $jsonFile
     * @dataProvider getUpdateProductsImportStatusProvider
     */
    public function testUpdateProductsImportStatus($jsonFile)
    {
        $listing = $this->createSampleListing();

        $connection = $listing->getConnection();
        $connection->setSkuCode('shop_sku');
        $connection->setErrorsCode('error');
        $connection->setSuccessSkuCode('shop_sku');
        $connection->setMessagesCode('error');
        $this->connectionRessource->save($connection);

        $data = $this->_getJsonFileContents($jsonFile);

        foreach ($data['offers_before'] as $offerData) {
            $offer = $this->offerFactory->create();
            $offer->setData($offerData);
            $offer->setListingId($listing->getId());
            $this->offerResource->save($offer);
        }

        $trackingProduct = $this->trackingProductFactory->create();
        $trackingProduct->setData($data['listing_tracking_product']);
        $trackingProduct->setListingId($listing->getId());
        $this->trackingProductResource->save($trackingProduct);

        /** @var Process|\PHPUnit\Framework\MockObject\MockObject $processMock */
        $processMock = $this->createMock(Process::class);

        $this->productApiHelper->expects($this->any())
            ->method('getProductImportResult')
            ->willReturn(new ProductImportResult($data['P42']['product_import_trackings'][0]));

        $this->productApiHelper->expects($this->any())
            ->method('getProductsTransformationErrorReport')
            ->willReturn(new FileWrapper(implode("\n", $data['P47'])));

        $this->productApiHelper->expects($this->any())
            ->method('getProductsIntegrationErrorReport')
            ->willReturn(new FileWrapper(implode("\n", $data['P44'])));

        $this->productApiHelper->expects($this->any())
            ->method('getNewProductsIntegrationReport')
            ->willReturn(new FileWrapper(implode("\n", $data['P45'])));

        $this->helper->updateProductsImportStatus($processMock, $trackingProduct->getId());

        $this->trackingProductResource->load($trackingProduct, $trackingProduct->getId());

        $this->assertEquals($data['listing_tracking_product_status_after'], $trackingProduct->getImportStatus());
        $this->assertEquals($data['listing_tracking_product_status_reason_after'], $trackingProduct->getImportStatusReason());

        $offers = $this->offerCollectionFactory->create()->addFieldToFilter('listing_id', $listing->getId());
        $this->assertCount(count($data['offers_after']), $offers);

        foreach ($offers as $offer) {
            $this->assertArrayHasKey($offer->getProductId(), $data['offers_after']);
            foreach ($data['offers_after'][$offer->getProductId()] as $key => $value) {
                $this->assertEquals($value, $offer->getData($key));
            }
        }
    }

    /**
     * @return  array
     */
    public function getUpdateProductsImportStatusProvider()
    {
        return [
            ['transformationSuccess.json'],
            ['transformationFail.json'],
            ['transformationMixed.json'],
            ['integrationSuccessWithoutFile.json'],
            ['integrationSuccessWithFile.json'],
            ['integrationErrorWithoutFile.json'],
            ['integrationErrorWithFile.json'],
            ['integrationMixedWithoutFile.json'],
            ['integrationMixedWithFile.json'],
            ['integrationSuccessWithFileAndTransfoDone.json'],
        ];
    }
}
