<?php
namespace MiraklSeller\Sales\Test\Integration\Helper\Loader;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Helper\Loader\MiraklOrder as MiraklOrderLoader;

class MiraklOrderTest extends TestCase
{
    /**
     * @param   string  $name
     * @return  Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConnectionMock($name = 'Connection Mock')
    {
        /** @var Connection|\PHPUnit\Framework\MockObject\MockObject $connectionMock */
        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['__call', 'getData', 'setData'])
            ->getMock();
        $connectionMock->setName($name);

        return $connectionMock;
    }

    public function testGetCurrentMiraklOrderWithoutOrderId()
    {
        $this->expectException(\Magento\Framework\Exception\NotFoundException::class);
        $this->expectExceptionMessage('Mirakl order id could not be found');

        /** @var MiraklOrderLoader $miraklOrderLoader */
        $miraklOrderLoader = $this->objectManager->create(MiraklOrderLoader::class);
        $miraklOrderLoader->getCurrentMiraklOrder($this->getConnectionMock());
    }

    public function testGetCurrentMiraklOrderWithOrderNotFound()
    {
        $this->expectException(\Magento\Framework\Exception\NotFoundException::class);
        $this->expectExceptionMessage("Could not find Mirakl order for id 'foo' with connection 'bar'");

        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->any())
            ->method('getParam')
            ->will($this->returnValueMap([['order_id', null, 'foo']]));

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($requestMock);

        $miraklOrderMock = $this->objectManager->create(ShopOrder::class);

        $apiOrderMock = $this->getMockBuilder(ApiOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiOrderMock->expects($this->once())
            ->method('getOrderById')
            ->willReturn($miraklOrderMock);

        /** @var MiraklOrderLoader $miraklOrderLoader */
        $miraklOrderLoader = $this->objectManager->create(MiraklOrderLoader::class, [
            'context'  => $contextMock,
            'apiOrder' => $apiOrderMock,
        ]);

        $miraklOrderLoader->getCurrentMiraklOrder($this->getConnectionMock('bar'));
    }

    public function testGetCurrentMiraklOrder()
    {
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->any())
            ->method('getParam')
            ->will($this->returnValueMap([['order_id', null, 'foo']]));

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($requestMock);

        $miraklOrderMock = $this->objectManager->create(ShopOrder::class, [
            'data' => $this->_getJsonFileContents('mirakl_order.json')
        ]);

        $apiOrderMock = $this->getMockBuilder(ApiOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiOrderMock->expects($this->once())
            ->method('getOrderById')
            ->willReturn($miraklOrderMock);

        /** @var MiraklOrderLoader $miraklOrderLoader */
        $miraklOrderLoader = $this->objectManager->create(MiraklOrderLoader::class, [
            'context'  => $contextMock,
            'apiOrder' => $apiOrderMock,
        ]);

        $miraklOrder = $miraklOrderLoader->getCurrentMiraklOrder($this->getConnectionMock());
        $this->assertSame('DKFSLFKZ-SDF993-A', $miraklOrder->getId());
    }
}
