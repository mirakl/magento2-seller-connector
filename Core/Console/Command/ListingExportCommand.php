<?php
namespace MiraklSeller\Core\Console\Command;

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

class ListingExportCommand extends Command
{
    /**
     * options key
     */
    const TYPE_OPTION         = 'type';
    const LISTING_OPTION      = 'listing';
    const CONNECTION_OPTION   = 'connection';
    const ALL_OPTION          = 'all';
    const OFFER_DELTA_OPTION  = 'offer-delta';
    const PRODUCT_MODE_OPTION = 'product-mode';

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
            new InputOption(
                self::OFFER_DELTA_OPTION,
                'o',
                InputOption::VALUE_NONE,
                'Export only modified prices & stocks (for type OFFER only)'
            ),
            new InputOption(
                self::PRODUCT_MODE_OPTION,
                'p',
                InputOption::VALUE_REQUIRED,
                'Determine which products will be exported: PENDING (default), ERROR, ALL (for type PRODUCT only)'
            ),
        ];

        $this->setName('mirakl:seller:listing-export')
            ->setDescription('Handles listing export to Mirakl')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::ALL_OPTION)) {
            $this->exportAll($input, $output);
        } else {
            if ($listing = $input->getOption(self::LISTING_OPTION)) {
                try {
                    $this->exportListing($input, $output, $listing);
                } catch (\Exception $e) {
                    throw new \Exception('An exception has been thrown: ' . $e->getMessage());
                }

            } elseif ($connection = $input->getOption(self::CONNECTION_OPTION)) {
                try {
                    $this->exportConnection($input, $output, $connection);
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
    }

    /**
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @param   int|Listing     $listing
     * @return  $this
     */
    protected function exportListing(InputInterface $input, OutputInterface $output, $listing)
    {
        if (!$listing instanceof Listing) {
            $listingId = $listing;
            $listing = $this->listingFactory->create();
            $this->listingResource->load($listing, $listingId);
        }

        $offerFull = !$input->getOption(self::OFFER_DELTA_OPTION);
        $productMode = $this->getProductMode($input, $output);

        $processes = $this->listingHelper->export(
            $listing, $this->getType($input, $output), $offerFull, $productMode, Process::TYPE_CLI
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
    protected function exportConnection(InputInterface $input, OutputInterface $output, $connection)
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
                $this->exportListing($input, $output, $listing);
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
    protected function exportAll(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('All active listings will be treated for all connections');

        $activeConnections =  $this->connectionCollectionFactory->create()
            ->setOrder('name', 'ASC');

        foreach ($activeConnections as $activeConnection) {
            $this->exportConnection($input, $output, $activeConnection);
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
    protected function getProductMode(InputInterface $input, OutputInterface $output)
    {
        $mode = $input->getOption(self::PRODUCT_MODE_OPTION);

        if (empty($mode)) {
            $mode = Listing::PRODUCT_MODE_PENDING;
        }

        $allowedModes = Listing::getAllowedProductModes();

        if (!in_array($mode, $allowedModes)) {
            $this->fault($output, 'Available product modes are: ' . implode(', ', $allowedModes));
        }

        return $mode;
    }

    /**
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @return  string
     */
    protected function getType(InputInterface $input, OutputInterface $output)
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
