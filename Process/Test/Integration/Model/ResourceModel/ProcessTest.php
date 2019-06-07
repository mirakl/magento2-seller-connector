<?php
namespace MiraklSeller\Process\Test\Integration\ResourceModel;

use MiraklSeller\Process\Model\ResourceModel\Process;
use MiraklSeller\Process\Test\Integration;

/**
 * @group process
 * @group model
 * @coversDefaultClass \MiraklSeller\Process\Model\ResourceModel\Process
 */
class ProcessTest extends Integration\TestCase
{
    /**
     * @var Process
     */
    protected $resourceModel;

    protected function setUp()
    {
        parent::setUp();
        $this->resourceModel = $this->processResourceFactory->create();
    }

    /**
     * @covers ::markAsTimeout
     */
    public function testMarkAsTimeout()
    {
        $process = $this->createSampleProcess();
        $process->setStatus(\MiraklSeller\Process\Model\Process::STATUS_PROCESSING);
        $process->setCreatedAt('2017-07-19 05:00:00');
        $this->resourceModel->save($process);

        $this->resourceModel->markAsTimeout(10); // 10 minutes

        // Reload process
        $process = $this->getProcessById($process->getId());

        $this->assertTrue($process->isTimeout());
    }

    /**
     * @covers ::markAsTimeout
     * @expectedException \Exception
     * @expectedExceptionMessage Delay for expired processes cannot be empty
     */
    public function testMarkAsTimeoutWithEmptyDelay()
    {
        $this->resourceModel->markAsTimeout(0);
    }
}