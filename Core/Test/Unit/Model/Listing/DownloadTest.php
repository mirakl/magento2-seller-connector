<?php
namespace MiraklSeller\Core\Test\Unit\Model\Listing;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Download;
use MiraklSeller\Core\Model\Listing\Export\Products;
use PHPUnit\Framework\TestCase;

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
    protected $downloadModel;

    /**
     * @var Download\Adapter\AdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $adapterMock;

    /**
     * @var Products|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $exportModelMock;

    protected function setUp(): void
    {
        $this->adapterMock = $this->createMock(Download\Adapter\AdapterInterface::class);
        $this->exportModelMock = $this->createMock(Products::class);

        $objectManager = new ObjectManager($this);
        $this->downloadModel = $objectManager->getObject(Download::class, [
            'adapter' => $this->adapterMock,
            'exportModel' => $this->exportModelMock,
        ]);
    }

    /**
     * @covers ::prepare
     */
    public function testPrepare()
    {
        $expectedResult = 'name;description';

        $this->adapterMock->expects($this->once())
            ->method('write')
            ->willReturn(123);
        $this->adapterMock->expects($this->once())
            ->method('getContents')
            ->willReturn($expectedResult);

        $this->exportModelMock->expects($this->once())
            ->method('export')
            ->willReturn([['name', 'description']]);

        /** @var Listing $listingMock */
        $listingMock = $this->createMock(Listing::class);
        $this->assertSame($expectedResult, $this->downloadModel->prepare($listingMock));
    }

    /**
     * @covers ::prepare
     */
    public function testPrepareWithEmptyProducts()
    {
        $expectedResult = '';

        $this->exportModelMock->expects($this->once())
            ->method('export')
            ->willReturn([]);

        /** @var Listing $listingMock */
        $listingMock = $this->createMock(Listing::class);

        $this->assertSame($expectedResult, $this->downloadModel->prepare($listingMock));
    }

    /**
     * @covers ::getFileExtension
     */
    public function testGetFileExtension()
    {
        $expectedResult = 'csv';

        $this->adapterMock->expects($this->once())
            ->method('getFileExtension')
            ->willReturn($expectedResult);

        $this->assertSame($expectedResult, $this->downloadModel->getFileExtension());
    }
}
