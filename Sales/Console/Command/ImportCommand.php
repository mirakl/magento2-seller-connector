<?php
namespace MiraklSeller\Sales\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Sales\Helper\Order\Sync as OrderSync;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Sales\Helper\Config;

class ImportCommand extends Command
{
    /**
     * Import orders from specific connection
     */
    const IMPORT_CONNECTION_OPTION = 'connection';

    /**
     * Import orders from specific all connections
     */
    const IMPORT_ALL_CONNECTIONS_OPTION = 'all';

    /**
     * @var State
     */
    private $appState;

    /**
     * @var OrderSync
     */
    private $orderSync;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    private $connectionResourceFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param   State                       $state
     * @param   OrderSync                   $orderSync
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   Config                      $config
     * @param   string|null                 $name
     */
    public function __construct(
        State $state,
        OrderSync $orderSync,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        Config $config,
        $name = null
    ) {
        parent::__construct($name);

        $this->appState = $state;
        $this->orderSync = $orderSync;
        $this->connectionFactory = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::IMPORT_CONNECTION_OPTION,
                null,
                InputOption::VALUE_REQUIRED,
                'Identifier of the connection to import Mirakl orders from'
            ),
            new InputOption(
                self::IMPORT_ALL_CONNECTIONS_OPTION,
                null,
                InputOption::VALUE_NONE,
                'Import all Mirakl orders of all connections'
            ),
        ];

        $this->setName('mirakl:seller:order-import')
            ->setDescription('Imports Mirakl orders in Magento')
            ->setDefinition($options);
    }

    /**
     * @param   Connection  $connection
     * @return  Process
     */
    private function importOrdersFromConnection(Connection $connection)
    {
        $process = $this->orderSync->synchronizeConnection($connection, Process::TYPE_CLI, Process::STATUS_IDLE);

        return $process->run(true);
    }

    /**
     * @return  $this
     */
    private function importOrdersFromAllConnections()
    {
        $processes = $this->orderSync->synchronizeAllConnections(Process::TYPE_CLI, Process::STATUS_IDLE);

        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run(true);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(Area::AREA_ADMINHTML);

        if (!$this->config->isAutoOrdersImport()) {
            throw new \ErrorException(__('Auto import is disabled. Please verify Mirakl configuration in Magento.'));
        }

        if ($input->getOption('all')) {
            $output->writeln('<info>All Mirakl orders will be imported from all connections</info>');
            $this->importOrdersFromAllConnections();
        } elseif ($connectionId = $input->getOption('connection')) {
            $connection = $this->getConnectionById($connectionId);
            $this->importOrdersFromConnection($connection);
        } else {
            $output->writeln('<error>Please provide an option or use help</error>');
        }

        return cli::RETURN_SUCCESS;
    }

    /**
     * Retrieves Mirakl connection by specified id
     *
     * @param   int $connectionId
     * @return  Connection
     * @throws  NoSuchEntityException
     */
    private function getConnectionById($connectionId)
    {
        $connection = $this->connectionFactory->create();
        $this->connectionResourceFactory->create()->load($connection, $connectionId);

        if (!$connection->getId()) {
            throw new NoSuchEntityException(__("Could not find connection with id '%1'", $connectionId));
        }

        return $connection;
    }
}
