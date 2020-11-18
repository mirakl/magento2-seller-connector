<?php
namespace MiraklSeller\Sales\Helper\Order;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory as ConnectionCollectionFactory;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

class Sync extends AbstractHelper
{
    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    private $processResourceFactory;

    /**
     * @var ConnectionCollectionFactory
     */
    private $connectionCollectionFactory;

    /**
     * @param   Context                     $context
     * @param   ProcessFactory              $processFactory
     * @param   ProcessResourceFactory      $processResourceFactory
     * @param   ConnectionCollectionFactory $connectionCollectionFactory
     */
    public function __construct(
        Context $context,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        ConnectionCollectionFactory $connectionCollectionFactory
    ) {
        parent::__construct($context);

        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->connectionCollectionFactory = $connectionCollectionFactory;
    }

    /**
     * @param   string  $processType
     * @return  Process[]
     */
    public function synchronizeAllConnections($processType = Process::TYPE_ADMIN)
    {
        $processes = [];

        $connections = $this->connectionCollectionFactory->create();

        /** @var Connection $connection */
        foreach ($connections as $connection) {
            $processes[] = $this->synchronizeConnection($connection, $processType);
        }

        return $processes;
    }

    /**
     * Creates a process for synchronization of all orders from specifed marketplace connection
     *
     * @param   Connection  $connection
     * @param   string      $processType
     * @return  Process
     */
    public function synchronizeConnection(Connection $connection, $processType = Process::TYPE_ADMIN)
    {
        $process = $this->processFactory->create()
            ->setType($processType)
            ->setName('Synchronize Mirakl orders')
            ->setHelper(\MiraklSeller\Sales\Helper\Order\Process::class)
            ->setMethod('synchronizeConnection')
            ->setParams([$connection->getId()]);

        $this->processResourceFactory->create()->save($process);

        return $process;
    }
}