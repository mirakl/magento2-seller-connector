<?php
namespace Mirakl\Test\Integration\Core\Model\Listing;

use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Download;
use MiraklSeller\Core\Test\Integration\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @coversDefaultClass \MiraklSeller\Core\Model\Listing\Download
 */
class DownloadTest extends TestCase
{
    /**
     * @var Download
     */
    protected $download;

    protected function setUp(): void
    {
        parent::setUp();

        $this->download = $this->objectManager->create(Download::class);
    }

    /**
     * @covers ::prepare
     * @param   array   $productIds
     * @param   string  $expectedResult
     * @dataProvider getTestPrepareDataProvider
     * @magentoConfigFixture current_store web/unsecure/base_url http://foobar.com/
     * @magentoConfigFixture current_store mirakl_seller_core/listing/nb_image_exported 1
     * @magentoDbIsolation enabled
     */
    public function testPrepare($productIds, $expectedResult)
    {
        /** @var Listing|\PHPUnit\Framework\MockObject\MockObject $listingMock */
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->any())
            ->method('getExportableAttributes')
            ->willReturn([]);

        /** @var Listing|\PHPUnit\Framework\MockObject\MockObject $listingMock */
        $listingMock = $this->createMock(Listing::class);
        $listingMock->expects($this->any())
            ->method('getProductIds')
            ->willReturn($productIds);

        $listingMock->expects($this->any())
            ->method('getVariantsAttributes')
            ->willReturn([]);

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $result = $this->download->prepare($listingMock);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestPrepareDataProvider()
    {
        return [
            [[232, 233, 234], $this->_getFileContents('expected_download_result_1.csv')],
            [[36, 37, 38], $this->_getFileContents('expected_download_result_2.csv')],
            [[], ''],
        ];
    }

    /**
     * @covers ::getFileExtension
     */
    public function testGetFileExtension()
    {
        $this->assertSame('csv', $this->download->getFileExtension());
    }

    /**
     * @covers ::getFileExtension
     */
    public function testGetFileExtensionWithCustomAdapter()
    {
        $customAdapter = new class() implements Download\Adapter\AdapterInterface {
            public function getContents() {}

            public function getFileExtension() { return 'xml'; }

            public function write(array $data) {}
        };

        $factoryMock = $this->createMock(Download\Adapter\AdapterFactory::class);
        $factoryMock->expects($this->any())
            ->method('create')
            ->willReturn($customAdapter);

        /** @var Download $downloadModel */
        $downloadModel = $this->objectManager->create(Download::class, [
            'adapterFactory' => $factoryMock
        ]);

        $this->assertSame('xml', $downloadModel->getFileExtension());
    }
}
