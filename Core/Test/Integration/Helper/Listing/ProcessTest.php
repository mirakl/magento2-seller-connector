<?php
namespace MiraklSeller\Core\Test\Integration\Helper\Listing;

use Mirakl\MCI\Common\Domain\Product\ProductImportTracking;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferProductImportTracking;
use MiraklSeller\Api\Helper\Offer as OfferApi;
use MiraklSeller\Api\Helper\Product as ProductApi;
use MiraklSeller\Core\Helper\Listing\Process as ProcessHelper;
use MiraklSeller\Core\Model\Listing\Builder\Standard;
use MiraklSeller\Core\Model\ListingFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing as ListingResource;
use MiraklSeller\Core\Model\ResourceModel\ListingFactory as ListingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer\Collection as TrackingOfferCollection;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer\CollectionFactory as TrackingOfferCollectionFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product\Collection as TrackingProductCollection;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product\CollectionFactory as TrackingProductCollectionFactory;
use MiraklSeller\Core\Model\ResourceModel\Offer as OfferResource;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use MiraklSeller\Core\Model\Offer as Offer;
use MiraklSeller\Core\Test\Integration\TestCase;
use MiraklSeller\Process\Model\Process;

/**
 * @group core
 * @group helper
 * @group listing
 * @group process
 * @coversDefaultClass \MiraklSeller\Core\Helper\Listing\Process
 */
class ProcessTest extends TestCase
{
    /**
     * @var ProcessHelper
     */
    protected $helper;

    /**
     * @var array
     */
    protected $helperParamMocks;

    /**
     * @var OfferResource
     */
    protected $offerResource;

    /**
     * @var TrackingOfferCollectionFactory
     */
    protected $trackingOfferCollectionFactory;

    /**
     * @var TrackingProductCollectionFactory
     */
    protected $trackingProductCollectionFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->helperParamMocks = [
            'listingFactory' => $this->createMock(ListingFactory::class),
            'listingResourceFactory' => $this->createMock(ListingResourceFactory::class),
            'offerApi' => $this->createMock(OfferApi::class),
            'productApi' => $this->createMock(ProductApi::class),
        ];
        $resource = $this->createMock(ListingResource::class);
        $resource->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->helperParamMocks['listingResourceFactory']->expects($this->any())
            ->method('create')
            ->willReturn($resource);
        $this->helper = $this->objectManager->create(ProcessHelper::class, $this->helperParamMocks);

        $this->offerResource = $this->objectManager->get(OfferResourceFactory::class)->create();

