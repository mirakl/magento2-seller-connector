<?php
namespace MiraklSeller\Process\Console\Command;

use Magento\Framework\Console\Cli;
use MiraklSeller\Process\Helper\Config;
use MiraklSeller\Process\Model\HistoryClearer;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\Process as ProcessResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearHistoryCommand extends Command
{
    const BEFORE_DATE_OPTION = 'before';

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
     * @param  string|null  $name
     */
    public function __construct(
        Config $config,
        ProcessFactory $processFactory,
        ProcessResource $processResource,
        $name = null
    ) {
        parent::__construct($name);
        $this->config = $config;
        $this->processFactory = $processFactory;
        $this->processResource = $processResource;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::BEFORE_DATE_OPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Clear history of processes created before date (yyyy-mm-dd)'
            )
        ];

        $this->setName('mirakl:process:clear-history')
             ->setDescription('Handles Mirakl processes history clearing')
             ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $beforeDate = $input->getOption(self::BEFORE_DATE_OPTION);

        if ($beforeDate && !preg_match('(^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$)', $beforeDate)) {
            $output->writeln('<error>--before parameter must have yyyy-mm-dd format</error>');

            return Cli::RETURN_FAILURE;
        }

        $beforeDate = $beforeDate ? $beforeDate .' 00:00:00' : $this->config->getProcessClearHistoryBeforeDate();

        /** @var Process $process */
        $process = $this->processFactory->create();
        $process->setStatus(Process::STATUS_PENDING)
                ->setType(Process::TYPE_CLI)
                ->setName('Clear history of processes created before configured days count or a given date')
                ->setHelper(HistoryClearer::class)
                ->setMethod('execute')
                ->setParams([$beforeDate]);
        $this->processResource->save($process);
        $process->addOutput('cli');

        $process->run();

        return Cli::RETURN_SUCCESS;
    }
}