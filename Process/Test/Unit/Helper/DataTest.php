<?php
namespace MiraklSeller\Process\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Api\Helper\Data as ApiHelper;
use MiraklSeller\Process\Helper\Data as ProcessHelper;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ResourceModel\Process\Collection;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * @group process
 * @group helper
 * @coversDefaultClass \MiraklSeller\Process\Helper\Data
 */
class ProcessTest extends TestCase
{
    /**
     * @var ProcessHelper
     */
    protected $helper;

    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionFactoryMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $context = $objectManager->getObject(Context::class);

        $apiHelper = $this->getMockBuilder(ApiHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $objectManager->getObject(ProcessHelper::class, [
            'context' => $context,
            'apiHelper' => $apiHelper,
            'collectionFactory' => $this->collectionFactoryMock,
        ]);
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcess()
    {
        /** @var Collection|\PHPUnit_Framework_MockObject_MockObject $processingMock */
        $processingMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processingMock->expects($this->exactly(2))
            ->method('addProcessingFilter')
            ->willReturnSelf();
        $processingMock->expects($this->exactly(2))
            ->method('getColumnValues')
            ->willReturn([]);

        /** @var Collection|\PHPUnit_Framework_MockObject_MockObject $pendingMock */
        $pendingMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pendingMock->expects($this->exactly(2))
            ->method('addPendingFilter')
            ->willReturnSelf();
        $pendingMock->expects($this->exactly(2))
            ->method('addExcludeHashFilter')
            ->willReturnSelf();
        $pendingMock->expects($this->exactly(2))
            ->method('addParentCompletedFilter')
            ->willReturnSelf();
        $pendingMock->expects($this->exactly(2))
            ->method('setOrder')
            ->willReturnSelf();
        $pendingMock->expects($this->exactly(2))
            ->method('count')
            ->willReturnOnConsecutiveCalls(0, 3);
        $pendingMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->createMock(Process::class));

        $selectMock = $this->createMock(Select::class);
        $selectMock->expects($this->exactly(2))
            ->method('limit')
            ->willReturnSelf();
        $pendingMock->expects($this->exactly(2))
            ->method('getSelect')
            ->willReturn($selectMock);

        $this->collectionFactoryMock->expects($this->exactly(4))
            ->method('create')
            ->willReturnOnConsecutiveCalls($processingMock, $pendingMock, $processingMock, $pendingMock);

        $this->assertNull($this->helper->getPendingProcess());
        $this->assertInstanceOf(Process::class, $this->helper->getPendingProcess());
    }
}