<?php
namespace MiraklSeller\Api\Test\Unit\Model\Client;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Api\Model\Client\Manager;
use MiraklSeller\Api\Model\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @group api
 * @group model
 * @coversDefaultClass \MiraklSeller\Api\Model\Client\Manager
 */
class ManagerTest extends TestCase
{
    /**
     * @var Manager
     */
    protected $clientManager;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $productMetadata = $this->getMockBuilder(\Magento\Framework\App\ProductMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventManager = $this->getMockBuilder(\Magento\Framework\Event\Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiConfig = $this->getMockBuilder(\MiraklSeller\Api\Helper\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiHelper = $this->getMockBuilder(\MiraklSeller\Api\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $objectManager->getObject(\MiraklSeller\Api\Model\Client\Factory::class, [
            'productMetadata' => $productMetadata,
            'eventManager'    => $eventManager,
            'apiConfig'       => $apiConfig,
            'apiHelper'       => $apiHelper,
        ]);
        $this->clientManager = $objectManager->getObject(Manager::class, [
            'factory' => $factory,
        ]);
    }

    /**
     * @covers ::disableClient
     */
    public function testDisableClient()
    {
        $this->expectException(\Mirakl\Core\Exception\ClientDisabledException::class);

        /** @var Connection|\PHPUnit\Framework\MockObject\MockObject $connection */
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $connection->expects($this->any())
            ->method('getId')
            ->willReturn(1234);

        $client = $this->clientManager->get($connection, 'MMP');

        Manager::disable();

        /** @var \Mirakl\Core\Request\RequestInterface $requestMock */
        $requestMock = $this->getMockBuilder(\Mirakl\Core\Request\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client->run($requestMock);
    }
}