        $this->trackingOfferCollectionFactory = $this->objectManager->get(TrackingOfferCollectionFactory::class);
        $this->trackingProductCollectionFactory = $this->objectManager->get(TrackingProductCollectionFactory::class);
    }

    /**
     * @covers ::refresh
     */
    public function testRefreshNewListing()
    {
        $listing = $this->createSampleListing();

        // Mock listing builder in order to specify product ids manually
        /** @var Standard|\PHPUnit_Framework_MockObject_MockObject $builderMock */
        $builderMock = $this->createMock(Standard::class);
        $builderMock->expects($this->once())
            ->method('build')
            ->willReturn([231, 232, 233, 237, 238, 239]);

        $listing->setBuilder($builderMock);

        $this->helperParamMocks['listingFactory']->expects($this->once())
            ->method('create')
            ->willReturn($listing);

        /** @var Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(Process::class);

        // Build and save listing product ids in db
        $this->helper->refresh($processMock, $listing->getId());

        /**
         * Expected listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 231 | 232 | 233 | 237 | 238 | 239 |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         */

        // All offers MUST have the status NEW because not imported yet
        $offers = $this->offerResource->getListingProductIds($listing->getId(), Offer::OFFER_NEW);
        $this->assertSame(6, count($offers));

        // All products MUST have the status NEW because not imported yet
        $products = $this->offerResource->getListingProductIds($listing->getId(), null, Offer::PRODUCT_NEW);
        $this->assertSame(6, count($products));
    }

    /**
     * @covers ::refresh
     */
    public function testRefreshExistingListing()
    {
        $listing = $this->createSampleListing();

        // Mock listing builder in order to specify product ids manually
        /** @var Standard|\PHPUnit_Framework_MockObject_MockObject $builderMock */
        $builderMock = $this->createMock(Standard::class);
        $builderMock->expects($this->exactly(3))
            ->method('build')
            ->willReturnOnConsecutiveCalls(
                [231, 232, 233, 237, 238, 239],
                [231, 232, 233, 237, 238, 239],
                [231, 232, 233]
            );

        $listing->setBuilder($builderMock);

        $this->helperParamMocks['listingFactory']->expects($this->exactly(3))
            ->method('create')
            ->willReturn($listing);

        /** @var Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(Process::class);

        // Build and save listing product ids in db
        $this->helper->refresh($processMock, $listing->getId());

        /**
         * Expected listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 231 | 232 | 233 | 237 | 238 | 239 |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         */

        // Mark 3 offers as SUCCESS and 3 products as ERROR in order to test that they are set to NEW after refresh
        $this->offerResource->updateOffersStatus($listing->getId(), [231, 232, 233], Offer::OFFER_SUCCESS);
        $this->offerResource->updateOffersStatus($listing->getId(), [237, 238, 239], Offer::OFFER_ERROR);
        $this->offerResource->updateProductsStatus($listing->getId(), [231, 232, 233], Offer::PRODUCT_SUCCESS);
        $this->offerResource->updateProductsStatus($listing->getId(), [237, 238, 239], Offer::PRODUCT_INTEGRATION_ERROR);

        /**
         * Expected listing products:
         * +----------------+---------+---------+---------+-------------------+-------------------+-------------------+
         * | Product Id     | 231     | 232     | 233     | 237               | 238               | 239               |
         * +----------------+---------+---------+---------+-------------------+-------------------+-------------------+
         * | Product Status | SUCCESS | SUCCESS | SUCCESS | INTEGRATION_ERROR | INTEGRATION_ERROR | INTEGRATION_ERROR |
         * +----------------+---------+---------+---------+-------------------+-------------------+-------------------+
         * | Offer Status   | SUCCESS | SUCCESS | SUCCESS | ERROR             | ERROR             | ERROR             |
         * +----------------+---------+---------+---------+-------------------+-------------------+-------------------+
         */

        $offersNew = $this->offerResource->getListingProductIds($listing->getId(), Offer::OFFER_NEW);
        $this->assertSame(0, count($offersNew));

        $offersSuccess = $this->offerResource->getListingProductIds($listing->getId(), Offer::OFFER_SUCCESS);
        $this->assertSame(3, count($offersSuccess));
        $this->assertSame(['231', '232', '233'], $offersSuccess);

        $offersError = $this->offerResource->getListingProductIds($listing->getId(), Offer::OFFER_ERROR);
        $this->assertSame(3, count($offersError));
        $this->assertSame(['237', '238', '239'], $offersError);

        // Update products in db
        $this->helper->refresh($processMock, $listing->getId());

        /**
         * Expected listing products:
         * +----------------+---------+---------+---------+-----+-----+-----+
         * | Product Id     | 231     | 232     | 233     | 237 | 238 | 239 |
         * +----------------+---------+---------+---------+-----+-----+-----+
         * | Product Status | SUCCESS | SUCCESS | SUCCESS | NEW | NEW | NEW |
         * +----------------+---------+---------+---------+-----+-----+-----+
         * | Offer Status   | SUCCESS | SUCCESS | SUCCESS | NEW | NEW | NEW |
         * +----------------+---------+---------+---------+-----+-----+-----+
         */

        $offersNew = $this->offerResource->getListingProductIds($listing->getId(), Offer::OFFER_NEW);
        $this->assertSame(3, count($offersNew));
        $this->assertSame(['237', '238', '239'], $offersNew);

        $offersSuccess = $this->offerResource->getListingProductIds($listing->getId(), Offer::OFFER_SUCCESS);
        $this->assertSame(3, count($offersSuccess));
        $this->assertSame(['231', '232', '233'], $offersSuccess);

        // Modify listing products manually (keep 3 and remove the 3 others), we should get 3 offers with status DELETE
        // Update products in db
        $this->helper->refresh($processMock, $listing->getId());

        /**
         * Expected listing products:
         * +----------------+---------+---------+---------+--------+--------+--------+
         * | Product Id     | 231     | 232     | 233     | 237    | 238    | 239    |
         * +----------------+---------+---------+---------+--------+--------+--------+
         * | Product Status | SUCCESS | SUCCESS | SUCCESS | NEW    | NEW    | NEW    |
         * +----------------+---------+---------+---------+--------+--------+--------+
         * | Offer Status   | SUCCESS | SUCCESS | SUCCESS | DELETE | DELETE | DELETE |
         * +----------------+---------+---------+---------+--------+--------+--------+
         */

        $offersSuccess = $this->offerResource->getListingProductIds($listing->getId(), Offer::OFFER_SUCCESS);
        $this->assertSame(3, count($offersSuccess));
        $this->assertSame(['231', '232', '233'], $offersSuccess);

        $offersDelete = $this->offerResource->getListingProductIds($listing->getId(), Offer::OFFER_DELETE);
        $this->assertSame(0, count($offersDelete));
    }

    /**
     * @covers ::exportOffer
     */
    public function testExportOffer()
    {
        $listing = $this->createSampleListing();

        // Mock listing builder in order to specify product ids manually
        /** @var Standard|\PHPUnit_Framework_MockObject_MockObject $builderMock */
        $builderMock = $this->createMock(Standard::class);
        $builderMock->expects($this->once())
            ->method('build')
            ->willReturn([231, 232, 233, 237, 238, 239]);

        $listing->setBuilder($builderMock);

        $this->helperParamMocks['listingFactory']->expects($this->any())
            ->method('create')
            ->willReturn($listing);

        /** @var Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(Process::class);

        // Build and save listing product ids in db
        $this->helper->refresh($processMock, $listing->getId());

        /**
         * Current listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 231 | 232 | 233 | 237 | 238 | 239 |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         */

        $this->offerResource->updateOffersStatus($listing->getId(), [237], Offer::OFFER_PENDING);
        $this->offerResource->updateOffersStatus($listing->getId(), [231, 233, 238], Offer::OFFER_SUCCESS);
        $this->offerResource->updateProductsStatus($listing->getId(), [231, 233, 237, 238], Offer::PRODUCT_SUCCESS);
        $this->offerResource->updateOffersStatus($listing->getId(), [239], Offer::OFFER_DELETE);

        /**
         * Expected listing products:
         * +----------------+---------+-----+---------+---------+---------+--------+
         * | Product Id     | 231     | 232 | 233     | 237     | 238     | 239    |
         * +----------------+---------+-----+---------+---------+---------+--------+
         * | Product Status | SUCCESS | NEW | SUCCESS | SUCCESS | SUCCESS | NEW    |
         * +----------------+---------+-----+---------+---------+---------+--------+
         * | Offer Status   | SUCCESS | NEW | SUCCESS | PENDING | SUCCESS | DELETE |
         * +----------------+---------+-----+---------+---------+---------+--------+
         */

        $cols = ['product_id', 'product_import_status', 'offer_import_status'];
        $offers = $this->offerResource->getListingProducts($listing->getId(), [], $cols);
        $expectedOffers = [
            231 => [
                'product_id' => '231',
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_status' => Offer::OFFER_SUCCESS,
            ],
            232 => [
                'product_id' => '232',
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_status' => Offer::PRODUCT_NEW,
            ],
            233 => [
                'product_id' => '233',
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_status' => Offer::OFFER_SUCCESS,
            ],
            237 => [
                'product_id' => '237',
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            238 => [
                'product_id' => '238',
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_status' => Offer::OFFER_SUCCESS,
            ],
            239 => [
                'product_id' => '239',
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_status' => Offer::OFFER_DELETE,
            ],
        ];
        $this->assertSame($expectedOffers, $offers);

        $this->helperParamMocks['offerApi']->expects($this->once())
            ->method('importOffers')
            ->willReturn(new OfferProductImportTracking(['import_id' => 2028]));

        $this->helper->exportOffer($processMock, $listing->getId());

        $cols = ['product_id', 'product_import_id', 'product_import_status', 'offer_import_id', 'offer_import_status'];
        $offers = $this->offerResource->getListingProducts($listing->getId(), [], $cols);

        $expectedOffers = [
            231 => [
                'product_id' => '231',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => '2028',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            232 => [
                'product_id' => '232',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2028',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            233 => [
                'product_id' => '233',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => '2028',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            237 => [
                'product_id' => '237',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            238 => [
                'product_id' => '238',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => '2028',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
        ];
        $this->assertSame($expectedOffers, $offers);

        // Verify that tracking has been created correctly
        /** @var TrackingOfferCollection $trackings */
        $trackings = $this->trackingOfferCollectionFactory->create();
        $trackings->addListingFilter($listing->getId());

        $this->assertCount(1, $trackings);

        $tracking = $trackings->getFirstItem();
        $this->assertSame('2028', $tracking->getImportId());
        $this->assertNull($tracking->getImportStatus());
    }

    /**
     * @covers ::exportOffer
     */
    public function testExportOfferDelta()
    {
        $listing = $this->createSampleListing();

        // Mock listing builder in order to specify product ids manually
        /** @var Standard|\PHPUnit_Framework_MockObject_MockObject $builderMock */
        $builderMock = $this->createMock(Standard::class);
        $builderMock->expects($this->once())
            ->method('build')
            ->willReturn([392, 393, 394, 395, 396, 397, 398, 399, 400]);

        $listing->setBuilder($builderMock);

        $this->helperParamMocks['listingFactory']->expects($this->any())
            ->method('create')
            ->willReturn($listing);

        /** @var Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->objectManager->create(Process::class);
        $processMock->addOutput('db');

        // Build and save listing product ids in db
        $this->helper->refresh($processMock, $listing->getId());

        /**
         * Current listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 392 | 393 | 394 | 395 | 396 | 397 | 398 | 399 | 400 |
         * +----------------+-----+-----+-----+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+-----+-----+-----+
         */

        $this->helperParamMocks['offerApi']->expects($this->once())
            ->method('importOffers')
            ->willReturn(new OfferProductImportTracking(['import_id' => 2378]));

        $this->helper->exportOffer($processMock, $listing->getId(), false);

        $cols = ['product_id', 'product_import_id', 'product_import_status', 'offer_import_id', 'offer_import_status'];
        $offers = $this->offerResource->getListingProducts($listing->getId(), [], $cols);

        /**
         * Expected listing products:
         * +----------------+---------+---------+---------+---------+---------+---------+---------+---------+---------+
         * | Product Id     |   392   |   393   |   394   |   395   |   396   |   397   |   398   |   399   |   400   |
         * +----------------+---------+---------+---------+---------+---------+---------+---------+---------+---------+
         * | Product Status |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |
         * +----------------+---------+---------+---------+---------+---------+---------+---------+---------+---------+
         * | Offer Status   | PENDING | PENDING | PENDING | PENDING | PENDING | PENDING | PENDING | PENDING | PENDING |
         * +----------------+---------+---------+---------+---------+---------+---------+---------+---------+---------+
         */

        $expectedOffers = [
            392 => [
                'product_id' => '392',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            393 => [
                'product_id' => '393',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            394 => [
                'product_id' => '394',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            395 => [
                'product_id' => '395',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            396 => [
                'product_id' => '396',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            397 => [
                'product_id' => '397',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            398 => [
                'product_id' => '398',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            399 => [
                'product_id' => '399',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            400 => [
                'product_id' => '400',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
        ];
        $this->assertSame($expectedOffers, $offers);

        // Verify that tracking has been created correctly
        /** @var TrackingOfferCollection $trackings */
        $trackings = $this->trackingOfferCollectionFactory->create();
        $trackings->addListingFilter($listing->getId());

        $this->assertCount(1, $trackings);

        $tracking = $trackings->getFirstItem();
        $this->assertSame('2378', $tracking->getImportId());
        $this->assertNull($tracking->getImportStatus());

        // Run export again in delta mode, nothing should change
        $this->helper->exportOffer($processMock, $listing->getId(), false);

        $this->assertTrue(false !== stripos($processMock->getOutput(), 'No offer to export'));
    }

    /**
     * @covers ::exportProduct
     */
    public function testExportProduct()
    {
        $listing = $this->createSampleListing();

        // Mock listing builder in order to specify product ids manually
        /** @var Standard|\PHPUnit_Framework_MockObject_MockObject $builderMock */
        $builderMock = $this->createMock(Standard::class);
        $builderMock->expects($this->once())
            ->method('build')
            ->willReturn([231, 232, 233, 237, 238, 239, 240]);

        $listing->setBuilder($builderMock);

        $this->helperParamMocks['listingFactory']->expects($this->exactly(2))
            ->method('create')
            ->willReturn($listing);

        /** @var Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(Process::class);

        // Build and save listing product ids in db
        $this->helper->refresh($processMock, $listing->getId());

        /**
         * Current listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 231 | 232 | 233 | 237 | 238 | 239 |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         */

        $this->offerResource->updateProductsStatus($listing->getId(), [232], Offer::PRODUCT_PENDING);
        $this->offerResource->updateProductsStatus($listing->getId(), [233], Offer::PRODUCT_TRANSFORMATION_ERROR);
        $this->offerResource->updateProductsStatus($listing->getId(), [237], Offer::PRODUCT_WAITING_INTEGRATION);
        $this->offerResource->updateProductsStatus($listing->getId(), [238], Offer::PRODUCT_INTEGRATION_COMPLETE);
        $this->offerResource->updateProductsStatus($listing->getId(), [239], Offer::PRODUCT_INTEGRATION_ERROR);
        $this->offerResource->updateProductsStatus($listing->getId(), [240], Offer::PRODUCT_SUCCESS);

        /**
         * Expected listing products:
         * +----------------+-----+---------+---------------+--------------+---------------+------------+---------+
         * | Product Id     | 231 | 232     | 233           | 237          | 238           | 239        |         |
         * +----------------+-----+---------+---------------+--------------+---------------+------------+---------+
         * | Product Status | NEW | PENDING | TRANSF._ERROR | WAITING_INT. | INT._COMPLETE | INT._ERROR | SUCCESS |
         * +----------------+-----+---------+---------------+--------------+---------------+------------+---------+
         * | Offer Status   | NEW | NEW     | NEW           | NEW          | NEW           | NEW        | NEW     |
         * +----------------+-----+---------+---------------+--------------+---------------+------------+---------+
         */

        $this->helperParamMocks['productApi']->expects($this->once())
            ->method('importProducts')
            ->willReturn(new ProductImportTracking(['import_id' => 2033]));

        $this->helper->exportProduct($processMock, $listing->getId());

        /**
         * Expected listing products:
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         * | Product Id        | 231     | 232     | 233     | 237          | 238           | 239     | 240     |
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         * | Product Import Id | 2033    | NULL    | 2033    | NULL         | NULL          | 2033    | NULL    |
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         * | Product Status    | PENDING | PENDING | PENDING | WAITING_INT. | INT._COMPLETE | PENDING | SUCCESS |
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         * | Offer Status      | NEW     | NEW     | NEW     | NEW          | NEW           | NEW     | NEW     |
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         */

        $cols = ['product_id', 'product_import_id', 'product_import_status', 'offer_import_id', 'offer_import_status'];
        $products = $this->offerResource->getListingProducts($listing->getId(), [], $cols);
        $expectedProducts = [
            231 => [
                'product_id' => '231',
                'product_import_id' => '2033',
                'product_import_status' => Offer::PRODUCT_PENDING,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            232 => [
                'product_id' => '232',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_PENDING,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            233 => [
                'product_id' => '233',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_TRANSFORMATION_ERROR,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            237 => [
                'product_id' => '237',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_WAITING_INTEGRATION,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            238 => [
                'product_id' => '238',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_INTEGRATION_COMPLETE,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            239 => [
                'product_id' => '239',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_INTEGRATION_ERROR,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            240 => [
                'product_id' => '240',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
        ];
        $this->assertSame($expectedProducts, $products);

        // Verify that tracking has been created correctly
        /** @var TrackingProductCollection $trackings */
        $trackings = $this->trackingProductCollectionFactory->create();
        $trackings->addListingFilter($listing->getId());

        $this->assertCount(1, $trackings);

        $tracking = $trackings->getFirstItem();
        $this->assertSame('2033', $tracking->getImportId());
        $this->assertNull($tracking->getImportStatus());
    }
}