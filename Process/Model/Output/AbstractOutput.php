<?php
namespace MiraklSeller\Process\Model\Output;

use MiraklSeller\Process\Helper\Data as ProcessHelper;
use MiraklSeller\Process\Model\Process;
use Psr\Log\LoggerInterface;

abstract class AbstractOutput implements OutputInterface
{
    /**
     * @var Process
     */
    protected $process;

    /**
     * @var ProcessHelper
     */
    protected $processHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    abstract public function display($str);

    /**
     * @param   ProcessHelper   $processHelper
     * @param   Process         $process
     * @param   LoggerInterface $logger
     */
    public function __construct(ProcessHelper $processHelper, Process $process, LoggerInterface $logger)
    {
        $this->processHelper = $processHelper;
        $this->process = $process;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $memory = $this->processHelper->formatSize(memory_get_peak_usage(true));
        $this->display(
            sprintf('memory: %s, sapi: %s, pid: %s, uid: %s', $memory, PHP_SAPI, getmypid(), getmyuid())
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        $class = get_class($this);

        return strtolower(substr($class, strrpos($class, '\\') + 1));
    }
}
