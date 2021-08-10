<?php
namespace MiraklSeller\Api\Test\Unit\Model;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Api\Helper\Shop as ShopApi;
use MiraklSeller\Api\Model\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @group api
 * @group model
 * @group connection
 * @coversDefaultClass \MiraklSeller\Api\Model\Connection
 */
class ConnectionTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $connectionModel;

    /**
     * @var Response|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $responseMock;

    protected function setUp(): void
    {
        $context = $this->getMockBuilder('Magento\Framework\Model\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $registry = $this->getMockBuilder('Magento\Framework\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatusCode'])
            ->getMock();

        $shopApi = $this->getMockBuilder(ShopApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccount'])
            ->getMock();
        $shopApi->expects($this->any())
            ->method('getAccount')
            ->will($this->returnCallback(function () {
                $requestMock = $this->getMockBuilder(\GuzzleHttp\Psr7\Request::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                throw new RequestException('foo', $requestMock, $this->responseMock);
            }));

        $this->connectionModel = (new ObjectManager($this))->getObject(Connection::class, [
            'context' => $context,
            'registry' => $registry,
            'shopApi' => $shopApi
        ]);
    }

    /**
     * @covers ::validate
     * @param   int     $responseCode
     * @param   string  $expectedExceptionMessage
     * @dataProvider getValidateConnectionWithExceptionDataProvider
     */
    public function testValidateConnectionWithException($responseCode, $expectedExceptionMessage)
    {
        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn($responseCode);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->connectionModel->validate();
    }

    /**
     * @return  array
     */
    public function getValidateConnectionWithExceptionDataProvider()
    {
        return [
            [401, 'CONN-03: You are not authorized to use the API. Please check your API key.'],
            [404, 'CONN-02: The API cannot be reached. Please check the API URL.'],
            [500, 'CONN-01: Unexpected system error. Mirakl cannot be reached.'],
        ];
    }
}
