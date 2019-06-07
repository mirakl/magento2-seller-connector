<?php
namespace MiraklSeller\Process\Test\Integration\Helper;

use MiraklSeller\Process\Helper\Data as Helper;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Test\Integration;

/**
 * @group process
 * @group helper
 * @coversDefaultClass \MiraklSeller_Process_Helper_Data
 */
class DataTest extends Integration\TestCase
{
    /**
     * @var Helper
     */
    protected $helper;

    protected function setUp()
    {
        parent::setUp();
        $this->helper = $this->objectManager->get(Helper::class);
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testNoPendingProcessFound()
    {
        // No process is present in db, no pending process should be found
        // Do not use assertNull() to avoid to print object dump when test fail
        $this->assertTrue($this->helper->getPendingProcess() === null);
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetOlderPendingProcess()
    {
        // Create 2 sample processes for test
        $process1 = $this->createSampleProcess();
        $process2 = $this->createSampleProcess();

        // Ensure that both processes are in pending status
        $this->assertTrue($process1->isPending());
        $this->assertTrue($process2->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->helper->getPendingProcess();

        // Ensure that process #1 is the pending process because older than process #2
        $this->assertFalse($pendingProcess === null);
        $this->assertEquals($process1->getId(), $pendingProcess->getId());
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcessWithParentCompleted()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         */
        $process1 = $this->createSampleProcess();
        $process2 = $this->createSampleProcess($process1->getId());

        // Ensure that both processes are in pending status
        $this->assertTrue($process1->isPending());
        $this->assertTrue($process2->isPending());

        // Ensure that process #2 is a child of process #1
        $this->assertEquals($process1->getId(), $process2->getParentId());

        // Mock the process helper method for test
        $helperMock = new class {
            public function run(Process $process)
            {
                $process->output('This is a test');
            }
        };
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->willReturn($helperMock);

        $process1->run();

        // Ensure that process #1 has completed
        $this->assertTrue($process1->isCompleted());

        // Retrieve real pending process
        $pendingProcess = $this->helper->getPendingProcess();

        // Ensure that process #2 is the pending process
        $this->assertFalse($pendingProcess === null);
        $this->assertEquals($process2->getId(), $pendingProcess->getId());
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcessWhenParentHasFailed()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         * process #3
         */
        $process1 = $this->createSampleProcess();
        $process2 = $this->createSampleProcess($process1->getId());
        $process3 = $this->createSampleProcess();

        // Do not use fail() method in order to not cancel children automatically
        $process1->stop(Process::STATUS_ERROR);

        // Ensure that process #2 and #3 are in pending process
        $this->assertTrue($process2->isPending());
        $this->assertTrue($process3->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->helper->getPendingProcess();

        // Ensure that process #3 is the pending process because #1 is the parent of #2 and has failed
        $this->assertFalse($pendingProcess === null);
        $this->assertEquals($process3->getId(), $pendingProcess->getId());
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcessWhenParentHasFailedInCascade()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         *      |_ process #3
         */
        $process1 = $this->createSampleProcess();
        $process2 = $this->createSampleProcess($process1->getId());
        $process3 = $this->createSampleProcess($process2->getId());

        // Do not use fail() method in order to not cancel children automatically
        $process1->stop(Process::STATUS_ERROR);

        // Ensure that process #2 and #3 are in pending process
        $this->assertTrue($process2->isPending());
        $this->assertTrue($process3->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->helper->getPendingProcess();

        // Ensure that no pending process is found because no parent has completed
        $this->assertTrue($pendingProcess === null);
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testCannotGetPendingProcessWithTheSameHash()
    {
        // Create 2 sample processes for test
        $process1 = $this->createSampleProcess();
        $process1->setStatus(Process::STATUS_PROCESSING);
        $this->processResourceFactory->create()->save($process1);
        $process2 = $this->createSampleProcess();

        // Ensure that statuses are correct
        $this->assertTrue($process1->isProcessing());
        $this->assertTrue($process2->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->helper->getPendingProcess();

        // We should not have a pending process because processes have the same hash
        $this->assertTrue($pendingProcess === null);
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcessWithDifferentHash()
    {
        // Create 2 sample processes for test
        $process1 = $this->createSampleProcess();
        $process1->setStatus(Process::STATUS_PROCESSING)->setHash(md5(uniqid()));
        $this->processResourceFactory->create()->save($process1);
        $process2 = $this->createSampleProcess();

        // Ensure that statuses are correct
        $this->assertTrue($process1->isProcessing());
        $this->assertTrue($process2->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->helper->getPendingProcess();

        // Process #2 is the pending process because hash is different
        $this->assertFalse($pendingProcess === null);
        $this->assertEquals($process2->getId(), $pendingProcess->getId());
    }
}