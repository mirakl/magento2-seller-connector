<?php
namespace MiraklSeller\Api\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Api\Helper\Offer as OfferHelper;
use MiraklSeller\Api\Model\Client\Manager;
use MiraklSeller\Api\Model\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @group api
 * @group helper
 * @coversDefaultClass \MiraklSeller\Api\Helper\Offer
 */
class OfferTest extends TestCase
{
    /**
     * @var OfferHelper
     */
    protected $helper;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $context = $objectManager->getObject(Context::class);

        $manager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $objectManager->getObject(OfferHelper::class, [
            'context' => $context,
            'manager' => $manager,
        ]);
    }

    public function testImportOffersWithEmptyData()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('No offer to import');

        /** @var Connection|\PHPUnit\Framework\MockObject\MockObject $connectionMock */
        $connectionMock = $this->createMock(Connection::class);

        $this->helper->importOffers($connectionMock, []);
    }
}
