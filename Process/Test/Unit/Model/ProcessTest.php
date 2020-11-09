<?php
namespace MiraklSeller\Process\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\Output;
use MiraklSeller\Process\Model\Output\Factory as OutputFactory;
use MiraklSeller\Process\Model\ProcessFactory as ProcessModelFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory as ProcessCollectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * @group process
 * @group model
 * @coversDefaultClass \MiraklSeller\Process\Model\Process
 */
class ProcessTest extends TestCase
{
    /**
     * @var Process
     */
    protected $process;

    /**
     * @var OutputFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $outputFactory;

    /**
     * @var ProcessModelFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $processModelFactory;

    /**
     * @var ProcessResourceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $processResourceFactory;

    /**
     * @var ProcessCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $processCollectionFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->outputFactory = $this->createMock(OutputFactory::class);
        $this->processModelFactory = $this->createMock(ProcessModelFactory::class);
        $this->processResourceFactory = $this->createMock(ProcessResourceFactory::class);
        $this->processCollectionFactory = $this->createMock(ProcessCollectionFactory::class);

        $this->process = (new ObjectManager($this))->getObject(Process::class, [
            'context' => $this->createMock(\Magento\Framework\Model\Context::class),
            'registry' => $this->createMock(\Magento\Framework\Registry::class),
            'objectManager' => $this->createMock(\Magento\Framework\ObjectManagerInterface::class),
            'urlBuilder' => $this->createMock(\Magento\Framework\UrlInterface::class),
            'apiHelper' => $this->createMock(\MiraklSeller\Api\Helper\Data::class),
            'errorHelper' => $this->createMock(\MiraklSeller\Process\Helper\Error::class),
            'processHelper' => $this->createMock(\MiraklSeller\Process\Helper\Data::class),
            'processConfig' => $this->createMock(\MiraklSeller\Process\Helper\Config::class),
            'processModelFactory' => $this->processModelFactory,
            'processResourceFactory' => $this->processResourceFactory,
            'processCollectionFactory' => $this->processCollectionFactory,
            'outputFactory' => $this->outputFactory,
        ]);
    }

    public function testGetStatuses()
    {
        $expectedStatuses = [
            'pending',
            'processing',
            'idle',
            'completed',
            'stopped',
            'timeout',
            'cancelled',
            'error',
        ];
        $this->assertSame(array_combine($expectedStatuses, $expectedStatuses), Process::getStatuses());
    }

    /**
     * @covers ::isEnded
     */
    public function testIsEnded()
    {
        $this->process->setStatus(Process::STATUS_STOPPED);
        $this->assertTrue($this->process->isEnded());
        $this->process->setStatus(Process::STATUS_COMPLETED);
        $this->assertTrue($this->process->isEnded());
        $this->process->setStatus(Process::STATUS_CANCELLED);
        $this->assertTrue($this->process->isEnded());
        $this->process->setStatus(Process::STATUS_ERROR);
        $this->assertTrue($this->process->isEnded());
        $this->process->setStatus(Process::STATUS_TIMEOUT);
        $this->assertTrue($this->process->isEnded());
        $this->process->setStatus(Process::STATUS_IDLE);
        $this->assertFalse($this->process->isEnded());
    }

