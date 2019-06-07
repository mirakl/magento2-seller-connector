<?php
namespace Mirakl\Test\Integration\Core\Model\ResourceModel;

use Magento\Framework\Stdlib\DateTime\DateTime;
use MiraklSeller\Core\Helper\Listing\Process as ListingProcessHelper;
use MiraklSeller\Core\Model\ResourceModel\Listing as ListingResource;
use MiraklSeller\Core\Model\ResourceModel\ListingFactory as ListingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Offer as OfferResource;
use MiraklSeller\Core\Model\ListingFactory;
use MiraklSeller\Core\Model\Listing\Builder\Standard;
use MiraklSeller\Core\Test\Integration\TestCase;
use MiraklSeller\Core\Model\Offer;
use MiraklSeller\Process\Model\Process;

/**
 * @group core
 * @group model
 * @group resource
 * @group offer
 * @coversDefaultClass \MiraklSeller_Core_Model_Resource_Offer
 */
class OfferTest extends TestCase
{
    /**
     * @var ListingFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $listingFactoryMock;

    /**
     * @var DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeMock;

    /**
     * @var ListingProcessHelper
     */
    protected $listingHelper;

    /**
     * @var OfferResource
     */
    protected $offerResource;

    protected function setUp()
    {
        parent::setUp();

        $this->listingFactoryMock = $this->createMock(ListingFactory::class);
        $listingResourceMock = $this->createMock(ListingResource::class);
        $listingResourceMock->expects($this->any())->method('load')->willReturnSelf();
        $listingResourceFactoryMock = $this->createMock(ListingResourceFactory::class);
        $listingResourceFactoryMock->expects($this->any())->method('create')->willReturn($listingResourceMock);
        $this->listingHelper = $this->objectManager->create(ListingProcessHelper::class, [
            'listingFactory' => $this->listingFactoryMock,
            'listingResourceFactory' => $listingResourceFactoryMock,
        ]);

        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->offerResource = $this->objectManager->create(OfferResource::class, [
            'dateTime' => $this->dateTimeMock,
        ]);
    }

    /**
     * @covers ::getListingFailedProductIds
     * @param   string  $trackingUpdatedDate
     * @param   string  $currentDate
     * @param   int     $delay
     * @param   int     $expectedProductsCount
     * @dataProvider getTestGetListingFailedProductIdsDataProvider
     */
    public function testGetListingFailedProductIds($trackingUpdatedDate, $currentDate, $delay, $expectedProductsCount)
    {
        $tracking = $this->createSampleTrackingProduct();

        $listing = $tracking->getListing();

        $this->dateTimeMock->expects($this->any())
            ->method('date')
            ->willReturn($currentDate);
        $this->listingFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($listing);

        // Mock listing builder in order to specify product ids manually
        /** @var Standard|\PHPUnit_Framework_MockObject_MockObject $builderMock */
        $builderMock = $this->createMock(Standard::class);
        $builderMock->expects($this->once())
            ->method('build')
            ->willReturn([547, 548, 549, 551, 552, 553, 554]);

        $listing->setBuilder($builderMock);

        /** @var Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(Process::class);

        // Build and save listing product ids in db
        $this->listingHelper->refresh($processMock, $listing->getId());

        // Verify that offers have been saved correctly
        $offers = $this->offerResource->getListingProductIds($listing->getId());
        $this->assertSame(7, count($offers));

        // Define some products as failed manually
        $this->offerResource->updateProducts($listing->getId(), [549, 551], [
            'product_import_id'     => $tracking->getImportId(),
            'product_import_status' => Offer::PRODUCT_TRANSFORMATION_ERROR,
        ]);

        // Update product tracking updated date
        $this->offerResource->getConnection()->update(
            $this->offerResource->getTable('mirakl_seller_listing_tracking_product'),
            ['created_at' => $trackingUpdatedDate, 'updated_at' => $trackingUpdatedDate],
            ['listing_id = ?' => $listing->getId()]
        );

        $failedProductIds = $this->offerResource->getListingFailedProductIds($listing->getId(), $delay);

        $this->assertCount($expectedProductsCount, $failedProductIds);
    }

    /**
     * @return  array
     */
    public function getTestGetListingFailedProductIdsDataProvider()
    {
        return [
            ['2017-10-12 14:35:49', '2017-12-27 16:55:07', 10, 2],
            ['2017-12-25 09:14:30', '2017-12-27 16:55:07', 5, 0],
            ['2017-12-25 09:14:30', '2017-12-27 16:55:07', 1, 2],
            ['2017-12-27 16:55:07', '2017-12-25 09:14:30', 100, 0],
        ];
    }
}