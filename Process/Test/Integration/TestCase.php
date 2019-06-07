<?php
namespace MiraklSeller\Process\Test\Integration;

use Magento\Framework\ObjectManager\ObjectManager;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ProcessFactory as ProcessModelFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory as ProcessCollectionFactory;

abstract class TestCase extends \MiraklSeller\Api\Test\Integration\TestCase
{
    /**
     * @var ProcessModelFactory
     */
    protected $processModelFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @var ProcessCollectionFactory
     */
    protected $processCollectionFactory;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var array
     */
    protected $processIds = [];

    protected function setUp()
    {
        parent::setUp();

        $this->processModelFactory = $this->objectManager->create(ProcessModelFactory::class);
        $this->processResourceFactory = $this->objectManager->create(ProcessResourceFactory::class);
        $this->processCollectionFactory = $this->objectManager->create(ProcessCollectionFactory::class);
        $this->objectManagerMock = $this->createMock(ObjectManager::class);
    }

    protected function tearDown()
    {
        parent::tearDown();

        if (!empty($this->processIds)) {
            // Delete created processes
            $processes = $this->processCollectionFactory->create()
                ->addIdFilter($this->processIds);

            $resource = $this->processResourceFactory->create();
            foreach ($processes as $process) {
                $resource->delete($process);
            }
        }
    }

    /**
     * @param   int|null    $parentId
     * @return  Process
     */
    protected function createSampleProcess($parentId = null)
    {
        $process = $this->processModelFactory->create(['objectManager' => $this->objectManagerMock]);
        $process->setType('TESTS')
            ->setName('Sample process for integration tests')
            ->setHelper('MiraklSeller\Process\Helper\Data')
            ->setMethod('run')
            ->setParams(['foo', ['bar']])
            ->setParentId($parentId);

        $this->processResourceFactory->create()->save($process);

        $this->processIds[] = $process->getId();

        return $process;
    }

    /**
     * @param   int $processId
     * @return  Process
     */
    protected function getProcessById($processId)
    {
        $process = $this->processModelFactory->create();
        $this->processResourceFactory->create()->load($process, $processId);

        return $process;
    }
}