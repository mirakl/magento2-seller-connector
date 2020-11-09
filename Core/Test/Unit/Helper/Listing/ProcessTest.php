<?php
namespace MiraklSeller\Core\Test\Unit\Helper\Listing;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Api\Helper\Offer as OfferApi;
use MiraklSeller\Api\Helper\Product as ProductApi;
use MiraklSeller\Core\Helper\Listing\Process as ProcessHelper;
use MiraklSeller\Core\Helper\Listing\Product as ProductHelper;
use MiraklSeller\Core\Helper\Tracking\Product as ProductTrackingHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\ListingFactory;
use MiraklSeller\Core\Model\Listing\Export\Offers;
use MiraklSeller\Core\Model\Listing\Export\Products;
use MiraklSeller\Core\Model\Listing\Tracking\OfferFactory as OfferTrackingFactory;
use MiraklSeller\Core\Model\Listing\Tracking\ProductFactory as ProductTrackingFactory;
use MiraklSeller\Core\Model\Offer\Loader as OfferLoader;
use MiraklSeller\Core\Model\ResourceModel\Listing as ListingResource;
use MiraklSeller\Core\Model\ResourceModel\ListingFactory as ListingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\OfferFactory as OfferTrackingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\ProductFactory as ProductTrackingResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use MiraklSeller\Process\Model\Process as ProcessModel;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group helper
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
    protected $constructorEntityMock;

    protected function setUp(): void
    {
        $classes = [
            'context' => Context::class,
            'storeManager' => StoreManagerInterface::class,
            'offerResourceFactory' => OfferResourceFactory::class,
            'listingResourceFactory' => ListingResourceFactory::class,
            'listingFactory' => ListingFactory::class,
            'offers' => Offers::class,
            'offerLoader' => OfferLoader::class,
            'offerApi' => OfferApi::class,
            'offerTrackingFactory' => OfferTrackingFactory::class,
            'offerTrackingResourceFactory' => OfferTrackingResourceFactory::class,
            'products' => Products::class,
            'productHelper' => ProductHelper::class,
            'productTrackingHelper' => ProductTrackingHelper::class,
            'productApi' => ProductApi::class,
            'productTrackingFactory' => ProductTrackingFactory::class,
            'productTrackingResourceFactory' => ProductTrackingResourceFactory::class,
        ];

        $this->constructorEntityMock = [];
        foreach ($classes as $varName => $className) {
            $this->constructorEntityMock[$varName] = $this->getMockBuilder($className)
                ->disableOriginalConstructor()
                ->getMock();
        }

        $objectManager = new ObjectManager($this);
        $this->helper = $objectManager->getObject(ProcessHelper::class, $this->constructorEntityMock);
    }

    /**
     * @covers ::exportOffer
     */
    public function testExportOfferWithInactiveListing()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('This listing is inactive.');

        /** @var ProcessModel|\PHPUnit\Framework\MockObject\MockObject $processMock */
        $processMock = $this->getMockBuilder(ProcessModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Listing|\PHPUnit\Framework\MockObject\MockObject $listingMock */
        $listingMock = $this->getMockBuilder(Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['validate'])
            ->getMock();
        $listingMock->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $listingMock->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $this->constructorEntityMock['listingFactory']->expects($this->once())
            ->method('create')
            ->willReturn($listingMock);

        $listingResourceMock = $this->getMockBuilder(ListingResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->constructorEntityMock['listingResourceFactory']->expects($this->once())
            ->method('create')
            ->willReturn($listingResourceMock);

        $this->helper->exportOffer($processMock, 123);
    }

    /**
     * @covers ::exportProduct
     *
     */
    public function testExportProductWithInactiveListing()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('This listing is inactive.');

        /** @var ProcessModel|\PHPUnit\Framework\MockObject\MockObject $processMock */
        $processMock = $this->getMockBuilder(ProcessModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Listing|\PHPUnit\Framework\MockObject\MockObject $listingMock */
        $listingMock = $this->getMockBuilder(Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['validate'])
            ->getMock();
        $listingMock->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $listingMock->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $this->constructorEntityMock['listingFactory']->expects($this->once())
            ->method('create')
            ->willReturn($listingMock);

        $listingResourceMock = $this->getMockBuilder(ListingResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->constructorEntityMock['listingResourceFactory']->expects($this->once())
            ->method('create')
            ->willReturn($listingResourceMock);

        $this->helper->exportProduct($processMock, 123);
    }
}
