<?php
namespace MiraklSeller\Api\Test\Unit\Model\Log;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Api\Model\Log\RequestLogValidator;
use PHPUnit\Framework\TestCase;

class RequestLogValidatorTest extends TestCase
{
    /** @var RequestLogValidator */
    protected $requestLogValidator;

    /**
     * @var \MiraklSeller\Api\Helper\Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configMock;

    /**
     * @var \Mirakl\Core\Request\RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestMock;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(\MiraklSeller\Api\Helper\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(\Mirakl\Core\Request\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestLogValidator = (new ObjectManager($this))->getObject(RequestLogValidator::class, [
            'config' => $this->configMock
        ]);
    }

    public function testValidateWithLoggingDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isApiLogEnabled')
            ->willReturn(false);

        $this->assertFalse($this->requestLogValidator->validate($this->requestMock));
    }

    public function testValidateWithEmptyFilter()
    {
        $this->configMock->expects($this->once())
            ->method('isApiLogEnabled')
            ->willReturn(true);

        $this->configMock->expects($this->once())
            ->method('getApiLogFilter')
            ->willReturn('');

        $this->assertTrue($this->requestLogValidator->validate($this->requestMock));
    }

    /**
     * @param   string  $filter
     * @param   string  $requestUri
     * @param   array   $requestQueryParams
     * @param   bool    $expected
     * @dataProvider getTestValidateWithFilterDataProvider
     */
    public function testValidateWithFilter($filter, $requestUri, array $requestQueryParams, $expected)
    {
        $this->configMock->expects($this->once())
            ->method('isApiLogEnabled')
            ->willReturn(true);

        $this->configMock->expects($this->once())
            ->method('getApiLogFilter')
            ->willReturn($filter);

        $this->requestMock->expects($this->once())
            ->method('getQueryParams')
            ->willReturn($requestQueryParams);

        $this->requestMock->expects($this->once())
            ->method('getUri')
            ->willReturn($requestUri);

        $this->assertSame($expected, $this->requestLogValidator->validate($this->requestMock));
    }

    /**
     * @return  array
     */
    public function getTestValidateWithFilterDataProvider()
    {
        return [
            ['api/orders', 'locales', [], false],
            ['api/orders|api/locales', 'locales', [], true],
            ['api/orders|api/locales', 'orders', [], true],
            ['api/orders\?order_state_codes=WAITING_ACCEPTANCE|api/locales', 'orders', [], false],
            ['api/orders\?order_state_codes=WAITING_ACCEPTANCE|api/locales', 'orders', ['order_state_codes' => 'WAITING_ACCEPTANCE'], true],
        ];
    }
}
