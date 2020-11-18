<?php
namespace MiraklSeller\Sales\Cron;

use MiraklSeller\Process\Model\Process;
use MiraklSeller\Sales\Helper\Config;
use MiraklSeller\Sales\Helper\Order\Accept as OrderAccept;

class AcceptAllOrders
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderAccept
     */
    protected $orderAccept;

    /**
     * @param   Config      $config
     * @param   OrderAccept $orderAccept
     */
    public function __construct(Config $config, OrderAccept $orderAccept)
    {
        $this->config = $config;
        $this->orderAccept = $orderAccept;
    }

    /**
     * Accept all orders from all marketplace connections
     *
     * @return  void
     */
    public function execute()
    {
        if (!$this->config->isAutoAcceptOrdersEnabled()) {
            return; // Do not do anything if auto accept is off
        }

        $processes = $this->orderAccept->acceptAll(Process::TYPE_CRON);

        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run();
        }
    }
}
