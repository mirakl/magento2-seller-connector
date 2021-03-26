<?php
namespace MiraklSeller\Sales\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Sales\Helper\Order\Accept as OrderAccept;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Sales\Helper\Config;

class AcceptCommand extends Command
{
    /**
     * Accept orders from specific connection
     */
    const ACCEPT_CONNECTION_OPTION = 'connection';

    /**
     * Accept orders from specific all connections
     */
    const ACCEPT_ALL_CONNECTIONS_OPTION = 'all';

    /**
     * @var State
     */
    private $appState;

    /**
     * @var OrderAccept
     */
    private $orderAccept;

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
     * @param   OrderAccept                 $orderAccept
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   Config                      $config
     * @param   string|null                 $name
     */
    public function __construct(
        State $state,
        OrderAccept $orderAccept,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        Config $config,
        $name = null
    ) {
        parent::__construct($name);

        $this->appState = $state;
        $this->orderAccept = $orderAccept;
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
                self::ACCEPT_CONNECTION_OPTION,
                null,
                InputOption::VALUE_REQUIRED,
                'Identifier of the connection to accept Mirakl orders from'
            ),
            new InputOption(
                self::ACCEPT_ALL_CONNECTIONS_OPTION,
                null,
                InputOption::VALUE_NONE,
                'Accept all Mirakl orders of all connections'
            ),
        ];

        $this->setName('mirakl:seller:order-accept')
            ->setDescription('Accepts Mirakl orders in Magento')
            ->setDefinition($options);
    }

    /**
     * @param   Connection  $connection
     * @return  Process
     */
    private function acceptOrdersFromConnection(Connection $connection)
    {
        $process = $this->orderAccept->acceptConnection($connection, Process::TYPE_CLI, Process::STATUS_IDLE);

        return $process->run(true);
    }

    /**
     * @return  $this
     */
    private function acceptOrdersFromAllConnections()
    {
        $processes = $this->orderAccept->acceptAll(Process::TYPE_CLI, Process::STATUS_IDLE);

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

        if (!$this->config->isAutoAcceptOrdersEnabled()) {
            throw new \ErrorException(__('Auto acceptance is disabled. Please verify Mirakl configuration in Magento.'));
        }

        if ($input->getOption('all')) {
            $output->writeln('<info>All Mirakl orders will be accepted from all connections</info>');
            $this->acceptOrdersFromAllConnections();
        } elseif ($connectionId = $input->getOption('connection')) {
            $connection = $this->getConnectionById($connectionId);
            $this->acceptOrdersFromConnection($connection);
        } else {
            $output->writeln('<error>Please provide an option or use help</error>');
        }
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
