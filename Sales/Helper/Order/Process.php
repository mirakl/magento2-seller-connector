<?php
namespace MiraklSeller\Sales\Helper\Order;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Process\Model\Process as ProcessModel;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Helper\Order\Import as OrderImportHelper;
use MiraklSeller\Sales\Model\Synchronize\Order as OrderSynchronizer;

class Process extends AbstractHelper
{
    /**
     * @var ApiOrder
     */
    protected $apiOrder;

    /**
     * @var OrderSynchronizer
     */
    protected $synchronizeOrder;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderImportHelper
     */
    protected $orderImportHelper;

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @param   Context                     $context
     * @param   ApiOrder                    $apiOrder
     * @param   OrderSynchronizer           $synchronizeOrder
     * @param   OrderHelper                 $orderHelper
     * @param   OrderImportHelper           $orderImportHelper
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     */
    public function __construct(
        Context $context,
        ApiOrder $apiOrder,
        OrderSynchronizer $synchronizeOrder,
        OrderHelper $orderHelper,
        OrderImportHelper $orderImportHelper,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory
    ) {
        parent::__construct($context);

        $this->apiOrder                  = $apiOrder;
        $this->synchronizeOrder          = $synchronizeOrder;
        $this->orderHelper               = $orderHelper;
        $this->orderImportHelper         = $orderImportHelper;
        $this->connectionFactory         = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
    }

    /**
     * Synchronize or import all Mirakl orders from specified
     * Mirakl connection using the last synchronization date field.
     *
     * @param   ProcessModel    $process
     * @param   int             $connectionId
     * @return  ProcessModel
     */
    public function synchronizeConnection(ProcessModel $process, $connectionId)
    {
        $connection = $this->getConnectionById($connectionId);

        if (!$connection->getId()) {
            return $process->fail(__("Could not find connection with id '%1'", $connectionId));
        }

        $process->output(__(
            "Importing Mirakl orders of connection '%1' (id: %2) ...",
            $connection->getName(),
            $connection->getId()
        ));

        $params = [];
        if ($lastSyncDate = $connection->getLastOrdersSynchronizationDate()) {
            $updatedSince = new \DateTime($lastSyncDate);
            $params['start_update_date'] = $updatedSince->format(\DateTime::ISO8601);
            $process->output(__('=> fetching Mirakl orders modified since %1 only', $lastSyncDate));
        }

        $now = date('Y-m-d H:i:s');

        $miraklOrders = $this->apiOrder->getAllOrders($connection, $params);

        if (!$miraklOrders->count()) {
            return $process->output(__('No Mirakl order to import for this connection'));
        }

        /** @var ShopOrder $miraklOrder */
        foreach ($miraklOrders as $miraklOrder) {
            try {
                $process->output(__('Processing Mirakl order #%1 ...', $miraklOrder->getId()));
                $this->synchronizeMiraklOrder($process, $connection, $miraklOrder);
            } catch (\Exception $e) {
                $process->output(__('ERROR: %1', $e->getMessage()));
            }
        }

        $connection->setLastOrdersSynchronizationDate($now);
        $this->connectionResourceFactory->create()->save($connection);

        return $process;
    }

    /**
     * Retrieves Mirakl connection by specified id
     *
     * @param   int $connectionId
     * @return  Connection
     */
    protected function getConnectionById($connectionId)
    {
        $connection = $this->connectionFactory->create();
        $this->connectionResourceFactory->create()->load($connection, $connectionId);

        return $connection;
    }

    /**
     * Synchronizes the Mirakl order with Magento if already imported or import it otherwise
     *
     * @param   ProcessModel    $process
     * @param   Connection      $connection
     * @param   ShopOrder       $miraklOrder
     * @return  ProcessModel
     */
    protected function synchronizeMiraklOrder(ProcessModel $process, Connection $connection, ShopOrder $miraklOrder)
    {
        if ($order = $this->orderHelper->getOrderByMiraklOrderId($miraklOrder->getId())) {
            // Synchronize Magento order if already imported
            if ($this->synchronizeOrder->synchronize($order, $miraklOrder, $connection)) {
                $process->output(__('Mirakl order has been synchronized with Magento'));
            } else {
                $process->output(__('Mirakl order is already up to date in Magento'));
            }
        } elseif ($this->orderHelper->canImport($miraklOrder->getStatus()->getState())) {
            // Import Mirakl order if possible
            $this->orderImportHelper->importMiraklOrder($connection, $miraklOrder);
            $process->output(__('Mirakl order has been imported in Magento'));
        } else {
            $process->output(__('Nothing to do with this Mirakl order'));
        }

        return $process;
    }
}
