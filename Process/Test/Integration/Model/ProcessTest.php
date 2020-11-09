<?php
namespace MiraklSeller\Process\Test\Integration\Model;

use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Test\Integration;

/**
 * @group process
 * @group model
 * @coversDefaultClass \MiraklSeller\Process\Model\Process
 */
class ProcessTest extends Integration\TestCase
{
    /**
     * @covers ::run
     */
    public function testRunProcessWithParams()
    {
        // Create a sample process for test
        $process = $this->createSampleProcess();

        // Mock the process helper method for test
        $helperMock = new class {
            public function run(Process $process, $foo, $bar)
            {
                $process->output('This is a test');
                Integration\TestCase::assertTrue($process->isProcessing());
                Integration\TestCase::assertSame('foo', $foo);
                Integration\TestCase::assertSame(['bar'], $bar);
            }
        };
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->willReturn($helperMock);

        // Ensure that process has been saved correctly in pending status and with params
        $this->assertNotEmpty($process->getId());
        $this->assertTrue($process->isPending());
        $this->assertNull($process->getParentId());
        $this->assertNotEmpty($process->getParams());

        // Run the process
        $process->run();

        // Process should be completed without any error
        $this->assertTrue($process->isCompleted());

        $this->assertGreaterThan(0, $process->getDuration());
        $this->assertNotEmpty($process->getOutput());
    }

    /**
     * @covers ::run
     */
    public function testRunProcessWithUserError()
    {
        // Create a sample process for test
        $process = $this->createSampleProcess();

        // Mock the process helper method for test
        $helperMock = new class {
            public function run()
            {
                trigger_error('This is a sample user error', E_USER_ERROR);
            }
        };
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->willReturn($helperMock);

        // Run the process, an error should occurred and mark the process has "error"
        $process->run();

        // Process must have the status "error" and error message should be logged in process output
        $this->assertTrue($process->isError());
        $this->assertNotEmpty($process->getOutput());
    }

    /**
     * @covers ::run
     */
    public function testRunChildProcessWhenParentIsCompleted()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         */
        $process1 = $this->createSampleProcess();
        $process2 = $this->createSampleProcess($process1->getId());

        // Mock the process helper method for test
        $helperMock = new class {
            public function run(Process $process)
            {
                $process->output('This is a test');
            }
        };
        $this->objectManagerMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($helperMock);

        // Run both processes one after the other
        $process1->run();
        $process2->run();

        // Ensure that both processes have been executed successfully
        $this->assertTrue($process1->isCompleted());
        $this->assertTrue($process2->isCompleted());
    }

    /**
     * @covers ::run
     */
    public function testCannotRunChildProcessIfParentIsNotCompleted()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         */
        $process1 = $this->createSampleProcess();
        $process2 = $this->createSampleProcess($process1->getId());

        try {
            // Use try/catch in order to be able to test processes status afterwards
            $process2->run();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertStringContainsString('has not completed yet', $e->getMessage());
        }

        // Verify that statuses did not change
        $this->assertTrue($process1->isPending());
        $this->assertTrue($process2->isPending());
    }

    /**
     * @coversNothing
     */
    public function testCancelChildrenProcessesInCascadeWhenParentFails()
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

        $process1->fail('Failing process #1 to test children automatic cascade cancellation');

        // Ensure that process #1 has failed and that other processes have been cancelled in cascade
        $this->assertTrue($process1->isError());
        $this->assertTrue($this->getProcessById($process2->getId())->isCancelled());
        $this->assertTrue($this->getProcessById($process3->getId())->isCancelled());
    }

    /**
     * @coversNothing
     */
    public function testDeleteParentProcessMustDeleteChildrenInCascade()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         *  |_ process #3
         *      |_ process #4
         *  |_ process #5
         */
        $process1 = $this->createSampleProcess();
        $process2 = $this->createSampleProcess($process1->getId());
        $process3 = $this->createSampleProcess($process1->getId());
        $process4 = $this->createSampleProcess($process3->getId());
        $process5 = $this->createSampleProcess($process1->getId());

        // Delete the main process should delete all children in cascade
        $this->processResourceFactory->create()->delete($process1);

        $this->assertNull($this->getProcessById($process1->getId())->getId());
        $this->assertNull($this->getProcessById($process2->getId())->getId());
        $this->assertNull($this->getProcessById($process3->getId())->getId());
        $this->assertNull($this->getProcessById($process4->getId())->getId());
        $this->assertNull($this->getProcessById($process5->getId())->getId());
    }
}