    /**
     * @covers ::canRun
     */
    public function testCanRun()
    {
        /** @var Process|\PHPUnit\Framework\MockObject\MockObject $processMock */
        $processMock = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['canRun', '__call'])
            ->getMock();
        $processMock->expects($this->exactly(5))
            ->method('getParent')
            ->willReturnOnConsecutiveCalls(null, null, null, $processMock, $processMock);
        $processMock->expects($this->exactly(5))
            ->method('isProcessing')
            ->willReturnOnConsecutiveCalls(true, false, false, false, false);
        $processMock->expects($this->exactly(4))
            ->method('isStatusIdle')
            ->willReturnOnConsecutiveCalls(false, true, false, false);
        $processMock->expects($this->exactly(2))
            ->method('isCompleted')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->assertFalse($processMock->canRun());
        $this->assertTrue($processMock->canRun());
        $this->assertFalse($processMock->canRun());
        $this->assertFalse($processMock->canRun());
        $this->assertTrue($processMock->canRun());
    }

    /**
     * @covers ::run
     */
    public function testRun()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot run a process that is not in pending status');

        /** @var Process|\PHPUnit\Framework\MockObject\MockObject $processMock */
        $processMock = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['run'])
            ->getMock();
        $processMock->expects($this->exactly(3))
            ->method('isPending')
            ->willReturnOnConsecutiveCalls(true, false, false);
        $processMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnSelf();

        $processMock->run();
        $processMock->run(true);

        $processMock->run();
    }

    /**
     * @covers ::getParent
     */
    public function testGetParent()
    {
        $this->processModelFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->process);

        $processResource = $this->createMock(\MiraklSeller\Process\Model\ResourceModel\Process::class);
        $this->processResourceFactory->expects($this->once())
            ->method('create')
            ->willReturn($processResource);

        $this->process->setParentId(null);
        $this->assertNull($this->process->getParent());

        $this->process->setParentId(1234);
        $this->assertInstanceOf(Process::class, $this->process->getParent());
    }

    /**
     * @covers ::addOutput
     */
    public function testAddOutput()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid output specified.');

        $outputMock = $this->createMock(Output\Db::class);

        $this->assertEquals($this->process, $this->process->addOutput('cli')); // no exception expected
        $this->assertEquals($this->process, $this->process->addOutput($outputMock));  // no exception expected

        $this->process->addOutput($this->anything());
    }

    /**
     * @covers ::execute
     */
    public function testExecuteProcessingThrowsException()
    {
        $processResource = $this->createMock(\MiraklSeller\Process\Model\ResourceModel\Process::class);
        $this->processResourceFactory->expects($this->any())
            ->method('create')
            ->willReturn($processResource);

        $processCollection = $this->createMock(\MiraklSeller\Process\Model\ResourceModel\Process\Collection::class);
        $processCollection->expects($this->exactly(1))
            ->method('addParentFilter')
            ->willReturnSelf();
        $this->processCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($processCollection);

        $outputMock = $this->getMockBuilder(Output\Db::class)
            ->setConstructorArgs([
                'processHelper' => $this->createMock(\MiraklSeller\Process\Helper\Data::class),
                'process' => $this->process,
                'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            ])
            ->setMethodsExcept(['display'])
            ->getMock();
        $this->outputFactory->expects($this->any())
            ->method('create')
            ->willReturn($outputMock);

        $this->process->setStatus(Process::STATUS_PROCESSING);
        $this->process->execute();

        $this->assertEquals($this->process->getStatus(), Process::STATUS_ERROR);
        $this->assertStringContainsString('Process is already running.', $this->process->getOutput());
    }

    /**
     * @covers ::execute
     */
    public function testExecuteUnknownHelperMethodThrowsException()
    {
        $processResource = $this->createMock(\MiraklSeller\Process\Model\ResourceModel\Process::class);
        $this->processResourceFactory->expects($this->any())
            ->method('create')
            ->willReturn($processResource);

        $processCollection = $this->createMock(\MiraklSeller\Process\Model\ResourceModel\Process\Collection::class);
        $processCollection->expects($this->exactly(2))
            ->method('addParentFilter')
            ->willReturnSelf();
        $this->processCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($processCollection);

        $outputMock = $this->getMockBuilder(Output\Db::class)
            ->setConstructorArgs([
                'processHelper' => $this->createMock(\MiraklSeller\Process\Helper\Data::class),
                'process' => $this->process,
                'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            ])
            ->setMethodsExcept(['display'])
            ->getMock();
        $this->outputFactory->expects($this->any())
            ->method('create')
            ->willReturn($outputMock);

        $this->process->setHelper('One\Unknown\Class\Name');
        $this->process->setMethod('foo');
        $this->process->execute();

        $this->assertEquals($this->process->getStatus(), Process::STATUS_ERROR);
        $this->assertStringContainsString('Invalid helper specified', $this->process->getOutput());

        $this->process->setHelper('MiraklSeller\Process\Helper\Data');
        $this->process->setMethod('bar');
        $this->process->execute();

        $this->assertEquals($this->process->getStatus(), Process::STATUS_ERROR);
        $this->assertStringContainsString('Invalid helper method specified', $this->process->getOutput());
    }
}
