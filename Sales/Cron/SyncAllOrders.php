<?php
namespace MiraklSeller\Sales\Cron;

use MiraklSeller\Process\Model\Process;
use MiraklSeller\Sales\Helper\Config;
use MiraklSeller\Sales\Helper\Order\Sync as OrderSync;

class SyncAllOrders
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderSync
     */
    protected $orderSync;

    /**
     * @param   Config      $config
     * @param   OrderSync   $orderSync
     */
    public function __construct(Config $config, OrderSync $orderSync)
    {
        $this->config = $config;
        $this->orderSync = $orderSync;
    }

    /**
     * Synchronizes all orders from all marketplace connections
     *
     * @return  void
     */
    public function execute()
    {
        if (!$this->config->isAutoOrdersImport()) {
            return; // Do not do anything if auto import is off
        }

        $processes = $this->orderSync->synchronizeAllConnections(Process::TYPE_CRON, Process::STATUS_IDLE);

        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run(true);
        }
    }
}
