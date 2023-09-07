<?php
namespace MiraklSeller\Process\Console\Command;

use Magento\Framework\Console\Cli;
use MiraklSeller\Process\Helper\Data as Helper;
use MiraklSeller\Process\Model\ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCommand extends Command
{
    /**
     * Pending option key
     */
    const PENDING_OPTION = 'pending';

    /**
     * Run specific id key
     */
    const RUN_PROCESS_OPTION = 'run';

    /**
     * Run specific id key
     */
    const FORCE_EXECUTION_OPTION = 'force';

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    private $processResourceFactory;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   Helper                  $helper
     * @param   string|null             $name
     */
    public function __construct(
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        Helper $helper,
        $name = null
    ) {
        parent::__construct($name);
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::PENDING_OPTION,
                null,
                InputOption::VALUE_NONE,
                'Execute the older PENDING process (one by one)'
            ),
            new InputOption(
                self::RUN_PROCESS_OPTION,
                null,
                InputOption::VALUE_REQUIRED,
                'Execute a specific process id'
            ),
            new InputOption(
                self::FORCE_EXECUTION_OPTION,
                null,
                InputOption::VALUE_NONE,
                'Force process execution even if not in pending status'
            )
        ];

        $this->setName('mirakl:seller:process')
            ->setDescription('Handles Mirakl processes execution')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($processId = $input->getOption(self::RUN_PROCESS_OPTION)) {
            $output->writeln(sprintf('<info>Processing #%s</info>', $processId));
            $process = $this->processFactory->create();
            $this->processResourceFactory->create()->load($process, $processId);
            if (!$process->getId()) {
                throw new \InvalidArgumentException('This process no longer exists.');
            }
            if (!$process->isPending() && !$input->getOption(self::FORCE_EXECUTION_OPTION)) {
                throw new \Exception('This process has already been executed. Use --force option to force execution.');
            }
            $process->addOutput('cli');
            $process->run(true);
        } elseif ($input->getOption(self::PENDING_OPTION)) {
            $process = $this->helper->getPendingProcess();
            if ($process) {
                $output->writeln(sprintf('<info>Processing #%s</info>', $process->getId()));
                $process->addOutput('cli');
                $process->run();
            } else {
                $output->writeln('<comment>Nothing to process</comment>');
            }
        } else {
            $output->writeln('<error>Please provide an option or use help</error>');
        }

        return Cli::RETURN_SUCCESS;
    }
}
