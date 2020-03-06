<?php
namespace MiraklSeller\Core\Test\Integration;

use Mirakl\MCI\Common\Domain\Product\ProductImportWithTransformationStatus;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\Connection as ConnectionResource;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory as ConnectionCollectionFactory;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\ListingFactory;
use MiraklSeller\Core\Model\Listing\Tracking\Product as TrackingProduct;
use MiraklSeller\Core\Model\Listing\Tracking\ProductFactory as TrackingProductFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing as ListingResource;
use MiraklSeller\Core\Model\ResourceModel\ListingFactory as ListingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\CollectionFactory as ListingCollectionFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product as TrackingProductResource;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\ProductFactory as TrackingProductResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product\CollectionFactory as TrackingProductCollectionFactory;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory as ProcessCollectionFactory;

abstract class TestCase extends \MiraklSeller\Api\Test\Integration\TestCase
{
    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResource
     */
    protected $connectionResource;

    /**
     * @var ConnectionCollectionFactory
     */
    protected $connectionCollectionFactory;

    /**
     * @var array
     */
    protected $connectionIds = [];

    /**
     * @var ListingFactory
     */
    protected $listingFactory;

    /**
     * @var ListingResource
     */
    protected $listingResource;

    /**
     * @var ListingCollectionFactory
     */
    protected $listingCollectionFactory;

    /**
     * @var array
     */
    protected $listingIds = [];

    /**
     * @var TrackingProductFactory
     */
    protected $trackingProductFactory;

    /**
     * @var TrackingProductResource
     */
    protected $trackingProductResource;

    /**
     * @var TrackingProductCollectionFactory
     */
    protected $trackingProductCollectionFactory;

    /**
     * @var array
     */
    protected $trackingProductIds = [];

    /**
     * @var ProcessCollectionFactory
     */
    protected $processCollectionFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->connectionFactory = $this->objectManager->create(ConnectionFactory::class);
        $this->connectionResource = $this->objectManager->create(ConnectionResourceFactory::class)->create();
        $this->connectionCollectionFactory = $this->objectManager->create(ConnectionCollectionFactory::class);
        $this->listingFactory = $this->objectManager->create(ListingFactory::class);
        $this->listingResource = $this->objectManager->create(ListingResourceFactory::class)->create();
        $this->listingCollectionFactory = $this->objectManager->create(ListingCollectionFactory::class);
        $this->trackingProductFactory = $this->objectManager->create(TrackingProductFactory::class);
        $this->trackingProductResource = $this->objectManager->create(TrackingProductResourceFactory::class)->create();
        $this->trackingProductCollectionFactory = $this->objectManager->create(TrackingProductCollectionFactory::class);
        $this->processCollectionFactory = $this->objectManager->create(ProcessCollectionFactory::class);
    }

    protected function tearDown()
    {
        if (!empty($this->connectionIds)) {
            // Delete created connections
            $this->connectionCollectionFactory->create()
                ->addIdFilter($this->connectionIds)
                ->walk('delete');
        }

        if (!empty($this->listingIds)) {
            // Delete created listings
            $this->listingCollectionFactory->create()
                ->addIdFilter($this->listingIds)
                ->walk('delete');
        }

        if (!empty($this->trackingProductIds)) {
            // Delete created listings
            $this->trackingProductCollectionFactory->create()
                ->addIdFilter($this->trackingProductIds)
                ->walk('delete');
        }

        $this->processCollectionFactory->create()
            ->walk('delete');

        parent::tearDown();
    }

    /**
     * @return  Connection
     */
    protected function createSampleConnection()
    {
        $connection = $this->connectionFactory->create();
        $connection->setName('[TEST] Sample connection for integration tests');
        $this->connectionResource->save($connection);

        $this->connectionIds[] = $connection->getId();

        return $connection;
    }

    /**
     * @return  Listing
     */
    protected function createSampleListing()
    {
        $connection = $this->createSampleConnection();

        $listing = $this->listingFactory->create();
        $listing->setName('[TEST] Sample listing for integration tests')
            ->setConnectionId($connection->getId())
            ->setIsActive(1);
        $this->listingResource->save($listing);

        $this->listingIds[] = $listing->getId();

        return $listing;
    }

    /**
     * @return  TrackingProduct
     */
    protected function createSampleTrackingProduct()
    {
        $listing = $this->createSampleListing();

        $tracking = $this->trackingProductFactory->create();
        $tracking->setListingId($listing->getId())
            ->setImportId(random_int(2000, 9999))
            ->setImportStatus(ProductImportWithTransformationStatus::COMPLETE);
        $this->trackingProductResource->save($tracking);

        $this->trackingProductIds[] = $tracking->getId();

        return $tracking;
    }
}