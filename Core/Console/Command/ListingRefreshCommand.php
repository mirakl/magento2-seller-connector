<?php
namespace MiraklSeller\Core\Console\Command;

use Magento\Framework\Console\Cli;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\Connection as ConnectionResource;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory as ConnectionCollectionFactory;
use MiraklSeller\Core\Helper\Connection as ConnectionHelper;
use MiraklSeller\Core\Helper\Listing as ListingHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\ListingFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing as ListingResource;
use MiraklSeller\Process\Model\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListingRefreshCommand extends Command
{
    /**
     * options key
     */
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
     * @var ListingFactory
     */
    private $listingFactory;

    /**
     * @var ListingResource
     */
    private $listingResource;

    /**
     * @var ListingHelper
     */
    private $listingHelper;

    /**
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResource          $connectionResource
     * @param   ConnectionCollectionFactory $connectionCollectionFactory
     * @param   ConnectionHelper            $connectionHelper
     * @param   ListingFactory              $listingFactory
     * @param   ListingResource             $listingResource
     * @param   ListingHelper               $listingHelper
     * @param   string|null                 $name
     */
    public function __construct(
        ConnectionFactory $connectionFactory,
        ConnectionResource $connectionResource,
        ConnectionCollectionFactory $connectionCollectionFactory,
        ConnectionHelper $connectionHelper,
        ListingFactory $listingFactory,
        ListingResource $listingResource,
        ListingHelper $listingHelper,
        $name = null
    ) {
        parent::__construct($name);
        $this->connectionFactory = $connectionFactory;
        $this->connectionResource = $connectionResource;
        $this->connectionCollectionFactory = $connectionCollectionFactory;
        $this->connectionHelper = $connectionHelper;
        $this->listingFactory = $listingFactory;
        $this->listingResource = $listingResource;
        $this->listingHelper = $listingHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
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

        $this->setName('mirakl:seller:listing-refresh')
            ->setDescription('Handles listing refresh')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::ALL_OPTION)) {
            $this->refreshAll($input, $output);
        } else {
            if ($listing = $input->getOption(self::LISTING_OPTION)) {
                try {
                    $this->refreshListing($input, $output, $listing);
                } catch (\Exception $e) {
                    throw new \Exception('An exception has been thrown: ' . $e->getMessage());
                }

            } elseif ($connection = $input->getOption(self::CONNECTION_OPTION)) {
                try {
                    $this->refreshConnection($input, $output, $connection);
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
     * @param   int|Listing     $listing
     * @return  $this
     */
    protected function refreshListing(InputInterface $input, OutputInterface $output, $listing)
    {
        if (!$listing instanceof Listing) {
            $listingId = $listing;
            $listing = $this->listingFactory->create();
            $this->listingResource->load($listing, $listingId);
        }

        $process = $this->listingHelper->refresh($listing, Process::TYPE_CLI);

        if (!$input->getOption('quiet')) {
            $process->addOutput('cli');
        }
        $process->run();

        return $this;
    }

    /**
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @param   int|Connection  $connection
     * @return  $this
     */
    protected function refreshConnection(InputInterface $input, OutputInterface $output, $connection)
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
                $this->refreshListing($input, $output, $listing);
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
    protected function refreshAll(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('All active listings will be treated for all connections');

        $activeConnections =  $this->connectionCollectionFactory->create()
            ->setOrder('name', 'ASC');

        foreach ($activeConnections as $activeConnection) {
            $this->refreshConnection($input, $output, $activeConnection);
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
}
