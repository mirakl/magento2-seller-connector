<?php
namespace MiraklSeller\Core\Console\Command;

use Magento\Framework\Console\Cli;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\Connection as ConnectionResource;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory as ConnectionCollectionFactory;
use MiraklSeller\Core\Helper\Connection as ConnectionHelper;
use MiraklSeller\Core\Helper\Tracking as TrackingHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Process\Model\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TrackingUpdateCommand extends Command
{
    /**
     * options key
     */
    const TYPE_OPTION         = 'type';
    const LISTING_OPTION      = 'listing';
    const CONNECTION_OPTION   = 'connection';
    const ALL_OPTION          = 'all';

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var ConnectionResource
     */
    private $connectionResource;

    /**
     * @var ConnectionCollectionFactory
     */
    private $connectionCollectionFactory;

    /**
     * @var ConnectionHelper
     */
    private $connectionHelper;

    /**
     * @var TrackingHelper
     */
    private $trackingHelper;

    /**
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResource          $connectionResource
     * @param   ConnectionCollectionFactory $connectionCollectionFactory
     * @param   ConnectionHelper            $connectionHelper
     * @param   TrackingHelper              $trackingHelper
     * @param   string|null                 $name
     */
    public function __construct(
        ConnectionFactory $connectionFactory,
        ConnectionResource $connectionResource,
        ConnectionCollectionFactory $connectionCollectionFactory,
        ConnectionHelper $connectionHelper,
        TrackingHelper $trackingHelper,
        $name = null
    ) {
        parent::__construct($name);
        $this->connectionFactory = $connectionFactory;
        $this->connectionResource = $connectionResource;
        $this->connectionCollectionFactory = $connectionCollectionFactory;
        $this->connectionHelper = $connectionHelper;
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::TYPE_OPTION,
                't',
                InputOption::VALUE_REQUIRED,
                'Determine which export is made. Available values: ALL (default), PRODUCT, OFFER.'
            ),
            new InputOption(
                self::LISTING_OPTION,
                'l',
                InputOption::VALUE_REQUIRED,
                'Identifier of the listing'
            ),
            new InputOption(
                self::CONNECTION_OPTION,
                'c',
                InputOption::VALUE_REQUIRED,
                'Identifier of the connection'
            ),
            new InputOption(
                self::ALL_OPTION,
                null,
                InputOption::VALUE_NONE,
                'Export all active listings'
            ),
        ];

        $this->setName('mirakl:seller:tracking-update')
            ->setDescription('Handles tracking update to Mirakl')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::ALL_OPTION)) {
            $this->updateAllListingTrackings($input, $output);
        } else {
            if ($listing = $input->getOption(self::LISTING_OPTION)) {
                try {
                    $this->updateListingTrackings($input, $output, $listing);
                } catch (\Exception $e) {
                    throw new \Exception('An exception has been thrown: ' . $e->getMessage());
                }

            } elseif ($connection = $input->getOption(self::CONNECTION_OPTION)) {
                try {
                    $this->updateConnectionListingTrackings($input, $output, $connection);
                } catch (\Exception $e) {
                    throw new \Exception('An exception has been thrown: ' . $e->getMessage());
                }

            } else {
                $this->fault($output, sprintf(
                    'The parameter %s | %s | %s is required',
                    self::LISTING_OPTION,
                    self::CONNECTION_OPTION,
                    self::ALL_OPTION
                ));
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @param   int             $listingId
     * @return  $this
     */
    protected function updateListingTrackings(InputInterface $input, OutputInterface $output, $listingId)
    {
        $processes = $this->trackingHelper->updateListingTrackingsByType(
            $listingId, $this->getType($input, $output), Process::TYPE_CLI
        );

        /** @var Process $process */
        foreach ($processes as $process) {
            if (!$input->getOption('quiet')) {
                $process->addOutput('cli');
            }
            $process->run();
        }

        return $this;
    }

    /**
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @param   int|Connection  $connection
     * @return  $this
     */
    protected function updateConnectionListingTrackings(InputInterface $input, OutputInterface $output, $connection)
    {
        if (!$connection instanceof Connection) {
            $connectionId = $connection;
            $connection = $this->connectionFactory->create();
            $this->connectionResource->load($connection, $connectionId);
        }

        $output->writeln(sprintf('Connection %s (%s) will be treated', $connection->getName(), $connection->getId()));
        $listings = $this->connectionHelper->getActiveListings($connection);

        if ($listings->count() > 0) {
            foreach ($listings as $listing) {
                $output->writeln(sprintf(' --> Active listing %s (%s) will be treated', $listing->getName(), $listing->getId()));
                $this->updateListingTrackings($input, $output, $listing->getId());
            }
        } else {
            $output->writeln('No active listing associated with this connection');
        }

        return $this;
    }

    /**
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @return  $this
     */
    protected function updateAllListingTrackings(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('All active listings will be treated for all connections');

        $activeConnections =  $this->connectionCollectionFactory->create()
            ->setOrder('name', 'ASC');

        foreach ($activeConnections as $activeConnection) {
            $this->updateConnectionListingTrackings($input, $output, $activeConnection);
        }

        return $this;
    }

    /**
     * @param   OutputInterface $output
     * @param   string          $message
     */
    protected function fault(OutputInterface $output, $message)
    {
        $output->writeln(sprintf(
            '<error>%s</error>',
            $output->getFormatter()->escape($message)
        ));
    }

    /**
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @return  string
     */
    protected function getType(InputInterface  $input, OutputInterface $output)
    {
        $type = $input->getOption(self::TYPE_OPTION);

        if (empty($type)) {
            $type = Listing::TYPE_ALL;
        }

        $allowedTypes = Listing::getAllowedTypes();

        if (!in_array($type, $allowedTypes)) {
            $this->fault($output, 'Available types are: ' . implode(', ', $allowedTypes));
        }

        return $type;
    }
}
