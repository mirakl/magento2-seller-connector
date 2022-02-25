<?php
namespace MiraklSeller\Process\Cron;

use MiraklSeller\Process\Helper\Config;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\Process as ProcessResource;
use MiraklSeller\Process\Model\HistoryClearer;

class ClearHistory
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ProcessResource
     */
    private $processResource;

    /**
     * @param Config          $config
     * @param ProcessFactory  $processFactory
     * @param ProcessResource $processResource
     */
    public function __construct(
        Config $config,
        ProcessFactory $processFactory,
        ProcessResource $processResource
    ) {
        $this->config = $config;
        $this->processFactory = $processFactory;
        $this->processResource = $processResource;
    }

    /**
     * Clears history of processes created before configured days count
     *
     * @return  void
     */
    public function execute()
    {
        $beforeDate = $this->config->getProcessClearHistoryBeforeDate();
        /** @var Process $process */
        $process = $this->processFactory->create();
        $process->setStatus(Process::STATUS_PENDING)
                ->setType(Process::TYPE_CLI)
                ->setName('Clear history of processes created before configured days count')
                ->setHelper(HistoryClearer::class)
                ->setMethod('execute')
                ->setParams([$beforeDate]);
        $this->processResource->save($process);
        $process->addOutput('cli');
        $process->run();
    }
}
